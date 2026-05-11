<?php
require_once "../../config/cors.php";
require_once "../../config/database.php";
require_once "../../config/auth.php";
require_once "../../utils/upload.php";

configurarCORS();
requireAuth();

$db   = conectarDB();
$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['id'])) {
    echo json_encode(['success' => false, 'message' => 'Falta el ID del locutor.']);
    exit;
}

$id  = (int)$data['id'];
$s   = $db->prepare("SELECT imagen FROM noticias WHERE id = :id");
$s->execute([':id' => $id]);
$loc = $s->fetch(PDO::FETCH_ASSOC);

if (!$loc) {
    echo json_encode(['success' => false, 'message' => 'Locutor no encontrado.']);
    exit;
}

// Borra la foto del disco (compatible con ruta completa y con nombre solo)
$foto = $loc['imagen'] ?? '';
if ($foto && strpos($foto, 'uploads/') !== 0) {
    // Legado: solo nombre de archivo
    $foto = 'uploads/noticias/' . $foto;
}
eliminarImagen($foto);

$d = $db->prepare("DELETE FROM noticias WHERE id = :id");
$d->execute([':id' => $id]);
echo json_encode(['success' => true, 'message' => 'Noticia eliminada.']);
