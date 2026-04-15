<?php
header("Access-Control-Allow-Origin: *"); // Permite que React se conecte
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Conexión a SQLite
$db = new PDO('sqlite:' . __DIR__ . '/database.db');

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