<?php
require_once "../../config/cors.php";
require_once "../../config/database.php";
require_once "../../config/auth.php";
require_once "../../utils/upload.php";

configurarCORS();
requireAuth();

$db = conectarDB();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$nombre           = trim($_POST['nombre']           ?? '');
$cargo            = trim($_POST['cargo']             ?? '');
$bio              = trim($_POST['bio']               ?? '');
$instagram_usuario = trim($_POST['instagram_usuario'] ?? '');
$instagram_url    = trim($_POST['instagram_url']     ?? '');
$destacado        = isset($_POST['destacado']) ? (int)$_POST['destacado'] : 0;
$activo           = isset($_POST['activo'])    ? (int)$_POST['activo']    : 1;

if (!$nombre || !$cargo) {
    echo json_encode(['success' => false, 'message' => 'Nombre y cargo son obligatorios.']);
    exit;
}

// Foto opcional al crear (puede subirse después)
$fotoPath = null;
if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
    $res = subirImagen($_FILES['foto'], 'locutores', [
        'max_bytes' => 3 * 1024 * 1024,
        'max_ancho' => 600,
        'max_alto'  => 800,
        'calidad'   => 78,
    ]);
    if (!$res['success']) { echo json_encode($res); exit; }
    $fotoPath = $res['path'];
}

$maxOrden = (int)$db->query("SELECT COALESCE(MAX(orden),0) FROM locutores")->fetchColumn();

$stmt = $db->prepare("INSERT INTO locutores (nombre, cargo, bio, instagram_usuario, instagram_url, foto, destacado, orden, activo) VALUES (?,?,?,?,?,?,?,?,?)");
$stmt->execute([$nombre, $cargo, $bio, $instagram_usuario, $instagram_url, $fotoPath, $destacado, $maxOrden + 1, $activo]);
$newId = $db->lastInsertId();

$loc = $db->query("SELECT * FROM locutores WHERE id = $newId")->fetch(PDO::FETCH_ASSOC);
echo json_encode(['success' => true, 'locutor' => $loc, 'message' => 'Locutor creado.']);
