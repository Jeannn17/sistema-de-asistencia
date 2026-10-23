<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>dashboard</title>
    <link rel="stylesheet" href="../../public/asset/css/dashboard.css">
    <link rel="stylesheet" href="../../public/asset/css/permisos.css">
    <!-- Opcional: FontAwesome para los iconos si lo usas -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
                <a href="/index.php?action=dashboard">Inicio</a>
                <a href="/index.php?action=logout">Cerrar sesión</a>
            </div>
        </div>
    </header>

    <!-- MENÚ FIJO -->
    <aside class="sidebar">
        <nav>
            <ul>
                <li><a href="#" class="nav-link active" data-target="vistaInicio">Inicio</a></li>
                <li><a href="#" class="nav-link" data-target="vistaRegistro">Registro</a></li>
                <li><a href="#" class="nav-link" data-target="vistaImportar">Importar Asistencia</a></li>
                <li><a href="#" class="nav-link" data-target="vistaPermisos">Permisos</a></li>
                <li><a href="#" class="nav-link" data-target="vistaConfig">Configuración</a></li>
                <li><a href="/index.php?action=logout">Cerrar sesión</a></li>
            </ul>
        </nav>
    </aside>

    <!-- ÁREA PRINCIPAL -->
    <main class="main-content">
        <!-- CONTENEDOR DE DATOS -->
        <section class="data-wrapper">
            
            <!-- BARRA DE BÚSQUEDA Y FILTRO -->
            <div class="search-header">
                <div class="search-input-group">
                    <span class="search-icon">🔍</span>
                    <input type="text" placeholder="Buscar en el sistema...">
                </div>
                <button class="filter-btn">
                    <span class="filter-icon">≡</span> Filtros
                </button>
            </div>

            <!-- CONTENEDOR DE VISTAS -->
            <div class="content-area">
                
                <!-- 1. VISTA DE INICIO (Visible por defecto) -->
                <div id="vistaInicio" class="seccion-vista">
                    <div class="welcome-container">
                        <h2>Bienvenido al Sistema de Asistencia - U.E. El Itaca</h2>
                        <p style="margin-top: 15px; color: #666;">
                            Seleccione una opción del menú lateral para comenzar.
                        </p>
                    </div>
                </div>

                <!-- 2. VISTA DE REGISTRO (Oculta al inicio) -->
                <div id="vistaRegistro" class="seccion-vista" style="display: none;">
                    <!-- PASO 1: Selección de Cargos -->
                    <div id="pasoCargos">
                        <div class="header-paso">
                            <h2>Seleccione el Departamento o Cargo:</h2>
                        </div>
                        <div class="cargos-grid">
                            <button type="button" class="btn-cargo" data-id="1" data-cargo="Directivo">
                                <span class="cargo-icon">👔</span>
                                <span class="cargo-text">Directivo</span>
                            </button>
                            <button type="button" class="btn-cargo" data-id="2" data-cargo="Administrativo">
                                <span class="cargo-icon">📋</span>
                                <span class="cargo-text">Administrativo</span>
                            </button>
                            <button type="button" class="btn-cargo" data-id="3" data-cargo="Profesor">
                                <span class="cargo-icon">👨‍🏫</span>
                                <span class="cargo-text">Profesor</span>
                            </button>
                            <button type="button" class="btn-cargo" data-id="4" data-cargo="Cocinero">
                                <span class="cargo-icon">🧑‍🍳</span>
                                <span class="cargo-text">Cocinero</span>
                            </button>
                            <button type="button" class="btn-cargo" data-id="5" data-cargo="Obrero">
                                <span class="cargo-icon">🛠️</span>
                                <span class="cargo-text">Obrero</span>
                            </button>
                            <button type="button" class="btn-cargo" data-id="6" data-cargo="Vigilante">
                                <span class="cargo-icon">🛡️</span>
                                <span class="cargo-text">Vigilante</span>
                            </button>
                        </div>
                    </div>

                    <!-- PASO 2: Formulario Dinámico de Registro -->
                    <div id="pasoFormulario" style="display: none;">
                        <div class="header-paso">
                            <button type="button" id="btnVolverCargos" class="btn-volver">← Volver</button>
                            <h2 id="tituloCargoFormulario">Registro de Personal</h2>
                        </div>

                        <form id="formRegistroTrabajadores" action="/index.php?action=store" method="POST">
                            <input type="hidden" name="id_cargo" id="inputCargoSeleccionado">
                            <div id="contenedorTrabajadores"></div>
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

                <!-- 3. VISTA DE IMPORTAR ASISTENCIA (Con diseño mejorado y oculta por defecto) -->
              <div id="vistaImportar" class="seccion-vista" style="display: none;">
    <div class="import-container">
        <div class="import-card">
            <div class="import-header">
                <h3>📊 Cargar Asistencia del Dispositivo Biométrico</h3>
            </div>
            <p class="import-subtitle">
                Selecciona el archivo <strong>.xlsx o .xls</strong> exportado para procesar los registros de asistencia mediante el módulo ETL.
            </p>
            
            <form action="index.php?action=asistencia" method="POST" enctype="multipart/form-data" class="import-form">
                <div class="file-drop-area">
                    <!-- Se actualizó el texto para reflejar ambos formatos -->
                    <span class="file-msg" id="fileNameText">📂 Arrastra tu archivo Excel aquí o haz clic para buscar (.xlsx, .xls)</span>
                    
                    <!-- Se modificó accept para incluir .xls -->
                    <input type="file" name="archivo_excel" id="inputExcel" accept=".xlsx, .xls" required class="file-input">
                </div>
                
                <button type="submit" class="btn-primary-etl">
                    ⚙️ Procesar Archivo ETL
                </button>
            </form>
        </div>
    </div>
</div>

                <!-- 3.1 VISTA DE PERMISOS / JUSTIFICACIONES (Oculta por defecto) -->
                <div id="vistaPermisos" class="seccion-vista" style="display: none;">
                    <?php
                    $e = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
                    $tiposPermiso = permisosController::TIPOS;
                    $modos = ['reposo' => 'rango', 'accidente_laboral' => 'rango', 'permiso_dia' => 'dia',
                              'salida_temprana' => 'hora', 'llegada_tarde' => 'hora'];
                    ?>
                    <div class="permisos-container">
                        <div class="permiso-card">
                            <div class="header-paso">
                                <h2>📝 Registrar Permiso / Justificación</h2>
                            </div>
                            <p class="permiso-subtitulo">
                                Registra reposos, accidentes laborales y permisos para justificar las faltas o retrasos del personal.
                            </p>

                            <form id="formPermiso" action="/index.php?action=permiso_guardar" method="POST" autocomplete="off">
                                <input type="hidden" name="id_persona" id="permisoIdPersona">

                                <div class="inputs-grid">
                                    <div class="input-group input-wide">
                                        <label for="permisoBuscarPersona">Trabajador</label>
                                        <input type="text" id="permisoBuscarPersona" list="listaPersonalPermiso"
                                               placeholder="Escribe la cédula, el nombre o el apellido..." required>
                                        <datalist id="listaPersonalPermiso">
                                            <?php foreach (($personalPermisos ?? []) as $p): ?>
                                                <option value="<?= $e($p['cedula'] . ' — ' . $p['apellido'] . ' ' . $p['nombre'] . ' (N° ' . $p['nro_registro'] . ')') ?>"
                                                        data-id="<?= (int) $p['id_persona'] ?>"></option>
                                            <?php endforeach; ?>
                                        </datalist>
                                    </div>

                                    <div class="input-group">
                                        <label for="permisoTipo">Tipo de justificación</label>
                                        <select name="tipo" id="permisoTipo" required>
                                            <?php foreach ($tiposPermiso as $clave => $info): ?>
                                                <option value="<?= $e($clave) ?>" data-modo="<?= $e($modos[$clave]) ?>"><?= $e($info[0]) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="input-group">
                                        <label for="permisoFechaInicio" id="labelFechaInicio">Fecha de inicio</label>
                                        <input type="date" name="fecha_inicio" id="permisoFechaInicio" required>
                                    </div>

                                    <div class="input-group" id="grupoFechaFin">
                                        <label for="permisoFechaFin">Fecha de fin</label>
                                        <input type="date" name="fecha_fin" id="permisoFechaFin">
                                    </div>

                                    <div class="input-group campo-oculto" id="grupoHora">
                                        <label for="permisoHora" id="labelHora">Hora</label>
                                        <input type="time" name="hora" id="permisoHora">
                                    </div>

                                    <div class="input-group input-wide">
                                        <label for="permisoMotivo">Motivo</label>
                                        <textarea name="motivo" id="permisoMotivo" maxlength="500"
                                                  placeholder="Describe el motivo (ej: reposo médico por 3 días, cita médica...)" required></textarea>
                                        <span class="contador-motivo" id="contadorMotivo">0/500</span>
                                    </div>
                                </div>

                                <div class="form-acciones">
                                    <span></span>
                                    <button type="submit" class="btn-guardar-lote">
                                        <span>💾</span> Guardar Permiso
                                    </button>
                                </div>
                            </form>
                        </div>

                        <div class="permisos-lista">
                            <h3>Últimos permisos registrados</h3>
                            <div class="permisos-tabla-wrapper">
                                <table class="permisos-tabla">
                                    <thead>
                                        <tr>
                                            <th>Trabajador</th>
                                            <th>Tipo</th>
                                            <th>Desde</th>
                                            <th>Hasta / Hora</th>
                                            <th>Motivo</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($permisosRecientes)): ?>
                                            <tr><td colspan="5" class="permisos-vacio">Aún no hay permisos registrados.</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($permisosRecientes as $j): ?>
                                                <?php
                                                $etiqueta = $tiposPermiso[$j['tipo']][0] ?? $j['tipo'];
                                                $desde = date('d/m/Y', strtotime($j['fecha_inicio']));
                                                if (!empty($j['hora'])) {
                                                    $hastaHora = substr($j['hora'], 0, 5);
                                                } elseif ($j['fecha_fin'] !== $j['fecha_inicio']) {
                                                    $hastaHora = date('d/m/Y', strtotime($j['fecha_fin']));
                                                } else {
                                                    $hastaHora = '—';
                                                }
                                                ?>
                                                <tr>
                                                    <td><?= $e($j['apellido'] . ' ' . $j['nombre']) ?><br><small><?= $e($j['cedula']) ?></small></td>
                                                    <td><span class="badge-tipo badge-<?= $e($j['tipo']) ?>"><?= $e($etiqueta) ?></span></td>
                                                    <td><?= $e($desde) ?></td>
                                                    <td><?= $e($hastaHora) ?></td>
                                                    <td><?= $e($j['motivo']) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 4. VISTA DE CONFIGURACIÓN (Ejemplo por si la usas luego) -->
                <div id="vistaConfig" class="seccion-vista" style="display: none;">
                    <h2>Configuración del Sistema</h2>
                    <p style="margin-top: 15px; color: #666;">Panel de ajustes generales.</p>
                </div>

            </div>
        </section>
    </main>

    <div id="toastNotificacion" class="toast-notificacion">
        <span id="toastMensaje">Mensaje aquí</span>
    </div>

    <script>
        // Turnos reales de la base de datos para el formulario de registro
        window.TURNOS = <?= json_encode($turnos ?? [], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    </script>
    <script src="../../public/asset/js/navMenu.js"></script>
    <script src="../../public/asset/js/viewsSwitcher.js"></script>
    <script src="../../public/asset/js/permisos.js"></script>
    <script src="../../public/asset/js/registroWorkflow.js"></script>
    <script src="../../public/asset/js/importAsistencias.js"></script>
</body>
</html>