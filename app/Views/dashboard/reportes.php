<?php
/**
 * Sección: Reportes de asistencia en PDF.  Datos: $departamentos, $frecuencias (DashboardController::datosReportes)
 * El formulario va por GET a /reporte/pdf; el período exacto lo calcula ReporteModel::calcularPeriodo
 * (reportes.js solo muestra una vista previa del rango).
 */
$departamentos = $departamentos ?? [];
$frecuencias   = $frecuencias ?? \App\Models\ReporteModel::FRECUENCIAS;
$ayudas = [
    'semanal'   => 'Lunes a domingo de la semana elegida',
    'quincenal' => 'Del 1 al 15, o del 16 al fin de mes',
    'mensual'   => 'El mes completo',
];
$ayudasTipo = [
    'resumen'   => 'Días asistidos, retardos, justificaciones, inasistencias y % por trabajador',
    'detallado' => 'ID, cédula, nombre, apellido, departamento y la entrada y salida de cada día',
];
?>
<div class="gestion-cabecera">
    <div>
        <h2>Reportes de asistencia</h2>
        <p class="panel-sub">Genera un PDF con la asistencia, retardos, justificaciones e inasistencias del personal por departamento.</p>
    </div>
</div>

<form class="panel reporte-form" method="GET" action="<?= url('reporte/pdf') ?>" id="formReporte">
    <div class="reporte-paso">
        <span class="reporte-numero">1</span>
        <div class="campo">
            <span class="reporte-etiqueta">Reporte</span>
            <div class="reporte-frecuencias" role="radiogroup">
                <?php foreach (\App\Models\ReporteModel::TIPOS as $clave => $nombre): ?>
                    <label class="reporte-opcion">
                        <input type="radio" name="tipo" value="<?= $e($clave) ?>" <?= $clave === 'resumen' ? 'checked' : '' ?> required>
                        <span><strong><?= $e($nombre) ?></strong><small><?= $e($ayudasTipo[$clave] ?? '') ?></small></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="reporte-paso">
        <span class="reporte-numero">2</span>
        <div class="campo">
            <label for="reporteDepartamento">Departamento</label>
            <select id="reporteDepartamento" name="id_cargo">
                <option value="0">Todos los departamentos</option>
                <?php foreach ($departamentos as $d): ?>
                    <option value="<?= (int) $d['id_cargo'] ?>"><?= $e($d['nombre_cargo']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="reporte-paso">
        <span class="reporte-numero">3</span>
        <div class="campo">
            <span class="reporte-etiqueta">Período</span>
            <div class="reporte-frecuencias" role="radiogroup">
                <?php foreach ($frecuencias as $clave => $nombre): ?>
                    <label class="reporte-opcion">
                        <input type="radio" name="frecuencia" value="<?= $e($clave) ?>" <?= $clave === 'mensual' ? 'checked' : '' ?> required>
                        <span><strong><?= $e($nombre) ?></strong><small><?= $e($ayudas[$clave] ?? '') ?></small></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="reporte-paso">
        <span class="reporte-numero">4</span>
        <div class="campo">
            <label for="reporteFecha">Cualquier día del período</label>
            <input type="date" id="reporteFecha" name="fecha" value="<?= $e(date('Y-m-d')) ?>" max="<?= $e(date('Y-m-d')) ?>" required>
            <p class="reporte-rango" id="reporteRango" aria-live="polite"></p>
        </div>
    </div>

    <div class="reporte-pie">
        <label class="check-linea" id="reporteOpcionDetalle">
            <input type="checkbox" name="detalle" value="1"> Incluir el detalle diario de cada trabajador
        </label>
        <button type="submit" class="btn-primario">⬇ Descargar PDF</button>
    </div>
</form>
