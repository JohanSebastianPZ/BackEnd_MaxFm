<?php

// Funciones para verificar si el usuario está logueado

function extraerToken(): string {
    // 1. Variable estándar (PHP built-in server, Nginx bien configurado)
    if (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
        if (preg_match('/Bearer\s(\S+)/', $_SERVER['HTTP_AUTHORIZATION'], $m)) {
            return $m[1];
        }
    }

    // 2. Apache con mod_rewrite pone el header aquí
    if (!empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        if (preg_match('/Bearer\s(\S+)/', $_SERVER['REDIRECT_HTTP_AUTHORIZATION'], $m)) {
            return $m[1];
        }
    }

    // 3. getallheaders() — disponible en Apache y algunos Nginx
    if (function_exists('getallheaders')) {
        $headers = getallheaders();
        // la clave puede venir en cualquier capitalización
        foreach ($headers as $name => $value) {
            if (strcasecmp($name, 'Authorization') === 0) {
                if (preg_match('/Bearer\s(\S+)/', $value, $m)) {
                    return $m[1];
                }
            }
        }
    }

    // 4. Fallback: token en el body JSON (para peticiones POST que lo envíen)
    $raw = file_get_contents('php://input');
    if ($raw) {
        $data = json_decode($raw, true);
        if (!empty($data['token'])) {
            return $data['token'];
        }
    }

    return '';
}

function verificarSesion() {
    $token = extraerToken();

    if (!$token) {
        return false;
    }

    $db   = conectarDB();
    $stmt = $db->prepare("SELECT id, nombre, email, rol FROM usuarios WHERE token_sesion = :token AND activo = 1");
    $stmt->bindParam(':token', $token);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    return $user ?: false;
}

function requireAuth() {
    $user = verificarSesion();
    if (!$user) {
        http_response_code(401);
        echo json_encode(["success" => false, "message" => "Sesión inválida o expirada"]);
        exit;
    }
    return $user;
}
