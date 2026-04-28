<?php
require_once "../../config/cors.php";
require_once "../../config/database.php";
require_once "../../config/auth.php";

configurarCORS();
requireAuth();

$data = json_decode(file_get_contents('php://input'), true);
if (!$data || !isset($data['orden'])) { echo json_encode(['success' => false]); exit; }

$db   = conectarDB();
$stmt = $db->prepare("UPDATE eventos SET orden=? WHERE id=?");
foreach ($data['orden'] as $item) {
    $stmt->execute([(int)$item['orden'], (int)$item['id']]);
}

echo json_encode(['success' => true]);
