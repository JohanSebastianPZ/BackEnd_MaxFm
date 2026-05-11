<?php
require_once "../../config/cors.php";
require_once "../../config/database.php";
require_once "../../config/auth.php";

configurarCORS();
requireAuth();

$data = json_decode(file_get_contents('php://input'), true);
if (!$data || !isset($data['velocidad'])) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos.']);
    exit;
}

$db        = conectarDB();
$velocidad = max(2000, min(20000, (int)$data['velocidad']));

try { $db->exec("ALTER TABLE config_general ADD COLUMN hero_velocidad INTEGER DEFAULT 8500"); } catch (PDOException $e) {}

$row = $db->query("SELECT id FROM config_general LIMIT 1")->fetch(PDO::FETCH_ASSOC);
if ($row) {
    $u = $db->prepare("UPDATE config_general SET hero_velocidad = :v WHERE id = :id");
    $u->execute([':v' => $velocidad, ':id' => $row['id']]);
} else {
    $i = $db->prepare("INSERT INTO config_general (hero_velocidad) VALUES (:v)");
    $i->execute([':v' => $velocidad]);
}

echo json_encode(['success' => true, 'velocidad' => $velocidad]);
