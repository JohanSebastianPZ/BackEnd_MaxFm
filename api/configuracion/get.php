<?php

require_once "../../config/cors.php";
require_once "../../config/database.php";
require_once "../../config/auth.php";

configurarCORS();
requireAuth();

$db = conectarDB();

$config = $db->query("SELECT * FROM config_general LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$chat   = $db->query("SELECT * FROM chat_config LIMIT 1")->fetch(PDO::FETCH_ASSOC);

echo json_encode([
    "success"        => true,
    "config_general" => $config ?: null,
    "chat_config"    => $chat   ?: null,
]);
