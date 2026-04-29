<?php

require_once "../../config/cors.php";
require_once "../../config/database.php";

configurarCORS();

$db = conectarDB();

// config_general
$row = $db->query("SELECT * FROM config_general LIMIT 1")->fetch(PDO::FETCH_ASSOC);

$config = null;
if ($row) {
    $campos = [
        'nombre_emisora', 'slogan', 'logo', 'favicon', 'descripcion',
        'url_streaming', 'url_app_android', 'url_app_ios',
        'telefono', 'whatsapp', 'email', 'direccion',
        'facebook', 'instagram', 'tiktok', 'youtube', 'twitter',
        'footer_texto', 'footer_copyright', 'meta_titulo', 'meta_descripcion',
    ];
    foreach ($campos as $campo) {
        $config[$campo] = $row[$campo] ?? null;
    }
}

// chat_config (solo campos que el front necesita)
$chatRow = $db->query("SELECT titulo, subtitulo, visible FROM chat_config LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$chat = $chatRow ?: null;

echo json_encode(['success' => true, 'config' => $config, 'chat' => $chat]);
