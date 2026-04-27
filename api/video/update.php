<?php
require_once "../../config/cors.php";
require_once "../../config/database.php";
require_once "../../config/auth.php";

configurarCORS();
requireAuth();

$db = conectarDB();

//Forzamos a que la Base de Datos nos "grite" si hay algún error
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['url'])) {
    echo json_encode(["success" => false, "message" => "URL no proporcionada desde React"]);
    exit;
}

try {
    $check = $db->query("SELECT id FROM recomendada LIMIT 1")->fetch(PDO::FETCH_ASSOC);

    if ($check) {
        // Hacemos el UPDATE solo del campo youtube_url (quitamos actualizado_en para evitar errores de columnas inexistentes)
        $stmt = $db->prepare("UPDATE recomendada SET youtube_url = ? WHERE id = ?");
        $resultado = $stmt->execute([$data['url'], $check['id']]);
        
        // Verificamos si realmente se modificó la fila
        if ($stmt->rowCount() > 0) {
            echo json_encode(["success" => true, "message" => "¡Video actualizado correctamente en la BD!"]);
        } else {
            // Si llega aquí, significa que la URL que enviaste es IDÉNTICA a la que ya estaba guardada.
            // Para MySQL, actualizar algo por el mismo valor = 0 cambios.
            echo json_encode(["success" => true, "message" => "La URL es la misma que ya estaba guardada."]);
        }
    } else {
        // Insertamos si no existía nada
        $stmt = $db->prepare("INSERT INTO recomendada (youtube_url, titulo_bloque) VALUES (?, ?)");
        $stmt->execute([$data['url'], 'Video Destacado']);
        
        echo json_encode(["success" => true, "message" => "Primer video insertado correctamente."]);
    }

} catch (PDOException $e) {
    // Si la tabla no existe, o la columna se llama diferente, PHP se lo dirá a tu VideoAdmin.tsx
    echo json_encode([
        "success" => false, 
        "message" => "Error en la Base de Datos: " . $e->getMessage()
    ]);
}
?>