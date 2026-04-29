<?php
require_once "../../config/cors.php";
require_once "../../config/database.php";
require_once "../../config/auth.php";

configurarCORS();
requireAuth();

$db = conectarDB();

$programas = $db->query("
    SELECT p.*, l.nombre AS locutor_nombre
    FROM programas p
    LEFT JOIN locutores l ON p.locutor_id = l.id
    ORDER BY p.orden ASC, p.id ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Locutores disponibles (para el selector del form)
$locutores = $db->query("SELECT id, nombre FROM locutores WHERE activo = 1 ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['success' => true, 'programas' => $programas, 'locutores' => $locutores]);
