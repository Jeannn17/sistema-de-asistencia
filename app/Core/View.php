<?php

namespace App\Core;

final class View
{
    /** Muestra app/Views/<vista>.php con las variables recibidas. */
    public static function render(string $vista, array $datos = []): void
    {
        extract($datos, EXTR_SKIP);
        require dirname(__DIR__) . '/Views/' . $vista . '.php';
    }

    /** Igual que render(), pero devuelve el HTML en vez de mostrarlo (ej. para convertirlo en PDF). */
    public static function capturar(string $vista, array $datos = []): string
    {
        ob_start();
        try {
            self::render($vista, $datos);
            return (string) ob_get_contents();
        } finally {
            ob_end_clean();
        }
    }
}
