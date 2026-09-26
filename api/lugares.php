<?php
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    $sql = "SELECT lt.id_lugar, lt.nombre, lt.descripcion, lt.descripcion_corta, lt.latitud, lt.longitud,
                   lt.direccion, lt.calificacion, lt.costo, lt.es_gratuito, lt.condiciones_gratis, lt.tabla_precios,
                   lt.abierto_todos_los_dias, lt.horarios, lt.tipo_transporte,
                   lt.activo, lt.created_at, lt.updated_at, lt.id_categoria,
                   cl.nombre as categoria, cl.slug as categoria_slug, cl.icono as categoria_icono
            FROM lugar_turistico lt
            LEFT JOIN categoria_lugar cl ON lt.id_categoria = cl.id_categoria
            WHERE lt.activo = 1
            ORDER BY lt.id_lugar ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $lugares = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Obtener multimedia para cada lugar
    foreach ($lugares as &$lugar) {
        $lugarId = $lugar['id_lugar'];

        // Obtener imágenes normales
        $stmtImagenes = $pdo->prepare("
            SELECT url, descripcion, orden
            FROM lugar_multimedia
            WHERE id_lugar = ? AND tipo = 'imagen' AND activo = 1
            ORDER BY orden ASC
        ");
        $stmtImagenes->execute([$lugarId]);
        $imagenes = $stmtImagenes->fetchAll(PDO::FETCH_ASSOC);
        $lugar['imagenes'] = array_column($imagenes, 'url');

        // Obtener imágenes 360
        $stmt360 = $pdo->prepare("
            SELECT url, descripcion, orden
            FROM lugar_multimedia
            WHERE id_lugar = ? AND tipo = '360' AND activo = 1
            ORDER BY orden ASC
        ");
        $stmt360->execute([$lugarId]);
        $imagenes360 = $stmt360->fetchAll(PDO::FETCH_ASSOC);
        $lugar['imagenes360'] = array_column($imagenes360, 'url');

        // Obtener modelos 3D
        $stmt3d = $pdo->prepare("
            SELECT url, descripcion, orden
            FROM lugar_multimedia
            WHERE id_lugar = ? AND tipo = '3d' AND activo = 1
            ORDER BY orden ASC
        ");
        $stmt3d->execute([$lugarId]);
        $modelos3d = $stmt3d->fetchAll(PDO::FETCH_ASSOC);
        $lugar['modelos3d'] = array_column($modelos3d, 'url');

        // Obtener audios
        $stmtAudio = $pdo->prepare("
            SELECT url, descripcion, idioma, duracion_seg, orden
            FROM lugar_multimedia
            WHERE id_lugar = ? AND tipo = 'audio' AND activo = 1
            ORDER BY orden ASC
        ");
        $stmtAudio->execute([$lugarId]);
        $audios = $stmtAudio->fetchAll(PDO::FETCH_ASSOC);
        $lugar['audios'] = $audios;

        // Obtener videos
        $stmtVideo = $pdo->prepare("
            SELECT url, descripcion, orden
            FROM lugar_multimedia
            WHERE id_lugar = ? AND tipo = 'video' AND activo = 1
            ORDER BY orden ASC
        ");
        $stmtVideo->execute([$lugarId]);
        $videos = $stmtVideo->fetchAll(PDO::FETCH_ASSOC);
        $lugar['videos'] = array_column($videos, 'url');

        // Campos legacy para compatibilidad con app Flutter existente
        $lugar['foto_url'] = !empty($lugar['imagenes']) ? $lugar['imagenes'][0] : null;
        $lugar['panorama_url'] = !empty($lugar['imagenes360']) ? $lugar['imagenes360'][0] : null;
        $lugar['fotos'] = $lugar['imagenes'];
    }
    unset($lugar);

    $iconosDefault = [
        'mirador'    => 'landmark',
        'museo'      => 'museum',
        'parque'     => 'park',
        'plaza'      => 'town-hall',
        'iglesia'    => 'religious-christian',
        'naturaleza' => 'garden',
        'mercado'    => 'shop',
    ];

    foreach ($lugares as &$lugar) {
        // Asegurar que campos opcionales tengan valores por defecto
        if (!isset($lugar['descripcion_corta'])) {
            $lugar['descripcion_corta'] = '';
        }
        if (!isset($lugar['calificacion'])) {
            $lugar['calificacion'] = 0.0;
        }
        if (!isset($lugar['costo'])) {
            $lugar['costo'] = 0.00;
        }
        if (!isset($lugar['es_gratuito'])) {
            $lugar['es_gratuito'] = 0;
        }
        if (!isset($lugar['abierto_todos_los_dias'])) {
            $lugar['abierto_todos_los_dias'] = 0;
        }
        if (!isset($lugar['horarios'])) {
            $lugar['horarios'] = null;
        }
        if (!isset($lugar['id_categoria'])) {
            $lugar['id_categoria'] = null;
        }

        // ✅ FIX GENERAL: Corregir lugares marcados como "abierto todos los días" que tienen horarios específicos
        // Solo debe ser abierto_todos_los_dias = true si realmente está abierto 24/7 (00:00-23:59 todos los días)
        if ($lugar['abierto_todos_los_dias'] == 1 && !empty($lugar['horarios'])) {
            $horarios = json_decode($lugar['horarios'], true);
            if (is_array($horarios)) {
                // Verificar si los horarios son realmente de 00:00-23:59 todos los días
                $esRealmente24_7 = true;
                foreach ($horarios as $horario) {
                    $horaApertura = $horario['hora_apertura'] ?? '';
                    $horaCierre = $horario['hora_cierre'] ?? '';
                    // Si no es 00:00-23:59, no es realmente abierto todos los días
                    if ($horaApertura !== '00:00' || $horaCierre !== '23:59') {
                        $esRealmente24_7 = false;
                        break;
                    }
                }
                // Si no es realmente 24/7, corregir el campo
                if (!$esRealmente24_7) {
                    $lugar['abierto_todos_los_dias'] = 0;
                }
            }
        }
    }
    unset($lugar);

    echo json_encode([
        'success' => true,
        'total'   => count($lugares),
        'data'    => $lugares,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
