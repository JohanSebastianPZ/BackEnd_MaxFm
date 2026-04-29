<?php
require_once "../../config/cors.php";
require_once "../../config/database.php";
require_once "../../config/auth.php";
require_once "../../utils/upload.php";

configurarCORS();
requireAuth();

$db = conectarDB();

$titulo      = trim($_POST['titulo']      ?? '');
$descripcion = trim($_POST['descripcion'] ?? '');
$hora_inicio = $_POST['hora_inicio'] ?? '00:00:00';
$hora_fin    = $_POST['hora_fin']    ?? '00:00:00';
$horario_texto = trim($_POST['horario_texto'] ?? '');
$dias        = $_POST['dias']        ?? '[]';
$locutor_id  = !empty($_POST['locutor_id']) ? (int)$_POST['locutor_id'] : null;
$destacado   = isset($_POST['destacado']) ? (int)$_POST['destacado'] : 0;
$activo      = isset($_POST['activo'])    ? (int)$_POST['activo']    : 1;

if (!$titulo) {
    echo json_encode(['success' => false, 'message' => 'El título es obligatorio.']);
    exit;
}

// Imagen opcional
$imagenPath = null;
if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
    $res = subirImagen($_FILES['imagen'], 'programas', [
        'max_bytes' => 3 * 1024 * 1024,
        'max_ancho' => 800,
        'max_alto'  => 800,
        'calidad'   => 75,
    ]);
    if (!$res['success']) { echo json_encode($res); exit; }
    $imagenPath = $res['path'];
}

$maxOrden = (int)$db->query("SELECT COALESCE(MAX(orden), 0) FROM programas")->fetchColumn();

$stmt = $db->prepare("INSERT INTO programas (titulo, descripcion, imagen, hora_inicio, hora_fin, horario_texto, dias, locutor_id, destacado, orden, activo) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
$stmt->execute([$titulo, $descripcion, $imagenPath, $hora_inicio, $hora_fin, $horario_texto, $dias, $locutor_id, $destacado, $maxOrden + 1, $activo]);
$newId = $db->lastInsertId();

$prog = $db->query("SELECT p.*, l.nombre AS locutor_nombre FROM programas p LEFT JOIN locutores l ON p.locutor_id = l.id WHERE p.id = $newId")->fetch(PDO::FETCH_ASSOC);
echo json_encode(['success' => true, 'programa' => $prog, 'message' => 'Programa creado.']);
