<?php
require_once "../../config/cors.php";
require_once "../../config/database.php";
require_once "../../config/auth.php";

configurarCORS();
requireAuth();

$data = json_decode(file_get_contents('php://input'), true);
if (!$data || !isset($data['id'])) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos.']);
    exit;
}

$db = conectarDB();
$id = (int)$data['id'];

$stmt = $db->prepare("UPDATE eventos SET titulo=?, activo=?, orden=?, actualizado_en=CURRENT_TIMESTAMP WHERE id=?");
$stmt->execute([
    $data['titulo'] ?? null,
    isset($data['activo']) ? (int)$data['activo'] : 1,
    isset($data['orden'])  ? (int)$data['orden']  : 0,
    $id,
]);

echo json_encode(['success' => true, 'message' => 'Evento actualizado.']);
