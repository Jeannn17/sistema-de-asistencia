<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>dashboard</title>
    <link rel= "stylesheet" href= "../../public/asset/css/dashboard.css">
</head>
<body>
     <header>
    <div class="menu-container">
        <!-- Botón de 3 líneas -->
        <button class="hamburger-btn" id="menuToggle" type="button">
            <span></span>
            <span></span>
            <span></span>
        </button>
        <div class="dropdown-menu" id="dropdownMenu">
            <a href="#">....</a>
            <a href="#">....</a>
            <a href="#">....</a>
            <a href="#">....</a>
        </div>
    </div>
    
</div>
    </header>
    <!-- MENÚ FIJO (Representa tu línea roja) -->
    <aside class="sidebar">
        <nav>
            <ul>
                <li><a href="#" class="nav-link active" data-target="vistaInicio">Inicio</a></li>
                <li><a href="#" class="nav-link" data-target="vistaRegistro">Registro</a></li>
                <li><a href="#" class="nav-link" data-target="vistaImportar">Importar Asistencia</a></li>
                <li><a href="#" class="nav-link" data-target="vistaConfig">Configuración</a></li>
            </ul>
        </nav>
    </aside>
    <!-- ÁREA PRINCIPAL -->
    <main class="main-content">
        
        <!-- CONTENEDOR DE DATOS (Representa tu línea amarilla) -->
        <section class="data-wrapper">
            
            <!-- BARRA DE BÚSQUEDA Y FILTRO (Representa tu línea negra) -->
            <div class="search-header">
                <div class="search-input-group">
                    <span class="search-icon">🔍</span>
                    <input type="text" placeholder="Buscar en el sistema...">
                </div>
                <button class="filter-btn">
                    <span class="filter-icon">≡</span> Filtros
                </button>
            </div>

            <!-- ESPACIO LIBRE PARA FUTURAS TABLAS O GRÁFICOS -->
            <div class="content-area">
              <!-- 1. VISTA DE INICIO (Visible por defecto) -->
            <div id="vistaInicio" class="seccion-vista">
                <h2>Bienvenido al Sistema de Asistencia - U.E. El Itaca</h2>
                <p style="margin-top: 15px; color: #666;">
                    Seleccione una opción del menú lateral para comenzar.
                </p>
                <!-- Aquí puedes colocar futuros gráficos, tarjetas de resumen, etc. -->
            </div>

            <!-- 2. VISTA DE REGISTRO (Oculta al inicio) -->
<div id="vistaRegistro" class="seccion-vista" style="display: none;">

    <!-- NUEVO PASO 1: Selección de Cargos (Pantalla inicial de registro) -->
    <div id="pasoCargos">
        <div class="header-paso">
            <!-- Se eliminó el botón de volver de aquí -->
            <h2>Seleccione el Departamento o Cargo:</h2>
        </div>
            <div class="cargos-grid">
                <!-- Directivo -->
                <button type="button" class="btn-cargo" data-id="1" data-cargo="Directivo">
                    <span class="cargo-icon">👔</span>
                    <span class="cargo-text">Directivo</span>
                </button>

                <!-- Administrativo -->
                <button type="button" class="btn-cargo" data-id="2" data-cargo="Administrativo">
                    <span class="cargo-icon">📋</span>
                    <span class="cargo-text">Administrativo</span>
                </button>

                <!-- Profesor -->
                <button type="button" class="btn-cargo" data-id="3" data-cargo="Profesor">
                    <span class="cargo-icon">👨‍🏫</span>
                    <span class="cargo-text">Profesor</span>
                </button>

                <!-- Cocinero -->
                <button type="button" class="btn-cargo" data-id="4" data-cargo="Cocinero">
                    <span class="cargo-icon">🧑‍🍳</span>
                    <span class="cargo-text">Cocinero</span>
                </button>

                <!-- Obrero -->
                <button type="button" class="btn-cargo" data-id="5" data-cargo="Obrero">
                    <span class="cargo-icon">🛠️</span>
                    <span class="cargo-text">Obrero</span>
                </button>

                <!-- Vigilante -->
                <button type="button" class="btn-cargo" data-id="6" data-cargo="Vigilante">
                    <span class="cargo-icon">🛡️</span>
                    <span class="cargo-text">Vigilante</span>
                </button>
            </div>
        </div>

       <!-- NUEVO PASO 2: Formulario Dinámico de Registro -->
    <div id="pasoFormulario" style="display: none;">
        <div class="header-paso">
            <button type="button" id="btnVolverCargos" class="btn-volver">← Volver</button>
            <h2 id="tituloCargoFormulario">Registro de Personal</h2>
        </div>

        <form id="formRegistroTrabajadores" action="../../index.php?action=store" method="POST">
            <!-- Campo oculto -->
            <input type="hidden" name="id_cargo" id="inputCargoSeleccionado">

            <!-- Contenedor dinámico -->
            <div id="contenedorTrabajadores"></div>

            <!-- Botones de Acción -->
            <div class="form-acciones">
                <button type="button" id="btnAgregarTrabajador" class="btn-agregar-mas">
                    <span>➕</span> Agregar otro trabajador
                </button>
                <button type="submit" class="btn-guardar-lote">
                    <span>💾</span> Guardar Registro
                </button>
            </div>
        </form>
    </div>
</div>
        </section>

    </main>
    <div id="toastNotificacion" class="toast-notificacion">
    <span id="toastMensaje">Mensaje aquí</span>
</div>
    <script src="../../public/asset/js/navMenu.js"></script>
</body>
</html>