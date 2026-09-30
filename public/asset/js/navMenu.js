// =========================================
// 1. MENÚ DESPLEGABLE / HAMBURGUESA
// =========================================
const menuToggle = document.getElementById('menuToggle');
const dropdownMenu = document.getElementById('dropdownMenu');
const menuOverlay = document.getElementById('menuOverlay');

function toggleMenu(e) {
    e.stopPropagation();
    if (dropdownMenu) dropdownMenu.classList.toggle('active');
    if (menuOverlay) menuOverlay.classList.toggle('active');
}

function closeMenu(e) {
    if (menuToggle && dropdownMenu) {
        if (!menuToggle.contains(e.target) && !dropdownMenu.contains(e.target)) {
            dropdownMenu.classList.remove('active');
            if (menuOverlay) menuOverlay.classList.remove('active');
        }
    }
}

if (menuToggle) {
    menuToggle.addEventListener('click', toggleMenu);
    document.addEventListener('click', closeMenu);
}

// =========================================
// 2. CAMBIO DE VISTAS PRINCIPALES (INICIO / REGISTRO / ETC.)
// =========================================
const navLinks = document.querySelectorAll('.nav-link');
const seccionesVista = document.querySelectorAll('.seccion-vista');

navLinks.forEach(link => {
    link.addEventListener('click', function(e) {
        e.preventDefault();

        // 1. Quitar estado activo de los enlaces
        navLinks.forEach(l => l.classList.remove('active'));
        this.classList.add('active');

        // 2. Ocultar todas las secciones principales
        seccionesVista.forEach(seccion => seccion.style.display = 'none');

        // 3. Mostrar la sección objetivo
        const targetId = this.getAttribute('data-target');
        const seccionObjetivo = document.getElementById(targetId);

        if (seccionObjetivo) {
            seccionObjetivo.style.display = 'block';
        }
    });
});

// =========================================
// 3. FLUJO DE REGISTRO SIMPLIFICADO
// =========================================
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
                <input type="text" name="cedula[]" placeholder="Ej: V-12345678" required>
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
                <select name="id_turno[]" required>
                    <option value="1">Mañana</option>
                    <option value="2">Tarde</option>
                </select>
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

// Eliminar formulario dinámico
function eliminarTrabajador(btn) {
    btn.closest('.trabajador-card').remove();
}

// 1. Clic en cualquiera de los botones de Cargo
btnCargos.forEach(btn => {
    btn.addEventListener('click', function() {
        const cargoTexto = this.getAttribute('data-cargo');
        const cargoId = this.getAttribute('data-id');
        
        if (searchHeader) searchHeader.style.display = 'none';
        pasoCargos.style.display = 'none';
        pasoFormulario.style.display = 'block';

        // Título limpio, sin la palabra "Masivo"
        if (tituloCargoFormulario) tituloCargoFormulario.textContent = `Registro - Cargo: ${cargoTexto}`;
        if (inputCargoSeleccionado) inputCargoSeleccionado.value = cargoId; 
        
        if (contenedorTrabajadores) contenedorTrabajadores.innerHTML = '';
        contadorTrabajadores = 0;
        agregarFormularioTrabajador(); // Inicia siempre con 1 trabajador
    });
});

// 2. Clic en botón "+" para añadir otro trabajador
if (btnAgregarTrabajador) {
    btnAgregarTrabajador.addEventListener('click', () => {
        agregarFormularioTrabajador();
    });
}

// 3. Botón "← Volver" (Regresa a los cargos)
if (btnVolverCargos) {
    btnVolverCargos.addEventListener('click', () => {
        pasoFormulario.style.display = 'none';
        if (searchHeader) searchHeader.style.display = 'flex';
        pasoCargos.style.display = 'block';
    });
}

// 4. Restablecer interfaz al presionar "Registro" desde el sidebar
const linkRegistro = document.querySelector('[data-target="vistaRegistro"]');
if (linkRegistro) {
    linkRegistro.addEventListener('click', () => {
        if (searchHeader) searchHeader.style.display = 'flex';
        pasoCargos.style.display = 'block';
        if (pasoFormulario) pasoFormulario.style.display = 'none';
    });
}

// =========================================
// 7. SISTEMA DE NOTIFICACIONES (TOAST)
// =========================================
function mostrarToast(mensaje, tipo) {
    const toast = document.getElementById('toastNotificacion');
    const toastMensaje = document.getElementById('toastMensaje');

    if (!toast) return;

    // Asignar el texto
    toastMensaje.textContent = mensaje;

    // Limpiar clases previas
    toast.classList.remove('toast-exito', 'toast-error', 'mostrar');

    // Asignar color según el tipo
    if (tipo === 'exito') {
        toast.classList.add('toast-exito');
    } else {
        toast.classList.add('toast-error');
    }

    // Mostrar el cuadro
    toast.classList.add('mostrar');

    // Ocultarlo automáticamente después de 4 segundos (4000 ms)
    setTimeout(() => {
        toast.classList.remove('mostrar');
    }, 4000);
}

// Ejecutar al cargar la página verificando la URL
document.addEventListener('DOMContentLoaded', () => {
    const params = new URLSearchParams(window.location.search);
    
    if (params.has('status')) {
        const status = params.get('status');
        
        if (status === 'success') {
            mostrarToast('✅ ¡Registro exitoso!', 'exito');
        } else if (status === 'error') {
            mostrarToast('❌ No se pudo registrar los datos.', 'error');
        }

        // Limpia la URL para que no reaparezca el aviso si el usuario presiona F5
        window.history.replaceState({}, document.title, window.location.pathname);
    }
});