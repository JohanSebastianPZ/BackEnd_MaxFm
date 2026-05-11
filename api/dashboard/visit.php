<?php

require_once "../../config/cors.php";
require_once "../../config/database.php";

configurarCORS();

// Obtener IP real (soporte para proxies)
$ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
if (strpos($ip, ',') !== false) {
    $ip = trim(explode(',', $ip)[0]);
}
$ip = filter_var($ip, FILTER_VALIDATE_IP) ?: '';

if (!$ip) {
    echo json_encode(['success' => false, 'message' => 'IP inválida']);
    exit;
}

$db = conectarDB();

// Migraciones automáticas
$db->exec("CREATE TABLE IF NOT EXISTS visitas (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    ip         TEXT NOT NULL,
    fecha      DATE NOT NULL,
    creado_en  DATETIME DEFAULT CURRENT_TIMESTAMP
)");
try { $db->exec("ALTER TABLE usuarios ADD COLUMN last_login_ip TEXT"); } catch (PDOException $e) {}

// Excluir IPs de administradores
$admin_ips = $db->query(
    "SELECT DISTINCT last_login_ip FROM usuarios WHERE last_login_ip IS NOT NULL AND last_login_ip != ''"
)->fetchAll(PDO::FETCH_COLUMN);

if (in_array($ip, $admin_ips)) {
    echo json_encode(['success' => true, 'counted' => false, 'reason' => 'admin_ip']);
    exit;
}

// Una sola visita por IP y día
$fecha = date('Y-m-d');
$stmt = $db->prepare("SELECT COUNT(*) FROM visitas WHERE ip = ? AND fecha = ?");
$stmt->execute([$ip, $fecha]);

if ($stmt->fetchColumn() == 0) {
    $db->prepare("INSERT INTO visitas (ip, fecha) VALUES (?, ?)")->execute([$ip, $fecha]);
    echo json_encode(['success' => true, 'counted' => true]);
} else {
    echo json_encode(['success' => true, 'counted' => false, 'reason' => 'already_counted']);
}
