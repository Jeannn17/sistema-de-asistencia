// =========================================
// MENÚ DESPLEGABLE / HAMBURGUESA
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