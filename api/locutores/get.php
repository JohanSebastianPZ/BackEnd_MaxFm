<?php

require_once "../../config/cors.php";
require_once "../../config/database.php";
require_once "../../config/auth.php";

configurarCORS();
requireAuth();

$db = conectarDB();

// Lógica para get locutores
// TODO: Implementar

echo json_encode(["success" => true, "message" => "get locutores - Implementar lógica"]);

