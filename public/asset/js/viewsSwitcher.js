// =========================================
// CAMBIO DE VISTAS PRINCIPALES (SIDEBAR)
// =========================================
document.addEventListener('DOMContentLoaded', () => {
    const navLinks = document.querySelectorAll('.nav-link');
    const seccionesVista = document.querySelectorAll('.seccion-vista');
    const searchHeader = document.querySelector('.search-header');

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

            // 4. Restablecer la barra de búsqueda
            if (searchHeader) searchHeader.style.display = 'flex';

            // 5. Al entrar a Trabajadores siempre se muestra la lista (no un registro a medias)
            if (targetId === 'vistaTrabajadores' && typeof window.mostrarListaTrabajadores === 'function') {
                window.mostrarListaTrabajadores();
            }
        });
    });
});