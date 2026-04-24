<?php

require_once "../../config/cors.php";
require_once "../../config/database.php";
require_once "../../config/auth.php";

configurarCORS();
requireAuth();

$db = conectarDB();

// Lógica para get banners
// TODO: Implementar

echo json_encode(["success" => true, "message" => "get banners - Implementar lógica"]);

