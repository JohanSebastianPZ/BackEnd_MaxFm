<?php
require_once "../../config/cors.php";
require_once "../../config/database.php";
require_once "../../config/auth.php";
require_once "../../utils/upload.php";

configurarCORS();
requireAuth();

$data = json_decode(file_get_contents('php://input'), true);
if (!$data || !isset($data['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID requerido.']);
    exit;
}

$id = (int) $data['id'];
$db = conectarDB();

$slide = $db->query("SELECT imagen_movil FROM hero_slides WHERE id = $id")->fetch(PDO::FETCH_ASSOC);
if (!$slide) {
    echo json_encode(['success' => false, 'message' => 'Slide no encontrado.']);
    exit;
}

if (!empty($slide['imagen_movil'])) eliminarImagen($slide['imagen_movil']);

$stmt = $db->prepare("UPDATE hero_slides SET imagen_movil=NULL, actualizado_en=CURRENT_TIMESTAMP WHERE id=?");
$stmt->execute([$id]);

echo json_encode(['success' => true, 'message' => 'Imagen móvil eliminada.']);
