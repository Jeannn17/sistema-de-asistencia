<?php

namespace App\Core;

/**
 * Protección CSRF: todo formulario POST lleva un token secreto de la sesión.
 * index.php lo verifica ANTES de ejecutar cualquier controlador.
 */
final class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf'];
    }

    public static function campo(): string
    {
        return '<input type="hidden" name="_csrf" value="' . htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8') . '">';
    }

    public static function validar($token): bool
    {
        return !empty($_SESSION['_csrf']) && is_string($token) && hash_equals($_SESSION['_csrf'], $token);
    }

    /** Se llama al iniciar sesión, junto con session_regenerate_id() */
    public static function regenerar(): void
    {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
}
