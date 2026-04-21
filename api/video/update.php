<?php
require_once "../../config/cors.php";
require_once "../../config/database.php";
require_once "../../config/auth.php";

configurarCORS();
requireAuth();

$db = conectarDB();

// CORRECCIÓN AQUÍ: Es json_decode (para decodificar lo que manda React), NO json_encode
$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['url'])) {
    echo json_encode(["success" => false, "message" => "URL no proporcionada"]);
    exit;
}

try {
    // Verificamos si ya existe un registro para actualizarlo o crearlo
    $check = $db->query("SELECT id FROM recomendada LIMIT 1")->fetch();

    if ($check) {
        $stmt = $db->prepare("UPDATE recomendada SET youtube_url = ?, actualizado_en = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$data['url'], $check['id']]);
    } else {
        $stmt = $db->prepare("INSERT INTO recomendada (youtube_url, titulo_bloque) VALUES (?, ?)");
        $stmt->execute([$data['url'], 'Video Destacado']);
    }

    echo json_encode(["success" => true, "message" => "Video actualizado correctamente"]);

} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}