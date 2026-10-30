// =========================================
// Muestra el aviso (toast) de ?status=...
// La sección que se abre (?vista=...) ya la decide DashboardController en el servidor.
// IMPORTANTE: va ANTES de registroWorkflow.js, porque ese archivo limpia la URL.
// =========================================
document.addEventListener('DOMContentLoaded', () => {
    const params = new URLSearchParams(window.location.search);

    // ---------- Avisos ----------
    // Único lugar donde se muestran los avisos de ?status=... Si el servidor dejó un mensaje
    // detallado (Flash, en data-flash-*), ese tiene prioridad sobre el texto genérico.
    const mensajes = {
        success:             ['✅ ¡Registro exitoso!', 'exito'],
        duplicado:           ['⚠️ La cédula o el N° de registro ya existe.', 'error'],
        error:               ['❌ No se pudo registrar los datos.', 'error'],
        import_ok:           [`✅ Importadas: ${params.get('insertados')} · Repetidas: ${params.get('duplicados')} · Empleados no registrados: ${params.get('omitidos')}`, 'exito'],
        import_vacio:        ['⚠️ No se encontraron marcaciones en el archivo.', 'error'],
        import_invalido:     ['⚠️ Sube un archivo .xlsx o .xls válido.', 'error'],
        import_error:        ['❌ No se pudo procesar el archivo.', 'error'],
        permiso_ok:          ['✅ Permiso registrado correctamente.', 'exito'],
        permiso_duplicado:   ['⚠️ Ya existe un permiso de ese tipo que se cruza con esas fechas.', 'error'],
        permiso_invalido:    ['⚠️ Revisa los datos del formulario.', 'error'],
        permiso_error:       ['❌ No se pudo guardar el permiso.', 'error'],
        sin_permiso:         ['⛔ Tu rol no tiene permiso para realizar esa acción.', 'error'],
        persona_ok:          ['✅ Datos del trabajador actualizados.', 'exito'],
        persona_estado_ok:   ['✅ Estado del trabajador actualizado.', 'exito'],
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
    const toast = document.getElementById('toastNotificacion');
    const toastMensaje = document.getElementById('toastMensaje');
    const aviso = (toast && toast.dataset.flashMensaje)
        ? [toast.dataset.flashMensaje, toast.dataset.flashTipo]
        : mensajes[params.get('status')];

    if (aviso && toast && toastMensaje) {
        toastMensaje.textContent = aviso[0];
        toast.classList.remove('toast-exito', 'toast-error', 'mostrar');
        toast.classList.add(aviso[1] === 'exito' ? 'toast-exito' : 'toast-error', 'mostrar');
        // Los mensajes largos (ej. el resumen de la importación) se dejan más tiempo en pantalla
        const duracion = Math.min(12000, Math.max(4500, aviso[0].length * 60));
        setTimeout(() => toast.classList.remove('mostrar'), duracion);
        toast.addEventListener('click', () => toast.classList.remove('mostrar'), { once: true });
    }
});
