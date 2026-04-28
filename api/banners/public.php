<?php
require_once "../../config/cors.php";
require_once "../../config/database.php";

configurarCORS();

$db = conectarDB();

try { $db->exec("ALTER TABLE config_general ADD COLUMN hero_velocidad INTEGER DEFAULT 8500"); } catch (PDOException $e) {}

$slides    = $db->query("SELECT * FROM hero_slides WHERE activo = 1 ORDER BY orden ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);
$cfg       = $db->query("SELECT hero_velocidad FROM config_general LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$velocidad = $cfg ? (int)($cfg['hero_velocidad'] ?? 8500) : 8500;

echo json_encode(['success' => true, 'slides' => $slides, 'velocidad' => $velocidad]);
