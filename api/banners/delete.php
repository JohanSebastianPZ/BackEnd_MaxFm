<?php

require_once "../../config/cors.php";
require_once "../../config/database.php";
require_once "../../config/auth.php";

configurarCORS();
requireAuth();

$db = conectarDB();

// Lógica para delete banners
// TODO: Implementar

echo json_encode(["success" => true, "message" => "delete banners - Implementar lógica"]);

