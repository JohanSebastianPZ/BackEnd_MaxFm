<?php

// Funciones para verificar si el usuario está logueado

function verificarSesion() {
    $db = conectarDB();

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
        return false;
    }

    $stmt = $db->prepare("SELECT id, nombre, email, rol FROM usuarios WHERE token_sesion = :token AND activo = 1");
    $stmt->bindParam(':token', $token);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    return $user ?: false;
}

function requireAuth() {
    $user = verificarSesion();
    if (!$user) {
        echo json_encode(["success" => false, "message" => "Sesión inválida o expirada"]);
        exit;
    }
    return $user;
}