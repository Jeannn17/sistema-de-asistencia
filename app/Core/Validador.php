<?php

namespace App\Core;

use DateTime;

/**
 * Reglas de formato reutilizables por los modelos.
 * Cada método recibe el valor CRUDO (tal como llega del formulario) y devuelve el valor
 * limpio y con el tipo correcto, o lanza ValidacionException con un mensaje claro.
 * $campo es el nombre que verá el usuario en el mensaje ("la fecha de ingreso", "el turno"...).
 */
final class Validador
{
    /** Texto libre: se recortan los espacios de los extremos. Vacío y opcional -> null. */
    public static function texto($valor, string $campo, int $max, bool $obligatorio = true): ?string
    {
        $valor = self::cadena($valor, $campo);
        if ($valor === '') {
            if ($obligatorio) {
                throw new ValidacionException(self::mayuscula($campo) . ' es obligatorio.');
            }
            return null;
        }
        if (mb_strlen($valor) > $max) {
            throw new ValidacionException(self::mayuscula($campo) . " admite máximo {$max} caracteres.");
        }
        return $valor;
    }

    /** Nombre o apellido: letras (con acentos), espacios, apóstrofo, punto y guion. */
    public static function nombrePropio($valor, string $campo, int $max = 100): string
    {
        $valor = preg_replace('/\s+/u', ' ', self::texto($valor, $campo, $max));
        if (!preg_match("/^\p{L}[\p{L} '.-]*$/u", $valor)) {
            throw new ValidacionException(self::mayuscula($campo) . ' solo puede contener letras.');
        }
        return $valor;
    }

    /** Entero dentro de un rango. Vacío y opcional -> null. */
    public static function entero($valor, string $campo, bool $obligatorio = true, int $min = 1, int $max = 2147483647): ?int
    {
        $valor = self::cadena($valor, $campo);
        if ($valor === '') {
            if ($obligatorio) {
                throw new ValidacionException(self::mayuscula($campo) . ' es obligatorio.');
            }
            return null;
        }
        $numero = filter_var($valor, FILTER_VALIDATE_INT, ['options' => ['min_range' => $min, 'max_range' => $max]]);
        if ($numero === false) {
            throw new ValidacionException(self::mayuscula($campo) . " debe ser un número entero entre {$min} y {$max}.");
        }
        return $numero;
    }

    /** Id de un registro seleccionado en una lista (cargo, turno, trabajador...). */
    public static function id($valor, string $campo): int
    {
        $id = filter_var(self::cadena($valor, $campo), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($id === false) {
            throw new ValidacionException('Selecciona ' . $campo . '.');
        }
        return $id;
    }

    /** 'YYYY-MM-DD' (formato del <input type="date">). Vacío y opcional -> null. */
    public static function fecha($valor, string $campo, bool $obligatorio = true): ?string
    {
        $valor = self::cadena($valor, $campo);
        if ($valor === '') {
            if ($obligatorio) {
                throw new ValidacionException(self::mayuscula($campo) . ' es obligatoria.');
            }
            return null;
        }
        $d = DateTime::createFromFormat('!Y-m-d', $valor);
        if (!$d || $d->format('Y-m-d') !== $valor || (int) $d->format('Y') < 1900) {
            throw new ValidacionException(self::mayuscula($campo) . ' no es una fecha válida.');
        }
        return $valor;
    }

    /** 'HH:MM' o 'HH:MM:SS' -> 'HH:MM:00'. Vacío y opcional -> null. */
    public static function hora($valor, string $campo, bool $obligatorio = false): ?string
    {
        $valor = self::cadena($valor, $campo);
        if ($valor === '') {
            if ($obligatorio) {
                throw new ValidacionException(self::mayuscula($campo) . ' es obligatoria.');
            }
            return null;
        }
        if (!preg_match('/^([01]\d|2[0-3]):([0-5]\d)(:[0-5]\d)?$/', $valor, $m)) {
            throw new ValidacionException(self::mayuscula($campo) . ' no es una hora válida (HH:MM).');
        }
        return $m[1] . ':' . $m[2] . ':00';
    }

    /**
     * Cédula venezolana. Acepta "V-12.345.678", "e 1234567" o "12345678"
     * y la guarda solo con los dígitos (como están las cédulas ya registradas).
     */
    public static function cedula($valor): string
    {
        $valor = strtoupper(self::cadena($valor, 'la cédula'));
        if ($valor === '') {
            throw new ValidacionException('La cédula es obligatoria.');
        }
        $limpia = str_replace(['.', ' ', '-'], '', preg_replace('/^[VE]\s*-?\s*/', '', $valor));
        if (!preg_match('/^\d{6,10}$/', $limpia)) {
            throw new ValidacionException('La cédula "' . $valor . '" no es válida: debe tener entre 6 y 10 dígitos.');
        }
        return $limpia;
    }

    public static function correo($valor): string
    {
        $valor = self::texto($valor, 'el correo electrónico', 120);
        if (!filter_var($valor, FILTER_VALIDATE_EMAIL)) {
            throw new ValidacionException('Escribe un correo electrónico válido.');
        }
        return mb_strtolower($valor);
    }

    /** El valor debe ser una de las opciones permitidas (ej. un tipo de permiso). */
    public static function opcion($valor, array $permitidas, string $campo): string
    {
        $valor = self::cadena($valor, $campo);
        if (!in_array($valor, $permitidas, true)) {
            throw new ValidacionException('Selecciona ' . $campo . ' válido.');
        }
        return $valor;
    }

    // ---------------------------------------------------------------- AUXILIARES
    /** Solo se aceptan valores simples en UTF-8 válido (un arreglo en el POST no es texto). */
    private static function cadena($valor, string $campo): string
    {
        if ($valor === null) {
            return '';
        }
        if (!is_scalar($valor) || !mb_check_encoding((string) $valor, 'UTF-8')) {
            throw new ValidacionException(self::mayuscula($campo) . ' tiene un formato inválido.');
        }
        return trim((string) $valor);
    }

    private static function mayuscula(string $texto): string
    {
        return mb_strtoupper(mb_substr($texto, 0, 1)) . mb_substr($texto, 1);
    }
}
