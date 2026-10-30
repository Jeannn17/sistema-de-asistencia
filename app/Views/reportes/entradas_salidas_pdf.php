<?php
/**
 * Plantilla del reporte de ENTRADAS Y SALIDAS POR DÍA (hoja horizontal, la convierte Dompdf en ReporteController).
 * Datos: $filtros, $reporte (ReporteModel::generarDetallado), $institucion, $generadoPor, $generadoEl
 * Una fila por trabajador y una columna por día. El mes se parte en dos tablas (1-15 y 16-fin).
 * Dompdf solo entiende CSS 2.1 (sin flexbox ni grid): la maquetación usa tablas.
 */
use App\Models\JustificacionModel;
use App\Models\ReporteModel;

$e   = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$tot = $reporte['totales'];
$dm  = fn(string $fecha) => date('d/m', strtotime($fecha));

// Abreviatura de cada permiso para que quepa en la celda (se explica en la leyenda)
$abreviaturas = [
    'reposo'            => 'REP',
    'accidente_laboral' => 'ACC',
    'permiso_dia'       => 'PER',
    'salida_temprana'   => 'PST',
    'llegada_tarde'     => 'PLT',
];
$ultimoBloque = count($reporte['bloques']) - 1;
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Entradas y salidas por día</title>
<style>
    @page { margin: 115px 28px 50px 28px; }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 7.5px; color: #1e293b; background: #fff; }

    /* Membrete y pie: se repiten en todas las páginas */
    header { position: fixed; top: -95px; left: 0; right: 0; height: 85px; }
    footer { position: fixed; bottom: -32px; left: 0; right: 0; height: 18px;
             border-top: 1px solid #cbd5e1; padding-top: 4px; font-size: 7px; color: #64748b; }
    .membrete { width: 100%; border-bottom: 2px solid #1e3a8a; }
    .membrete td { vertical-align: middle; }
    .membrete .logo { width: 70px; }
    .membrete .logo img { max-width: 60px; max-height: 60px; }
    .membrete .textos { text-align: center; }
    .membrete .linea { font-size: 8px; color: #475569; }
    .membrete .nombre { font-size: 14px; font-weight: bold; color: #1e3a8a; margin-top: 2px; }
    .membrete .sistema { font-size: 8px; color: #64748b; margin: 2px 0 6px; }

    h1 { font-size: 12px; text-align: center; margin: 0 0 6px; color: #0f172a; letter-spacing: 0.5px; }

    .ficha { width: 100%; border-collapse: collapse; margin-bottom: 8px; font-size: 8px; }
    .ficha td { padding: 3px 6px; border: 1px solid #e2e8f0; }
    .ficha .etq { width: 11%; background: #f1f5f9; font-weight: bold; color: #475569; }

    h2 { font-size: 9px; background: #1e3a8a; color: #fff; padding: 3px 6px; margin: 10px 0 0; }

    table.planilla { width: 100%; border-collapse: collapse; }
    table.planilla thead { display: table-header-group; }   /* repite la cabecera si la tabla salta de página */
    table.planilla th { background: #e2e8f0; color: #334155; font-size: 6.5px; padding: 2px 1px;
                        border: 1px solid #cbd5e1; text-align: center; }
    table.planilla td { padding: 2px; border: 1px solid #e2e8f0; vertical-align: middle; }
    table.planilla tr { page-break-inside: avoid; }
    table.planilla tbody tr:nth-child(even) td.dato { background: #f8fafc; }
    .texto-izq { text-align: left !important; }
    .num { text-align: center; }

    /* Celdas de cada día */
    td.dia { text-align: center; font-size: 6.5px; line-height: 1.25; }
    td.dia .ent { color: #0f172a; }
    td.dia .sal { color: #475569; }
    td.retardo .ent { color: #be123c; font-weight: bold; }
    td.falta { background: #fee2e2; color: #be123c; font-weight: bold; }
    td.justificado { background: #fef3c7; color: #92400e; font-weight: bold; }
    td.descanso, th.descanso { background: #f1f5f9; color: #94a3b8; }
    td.sin-ingreso { color: #cbd5e1; }
    th.descanso { color: #64748b; }

    .total { font-weight: bold; }
    .alerta { color: #be123c; }

    .leyenda { width: 100%; margin-top: 10px; border-collapse: collapse; font-size: 7px; color: #475569; }
    .leyenda td { padding: 2px 4px; vertical-align: top; }
    .muestra { display: inline-block; width: 18px; text-align: center; font-weight: bold; border: 1px solid #e2e8f0; }

    .firmas { width: 100%; margin-top: 35px; page-break-inside: avoid; }
    .firmas td { width: 50%; text-align: center; padding: 0 60px; }
    .firmas .raya { border-top: 1px solid #334155; padding-top: 3px; }
</style>
</head>
<body>

<?php require __DIR__ . '/_membrete.php'; ?>

<h1>REPORTE <?= $e(mb_strtoupper(ReporteModel::FRECUENCIAS[$filtros['frecuencia']])) ?> DE ENTRADAS Y SALIDAS DEL PERSONAL</h1>

<table class="ficha">
    <tr>
        <td class="etq">Período</td>
        <td><?= $e($filtros['periodo']) ?></td>
        <td class="etq">Departamento</td>
        <td><?= $e($filtros['departamento']) ?></td>
        <td class="etq">Trabajadores</td>
        <td><?= (int) $tot['trabajadores'] ?></td>
    </tr>
    <tr>
        <td class="etq">Desde</td>
        <td><?= $e(date('d/m/Y', strtotime($filtros['desde']))) ?></td>
        <td class="etq">Hasta</td>
        <td><?= $e(date('d/m/Y', strtotime($filtros['hasta']))) ?></td>
        <td class="etq">Asist. / Ret. / Faltas</td>
        <td><?= (int) $tot['asistencias'] ?> / <?= (int) $tot['retardos'] ?> / <?= (int) $tot['faltas'] ?></td>
    </tr>
</table>

<?php foreach ($reporte['bloques'] as $b => $fechas): ?>
    <?php $conTotales = $b === $ultimoBloque; ?>
    <?php if (count($reporte['bloques']) > 1): ?>
        <h2>Días <?= $e($dm(reset($fechas))) ?> al <?= $e($dm(end($fechas))) ?></h2>
    <?php endif; ?>
    <table class="planilla">
        <thead>
            <tr>
                <th rowspan="2" style="width: 16px">N°</th>
                <th rowspan="2" style="width: 26px">ID</th>
                <th rowspan="2" style="width: 46px">Cédula</th>
                <th rowspan="2" class="texto-izq" style="width: 72px">Apellidos</th>
                <th rowspan="2" class="texto-izq" style="width: 72px">Nombres</th>
                <th rowspan="2" class="texto-izq" style="width: 62px">Departamento</th>
                <?php foreach ($fechas as $fecha): $d = $reporte['dias'][$fecha]; ?>
                    <th class="<?= $d['fin_semana'] ? 'descanso' : '' ?>"><?= $e($d['nombre']) ?></th>
                <?php endforeach; ?>
                <?php if ($conTotales): ?>
                    <th colspan="3">Total del período</th>
                <?php endif; ?>
            </tr>
            <tr>
                <?php foreach ($fechas as $fecha): $d = $reporte['dias'][$fecha]; ?>
                    <th class="<?= $d['fin_semana'] ? 'descanso' : '' ?>"><?= $e($dm($fecha)) ?></th>
                <?php endforeach; ?>
                <?php if ($conTotales): ?>
                    <th style="width: 22px">Asist.</th>
                    <th style="width: 22px">Ret.</th>
                    <th style="width: 22px">Faltas</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($reporte['trabajadores'] as $n => $t): ?>
                <tr>
                    <td class="dato num"><?= $n + 1 ?></td>
                    <td class="dato num"><?= $e($t['nro_registro'] ?? '—') ?></td>
                    <td class="dato num"><?= $e($t['cedula']) ?></td>
                    <td class="dato"><?= $e($t['apellido']) ?></td>
                    <td class="dato"><?= $e($t['nombre']) ?></td>
                    <td class="dato"><?= $e($t['departamento']) ?></td>
                    <?php foreach ($fechas as $fecha): ?>
                        <?php $c = $t['dias'][$fecha] ?? null; ?>
                        <?php if ($c === null): ?>
                            <td class="dia sin-ingreso">·</td>
                        <?php elseif ($c['estado'] === 'falta'): ?>
                            <td class="dia falta">F</td>
                        <?php elseif ($c['estado'] === 'justificado'): ?>
                            <td class="dia justificado"><?= $e($abreviaturas[$c['justificacion']] ?? 'J') ?></td>
                        <?php elseif ($c['estado'] === 'descanso'): ?>
                            <td class="dia descanso"></td>
                        <?php else: ?>
                            <td class="dia <?= $c['estado'] === 'retardo' ? 'retardo' : '' ?>">
                                <div class="ent"><?= $e($c['entrada'] ?? '—') ?></div>
                                <div class="sal"><?= $e($c['salida'] ?? '—') ?></div>
                            </td>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    <?php if ($conTotales): $r = $t['resumen']; ?>
                        <td class="dato num total"><?= (int) $r['asistencias'] ?></td>
                        <td class="dato num total<?= $r['retardos'] > 0 ? ' alerta' : '' ?>"><?= (int) $r['retardos'] ?></td>
                        <td class="dato num total<?= $r['faltas'] > 0 ? ' alerta' : '' ?>"><?= (int) $r['faltas'] ?></td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endforeach; ?>

<table class="leyenda">
    <tr>
        <td style="width: 34%">
            <strong>Cómo leer cada día:</strong> arriba la primera entrada y abajo la última salida (— = no marcó).
            Entrada en <span class="alerta"><strong>rojo</strong></span>: retardo (más de <?= \App\Models\AsistenciaModel::TOLERANCIA_MIN ?> min después del inicio del turno).
            ID = N° de registro del biométrico.
        </td>
        <td style="width: 33%">
            <span class="muestra" style="background:#fee2e2;color:#be123c">F</span> Falta (día hábil sin marcación ni permiso)<br>
            <span class="muestra" style="background:#f1f5f9">&nbsp;</span> Sábado o domingo sin marcación<br>
            <span class="muestra" style="color:#cbd5e1">·</span> Antes de la fecha de ingreso
        </td>
        <td style="width: 33%">
            <?php foreach ($abreviaturas as $tipo => $abr): ?>
                <span class="muestra" style="background:#fef3c7;color:#92400e"><?= $e($abr) ?></span> <?= $e(JustificacionModel::TIPOS[$tipo][0] ?? $tipo) ?><br>
            <?php endforeach; ?>
        </td>
    </tr>
</table>

<table class="firmas">
    <tr>
        <td><div class="raya">Elaborado por</div></td>
        <td><div class="raya">Dirección</div></td>
    </tr>
</table>

</body>
</html>
