<?php
require_once "../../config/cors.php";
require_once "../../config/database.php";

configurarCORS();

$db      = conectarDB();
$eventos = $db->query("SELECT * FROM eventos WHERE activo = 1 ORDER BY orden ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['success' => true, 'eventos' => $eventos]);
