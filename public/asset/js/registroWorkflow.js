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
                ${contadorTrabajadores > 1 ? '<button type="button" class="btn-eliminar-fila" onclick="eliminarTrabajador(this)">✕ Eliminar</button>' : ''}
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

    // Botón "← Volver" (Regresa a los cargos)
    if (btnVolverCargos) {
        btnVolverCargos.addEventListener('click', () => {
            if (pasoFormulario) pasoFormulario.style.display = 'none';
            if (searchHeader) searchHeader.style.display = 'flex';
            if (pasoCargos) pasoCargos.style.display = 'block';
        });
    }

    // Sistema de Notificaciones (Toast)
    function mostrarToast(mensaje, tipo) {
        const toast = document.getElementById('toastNotificacion');
        const toastMensaje = document.getElementById('toastMensaje');

        if (!toast) return;

        toastMensaje.textContent = mensaje;
        toast.classList.remove('toast-exito', 'toast-error', 'mostrar');

        if (tipo === 'exito') {
            toast.classList.add('toast-exito');
        } else {
            toast.classList.add('toast-error');
        }

        toast.classList.add('mostrar');

        setTimeout(() => {
            toast.classList.remove('mostrar');
        }, 4000);
    }

    // Verificar parámetros URL al cargar
    const params = new URLSearchParams(window.location.search);
    if (params.has('status')) {
        const status = params.get('status');
        
        if (status === 'success') {
            mostrarToast('✅ ¡Registro exitoso!', 'exito');
        } else if (status === 'duplicado') {
            mostrarToast('⚠️ La cédula o el N° de registro ya existe.', 'error');
        } else if (status === 'error') {
            mostrarToast('❌ No se pudo registrar los datos.', 'error');
        } else if (status === 'import_ok') {
            mostrarToast(`✅ Importadas: ${params.get('insertados')} · Repetidas: ${params.get('duplicados')} · Empleados no registrados: ${params.get('omitidos')}`, 'exito');
        } else if (status === 'import_vacio') {
            mostrarToast('⚠️ No se encontraron marcaciones en el archivo.', 'error');
        } else if (status === 'import_invalido') {
            mostrarToast('⚠️ Sube un archivo .xlsx o .xls válido.', 'error');
        } else if (status === 'import_error') {
            mostrarToast('❌ No se pudo procesar el archivo.', 'error');
        }

        window.history.replaceState({}, document.title, window.location.pathname);
    }
});