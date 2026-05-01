<?php
require_once "../../config/cors.php";
require_once "../../config/database.php";
require_once "../../config/auth.php";
require_once "../../utils/upload.php";

configurarCORS();
requireAuth();

// 1. Verificamos que llegue el ID y el archivo con el nombre 'imagen'
if (!isset($_FILES['imagen'], $_POST['id'])) {
    echo json_encode(['success' => false, 'message' => 'Faltan datos o la imagen no se subió correctamente.']);
    exit;
}

$id = (int)$_POST['id'];
$db = conectarDB();

// 2. Buscamos la noticia para obtener la ruta de la imagen vieja
$noticia = $db->query("SELECT imagen FROM noticias WHERE id = $id")->fetch(PDO::FETCH_ASSOC);
if (!$noticia) {
    echo json_encode(['success' => false, 'message' => 'Noticia no encontrada.']);
    exit;
}

// 3. Procesamos y subimos la nueva imagen (Ajustado para formato panorámico)
$res = subirImagen($_FILES['imagen'], 'noticias', [
    'max_bytes' => 5 * 1024 * 1024, // 5 MB
    'max_ancho' => 1200,            // Ideal para cabeceras de noticias
    'max_alto'  => 800,
    'calidad'   => 80,
]);

// Si algo falló al subir, devolvemos el error
if (!$res['success']) { 
    echo json_encode($res); 
    exit; 
}

// 4. Borramos la imagen anterior para no llenar el servidor de archivos basura
$imagenAnterior = $noticia['imagen'] ?? '';
if ($imagenAnterior && !str_starts_with($imagenAnterior, 'uploads/')) {
    $imagenAnterior = 'uploads/noticias/' . $imagenAnterior;
}
eliminarImagen($imagenAnterior);

// 5. Actualizamos la base de datos con la nueva ruta
$stmt = $db->prepare("UPDATE noticias SET imagen=?, actualizado_en=CURRENT_TIMESTAMP WHERE id=?");
$stmt->execute([$res['path'], $id]);

// 6. Respondemos con éxito y devolvemos el nuevo 'path'
echo json_encode(['success' => true, 'path' => $res['path'], 'message' => 'Imagen actualizada con éxito.']);