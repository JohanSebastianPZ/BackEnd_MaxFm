<?php

require_once '../../config/cors.php';
require_once '../../config/database.php';
require_once '../../config/auth.php';

configurarCORS();

$db = conectarDB();

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
    // Generamos un token único y seguro
    $token = bin2hex(random_bytes(32));

    // Guardamos el token en la base de datos para este usuario
    $updateStmt = $db->prepare("UPDATE usuarios SET token_sesion = :token, ultimo_acceso = CURRENT_TIMESTAMP WHERE id = :id");
    $updateStmt->bindParam(':token', $token);
    $updateStmt->bindParam(':id', $user['id']);
    $updateStmt->execute();

    // Enviamos respuesta exitosa con el token
    echo json_encode([
        "success" => true,
        "token" => $token,
        "user" => [
            "id" => $user['id'],
            "nombre" => $user['nombre'],
            "rol" => $user['rol']
        ]
    ]);
} else {
    echo json_encode(["success" => false, "message" => "Usuario o contraseña incorrectos"]);
}