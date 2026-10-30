<?php
/**
 * Plantilla del reporte de asistencia en PDF (la convierte Dompdf en ReporteController).
 * Datos: $filtros, $reporte, $institucion, $generadoPor, $generadoEl
 * Dompdf solo entiende CSS 2.1 (sin flexbox ni grid): la maquetación usa tablas.
 */
$e   = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$pct = fn($v) => $v === null ? '—' : number_format($v, 1, ',', '.') . ' %';
$tot = $reporte['totales'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Reporte de asistencia</title>
<style>
    @page { margin: 125px 40px 55px 40px; }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 9px; color: #1e293b; background: #fff; }

    /* Membrete y pie: se repiten en todas las páginas */
    header { position: fixed; top: -100px; left: 0; right: 0; height: 90px; }
    footer { position: fixed; bottom: -35px; left: 0; right: 0; height: 20px;
             border-top: 1px solid #cbd5e1; padding-top: 4px; font-size: 7px; color: #64748b; }
    .membrete { width: 100%; border-bottom: 2px solid #1e3a8a; }
    .membrete td { vertical-align: middle; }
    .membrete .logo { width: 70px; }
    .membrete .logo img { max-width: 64px; max-height: 64px; }
    .membrete .textos { text-align: center; }
    .membrete .linea { font-size: 8px; color: #475569; }
    .membrete .nombre { font-size: 14px; font-weight: bold; color: #1e3a8a; margin-top: 2px; }
    .membrete .sistema { font-size: 8px; color: #64748b; margin: 2px 0 6px; }

    h1 { font-size: 13px; text-align: center; margin: 0 0 8px; color: #0f172a; letter-spacing: 0.5px; }

    .ficha { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
    .ficha td { padding: 4px 6px; border: 1px solid #e2e8f0; }
    .ficha .etq { width: 18%; background: #f1f5f9; font-weight: bold; color: #475569; }

    .kpis { width: 100%; border-collapse: separate; border-spacing: 4px 0; margin: 0 -4px 12px; }
    .kpis td { width: 20%; border: 1px solid #e2e8f0; padding: 6px; text-align: center; }
    .kpis .valor { font-size: 13px; font-weight: bold; color: #1e3a8a; }
    .kpis .texto { font-size: 7px; color: #64748b; text-transform: uppercase; }

    h2 { font-size: 10px; background: #1e3a8a; color: #fff; padding: 4px 6px; margin: 12px 0 0; }

    table.datos { width: 100%; border-collapse: collapse; }
    table.datos th { background: #e2e8f0; color: #334155; font-size: 7.5px; text-transform: uppercase;
                     padding: 4px 3px; border: 1px solid #cbd5e1; }
    table.datos td { padding: 3px; border: 1px solid #e2e8f0; }
    table.datos tr:nth-child(even) td { background: #f8fafc; }
    table.datos tfoot td { background: #f1f5f9; font-weight: bold; }
    table.datos thead { display: table-header-group; }   /* repite la cabecera si la tabla salta de página */
    tr { page-break-inside: avoid; }
    .num { text-align: center; }
    .alerta { color: #be123c; font-weight: bold; }

    .detalle-titulo { font-size: 8.5px; font-weight: bold; margin: 8px 0 2px; color: #334155; }
    .nota { margin-top: 14px; font-size: 7.5px; color: #64748b; }

    .firmas { width: 100%; margin-top: 45px; page-break-inside: avoid; }
    .firmas td { width: 50%; text-align: center; padding: 0 30px; }
    .firmas .raya { border-top: 1px solid #334155; padding-top: 3px; }
</style>
</head>
<body>

<?php require __DIR__ . '/_membrete.php'; ?>

<h1>REPORTE <?= $e(mb_strtoupper(\App\Models\ReporteModel::FRECUENCIAS[$filtros['frecuencia']])) ?> DE ASISTENCIA DEL PERSONAL</h1>

<table class="ficha">
    <tr>
        <td class="etq">Período</td>
        <td><?= $e($filtros['periodo']) ?></td>
        <td class="etq">Departamento</td>
        <td><?= $e($filtros['departamento']) ?></td>
    </tr>
    <tr>
        <td class="etq">Desde</td>
        <td><?= $e(date('d/m/Y', strtotime($filtros['desde']))) ?></td>
        <td class="etq">Hasta</td>
        <td><?= $e(date('d/m/Y', strtotime($filtros['hasta']))) ?></td>
    </tr>
</table>

<table class="kpis">
    <tr>
        <td><div class="valor"><?= (int) $tot['trabajadores'] ?></div><div class="texto">Trabajadores</div></td>
        <td><div class="valor"><?= (int) $tot['asistencias'] ?></div><div class="texto">Días asistidos</div></td>
        <td><div class="valor"><?= (int) $tot['retardos'] ?></div><div class="texto">Retardos</div></td>
        <td><div class="valor"><?= (int) $tot['inasistencias'] ?></div><div class="texto">Inasistencias</div></td>
        <td><div class="valor"><?= $pct($tot['porcentaje']) ?></div><div class="texto">Asistencia general</div></td>
    </tr>
</table>

<?php foreach ($reporte['departamentos'] as $nombreDep => $dep): ?>
    <h2><?= $e($nombreDep) ?> · <?= (int) $dep['totales']['trabajadores'] ?> trabajador(es)</h2>
    <table class="datos">
        <thead>
            <tr>
                <th style="width: 4%">N°</th>
                <th style="width: 13%">Cédula</th>
                <th>Trabajador</th>
                <th style="width: 15%">Turno</th>
                <th style="width: 8%">Días lab.</th>
                <th style="width: 8%">Asist.</th>
                <th style="width: 8%">Retardos</th>
                <th style="width: 8%">Justif.</th>
                <th style="width: 8%">Inasist.</th>
                <th style="width: 9%">% Asist.</th>
            </tr>
        </thead>
        <tbody>
            <?php $n = 0; foreach ($dep['trabajadores'] as $t): $r = $t['resumen']; ?>
                <tr>
                    <td class="num"><?= ++$n ?></td>
                    <td><?= $e($t['cedula']) ?></td>
                    <td><?= $e($t['nombre']) ?></td>
                    <td><?= $e($t['turno']) ?></td>
                    <td class="num"><?= (int) $r['laborables'] ?></td>
                    <td class="num"><?= (int) $r['asistencias'] ?></td>
                    <td class="num"><?= (int) $r['retardos'] ?></td>
                    <td class="num"><?= (int) $r['justificados'] ?></td>
                    <td class="num<?= $r['inasistencias'] > 0 ? ' alerta' : '' ?>"><?= (int) $r['inasistencias'] ?></td>
                    <td class="num"><?= $pct($r['porcentaje']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <?php $d = $dep['totales']; ?>
            <tr>
                <td colspan="4">Total del departamento</td>
                <td class="num"><?= (int) $d['laborables'] ?></td>
                <td class="num"><?= (int) $d['asistencias'] ?></td>
                <td class="num"><?= (int) $d['retardos'] ?></td>
                <td class="num"><?= (int) $d['justificados'] ?></td>
                <td class="num"><?= (int) $d['inasistencias'] ?></td>
                <td class="num"><?= $pct($d['porcentaje']) ?></td>
            </tr>
        </tfoot>
    </table>

    <?php if ($filtros['incluir_detalle']): ?>
        <?php foreach ($dep['trabajadores'] as $t): ?>
            <div class="detalle-titulo"><?= $e($t['nombre']) ?> · C.I. <?= $e($t['cedula']) ?></div>
            <table class="datos">
                <thead>
                    <tr><th style="width: 16%">Fecha</th><th style="width: 12%">Entrada</th><th style="width: 12%">Salida</th><th>Observación</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($t['detalle'] as $dia): ?>
                        <tr>
                            <td><?= $e(date('d/m/Y', strtotime($dia['fecha']))) ?></td>
                            <td class="num"><?= $e($dia['entrada'] ?? '—') ?></td>
                            <td class="num"><?= $e($dia['salida'] ?? '—') ?></td>
                            <td class="<?= $dia['estado'] === 'Inasistencia' ? 'alerta' : '' ?>"><?= $e($dia['estado']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endforeach; ?>
    <?php endif; ?>
<?php endforeach; ?>

<p class="nota">
    Días laborables: lunes a viernes del período, sin contar días futuros ni los anteriores al ingreso del trabajador en los que no marcó.
    Retardo: entrada después de la hora de inicio del turno más <?= \App\Models\AsistenciaModel::TOLERANCIA_MIN ?> minutos de tolerancia.
    % de asistencia = (días asistidos + días justificados) / días laborables. No se descuentan feriados.
</p>

<table class="firmas">
    <tr>
        <td><div class="raya">Elaborado por</div></td>
        <td><div class="raya">Dirección</div></td>
    </tr>
</table>

</body>
</html>
