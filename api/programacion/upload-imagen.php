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

$id = (int)$_POST['id'];
$db = conectarDB();

$prog = $db->query("SELECT imagen FROM programas WHERE id = $id")->fetch(PDO::FETCH_ASSOC);
if (!$prog) {
    echo json_encode(['success' => false, 'message' => 'Programa no encontrado.']);
    exit;
}

$res = subirImagen($_FILES['imagen'], 'programas', [
    'max_bytes' => 3 * 1024 * 1024,
    'max_ancho' => 800,
    'max_alto'  => 800,
    'calidad'   => 75,
]);
if (!$res['success']) { echo json_encode($res); exit; }

eliminarImagen($prog['imagen']);

$stmt = $db->prepare("UPDATE programas SET imagen=?, actualizado_en=CURRENT_TIMESTAMP WHERE id=?");
$stmt->execute([$res['path'], $id]);

echo json_encode(['success' => true, 'path' => $res['path'], 'message' => 'Imagen actualizada.']);
