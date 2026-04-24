<?php

require_once "../../config/cors.php";
require_once "../../config/database.php";
require_once "../../config/auth.php";

configurarCORS();
requireAuth();

$db = conectarDB();

// Lógica para get programacion
// TODO: Implementar

echo json_encode(["success" => true, "message" => "get programacion - Implementar lógica"]);

