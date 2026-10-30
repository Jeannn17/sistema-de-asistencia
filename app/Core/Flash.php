<?php

namespace App\Core;

/**
 * Mensaje de "un solo uso" guardado en la sesión: el controlador lo deja antes de redirigir
 * y la vista lo muestra (y lo borra) en la siguiente página. Así el aviso puede explicar
 * el error exacto sin meter textos en la URL.
 */
final class Flash
{
    public static function exito(string $mensaje): void
    {
        $_SESSION['_flash'] = ['tipo' => 'exito', 'mensaje' => $mensaje];
    }

    public static function error(string $mensaje): void
    {
        $_SESSION['_flash'] = ['tipo' => 'error', 'mensaje' => $mensaje];
    }

    /** @return array{tipo:string, mensaje:string}|null */
    public static function tomar(): ?array
    {
        $flash = $_SESSION['_flash'] ?? null;
        unset($_SESSION['_flash']);
        return is_array($flash) ? $flash : null;
    }
}
