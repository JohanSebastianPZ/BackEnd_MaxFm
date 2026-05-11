<?php
require_once "../../config/cors.php";
require_once "../../config/database.php";
require_once "../../config/auth.php";

configurarCORS();
requireAuth(); // Solo el admin puede eliminar mensajes del chat

// Recibir los datos enviados por React
$data = json_decode(file_get_contents("php://input"), true);

// Validar que exista el ID
if (!isset($data['id'])) {
    echo json_encode(["success" => false, "error" => "No se proporcionó el ID del mensaje."]);
    exit();
}

$id_eliminar = $data['id'];

// 3. Ruta de tu archivo JSON
$archivo_json = __DIR__ . '/chat_messages.json';

if (!file_exists($archivo_json)) {
    echo json_encode(["success" => false, "error" => "El archivo de datos del chat no existe."]);
    exit();
}

// 4. Leer el archivo JSON
$contenido_json = file_get_contents($archivo_json);
$datos = json_decode($contenido_json, true) ?: [];

// 5. Ubicar el arreglo de mensajes (Tu post_message guarda un array directo, así que es $datos)
$mensajes = isset($datos['messages']) ? $datos['messages'] : $datos;

$cantidad_inicial = count($mensajes);

// 6. Filtrar los mensajes (AQUÍ ESTABA EL ERROR)
$mensajes_filtrados = array_filter($mensajes, function($msg) use ($id_eliminar) {
    // Si el mensaje es antiguo y NO tiene ID, lo conservamos (return true)
    if (!isset($msg['id'])) {
        return true; 
    }
    // Si tiene ID, lo conservamos SOLO si es diferente al que queremos borrar
    return $msg['id'] !== $id_eliminar;
});

// Comprobar si realmente se borró alguno
if (count($mensajes_filtrados) === $cantidad_inicial) {
    echo json_encode(["success" => false, "error" => "El mensaje no existe o ya fue eliminado."]);
    exit();
}

// 7. Reindexar el array para evitar errores de formato JSON
$mensajes_filtrados = array_values($mensajes_filtrados);

// 8. Reconstruir la estructura original
if (isset($datos['messages'])) {
    $datos['messages'] = $mensajes_filtrados;
} else {
    $datos = $mensajes_filtrados;
}

// 9. Guardar los cambios de nuevo en el archivo JSON
if (file_put_contents($archivo_json, json_encode($datos, JSON_UNESCAPED_UNICODE))) {
    echo json_encode(["success" => true, "message" => "Mensaje eliminado exitosamente."]);
} else {
    echo json_encode(["success" => false, "error" => "Error al guardar en el archivo JSON."]);
}
?>