<?php
require_once "../../config/cors.php";
require_once "../../config/database.php";
require_once "../../config/auth.php";
require_once "../../utils/upload.php";

configurarCORS();
requireAuth();

$db = conectarDB();

$total = (int)$db->query("SELECT COUNT(*) FROM eventos")->fetchColumn();
if ($total >= 15) {
    echo json_encode(['success' => false, 'message' => 'Límite de 15 eventos alcanzado.']);
    exit;
}

if (!isset($_FILES['imagen']) || $_FILES['imagen']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'La imagen es obligatoria.']);
    exit;
}

$res = subirImagen($_FILES['imagen'], 'eventos', [
    'max_bytes' => 5 * 1024 * 1024,
    'max_ancho' => 900,
    'max_alto'  => 1600,
    'calidad'   => 78,
]);
if (!$res['success']) { echo json_encode($res); exit; }

$titulo   = trim($_POST['titulo'] ?? '');
$activo   = isset($_POST['activo']) ? (int)$_POST['activo'] : 1;
$maxOrden = (int)$db->query("SELECT COALESCE(MAX(orden), -1) FROM eventos")->fetchColumn();

$stmt = $db->prepare("INSERT INTO eventos (imagen, titulo, orden, activo) VALUES (?,?,?,?)");
$stmt->execute([$res['path'], $titulo ?: null, $maxOrden + 1, $activo]);
$newId = $db->lastInsertId();

$ev = $db->query("SELECT * FROM eventos WHERE id = $newId")->fetch(PDO::FETCH_ASSOC);
echo json_encode(['success' => true, 'evento' => $ev, 'message' => 'Evento creado.']);
