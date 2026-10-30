<?php /** Sección: Turnos  (se incluye desde app/Views/dashboard.php; usa $e, $puede, etc.) */ ?>
<div class="gestion-cabecera">
    <div>
        <h2>Turnos</h2>
        <p class="panel-sub">Un turno inactivo no se ofrece en nuevos registros; los trabajadores que ya lo tienen no se afectan.</p>
    </div>
    <?php if ($puede('gestionar_turnos')): ?>
        <button type="button" class="btn-primario" data-nuevo="modalTurno">＋ Nuevo turno</button>
    <?php endif; ?>
</div>
<div class="tabla-wrapper">
    <table class="tabla-registros">
        <thead>
            <tr>
                <th>Turno</th>
                <th>Periodo 1</th>
                <th>Periodo 2</th>
                <th>Extra</th>
                <th>Trabajadores activos</th>
                <th>Estado</th>
                <?php if ($puede('gestionar_turnos')): ?><th>Acciones</th><?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($listadoTurnos)): ?>
                <tr><td colspan="7" class="vacio-aviso">Aún no hay turnos registrados.</td></tr>
            <?php else: ?>
                <?php foreach ($listadoTurnos as $t): ?>
                    <?php
                    $act = $esAct($t['activo']);
                    $rango = fn($i, $f) => $i ? $hm($i) . ' – ' . $hm($f) : '—';
                    ?>
                    <tr class="<?= $act ? '' : 'fila-inactiva' ?>">
                        <td><strong><?= $e($t['nombre_turno']) ?></strong></td>
                        <td class="hora-mono"><?= $e($rango($t['inicio_p1'], $t['fin_p1'])) ?></td>
                        <td class="hora-mono"><?= $e($rango($t['inicio_p2'], $t['fin_p2'])) ?></td>
                        <td class="hora-mono"><?= $e($rango($t['inicio_extra'], $t['fin_extra'])) ?></td>
                        <td><?= (int) $t['trabajadores'] ?></td>
                        <td><span class="etiqueta <?= $act ? 'verde' : 'gris' ?>"><?= $act ? 'Activo' : 'Inactivo' ?></span></td>
                        <?php if ($puede('gestionar_turnos')): ?>
                            <td>
                                <div class="acciones-celda">
                                    <button type="button" class="btn-tabla" data-editar="modalTurno"
                                            data-json="<?= $e(json_encode([
                                                'id_turno'     => (int) $t['id_turno'],
                                                'nombre_turno' => $t['nombre_turno'],
                                                'inicio_p1'    => $hm($t['inicio_p1']),
                                                'fin_p1'       => $hm($t['fin_p1']),
                                                'inicio_p2'    => $hm($t['inicio_p2']),
                                                'fin_p2'       => $hm($t['fin_p2']),
                                                'inicio_extra' => $hm($t['inicio_extra']),
                                                'fin_extra'    => $hm($t['fin_extra']),
                                            ], JSON_UNESCAPED_UNICODE)) ?>">Editar</button>
                                    <form class="form-inline" method="POST" action="<?= url('turno/estado') ?>"
                                          data-confirmar="<?= $act ? '¿Desactivar este turno? Ya no se ofrecerá en nuevos registros.' : '¿Activar este turno?' ?>">
<?= csrf_campo() ?>
                                        <input type="hidden" name="id_turno" value="<?= (int) $t['id_turno'] ?>">
                                        <input type="hidden" name="activo" value="<?= $act ? '0' : '1' ?>">
                                        <button type="submit" class="btn-tabla <?= $act ? 'peligro' : 'ok' ?>"><?= $act ? 'Desactivar' : 'Activar' ?></button>
                                    </form>
                                </div>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
