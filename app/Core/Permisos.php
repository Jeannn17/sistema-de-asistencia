<?php

namespace App\Core;

/**
 * Permisos por rol. La ÚNICA fuente de verdad es la matriz de config/rutas.php
 * (la misma que usa el front controller para dejar o no entrar a cada ruta).
 * Aquí solo se traducen a "capacidades" para mostrar u ocultar botones en las vistas.
 */
final class Permisos
{
    public const DIRECTIVO      = 'directivo';
    public const ADMINISTRATIVO = 'administrativo';

    /** capacidad => [controlador, acción] de config/rutas.php */
    private const CAPACIDADES = [
        'gestionar_personal'  => ['trabajador', 'guardar'],
        'gestionar_turnos'    => ['turno', 'guardar'],
        'gestionar_cargos'    => ['cargo', 'guardar'],
        'importar_asistencia' => ['asistencia', 'importar'],
        'registrar_permisos'  => ['permiso', 'guardar'],
    ];

    private static ?array $config = null;

    public static function config(): array
    {
        return self::$config ??= require dirname(__DIR__, 2) . '/config/rutas.php';
    }

    /** Rol de la sesión. Si no se reconoce, se trata como administrativo (el de menos permisos). */
    public static function rol(): string
    {
        $rol = strtolower(trim((string) ($_SESSION['rol'] ?? '')));
        return in_array($rol, [self::DIRECTIVO, self::ADMINISTRATIVO], true) ? $rol : self::ADMINISTRATIVO;
    }

    public static function puedeRuta(string $controlador, string $accion): bool
    {
        $roles = self::config()['rutas'][$controlador][$accion] ?? null;
        return $roles !== null && in_array(self::rol(), $roles, true);
    }

    /** Usuarios de la directiva principal (config/rutas.php). */
    public static function directivaPrincipal(): array
    {
        return self::config()['directiva_principal'] ?? [];
    }

    /**
     * ¿La sesión es de la directiva principal? (aprobar cuentas y cambiar roles)
     * Debe cumplir las dos cosas: tener rol directivo Y estar en la lista.
     */
    public static function esDirectivaPrincipal(): bool
    {
        $usuario = strtolower((string) ($_SESSION['usuario'] ?? ''));
        return $usuario !== ''
            && self::rol() === self::DIRECTIVO
            && in_array($usuario, array_map('strtolower', self::directivaPrincipal()), true);
    }

    public static function puede(string $capacidad): bool
    {
        if ($capacidad === 'aprobar_usuarios') {
            return self::esDirectivaPrincipal() && self::puedeRuta('seccion', 'aprobar');
        }
        if (!isset(self::CAPACIDADES[$capacidad])) {
            return false;
        }
        [$controlador, $accion] = self::CAPACIDADES[$capacidad];
        return self::puedeRuta($controlador, $accion);
    }
}
