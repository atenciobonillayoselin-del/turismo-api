<?php
/**
 * reestructurar_base_datos.php - Reestructura la tabla lugar_turistico para coincidir con Laravel
 * ⚠️ USAR CON PRECAUCIÓN - MODIFICA LA ESTRUCTURA DE LA BASE DE DATOS
 */

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Solo permitir método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Método no permitido. Use POST para reestructurar la base de datos.'
    ]);
    exit;
}

try {
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    $cambiosRealizados = [];
    $errores = [];
    
    // 1. Eliminar campos de uMap que no están en Laravel
    $camposAEliminar = ['grupo_umap', 'icono_umap', 'color_hex', 'uuid_capa', 'id_umap', 'panorama_url'];
    
    foreach ($camposAEliminar as $campo) {
        try {
            if (colExiste($pdo, 'lugar_turistico', $campo)) {
                $pdo->exec("ALTER TABLE lugar_turistico DROP COLUMN `$campo`");
                $cambiosRealizados[] = "Eliminado campo: $campo";
            }
        } catch (PDOException $e) {
            $errores[] = "Error al eliminar $campo: " . $e->getMessage();
        }
    }
    
    // 2. Renombrar categoria a id_categoria si existe
    try {
        if (colExiste($pdo, 'lugar_turistico', 'categoria') && !colExiste($pdo, 'lugar_turistico', 'id_categoria')) {
            $pdo->exec("ALTER TABLE lugar_turistico CHANGE categoria id_categoria INT NULL");
            $cambiosRealizados[] = "Renombrado categoria a id_categoria";
        }
    } catch (PDOException $e) {
        $errores[] = "Error al renombrar categoria: " . $e->getMessage();
    }
    
    // 3. Agregar campos faltantes del modelo Laravel
    $camposAAgregar = [
        'descripcion_corta' => "ALTER TABLE lugar_turistico ADD COLUMN descripcion_corta VARCHAR(255) NULL AFTER descripcion",
        'calificacion' => "ALTER TABLE lugar_turistico ADD COLUMN calificacion DECIMAL(2,1) NULL DEFAULT 0.0",
        'costo' => "ALTER TABLE lugar_turistico ADD COLUMN costo DECIMAL(10,2) NULL DEFAULT 0.00",
        'es_gratuito' => "ALTER TABLE lugar_turistico ADD COLUMN es_gratuito TINYINT(1) NULL DEFAULT 0",
        'abierto_todos_los_dias' => "ALTER TABLE lugar_turistico ADD COLUMN abierto_todos_los_dias TINYINT(1) NULL DEFAULT 0",
        'horarios' => "ALTER TABLE lugar_turistico ADD COLUMN horarios JSON NULL",
        'costo_nino' => "ALTER TABLE lugar_turistico ADD COLUMN costo_nino DECIMAL(10,2) NULL DEFAULT 0.00",
        'costo_adulto' => "ALTER TABLE lugar_turistico ADD COLUMN costo_adulto DECIMAL(10,2) NULL DEFAULT 0.00",
        'costo_tercera_edad' => "ALTER TABLE lugar_turistico ADD COLUMN costo_tercera_edad DECIMAL(10,2) NULL DEFAULT 0.00",
    ];
    
    foreach ($camposAAgregar as $campo => $sql) {
        try {
            if (!colExiste($pdo, 'lugar_turistico', $campo)) {
                $pdo->exec($sql);
                $cambiosRealizados[] = "Agregado campo: $campo";
            }
        } catch (PDOException $e) {
            $errores[] = "Error al agregar $campo: " . $e->getMessage();
        }
    }
    
    // 4. Crear tabla categoria_lugar si no existe
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS categoria_lugar (
            id_categoria INT AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(100) NOT NULL,
            descripcion TEXT NULL,
            activo TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");
        $cambiosRealizados[] = "Verificada tabla categoria_lugar";
    } catch (PDOException $e) {
        $errores[] = "Error al crear tabla categoria_lugar: " . $e->getMessage();
    }
    
    // 5. Insertar categorías por defecto si la tabla está vacía
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM categoria_lugar");
        $count = $stmt->fetchColumn();
        
        if ($count == 0) {
            $categorias = [
                ['Mirador', 'Puntos con vista panorámica'],
                ['Parque', 'Áreas verdes y parques urbanos'],
                ['Plaza', 'Plazas y espacios públicos'],
                ['Museo', 'Museos y centros culturales'],
                ['Iglesia', 'Templos religiosos'],
                ['Naturaleza', 'Sitios naturales y ecológicos'],
                ['Mercado', 'Mercados tradicionales'],
                ['Monumento', 'Monumentos históricos'],
            ];
            
            foreach ($categorias as $cat) {
                $stmt = $pdo->prepare("INSERT INTO categoria_lugar (nombre, descripcion) VALUES (?, ?)");
                $stmt->execute($cat);
            }
            $cambiosRealizados[] = "Insertadas categorías por defecto";
        }
    } catch (PDOException $e) {
        $errores[] = "Error al insertar categorías: " . $e->getMessage();
    }
    
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    echo json_encode([
        'success' => true,
        'mensaje' => 'Base de datos reestructurada exitosamente',
        'cambios_realizados' => $cambiosRealizados,
        'total_cambios' => count($cambiosRealizados),
        'errores' => $errores,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    try {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    } catch (Exception $e2) {
        // Ignorar error al reactivar restricciones
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], JSON_UNESCAPED_UNICODE);
}

function colExiste(PDO $pdo, string $tabla, string $col): bool {
    static $cache = [];
    $key = "$tabla.$col";
    if (isset($cache[$key])) return $cache[$key];
    try {
        $stmt = $pdo->prepare("SHOW COLUMNS FROM `$tabla` LIKE ?");
        $stmt->execute([$col]);
        $cache[$key] = (bool)$stmt->fetch();
        return $cache[$key];
    } catch (Throwable $e) {
        $cache[$key] = false;
        return false;
    }
}
