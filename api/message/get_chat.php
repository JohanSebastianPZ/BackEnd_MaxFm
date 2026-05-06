<?php
// Permisos CORS esenciales
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header('Content-Type: application/json');

$archivo_mensajes = __DIR__ . '/chat_messages.json';
$archivo_stats = __DIR__ . '/chat_stats.json';

// Leemos los archivos si existen, si no, devolvemos datos vacíos
$mensajes = file_exists($archivo_mensajes) ? json_decode(file_get_contents($archivo_mensajes), true) : [];
$stats = file_exists($archivo_stats) ? json_decode(file_get_contents($archivo_stats), true) : null;

echo json_encode([
    "success" => true,
    "messages" => $mensajes ? $mensajes : [],
    "stats" => $stats
]);
?>
