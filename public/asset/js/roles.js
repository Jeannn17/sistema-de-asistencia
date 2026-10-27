// =========================================
// Abre la vista indicada en ?vista=... y muestra el aviso (toast) de ?status=...
// IMPORTANTE: va ANTES de registroWorkflow.js, porque ese archivo limpia la URL.
// =========================================
document.addEventListener('DOMContentLoaded', () => {
    const params = new URLSearchParams(window.location.search);

    // ---------- Vista ----------
    const vistas = {
        trabajadores: 'vistaTrabajadores',
        registro:     'vistaRegistro',
        turnos:       'vistaTurnos',
        cargos:       'vistaCargos',
        asistencias:  'vistaAsistencias',
        importar:     'vistaImportar',
        permisos:     'vistaPermisos',
    };
    const destino = vistas[params.get('vista')];
    if (destino) {
        const link = document.querySelector(`.nav-link[data-target="${destino}"]`);
        if (link) link.click();
    }

    // ---------- Avisos ----------
    const mensajes = {
        sin_permiso:         ['⛔ Tu rol no tiene permiso para realizar esa acción.', 'error'],
        persona_ok:          ['✅ Datos del trabajador actualizados.', 'exito'],
        persona_invalido:    ['⚠️ Revisa los datos del trabajador.', 'error'],
        persona_duplicado:   ['⚠️ Esa cédula o ese N° de registro ya pertenece a otro trabajador.', 'error'],
        persona_error:       ['❌ No se pudo actualizar al trabajador.', 'error'],
        turno_ok:            ['✅ Turno guardado correctamente.', 'exito'],
        turno_estado_ok:     ['✅ Estado del turno actualizado.', 'exito'],
        turno_invalido:      ['⚠️ Revisa el nombre y los horarios del turno.', 'error'],
        turno_duplicado:     ['⚠️ Ya existe un turno con ese nombre.', 'error'],
        turno_error:         ['❌ No se pudo guardar el turno.', 'error'],
        cargo_ok:            ['✅ Cargo guardado correctamente.', 'exito'],
        cargo_estado_ok:     ['✅ Estado del cargo actualizado.', 'exito'],
        cargo_invalido:      ['⚠️ Escribe un nombre de cargo válido.', 'error'],
        cargo_duplicado:     ['⚠️ Ya existe un cargo con ese nombre.', 'error'],
        cargo_error:         ['❌ No se pudo guardar el cargo.', 'error'],
    };
    const aviso = mensajes[params.get('status')];
    const toast = document.getElementById('toastNotificacion');
    const toastMensaje = document.getElementById('toastMensaje');

    if (aviso && toast && toastMensaje) {
        toastMensaje.textContent = aviso[0];
        toast.classList.remove('toast-exito', 'toast-error', 'mostrar');
        toast.classList.add(aviso[1] === 'exito' ? 'toast-exito' : 'toast-error', 'mostrar');
        setTimeout(() => toast.classList.remove('mostrar'), 4500);
    }
});
