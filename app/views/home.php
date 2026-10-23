<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Asistencia - Panel de Registro</title>
    <link rel="stylesheet" href="../../public/asset/css/index.css">
</head>
<body>

    <!-- Header Superior -->
    <header>
        <div class="logo-section">
    <div class="menu-container">
        <!-- Botón de 3 líneas -->
        <button class="hamburger-btn" id="menuToggle" type="button">
            <span></span>
            <span></span>
            <span></span>
        </button>

        <!-- Menú de opciones que se abrirá -->
        <div class="dropdown-menu" id="dropdownMenu">
            <a href="#">Inicio</a>
            <a href="#">Registrar Asistencia</a>
            <a href="#">Reportes</a>
            <a href="#">Configuración</a>
        </div>
    </div>
    
</div>
           <?php if (isset($_SESSION['usuario'])): ?>
               <a href="/index.php?action=dashboard" class="btn login" id="login">Ir al panel</a>
           <?php else: ?>
               <a href="/index.php?action=login" class="btn login" id="login">Login</a>
           <?php endif; ?>
    </header>

    <!-- Contenido Principal -->
    <main>
        
        <!-- Título fuera del recuadro -->
        <div class="page-header">
            <h1>J.A.N.E</h1>
            <p>Gestión y control general de asistencias</p>
        </div>
        <div class="status-bar">
        <div class="status-item">
            <span class="status-label">Fecha y Hora Actual</span>
            <strong id="liveClock">Cargando...</strong>
        </div>
        <div class="status-divider"></div>
        <div class="status-item">
            <span class="status-label">Última Actualización de Datos</span>
            <strong>10/09/2026 - 02:30 PM</strong>
        </div>
    </div>

    <!-- Módulo de Consulta Pública -->
    <div class="search-card">
        <h2>Consulta Pública de Asistencia</h2>
        <p>Ingresa tu número de documento para verificar tu registro acumulado.</p>
        
        <form class="search-form" onsubmit="event.preventDefault();">
            <input type="text" placeholder="Ej. v28.123.456" class="search-input" required>
            <button type="submit" class="btn btn-primary">Consultar</button>
        </form>
    </div>

    <!-- Sección de Comunicados -->
    <div class="announcements-card">
        <h3>📢 Comunicados</h3>
        <ul>
            <li>Los datos de asistencia correspondientes a la presente semana ya se encuentran actualizados en el sistema.</li>
            <li>Para correcciones de registro, diríjase a la coordinación correspondiente con el personal autorizado.</li>
        </ul>
    </div>

    </main>

    <!-- Pie de página -->
    <footer>
        &copy; 2026 J.A.N.E. Todos los derechos reservados.
    </footer>
    <div class="menu-overlay" id="menuOverlay"></div>
<script src="../../public/asset/js/navMenu.js"></script>
<script src="../../public/asset/js/index.js"></script>
</body>
</html>