<?php
// public/index.php
// Front Controller: ÚNICO punto de entrada del sistema.
// Sesión segura, cabeceras de seguridad, rutas /controlador/accion, roles y CSRF.

use App\Core\Csrf;
use App\Core\Env;
use App\Core\Permisos;

define('RAIZ', dirname(__DIR__));

require RAIZ . '/app/Core/helpers.php';
if (is_file(RAIZ . '/vendor/autoload.php')) {
    require RAIZ . '/vendor/autoload.php';          // PhpSpreadsheet (composer install)
}

// ============================================================
// 1. AUTOLOADER CON NAMESPACES  (App\Controllers\X -> app/Controllers/X.php)
//    En Linux las mayúsculas importan: el archivo debe llamarse igual que la clase.
// ============================================================
spl_autoload_register(function ($clase) {
    $prefijo = 'App\\';
    if (strncmp($prefijo, $clase, strlen($prefijo)) !== 0) {
        return;
    }
    $archivo = RAIZ . '/app/' . str_replace('\\', '/', substr($clase, strlen($prefijo))) . '.php';
    if (is_file($archivo)) {
        require $archivo;
    }
});

// ============================================================
// 2. CONFIGURACIÓN (.env) Y MANEJO DE ERRORES
//    En producción NO se muestran errores al usuario: se guardan en storage/logs
// ============================================================
Env::cargar(RAIZ . '/.env');
$depurar = Env::bool('APP_DEBUG', false);

// Zona horaria del sistema (XAMPP trae Europe/Berlin por defecto): fechas de reportes, permisos, etc.
date_default_timezone_set(Env::get('APP_TIMEZONE', 'America/Caracas'));

error_reporting(E_ALL);
ini_set('display_errors', $depurar ? '1' : '0');
ini_set('log_errors', '1');
// APP_LOG=stderr (en Docker/Render): los errores van al registro del servidor en vez de a un archivo
if (Env::get('APP_LOG', 'archivo') !== 'stderr') {
    ini_set('error_log', RAIZ . '/storage/logs/php-error.log');
}

function mostrarError(int $codigo, string $titulo, string $mensaje = ''): void
{
    http_response_code($codigo);
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>' . e($titulo) . '</title></head>'
        . '<body style="font-family:Arial,sans-serif;text-align:center;margin-top:12vh">'
        . '<h1>' . e($titulo) . '</h1><p>' . e($mensaje) . '</p></body></html>';
    exit;
}

set_exception_handler(function (Throwable $e) use ($depurar) {
    error_log('Excepción no controlada: ' . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine());
    if ($depurar) {
        http_response_code(500);
        echo '<pre>' . htmlspecialchars((string) $e, ENT_QUOTES, 'UTF-8') . '</pre>';
        exit;
    }
    mostrarError(500, '500 - Error interno', 'Ocurrió un problema. Inténtalo de nuevo más tarde.');
});

// ============================================================
// 3. DETECCIÓN AUTOMÁTICA DE BASE_URL
// ============================================================
$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
// En Windows dirname() devuelve "\" (barra invertida): se normaliza DESPUÉS de llamarla
$scriptDir  = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
define('BASE_URL', ($scriptDir === '' || $scriptDir === '/') ? '/' : $scriptDir . '/');

// ============================================================
// 4. CABECERAS DE SEGURIDAD
// ============================================================
$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (Env::bool('TRUST_PROXY', false) && ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

define('CSP_NONCE', base64_encode(random_bytes(16)));

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
header("Content-Security-Policy: default-src 'self'; "
    . "script-src 'self' 'nonce-" . CSP_NONCE . "'; "
    . "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; "
    . "font-src 'self' https://fonts.gstatic.com; "
    . "img-src 'self' data:; media-src 'self'; connect-src 'self'; "
    . "object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'");
if ($https && Env::get('APP_ENV', 'production') === 'production') {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}
header_remove('X-Powered-By');

// ============================================================
// 5. SESIÓN SEGURA
// ============================================================
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
session_name('ASISTENCIA_SID');
session_set_cookie_params([
    'lifetime' => 0,               // la cookie muere al cerrar el navegador
    'path'     => '/',
    'domain'   => '',
    'secure'   => $https,          // en HTTPS la cookie solo viaja cifrada
    'httponly' => true,            // JavaScript no puede leerla
    'samesite' => 'Strict',
]);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cierre automático por inactividad
if (isset($_SESSION['usuario_id'])) {
    $limite = max(1, Env::int('SESSION_IDLE_MINUTES', 30)) * 60;
    if (time() - ($_SESSION['ultima_actividad'] ?? time()) > $limite) {
        $_SESSION = [];
        session_destroy();
        redirigir('auth/login', ['expirada' => 1]);
    }
    $_SESSION['ultima_actividad'] = time();

    // La sesión se sincroniza con la base de datos en cada petición: si la directiva cambió
    // el rol, el cambio aplica de inmediato; si la cuenta ya no existe o no está aprobada, se cierra.
    $cuenta = (new App\Models\UsuarioModel(App\Core\Database::conectar()))->buscarPorId((int) $_SESSION['usuario_id']);
    if (!$cuenta || $cuenta['estado'] !== App\Models\UsuarioModel::ESTADO_APROBADO) {
        $_SESSION = [];
        session_destroy();
        redirigir('auth/login');
    }
    $_SESSION['rol']     = (string) $cuenta['rol'];
    $_SESSION['usuario'] = (string) $cuenta['usuario'];
    $_SESSION['nombre']  = trim((string) $cuenta['nombre']) ?: (string) $cuenta['usuario'];
}

// ============================================================
// 6. OBTENCIÓN Y LIMPIEZA DE LA URI
// ============================================================
$uriSolicitada  = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
$uriDecodificada = urldecode($uriSolicitada);

$rutaRelativa = (BASE_URL !== '/' && strncmp($uriDecodificada, BASE_URL, strlen(BASE_URL)) === 0)
    ? substr($uriDecodificada, strlen(BASE_URL))
    : $uriDecodificada;
$rutaRelativa = '/' . ltrim($rutaRelativa, '/');

// Prevención de path traversal
if (strpos($rutaRelativa, '../') !== false || strpos($rutaRelativa, '..\\') !== false) {
    mostrarError(400, '400 - Solicitud inválida');
}

// ============================================================
// 7. SEGMENTOS: /controlador/accion/parametros
// ============================================================
$uriLimpia  = trim($rutaRelativa, '/');
if ($uriLimpia === 'index.php') {
    $uriLimpia = '';                       // /index.php equivale a la raíz
}
$segmentos  = $uriLimpia !== '' ? explode('/', $uriLimpia) : [];
$controllerR = $segmentos[0] ?? 'auth';
$actionR     = $segmentos[1] ?? 'login';
$parametrosRuta = array_slice($segmentos, 2);

$controller = ctype_alnum($controllerR) ? $controllerR : null;
$action     = ctype_alnum($actionR) ? $actionR : null;
if ($controller === null || $action === null) {
    mostrarError(400, '400 - Solicitud inválida');
}

// ============================================================
// 8. MATRIZ DE RUTAS Y ROLES (config/rutas.php)
// ============================================================
$config = Permisos::config();
$rolesPermitidos = $config['rutas'][$controller][$action] ?? null;
if ($rolesPermitidos === null) {
    mostrarError(404, '404 - Ruta no encontrada');
}

// ============================================================
// 9. MIDDLEWARE DE AUTENTICACIÓN Y AUTORIZACIÓN
// ============================================================
$esRutaPublica = in_array('publico', $rolesPermitidos, true);

if (!$esRutaPublica && !isset($_SESSION['usuario_id'])) {
    redirigir('auth/login');
}

if (!$esRutaPublica && !in_array(Permisos::rol(), $rolesPermitidos, true)) {
    // Sesión válida pero rol insuficiente: vuelve al panel con un aviso
    redirigir('dashboard/index', ['status' => 'sin_permiso']);
}

// Ruta pública con sesión iniciada: se envía al panel
if ($esRutaPublica && isset($_SESSION['usuario_id'])) {
    redirigir('dashboard/index');
}

// ============================================================
// 10. MÉTODO HTTP Y TOKEN CSRF
// ============================================================
$metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if (in_array($controller . '/' . $action, $config['solo_post'], true) && $metodo !== 'POST') {
    header('Allow: POST');
    mostrarError(405, '405 - Método no permitido');
}

if ($metodo === 'POST' && !Csrf::validar($_POST['_csrf'] ?? null)) {
    mostrarError(419, 'La página expiró', 'Recarga la página e inténtalo de nuevo.');
}

// ============================================================
// 11. INYECCIÓN DE PARÁMETROS EN $_GET (/controlador/accion/ID)
// ============================================================
foreach ($parametrosRuta as $indice => $valor) {
    $_GET[$indice === 0 ? 'id' : 'param' . ($indice + 1)] = htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
}

// ============================================================
// 12. CARGA Y EJECUCIÓN DEL CONTROLADOR
// ============================================================
$nombreClaseControlador = 'App\\Controllers\\' . ucfirst($controller) . 'Controller';

if (!class_exists($nombreClaseControlador)) {
    error_log('Falta el controlador: ' . $nombreClaseControlador);
    mostrarError(500, '500 - Error interno');
}

$instanciaControlador = new $nombreClaseControlador();

if (!is_callable([$instanciaControlador, $action])) {
    error_log("El método {$action} no existe en {$nombreClaseControlador}");
    mostrarError(500, '500 - Error interno');
}

$instanciaControlador->$action();
