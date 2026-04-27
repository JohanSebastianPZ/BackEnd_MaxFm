<?php

require_once "../../config/cors.php";
require_once "../../config/database.php";
require_once "../../config/auth.php";
require_once "../../utils/upload.php";

configurarCORS();
requireAuth();

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(["success" => false, "message" => "Datos inválidos"]);
    exit;
}

$db = conectarDB();

// --- config_general ---
$camposConfig = [
    'nombre_emisora', 'slogan', 'logo', 'favicon', 'descripcion',
    'url_streaming', 'url_app_android', 'url_app_ios',
    'telefono', 'whatsapp', 'email', 'direccion',
    'facebook', 'instagram', 'tiktok', 'youtube', 'twitter',
    'footer_texto', 'footer_copyright', 'meta_titulo', 'meta_descripcion',
];

$configData = $data['config_general'] ?? [];

$existingConfig = $db->query("SELECT id, logo FROM config_general LIMIT 1")->fetch(PDO::FETCH_ASSOC);

if ($existingConfig) {
    // Si el logo cambió y el anterior era un archivo nuestro, eliminarlo
    $logoNuevo   = $configData['logo'] ?? '';
    $logoAnterior = $existingConfig['logo'] ?? '';
    if ($logoAnterior !== $logoNuevo) {
        eliminarImagen($logoAnterior);
    }

    $sets = implode(', ', array_map(fn($c) => "$c = :$c", $camposConfig));
    $stmt = $db->prepare("UPDATE config_general SET $sets, actualizado_en = CURRENT_TIMESTAMP WHERE id = :id");
    $stmt->bindValue(':id', $existingConfig['id']);
} else {
    $cols = implode(', ', $camposConfig);
    $placeholders = implode(', ', array_map(fn($c) => ":$c", $camposConfig));
    $stmt = $db->prepare("INSERT INTO config_general ($cols) VALUES ($placeholders)");
}

foreach ($camposConfig as $campo) {
    $stmt->bindValue(":$campo", $configData[$campo] ?? null);
}
$stmt->execute();

// --- chat_config ---
$camposChat = ['titulo', 'subtitulo', 'embed_code', 'visible'];
$chatData   = $data['chat_config'] ?? [];

$existingChat = $db->query("SELECT id FROM chat_config LIMIT 1")->fetch(PDO::FETCH_ASSOC);

if ($existingChat) {
    $sets = implode(', ', array_map(fn($c) => "$c = :$c", $camposChat));
    $stmt = $db->prepare("UPDATE chat_config SET $sets, actualizado_en = CURRENT_TIMESTAMP WHERE id = :id");
    $stmt->bindValue(':id', $existingChat['id']);
} else {
    $cols = implode(', ', $camposChat);
    $placeholders = implode(', ', array_map(fn($c) => ":$c", $camposChat));
    $stmt = $db->prepare("INSERT INTO chat_config ($cols) VALUES ($placeholders)");
}

foreach ($camposChat as $campo) {
    $stmt->bindValue(":$campo", $chatData[$campo] ?? null);
}
$stmt->execute();

echo json_encode(["success" => true, "message" => "Configuración guardada correctamente"]);
