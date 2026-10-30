<?php

namespace App\Controllers;

use App\Core\Database;
use App\Core\Flash;
use App\Core\Permisos;
use App\Core\View;
use App\Models\CatalogosModel;
use App\Models\ConsultaModel;
use App\Models\DashboardModel;
use App\Models\JustificacionModel;
use App\Models\PersonalModel;
use App\Models\ReporteModel;
use App\Models\UsuarioModel;
use PDO;
use PDOException;

class DashboardController
{
    /**
     * Secciones del panel, en el orden del menú.
     *   parcial:   archivo en app/Views/dashboard/
     *   id:        id del <div> que usa viewsSwitcher.js
     *   capacidad: la que exige Permisos::puede() (null = cualquier usuario con sesión)
     *   datos:     método de esta clase que carga lo que la sección necesita
     */
    private const SECCIONES = [
        'inicio'        => ['titulo' => 'Inicio',                 'parcial' => 'inicio',        'id' => 'vistaInicio',       'grupo' => 'principal', 'capacidad' => null,                  'datos' => null],
        'trabajadores'  => ['titulo' => 'Trabajadores',           'parcial' => 'trabajadores',  'id' => 'vistaTrabajadores', 'grupo' => 'principal', 'capacidad' => null,                  'datos' => 'datosPersonal'],
        'turnos'        => ['titulo' => 'Turnos',                 'parcial' => 'turnos',        'id' => 'vistaTurnos',       'grupo' => 'principal', 'capacidad' => null,                  'datos' => 'datosCatalogos'],
        'cargos'        => ['titulo' => 'Cargos',                 'parcial' => 'cargos',        'id' => 'vistaCargos',       'grupo' => 'principal', 'capacidad' => null,                  'datos' => 'datosCatalogos'],
        'asistencias'   => ['titulo' => 'Asistencias',            'parcial' => 'asistencias',   'id' => 'vistaAsistencias',  'grupo' => 'principal', 'capacidad' => null,                  'datos' => 'datosAsistencias'],
        'importar'      => ['titulo' => 'Importar asistencia',    'parcial' => 'importar',      'id' => 'vistaImportar',     'grupo' => 'principal', 'capacidad' => 'importar_asistencia', 'datos' => null],
        'permisos'      => ['titulo' => 'Permisos',               'parcial' => 'permisos',      'id' => 'vistaPermisos',     'grupo' => 'principal', 'capacidad' => null,                  'datos' => 'datosPermisos'],
        'reportes'      => ['titulo' => 'Reportes',               'parcial' => 'reportes',      'id' => 'vistaReportes',     'grupo' => 'principal', 'capacidad' => null,                  'datos' => 'datosReportes'],
        'seccion'       => ['titulo' => 'Sección',                'parcial' => 'seccion',       'id' => 'vistaSeccion',      'grupo' => 'gestion',   'capacidad' => null,                  'datos' => 'datosSeccion'],
        'configuracion' => ['titulo' => 'Configuración',          'parcial' => 'configuracion', 'id' => 'vistaConfig',       'grupo' => 'gestion',   'capacidad' => null,                  'datos' => null],
    ];

    private PDO $db;

    /** Datos que se van juntando para la vista */
    private array $datos = [];

    public function __construct()
    {
        $this->db = Database::conectar();
    }

    /**
     * GET /dashboard/index[/<seccion>]   o   /dashboard/index?vista=<seccion>
     * (?status=... lo usa roles.js para mostrar el aviso)
     */
    public function index(): void
    {
        $secciones = $this->seccionesPermitidas();
        $pedida    = (string) ($_GET['vista'] ?? $_GET['id'] ?? 'inicio');
        $activa    = isset($secciones[$pedida]) ? $pedida : 'inicio';

        $this->datos = [
            'secciones'      => $secciones,
            'seccionActiva'  => $activa,
            'resumen'        => (new DashboardModel($this->db))->obtenerResumen(),   // inicio y menú
            'turnos'         => [],    // window.TURNOS del formulario de registro
            'errorMigracion' => false,
            'flash'          => Flash::tomar(),   // aviso detallado que dejó el último formulario
        ];

        // Cada grupo de datos se carga una sola vez, aunque lo usen varias secciones
        $cargados = [];
        foreach ($secciones as $s) {
            $metodo = $s['datos'];
            if ($metodo !== null && !isset($cargados[$metodo])) {
                $this->$metodo($activa);
                $cargados[$metodo] = true;
            }
        }

        View::render('dashboard', $this->datos);
    }

    /** Las secciones que el rol de la sesión puede ver. */
    private function seccionesPermitidas(): array
    {
        return array_filter(
            self::SECCIONES,
            fn(array $s) => $s['capacidad'] === null || Permisos::puede($s['capacidad'])
        );
    }

    // ------------------------------------------------- DATOS DE CADA SECCIÓN
    // Cada bloque va en su propio try/catch para que un fallo no tumbe todo el panel

    private function datosPersonal(): void
    {
        $this->cargar('trabajadores', function () {
            $personal = new PersonalModel($this->db);
            return [
                'listadoPersonal' => $personal->all(),
                'cargosActivos'   => $personal->getCargos(),
                'turnos'          => $personal->getTurnos(),
            ];
        });
        $this->datosCatalogos();   // la ventana de edición usa los listados de cargos y turnos
    }

    private function datosCatalogos(): void
    {
        if (isset($this->datos['listadoTurnos'])) {
            return;
        }
        $this->cargar('catálogos', function () {
            $catalogos = new CatalogosModel($this->db);
            return ['listadoTurnos' => $catalogos->listarTurnos(), 'listadoCargos' => $catalogos->listarCargos()];
        });
    }

    /** La consulta (pesada) solo se ejecuta cuando la sección Asistencias es la que se abre. */
    private function datosAsistencias(string $activa): void
    {
        $this->cargar('asistencias', function () use ($activa) {
            $consulta   = new ConsultaModel($this->db);
            $filtros    = $consulta->normalizarFiltros($_GET);
            $consultada = $activa === 'asistencias';
            return [
                'filtrosAsistencia'      => $filtros,
                'asistenciasConsultadas' => $consultada,
                'asistencias'            => $consultada ? $consulta->asistencias($filtros) : [],
            ];
        });
    }

    private function datosPermisos(): void
    {
        $justificaciones = new JustificacionModel($this->db);
        $this->cargar('personal para permisos', fn() => ['personalPermisos' => $justificaciones->obtenerPersonalActivo()]);
        $this->cargar('permisos recientes', fn() => ['permisosRecientes' => $justificaciones->listarRecientes()]);
    }

    private function datosReportes(): void
    {
        $this->cargar('reportes', fn() => [
            'departamentos' => (new ReporteModel($this->db))->departamentos(),
            'frecuencias'   => ReporteModel::FRECUENCIAS,
        ]);
    }

    /** Perfil propio y, para la directiva principal, solicitudes pendientes y cuentas aprobadas. */
    private function datosSeccion(): void
    {
        $this->cargar('sección', function () {
            $usuarios = new UsuarioModel($this->db);
            $datos = [
                'perfil'              => $usuarios->buscarPorId((int) $_SESSION['usuario_id']) ?: [],
                'esDirectivaPrincipal' => Permisos::esDirectivaPrincipal(),
                'directivaPrincipal'  => Permisos::directivaPrincipal(),
            ];
            if ($datos['esDirectivaPrincipal']) {
                $datos['cuentasPendientes'] = $usuarios->listarPendientes();
                $datos['cuentasAprobadas']  = $usuarios->listarAprobados();
            }
            return $datos;
        });
    }

    /** Ejecuta $consulta y mezcla su resultado en los datos; si falla, lo registra y sigue. */
    private function cargar(string $que, callable $consulta): void
    {
        try {
            $this->datos = $consulta() + $this->datos;
        } catch (PDOException $e) {
            // Casi siempre: falta ejecutar alguna migración de database/
            error_log("Dashboard ({$que}): " . $e->getMessage());
            $this->datos['errorMigracion'] = true;
        }
    }
}
