<?php
$e = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');

// Permisos según el rol de la sesión (reglas en config/permisos.php)
$puede   = fn(string $c): bool => Permisos::puede($c);
$hm      = fn($t) => $t ? substr((string) $t, 0, 5) : '';
$esAct   = fn($v): bool => $v === true || $v === 't' || $v === 1 || $v === '1';
$errorMigracion    = $errorMigracion ?? false;
$listadoPersonal   = $listadoPersonal ?? [];
$listadoTurnos     = $listadoTurnos ?? [];
$listadoCargos     = $listadoCargos ?? [];
$cargosActivos     = $cargosActivos ?? [];
$asistencias       = $asistencias ?? [];
$filtrosAsistencia = $filtrosAsistencia ?? ['q' => '', 'desde' => '', 'hasta' => ''];
$tiposJust         = permisosController::TIPOS;
$iconosCargo       = ['directivo' => '👔', 'administrativo' => '📋', 'profesor' => '👨‍🏫',
                      'cocinero' => '🧑‍🍳', 'obrero' => '🛠️', 'vigilante' => '🛡️'];

// Datos del Inicio (los arma dashboardModel desde index.php)
$r        = $resumen ?? [];
$total    = (int) ($r['total'] ?? 0);
$activos  = (int) ($r['activos'] ?? 0);
$inactivos = (int) ($r['inactivos'] ?? 0);
$pctActivos = $total > 0 ? round($activos * 100 / $total, 1) : 0;
$pctAsist = $r['pct_asistencia'] ?? null;
$perm     = $r['permisos'] ?? ['total' => 0, 'reposo' => 0, 'accidente' => 0, 'otros' => 0];
$fechaRef = !empty($r['fecha_ref']) ? date('d/m/Y', strtotime($r['fecha_ref'])) : null;

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
    <link rel="stylesheet" href="../../public/asset/css/dashboard.css">
    <link rel="stylesheet" href="../../public/asset/css/permisos.css">
    <link rel="stylesheet" href="../../public/asset/css/gestion.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- ================= MENÚ LATERAL ================= -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-scroll">
            <div class="marca">
                <div class="marca-logo"><svg viewBox="0 0 24 24"><path d="M12 14l9-5-9-5-9 5 9 5z"/><path d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/><path d="M12 14v7"/></svg></div>
                <div>
                    <div class="marca-nombre">U.E. El Itaca <span class="punto-online"></span></div>
                    <div class="marca-sub">Control de personal</div>
                </div>
            </div>

            <span class="menu-titulo">Menú principal</span>
            <nav aria-label="Navegación principal">
                <a href="#" class="nav-link active" data-target="vistaInicio"><svg viewBox="0 0 24 24"><path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg><span>Inicio</span></a>
                <a href="#" class="nav-link" data-target="vistaTrabajadores"><svg viewBox="0 0 24 24"><path d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg><span>Trabajadores</span></a>
                <?php if ($puede('gestionar_personal')): ?>
                <a href="#" class="nav-link" data-target="vistaRegistro"><svg viewBox="0 0 24 24"><path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg><span>Registrar trabajadores</span></a>
                <?php endif; ?>
                <a href="#" class="nav-link" data-target="vistaTurnos"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg><span>Turnos</span></a>
                <a href="#" class="nav-link" data-target="vistaCargos"><svg viewBox="0 0 24 24"><path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg><span>Cargos</span></a>
                <a href="#" class="nav-link" data-target="vistaAsistencias"><svg viewBox="0 0 24 24"><path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg><span>Asistencias</span></a>
                <?php if ($puede('importar_asistencia')): ?>
                <a href="#" class="nav-link" data-target="vistaImportar"><svg viewBox="0 0 24 24"><path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg><span>Importar asistencia</span></a>
                <?php endif; ?>
                <a href="#" class="nav-link" data-target="vistaPermisos"><svg viewBox="0 0 24 24"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg><span>Permisos</span>
                    <?php if (($perm['total'] ?? 0) > 0): ?><span class="insignia"><?= (int) $perm['total'] ?> vigentes</span><?php endif; ?>
                </a>
            </nav>

            <span class="menu-titulo">Gestión</span>
            <nav aria-label="Navegación de gestión">
                <a href="#" class="nav-link" data-target="vistaConfig"><svg viewBox="0 0 24 24"><path d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg><span>Configuración</span></a>
            </nav>
        </div>

        <div class="sidebar-pie">
            <div class="usuario-caja">
                <div class="usuario-avatar"><?= $e($iniciales) ?></div>
                <div>
                    <div class="usuario-nombre"><?= $e($nombreUsuario) ?></div>
                    <div class="usuario-rol"><?= $e($rolUsuario) ?></div>
                </div>
            </div>
            <a href="/index.php?action=logout" class="cerrar-sesion"><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>Cerrar sesión</a>
        </div>
    </aside>

    <!-- ================= ÁREA PRINCIPAL ================= -->
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

                <!-- 1. VISTA DE INICIO (visible por defecto) -->
                <div id="vistaInicio" class="seccion-vista">
                    <section class="hero">
                        <div>
                            <h2>Bienvenido al Sistema de Asistencia – U.E. El Itaca</h2>
                            <p>Gestión centralizada de la asistencia laboral, la importación de marcaciones del biométrico y las justificaciones del personal.</p>
                        </div>
                        <div class="hero-acciones">
                            <?php if ($puede('importar_asistencia')): ?>
                                <button type="button" class="btn-hero" data-goto="vistaImportar">Importar asistencia</button>
                            <?php else: ?>
                                <button type="button" class="btn-hero" data-goto="vistaTrabajadores">Ver trabajadores</button>
                            <?php endif; ?>
                            <?php if ($puede('registrar_permisos')): ?>
                                <button type="button" class="btn-hero principal" data-goto="vistaPermisos">Registrar permiso</button>
                            <?php else: ?>
                                <button type="button" class="btn-hero principal" data-goto="vistaAsistencias">Consultar asistencias</button>
                            <?php endif; ?>
                        </div>
                    </section>

                    <section class="stats-grid">
                        <article class="stat-card">
                            <div class="stat-titulo">Total de trabajadores</div>
                            <div class="stat-valor"><?= $total ?> <small>registrados</small></div>
                            <div class="stat-detalle">
                                <span>Activos: <strong><?= $activos ?></strong></span>
                                <?php if (($r['nuevos_mes'] ?? 0) > 0): ?>
                                    <span class="etiqueta verde">+<?= (int) $r['nuevos_mes'] ?> este mes</span>
                                <?php else: ?>
                                    <span>Inactivos: <strong><?= $inactivos ?></strong></span>
                                <?php endif; ?>
                            </div>
                        </article>

                        <article class="stat-card">
                            <div class="stat-titulo">Estado del personal</div>
                            <div class="stat-valor"><?= $activos ?> <small>activos · <?= $inactivos ?> inactivos</small></div>
                            <div class="barra"><span style="width: <?= $pctActivos ?>%"></span></div>
                            <div class="stat-detalle"><span><?= $pctActivos ?>% del personal está activo</span></div>
                        </article>

                        <article class="stat-card">
                            <div class="stat-titulo">Permisos vigentes hoy</div>
                            <div class="stat-valor"><?= (int) $perm['total'] ?> <small>justificaciones</small></div>
                            <div class="stat-detalle">
                                <span>Reposo: <strong><?= (int) $perm['reposo'] ?></strong></span>
                                <span>Accidente: <strong><?= (int) $perm['accidente'] ?></strong></span>
                                <span>Otros: <strong><?= (int) $perm['otros'] ?></strong></span>
                            </div>
                        </article>

                        <article class="stat-card">
                            <div class="stat-titulo">Asistencia del día</div>
                            <?php if ($pctAsist !== null): ?>
                                <div class="stat-valor pct-verde"><?= $pctAsist ?>%</div>
                                <div class="stat-detalle">
                                    <span><?= (int) $r['presentes'] ?> de <?= $activos ?> con marcación</span>
                                    <span class="pct-rojo"><?= round(100 - $pctAsist, 1) ?>% sin marcar</span>
                                </div>
                                <div class="stat-detalle" style="border-top:none; padding-top:0; margin-top:4px;">
                                    <span>Último día importado: <?= $e($fechaRef) ?></span>
                                </div>
                            <?php else: ?>
                                <div class="stat-valor">—</div>
                                <div class="stat-detalle"><span>Aún no hay asistencia importada</span></div>
                            <?php endif; ?>
                        </article>
                    </section>

                    <section class="inicio-fila">
                        <article class="panel">
                            <h3>Asistencia por cargo</h3>
                            <p class="panel-sub">
                                <?= $fechaRef ? 'Personal activo con marcación el ' . $e($fechaRef) : 'Importa un informe para ver la asistencia por cargo' ?>
                            </p>
                            <?php if (empty($r['por_cargo'])): ?>
                                <div class="vacio-aviso">Sin datos de asistencia todavía.</div>
                            <?php else: ?>
                                <?php foreach ($r['por_cargo'] as $c): ?>
                                    <div class="cargo-fila">
                                        <div class="cargo-fila-info">
                                            <?= $e($c['cargo']) ?>
                                            <span><strong><?= $e($c['pct']) ?>%</strong> (<?= (int) $c['presentes'] ?>/<?= (int) $c['total'] ?> presentes)</span>
                                        </div>
                                        <div class="barra <?= $c['pct'] >= 80 ? '' : 'azul' ?>"><span style="width: <?= (float) $c['pct'] ?>%"></span></div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </article>

                        <article class="panel">
                            <h3>Acciones rápidas</h3>
                            <?php if ($puede('importar_asistencia')): ?>
                            <button type="button" class="accion-item destacada" data-goto="vistaImportar">
                                <span class="accion-icono"><svg viewBox="0 0 24 24"><path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg></span>
                                <span><strong>Importar asistencia</strong><small>Archivo Excel (.xls o .xlsx) del biométrico</small></span>
                            </button>
                            <?php endif; ?>
                            <?php if ($puede('registrar_permisos')): ?>
                            <button type="button" class="accion-item" data-goto="vistaPermisos">
                                <span class="accion-icono ambar"><svg viewBox="0 0 24 24"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg></span>
                                <span><strong>Registrar permiso o justificación</strong><small>Reposo, accidente laboral o permiso</small></span>
                            </button>
                            <?php endif; ?>
                            <?php if ($puede('gestionar_personal')): ?>
                            <button type="button" class="accion-item" data-goto="vistaRegistro">
                                <span class="accion-icono"><svg viewBox="0 0 24 24"><path d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg></span>
                                <span><strong>Registrar trabajadores</strong><small>Alta de personal por cargo</small></span>
                            </button>
                            <?php endif; ?>
                            <button type="button" class="accion-item" data-goto="vistaAsistencias">
                                <span class="accion-icono"><svg viewBox="0 0 24 24"><path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg></span>
                                <span><strong>Consultar asistencias</strong><small>Por trabajador y rango de fechas</small></span>
                            </button>
                        </article>
                    </section>

                    <section class="panel panel-tabla">
                        <div class="panel-tabla-cabecera">
                            <h3>Últimos registros de asistencia importados</h3>
                            <p class="panel-sub">Marcaciones más recientes guardadas en el sistema.</p>
                        </div>
                        <div class="tabla-wrapper">
                            <table class="tabla-registros">
                                <thead>
                                    <tr>
                                        <th>Trabajador / Identificación</th>
                                        <th>Cargo</th>
                                        <th>Fecha</th>
                                        <th>Hora</th>
                                        <th>Tipo</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($r['ultimos'])): ?>
                                        <tr><td colspan="6" class="vacio-aviso">Aún no hay marcaciones importadas.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($r['ultimos'] as $m): ?>
                                            <tr>
                                                <td>
                                                    <div class="persona-celda">
                                                        <span class="persona-avatar"><?= $e($m['iniciales']) ?></span>
                                                        <span><strong><?= $e($m['nombre']) ?></strong><small>C.I: <?= $e($m['cedula']) ?></small></span>
                                                    </div>
                                                </td>
                                                <td><?= $e($m['cargo']) ?></td>
                                                <td><?= $e($m['fecha']) ?></td>
                                                <td class="hora-mono"><?= $e($m['hora']) ?></td>
                                                <td><span class="etiqueta gris"><?= $e($m['tipo']) ?></span></td>
                                                <?php $clase = ['ok' => 'verde', 'alerta' => 'ambar', 'neutro' => 'gris'][$m['estado']['clase']] ?? 'gris'; ?>
                                                <td><span class="etiqueta <?= $clase ?>"><?= $e($m['estado']['texto']) ?></span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>

                <!-- 2. VISTA DE REGISTRO (Oculta al inicio) -->
                <div id="vistaRegistro" class="seccion-vista" style="display: none;">
                    <!-- PASO 1: Selección de Cargos -->
                    <div id="pasoCargos">
                        <div class="header-paso">
                            <h2>Seleccione el Departamento o Cargo:</h2>
                        </div>
                        <div class="cargos-grid">
                            <?php foreach ($cargosActivos as $c): ?>
                                <button type="button" class="btn-cargo" data-id="<?= (int) $c['id_cargo'] ?>" data-cargo="<?= $e($c['nombre_cargo']) ?>">
                                    <span class="cargo-icon"><?= $iconosCargo[mb_strtolower($c['nombre_cargo'])] ?? '💼' ?></span>
                                    <span class="cargo-text"><?= $e($c['nombre_cargo']) ?></span>
                                </button>
                            <?php endforeach; ?>
                            <?php if (empty($cargosActivos)): ?>
                                <p class="vacio-aviso">No hay cargos activos. Crea uno en la sección Cargos.</p>
                            <?php endif; ?>
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
                        <?php if ($puede('registrar_permisos')): ?>
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

                        <?php endif; ?>
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

                <!-- 3.2 TRABAJADORES (consulta para todos; editar solo con permiso) -->
                <div id="vistaTrabajadores" class="seccion-vista" style="display: none;">
                    <div class="gestion-cabecera">
                        <div>
                            <h2>Trabajadores</h2>
                            <p class="panel-sub"><?= count($listadoPersonal) ?> trabajadores registrados</p>
                        </div>
                        <?php if ($puede('gestionar_personal')): ?>
                            <button type="button" class="btn-primario" data-goto="vistaRegistro">＋ Registrar trabajadores</button>
                        <?php endif; ?>
                    </div>
                    <div class="tabla-wrapper">
                        <table class="tabla-registros">
                            <thead>
                                <tr>
                                    <th>Cédula</th>
                                    <th>Trabajador</th>
                                    <th>N° registro</th>
                                    <th>Cargo</th>
                                    <th>Turno</th>
                                    <th>Ingreso</th>
                                    <th>Estado</th>
                                    <?php if ($puede('gestionar_personal')): ?><th>Acciones</th><?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($listadoPersonal)): ?>
                                    <tr><td colspan="8" class="vacio-aviso">Aún no hay trabajadores registrados.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($listadoPersonal as $p): ?>
                                        <?php $act = $esAct($p['activo']); ?>
                                        <tr class="<?= $act ? '' : 'fila-inactiva' ?>">
                                            <td><?= $e($p['cedula']) ?></td>
                                            <td><strong><?= $e($p['apellido'] . ' ' . $p['nombre']) ?></strong></td>
                                            <td><?= $e($p['nro_registro'] ?? '—') ?></td>
                                            <td><?= $e($p['nombre_cargo']) ?></td>
                                            <td><?= $e($p['nombre_turno']) ?></td>
                                            <td><?= $e(date('d/m/Y', strtotime($p['fecha_ingreso']))) ?></td>
                                            <td><span class="etiqueta <?= $act ? 'verde' : 'gris' ?>"><?= $act ? 'Activo' : 'Inactivo' ?></span></td>
                                            <?php if ($puede('gestionar_personal')): ?>
                                                <td>
                                                    <button type="button" class="btn-tabla" data-editar="modalPersona"
                                                            data-json="<?= $e(json_encode([
                                                                'id_persona'    => (int) $p['id_persona'],
                                                                'cedula'        => $p['cedula'],
                                                                'nombre'        => $p['nombre'],
                                                                'apellido'      => $p['apellido'],
                                                                'nro_registro'  => $p['nro_registro'],
                                                                'id_cargo'      => (int) $p['id_cargo'],
                                                                'id_turno'      => (int) $p['id_turno'],
                                                                'fecha_ingreso' => $p['fecha_ingreso'],
                                                                'activo'        => $act,
                                                            ], JSON_UNESCAPED_UNICODE)) ?>">Editar</button>
                                                </td>
                                            <?php endif; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- 3.3 TURNOS -->
                <div id="vistaTurnos" class="seccion-vista" style="display: none;">
                    <div class="gestion-cabecera">
                        <div>
                            <h2>Turnos</h2>
                            <p class="panel-sub">Un turno inactivo no se ofrece en nuevos registros; los trabajadores que ya lo tienen no se afectan.</p>
                        </div>
                        <?php if ($puede('gestionar_turnos')): ?>
                            <button type="button" class="btn-primario" data-nuevo="modalTurno">＋ Nuevo turno</button>
                        <?php endif; ?>
                    </div>
                    <div class="tabla-wrapper">
                        <table class="tabla-registros">
                            <thead>
                                <tr>
                                    <th>Turno</th>
                                    <th>Periodo 1</th>
                                    <th>Periodo 2</th>
                                    <th>Extra</th>
                                    <th>Trabajadores activos</th>
                                    <th>Estado</th>
                                    <?php if ($puede('gestionar_turnos')): ?><th>Acciones</th><?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($listadoTurnos)): ?>
                                    <tr><td colspan="7" class="vacio-aviso">Aún no hay turnos registrados.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($listadoTurnos as $t): ?>
                                        <?php
                                        $act = $esAct($t['activo']);
                                        $rango = fn($i, $f) => $i ? $hm($i) . ' – ' . $hm($f) : '—';
                                        ?>
                                        <tr class="<?= $act ? '' : 'fila-inactiva' ?>">
                                            <td><strong><?= $e($t['nombre_turno']) ?></strong></td>
                                            <td class="hora-mono"><?= $e($rango($t['inicio_p1'], $t['fin_p1'])) ?></td>
                                            <td class="hora-mono"><?= $e($rango($t['inicio_p2'], $t['fin_p2'])) ?></td>
                                            <td class="hora-mono"><?= $e($rango($t['inicio_extra'], $t['fin_extra'])) ?></td>
                                            <td><?= (int) $t['trabajadores'] ?></td>
                                            <td><span class="etiqueta <?= $act ? 'verde' : 'gris' ?>"><?= $act ? 'Activo' : 'Inactivo' ?></span></td>
                                            <?php if ($puede('gestionar_turnos')): ?>
                                                <td>
                                                    <div class="acciones-celda">
                                                        <button type="button" class="btn-tabla" data-editar="modalTurno"
                                                                data-json="<?= $e(json_encode([
                                                                    'id_turno'     => (int) $t['id_turno'],
                                                                    'nombre_turno' => $t['nombre_turno'],
                                                                    'inicio_p1'    => $hm($t['inicio_p1']),
                                                                    'fin_p1'       => $hm($t['fin_p1']),
                                                                    'inicio_p2'    => $hm($t['inicio_p2']),
                                                                    'fin_p2'       => $hm($t['fin_p2']),
                                                                    'inicio_extra' => $hm($t['inicio_extra']),
                                                                    'fin_extra'    => $hm($t['fin_extra']),
                                                                ], JSON_UNESCAPED_UNICODE)) ?>">Editar</button>
                                                        <form class="form-inline" method="POST" action="/index.php?action=turno_estado"
                                                              data-confirmar="<?= $act ? '¿Desactivar este turno? Ya no se ofrecerá en nuevos registros.' : '¿Activar este turno?' ?>">
                                                            <input type="hidden" name="id_turno" value="<?= (int) $t['id_turno'] ?>">
                                                            <input type="hidden" name="activo" value="<?= $act ? '0' : '1' ?>">
                                                            <button type="submit" class="btn-tabla <?= $act ? 'peligro' : 'ok' ?>"><?= $act ? 'Desactivar' : 'Activar' ?></button>
                                                        </form>
                                                    </div>
                                                </td>
                                            <?php endif; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- 3.4 CARGOS -->
                <div id="vistaCargos" class="seccion-vista" style="display: none;">
                    <div class="gestion-cabecera">
                        <div>
                            <h2>Cargos</h2>
                            <p class="panel-sub">Un cargo inactivo no se ofrece en nuevos registros; el historial no se borra.</p>
                        </div>
                        <?php if ($puede('gestionar_cargos')): ?>
                            <button type="button" class="btn-primario" data-nuevo="modalCargo">＋ Nuevo cargo</button>
                        <?php endif; ?>
                    </div>
                    <div class="tabla-wrapper">
                        <table class="tabla-registros">
                            <thead>
                                <tr>
                                    <th>Cargo</th>
                                    <th>Trabajadores activos</th>
                                    <th>Estado</th>
                                    <?php if ($puede('gestionar_cargos')): ?><th>Acciones</th><?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($listadoCargos)): ?>
                                    <tr><td colspan="4" class="vacio-aviso">Aún no hay cargos registrados.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($listadoCargos as $c): ?>
                                        <?php $act = $esAct($c['activo']); ?>
                                        <tr class="<?= $act ? '' : 'fila-inactiva' ?>">
                                            <td><strong><?= $e($c['nombre_cargo']) ?></strong></td>
                                            <td><?= (int) $c['trabajadores'] ?></td>
                                            <td><span class="etiqueta <?= $act ? 'verde' : 'gris' ?>"><?= $act ? 'Activo' : 'Inactivo' ?></span></td>
                                            <?php if ($puede('gestionar_cargos')): ?>
                                                <td>
                                                    <div class="acciones-celda">
                                                        <button type="button" class="btn-tabla" data-editar="modalCargo"
                                                                data-json="<?= $e(json_encode([
                                                                    'id_cargo'     => (int) $c['id_cargo'],
                                                                    'nombre_cargo' => $c['nombre_cargo'],
                                                                ], JSON_UNESCAPED_UNICODE)) ?>">Editar</button>
                                                        <form class="form-inline" method="POST" action="/index.php?action=cargo_estado"
                                                              data-confirmar="<?= $act ? '¿Desactivar este cargo? Ya no se ofrecerá en nuevos registros.' : '¿Activar este cargo?' ?>">
                                                            <input type="hidden" name="id_cargo" value="<?= (int) $c['id_cargo'] ?>">
                                                            <input type="hidden" name="activo" value="<?= $act ? '0' : '1' ?>">
                                                            <button type="submit" class="btn-tabla <?= $act ? 'peligro' : 'ok' ?>"><?= $act ? 'Desactivar' : 'Activar' ?></button>
                                                        </form>
                                                    </div>
                                                </td>
                                            <?php endif; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- 3.5 ASISTENCIAS (consulta por trabajador y rango de fechas) -->
                <div id="vistaAsistencias" class="seccion-vista" style="display: none;">
                    <div class="gestion-cabecera">
                        <div>
                            <h2>Consulta de asistencias</h2>
                            <p class="panel-sub">Se muestran los días con marcaciones. Si el trabajador tenía un permiso o reposo ese día, aparece en la columna Justificación.</p>
                        </div>
                    </div>

                    <form class="filtros-form" method="GET" action="/index.php">
                        <input type="hidden" name="action" value="dashboard">
                        <input type="hidden" name="vista" value="asistencias">
                        <div class="campo crece">
                            <label for="filtroQ">Trabajador</label>
                            <input type="text" id="filtroQ" name="q" maxlength="60" placeholder="Cédula, nombre o apellido (vacío = todos)"
                                   value="<?= $e($filtrosAsistencia['q']) ?>">
                        </div>
                        <div class="campo">
                            <label for="filtroDesde">Desde</label>
                            <input type="date" id="filtroDesde" name="desde" value="<?= $e($filtrosAsistencia['desde']) ?>">
                        </div>
                        <div class="campo">
                            <label for="filtroHasta">Hasta</label>
                            <input type="date" id="filtroHasta" name="hasta" value="<?= $e($filtrosAsistencia['hasta']) ?>">
                        </div>
                        <button type="submit" class="btn-primario">Consultar</button>
                    </form>

                    <?php if (($_GET['vista'] ?? '') !== 'asistencias'): ?>
                        <p class="vacio-aviso">Elige los filtros y pulsa Consultar.</p>
                    <?php else: ?>
                        <p class="panel-sub" style="margin-bottom: 10px;">
                            <?= count($asistencias) ?> resultados
                            <?php if (count($asistencias) >= consultaModel::limite()): ?>
                                (se muestran los primeros <?= consultaModel::limite() ?>; acota el rango de fechas)
                            <?php endif; ?>
                        </p>
                        <div class="tabla-wrapper">
                            <table class="tabla-registros">
                                <thead>
                                    <tr>
                                        <th>Trabajador</th>
                                        <th>Cargo</th>
                                        <th>Fecha</th>
                                        <th>Entrada</th>
                                        <th>Salida</th>
                                        <th>Justificación</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($asistencias)): ?>
                                        <tr><td colspan="6" class="vacio-aviso">No hay marcaciones con esos filtros.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($asistencias as $a): ?>
                                            <tr>
                                                <td>
                                                    <strong><?= $e($a['apellido'] . ' ' . $a['nombre']) ?></strong><br>
                                                    <small>C.I: <?= $e($a['cedula']) ?></small>
                                                </td>
                                                <td><?= $e($a['cargo']) ?></td>
                                                <td><?= $e(date('d/m/Y', strtotime($a['fecha']))) ?></td>
                                                <td class="hora-mono"><?= $e($a['entrada'] ?? '—') ?></td>
                                                <td class="hora-mono"><?= $e($a['salida'] ?? '—') ?></td>
                                                <td>
                                                    <?php if (!empty($a['justificacion'])): ?>
                                                        <span class="etiqueta ambar"><?= $e($tiposJust[$a['justificacion']][0] ?? $a['justificacion']) ?></span>
                                                    <?php else: ?>—<?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- CONFIGURACIÓN -->
                <div id="vistaConfig" class="seccion-vista" style="display: none;">
                    <h2>Configuración del sistema</h2>
                    <p style="margin-top: 15px; color: #64748b;">Panel de ajustes generales.</p>
                </div>

            </div>
        </main>
    </div>

    <?php if ($puede('gestionar_personal')): ?>
    <!-- Modal: editar trabajador -->
    <div class="modal-fondo" id="modalPersona" hidden>
        <div class="modal-caja" role="dialog" aria-modal="true" aria-labelledby="tituloPersona">
            <div class="modal-cabecera">
                <h3 id="tituloPersona" data-titulo data-nuevo="Trabajador" data-editar="Editar trabajador">Editar trabajador</h3>
                <button type="button" class="modal-cerrar" data-cerrar-modal aria-label="Cerrar">&times;</button>
            </div>
            <form method="POST" action="/index.php?action=update">
                <input type="hidden" name="id_persona" data-id>
                <div class="modal-grid">
                    <div class="campo">
                        <label for="pCedula">Cédula</label>
                        <input type="text" id="pCedula" name="cedula" maxlength="12" required>
                    </div>
                    <div class="campo">
                        <label for="pNro">N° de registro</label>
                        <input type="number" id="pNro" name="nro_registro" min="1">
                    </div>
                    <div class="campo">
                        <label for="pNombre">Nombre</label>
                        <input type="text" id="pNombre" name="nombre" required>
                    </div>
                    <div class="campo">
                        <label for="pApellido">Apellido</label>
                        <input type="text" id="pApellido" name="apellido" required>
                    </div>
                    <div class="campo">
                        <label for="pCargo">Cargo</label>
                        <select id="pCargo" name="id_cargo" required>
                            <?php foreach ($listadoCargos as $c): ?>
                                <option value="<?= (int) $c['id_cargo'] ?>"><?= $e($c['nombre_cargo']) ?><?= $esAct($c['activo']) ? '' : ' (inactivo)' ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="campo">
                        <label for="pTurno">Turno</label>
                        <select id="pTurno" name="id_turno" required>
                            <?php foreach ($listadoTurnos as $t): ?>
                                <option value="<?= (int) $t['id_turno'] ?>"><?= $e($t['nombre_turno']) ?><?= $esAct($t['activo']) ? '' : ' (inactivo)' ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="campo">
                        <label for="pIngreso">Fecha de ingreso</label>
                        <input type="date" id="pIngreso" name="fecha_ingreso" required>
                    </div>
                    <div class="campo" style="justify-content: flex-end;">
                        <label class="check-linea" style="text-transform: none; letter-spacing: 0; font-size: 0.88rem;">
                            <input type="checkbox" name="activo" value="1"> Trabajador activo
                        </label>
                    </div>
                </div>
                <p class="modal-nota">El N° de registro debe coincidir con el ID del reloj biométrico para importar sus asistencias.</p>
                <div class="modal-acciones">
                    <button type="button" class="btn-secundario" data-cerrar-modal>Cancelar</button>
                    <button type="submit" class="btn-primario">Guardar cambios</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($puede('gestionar_turnos')): ?>
    <!-- Modal: crear / editar turno -->
    <div class="modal-fondo" id="modalTurno" hidden>
        <div class="modal-caja" role="dialog" aria-modal="true" aria-labelledby="tituloTurno">
            <div class="modal-cabecera">
                <h3 id="tituloTurno" data-titulo data-nuevo="Nuevo turno" data-editar="Editar turno">Nuevo turno</h3>
                <button type="button" class="modal-cerrar" data-cerrar-modal aria-label="Cerrar">&times;</button>
            </div>
            <form method="POST" action="/index.php?action=turno_guardar">
                <input type="hidden" name="id_turno" data-id>
                <div class="modal-grid">
                    <div class="campo ancho">
                        <label for="tNombre">Nombre del turno</label>
                        <input type="text" id="tNombre" name="nombre_turno" maxlength="60" placeholder="ej: Turno mañana" required>
                    </div>
                    <div class="campo">
                        <label for="tI1">Periodo 1 · inicio</label>
                        <input type="time" id="tI1" name="inicio_p1" required>
                    </div>
                    <div class="campo">
                        <label for="tF1">Periodo 1 · fin</label>
                        <input type="time" id="tF1" name="fin_p1" required>
                    </div>
                    <div class="campo">
                        <label for="tI2">Periodo 2 · inicio</label>
                        <input type="time" id="tI2" name="inicio_p2">
                    </div>
                    <div class="campo">
                        <label for="tF2">Periodo 2 · fin</label>
                        <input type="time" id="tF2" name="fin_p2">
                    </div>
                    <div class="campo">
                        <label for="tIE">Extra · inicio</label>
                        <input type="time" id="tIE" name="inicio_extra">
                    </div>
                    <div class="campo">
                        <label for="tFE">Extra · fin</label>
                        <input type="time" id="tFE" name="fin_extra">
                    </div>
                </div>
                <p class="modal-nota">El periodo 2 y el extra son opcionales: si los usas, completa el inicio y el fin.</p>
                <div class="modal-acciones">
                    <button type="button" class="btn-secundario" data-cerrar-modal>Cancelar</button>
                    <button type="submit" class="btn-primario">Guardar turno</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($puede('gestionar_cargos')): ?>
    <!-- Modal: crear / editar cargo -->
    <div class="modal-fondo" id="modalCargo" hidden>
        <div class="modal-caja" role="dialog" aria-modal="true" aria-labelledby="tituloCargo">
            <div class="modal-cabecera">
                <h3 id="tituloCargo" data-titulo data-nuevo="Nuevo cargo" data-editar="Editar cargo">Nuevo cargo</h3>
                <button type="button" class="modal-cerrar" data-cerrar-modal aria-label="Cerrar">&times;</button>
            </div>
            <form method="POST" action="/index.php?action=cargo_guardar">
                <input type="hidden" name="id_cargo" data-id>
                <div class="modal-grid">
                    <div class="campo ancho">
                        <label for="cNombre">Nombre del cargo</label>
                        <input type="text" id="cNombre" name="nombre_cargo" maxlength="60" placeholder="ej: Coordinador" required>
                    </div>
                </div>
                <div class="modal-acciones">
                    <button type="button" class="btn-secundario" data-cerrar-modal>Cancelar</button>
                    <button type="submit" class="btn-primario">Guardar cargo</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <div id="toastNotificacion" class="toast-notificacion">
        <span id="toastMensaje">Mensaje aquí</span>
    </div>

    <script>
        // Turnos reales de la base de datos para el formulario de registro
        window.TURNOS = <?= json_encode($turnos ?? [], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    </script>
    <script src="../../public/asset/js/viewsSwitcher.js"></script>
    <script src="../../public/asset/js/permisos.js"></script>
    <script src="../../public/asset/js/roles.js"></script>
    <script src="../../public/asset/js/registroWorkflow.js"></script>
    <script src="../../public/asset/js/importAsistencias.js"></script>
    <script src="../../public/asset/js/dashboard.js"></script>
    <script src="../../public/asset/js/gestion.js"></script>
</body>
</html>
