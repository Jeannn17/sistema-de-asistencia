<?php
/** Sección: Permisos y justificaciones. Datos: $personalPermisos, $permisosRecientes */
$tiposPermiso = \App\Models\JustificacionModel::TIPOS;
// Qué campos de fecha/hora muestra el formulario para cada tipo (lo usa permisos.js)
$modos = ['reposo' => 'rango', 'accidente_laboral' => 'rango', 'permiso_dia' => 'dia',
          'salida_temprana' => 'hora', 'llegada_tarde' => 'hora'];
?>
<div class="permisos-container">
    <?php if ($puede('registrar_permisos')): ?>
    <div class="permiso-card">
        <div class="header-paso">
            <h2>📝 Registrar Permiso / Justificación</h2>
        </div>
        <p class="permiso-subtitulo">
            Registra reposos, accidentes laborales y permisos para justificar las faltas o retrasos del personal.
        </p>

        <form id="formPermiso" action="<?= url('permiso/guardar') ?>" method="POST" autocomplete="off">
<?= csrf_campo() ?>
            <input type="hidden" name="id_persona" id="permisoIdPersona">

            <div class="inputs-grid">
                <div class="input-group input-wide">
                    <label for="permisoBuscarPersona">Trabajador</label>
                    <input type="text" id="permisoBuscarPersona" list="listaPersonalPermiso"
                           placeholder="Escribe la cédula, el nombre o el apellido..." required>
                    <datalist id="listaPersonalPermiso">
                        <?php foreach (($personalPermisos ?? []) as $p): ?>
                            <option value="<?= $e($p['cedula'] . ' — ' . $p['apellido'] . ' ' . $p['nombre'] . ' (N° ' . $p['nro_registro'] . ')') ?>"
                                    data-id="<?= (int) $p['id_persona'] ?>"></option>
                        <?php endforeach; ?>
                    </datalist>
                </div>

                <div class="input-group">
                    <label for="permisoTipo">Tipo de justificación</label>
                    <select name="tipo" id="permisoTipo" required>
                        <?php foreach ($tiposPermiso as $clave => $info): ?>
                            <option value="<?= $e($clave) ?>" data-modo="<?= $e($modos[$clave]) ?>"><?= $e($info[0]) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="input-group">
                    <label for="permisoFechaInicio" id="labelFechaInicio">Fecha de inicio</label>
                    <input type="date" name="fecha_inicio" id="permisoFechaInicio" required>
                </div>

                <div class="input-group" id="grupoFechaFin">
                    <label for="permisoFechaFin">Fecha de fin</label>
                    <input type="date" name="fecha_fin" id="permisoFechaFin">
                </div>

                <div class="input-group campo-oculto" id="grupoHora">
                    <label for="permisoHora" id="labelHora">Hora</label>
                    <input type="time" name="hora" id="permisoHora">
                </div>

                <div class="input-group input-wide">
                    <label for="permisoMotivo">Motivo</label>
                    <textarea name="motivo" id="permisoMotivo" maxlength="500"
                              placeholder="Describe el motivo (ej: reposo médico por 3 días, cita médica...)" required></textarea>
                    <span class="contador-motivo" id="contadorMotivo">0/500</span>
                </div>
            </div>

            <div class="form-acciones">
                <span></span>
                <button type="submit" class="btn-guardar-lote">
                    <span>💾</span> Guardar Permiso
                </button>
            </div>
        </form>
    </div>

    <?php endif; ?>
    <div class="permisos-lista">
        <h3>Últimos permisos registrados</h3>
        <div class="permisos-tabla-wrapper">
            <table class="permisos-tabla">
                <thead>
                    <tr>
                        <th>Trabajador</th>
                        <th>Tipo</th>
                        <th>Desde</th>
                        <th>Hasta / Hora</th>
                        <th>Motivo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($permisosRecientes)): ?>
                        <tr><td colspan="5" class="permisos-vacio">Aún no hay permisos registrados.</td></tr>
                    <?php else: ?>
                        <?php foreach ($permisosRecientes as $j): ?>
                            <?php
                            $etiqueta = $tiposPermiso[$j['tipo']][0] ?? $j['tipo'];
                            $desde = date('d/m/Y', strtotime($j['fecha_inicio']));
                            if (!empty($j['hora'])) {
                                $hastaHora = substr($j['hora'], 0, 5);
                            } elseif ($j['fecha_fin'] !== $j['fecha_inicio']) {
                                $hastaHora = date('d/m/Y', strtotime($j['fecha_fin']));
                            } else {
                                $hastaHora = '—';
                            }
                            ?>
                            <tr>
                                <td><?= $e($j['apellido'] . ' ' . $j['nombre']) ?><br><small><?= $e($j['cedula']) ?></small></td>
                                <td><span class="badge-tipo badge-<?= $e($j['tipo']) ?>"><?= $e($etiqueta) ?></span></td>
                                <td><?= $e($desde) ?></td>
                                <td><?= $e($hastaHora) ?></td>
                                <td><?= $e($j['motivo']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
