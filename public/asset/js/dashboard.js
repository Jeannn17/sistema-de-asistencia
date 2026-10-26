// =========================================
// DASHBOARD: menú móvil, fecha, atajos y buscador
// (el cambio de vistas lo sigue haciendo viewsSwitcher.js)
// =========================================
document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.getElementById('sidebar');
    const btnMenu = document.getElementById('sidebarToggle');

    // ---------- Menú lateral en pantallas pequeñas ----------
    if (btnMenu && sidebar) {
        btnMenu.addEventListener('click', (e) => {
            e.stopPropagation();
            sidebar.classList.toggle('abierto');
        });
        document.addEventListener('click', (e) => {
            if (!sidebar.contains(e.target) && e.target !== btnMenu) {
                sidebar.classList.remove('abierto');
            }
        });
        sidebar.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', () => sidebar.classList.remove('abierto'));
        });
    }

    // ---------- Fecha de hoy en la barra superior ----------
    const fecha = document.getElementById('fechaHoy');
    if (fecha) {
        const texto = new Date().toLocaleDateString('es-VE', {
            weekday: 'long', day: 'numeric', month: 'long', year: 'numeric'
        });
        fecha.textContent = texto.charAt(0).toUpperCase() + texto.slice(1);
    }

    // ---------- Botones de acceso rápido: <button data-goto="vistaImportar"> ----------
    document.querySelectorAll('[data-goto]').forEach(boton => {
        boton.addEventListener('click', () => {
            const enlace = document.querySelector(`.nav-link[data-target="${boton.dataset.goto}"]`);
            if (enlace) enlace.click();
        });
    });

    // ---------- Buscador: filtra las filas de las tablas visibles ----------
    const buscador = document.getElementById('buscadorGlobal');
    if (buscador) {
        buscador.addEventListener('input', () => {
            const q = buscador.value.trim().toLowerCase();
            document.querySelectorAll('.tabla-registros tbody tr, .permisos-tabla tbody tr').forEach(fila => {
                fila.style.display = (!q || fila.textContent.toLowerCase().includes(q)) ? '' : 'none';
            });
        });
    }
});
