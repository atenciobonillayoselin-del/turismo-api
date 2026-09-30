<?php
// api/_auth.php
// ------------------------------------------------------------------
// Utilidades compartidas de autenticación por token.
//
// PROBLEMA QUE RESUELVE: detrás del proxy de Render el header llega como
// "authorization" (minúscula) o solo en $_SERVER['HTTP_AUTHORIZATION'].
// Los endpoints que hacían isset($headers['Authorization']) NO veían el
// token, respondían "Token no proporcionado" y la app caía a datos viejos
// (Firestore) o cerraba la sesión.
// ------------------------------------------------------------------

function obtenerTokenDeRequest(): ?string
{
    $auth = null;

    if (function_exists('getallheaders')) {
        foreach (getallheaders() as $k => $v) {
            $kl = strtolower($k);
            if ($kl === 'authorization' || $kl === 'x-auth-token') {
                $auth = $v;
                break;
            }
        }
    }
    if (!$auth && !empty($_SERVER['HTTP_AUTHORIZATION'])) {
        $auth = $_SERVER['HTTP_AUTHORIZATION'];
    }
    if (!$auth && !empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        $auth = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
    }
    if (!$auth && !empty($_SERVER['HTTP_X_AUTH_TOKEN'])) {
        $auth = $_SERVER['HTTP_X_AUTH_TOKEN'];
    }
    if (!$auth) {
        return null;
    }

    $token = trim(preg_replace('/^Bearer\s+/i', '', trim($auth)));
    return $token !== '' ? $token : null;
}

/**
 * Devuelve la fila del usuario dueño del token (o false si no es válido).
 * Renueva la sesión de forma deslizante: cada uso válido extiende 30 días,
 * así el usuario no vuelve a iniciar sesión mientras use la app.
 */
function usuarioDesdeToken(PDO $pdo, string $token)
{
    $stmt = $pdo->prepare(
        "SELECT u.*
           FROM usuario_sesion s
           JOIN usuario u ON s.id_usuario = u.id_usuario
          WHERE s.token = ? AND s.activo = 1
            AND s.fecha_expiracion > NOW() AND u.activo = 1"
    );
    $stmt->execute([$token]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario) {
        try {
            $pdo->prepare(
                "UPDATE usuario_sesion
                    SET fecha_expiracion = DATE_ADD(NOW(), INTERVAL 30 DAY)
                  WHERE token = ?"
            )->execute([$token]);
        } catch (PDOException $e) { /* no crítico */ }
    }
    return $usuario;
}

function usuarioComoJson(array $u): array
{
    return [
        'id'              => (int)$u['id_usuario'],
        'email'           => $u['email'],
        'nombre'          => $u['nombre'],
        'rol'             => $u['rol'],
        'firebase_uid'    => $u['firebase_uid'] ?? '',
        'foto_perfil'     => ($u['foto_perfil'] ?? '') ?: ($u['photo_url'] ?? ''),
        'photo_url'       => ($u['photo_url'] ?? '') ?: ($u['foto_perfil'] ?? ''),
        'telefono'        => $u['telefono'] ?? '',
        'carnet'          => $u['carnet'] ?? '',
        'perfil_completo' => (int)($u['perfil_completo'] ?? 0),
        'updated_at'      => $u['updated_at'] ?? null,
    ];
}
