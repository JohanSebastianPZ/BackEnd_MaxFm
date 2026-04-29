<?php

require_once "../../config/cors.php";
require_once "../../config/database.php";
require_once "../../config/auth.php";
require_once "../../utils/upload.php";

configurarCORS();
requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

if (!isset($_FILES['logo'])) {
    echo json_encode(['success' => false, 'message' => 'No se recibió ningún archivo.']);
    exit;
}

// Logos: máx 2 MB, máx 800×800 px, calidad 75 (WebP)
$resultado = subirImagen($_FILES['logo'], 'configuracion', [
    'max_bytes' => 2 * 1024 * 1024,
    'max_ancho' => 800,
    'max_alto'  => 800,
    'calidad'   => 75,
]);

if (!$resultado['success']) {
    echo json_encode($resultado);
    exit;
}

// Actualizar logo en la base de datos
$db = conectarDB();
$existing = $db->query("SELECT id, logo FROM config_general LIMIT 1")->fetch(PDO::FETCH_ASSOC);

if ($existing) {
    // Eliminar el logo anterior si era un archivo subido por nosotros
    eliminarImagen($existing['logo']);

    $stmt = $db->prepare("UPDATE config_general SET logo = :logo, actualizado_en = CURRENT_TIMESTAMP WHERE id = :id");
    $stmt->execute([':logo' => $resultado['path'], ':id' => $existing['id']]);
} else {
    $stmt = $db->prepare("INSERT INTO config_general (logo) VALUES (:logo)");
    $stmt->execute([':logo' => $resultado['path']]);
}

echo json_encode([
    'success' => true,
    'path'    => $resultado['path'],
    'message' => $resultado['message'],
]);
