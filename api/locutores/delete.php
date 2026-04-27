<?php
require_once "../../config/cors.php";
require_once "../../config/database.php";
require_once "../../config/auth.php";

configurarCORS();
requireAuth(); // ¡Protegido! Solo admins pueden borrar

$db = conectarDB();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['id'])) {
    echo json_encode(["success" => false, "message" => "Falta el ID del locutor"]);
    exit;
}

try {
    // 1. Primero, buscamos el nombre de la foto para poder borrarla del disco
    $stmt = $db->prepare("SELECT foto FROM locutores WHERE id = ?");
    $stmt->execute([$data['id']]);
    $locutor = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($locutor && !empty($locutor['foto'])) {
        $rutaArchivo = __DIR__ . '/../../uploads/locutores/' . $locutor['foto'];
        
        // Verificamos si el archivo físico existe y lo borramos
        if (file_exists($rutaArchivo)) {
            unlink($rutaArchivo);
        }
    }

    // 2. Ahora sí, borramos el registro de la base de datos
    $stmtDelete = $db->prepare("DELETE FROM locutores WHERE id = ?");
    $stmtDelete->execute([$data['id']]);

    echo json_encode(["success" => true, "message" => "Locutor y foto eliminados correctamente"]);

} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Error de BD: " . $e->getMessage()]);
}
?>