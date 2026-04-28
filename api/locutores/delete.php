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
$loc = $db->query("SELECT foto FROM locutores WHERE id = $id")->fetch(PDO::FETCH_ASSOC);

if (!$loc) {
    echo json_encode(['success' => false, 'message' => 'Locutor no encontrado.']);
    exit;
}

// Borra la foto del disco (compatible con ruta completa y con nombre solo)
$foto = $loc['foto'] ?? '';
if ($foto && !str_starts_with($foto, 'uploads/')) {
    // Legado: solo nombre de archivo
    $foto = 'uploads/locutores/' . $foto;
}
eliminarImagen($foto);

$db->exec("DELETE FROM locutores WHERE id = $id");
echo json_encode(['success' => true, 'message' => 'Locutor eliminado.']);
