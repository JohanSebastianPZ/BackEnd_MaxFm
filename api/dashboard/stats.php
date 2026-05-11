<?php

require_once "../../config/cors.php";
require_once "../../config/database.php";
require_once "../../config/auth.php";

configurarCORS();
requireAuth();

$db = conectarDB();
// Lanzar excepciones para detectar errores de query en lugar de retornar false
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Migraciones automáticas
$db->exec("CREATE TABLE IF NOT EXISTS visitas (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    ip         TEXT NOT NULL,
    fecha      DATE NOT NULL,
    creado_en  DATETIME DEFAULT CURRENT_TIMESTAMP
)");
try { $db->exec("ALTER TABLE usuarios ADD COLUMN last_login_ip TEXT"); } catch (PDOException $e) {}

try {

// ── 1. Contadores de contenido ─────────────────────────────────────────────
// Columnas verificadas contra la BD real:
//   locutores    → tiene activo
//   programas    → tiene activo
//   top5_canciones → NO tiene activo (se cuentan todas)
//   noticias     → NO tiene estado (se cuentan todas)

$locutores = (int) $db->query("SELECT COUNT(*) FROM locutores WHERE activo = 1")->fetchColumn();
$programas = (int) $db->query("SELECT COUNT(*) FROM programas WHERE activo = 1")->fetchColumn();
$canciones = (int) $db->query("SELECT COUNT(*) FROM top5_canciones")->fetchColumn();
$noticias  = (int) $db->query("SELECT COUNT(*) FROM noticias")->fetchColumn();

// ── 2. Visitas (excluir IPs de admin) ─────────────────────────────────────

$admin_ips = $db->query(
    "SELECT DISTINCT last_login_ip FROM usuarios WHERE last_login_ip IS NOT NULL AND last_login_ip != ''"
)->fetchAll(PDO::FETCH_COLUMN);

$hoy  = date('Y-m-d');
$ayer = date('Y-m-d', strtotime('-1 day'));

$visitas_hoy  = _contarVisitas($db, $hoy,  $admin_ips);
$visitas_ayer = _contarVisitas($db, $ayer, $admin_ips);
$variacion    = $visitas_ayer > 0
    ? round((($visitas_hoy - $visitas_ayer) / $visitas_ayer) * 100, 1)
    : null;

// ── 3. Estado de la base de datos ─────────────────────────────────────────
$bd_ok = true; // Si llegamos aquí, la conexión funciona

// ── 4. Estado del stream de audio ─────────────────────────────────────────

$stream_ok    = false;
$stream_label = 'URL no configurada';
$stream_url   = '';

$row_cfg = $db->query("SELECT url_streaming FROM config_general LIMIT 1")->fetch(PDO::FETCH_ASSOC);
if ($row_cfg && !empty($row_cfg['url_streaming'])) {
    $stream_url = $row_cfg['url_streaming'];
    if (function_exists('curl_init')) {

        // Verificación real de emisión:
        // Hacemos un GET (no HEAD) y leemos el Content-Type de la respuesta.
        // Si hay audio transmitiéndose, el servidor responde con audio/mpeg, audio/ogg, etc.
        // Si el servidor está online pero sin fuente conectada, devuelve 404 o text/html.
        // Cortamos la descarga en el primer byte de cuerpo (WRITEFUNCTION → -1)
        // para no descargar el stream completo.

        $content_type = '';
        $ch = curl_init($stream_url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_TIMEOUT        => 6,
            CURLOPT_CONNECTTIMEOUT => 4,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT      => 'MaxFM-Dashboard/1.0',
            // Captura el Content-Type de las cabeceras de respuesta
            CURLOPT_HEADERFUNCTION => function ($curl, $header) use (&$content_type) {
                if (stripos($header, 'Content-Type:') === 0) {
                    $content_type = trim(substr($header, 13));
                }
                return strlen($header);
            },
            // Para en cuanto recibe el primer chunk de body (ya tenemos las cabeceras)
            CURLOPT_WRITEFUNCTION  => function ($curl, $data) {
                return -1; // Abortar descarga — genera CURLE_WRITE_ERROR (23), ignoramos ese errno
            },
        ]);
        curl_exec($ch);
        $http_code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Content-Type de audio real: audio/mpeg, audio/ogg, audio/aac, application/ogg, etc.
        $es_audio  = (bool) preg_match('#(audio/|application/ogg|video/ogg)#i', $content_type);
        $stream_ok = ($http_code === 200 && $es_audio);

        if ($stream_ok) {
            $stream_label = 'En emisión';
        } elseif ($http_code > 0) {
            $stream_label = 'Servidor online · sin emisión (HTTP ' . $http_code . ')';
        } else {
            $stream_label = 'Sin conexión al servidor';
        }

    } else {
        $stream_label = 'No verificable (sin curl)';
    }
}

// ── 5. Estado del chat ────────────────────────────────────────────────────

$dir_chat        = dirname(__DIR__) . '/message/';
$arch_msgs       = $dir_chat . 'chat_messages.json';
$arch_heartbeat  = $dir_chat . 'bot_heartbeat.json';

$msgs_count = 0;
if (file_exists($arch_msgs) && is_readable($arch_msgs)) {
    $decoded    = json_decode(@file_get_contents($arch_msgs), true);
    $msgs_count = is_array($decoded) ? count($decoded) : 0;
}

// El bot escribe bot_heartbeat.json cada 3 s incondicionalmente.
// Si lleva más de 15 s sin actualizarse, está parado o tuvo un error.
$bot_ok    = false;
$bot_label = 'Detenido';
if (file_exists($arch_heartbeat)) {
    $segundos = time() - filemtime($arch_heartbeat);
    if ($segundos < 15) {
        $bot_ok    = true;
        $bot_label = 'Activo';
    } else {
        $bot_label = 'Parado (' . $segundos . ' s sin señal)';
    }
}

$chat_cfg_row = $db->query("SELECT visible FROM chat_config LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$chat_visible = $chat_cfg_row ? (bool) $chat_cfg_row['visible'] : false;

// ── Respuesta ──────────────────────────────────────────────────────────────

echo json_encode([
    'success' => true,
    'stats' => [
        'locutores_activos'   => $locutores,
        'programas_activos'   => $programas,
        'canciones_top'       => $canciones,
        'noticias_publicadas' => $noticias,
        'visitas_hoy'         => $visitas_hoy,
        'visitas_ayer'        => $visitas_ayer,
        'variacion'           => $variacion,
    ],
    'estado' => [
        'base_datos' => ['ok' => $bd_ok,    'label' => 'Conectada · SQLite'],
        'stream'     => ['ok' => $stream_ok, 'label' => $stream_label, 'url' => $stream_url],
        'chat'       => [
            'visible'    => $chat_visible,
            'mensajes'   => $msgs_count,
            'bot_activo' => $bot_ok,
            'bot_label'  => $bot_label,
        ],
    ],
]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error de base de datos: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno: ' . $e->getMessage()]);
}

// ── Helper ────────────────────────────────────────────────────────────────

function _contarVisitas(PDO $db, string $fecha, array $exclude): int {
    if (count($exclude) > 0) {
        $ph   = implode(',', array_fill(0, count($exclude), '?'));
        $stmt = $db->prepare("SELECT COUNT(DISTINCT ip) FROM visitas WHERE fecha = ? AND ip NOT IN ($ph)");
        $stmt->execute(array_merge([$fecha], $exclude));
    } else {
        $stmt = $db->prepare("SELECT COUNT(DISTINCT ip) FROM visitas WHERE fecha = ?");
        $stmt->execute([$fecha]);
    }
    return (int) $stmt->fetchColumn();
}
