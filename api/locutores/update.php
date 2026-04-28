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

$stmt = $db->prepare("UPDATE locutores SET
    nombre=?, cargo=?, bio=?, instagram_usuario=?, instagram_url=?,
    destacado=?, activo=?, orden=?, actualizado_en=CURRENT_TIMESTAMP
    WHERE id=?");

$stmt->execute([
    $data['nombre']            ?? '',
    $data['cargo']             ?? '',
    $data['bio']               ?? '',
    $data['instagram_usuario'] ?? '',
    $data['instagram_url']     ?? '',
    isset($data['destacado']) ? (int)$data['destacado'] : 0,
    isset($data['activo'])    ? (int)$data['activo']    : 1,
    isset($data['orden'])     ? (int)$data['orden']     : 0,
    $id,
]);

echo json_encode(['success' => true, 'message' => 'Locutor actualizado.']);
