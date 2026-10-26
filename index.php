<?php
// 1. Iniciar sesión obligatoriamente al inicio de todo
session_start();

// 2. Incluir la conexión a la base de datos (PostgreSQL/MySQL) y los controladores
// Ajusta las rutas según la ubicación real de tus carpetas
require_once __DIR__ . '/config/database.php';
$db = database::conect();
require_once __DIR__ . '/app/models/registro.php';
require_once __DIR__ . '/app/models/asistenciaModel.php';
require_once __DIR__ . '/app/models/usuarioModel.php';
require_once __DIR__ . '/app/models/justificacionModel.php';
require_once __DIR__ . '/app/models/dashboardModel.php';
require_once __DIR__ . '/app/controllers/registroController.php';
require_once __DIR__ . '/app/controllers/asistenciaController.php';
require_once __DIR__ . '/app/controllers/permisosController.php';
require_once __DIR__ . '/app/controllers/loginController.php';


// 3. Obtener la acción solicitada por URL (por defecto carga el login)
$action = isset($_GET['action']) ? $_GET['action'] : 'home';

// 4. Definir las páginas públicas que NO requieren sesión previa
$rutasPublicas = ['home', 'login', 'autenticar'];

// 5. Control de Acceso (Seguridad): Si no ha iniciado sesión y la ruta no es pública, se devuelve al login
if (!isset($_SESSION['usuario']) && !in_array($action, $rutasPublicas)) {
    header('Location: index.php?action=login');
    exit();
}

// 6. Enrutador principal (Front Controller)
switch ($action) {
    case 'home':
        // Carga la vista principal de bienvenida o inicio
        include __DIR__ . '/app/views/home.php';
        break;

    case 'login':
        $loginController= new loginController($db);
        $loginController->mostrarLogin();
        break;

    case 'autenticar':
        $loginController= new loginController($db);
        $loginController->procesarLogin();
        break;

    case 'logout':
        $loginController= new loginController($db);
        $loginController->cerrarSesion();
        break;

    case 'dashboard':
        // Vista principal del sistema tras iniciar sesión.
        // Los turnos se cargan desde la BD para el formulario de registro de personal.
        $turnos = (new Registro($db))->getTurnos();
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
        // Módulo CRUD de personal / empleados de la institución
        $personalController= new registroController($db);
        $personalController->index();
        break;

    case 'create':
        $registrocontroller= new registroController($db);
        $registrocontroller->create();
        break;

    case 'store':
        $registrocontroller= new registroController($db);
        $registrocontroller->store();
        break;

    case 'editViews':
        $registrocontroller= new registroController($db);
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $registrocontroller->editView($id);
        break;

    case 'update':
        $registrocontroller= new registroController($db);
        $registrocontroller->update();
        break;

    default:
        // Acción desconocida: se muestra un 404 en vez de una pantalla en blanco
        http_response_code(404);
        echo '<h1>404 - Página no encontrada</h1>';
        echo '<p><a href="index.php?action=dashboard">Volver al panel</a></p>';
        break;
}
?>