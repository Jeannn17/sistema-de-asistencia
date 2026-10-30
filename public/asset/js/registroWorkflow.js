// =========================================
// FLUJO DE REGISTRO Y NOTIFICACIONES
// =========================================
document.addEventListener('DOMContentLoaded', () => {
    const searchHeader = document.querySelector('.search-header');
    const pasoCargos = document.getElementById('pasoCargos');
    const pasoFormulario = document.getElementById('pasoFormulario');
    const btnVolverCargos = document.getElementById('btnVolverCargos');

    const btnCargos = document.querySelectorAll('.btn-cargo');
    const tituloCargoFormulario = document.getElementById('tituloCargoFormulario');
    const inputCargoSeleccionado = document.getElementById('inputCargoSeleccionado');
    const contenedorTrabajadores = document.getElementById('contenedorTrabajadores');
    const btnAgregarTrabajador = document.getElementById('btnAgregarTrabajador');

    let contadorTrabajadores = 0;

    // Escapa texto para insertarlo de forma segura en HTML
    function escapeHtml(valor) {
        const div = document.createElement('div');
        div.textContent = String(valor ?? '');
        return div.innerHTML;
    }

    // Opciones del <select> de turnos
    function opcionesTurnos() {
        const turnos = Array.isArray(window.TURNOS) ? window.TURNOS : [];
        if (turnos.length === 0) {
            return '<option value="">Sin turnos disponibles</option>';
        }
        return turnos
            .map(t => `<option value="${Number(t.id_turno)}">${escapeHtml(t.nombre_turno)}</option>`)
            .join('');
    }

    // Función para generar un formulario de trabajador
    function agregarFormularioTrabajador() {
        contadorTrabajadores++;

        const card = document.createElement('div');
        card.className = 'trabajador-card';
        card.innerHTML = `
            <div class="card-header-row">
                <span>Trabajador #${contadorTrabajadores}</span>
                ${contadorTrabajadores > 1 ? '<button type="button" class="btn-eliminar-fila" data-eliminar-fila>✕ Eliminar</button>' : ''}
            </div>
            <div class="inputs-grid">
                <div class="input-group">
                    <label>Cédula</label>
                    <input type="text" name="cedula[]" placeholder="Ej: V-12345678" maxlength="12" required>
                </div>
                <div class="input-group">
                    <label>Nombre</label>
                    <input type="text" name="nombre[]" placeholder="Ej: Juan" required>
                </div>
                <div class="input-group">
                    <label>Apellido</label>
                    <input type="text" name="apellido[]" placeholder="Ej: Pérez" required>
                </div>
                <div class="input-group">
                    <label>N° Registro (Biométrico)</label>
                    <input type="number" name="nro_registro[]" placeholder="Ej: 101">
                </div>
                <div class="input-group">
                    <label>Turno</label>
                    <select name="id_turno[]" required>${opcionesTurnos()}</select>
                </div>
                <div class="input-group">
                    <label>Fecha Ingreso</label>
                    <input type="date" name="fecha_ingreso[]" value="${new Date().toISOString().split('T')[0]}" required>
                </div>
            </div>
        `;

        if (contenedorTrabajadores) {
            contenedorTrabajadores.appendChild(card);
        }
    }

    // Eliminar formulario dinámico (global para el botón inline)
    window.eliminarTrabajador = function(btn) {
        btn.closest('.trabajador-card').remove();
    }

    // Botón "Eliminar" de cada trabajador (delegación de eventos; no se usa onclick en línea)
    document.addEventListener('click', (e) => {
        const boton = e.target.closest('[data-eliminar-fila]');
        if (boton) window.eliminarTrabajador(boton);
    });

    // Clic en botones de Cargo
    btnCargos.forEach(btn => {
        btn.addEventListener('click', function() {
            const cargoTexto = this.getAttribute('data-cargo');
            const cargoId = this.getAttribute('data-id');
            
            if (searchHeader) searchHeader.style.display = 'none';
            if (pasoCargos) pasoCargos.style.display = 'none';
            if (pasoFormulario) pasoFormulario.style.display = 'block';

            if (tituloCargoFormulario) tituloCargoFormulario.textContent = `Registro - Cargo: ${cargoTexto}`;
            if (inputCargoSeleccionado) inputCargoSeleccionado.value = cargoId; 
            
            if (contenedorTrabajadores) contenedorTrabajadores.innerHTML = '';
            contadorTrabajadores = 0;
            agregarFormularioTrabajador();
        });
    });

    // Clic en botón "+" para añadir otro trabajador
    if (btnAgregarTrabajador) {
        btnAgregarTrabajador.addEventListener('click', () => {
            agregarFormularioTrabajador();
        });
    }

    // ---------- Modos de la sección Trabajadores: lista <-> registro ----------
    const modoLista = document.getElementById('trabajadoresLista');
    const modoRegistro = document.getElementById('trabajadoresRegistro');

    function hayDatosSinGuardar() {
        return !!contenedorTrabajadores && pasoFormulario && pasoFormulario.style.display !== 'none'
            && [...contenedorTrabajadores.querySelectorAll('input[type="text"]')].some(i => i.value.trim() !== '');
    }

    function mostrarRegistro() {
        if (!modoLista || !modoRegistro) return;
        modoLista.hidden = true;
        modoRegistro.hidden = false;
        if (pasoCargos) pasoCargos.style.display = 'block';
        if (pasoFormulario) pasoFormulario.style.display = 'none';
        if (searchHeader) searchHeader.style.display = 'none';   // el buscador filtra la lista, aquí no aplica
        window.scrollTo({ top: 0 });
    }

    // También la usa viewsSwitcher.js al entrar a la sección desde el menú
    window.mostrarListaTrabajadores = function () {
        if (!modoLista || !modoRegistro) return;
        modoRegistro.hidden = true;
        modoLista.hidden = false;
        if (contenedorTrabajadores) contenedorTrabajadores.innerHTML = '';
        contadorTrabajadores = 0;
        if (searchHeader) searchHeader.style.display = 'flex';
    };

    // "＋ Registrar trabajadores" (en la lista y en las acciones rápidas del Inicio)
    document.querySelectorAll('[data-abrir-registro]').forEach(boton => {
        boton.addEventListener('click', () => {
            const seccion = document.getElementById('vistaTrabajadores');
            if (seccion && seccion.style.display === 'none') {
                const enlace = document.querySelector('.nav-link[data-target="vistaTrabajadores"]');
                if (enlace) enlace.click();
            }
            mostrarRegistro();
        });
    });

    // "← Volver a la lista" / "✕ Cancelar y volver a la lista"
    document.querySelectorAll('[data-volver-lista]').forEach(boton => {
        boton.addEventListener('click', () => {
            if (hayDatosSinGuardar() && !confirm('Hay datos sin guardar. ¿Volver a la lista y descartarlos?')) return;
            window.mostrarListaTrabajadores();
        });
    });

    // Botón "← Cambiar cargo" (regresa al paso 1)
    if (btnVolverCargos) {
        btnVolverCargos.addEventListener('click', () => {
            if (pasoFormulario) pasoFormulario.style.display = 'none';
            if (pasoCargos) pasoCargos.style.display = 'block';
        });
    }

    // Los avisos de ?status=... los muestra roles.js (se carga antes que este archivo).
    // Aquí solo se limpia la URL para que al recargar no se repita el aviso.
    if (new URLSearchParams(window.location.search).has('status')) {
        window.history.replaceState({}, document.title, window.location.pathname);
    }
});