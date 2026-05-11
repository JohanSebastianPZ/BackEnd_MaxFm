<?php
require_once "../../config/cors.php";
require_once "../../config/database.php";
require_once "../../config/auth.php";
require_once "../../utils/upload.php";

configurarCORS();
requireAuth();

$db = conectarDB();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// 1. Recibir los datos del formulario (sin campos como 'activo' o 'fecha')
$titulo    = trim($_POST['titulo']    ?? '');
$slug      = trim($_POST['slug']      ?? '');
$resumen   = trim($_POST['resumen']   ?? '');
$contenido = trim($_POST['contenido'] ?? '');

// 2. Validación básica
if (!$titulo || !$slug) {
    echo json_encode(['success' => false, 'message' => 'El título y el slug son obligatorios.']);
    exit;
}

try {
    // 3. Imagen opcional al crear (puede subirse después desde el panel de edición)
    $imagenPath = null;
    // Fíjate que ahora buscamos 'imagen' en lugar de 'foto'
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $res = subirImagen($_FILES['imagen'], 'noticias', [
            'max_bytes'    => 5 * 1024 * 1024,
            'max_ancho'    => 900,
            'max_alto'     => 560,
            'calidad'      => 80,
            'thumb_ancho'  => 400,  // tarjeta card: ~375px CSS × ~1x en mobile
            'thumb_alto'   => 250,
            'thumb_calidad'=> 65,
        ]);
        if (!$res['success']) { 
            echo json_encode($res); 
            exit; 
        }
        $imagenPath = $res['path'];
    }

    // 4. Insertar en la base de datos
    // Nota: 'creado_en' y 'actualizado_en' se llenan solos por el DEFAULT CURRENT_TIMESTAMP
    $stmt = $db->prepare("INSERT INTO noticias (titulo, slug, resumen, contenido, imagen) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$titulo, $slug, $resumen, $contenido, $imagenPath]);
    
    $newId = $db->lastInsertId();

    // 5. Devolver la noticia recién creada para que React actualice la tabla
    $sn = $db->prepare("SELECT * FROM noticias WHERE id = :id");
    $sn->execute([':id' => $newId]);
    $noticia = $sn->fetch(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'noticia' => $noticia, 'message' => 'Noticia creada con éxito.']);

} catch (PDOException $e) {
    // 6. Manejar error si el 'slug' ya existe (violación de la restricción UNIQUE)
    if ($e->getCode() == 23000) {
        echo json_encode(['success' => false, 'message' => 'Error: El slug (URL) ya existe. Por favor, modifica el título o el slug manualmente.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error en la base de datos: ' . $e->getMessage()]);
    }
}