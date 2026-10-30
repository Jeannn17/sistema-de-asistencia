<?php
/**
 * Registro masivo de trabajadores (modo "Registro" de la sección Trabajadores).
 * Paso 1: elegir el cargo · Paso 2: formulario con una fila por trabajador (lo arma registroWorkflow.js)
 */
?>
<!-- PASO 1: Selección de Cargos -->
<div id="pasoCargos">
    <div class="header-paso">
        <button type="button" class="btn-volver" data-volver-lista>← Volver a la lista</button>
        <h2>Registrar trabajadores · Seleccione el Departamento o Cargo:</h2>
    </div>
    <div class="cargos-grid">
        <?php foreach ($cargosActivos as $c): ?>
            <button type="button" class="btn-cargo" data-id="<?= (int) $c['id_cargo'] ?>" data-cargo="<?= $e($c['nombre_cargo']) ?>">
                <span class="cargo-icon"><?= $iconosCargo[mb_strtolower($c['nombre_cargo'])] ?? '💼' ?></span>
                <span class="cargo-text"><?= $e($c['nombre_cargo']) ?></span>
            </button>
        <?php endforeach; ?>
        <?php if (empty($cargosActivos)): ?>
            <p class="vacio-aviso">No hay cargos activos. Crea uno en la sección Cargos.</p>
        <?php endif; ?>
    </div>
</div>

<!-- PASO 2: Formulario Dinámico de Registro -->
<div id="pasoFormulario" style="display: none;">
    <div class="header-paso">
        <button type="button" id="btnVolverCargos" class="btn-volver">← Cambiar cargo</button>
        <h2 id="tituloCargoFormulario">Registro de Personal</h2>
        <button type="button" class="btn-volver" data-volver-lista>✕ Cancelar y volver a la lista</button>
    </div>

    <form id="formRegistroTrabajadores" action="<?= url('trabajador/guardar') ?>" method="POST">
<?= csrf_campo() ?>
        <input type="hidden" name="id_cargo" id="inputCargoSeleccionado">
        <div id="contenedorTrabajadores"></div>
        <div class="form-acciones">
            <button type="button" id="btnAgregarTrabajador" class="btn-agregar-mas">
                <span>➕</span> Agregar otro trabajador
            </button>
            <button type="submit" class="btn-guardar-lote">
                <span>💾</span> Guardar Registro
            </button>
        </div>
    </form>
</div>
