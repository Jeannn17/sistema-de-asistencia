<?php /** Sección: Ventanas modales (editar trabajador, turno y cargo)  (se incluye desde app/Views/dashboard.php; usa $e, $puede, etc.) */ ?>
<?php if ($puede('gestionar_personal')): ?>
<!-- Modal: editar trabajador -->
<div class="modal-fondo" id="modalPersona" hidden>
    <div class="modal-caja" role="dialog" aria-modal="true" aria-labelledby="tituloPersona">
        <div class="modal-cabecera">
            <h3 id="tituloPersona" data-titulo data-nuevo="Trabajador" data-editar="Editar trabajador">Editar trabajador</h3>
            <button type="button" class="modal-cerrar" data-cerrar-modal aria-label="Cerrar">&times;</button>
        </div>
        <form method="POST" action="<?= url('trabajador/actualizar') ?>">
<?= csrf_campo() ?>
            <input type="hidden" name="id_persona" data-id>
            <div class="modal-grid">
                <div class="campo">
                    <label for="pCedula">Cédula</label>
                    <input type="text" id="pCedula" name="cedula" maxlength="12" required>
                </div>
                <div class="campo">
                    <label for="pNro">N° de registro</label>
                    <input type="number" id="pNro" name="nro_registro" min="1">
                </div>
                <div class="campo">
                    <label for="pNombre">Nombre</label>
                    <input type="text" id="pNombre" name="nombre" required>
                </div>
                <div class="campo">
                    <label for="pApellido">Apellido</label>
                    <input type="text" id="pApellido" name="apellido" required>
                </div>
                <div class="campo">
                    <label for="pCargo">Cargo</label>
                    <select id="pCargo" name="id_cargo" required>
                        <?php foreach ($listadoCargos as $c): ?>
                            <option value="<?= (int) $c['id_cargo'] ?>"><?= $e($c['nombre_cargo']) ?><?= $esAct($c['activo']) ? '' : ' (inactivo)' ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="campo">
                    <label for="pTurno">Turno</label>
                    <select id="pTurno" name="id_turno" required>
                        <?php foreach ($listadoTurnos as $t): ?>
                            <option value="<?= (int) $t['id_turno'] ?>"><?= $e($t['nombre_turno']) ?><?= $esAct($t['activo']) ? '' : ' (inactivo)' ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="campo">
                    <label for="pIngreso">Fecha de ingreso</label>
                    <input type="date" id="pIngreso" name="fecha_ingreso" required>
                </div>
                <div class="campo" style="justify-content: flex-end;">
                    <label class="check-linea" style="text-transform: none; letter-spacing: 0; font-size: 0.88rem;">
                        <input type="checkbox" name="activo" value="1"> Trabajador activo
                    </label>
                </div>
            </div>
            <p class="modal-nota">El N° de registro debe coincidir con el ID del reloj biométrico para importar sus asistencias.</p>
            <div class="modal-acciones">
                <button type="button" class="btn-secundario" data-cerrar-modal>Cancelar</button>
                <button type="submit" class="btn-primario">Guardar cambios</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php if ($puede('gestionar_turnos')): ?>
<!-- Modal: crear / editar turno -->
<div class="modal-fondo" id="modalTurno" hidden>
    <div class="modal-caja" role="dialog" aria-modal="true" aria-labelledby="tituloTurno">
        <div class="modal-cabecera">
            <h3 id="tituloTurno" data-titulo data-nuevo="Nuevo turno" data-editar="Editar turno">Nuevo turno</h3>
            <button type="button" class="modal-cerrar" data-cerrar-modal aria-label="Cerrar">&times;</button>
        </div>
        <form method="POST" action="<?= url('turno/guardar') ?>">
<?= csrf_campo() ?>
            <input type="hidden" name="id_turno" data-id>
            <div class="modal-grid">
                <div class="campo ancho">
                    <label for="tNombre">Nombre del turno</label>
                    <input type="text" id="tNombre" name="nombre_turno" maxlength="60" placeholder="ej: Turno mañana" required>
                </div>
                <div class="campo">
                    <label for="tI1">Periodo 1 · inicio</label>
                    <input type="time" id="tI1" name="inicio_p1" required>
                </div>
                <div class="campo">
                    <label for="tF1">Periodo 1 · fin</label>
                    <input type="time" id="tF1" name="fin_p1" required>
                </div>
                <div class="campo">
                    <label for="tI2">Periodo 2 · inicio</label>
                    <input type="time" id="tI2" name="inicio_p2">
                </div>
                <div class="campo">
                    <label for="tF2">Periodo 2 · fin</label>
                    <input type="time" id="tF2" name="fin_p2">
                </div>
                <div class="campo">
                    <label for="tIE">Extra · inicio</label>
                    <input type="time" id="tIE" name="inicio_extra">
                </div>
                <div class="campo">
                    <label for="tFE">Extra · fin</label>
                    <input type="time" id="tFE" name="fin_extra">
                </div>
            </div>
            <p class="modal-nota">El periodo 2 y el extra son opcionales: si los usas, completa el inicio y el fin.</p>
            <div class="modal-acciones">
                <button type="button" class="btn-secundario" data-cerrar-modal>Cancelar</button>
                <button type="submit" class="btn-primario">Guardar turno</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php if ($puede('gestionar_cargos')): ?>
<!-- Modal: crear / editar cargo -->
<div class="modal-fondo" id="modalCargo" hidden>
    <div class="modal-caja" role="dialog" aria-modal="true" aria-labelledby="tituloCargo">
        <div class="modal-cabecera">
            <h3 id="tituloCargo" data-titulo data-nuevo="Nuevo cargo" data-editar="Editar cargo">Nuevo cargo</h3>
            <button type="button" class="modal-cerrar" data-cerrar-modal aria-label="Cerrar">&times;</button>
        </div>
        <form method="POST" action="<?= url('cargo/guardar') ?>">
<?= csrf_campo() ?>
            <input type="hidden" name="id_cargo" data-id>
            <div class="modal-grid">
                <div class="campo ancho">
                    <label for="cNombre">Nombre del cargo</label>
                    <input type="text" id="cNombre" name="nombre_cargo" maxlength="60" placeholder="ej: Coordinador" required>
                </div>
            </div>
            <div class="modal-acciones">
                <button type="button" class="btn-secundario" data-cerrar-modal>Cancelar</button>
                <button type="submit" class="btn-primario">Guardar cargo</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>
