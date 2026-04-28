<?php
require_once "../../config/cors.php";
require_once "../../config/database.php";
require_once "../../config/auth.php";

configurarCORS();
requireAuth();

$data = json_decode(file_get_contents('php://input'), true);
if (!$data || !isset($data['orden']) || !is_array($data['orden'])) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos.']);
    exit;
}

$db   = conectarDB();
$stmt = $db->prepare("UPDATE hero_slides SET orden=? WHERE id=?");

foreach ($data['orden'] as $item) {
    $stmt->execute([(int)$item['orden'], (int)$item['id']]);
}

echo json_encode(['success' => true, 'message' => 'Orden actualizado.']);
