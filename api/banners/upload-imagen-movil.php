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

try { $db->exec("ALTER TABLE hero_slides ADD COLUMN imagen_movil TEXT"); } catch (PDOException $e) {}

$slide = $db->query("SELECT imagen_movil FROM hero_slides WHERE id = $id")->fetch(PDO::FETCH_ASSOC);
if (!$slide) {
    echo json_encode(['success' => false, 'message' => 'Slide no encontrado.']);
    exit;
}

$res = subirImagen($_FILES['imagen'], 'hero', [
    'max_bytes' => 10 * 1024 * 1024,
    'max_ancho' => 1080,
    'max_alto'  => 1920,
    'calidad'   => 75,
]);

if (!$res['success']) {
    echo json_encode($res);
    exit;
}

if (!empty($slide['imagen_movil'])) eliminarImagen($slide['imagen_movil']);

$stmt = $db->prepare("UPDATE hero_slides SET imagen_movil=?, actualizado_en=CURRENT_TIMESTAMP WHERE id=?");
$stmt->execute([$res['path'], $id]);

echo json_encode(['success' => true, 'path' => $res['path'], 'message' => 'Imagen móvil actualizada.']);
