<?php
require_once "../../config/cors.php";
require_once "../../config/database.php";
require_once "../../config/auth.php";

configurarCORS();

$db = conectarDB();

try {
    // Consultamos el primer registro de la tabla
    $stmt = $db->prepare("SELECT id, titulo_bloque, titulo_video, descripcion, youtube_url, activo FROM recomendada LIMIT 1");
    $stmt->execute();
    $video = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($video) {
        echo json_encode([
            "success" => true,
            "data" => $video
        ]);
    } else {
        // Si la tabla está vacía, devolvemos success pero data null
        echo json_encode([
            "success" => true,
            "data" => null,
            "message" => "No hay ningún video configurado aún."
        ]);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Error en la base de datos: " . $e->getMessage()
    ]);
}