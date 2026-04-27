<?php

/**
 * Sistema centralizado de subida, validación y compresión de imágenes.
 *
 * Funciones disponibles:
 *   subirImagen($_FILES['campo'], 'seccion', $opciones)  → sube y comprime
 *   eliminarImagen('uploads/seccion/archivo.webp')       → borra del disco
 *
 * Uso:
 *   require_once "../../utils/upload.php";
 *   $resultado = subirImagen($_FILES['campo'], 'seccion', $opciones);
 *
 * Opciones disponibles:
 *   max_bytes  → Peso máximo en bytes       (default: 5MB)
 *   max_ancho  → Ancho máximo en píxeles    (default: 1200)
 *   max_alto   → Alto máximo en píxeles     (default: 1200)
 *   calidad    → Calidad WebP/JPEG (0-100)  (default: 75)
 *
 * El archivo siempre se guarda en: uploads/{seccion}/
 * Retorna: ['success' => bool, 'path' => string, 'message' => string]
 */

function subirImagen(array $archivo, string $seccion, array $opciones = []): array
{
    $maxBytes = $opciones['max_bytes'] ?? 5 * 1024 * 1024; // 5 MB
    $maxAncho = $opciones['max_ancho'] ?? 1200;
    $maxAlto  = $opciones['max_alto']  ?? 1200;
    $calidad  = $opciones['calidad']   ?? 75; // calidad 75 → ~70-80% reducción vs original

    // ── 1. Error de subida ──────────────────────────────────────────────────
    if ($archivo['error'] !== UPLOAD_ERR_OK) {
        $errores = [
            UPLOAD_ERR_INI_SIZE   => 'El archivo supera el límite del servidor.',
            UPLOAD_ERR_FORM_SIZE  => 'El archivo supera el límite del formulario.',
            UPLOAD_ERR_PARTIAL    => 'La subida fue interrumpida.',
            UPLOAD_ERR_NO_FILE    => 'No se seleccionó ningún archivo.',
            UPLOAD_ERR_NO_TMP_DIR => 'No hay carpeta temporal en el servidor.',
            UPLOAD_ERR_CANT_WRITE => 'Sin permisos de escritura en disco.',
        ];
        return ['success' => false, 'path' => '', 'message' => $errores[$archivo['error']] ?? 'Error al subir el archivo.'];
    }

    // ── 2. Peso del archivo ─────────────────────────────────────────────────
    if ($archivo['size'] > $maxBytes) {
        $mb = round($maxBytes / 1024 / 1024, 1);
        return ['success' => false, 'path' => '', 'message' => "La imagen supera el límite de {$mb} MB."];
    }

    // ── 3. Tipo MIME real (no confiar en $_FILES['type']) ───────────────────
    $mime = mime_content_type($archivo['tmp_name']);
    $permitidos = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    if (!in_array($mime, $permitidos)) {
        return ['success' => false, 'path' => '', 'message' => 'Formato no permitido. Usa JPG, PNG, WEBP o GIF.'];
    }

    // ── 4. Verificar extensión GD ───────────────────────────────────────────
    if (!extension_loaded('gd')) {
        return ['success' => false, 'path' => '', 'message' => 'El servidor no tiene la extensión GD instalada.'];
    }

    // ── 5. Cargar imagen en memoria ─────────────────────────────────────────
    $img = match ($mime) {
        'image/jpeg' => imagecreatefromjpeg($archivo['tmp_name']),
        'image/png'  => imagecreatefrompng($archivo['tmp_name']),
        'image/webp' => imagecreatefromwebp($archivo['tmp_name']),
        'image/gif'  => imagecreatefromgif($archivo['tmp_name']),
    };

    if (!$img) {
        return ['success' => false, 'path' => '', 'message' => 'No se pudo procesar la imagen.'];
    }

    $ancho = imagesx($img);
    $alto  = imagesy($img);

    // ── 6. Redimensionar si supera el máximo ────────────────────────────────
    if ($ancho > $maxAncho || $alto > $maxAlto) {
        $ratio  = min($maxAncho / $ancho, $maxAlto / $alto);
        $nAncho = (int) round($ancho * $ratio);
        $nAlto  = (int) round($alto  * $ratio);

        $imgR = imagecreatetruecolor($nAncho, $nAlto);

        // Preservar canal alfa (transparencia)
        imagealphablending($imgR, false);
        imagesavealpha($imgR, true);
        $alfa = imagecolorallocatealpha($imgR, 0, 0, 0, 127);
        imagefilledrectangle($imgR, 0, 0, $nAncho, $nAlto, $alfa);

        imagecopyresampled($imgR, $img, 0, 0, 0, 0, $nAncho, $nAlto, $ancho, $alto);
        imagedestroy($img);

        $img   = $imgR;
        $ancho = $nAncho;
        $alto  = $nAlto;
    }

    // ── 7. Crear directorio de destino ──────────────────────────────────────
    $dirBase = dirname(__DIR__) . '/uploads/' . $seccion . '/';
    if (!is_dir($dirBase)) {
        mkdir($dirBase, 0755, true);
    }

    // ── 8. Guardar: preferimos WebP (soporte alfa + mejor compresión) ───────
    $nombre = time() . '_' . uniqid();

    if (function_exists('imagewebp')) {
        $nombreFinal = $nombre . '.webp';
        imagewebp($img, $dirBase . $nombreFinal, $calidad);
    } elseif ($mime === 'image/png') {
        // Fallback: PNG con compresión máxima (nivel 9)
        $nombreFinal = $nombre . '.png';
        imagepng($img, $dirBase . $nombreFinal, 9);
    } else {
        // Fallback: JPEG
        $nombreFinal = $nombre . '.jpg';
        imagejpeg($img, $dirBase . $nombreFinal, $calidad);
    }

    imagedestroy($img);

    return [
        'success' => true,
        'path'    => 'uploads/' . $seccion . '/' . $nombreFinal,
        'message' => 'Imagen procesada y comprimida correctamente.',
    ];
}

/**
 * Elimina del disco una imagen subida por el sistema.
 * Solo actúa sobre rutas relativas que empiecen por "uploads/"
 * para evitar borrar archivos del sistema por error.
 *
 * @param string|null $ruta  Ruta relativa guardada en BD (ej: "uploads/configuracion/xxx.webp")
 * @return bool  true si se eliminó, false si no existía o no era una ruta válida
 */
function eliminarImagen(?string $ruta): bool
{
    if (!$ruta || !str_starts_with($ruta, 'uploads/')) {
        return false; // No es un archivo nuestro (URL externa, vacío, etc.)
    }

    $rutaAbsoluta = dirname(__DIR__) . '/' . $ruta;

    if (file_exists($rutaAbsoluta)) {
        return unlink($rutaAbsoluta);
    }

    return false;
}
