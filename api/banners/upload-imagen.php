<?php
require_once "../../config/cors.php";
require_once "../../config/database.php";
require_once "../../config/auth.php";
require_once "../../utils/upload.php";

configurarCORS();
requireAuth();

if (!isset($_FILES['imagen'], $_POST['id'])) {
    echo json_encode(['success' => false, 'message' => 'Faltan datos (imagen e id).']);
    exit;
}

$id = (int) $_POST['id'];
$db = conectarDB();

$slide = $db->query("SELECT imagen FROM hero_slides WHERE id = $id")->fetch(PDO::FETCH_ASSOC);
if (!$slide) {
    echo json_encode(['success' => false, 'message' => 'Slide no encontrado.']);
    exit;
}

$res = subirImagen($_FILES['imagen'], 'hero', [
    'max_bytes' => 10 * 1024 * 1024,
    'max_ancho' => 2920,
    'max_alto' => 1800,
    'calidad' => 75,
]);

if (!$res['success']) {
    echo json_encode($res);
    exit;
}

eliminarImagen($slide['imagen']); // borra la imagen anterior

$stmt = $db->prepare("UPDATE hero_slides SET imagen=?, actualizado_en=CURRENT_TIMESTAMP WHERE id=?");
$stmt->execute([$res['path'], $id]);

echo json_encode(['success' => true, 'path' => $res['path'], 'message' => 'Imagen actualizada.']);
