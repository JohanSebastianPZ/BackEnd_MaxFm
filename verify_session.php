<?php

require_once 'config/cors.php';
require_once 'config/database.php';
require_once 'config/auth.php';

configurarCORS();

$user = verificarSesion();

if ($user) {
    echo json_encode([
        "success" => true,
        "user" => $user
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Sesión inválida"
    ]);
}
?>