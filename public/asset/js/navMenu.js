const menuToggle = document.getElementById('menuToggle');
const dropdownMenu = document.getElementById('dropdownMenu');
const menuOverlay = document.getElementById('menuOverlay');

function toggleMenu(e) {
    e.stopPropagation();
    dropdownMenu.classList.toggle('active');
    menuOverlay.classList.toggle('active');
}

function closeMenu() {
   if (!menuToggle.contains(e.target) && !dropdownMenu.contains(e.target)) {
        dropdownMenu.classList.remove('active');
   }
}

// Abrir/Cerrar al presionar el botón de tres líneas
menuToggle.addEventListener('click', toggleMenu);

// Cerrar el menú al hacer clic en cualquier parte difuminada fuera del menú
document.addEventListener('click', closeMenu);
