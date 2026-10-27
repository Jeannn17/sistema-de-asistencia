<?php
class LoginController {
    // Toda cuenta nueva se crea con este rol. NUNCA se toma del formulario.
    private const ROL_NUEVOS_USUARIOS = 'administrativo';

    private $model;

    public function __construct($db) {
        // Inicializamos el modelo de usuarios
        $this->model = new UsuarioModel($db);
    }

    // 1. Mostrar la vista del formulario de login
    public function mostrarLogin() {
        // Si el usuario ya inició sesión previamente, lo redirigimos directo al dashboard unificado
        if (isset($_SESSION['usuario'])) {
            header('Location: index.php?action=dashboard');
            exit();
        }
        // Aviso después de crear una cuenta (redirección desde registrar())
        $exito = (($_GET['registro'] ?? '') === 'ok')
            ? 'Cuenta creada correctamente. Ya puedes iniciar sesión.'
            : null;
        include __DIR__ . '/../views/logiin.php';
    }

    // 2. Procesar las credenciales enviadas desde el formulario
    public function procesarLogin() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $identificador = trim($_POST['usuario'] ?? ''); // Puede ser el nombre de usuario o la cédula
            $password = $_POST['clave'] ?? '';

            // Buscar el usuario en la base de datos usando el modelo
            $user = $this->model->obtenerUsuario($identificador);

            // Obtener el hash de la contraseña almacenada (soporta 'clave' o 'password')
            $hashAlmacenado = $user['clave'] ?? $user['password'] ?? '';

            // Verificar si el usuario existe y si la contraseña coincide con el hash
            if ($user && $hashAlmacenado !== '' && password_verify($password, $hashAlmacenado)) {

                // Registrar las variables esenciales en la sesión
                $_SESSION['id'] = $user['id'] ?? null;
                $nombreCompleto = trim(($user['nombre'] ?? '') );
                $_SESSION['nombre'] = $nombreCompleto ?: ($user['usuario'] ?? 'Usuario');
                $_SESSION['usuario'] = $user['usuario'] ?? '';
                $_SESSION['rol'] = $user['rol'] ?? ''; // Almacena 'directivo' o 'administrativo'

                // REDIRECCIÓN UNIFICADA:
                // Ambos roles van al mismo dashboard. La interfaz se encargará
                // de mostrar u ocultar los módulos según el rol ($_SESSION['rol']).
                header('Location: index.php?action=dashboard');
                exit();

            } else {
                // Si las credenciales fallan, recargamos la vista con un mensaje de error
                $error = "Usuario o contraseña incorrectos.";
                include __DIR__ . '/../views/logiin.php';
            }
        }
    }

    // 2.1 Registrar una cuenta nueva (siempre como rol 'administrativo')
    public function registrar() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?action=login');
            exit();
        }

        $nombre  = trim($_POST['nombre'] ?? '');
        $usuario = trim($_POST['usuario'] ?? '');
        $correo  = trim($_POST['correo'] ?? '');
        $clave   = $_POST['clave'] ?? '';
        $old     = ['nombre' => $nombre, 'usuario' => $usuario, 'correo' => $correo];
        $errorRegistro = null;

        if ($nombre === '' || $usuario === '' || $correo === '' || $clave === '') {
            $errorRegistro = 'Completa todos los campos.';
        } elseif (mb_strlen($nombre) > 50 || mb_strlen($usuario) > 50) {
            $errorRegistro = 'El nombre y el usuario deben tener máximo 50 caracteres.';
        } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            $errorRegistro = 'Escribe un correo electrónico válido.';
        } elseif (mb_strlen($clave) < 6) {
            $errorRegistro = 'La contraseña debe tener al menos 6 caracteres.';
        } else {
            try {
                if ($this->model->existeUsuarioOCorreo($usuario, $correo)) {
                    $errorRegistro = 'Ese usuario o ese correo ya está registrado.';
                } else {
                    $this->model->registrar($nombre, $usuario, $correo, $clave, self::ROL_NUEVOS_USUARIOS);
                    // Redirigir evita que al recargar la página se reenvíe el formulario
                    header('Location: index.php?action=login&registro=ok');
                    exit();
                }
            } catch (PDOException $e) {
                error_log('Registro de usuario: ' . $e->getMessage());
                // 23505 = violación de clave única (usuario o correo repetido)
                $errorRegistro = $e->getCode() === '23505'
                    ? 'Ese usuario o ese correo ya está registrado.'
                    : 'No se pudo crear la cuenta. Inténtalo de nuevo.';
            }
        }

        // Hubo un error: se vuelve al formulario mostrando el panel de registro
        include __DIR__ . '/../views/logiin.php';
    }

    // 3. Cerrar sesión de manera segura
    public function cerrarSesion() {
        // Destruir todas las variables de sesión
        session_unset();
        session_destroy();
        
        // Redirigir al formulario de login
        header('Location: index.php?action=login');
        exit();
    }
}
?>