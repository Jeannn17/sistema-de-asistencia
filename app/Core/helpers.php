<?php
// Funciones auxiliares globales (se cargan desde public/index.php)

use App\Core\Csrf;

/** URL de una ruta del sistema: url('dashboard/index') */
function url(string $ruta = ''): string
{
    return BASE_URL . ltrim($ruta, '/');
}

/**
 * URL de un archivo público: asset('css/dashboard.css') -> /asset/css/dashboard.css?v=1730190000
 * El ?v= cambia cada vez que se modifica el archivo, así el navegador puede guardarlo en caché
 * mucho tiempo (ver public/.htaccess) y aun así recibe la versión nueva tras una actualización.
 */
function asset(string $ruta): string
{
    $ruta    = ltrim($ruta, '/');
    $version = @filemtime(RAIZ . '/public/asset/' . $ruta);
    return BASE_URL . 'asset/' . $ruta . ($version ? '?v=' . $version : '');
}

/** Escapa texto para mostrarlo en HTML (evita XSS) */
function e($valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

/** Campo oculto con el token CSRF para los formularios POST */
function csrf_campo(): string
{
    return Csrf::campo();
}

/** Nonce de la política de seguridad de contenido, para los <script> en línea */
function nonce(): string
{
    return defined('CSP_NONCE') ? CSP_NONCE : '';
}

/**
 * IP del visitante. Detrás de un proxy inverso (Nginx, Apache, balanceador) REMOTE_ADDR es la IP
 * del proxy: con TRUST_PROXY=true se toma la última IP de X-Forwarded-For, que es la que agregó
 * nuestro proxy (las anteriores las puede inventar el cliente). Sin proxy, dejar TRUST_PROXY=false.
 */
function ip_cliente(): string
{
    $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    if (\App\Core\Env::bool('TRUST_PROXY', false) && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $cadena  = explode(',', (string) $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ultima  = trim((string) end($cadena));
        if (filter_var($ultima, FILTER_VALIDATE_IP)) {
            $ip = $ultima;
        }
    }
    return $ip;
}

/** Redirige a una ruta del sistema y corta la ejecución */
function redirigir(string $ruta, array $parametros = []): void
{
    $destino = url($ruta);
    if ($parametros) {
        $destino .= '?' . http_build_query($parametros);
    }
    header('Location: ' . $destino);
    exit;
}
