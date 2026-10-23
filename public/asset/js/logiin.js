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