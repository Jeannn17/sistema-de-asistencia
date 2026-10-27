// =========================================
// GESTIÓN: ventanas modales, confirmaciones
//   <button data-nuevo="modalTurno">            abre el modal vacío
//   <button data-editar="modalTurno" data-json='{"campo":"valor"}'>   abre el modal con datos
//   <form data-confirmar="¿Seguro?">            pide confirmación antes de enviar
// =========================================
document.addEventListener('DOMContentLoaded', () => {
    function abrir(modal) {
        modal.hidden = false;
        const primero = modal.querySelector('input[type="text"], input[type="number"]');
        if (primero) setTimeout(() => primero.focus(), 30);
    }

    function cerrar(modal) {
        modal.hidden = true;
    }

    // ---------- Nuevo ----------
    document.querySelectorAll('[data-nuevo]').forEach(boton => {
        boton.addEventListener('click', () => {
            const modal = document.getElementById(boton.dataset.nuevo);
            if (!modal) return;
            const form = modal.querySelector('form');
            form.reset();
            form.querySelectorAll('input[type="hidden"][data-id]').forEach(h => { h.value = ''; });
            const titulo = modal.querySelector('[data-titulo]');
            if (titulo) titulo.textContent = titulo.dataset.nuevo;
            abrir(modal);
        });
    });

    // ---------- Editar ----------
    document.querySelectorAll('[data-editar]').forEach(boton => {
        boton.addEventListener('click', () => {
            const modal = document.getElementById(boton.dataset.editar);
            if (!modal) return;
            let datos = {};
            try { datos = JSON.parse(boton.dataset.json || '{}'); } catch (e) { return; }

            const form = modal.querySelector('form');
            form.reset();
            Object.entries(datos).forEach(([nombre, valor]) => {
                const campo = form.elements[nombre];
                if (!campo) return;
                if (campo.type === 'checkbox') {
                    campo.checked = valor === true || valor === 1 || valor === '1';
                } else {
                    campo.value = valor === null ? '' : valor;
                }
            });
            const titulo = modal.querySelector('[data-titulo]');
            if (titulo) titulo.textContent = titulo.dataset.editar;
            abrir(modal);
        });
    });

    // ---------- Cerrar ----------
    document.querySelectorAll('.modal-fondo').forEach(modal => {
        modal.addEventListener('click', (e) => {
            if (e.target === modal || e.target.closest('[data-cerrar-modal]')) cerrar(modal);
        });
    });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal-fondo:not([hidden])').forEach(cerrar);
        }
    });

    // ---------- Confirmaciones ----------
    document.querySelectorAll('form[data-confirmar]').forEach(form => {
        form.addEventListener('submit', (e) => {
            if (!confirm(form.dataset.confirmar)) e.preventDefault();
        });
    });
});
