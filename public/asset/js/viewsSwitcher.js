// =========================================
// CAMBIO DE VISTAS PRINCIPALES (SIDEBAR)
// =========================================
document.addEventListener('DOMContentLoaded', () => {
    const navLinks = document.querySelectorAll('.nav-link');
    const seccionesVista = document.querySelectorAll('.seccion-vista');
    const searchHeader = document.querySelector('.search-header');
    const pasoCargos = document.getElementById('pasoCargos');
    const pasoFormulario = document.getElementById('pasoFormulario');

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

            // 4. Restablecer la barra de búsqueda y limpiar sub-pasos de registro
            if (searchHeader) searchHeader.style.display = 'flex';
            
            if (targetId === 'vistaRegistro') {
                if (pasoCargos) pasoCargos.style.display = 'block';
                if (pasoFormulario) pasoFormulario.style.display = 'none';
            }
        });
    });
});