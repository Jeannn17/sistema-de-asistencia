<?php
/**
 * Sección: Trabajadores. Tiene dos modos (los alterna trabajadores.js sin recargar):
 *   - Lista:    consultar, modificar (ventana modal) y activar/inactivar
 *   - Registro: alta masiva por cargo (_trabajadores_registro.php), con botón para volver a la lista
 */
$gestiona = $puede('gestionar_personal');
$activosLista = count(array_filter($listadoPersonal, fn($p) => $esAct($p['activo'])));
?>
<div id="trabajadoresLista">
    <div class="gestion-cabecera">
        <div>
            <h2>Trabajadores</h2>
            <p class="panel-sub"><?= count($listadoPersonal) ?> trabajadores registrados · <?= $activosLista ?> activos</p>
        </div>
        <?php if ($gestiona): ?>
            <button type="button" class="btn-primario" data-abrir-registro>＋ Registrar trabajadores</button>
        <?php endif; ?>
    </div>
    <div class="tabla-wrapper">
        <table class="tabla-registros">
            <thead>
                <tr>
                    <th>Cédula</th>
                    <th>Trabajador</th>
                    <th>N° registro</th>
                    <th>Cargo</th>
                    <th>Turno</th>
                    <th>Ingreso</th>
                    <th>Estado</th>
                    <?php if ($gestiona): ?><th>Acciones</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($listadoPersonal)): ?>
                    <tr><td colspan="8" class="vacio-aviso">Aún no hay trabajadores registrados.</td></tr>
                <?php else: ?>
                    <?php foreach ($listadoPersonal as $p): ?>
                        <?php $act = $esAct($p['activo']); $nombre = $p['apellido'] . ' ' . $p['nombre']; ?>
                        <tr class="<?= $act ? '' : 'fila-inactiva' ?>">
                            <td><?= $e($p['cedula']) ?></td>
                            <td><strong><?= $e($nombre) ?></strong></td>
                            <td><?= $e($p['nro_registro'] ?? '—') ?></td>
                            <td><?= $e($p['nombre_cargo']) ?></td>
                            <td><?= $e($p['nombre_turno']) ?></td>
                            <td><?= $e(date('d/m/Y', strtotime($p['fecha_ingreso']))) ?></td>
                            <td><span class="etiqueta <?= $act ? 'verde' : 'gris' ?>"><?= $act ? 'Activo' : 'Inactivo' ?></span></td>
                            <?php if ($gestiona): ?>
                                <td>
                                    <div class="acciones-celda">
                                        <button type="button" class="btn-tabla" data-editar="modalPersona"
                                                data-json="<?= $e(json_encode([
                                                    'id_persona'    => (int) $p['id_persona'],
                                                    'cedula'        => $p['cedula'],
                                                    'nombre'        => $p['nombre'],
                                                    'apellido'      => $p['apellido'],
                                                    'nro_registro'  => $p['nro_registro'],
                                                    'id_cargo'      => (int) $p['id_cargo'],
                                                    'id_turno'      => (int) $p['id_turno'],
                                                    'fecha_ingreso' => $p['fecha_ingreso'],
                                                    'activo'        => $act,
                                                ], JSON_UNESCAPED_UNICODE)) ?>">Modificar</button>
                                        <form class="form-inline" method="POST" action="<?= url('trabajador/estado') ?>"
                                              data-confirmar="<?= $e($act
                                                  ? "¿Inactivar a {$nombre}? Ya no aparecerá en permisos ni se importarán sus marcaciones. Su historial se conserva."
                                                  : "¿Activar de nuevo a {$nombre}?") ?>">
<?= csrf_campo() ?>
                                            <input type="hidden" name="id_persona" value="<?= (int) $p['id_persona'] ?>">
                                            <input type="hidden" name="activo" value="<?= $act ? '0' : '1' ?>">
                                            <button type="submit" class="btn-tabla <?= $act ? 'peligro' : 'ok' ?>"><?= $act ? 'Inactivar' : 'Activar' ?></button>
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
</div>

<?php if ($gestiona): ?>
    <div id="trabajadoresRegistro" hidden>
        <?php require __DIR__ . '/_trabajadores_registro.php'; ?>
    </div>
<?php endif; ?>
