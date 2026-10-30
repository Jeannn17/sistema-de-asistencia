<?php
/**
 * LAYOUT del panel. Arma la página con los parciales de app/Views/dashboard/:
 *   _menu.php      menú lateral (solo las secciones permitidas al rol)
 *   <seccion>.php  una por cada entrada de $secciones (las define DashboardController::SECCIONES)
 *   _modales.php   ventanas de edición
 * Todas las secciones permitidas viajan en la página (viewsSwitcher.js cambia entre ellas sin recargar);
 * $seccionActiva es la que se ve al abrir.
 */
$e = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');

// Ayudantes que comparten los parciales
$puede = fn(string $c): bool => \App\Core\Permisos::puede($c);   // reglas en config/rutas.php
$hm    = fn($t) => $t ? substr((string) $t, 0, 5) : '';
$esAct = fn($v): bool => $v === true || $v === 't' || $v === 1 || $v === '1';

$secciones         = $secciones ?? [];
$seccionActiva     = $seccionActiva ?? 'inicio';
$errorMigracion    = $errorMigracion ?? false;
$flash             = $flash ?? null;
$listadoPersonal   = $listadoPersonal ?? [];
$listadoTurnos     = $listadoTurnos ?? [];
$listadoCargos     = $listadoCargos ?? [];
$cargosActivos     = $cargosActivos ?? [];
$asistencias       = $asistencias ?? [];
$asistenciasConsultadas = $asistenciasConsultadas ?? false;
$filtrosAsistencia = $filtrosAsistencia ?? ['q' => '', 'desde' => '', 'hasta' => ''];
$tiposJust         = \App\Models\JustificacionModel::TIPOS;
$iconosCargo       = ['directivo' => '👔', 'administrativo' => '📋', 'profesor' => '👨‍🏫',
                      'cocinero' => '🧑‍🍳', 'obrero' => '🛠️', 'vigilante' => '🛡️'];
$perm              = ($resumen ?? [])['permisos'] ?? ['total' => 0, 'reposo' => 0, 'accidente' => 0, 'otros' => 0];
$solicitudesPendientes = count($cuentasPendientes ?? []);   // insignia del menú "Sección"

// Usuario que inició sesión
$nombreUsuario = $_SESSION['nombre'] ?? 'Usuario';
$rolUsuario    = $_SESSION['rol'] ?? '';
$partes        = preg_split('/\s+/', trim($nombreUsuario));
$iniciales     = mb_strtoupper(mb_substr($partes[0] ?? 'U', 0, 1) . mb_substr($partes[1] ?? '', 0, 1));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Asistencia | U.E. El Itaca</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('css/dashboard.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/permisos.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/gestion.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/reportes.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/seccion.css') ?>">
</head>
<body>
    <?php require __DIR__ . '/dashboard/_menu.php'; ?>

    <div class="main-area">
        <header class="topbar">
            <button type="button" class="btn-menu-movil" id="sidebarToggle" aria-label="Abrir menú">☰</button>

            <div class="search-header">
                <div class="search-input-group">
                    <span>🔍</span>
                    <input type="text" id="buscadorGlobal" placeholder="Filtrar los registros visibles por nombre, cédula o cargo...">
                </div>
            </div>

            <div class="topbar-fecha" id="fechaHoy"></div>
        </header>

        <main class="contenido">
            <div class="content-area">
                <?php if ($errorMigracion): ?>
                    <div class="aviso-migracion">
                        <strong>Falta actualizar la base de datos.</strong>
                        Ejecuta <code>database/migracion_roles.sql</code> en PostgreSQL y recarga la página.
                    </div>
                <?php endif; ?>

                <?php foreach ($secciones as $clave => $s): ?>
                    <!-- Sección: <?= $e($s['titulo']) ?> (app/Views/dashboard/<?= $e($s['parcial']) ?>.php) -->
                    <div id="<?= $e($s['id']) ?>" class="seccion-vista"<?= $clave === $seccionActiva ? '' : ' style="display: none;"' ?>>
                        <?php require __DIR__ . '/dashboard/' . $s['parcial'] . '.php'; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>

    <?php require __DIR__ . '/dashboard/_modales.php'; ?>

    <div id="toastNotificacion" class="toast-notificacion" role="status" aria-live="polite"<?php if (!empty($flash)): ?>
         data-flash-tipo="<?= $e($flash['tipo']) ?>" data-flash-mensaje="<?= $e($flash['mensaje']) ?>"<?php endif; ?>>
        <span id="toastMensaje">Mensaje aquí</span>
    </div>

    <script nonce="<?= nonce() ?>">
        // Turnos reales de la base de datos para el formulario de registro
        window.TURNOS = <?= json_encode($turnos ?? [], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    </script>
    <script src="<?= asset('js/viewsSwitcher.js') ?>"></script>
    <script src="<?= asset('js/permisos.js') ?>"></script>
    <script src="<?= asset('js/roles.js') ?>"></script>
    <script src="<?= asset('js/registroWorkflow.js') ?>"></script>
    <script src="<?= asset('js/importAsistencias.js') ?>"></script>
    <script src="<?= asset('js/dashboard.js') ?>"></script>
    <script src="<?= asset('js/gestion.js') ?>"></script>
    <script src="<?= asset('js/reportes.js') ?>"></script>
</body>
</html>
