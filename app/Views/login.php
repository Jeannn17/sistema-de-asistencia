<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Asistencia | U.E. El Itaca</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('css/login.css') ?>">
    <?php if (!empty($errorRegistro) || !empty($mostrarRegistro)): ?>
    <script nonce="<?= nonce() ?>">window.MOSTRAR_REGISTRO = true;</script>
    <?php endif; ?>
    <script src="<?= asset('js/logiin.js') ?>" defer></script>
</head>

<body>
    <!-- Video de fondo pesado: logiin.js lo carga después de la página y solo en pantallas grandes -->
    <video class="main-bg-video" loop muted playsinline preload="none" data-src="<?= asset('imgs/fuego.mp4') ?>"></video>
    <div class="auth-wrapper">

        <div class="form-container register-container" id="register-container">
            <form class="form-box" id="register-form" action="<?= url('auth/processRegister') ?>" method="POST">
<?= csrf_campo() ?>
                <h2 class="form-title">Solicitar cuenta</h2>
                <p class="form-description">Tu cuenta será <strong>administrativa</strong> y quedará pendiente hasta que la directiva la apruebe.</p>
                <?php if (empty($registroAbierto)): ?>
                    <p style="color: #ffd166; font-size: 0.95rem; margin: 12px 0;">Las solicitudes de cuenta están cerradas. Pide a la directiva que te cree la cuenta.</p>
                <?php else: ?>
                <?php if (!empty($errorRegistro)): ?>
                    <p style="color: #ff4d4d; font-size: 0.9rem; margin-bottom: 10px;"><?= htmlspecialchars($errorRegistro) ?></p>
                <?php endif; ?>
                <div class="form-group">
                    <label for="register-fullname">Nombre completo</label>
                    <input id="register-fullname" type="text" name="nombre" maxlength="50" placeholder="ejemplo: María Pérez"
                           value="<?= htmlspecialchars($old['nombre'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label for="register-name">Nombre de Usuario</label>
                    <input id="register-name" type="text" name="usuario" maxlength="50" placeholder="ejemplo: Usuario23"
                           value="<?= htmlspecialchars($old['usuario'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label for="register-email">Correo Electronico</label>
                    <input id="register-email" type="email" name="correo" placeholder="ejemplo: user@email.com"
                           value="<?= htmlspecialchars($old['correo'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label for="register-password">Contraseña</label>
                    <input id="register-password" type="password" name="clave" minlength="8" maxlength="72" placeholder="Mínimo 8 caracteres, con letras y números" required>
                </div>

                <button class="form-button submit-button" type="submit">Enviar solicitud</button>
                <?php endif; ?>
            </form>
        </div>


        <div class="form-container login-container" id="login-container">
            <form class="form-box" id="login-form" action="<?= url('auth/authenticate') ?>" method="POST">
<?= csrf_campo() ?>
                <h2 class="form-title">Login</h2>
                <p class="form-description">Inicia sesión con tu cuenta actual para continuar.</p>
                <?php if (!empty($error)): ?>
                    <p style="color: #ff4d4d; font-size: 0.9rem; margin-bottom: 10px;"><?= htmlspecialchars($error) ?></p>
                <?php endif; ?>
                <?php if (!empty($exito)): ?>
                    <p style="color: #22c55e; font-size: 0.9rem; margin-bottom: 10px;"><?= htmlspecialchars($exito) ?></p>
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
                    <source src="<?= asset('imgs/circulo_azul.mp4') ?>" type="video/mp4">
                </video>
                <h2>¡Bienvenido de nuevo!</h2>
                <p>Si ya tienes una cuenta, inicia sesión aquí.</p>
                <button class="switch-button toggle-btn">Login</button>
            </div>
            <div class="switch-content is-hidden" id="switch-register">
                <video class="bg-video" autoplay loop muted playsinline>
                    <source src="<?= asset('imgs/negro.mp4') ?>" type="video/mp4">
                </video>
                <h2>Eres nuevo aquí?</h2>
                <p>Solicita tu cuenta administrativa; la directiva la revisará y aprobará.</p>
                <button class="switch-button toggle-btn">Solicitar cuenta</button>
            </div>
        </div>
    </div>
  </body>
</html>