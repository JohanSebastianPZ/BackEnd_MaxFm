<?php
// Permisos CORS esenciales
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Forzamos a que se guarde en api/message/chat_messages.json
$archivo = __DIR__ . '/chat_messages.json';

$archivo = 'chat_messages.json';
$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !isset($data['user']) || !isset($data['text'])) {
    echo json_encode(["success" => false, "message" => "Datos inválidos"]);
    exit;
}

$nuevoMensaje = [
    "user" => htmlspecialchars($data['user']),
    "text" => htmlspecialchars($data['text']),
    "time" => date('h:i A'), // Formato 2:34 PM
    "host" => false
];

// Abrir el archivo y BLOQUEARLO
$fp = fopen($archivo, "c+"); // c+ abre para leer/escribir sin truncar
if (flock($fp, LOCK_EX)) { // Bloqueo exclusivo
    
    $tamaño = filesize($archivo);
    $mensajes = [];
    if ($tamaño > 0) {
        $contenido = fread($fp, $tamaño);
        $mensajes = json_decode($contenido, true) ?: [];
    }

    // Añadir el nuevo mensaje al final
    $mensajes[] = $nuevoMensaje;

    // Mantener solo los últimos 200
    if (count($mensajes) > 200) {
        $mensajes = array_slice($mensajes, -200);
    }

    // Regresar el puntero al inicio, vaciar archivo y escribir
    fseek($fp, 0);
    ftruncate($fp, 0);
    fwrite($fp, json_encode($mensajes, JSON_UNESCAPED_UNICODE));
    fflush($fp); // Forzar escritura al disco

    flock($fp, LOCK_UN); // Liberar bloqueo
} else {
    echo json_encode(["success" => false, "message" => "Sistema ocupado, intenta de nuevo"]);
    exit;
}
fclose($fp);

echo json_encode(["success" => true]);
?>