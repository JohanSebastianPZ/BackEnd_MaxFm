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

$s  = $db->prepare("SELECT imagen FROM eventos WHERE id = :id");
$s->execute([':id' => $id]);
$ev = $s->fetch(PDO::FETCH_ASSOC);
if (!$ev) { echo json_encode(['success' => false, 'message' => 'Evento no encontrado.']); exit; }

eliminarImagen($ev['imagen']);
$d = $db->prepare("DELETE FROM eventos WHERE id = :id");
$d->execute([':id' => $id]);

echo json_encode(['success' => true, 'message' => 'Evento eliminado.']);
