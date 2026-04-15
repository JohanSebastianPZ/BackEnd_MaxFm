<?php

// 1. FUNCIÓN PARA LEER EL ARCHIVO .env
function cargarEnv($ruta) {
    if (!file_exists($ruta)) return; // Si no existe el .env, no hace nada
    
    $lineas = file($ruta, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lineas as $linea) {
        // Ignorar los comentarios (líneas que empiezan con #)
        if (strpos(trim($linea), '#') === 0) continue;
        
        // Separar el nombre de la variable y su valor
        list($nombre, $valor) = explode('=', $linea, 2);
        
        // Guardarlo en el entorno de PHP
        $_ENV[trim($nombre)] = trim($valor);
    }
}

// Ejecutamos la función buscando el archivo .env en esta misma carpeta
cargarEnv(__DIR__ . '/.env');

// 2. CONFIGURACIÓN DE SEGURIDAD (CORS) USANDO EL .env

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

// Conexión a SQLite
$db_file = $_ENV['DB_NAME'] ?? 'database.db';
$db = new PDO('sqlite:' . __DIR__ . '/' . $db_file);

// Obtener datos del POST (React envía JSON)
$data = json_decode(file_get_contents("php://input"), true);
$email = $data['email'] ?? '';
$password = $data['password'] ?? '';

if (!$email || !$password) {
    echo json_encode(["success" => false, "message" => "Datos incompletos"]);
    exit;
}

// Consultar usuario
$stmt = $db->prepare("SELECT * FROM usuarios WHERE email = :email AND activo = 1");
$stmt->bindParam(':email', $email);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// --- AGREGA ESTO PARA DEPURAR ---
if (!$user) {
    echo json_encode(["success" => false, "message" => "El email no se encontró en la base de datos."]);
    exit;
}
// --------------------------------

// Verificar contraseña (Asumiendo que usas password_hash en PHP)
if ($user && password_verify($password, $user['password_hash'])) {
    // Aquí podrías generar un token, por ahora enviamos éxito
    echo json_encode([
        "success" => true,
        "user" => [
            "nombre" => $user['nombre'],
            "rol" => $user['rol']
        ]
    ]);
} else {
    echo json_encode(["success" => false, "message" => "Usuario o contraseña incorrectos"]);
}
?>