<?php
require_once "../../config/cors.php";
require_once "../../config/database.php";

configurarCORS();

$db = conectarDB();
cargarEnv(dirname(__DIR__, 2) . '/.env');

// ── Zona horaria del servidor ──────────────────────────────────────────────
date_default_timezone_set($_ENV['TIMEZONE'] ?? 'America/Bogota');

$ahora_hora  = date('H:i:s');
$diasMap     = ['Sunday'=>'domingo','Monday'=>'lunes','Tuesday'=>'martes',
                'Wednesday'=>'miercoles','Thursday'=>'jueves',
                'Friday'=>'viernes','Saturday'=>'sabado'];
$diaActual   = $diasMap[date('l')];

function horaAMin(string $h): int {
    $p = explode(':', $h);
    return (int)$p[0] * 60 + (int)$p[1];
}

function enAire(array $p, string $dia, int $minActual): bool {
    $dias   = json_decode($p['dias'], true) ?? [];
    $inicio = horaAMin($p['hora_inicio']);
    $fin    = horaAMin($p['hora_fin']);

    if ($fin > $inicio) {
        // Programa normal (no cruza medianoche)
        return in_array($dia, $dias) && $minActual >= $inicio && $minActual < $fin;
    }
    // Cruza medianoche (ej: 20:00 – 07:00)
    $prevMap = ['lunes'=>'domingo','martes'=>'lunes','miercoles'=>'martes',
                'jueves'=>'miercoles','viernes'=>'jueves','sabado'=>'viernes','domingo'=>'sabado'];
    if ($minActual >= $inicio) {
        return in_array($dia, $dias);
    }
    $diaAnterior = $prevMap[$dia] ?? '';
    return in_array($diaAnterior, $dias);
}

// ── Cargar programas activos ───────────────────────────────────────────────
$programas = $db->query("
    SELECT p.*, l.nombre AS locutor_nombre
    FROM programas p
    LEFT JOIN locutores l ON p.locutor_id = l.id
    WHERE p.activo = 1
    ORDER BY p.orden ASC, p.id ASC
")->fetchAll(PDO::FETCH_ASSOC);

$minActual = horaAMin($ahora_hora);

// ── Detectar programa en vivo (primera coincidencia por orden) ─────────────
$enVivo   = null;
$enVivoIdx = -1;
foreach ($programas as $i => $p) {
    if (enAire($p, $diaActual, $minActual)) {
        $enVivo    = $p;
        $enVivoIdx = $i;
        break;
    }
}

// ── Calcular elapsed / total para la barra de progreso ────────────────────
$elapsedMin = 0;
$totalMin   = 60;

if ($enVivo) {
    $inicio = horaAMin($enVivo['hora_inicio']);
    $fin    = horaAMin($enVivo['hora_fin']);
    $totalMin = $fin > $inicio ? ($fin - $inicio) : (1440 - $inicio + $fin);
    $elapsedMin = $minActual >= $inicio
        ? ($minActual - $inicio)
        : (1440 - $inicio + $minActual); // cruza medianoche
    $enVivo['elapsed_minutos'] = $elapsedMin;
    $enVivo['total_minutos']   = $totalMin;
}

// ── Antes y después (programas del mismo día ordenados por hora) ───────────
$hoy = array_values(array_filter($programas, function($p) use ($diaActual) {
    return in_array($diaActual, json_decode($p['dias'], true) ?? []);
}));
usort($hoy, fn($a, $b) => strcmp($a['hora_inicio'], $b['hora_inicio']));

$antes   = null;
$despues = null;
if ($enVivo) {
    $enVivoInicio = horaAMin($enVivo['hora_inicio']);
    foreach (array_reverse($hoy) as $p) {
        if ($p['id'] !== $enVivo['id'] && horaAMin($p['hora_inicio']) < $enVivoInicio) {
            $antes = $p; break;
        }
    }
    foreach ($hoy as $p) {
        if ($p['id'] !== $enVivo['id'] && horaAMin($p['hora_inicio']) > $enVivoInicio) {
            $despues = $p; break;
        }
    }
}

echo json_encode([
    'success'   => true,
    'programas' => $programas,
    'servidor'  => ['hora' => $ahora_hora, 'dia' => $diaActual, 'minutos' => $minActual],
    'en_vivo'   => $enVivo,
    'antes'     => $antes,
    'despues'   => $despues,
]);
