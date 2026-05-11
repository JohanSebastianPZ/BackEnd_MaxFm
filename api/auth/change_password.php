<?php

require_once '../../config/cors.php';
require_once '../../config/database.php';
require_once '../../config/auth.php';

configurarCORS();

$user = requireAuth();

$data = json_decode(file_get_contents("php://input"), true);
$password_actual   = $data['password_actual']   ?? '';
$password_nueva    = $data['password_nueva']     ?? '';
$password_confirmar = $data['password_confirmar'] ?? '';

if (!$password_actual || !$password_nueva || !$password_confirmar) {
    echo json_encode(["success" => false, "message" => "Todos los campos son obligatorios"]);
    exit;
}

if ($password_nueva !== $password_confirmar) {
    echo json_encode(["success" => false, "message" => "La nueva contraseña y la confirmación no coinciden"]);
    exit;
}

if (strlen($password_nueva) < 8) {
    echo json_encode(["success" => false, "message" => "La nueva contraseña debe tener al menos 8 caracteres"]);
    exit;
}

$db = conectarDB();

$stmt = $db->prepare("SELECT password_hash FROM usuarios WHERE id = :id AND activo = 1");
$stmt->bindParam(':id', $user['id']);
$stmt->execute();
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row || !password_verify($password_actual, $row['password_hash'])) {
    echo json_encode(["success" => false, "message" => "La contraseña actual no es correcta"]);
    exit;
}

$nuevo_hash = password_hash($password_nueva, PASSWORD_DEFAULT);

$update = $db->prepare("UPDATE usuarios SET password_hash = :hash WHERE id = :id");
$update->bindParam(':hash', $nuevo_hash);
$update->bindParam(':id', $user['id']);
$update->execute();

echo json_encode(["success" => true, "message" => "Contraseña actualizada correctamente"]);
