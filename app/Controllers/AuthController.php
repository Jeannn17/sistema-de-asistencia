<?php

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Database;
use App\Core\Env;
use App\Core\ValidacionException;
use App\Core\View;
use App\Models\UsuarioModel;
use PDOException;

class AuthController
{
    /** Toda cuenta nueva se crea con este rol (y en estado pendiente). NUNCA se toma del formulario. */
    private const ROL_NUEVOS_USUARIOS = UsuarioModel::ROL_ADMINISTRATIVO;

    private const MENSAJE_SOLICITUD = 'Su solicitud de cuenta administrativa ha sido registrada. '
        . 'Debe ser aprobada por la directiva antes de poder iniciar sesión.';

    /** Bloqueo temporal por intentos fallidos de login */
    private const VENTANA_MINUTOS = 15;
    private const MAX_FALLOS_USUARIO = 5;
    private const MAX_FALLOS_IP = 15;

    /** Hash falso: se verifica cuando el usuario no existe para que el tiempo de respuesta sea igual */
    private const HASH_FALSO = '$2y$10$9Uc9NoR6GLSSF1UiLbJmwOxQ6UxA23AFeZFDVq1u2wHvnAfvDHYo2';

    private UsuarioModel $model;

    public function __construct()
    {
        $this->model = new UsuarioModel(Database::conectar());
    }

    // ------------------------------------------------------------------ LOGIN
    /** GET /auth/login */
    public function login(): void
    {
        $this->mostrarLogin([
            'exito' => (($_GET['registro'] ?? '') === 'pendiente') ? self::MENSAJE_SOLICITUD : null,
            'error' => (($_GET['expirada'] ?? '') === '1') ? 'Tu sesión expiró por inactividad. Inicia sesión de nuevo.' : null,
        ]);
    }

    /** POST /auth/authenticate */
    public function authenticate(): void
    {
        $usuario  = trim((string) ($_POST['usuario'] ?? ''));
        $password = (string) ($_POST['clave'] ?? '');
        $ip       = ip_cliente();

        if ($this->bloqueado($usuario, $ip)) {
            http_response_code(429);
            $this->mostrarLogin(['error' => 'Demasiados intentos fallidos. Espera unos minutos e inténtalo de nuevo.']);
            return;
        }

        $user = $usuario !== '' ? $this->model->obtenerUsuario($usuario) : false;
        $hash = $user ? (string) $user['clave'] : '';

        if (!$user || $hash === '') {
            password_verify($password, self::HASH_FALSO);   // mismo costo de tiempo, exista o no el usuario
            $ok = false;
        } else {
            $ok = password_verify($password, $hash);
        }

        if (!$ok) {
            $this->registrarFallo($usuario, $ip);
            $this->mostrarLogin(['error' => 'Usuario o contraseña incorrectos.']);
            return;
        }

        // Clave correcta, pero la cuenta todavía no ha sido aprobada por la directiva
        if (($user['estado'] ?? UsuarioModel::ESTADO_APROBADO) !== UsuarioModel::ESTADO_APROBADO) {
            $this->mostrarLogin(['error' => 'Tu cuenta todavía está pendiente de aprobación por la directiva.']);
            return;
        }

        // Login correcto: se renueva el ID de sesión (evita session fixation) y el token CSRF
        session_regenerate_id(true);
        Csrf::regenerar();
        $_SESSION['usuario_id']       = (int) $user['id'];
        $_SESSION['id']               = (int) $user['id'];
        $_SESSION['nombre']           = trim((string) $user['nombre']) ?: (string) $user['usuario'];
        $_SESSION['usuario']          = (string) $user['usuario'];
        $_SESSION['rol']              = (string) $user['rol'];
        $_SESSION['ultima_actividad'] = time();

        try {
            $this->model->limpiarFallos($usuario);
            if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
                $this->model->actualizarClave((int) $user['id'], $password);
            }
        } catch (PDOException $e) {
            error_log('Login (limpieza): ' . $e->getMessage());
        }

        redirigir('dashboard/index');
    }

    // --------------------------------------------------------------- REGISTRO
    /** GET /auth/register  (muestra el login con el panel de registro abierto) */
    public function register(): void
    {
        $this->exigirRegistroAbierto();
        $this->mostrarLogin(['mostrarRegistro' => true]);
    }

    /** POST /auth/processRegister  (la cuenta nueva queda como 'administrativo' y 'pendiente') */
    public function processRegister(): void
    {
        $this->exigirRegistroAbierto();

        // Para volver a llenar el formulario si hay un error (nunca la contraseña)
        $old = [];
        foreach (['nombre', 'usuario', 'correo'] as $campo) {
            $old[$campo] = is_string($_POST[$campo] ?? null) ? trim($_POST[$campo]) : '';
        }

        try {
            // Las reglas de la cuenta (formato, contraseña, duplicados) están en UsuarioModel
            $this->model->registrarCuenta($_POST, self::ROL_NUEVOS_USUARIOS, ip_cliente());
            // Redirigir evita que al recargar la página se reenvíe el formulario
            redirigir('auth/login', ['registro' => 'pendiente']);
        } catch (ValidacionException $e) {
            $errorRegistro = $e->getMessage();
        } catch (PDOException $e) {
            error_log('Registro de usuario: ' . $e->getMessage());
            // 23505 = violación de clave única (usuario o correo repetido)
            $errorRegistro = (string) $e->getCode() === '23505'
                ? 'Ese usuario o ese correo ya está registrado.'
                : 'No se pudo crear la cuenta. Inténtalo de nuevo.';
        }

        $this->mostrarLogin(['errorRegistro' => $errorRegistro, 'old' => $old]);
    }

    // ----------------------------------------------------------------- LOGOUT
    /** POST /auth/logout */
    public function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires'  => time() - 42000,
                'path'     => $p['path'],
                'domain'   => $p['domain'],
                'secure'   => $p['secure'],
                'httponly' => $p['httponly'],
                'samesite' => $p['samesite'] ?: 'Strict',
            ]);
        }
        session_destroy();
        redirigir('auth/login');
    }

    // -------------------------------------------------------------- AUXILIARES
    private function mostrarLogin(array $datos = []): void
    {
        View::render('login', $datos + ['registroAbierto' => $this->registroAbierto()]);
    }

    private function registroAbierto(): bool
    {
        // Abierto por defecto: ninguna cuenta nueva entra sin la aprobación de la directiva.
        // Para cerrarlo, poner REGISTRO_ABIERTO=false en .env
        return Env::bool('REGISTRO_ABIERTO', true);
    }

    private function exigirRegistroAbierto(): void
    {
        if (!$this->registroAbierto()) {
            mostrarError(404, '404 - Ruta no encontrada');
        }
    }

    /** ¿Demasiados fallos recientes para este usuario o esta IP? Si la tabla no existe, no bloquea. */
    private function bloqueado(string $usuario, string $ip): bool
    {
        try {
            $f = $this->model->fallosRecientes($usuario, $ip, self::VENTANA_MINUTOS);
            return $f['por_usuario'] >= self::MAX_FALLOS_USUARIO || $f['por_ip'] >= self::MAX_FALLOS_IP;
        } catch (PDOException $e) {
            error_log('Login (bloqueo): ' . $e->getMessage() . ' — ¿falta database/migracion_seguridad.sql?');
            return false;
        }
    }

    private function registrarFallo(string $usuario, string $ip): void
    {
        try {
            $this->model->registrarFallo($usuario, $ip);
        } catch (PDOException $e) {
            error_log('Login (registrar fallo): ' . $e->getMessage());
        }
    }
}
