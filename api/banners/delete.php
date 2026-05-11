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

try { $db->exec("ALTER TABLE hero_slides ADD COLUMN imagen_movil TEXT"); } catch (PDOException $e) {}

$s     = $db->prepare("SELECT imagen, imagen_movil FROM hero_slides WHERE id = :id");
$s->execute([':id' => $id]);
$slide = $s->fetch(PDO::FETCH_ASSOC);
if (!$slide) {
    echo json_encode(['success' => false, 'message' => 'Slide no encontrado.']);
    exit;
}

eliminarImagen($slide['imagen']);
if (!empty($slide['imagen_movil'])) eliminarImagen($slide['imagen_movil']);
$d = $db->prepare("DELETE FROM hero_slides WHERE id = :id");
$d->execute([':id' => $id]);

echo json_encode(['success' => true, 'message' => 'Slide eliminado.']);
