<?php
class LoginController {
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