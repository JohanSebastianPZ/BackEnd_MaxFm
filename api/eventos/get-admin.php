<?php
require_once "../../config/cors.php";
require_once "../../config/database.php";
require_once "../../config/auth.php";

configurarCORS();
requireAuth();

$db      = conectarDB();
$eventos = $db->query("SELECT * FROM eventos ORDER BY orden ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['success' => true, 'eventos' => $eventos]);
