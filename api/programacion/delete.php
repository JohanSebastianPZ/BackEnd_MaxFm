<?php

require_once "../../config/cors.php";
require_once "../../config/database.php";
require_once "../../config/auth.php";

configurarCORS();
requireAuth();

$db = conectarDB();

// Lógica para delete programacion
// TODO: Implementar

echo json_encode(["success" => true, "message" => "delete programacion - Implementar lógica"]);

