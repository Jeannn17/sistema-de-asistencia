<?php
/**
 * Sección: perfil propio (todos) + aprobaciones y roles (solo directiva principal).
 * Datos (DashboardController::datosSeccion): $perfil, $esDirectivaPrincipal, $directivaPrincipal,
 *                                          $cuentasPendientes, $cuentasAprobadas
 */
use App\Models\UsuarioModel;

$perfil               = $perfil ?? [];
$esDirectivaPrincipal = $esDirectivaPrincipal ?? false;
$cuentasPendientes    = $cuentasPendientes ?? [];
$cuentasAprobadas     = $cuentasAprobadas ?? [];
$protegido = fn(string $usuario): bool => UsuarioModel::estaEn($usuario, $directivaPrincipal ?? []);
$fecha     = fn($v) => $v ? date('d/m/Y h:i a', strtotime($v)) : '—';
$miUsuario = (string) ($perfil['usuario'] ?? '');
?>
<div class="gestion-cabecera">
    <div>
        <h2>Sección</h2>
        <p class="panel-sub">Tus datos de acceso<?= $esDirectivaPrincipal ? ', solicitudes de cuenta y roles del personal administrativo' : '' ?>.</p>
    </div>
</div>

<div class="seccion-grid">
    <!-- ============ A. PERFIL PROPIO ============ -->
    <form class="panel seccion-card" method="POST" action="<?= url('seccion/perfil') ?>" autocomplete="off">
        <?= csrf_campo() ?>
        <h3>Mi perfil</h3>
        <p class="panel-sub">
            Rol: <span class="etiqueta <?= ($perfil['rol'] ?? '') === UsuarioModel::ROL_DIRECTIVO ? 'verde' : 'gris' ?>"><?= $e(UsuarioModel::ROLES[$perfil['rol'] ?? ''] ?? '—') ?></span>
            <?php if ($esDirectivaPrincipal): ?><span class="etiqueta ambar">Directiva principal</span><?php endif; ?>
            · El rol solo lo puede cambiar la directiva principal.
        </p>

        <div class="campo">
            <label for="perfilNombre">Nombre</label>
            <input type="text" id="perfilNombre" name="nombre" maxlength="50" required value="<?= $e($perfil['nombre'] ?? '') ?>">
        </div>
        <div class="campo">
            <label for="perfilUsuario">Usuario</label>
            <input type="text" id="perfilUsuario" name="usuario" maxlength="50" required pattern="[A-Za-z0-9._\-]{3,50}"
                   value="<?= $e($miUsuario) ?>" <?= $protegido($miUsuario) ? 'readonly' : '' ?>>
            <?php if ($protegido($miUsuario)): ?>
                <small class="seccion-nota">Tu usuario no se puede cambiar: el privilegio de la directiva principal depende de él.</small>
            <?php endif; ?>
        </div>
        <div class="campo">
            <label for="perfilCorreo">Correo electrónico</label>
            <input type="email" id="perfilCorreo" name="correo" maxlength="120" required value="<?= $e($perfil['correo'] ?? '') ?>">
        </div>

        <fieldset class="seccion-clave">
            <legend>Cambiar contraseña (opcional)</legend>
            <div class="campo">
                <label for="perfilClaveNueva">Contraseña nueva</label>
                <input type="password" id="perfilClaveNueva" name="clave_nueva" minlength="8" maxlength="72"
                       autocomplete="new-password" placeholder="Déjala vacía para no cambiarla">
            </div>
            <div class="campo">
                <label for="perfilClaveConfirmar">Confirmar contraseña nueva</label>
                <input type="password" id="perfilClaveConfirmar" name="clave_confirmar" minlength="8" maxlength="72" autocomplete="new-password">
            </div>
        </fieldset>

        <div class="campo">
            <label for="perfilClaveActual">Contraseña actual (para confirmar los cambios)</label>
            <input type="password" id="perfilClaveActual" name="clave_actual" required autocomplete="current-password">
        </div>

        <div class="seccion-acciones">
            <button type="submit" class="btn-primario">Guardar mis datos</button>
        </div>
    </form>

    <?php if ($esDirectivaPrincipal): ?>
    <!-- ============ B. PANEL DE LA DIRECTIVA PRINCIPAL ============ -->
    <div class="seccion-columna">
        <section class="panel seccion-card">
            <h3>Solicitudes de cuenta pendientes
                <?php if ($cuentasPendientes): ?><span class="etiqueta ambar"><?= count($cuentasPendientes) ?></span><?php endif; ?>
            </h3>
            <p class="panel-sub">Al aprobarlas, entran como <strong>administrativo</strong>. Rechazar elimina la solicitud.</p>
            <div class="tabla-wrapper">
                <table class="tabla-registros">
                    <thead>
                        <tr><th>Solicitante</th><th>Correo</th><th>Solicitada</th><th>Acciones</th></tr>
                    </thead>
                    <tbody>
                        <?php if (!$cuentasPendientes): ?>
                            <tr><td colspan="4" class="vacio-aviso">No hay solicitudes pendientes.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($cuentasPendientes as $c): ?>
                            <tr>
                                <td><strong><?= $e($c['nombre']) ?></strong><br><small>@<?= $e($c['usuario']) ?></small></td>
                                <td><?= $e($c['correo']) ?></td>
                                <td><?= $e($fecha($c['fecha_registro'])) ?></td>
                                <td>
                                    <div class="acciones-celda">
                                        <form class="form-inline" method="POST" action="<?= url('seccion/aprobar') ?>"
                                              data-confirmar="<?= $e('¿Aprobar la cuenta de ' . $c['nombre'] . '? Podrá iniciar sesión como administrativo.') ?>">
                                            <?= csrf_campo() ?>
                                            <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                                            <button type="submit" class="btn-tabla ok">Aprobar</button>
                                        </form>
                                        <form class="form-inline" method="POST" action="<?= url('seccion/rechazar') ?>"
                                              data-confirmar="<?= $e('¿Rechazar y eliminar la solicitud de ' . $c['nombre'] . '? No se puede deshacer.') ?>">
                                            <?= csrf_campo() ?>
                                            <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                                            <button type="submit" class="btn-tabla peligro">Rechazar</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="panel seccion-card">
            <h3>Roles de las cuentas</h3>
            <p class="panel-sub">Un directivo puede gestionar personal, turnos, cargos e importar asistencia. Aprobar cuentas y cambiar roles es exclusivo de la directiva principal.</p>
            <div class="tabla-wrapper">
                <table class="tabla-registros">
                    <thead>
                        <tr><th>Cuenta</th><th>Rol</th><th>Acción</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cuentasAprobadas as $c): ?>
                            <?php $esDir = $c['rol'] === UsuarioModel::ROL_DIRECTIVO; $nuevo = $esDir ? UsuarioModel::ROL_ADMINISTRATIVO : UsuarioModel::ROL_DIRECTIVO; ?>
                            <tr>
                                <td><strong><?= $e($c['nombre']) ?></strong><br><small>@<?= $e($c['usuario']) ?></small></td>
                                <td><span class="etiqueta <?= $esDir ? 'verde' : 'gris' ?>"><?= $e(UsuarioModel::ROLES[$c['rol']] ?? $c['rol']) ?></span></td>
                                <td>
                                    <?php if ($protegido($c['usuario'])): ?>
                                        <span class="etiqueta ambar">Directiva principal</span>
                                    <?php else: ?>
                                        <form class="form-inline" method="POST" action="<?= url('seccion/rol') ?>"
                                              data-confirmar="<?= $e('¿Cambiar a ' . $c['nombre'] . ' al rol ' . UsuarioModel::ROLES[$nuevo] . '?') ?>">
                                            <?= csrf_campo() ?>
                                            <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                                            <input type="hidden" name="rol" value="<?= $e($nuevo) ?>">
                                            <button type="submit" class="btn-tabla <?= $esDir ? 'peligro' : 'ok' ?>">
                                                <?= $esDir ? 'Pasar a administrativo' : 'Promover a directivo' ?>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
    <?php endif; ?>
</div>
