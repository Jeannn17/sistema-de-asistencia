<?php /** Sección: Consulta de asistencias  (se incluye desde app/Views/dashboard.php; usa $e, $puede, etc.) */ ?>
<div class="gestion-cabecera">
    <div>
        <h2>Consulta de asistencias</h2>
        <p class="panel-sub">Se muestran los días con marcaciones. Si el trabajador tenía un permiso o reposo ese día, aparece en la columna Justificación.</p>
    </div>
</div>

<form class="filtros-form" method="GET" action="<?= url('dashboard/index') ?>">
    <input type="hidden" name="vista" value="asistencias">
    <div class="campo crece">
        <label for="filtroQ">Trabajador</label>
        <input type="text" id="filtroQ" name="q" maxlength="60" placeholder="Cédula, nombre o apellido (vacío = todos)"
               value="<?= $e($filtrosAsistencia['q']) ?>">
    </div>
    <div class="campo">
        <label for="filtroDesde">Desde</label>
        <input type="date" id="filtroDesde" name="desde" value="<?= $e($filtrosAsistencia['desde']) ?>">
    </div>
    <div class="campo">
        <label for="filtroHasta">Hasta</label>
        <input type="date" id="filtroHasta" name="hasta" value="<?= $e($filtrosAsistencia['hasta']) ?>">
    </div>
    <button type="submit" class="btn-primario">Consultar</button>
</form>

<?php if (!$asistenciasConsultadas): ?>
    <p class="vacio-aviso">Elige los filtros y pulsa Consultar.</p>
<?php else: ?>
    <p class="panel-sub" style="margin-bottom: 10px;">
        <?= count($asistencias) ?> resultados
        <?php if (count($asistencias) >= \App\Models\ConsultaModel::limite()): ?>
            (se muestran los primeros <?= \App\Models\ConsultaModel::limite() ?>; acota el rango de fechas)
        <?php endif; ?>
    </p>
    <div class="tabla-wrapper">
        <table class="tabla-registros">
            <thead>
                <tr>
                    <th>Trabajador</th>
                    <th>Cargo</th>
                    <th>Fecha</th>
                    <th>Entrada</th>
                    <th>Salida</th>
                    <th>Justificación</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($asistencias)): ?>
                    <tr><td colspan="6" class="vacio-aviso">No hay marcaciones con esos filtros.</td></tr>
                <?php else: ?>
                    <?php foreach ($asistencias as $a): ?>
                        <tr>
                            <td>
                                <strong><?= $e($a['apellido'] . ' ' . $a['nombre']) ?></strong><br>
                                <small>C.I: <?= $e($a['cedula']) ?></small>
                            </td>
                            <td><?= $e($a['cargo']) ?></td>
                            <td><?= $e(date('d/m/Y', strtotime($a['fecha']))) ?></td>
                            <td class="hora-mono"><?= $e($a['entrada'] ?? '—') ?></td>
                            <td class="hora-mono"><?= $e($a['salida'] ?? '—') ?></td>
                            <td>
                                <?php if (!empty($a['justificacion'])): ?>
                                    <span class="etiqueta ambar"><?= $e($tiposJust[$a['justificacion']][0] ?? $a['justificacion']) ?></span>
                                <?php else: ?>—<?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
