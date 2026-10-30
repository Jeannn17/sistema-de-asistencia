<?php

namespace App\Core;

/**
 * Lee las variables del archivo .env (que está FUERA de la carpeta pública).
 * Así las claves de la base de datos no viven dentro del código.
 */
final class Env
{
    private static array $valores = [];

    public static function cargar(string $archivo): void
    {
        if (!is_file($archivo) || !is_readable($archivo)) {
            return;
        }
        foreach (file($archivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $linea) {
            $linea = trim($linea);
            if ($linea === '' || $linea[0] === '#' || strpos($linea, '=') === false) {
                continue;
            }
            [$clave, $valor] = explode('=', $linea, 2);
            $clave = trim($clave);
            $valor = trim($valor);
            $n = strlen($valor);
            if ($n >= 2 && (($valor[0] === '"' && $valor[$n - 1] === '"') || ($valor[0] === "'" && $valor[$n - 1] === "'"))) {
                $valor = substr($valor, 1, -1);
            }
            self::$valores[$clave] = $valor;
        }
    }

    public static function get(string $clave, ?string $porDefecto = null): ?string
    {
        if (array_key_exists($clave, self::$valores)) {
            return self::$valores[$clave];
        }
        $del_sistema = getenv($clave);
        return $del_sistema !== false ? $del_sistema : $porDefecto;
    }

    public static function bool(string $clave, bool $porDefecto = false): bool
    {
        $v = self::get($clave);
        if ($v === null) {
            return $porDefecto;
        }
        return in_array(strtolower($v), ['1', 'true', 'si', 'sí', 'yes', 'on'], true);
    }

    public static function int(string $clave, int $porDefecto = 0): int
    {
        $v = self::get($clave);
        return ($v !== null && is_numeric($v)) ? (int) $v : $porDefecto;
    }
}
