<?php
// 1. Iniciar sesión obligatoriamente al inicio de todo
session_start();

// 2. Incluir la conexión a la base de datos (PostgreSQL/MySQL) y los controladores
// Ajusta las rutas según la ubicación real de tus carpetas
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/permisos.php';
$db = database::conect();
require_once __DIR__ . '/app/models/registro.php';
require_once __DIR__ . '/app/models/asistenciaModel.php';
require_once __DIR__ . '/app/models/usuarioModel.php';
require_once __DIR__ . '/app/models/justificacionModel.php';
require_once __DIR__ . '/app/models/dashboardModel.php';
require_once __DIR__ . '/app/models/catalogosModel.php';
require_once __DIR__ . '/app/models/consultaModel.php';
require_once __DIR__ . '/app/controllers/registroController.php';
require_once __DIR__ . '/app/controllers/asistenciaController.php';
require_once __DIR__ . '/app/controllers/permisosController.php';
require_once __DIR__ . '/app/controllers/catalogosController.php';
require_once __DIR__ . '/app/controllers/loginController.php';


// 3. Obtener la acción solicitada por URL (por defecto carga el login)
$action = isset($_GET['action']) ? $_GET['action'] : 'login';

// 4. Definir las páginas públicas que NO requieren sesión previa
$rutasPublicas = ['login', 'autenticar', 'registrar'];

// 5. Control de Acceso (Seguridad): Si no ha iniciado sesión y la ruta no es pública, se devuelve al login
if (!isset($_SESSION['usuario']) && !in_array($action, $rutasPublicas)) {
    header('Location: index.php?action=login');
    exit();
}

// 5.1 Control por rol: si la acción exige una capacidad que el rol no tiene, se devuelve al panel
//     (las reglas están en config/permisos.php)
if (!Permisos::puedeAccion($action)) {
    header('Location: index.php?action=dashboard&status=sin_permiso');
    exit();
}

// 6. Enrutador principal (Front Controller)
switch ($action) {
    case 'login':
        $loginController= new loginController($db);
        $loginController->mostrarLogin();
        break;

    case 'autenticar':
        $loginController= new loginController($db);
        $loginController->procesarLogin();
        break;

    case 'registrar':
        // Registro de una cuenta nueva (siempre con rol 'administrativo')
        $loginController= new loginController($db);
        $loginController->registrar();
        break;

    case 'logout':
        $loginController= new loginController($db);
        $loginController->cerrarSesion();
        break;

    case 'dashboard':
        // Vista principal del sistema tras iniciar sesión.
        // Los turnos se cargan desde la BD para el formulario de registro de personal.
        $turnos = $listadoPersonal = $listadoTurnos = $listadoCargos = $cargosActivos = $asistencias = [];
        $filtrosAsistencia = ['q' => '', 'desde' => '', 'hasta' => ''];
        $errorMigracion = false;
        try {
            $registroModel     = new Registro($db);
            $catalogos         = new catalogosModel($db);
            $consulta          = new consultaModel($db);
            $turnos            = $registroModel->getTurnos();
            $cargosActivos     = $registroModel->getCargos();
            $listadoPersonal   = $registroModel->all();
            $listadoTurnos     = $catalogos->listarTurnos();
            $listadoCargos     = $catalogos->listarCargos();
            $filtrosAsistencia = $consulta->normalizarFiltros($_GET);
            if (($_GET['vista'] ?? '') === 'asistencias') {
                $asistencias = $consulta->asistencias($filtrosAsistencia);
            }
        } catch (PDOException $e) {
            // Casi siempre: falta ejecutar database/migracion_roles.sql
            error_log('Dashboard: ' . $e->getMessage());
            $errorMigracion = true;
        }
        // Datos del módulo Permisos (personal activo y últimas justificaciones)
        $datosPermisos    = (new permisosController($db))->datosVista();
        $personalPermisos = $datosPermisos['personal'];
        $permisosRecientes = $datosPermisos['recientes'];
        // Resumen real para la pantalla de Inicio
        $resumen = (new dashboardModel($db))->obtenerResumen();
        include __DIR__ . '/app/views/dashboard.php';
        break;

    case 'asistencia':
        // Módulo de control de asistencia y reportes
        $asistenciaController= new asistenciaController($db);
        $asistenciaController->importar();
        
        break;

    case 'permiso_guardar':
        // Guarda un permiso / justificación (reposo, accidente, salida temprana, etc.)
        $permisosController = new permisosController($db);
        $permisosController->guardar();
        break;

    case 'personal':
        // La consulta de trabajadores ahora es una sección del dashboard
        header('Location: index.php?action=dashboard&vista=trabajadores');
        exit();

    case 'store':
        $registrocontroller= new registroController($db);
        $registrocontroller->store();
        break;

    case 'update':
        $registrocontroller= new registroController($db);
        $registrocontroller->update();
        break;

    case 'turno_guardar':
        (new catalogosController($db))->turnoGuardar();
        break;

    case 'turno_estado':
        (new catalogosController($db))->turnoEstado();
        break;

    case 'cargo_guardar':
        (new catalogosController($db))->cargoGuardar();
        break;

    case 'cargo_estado':
        (new catalogosController($db))->cargoEstado();
        break;

    default:
        // Acción desconocida: se muestra un 404 en vez de una pantalla en blanco
        http_response_code(404);
        echo '<h1>404 - Página no encontrada</h1>';
        echo '<p><a href="index.php?action=dashboard">Volver al panel</a></p>';
        break;
}
?>