<?php
require_once "../../config/cors.php";
require_once "../../config/database.php";
// Fíjate que AQUÍ NO ponemos requireAuth(); para que el público pueda leer el Top 5.

configurarCORS();
$db = conectarDB();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

try {
    // Seleccionamos las 5 posiciones y las renombramos (alias) para que React las entienda a la perfección
    $stmt = $db->prepare("SELECT posicion AS pos, titulo AS title, artista AS artist, youtube_url FROM top5_canciones ORDER BY posicion ASC");
    $stmt->execute();
    $canciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Si la tabla está vacía, enviamos un array vacío
    if (!$canciones) {
        $canciones = [];
    }

    echo json_encode([
        "success" => true,
        "data" => $canciones
    ]);

} catch (PDOException $e) {
    echo json_encode([
        "success" => false,
        "message" => "Error de BD: " . $e->getMessage()
    ]);
}
?>