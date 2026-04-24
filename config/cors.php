<?php

// Archivo único para los encabezados CORS

function configurarCORS() {
    cargarEnv(dirname(__DIR__) . '/.env');

    $allowed_origins = [
        $_ENV['FRONTEND_URL_LOCAL'] ?? '',
        $_ENV['FRONTEND_URL_PROD'] ?? ''
    ];

    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

    if (in_array($origin, $allowed_origins)) {
        header("Access-Control-Allow-Origin: " . $origin);
    } else {
        if ($origin !== '') {
            header("HTTP/1.1 403 Forbidden");
            echo json_encode(["success" => false, "message" => "Acceso denegado (CORS)"]);
            exit;
        }
    }

    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");
    header("Content-Type: application/json");

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        exit(0);
    }
}