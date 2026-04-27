<?php
require_once "../../config/cors.php";
require_once "../../config/database.php";
require_once "../../config/auth.php";

configurarCORS();
requireAuth(); // ¡Protegido! Solo administradores pueden guardar

$db = conectarDB();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$data = json_decode(file_get_contents("php://input"), true);

// Validamos que vengan los datos necesarios
if (!isset($data['pos']) || !isset($data['url']) || !isset($data['title']) || !isset($data['artist'])) {
    echo json_encode(["success" => false, "message" => "Faltan datos en el formulario"]);
    exit;
}

try {
    // Verificamos si la posición ya existe en la tabla
    $stmtCheck = $db->prepare("SELECT id FROM top5_canciones WHERE posicion = ?");
    $stmtCheck->execute([$data['pos']]);
    $existe = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if ($existe) {
        // Si la posición ya existe, la ACTUALIZAMOS
        $stmt = $db->prepare("UPDATE top5_canciones SET titulo = ?, artista = ?, youtube_url = ?, actualizado_en = CURRENT_TIMESTAMP WHERE posicion = ?");
        $stmt->execute([$data['title'], $data['artist'], $data['url'], $data['pos']]);
    } else {
        // Si la posición está vacía (es la primera vez), la INSERTAMOS
        $stmt = $db->prepare("INSERT INTO top5_canciones (posicion, titulo, artista, youtube_url) VALUES (?, ?, ?, ?)");
        $stmt->execute([$data['pos'], $data['title'], $data['artist'], $data['url']]);
    }

    echo json_encode(["success" => true, "message" => "Posición actualizada correctamente"]);

} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Error de BD: " . $e->getMessage()]);
}
?>