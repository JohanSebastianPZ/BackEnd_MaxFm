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

$titulo    = $data['titulo']    ?? '';
$subtitulo = $data['subtitulo'] ?? '';
$texto     = $data['texto']     ?? '';
$mostrar   = isset($data['mostrar_texto']) ? (int)$data['mostrar_texto'] : 1;
$alin      = in_array($data['alineacion'] ?? '', ['izquierda','centro','derecha'])
             ? $data['alineacion'] : 'centro';
$activo    = isset($data['activo']) ? (int)$data['activo'] : 1;

$stmt = $db->prepare("UPDATE hero_slides SET titulo=?,subtitulo=?,texto=?,mostrar_texto=?,alineacion=?,activo=?,actualizado_en=CURRENT_TIMESTAMP WHERE id=?");
$stmt->execute([$titulo, $subtitulo, $texto, $mostrar, $alin, $activo, $id]);

echo json_encode(['success' => true, 'message' => 'Slide actualizado.']);
