<?php
/** Sección: Inicio (resumen del día). Datos: $resumen, armado por DashboardModel::obtenerResumen() */
$r          = $resumen ?? [];
$total      = (int) ($r['total'] ?? 0);
$activos    = (int) ($r['activos'] ?? 0);
$inactivos  = (int) ($r['inactivos'] ?? 0);
$pctActivos = $total > 0 ? round($activos * 100 / $total, 1) : 0;
$pctAsist   = $r['pct_asistencia'] ?? null;
$fechaRef   = !empty($r['fecha_ref']) ? date('d/m/Y', strtotime($r['fecha_ref'])) : null;
// $perm (permisos vigentes) lo calcula el layout porque también lo usa el menú
?>
<section class="hero">
    <div>
        <h2>Bienvenido al Sistema de Asistencia – U.E. El Itaca</h2>
        <p>Gestión centralizada de la asistencia laboral, la importación de marcaciones del biométrico y las justificaciones del personal.</p>
    </div>
    <div class="hero-acciones">
        <?php if ($puede('importar_asistencia')): ?>
            <button type="button" class="btn-hero" data-goto="vistaImportar">Importar asistencia</button>
        <?php else: ?>
            <button type="button" class="btn-hero" data-goto="vistaTrabajadores">Ver trabajadores</button>
        <?php endif; ?>
        <?php if ($puede('registrar_permisos')): ?>
            <button type="button" class="btn-hero principal" data-goto="vistaPermisos">Registrar permiso</button>
        <?php else: ?>
            <button type="button" class="btn-hero principal" data-goto="vistaAsistencias">Consultar asistencias</button>
        <?php endif; ?>
    </div>
</section>

<section class="stats-grid">
    <article class="stat-card">
        <div class="stat-titulo">Total de trabajadores</div>
        <div class="stat-valor"><?= $total ?> <small>registrados</small></div>
        <div class="stat-detalle">
            <span>Activos: <strong><?= $activos ?></strong></span>
            <?php if (($r['nuevos_mes'] ?? 0) > 0): ?>
                <span class="etiqueta verde">+<?= (int) $r['nuevos_mes'] ?> este mes</span>
            <?php else: ?>
                <span>Inactivos: <strong><?= $inactivos ?></strong></span>
            <?php endif; ?>
        </div>
    </article>

    <article class="stat-card">
        <div class="stat-titulo">Estado del personal</div>
        <div class="stat-valor"><?= $activos ?> <small>activos · <?= $inactivos ?> inactivos</small></div>
        <div class="barra"><span style="width: <?= $pctActivos ?>%"></span></div>
        <div class="stat-detalle"><span><?= $pctActivos ?>% del personal está activo</span></div>
    </article>

    <article class="stat-card">
        <div class="stat-titulo">Permisos vigentes hoy</div>
        <div class="stat-valor"><?= (int) $perm['total'] ?> <small>justificaciones</small></div>
        <div class="stat-detalle">
            <span>Reposo: <strong><?= (int) $perm['reposo'] ?></strong></span>
            <span>Accidente: <strong><?= (int) $perm['accidente'] ?></strong></span>
            <span>Otros: <strong><?= (int) $perm['otros'] ?></strong></span>
        </div>
    </article>

    <article class="stat-card">
        <div class="stat-titulo">Asistencia del día</div>
        <?php if ($pctAsist !== null): ?>
            <div class="stat-valor pct-verde"><?= $pctAsist ?>%</div>
            <div class="stat-detalle">
                <span><?= (int) $r['presentes'] ?> de <?= $activos ?> con marcación</span>
                <span class="pct-rojo"><?= round(100 - $pctAsist, 1) ?>% sin marcar</span>
            </div>
            <div class="stat-detalle" style="border-top:none; padding-top:0; margin-top:4px;">
                <span>Último día importado: <?= $e($fechaRef) ?></span>
            </div>
        <?php else: ?>
            <div class="stat-valor">—</div>
            <div class="stat-detalle"><span>Aún no hay asistencia importada</span></div>
        <?php endif; ?>
    </article>
</section>

<section class="inicio-fila">
    <article class="panel">
        <h3>Asistencia por cargo</h3>
        <p class="panel-sub">
            <?= $fechaRef ? 'Personal activo con marcación el ' . $e($fechaRef) : 'Importa un informe para ver la asistencia por cargo' ?>
        </p>
        <?php if (empty($r['por_cargo'])): ?>
            <div class="vacio-aviso">Sin datos de asistencia todavía.</div>
        <?php else: ?>
            <?php foreach ($r['por_cargo'] as $c): ?>
                <div class="cargo-fila">
                    <div class="cargo-fila-info">
                        <?= $e($c['cargo']) ?>
                        <span><strong><?= $e($c['pct']) ?>%</strong> (<?= (int) $c['presentes'] ?>/<?= (int) $c['total'] ?> presentes)</span>
                    </div>
                    <div class="barra <?= $c['pct'] >= 80 ? '' : 'azul' ?>"><span style="width: <?= (float) $c['pct'] ?>%"></span></div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </article>

    <article class="panel">
        <h3>Acciones rápidas</h3>
        <?php if ($puede('importar_asistencia')): ?>
        <button type="button" class="accion-item destacada" data-goto="vistaImportar">
            <span class="accion-icono"><svg viewBox="0 0 24 24"><path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg></span>
            <span><strong>Importar asistencia</strong><small>Archivo Excel (.xls o .xlsx) del biométrico</small></span>
        </button>
        <?php endif; ?>
        <?php if ($puede('registrar_permisos')): ?>
        <button type="button" class="accion-item" data-goto="vistaPermisos">
            <span class="accion-icono ambar"><svg viewBox="0 0 24 24"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg></span>
            <span><strong>Registrar permiso o justificación</strong><small>Reposo, accidente laboral o permiso</small></span>
        </button>
        <?php endif; ?>
        <?php if ($puede('gestionar_personal')): ?>
        <button type="button" class="accion-item" data-abrir-registro>
            <span class="accion-icono"><svg viewBox="0 0 24 24"><path d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg></span>
            <span><strong>Registrar trabajadores</strong><small>Alta de personal por cargo</small></span>
        </button>
        <?php endif; ?>
        <button type="button" class="accion-item" data-goto="vistaAsistencias">
            <span class="accion-icono"><svg viewBox="0 0 24 24"><path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg></span>
            <span><strong>Consultar asistencias</strong><small>Por trabajador y rango de fechas</small></span>
        </button>
    </article>
</section>

<section class="panel panel-tabla">
    <div class="panel-tabla-cabecera">
        <h3>Últimos registros de asistencia importados</h3>
        <p class="panel-sub">Marcaciones más recientes guardadas en el sistema.</p>
    </div>
    <div class="tabla-wrapper">
        <table class="tabla-registros">
            <thead>
                <tr>
                    <th>Trabajador / Identificación</th>
                    <th>Cargo</th>
                    <th>Fecha</th>
                    <th>Hora</th>
                    <th>Tipo</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($r['ultimos'])): ?>
                    <tr><td colspan="6" class="vacio-aviso">Aún no hay marcaciones importadas.</td></tr>
                <?php else: ?>
                    <?php foreach ($r['ultimos'] as $m): ?>
                        <tr>
                            <td>
                                <div class="persona-celda">
                                    <span class="persona-avatar"><?= $e($m['iniciales']) ?></span>
                                    <span><strong><?= $e($m['nombre']) ?></strong><small>C.I: <?= $e($m['cedula']) ?></small></span>
                                </div>
                            </td>
                            <td><?= $e($m['cargo']) ?></td>
                            <td><?= $e($m['fecha']) ?></td>
                            <td class="hora-mono"><?= $e($m['hora']) ?></td>
                            <td><span class="etiqueta gris"><?= $e($m['tipo']) ?></span></td>
                            <?php $clase = ['ok' => 'verde', 'alerta' => 'ambar', 'neutro' => 'gris'][$m['estado']['clase']] ?? 'gris'; ?>
                            <td><span class="etiqueta <?= $clase ?>"><?= $e($m['estado']['texto']) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
