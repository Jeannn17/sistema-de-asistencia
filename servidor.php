<?php
/**
 * Router SOLO PARA DESARROLLO con el servidor integrado de PHP (lo usa iniciar.bat):
 *   php -S localhost:8000 -t public servidor.php
 * Hace lo mismo que public/.htaccess en Apache: si se pide un archivo real de public/
 * (css, js, imágenes) lo entrega tal cual; todo lo demás pasa por el front controller.
 */
$ruta    = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/');
$archivo = __DIR__ . '/public' . $ruta;

if ($ruta !== '/' && strpos($ruta, '..') === false && is_file($archivo) && substr($archivo, -4) !== '.php') {
    return false;   // el servidor integrado entrega el archivo estático
}

$_SERVER['SCRIPT_NAME'] = '/index.php';   // así BASE_URL queda en "/"
require __DIR__ . '/public/index.php';
