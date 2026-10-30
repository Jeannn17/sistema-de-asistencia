<?php

namespace App\Core;

use InvalidArgumentException;

/**
 * La lanza un MODELO cuando los datos no cumplen una regla de negocio.
 * El mensaje ya viene redactado para mostrárselo al usuario, y el "motivo"
 * le dice al controlador qué aviso (status) corresponde: invalido o duplicado.
 */
final class ValidacionException extends InvalidArgumentException
{
    public const INVALIDO  = 'invalido';
    public const DUPLICADO = 'duplicado';

    private string $motivo;

    public function __construct(string $mensaje, string $motivo = self::INVALIDO)
    {
        parent::__construct($mensaje);
        $this->motivo = $motivo;
    }

    public static function duplicado(string $mensaje): self
    {
        return new self($mensaje, self::DUPLICADO);
    }

    public function motivo(): string
    {
        return $this->motivo;
    }

    /** El mismo error con un prefijo, ej. "Fila 3: ..." */
    public function conPrefijo(string $prefijo): self
    {
        return new self($prefijo . $this->getMessage(), $this->motivo);
    }
}
