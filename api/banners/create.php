<?php
require_once "../../config/cors.php";
require_once "../../config/database.php";
require_once "../../config/auth.php";
require_once "../../utils/upload.php";

configurarCORS();
requireAuth();

$db = conectarDB();

// Límite de 6 slides
$total = (int)$db->query("SELECT COUNT(*) FROM hero_slides")->fetchColumn();
if ($total >= 6) {
    echo json_encode(['success' => false, 'message' => 'Límite de 6 slides alcanzado.']);
    exit;
}

$tipo = $_POST['tipo'] ?? 'custom';

if ($tipo === 'video') {
    $imagenPath = '__video_default__';
} elseif ($tipo === 'url' && !empty($_POST['imagen_url'])) {
    $imagenPath = trim($_POST['imagen_url']);
} elseif (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
    $res = subirImagen($_FILES['imagen'], 'hero', [
        'max_bytes' => 5 * 1024 * 1024,
        'max_ancho' => 1920,
        'max_alto'  => 1080,
        'calidad'   => 75,
    ]);
    if (!$res['success']) { echo json_encode($res); exit; }
    $imagenPath = $res['path'];
} else {
    echo json_encode(['success' => false, 'message' => 'Se requiere una imagen válida.']);
    exit;
}

$titulo    = $_POST['titulo']    ?? '';
$subtitulo = $_POST['subtitulo'] ?? '';
$texto     = $_POST['texto']     ?? '';
$mostrar   = isset($_POST['mostrar_texto']) ? (int)$_POST['mostrar_texto'] : 1;
$alin      = in_array($_POST['alineacion'] ?? '', ['izquierda','centro','derecha'])
             ? $_POST['alineacion'] : 'centro';
$activo    = isset($_POST['activo']) ? (int)$_POST['activo'] : 1;

$maxOrden = (int)$db->query("SELECT COALESCE(MAX(orden),-1) FROM hero_slides")->fetchColumn();
$orden    = $maxOrden + 1;

$stmt = $db->prepare("INSERT INTO hero_slides (imagen,titulo,subtitulo,texto,mostrar_texto,alineacion,orden,activo) VALUES (?,?,?,?,?,?,?,?)");
$stmt->execute([$imagenPath, $titulo, $subtitulo, $texto, $mostrar, $alin, $orden, $activo]);
$newId = $db->lastInsertId();

$slide = $db->query("SELECT * FROM hero_slides WHERE id = $newId")->fetch(PDO::FETCH_ASSOC);
echo json_encode(['success' => true, 'slide' => $slide, 'message' => 'Slide creado correctamente.']);
