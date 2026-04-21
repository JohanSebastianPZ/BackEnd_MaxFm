<?php

// Archivo único para conectar a la base de datos

function cargarEnv($ruta) {
    if (!file_exists($ruta)) return;
    $lineas = file($ruta, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lineas as $linea) {
        if (strpos(trim($linea), '#') === 0) continue;
        list($nombre, $valor) = explode('=', $linea, 2);
        $_ENV[trim($nombre)] = trim($valor);
    }
}

function conectarDB() {
    cargarEnv(dirname(__DIR__) . '/.env');
    $db_file = $_ENV['DB_NAME'] ?? 'database.db';
    $db_path = dirname(__DIR__) . '/' . $db_file;
    return new PDO('sqlite:' . $db_path);
}