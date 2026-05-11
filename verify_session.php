<?php

require_once 'config/cors.php';
require_once 'config/database.php';
require_once 'config/auth.php';

configurarCORS();

$user = verificarSesion();

if ($user) {
    echo json_encode(["success" => true, "user" => $user]);
} else {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Sesión inválida"]);
}
?>