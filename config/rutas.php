<?php

/**
 * MATRIZ DE RUTAS Y ROLES  (controlador => acción => roles permitidos)
 *
 *   'publico'        cualquiera, sin sesión
 *   'directivo'      puede consultar y además gestionar (trabajadores, turnos, cargos, importar asistencias)
 *   'administrativo' solo consulta (y registra permisos; quita 'administrativo' de permiso/guardar si no debe)
 *
 * Una ruta que NO está aquí devuelve 404. La URL es  /controlador/accion
 */
$todos = ['directivo', 'administrativo'];

return [
    /**
     * DIRECTIVA PRINCIPAL: los únicos usuarios que pueden aprobar cuentas nuevas y cambiar roles
     * (sin distinguir mayúsculas). Deben tener rol 'directivo'. Su rol y su nombre de usuario
     * no se pueden cambiar desde el sistema.
     */
    'directiva_principal' => ['jeanM', 'itaca_32'],

    'rutas' => [
        'auth' => [
            'login'           => ['publico'],
            'authenticate'    => ['publico'],
            'register'        => ['publico'],
            'processRegister' => ['publico'],
            'logout'          => $todos,
        ],
        'dashboard' => [
            'index' => $todos,
        ],
        'trabajador' => [
            'guardar'    => ['directivo'],
            'actualizar' => ['directivo'],
            'estado'     => ['directivo'],
        ],
        'turno' => [
            'guardar' => ['directivo'],
            'estado'  => ['directivo'],
        ],
        'cargo' => [
            'guardar' => ['directivo'],
            'estado'  => ['directivo'],
        ],
        'asistencia' => [
            'importar' => ['directivo'],
        ],
        'permiso' => [
            'guardar' => $todos,
        ],
        'reporte' => [
            'pdf' => $todos,          // descarga del reporte en PDF (solo lectura, por GET)
        ],
        'seccion' => [
            'perfil'   => $todos,          // cada quien edita SU perfil
            // Además del rol, SeccionController exige ser de la directiva principal
            'aprobar'  => ['directivo'],
            'rechazar' => ['directivo'],
            'rol'      => ['directivo'],
        ],
    ],

    // Acciones que cambian datos: solo se aceptan por POST (y con token CSRF válido)
    'solo_post' => [
        'auth/authenticate', 'auth/processRegister', 'auth/logout',
        'trabajador/guardar', 'trabajador/actualizar', 'trabajador/estado',
        'turno/guardar', 'turno/estado',
        'cargo/guardar', 'cargo/estado',
        'asistencia/importar',
        'permiso/guardar',
        'seccion/perfil', 'seccion/aprobar', 'seccion/rechazar', 'seccion/rol',
    ],
];
