<?php

// 1. FUNCIÓN PARA LEER EL ARCHIVO .env
function cargarEnv($ruta) {
    if (!file_exists($ruta)) return;
    $lineas = file($ruta, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lineas as $linea) {
        if (strpos(trim($linea), '#') === 0) continue;
        list($nombre, $valor) = explode('=', $linea, 2);
        $_ENV[trim($nombre)] = trim($valor);
    }
}
cargarEnv(__DIR__ . '/.env');

// 2. CONFIGURACIÓN DE SEGURIDAD (CORS)
$allowed_origins = [
    $_ENV['FRONTEND_URL_LOCAL'] ?? '',
    $_ENV['FRONTEND_URL_PROD'] ?? ''
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if (in_array($origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: " . $origin);
} else {
    if ($origin !== '') {
        header("HTTP/1.1 403 Forbidden");
        echo json_encode(["success" => false, "message" => "Acceso denegado (CORS)"]);
        exit;
    }
}

header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// 3. CONEXIÓN A BBDD
$db_file = $_ENV['DB_NAME'] ?? 'database.db';
$db = new PDO('sqlite:' . __DIR__ . '/' . $db_file);

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
?>
