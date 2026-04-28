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

$db = conectarDB();
$id = (int)$data['id'];

$slide = $db->query("SELECT imagen FROM hero_slides WHERE id = $id")->fetch(PDO::FETCH_ASSOC);
if (!$slide) {
    echo json_encode(['success' => false, 'message' => 'Slide no encontrado.']);
    exit;
}

eliminarImagen($slide['imagen']); // borra del disco si es uploads/
$db->exec("DELETE FROM hero_slides WHERE id = $id");

echo json_encode(['success' => true, 'message' => 'Slide eliminado.']);
