// =========================================
// MÓDULO DE PERMISOS / JUSTIFICACIONES
// El aviso del resultado (?status=permiso_...) y la vista (?vista=permisos) los muestra roles.js.
// =========================================
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('formPermiso');
    if (!form) return;

    const buscarPersona = document.getElementById('permisoBuscarPersona');
    const idPersona = document.getElementById('permisoIdPersona');
    const lista = document.getElementById('listaPersonalPermiso');
    const selectTipo = document.getElementById('permisoTipo');
    const fechaInicio = document.getElementById('permisoFechaInicio');
    const fechaFin = document.getElementById('permisoFechaFin');
    const hora = document.getElementById('permisoHora');
    const grupoFin = document.getElementById('grupoFechaFin');
    const grupoHora = document.getElementById('grupoHora');
    const labelFechaInicio = document.getElementById('labelFechaInicio');
    const labelHora = document.getElementById('labelHora');
    const motivo = document.getElementById('permisoMotivo');
    const contador = document.getElementById('contadorMotivo');

    // ---------- Trabajador: texto del buscador -> id_persona ----------
    // Acepta el texto completo de la lista o solo una parte (cédula, nombre, apellido)
    // siempre que identifique a UN solo trabajador.
    function normalizar(t) {
        return String(t).toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').trim();
    }

    const opciones = [];
    if (lista) {
        lista.querySelectorAll('option').forEach(op => {
            opciones.push({ texto: op.value, id: op.dataset.id, norm: normalizar(op.value) });
        });
    }

    function sincronizarPersona(completar = false) {
        const texto = buscarPersona.value.trim();
        const q = normalizar(texto);
        let id = '';
        let mensaje = 'Elige un trabajador de la lista.';

        const exacta = opciones.find(o => o.texto === texto);
        if (exacta) {
            id = exacta.id;
        } else if (q) {
            const coincidencias = opciones.filter(o => o.norm.includes(q));
            if (coincidencias.length === 1) {
                id = coincidencias[0].id;
                if (completar) buscarPersona.value = coincidencias[0].texto; // completa el texto
            } else if (coincidencias.length > 1) {
                mensaje = 'Hay varios trabajadores con ese dato. Escribe más o elige uno de la lista.';
            } else {
                mensaje = 'No se encontró ningún trabajador activo con ese dato.';
            }
        }

        idPersona.value = id;
        buscarPersona.setCustomValidity(id ? '' : mensaje);
    }
    buscarPersona.addEventListener('input', () => sincronizarPersona(false));
    buscarPersona.addEventListener('change', () => sincronizarPersona(true));

    // ---------- Campos según el tipo de justificación ----------
    // data-modo en cada <option>: "rango" (desde-hasta), "dia" (un día) o "hora" (un día + hora)
    function actualizarCampos() {
        const opcion = selectTipo.options[selectTipo.selectedIndex];
        const modo = opcion ? opcion.dataset.modo : '';

        const usaRango = modo === 'rango';
        const usaHora = modo === 'hora';

        grupoFin.classList.toggle('campo-oculto', !usaRango);
        fechaFin.disabled = !usaRango;
        fechaFin.required = usaRango;

        grupoHora.classList.toggle('campo-oculto', !usaHora);
        hora.disabled = !usaHora;
        hora.required = usaHora;

        labelFechaInicio.textContent = usaRango ? 'Fecha de inicio' : 'Fecha';
        if (usaHora) {
            labelHora.textContent = selectTipo.value === 'salida_temprana'
                ? 'Hora de salida'
                : 'Hora de llegada';
        }
        if (!usaRango) fechaFin.value = '';
        if (!usaHora) hora.value = '';
    }
    selectTipo.addEventListener('change', actualizarCampos);

    // La fecha final no puede ser anterior a la inicial
    fechaInicio.addEventListener('change', () => {
        fechaFin.min = fechaInicio.value;
        if (fechaFin.value && fechaFin.value < fechaInicio.value) fechaFin.value = fechaInicio.value;
    });

    // Contador de caracteres del motivo
    motivo.addEventListener('input', () => {
        contador.textContent = `${motivo.value.length}/500`;
    });

    form.addEventListener('submit', (e) => {
        sincronizarPersona(true);
        if (!form.checkValidity()) {
            e.preventDefault();
            form.reportValidity();
        }
    });

    actualizarCampos();
    sincronizarPersona();
    buscarPersona.setCustomValidity(''); // no mostrar el error antes de que el usuario escriba

    // La vista (?vista=permisos) y el aviso del resultado (?status=permiso_...) los muestra roles.js
});
