<?php

require_once '../../config/cors.php';
require_once '../../config/database.php';
require_once '../../config/auth.php';

configurarCORS();

$db = conectarDB();

// 4. EXTRAER TOKEN
$token = '';
if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
    $matches = array();
    if (preg_match('/Bearer\s(\S+)/', $_SERVER['HTTP_AUTHORIZATION'], $matches)) {
        $token = $matches[1];
    }
} else {
    $data = json_decode(file_get_contents("php://input"), true);
    $token = $data['token'] ?? '';
}

if (!$token) {
    echo json_encode(["success" => false, "message" => "Token no proporcionado"]);
    exit;
}

// 5. DESTRUIR TOKEN EN LA BBDD
$stmt = $db->prepare("UPDATE usuarios SET token_sesion = NULL WHERE token_sesion = :token");
$stmt->bindParam(':token', $token);
$stmt->execute();

echo json_encode(["success" => true, "message" => "Sesión terminada exitosamente"]);