<?php

declare(strict_types=1);
$dataFile = __DIR__ . '/data/solicitudes.json';
if (!file_exists($dataFile)) die('Error: no se encontró data/solicitudes.json');
$solicitudes = json_decode(file_get_contents($dataFile), true);
if (!is_array($solicitudes)) die('Error: JSON inválido.');

function clasificarSolicitud(string $asunto, string $mensaje): array
{
    $texto = mb_strtolower($asunto . ' ' . $mensaje, 'UTF-8');
    $reglas = [
        'Reclamo' => ['reclamo', 'cobrado dos veces', 'no solucionó', 'demora en atención'],
        'Consulta técnica' => ['error técnico', 'configurar', 'configuración', 'conexión', 'problema de conexión', 'problemas de conexión', 'plataforma'],
        'Solicitud comercial' => ['cotización', 'comercial', 'precios', 'propuesta económica', 'propuesta de servicio', 'planes'],
        'Soporte operativo' => ['soporte operativo', 'procedimiento operativo', 'datos de operación', 'sede', 'operación'],
        'Solicitud de cliente' => ['solicitud de información', 'pregunta sobre servicio', 'requisitos', 'horarios', 'canales de atención']
    ];
    $categoria = 'Solicitud de cliente';
    foreach ($reglas as $nombre => $palabras) {
        foreach ($palabras as $palabra) {
            if (mb_strpos($texto, $palabra, 0, 'UTF-8') !== false) {
                $categoria = $nombre;
                break 2;
            }
        }
    }
    $areas = [
        'Reclamo' => 'Servicio al cliente',
        'Consulta técnica' => 'Soporte técnico',
        'Solicitud comercial' => 'Comercial',
        'Soporte operativo' => 'Operaciones',
        'Solicitud de cliente' => 'Atención al cliente'
    ];
    $esUrgente = preg_match('/urgente|urgencia|caído|interrupción|afectando la producción|afecta la operación|en curso/i', $texto);
    if ($esUrgente) {
        $prioridad = 'Crítica';
        $tiempo = 1;
    } elseif ($categoria === 'Reclamo') {
        $prioridad = 'Alta';
        $tiempo = 3;
    } elseif ($categoria === 'Consulta técnica' || $categoria === 'Soporte operativo') {
        $prioridad = 'Media';
        $tiempo = 5;
    } else {
        $prioridad = 'Baja';
        $tiempo = 10;
    }
    return ['categoria' => $categoria, 'area' => $areas[$categoria], 'prioridad' => $prioridad, 'tiempo' => $tiempo];
}
function clasePrioridad(string $p): string
{
    return match ($p) {
        'Crítica' => 'critical',
        'Alta' => 'high',
        'Media' => 'medium',
        'Baja' => 'low',
        default => 'low'
    };
}

$inicio = microtime(true);
$resultados = [];
foreach ($solicitudes as $s) $resultados[] = array_merge($s, clasificarSolicitud($s['asunto'], $s['mensaje']));
$tiempoProcesamientoMs = (microtime(true) - $inicio) * 1000;

$buscar = trim($_GET['buscar'] ?? '');
$urgencia = trim($_GET['urgencia'] ?? '');
$areaFiltro = trim($_GET['area'] ?? '');
$categoriaFiltro = trim($_GET['categoria'] ?? '');
$orden = $_GET['orden'] ?? 'prioridad';
$resultadosFiltrados = array_values(array_filter($resultados, function (array $s) use ($buscar, $urgencia, $areaFiltro, $categoriaFiltro): bool {
    $texto = $s['id'] . ' ' . $s['cliente'] . ' ' . $s['asunto'] . ' ' . $s['mensaje'];
    $b = $buscar === '' || mb_stripos($texto, $buscar, 0, 'UTF-8') !== false;
    return $b && ($urgencia === '' || $s['prioridad'] === $urgencia) && ($areaFiltro === '' || $s['area'] === $areaFiltro) && ($categoriaFiltro === '' || $s['categoria'] === $categoriaFiltro);
}));
$pv = ['Crítica' => 1, 'Alta' => 2, 'Media' => 3, 'Baja' => 4];
if ($orden === 'prioridad') usort($resultadosFiltrados, fn($a, $b) => $pv[$a['prioridad']] <=> $pv[$b['prioridad']]);
elseif ($orden === 'tiempo') usort($resultadosFiltrados, fn($a, $b) => $a['tiempo'] <=> $b['tiempo']);
elseif ($orden === 'cliente') usort($resultadosFiltrados, fn($a, $b) => strcmp($a['cliente'], $b['cliente']));

$total = count($resultados);
$totalFiltrados = count($resultadosFiltrados);
$criticas = count(array_filter($resultados, fn($r) => $r['prioridad'] === 'Crítica'));
$altas = count(array_filter($resultados, fn($r) => $r['prioridad'] === 'Alta'));
$categorias = [];
$areas = [];
foreach ($resultados as $r) {
    $categorias[$r['categoria']] = ($categorias[$r['categoria']] ?? 0) + 1;
    $areas[$r['area']] = ($areas[$r['area']] ?? 0) + 1;
}
$promedioAsignacion = $total ? array_sum(array_column($resultados, 'tiempo')) / $total : 0;
$detalleId = $_GET['detalle'] ?? '';
$detalle = null;
foreach ($resultados as $s) {
    if ($s['id'] === $detalleId) {
        $detalle = $s;
        break;
    }
}
?>
<!doctype html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Bandeja inteligente - Caso 2</title>
    <link rel="stylesheet" href="assets/styles.css">
</head>

<body>
    <header class="topbar">
        <div><span class="eyebrow">CASO 2 · SOLUCIÓN TO-BE</span>
            <h1>Bandeja inteligente de solicitudes</h1>
            <p>Clasificación, asignación y priorización automática mediante PHP.</p>
        </div>
        <div class="technology">PHP + HTML + CSS + JSON</div>
    </header>
    <main class="container">
        <section class="metrics">
            <article class="metric"><span>Volumen total</span><strong><?= $total ?></strong><small>solicitudes de ejemplo</small></article>
            <article class="metric"><span>Críticas + altas</span><strong><?= $criticas + $altas ?></strong><small><?= $criticas ?> críticas · <?= $altas ?> altas</small></article>
            <article class="metric"><span>Tiempo promedio</span><strong><?= number_format($promedioAsignacion, 1, ',', '.') ?> min</strong><small>estimado de asignación</small></article>
            <article class="metric"><span>Procesamiento PHP</span><strong><?= number_format($tiempoProcesamientoMs, 2, ',', '.') ?> ms</strong><small>tiempo del clasificador</small></article>
        </section>
        <section class="panel">
            <div class="panel-title">
                <div><span class="eyebrow">CONTROL DE BANDEJA</span>
                    <h2>Buscar y filtrar solicitudes</h2>
                </div><a class="button secondary" href="index.php">Limpiar filtros</a>
            </div>
            <form method="GET" class="filters">
                <div class="field"><label>Buscar</label><input type="text" name="buscar" placeholder="ID, cliente o asunto..." value="<?= htmlspecialchars($buscar) ?>"></div>
                <div class="field"><label>Urgencia</label><select name="urgencia">
                        <option value="">Todas</option><?php foreach (['Crítica', 'Alta', 'Media', 'Baja'] as $v): ?><option value="<?= $v ?>" <?= $urgencia === $v ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?>
                    </select></div>
                <div class="field"><label>Área</label><select name="area">
                        <option value="">Todas</option><?php foreach (array_keys($areas) as $v): ?><option value="<?= htmlspecialchars($v) ?>" <?= $areaFiltro === $v ? 'selected' : '' ?>><?= htmlspecialchars($v) ?></option><?php endforeach; ?>
                    </select></div>
                <div class="field"><label>Categoría</label><select name="categoria">
                        <option value="">Todas</option><?php foreach (array_keys($categorias) as $v): ?><option value="<?= htmlspecialchars($v) ?>" <?= $categoriaFiltro === $v ? 'selected' : '' ?>><?= htmlspecialchars($v) ?></option><?php endforeach; ?>
                    </select></div>
                <div class="field"><label>Ordenar por</label><select name="orden">
                        <option value="prioridad" <?= $orden === 'prioridad' ? 'selected' : '' ?>>Prioridad</option>
                        <option value="tiempo" <?= $orden === 'tiempo' ? 'selected' : '' ?>>Tiempo</option>
                        <option value="cliente" <?= $orden === 'cliente' ? 'selected' : '' ?>>Cliente</option>
                    </select></div>
                <div class="field button-field"><button class="button primary" type="submit">Aplicar filtros</button></div>
            </form>
            <div class="filter-result">Mostrando <strong><?= $totalFiltrados ?></strong> de <strong><?= $total ?></strong> solicitudes.</div>
        </section>
        <section class="panel">
            <div class="panel-title">
                <div><span class="eyebrow">BANDEJA AUTOMÁTICA</span>
                    <h2>Solicitudes clasificadas</h2>
                </div>
                <div class="legend"><span class="pill critical">Crítica</span><span class="pill high">Alta</span><span class="pill medium">Media</span><span class="pill low">Baja</span></div>
            </div>
            <?php if (!$resultadosFiltrados): ?><div class="empty">No se encontraron solicitudes con los filtros seleccionados.</div><?php else: ?><div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Cliente</th>
                                <th>Asunto</th>
                                <th>Categoría</th>
                                <th>Área responsable</th>
                                <th>Urgencia</th>
                                <th>Asignación</th>
                                <th>Detalle</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($resultadosFiltrados as $r): ?><tr>
                                    <td><strong><?= htmlspecialchars($r['id']) ?></strong></td>
                                    <td><?= htmlspecialchars($r['cliente']) ?></td>
                                    <td><?= htmlspecialchars($r['asunto']) ?></td>
                                    <td><span class="category"><?= htmlspecialchars($r['categoria']) ?></span></td>
                                    <td><?= htmlspecialchars($r['area']) ?></td>
                                    <td><span class="pill <?= clasePrioridad($r['prioridad']) ?>"><?= htmlspecialchars($r['prioridad']) ?></span></td>
                                    <td><strong><?= $r['tiempo'] ?> min</strong></td>
                                    <td><a class="detail-link" href="?detalle=<?= urlencode($r['id']) ?>">Ver</a></td>
                                </tr><?php endforeach; ?>
                        </tbody>
                    </table>
                </div><?php endif; ?>
        </section>
        <section class="grid2">
            <article class="panel compact"><span class="eyebrow">DISTRIBUCIÓN</span>
                <h2>Por categoría</h2><?php foreach ($categorias as $n => $c): ?><div class="bar-row"><span><?= htmlspecialchars($n) ?></span><strong><?= $c ?></strong></div>
                    <div class="bar"><i style="width:<?= ($c / $total) * 100 ?>%"></i></div><?php endforeach; ?>
            </article>
            <article class="panel compact"><span class="eyebrow">ASIGNACIÓN</span>
                <h2>Por área responsable</h2><?php foreach ($areas as $n => $c): ?><div class="bar-row"><span><?= htmlspecialchars($n) ?></span><strong><?= $c ?></strong></div>
                    <div class="bar"><i style="width:<?= ($c / $total) * 100 ?>%"></i></div><?php endforeach; ?>
            </article>
        </section>
        
    </main>
    <?php if ($detalle !== null): ?><div class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <div><span class="eyebrow">DETALLE DE SOLICITUD</span>
                        <h2><?= htmlspecialchars($detalle['id']) ?></h2>
                    </div><a class="close" href="index.php">×</a>
                </div>
                <div class="detail-grid">
                    <div><span>Cliente</span><strong><?= htmlspecialchars($detalle['cliente']) ?></strong></div>
                    <div><span>Hora</span><strong><?= htmlspecialchars($detalle['hora']) ?></strong></div>
                    <div><span>Categoría</span><strong><?= htmlspecialchars($detalle['categoria']) ?></strong></div>
                    <div><span>Área responsable</span><strong><?= htmlspecialchars($detalle['area']) ?></strong></div>
                    <div><span>Urgencia</span><strong><span class="pill <?= clasePrioridad($detalle['prioridad']) ?>"><?= htmlspecialchars($detalle['prioridad']) ?></span></strong></div>
                    <div><span>Tiempo estimado</span><strong><?= $detalle['tiempo'] ?> minutos</strong></div>
                </div>
                <div class="message"><span>ASUNTO</span>
                    <h3><?= htmlspecialchars($detalle['asunto']) ?></h3><span>MENSAJE</span>
                    <p><?= htmlspecialchars($detalle['mensaje']) ?></p>
                </div><a href="index.php" class="button primary">Volver a la bandeja</a>
            </div>
        </div><?php endif; ?>
    <footer>Prototipo académico · Datos simulados · Clasificación mediante reglas PHP · No envía correos reales.</footer>
</body>

</html>