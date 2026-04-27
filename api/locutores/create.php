<?php
require_once "../../config/cors.php";
require_once "../../config/database.php";
require_once "../../config/auth.php";

configurarCORS();
requireAuth();

$db = conectarDB();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Cuando usamos FormData, los textos llegan en $_POST y los archivos en $_FILES
if (!isset($_POST['nombre']) || !isset($_POST['cargo']) || !isset($_FILES['foto'])) {
    echo json_encode(["success" => false, "message" => "Faltan datos obligatorios"]);
    exit;
}

$nombre = $_POST['nombre'];
$cargo = $_POST['cargo'];
$instagram = isset($_POST['instagram_usuario']) ? $_POST['instagram_usuario'] : '';
$foto = $_FILES['foto'];

// 1. Validaciones de la imagen
if ($foto['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(["success" => false, "message" => "Error al subir la imagen. Código: " . $foto['error']]);
    exit;
}

// Validar que realmente sea una imagen por seguridad
$allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
if (!in_array($foto['type'], $allowedTypes)) {
    echo json_encode(["success" => false, "message" => "Formato de imagen no permitido. Usa JPG, PNG o WEBP."]);
    exit;
}

// 2. Procesar y guardar la imagen físicamente en el servidor
$extension = pathinfo($foto['name'], PATHINFO_EXTENSION);
// Creamos un nombre único: timestamp_textoaleatorio.extension
$nombreNuevoArchivo = time() . '_' . uniqid() . '.' . $extension;

// Ruta absoluta a la carpeta de destino
$directorioDestino = __DIR__ . '/../../uploads/locutores/';
$rutaCompleta = $directorioDestino . $nombreNuevoArchivo;

// Movemos el archivo de la memoria temporal del servidor a nuestra carpeta final
if (!move_uploaded_file($foto['tmp_name'], $rutaCompleta)) {
    echo json_encode(["success" => false, "message" => "No se pudo guardar la imagen en el servidor (Verifica los permisos de la carpeta)."]);
    exit;
}

try {
    // 3. Guardar en la base de datos (solo el nombre del archivo, no la imagen entera)
    $stmt = $db->prepare("INSERT INTO locutores (nombre, cargo, instagram_usuario, foto) VALUES (?, ?, ?, ?)");
    $stmt->execute([$nombre, $cargo, $instagram, $nombreNuevoArchivo]);

    echo json_encode([
        "success" => true, 
        "message" => "Locutor agregado correctamente",
        "foto_url" => $nombreNuevoArchivo // Devolvemos el nombre generado por si React lo necesita
    ]);

} catch (PDOException $e) {
    // Si la BD falla, borramos la imagen que acabamos de subir para no tener archivos "huérfanos"
    if (file_exists($rutaCompleta)) {
        unlink($rutaCompleta);
    }
    echo json_encode(["success" => false, "message" => "Error de BD: " . $e->getMessage()]);
}
?>