<?php

/**
 * Permisos por rol del sistema.
 *
 * Todos los usuarios con sesión iniciada pueden CONSULTAR: asistencias, trabajadores, turnos y cargos.
 * Lo demás se controla con "capacidades". Para cambiar lo que puede hacer un rol,
 * solo hay que editar la lista CAPACIDADES de abajo.
 */
class Permisos
{
    public const DIRECTIVO      = 'directivo';
    public const ADMINISTRATIVO = 'administrativo';

    private const CAPACIDADES = [
        self::DIRECTIVO => [
            'gestionar_personal',    // registrar y modificar trabajadores
            'gestionar_turnos',      // crear, modificar y activar/desactivar turnos
            'gestionar_cargos',      // crear, modificar y activar/desactivar cargos
            'importar_asistencia',   // importar el Excel del biométrico
            'registrar_permisos',    // registrar permisos / justificaciones
        ],
        self::ADMINISTRATIVO => [
        ],
    ];

    /** Acción del router => capacidad que exige. Las acciones que no están aquí solo piden sesión. */
    private const ACCIONES = [
        'store'           => 'gestionar_personal',
        'update'          => 'gestionar_personal',
        'turno_guardar'   => 'gestionar_turnos',
        'turno_estado'    => 'gestionar_turnos',
        'cargo_guardar'   => 'gestionar_cargos',
        'cargo_estado'    => 'gestionar_cargos',
        'asistencia'      => 'importar_asistencia',
        'permiso_guardar' => 'registrar_permisos',
    ];

    /** Rol de la sesión. Si el rol no se reconoce, se trata como administrativo (el de menos permisos). */
    public static function rol(): string
    {
        $rol = strtolower(trim((string) ($_SESSION['rol'] ?? '')));
        return isset(self::CAPACIDADES[$rol]) ? $rol : self::ADMINISTRATIVO;
    }

    public static function puede(string $capacidad): bool
    {
        return in_array($capacidad, self::CAPACIDADES[self::rol()], true);
    }

    public static function puedeAccion(string $accion): bool
    {
        $capacidad = self::ACCIONES[$accion] ?? null;
        return $capacidad === null || self::puede($capacidad);
    }
}
