<?php
require_once "../../config/cors.php";
require_once "../../config/database.php";

configurarCORS();
$db = conectarDB();

try {
    // Obtenemos los locutores activos ordenados por el campo 'orden'
    $stmt = $db->prepare("SELECT * FROM locutores");
    $stmt->execute();
    $locutores = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "data" => $locutores
    ]);

} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}