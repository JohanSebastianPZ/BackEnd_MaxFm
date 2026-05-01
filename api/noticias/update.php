<?php
require_once "../../config/cors.php";
require_once "../../config/database.php";
require_once "../../config/auth.php";

configurarCORS();
requireAuth();

// 1. Recibir y decodificar los datos
$data = json_decode(file_get_contents('php://input'), true);
if (!$data || !isset($data['id'])) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos o falta el ID de la noticia.']);
    exit;
}

$db = conectarDB();
// Activamos las excepciones para poder atrapar el error del slug duplicado
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); 
$id = (int)$data['id'];

try {
    // 2. Preparamos la consulta (sin imagen y sin activo)
    $stmt = $db->prepare("UPDATE noticias SET
        titulo=?, slug=?, resumen=?, contenido=?, actualizado_en=CURRENT_TIMESTAMP
        WHERE id=?");

    // 3. Ejecutamos la consulta con los datos recibidos del panel
    $stmt->execute([
        $data['titulo']    ?? '',
        $data['slug']      ?? '',
        $data['resumen']   ?? '',
        $data['contenido'] ?? '',
        $id,
    ]);

    echo json_encode(['success' => true, 'message' => 'Noticia actualizada con éxito.']);

} catch (PDOException $e) {
    // 4. Si el código de error es 23000, significa que se violó una restricción UNIQUE (el slug)
    if ($e->getCode() == 23000) {
        echo json_encode(['success' => false, 'message' => 'Error: El slug (URL amigable) ya está siendo usado por otra noticia.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error en la base de datos: ' . $e->getMessage()]);
    }
}