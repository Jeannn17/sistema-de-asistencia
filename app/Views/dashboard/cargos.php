<?php /** Sección: Cargos (departamentos)  (se incluye desde app/Views/dashboard.php; usa $e, $puede, etc.) */ ?>
<div class="gestion-cabecera">
    <div>
        <h2>Cargos</h2>
        <p class="panel-sub">Un cargo inactivo no se ofrece en nuevos registros; el historial no se borra.</p>
    </div>
    <?php if ($puede('gestionar_cargos')): ?>
        <button type="button" class="btn-primario" data-nuevo="modalCargo">＋ Nuevo cargo</button>
    <?php endif; ?>
</div>
<div class="tabla-wrapper">
    <table class="tabla-registros">
        <thead>
            <tr>
                <th>Cargo</th>
                <th>Trabajadores activos</th>
                <th>Estado</th>
                <?php if ($puede('gestionar_cargos')): ?><th>Acciones</th><?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($listadoCargos)): ?>
                <tr><td colspan="4" class="vacio-aviso">Aún no hay cargos registrados.</td></tr>
            <?php else: ?>
                <?php foreach ($listadoCargos as $c): ?>
                    <?php $act = $esAct($c['activo']); ?>
                    <tr class="<?= $act ? '' : 'fila-inactiva' ?>">
                        <td><strong><?= $e($c['nombre_cargo']) ?></strong></td>
                        <td><?= (int) $c['trabajadores'] ?></td>
                        <td><span class="etiqueta <?= $act ? 'verde' : 'gris' ?>"><?= $act ? 'Activo' : 'Inactivo' ?></span></td>
                        <?php if ($puede('gestionar_cargos')): ?>
                            <td>
                                <div class="acciones-celda">
                                    <button type="button" class="btn-tabla" data-editar="modalCargo"
                                            data-json="<?= $e(json_encode([
                                                'id_cargo'     => (int) $c['id_cargo'],
                                                'nombre_cargo' => $c['nombre_cargo'],
                                            ], JSON_UNESCAPED_UNICODE)) ?>">Editar</button>
                                    <form class="form-inline" method="POST" action="<?= url('cargo/estado') ?>"
                                          data-confirmar="<?= $act ? '¿Desactivar este cargo? Ya no se ofrecerá en nuevos registros.' : '¿Activar este cargo?' ?>">
<?= csrf_campo() ?>
                                        <input type="hidden" name="id_cargo" value="<?= (int) $c['id_cargo'] ?>">
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
