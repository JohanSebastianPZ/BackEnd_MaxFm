<?php
require_once "../../config/cors.php";
require_once "../../config/database.php";
require_once "../../config/auth.php";
require_once "../../utils/upload.php";

configurarCORS();
requireAuth();

if (!isset($_FILES['foto'], $_POST['id'])) {
    echo json_encode(['success' => false, 'message' => 'Faltan datos.']);
    exit;
}

$id = (int)$_POST['id'];
$db = conectarDB();

$loc = $db->query("SELECT foto FROM locutores WHERE id = $id")->fetch(PDO::FETCH_ASSOC);
if (!$loc) {
    echo json_encode(['success' => false, 'message' => 'Locutor no encontrado.']);
    exit;
}

$res = subirImagen($_FILES['foto'], 'locutores', [
    'max_bytes' => 3 * 1024 * 1024,
    'max_ancho' => 600,
    'max_alto'  => 800,
    'calidad'   => 78,
]);
if (!$res['success']) { echo json_encode($res); exit; }

// Borrar foto anterior (compatible con ambos formatos)
$fotoAnterior = $loc['foto'] ?? '';
if ($fotoAnterior && !str_starts_with($fotoAnterior, 'uploads/')) {
    $fotoAnterior = 'uploads/locutores/' . $fotoAnterior;
}
eliminarImagen($fotoAnterior);

$stmt = $db->prepare("UPDATE locutores SET foto=?, actualizado_en=CURRENT_TIMESTAMP WHERE id=?");
$stmt->execute([$res['path'], $id]);

echo json_encode(['success' => true, 'path' => $res['path'], 'message' => 'Foto actualizada.']);
