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

$prog = $db->query("SELECT imagen FROM programas WHERE id = $id")->fetch(PDO::FETCH_ASSOC);
if (!$prog) {
    echo json_encode(['success' => false, 'message' => 'Programa no encontrado.']);
    exit;
}

eliminarImagen($prog['imagen']);
$db->exec("DELETE FROM programas WHERE id = $id");

echo json_encode(['success' => true, 'message' => 'Programa eliminado.']);
