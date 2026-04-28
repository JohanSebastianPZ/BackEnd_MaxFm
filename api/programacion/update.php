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

$stmt = $db->prepare("UPDATE programas SET
    titulo=?, descripcion=?, hora_inicio=?, hora_fin=?,
    horario_texto=?, dias=?, locutor_id=?, destacado=?,
    activo=?, orden=?, actualizado_en=CURRENT_TIMESTAMP
    WHERE id=?");

$stmt->execute([
    $data['titulo']        ?? '',
    $data['descripcion']   ?? '',
    $data['hora_inicio']   ?? '00:00:00',
    $data['hora_fin']      ?? '00:00:00',
    $data['horario_texto'] ?? '',
    is_array($data['dias']) ? json_encode($data['dias']) : ($data['dias'] ?? '[]'),
    !empty($data['locutor_id']) ? (int)$data['locutor_id'] : null,
    isset($data['destacado']) ? (int)$data['destacado'] : 0,
    isset($data['activo'])    ? (int)$data['activo']    : 1,
    isset($data['orden'])     ? (int)$data['orden']     : 0,
    $id,
]);

echo json_encode(['success' => true, 'message' => 'Programa actualizado.']);
