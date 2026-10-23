<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Professional Login/Register</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../public/asset/css/login.css">
    <script src="../../public/asset/js/logiin.js" defer></script>
</head>

<body>
    <video class="main-bg-video" autoplay loop muted playsinline>
        <source src="../../public/asset/imgs/fuego.mp4" type="video/mp4">
    </video>
    <div class="auth-wrapper">

        <div class="form-container register-container" id="register-container">
            <form class="form-box" id="register-form">
                <h2 class="form-title">Create Account</h2>
                <p class="form-description">Regístrate para acceder a todas las funciones de nuestra plataforma.</p>
                <div class="form-group">
                    <label for="register-name">Nombre de Usuario</label>
                    <input id="register-name" type="text" placeholder="ejemplo: Usuario23">
                </div>
                <div class="form-group">
                    <label for="register-email">Correo Electronico</label>
                    <input id="register-email" type="email" placeholder="ejemplo: user@email.com">
                </div>
                <div class="form-group">
                    <label for="register-password">Contraseña</label>
                    <input id="register-password" type="password" placeholder="********">
                </div>

                <button class="form-button submit-button">Sign Up</button>
            </form>
        </div>


        <div class="form-container login-container" id="login-container">
            <form class="form-box" id="login-form" action="index.php?action=autenticar" method="POST">
                <h2 class="form-title">Login</h2>
                <p class="form-description">Inicia sesión con tu cuenta actual para continuar.</p>
                <?php if (!empty($error)): ?>
                    <p style="color: #ff4d4d; font-size: 0.9rem; margin-bottom: 10px;"><?= htmlspecialchars($error) ?></p>
                <?php endif; ?>
                <div class="form-group">
                    <label for="login-email">Usuario</label>
                   <input id="login-usuario" type="text" name="usuario" placeholder="ejemplo: tu_usuario" required>
                </div>
                <div class="form-group">
                    <label for="login-password">Contraseña</label>
                   <input id="login-password" type="password" name="clave" placeholder="********" required>
                </div>

                <button class="form-button submit-button" type="submit">Login</button>
            </form>
        </div>


        <div class="switch-panel" id="switch-panel">
            <div class="switch-content" id="switch-login">
                <video class="bg-video" autoplay loop muted playsinline>
                    <source src="../../public/asset/imgs/circulo_azul.mp4" type="video/mp4">
                </video>
                <h2>¡Bienvenido de nuevo!</h2>
                <p>Si ya tienes una cuenta, inicia sesión aquí.</p>
                <button class="switch-button toggle-btn">Login</button>
            </div>
            <div class="switch-content is-hidden" id="switch-register">
                <video class="bg-video" autoplay loop muted playsinline>
                    <source src="../../public/asset/imgs/negro.mp4" type="video/mp4">
                </video>
                <h2>Eres nuevo aquí?</h2>
                <p>Regístrate ahora y empieza a usar nuestra plataforma.</p>
                <button class="switch-button toggle-btn">Sign Up</button>
            </div>
        </div>
    </div>
  </body>
</html>