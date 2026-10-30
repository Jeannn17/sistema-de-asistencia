const switchPanel = document.getElementById("switch-panel");
const switchLogin = document.getElementById("switch-login");
const switchRegister = document.getElementById("switch-register");
const toggleButtons = document.querySelectorAll(".toggle-btn");
const registerContainer = document.getElementById("register-container");
const loginContainer = document.getElementById("login-container");

const toggleForm = () => {
    switchPanel.classList.toggle("move-right");
    switchPanel.classList.toggle("switch-inverse");

    switchLogin.classList.toggle("is-hidden");
    switchRegister.classList.toggle("is-hidden");
    registerContainer.classList.toggle("move-left");
    loginContainer.classList.toggle("move-left");
    loginContainer.classList.toggle("bring-front");

    loginContainer.classList.toggle("login-blue");
};

toggleButtons.forEach(button => {
    button.addEventListener("click", toggleForm);
});

// Si el registro falló en el servidor, se muestra de nuevo el panel de registro
if (window.MOSTRAR_REGISTRO) {
    toggleForm();
}

// El video de fondo pesa varios MB: se pide cuando la página ya cargó (el formulario se puede usar
// de inmediato) y no se descarga en pantallas pequeñas, con "reducir movimiento" o con ahorro de datos
window.addEventListener("load", () => {
    const fondo = document.querySelector(".main-bg-video[data-src]");
    const ahorroDatos = navigator.connection && navigator.connection.saveData;
    if (!fondo || ahorroDatos
        || window.matchMedia("(prefers-reduced-motion: reduce)").matches
        || window.matchMedia("(max-width: 768px)").matches) {
        return;
    }
    fondo.src = fondo.dataset.src;
    fondo.play().catch(() => {});   // si el navegador bloquea la reproducción, queda el fondo de color
});
