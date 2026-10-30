<?php
/**
 * Menú lateral. Solo muestra las secciones que DashboardController dejó en $secciones
 * (ya filtradas por el rol) y marca como activa $seccionActiva.
 */
$iconos = [
    'inicio'        => '<path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>',
    'trabajadores'  => '<path d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>',
    'turnos'        => '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',
    'cargos'        => '<path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>',
    'asistencias'   => '<path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>',
    'importar'      => '<path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>',
    'permisos'      => '<path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>',
    'reportes'      => '<path d="M12 10v6m0 0l-3-3m3 3l3-3M6 20h12a2 2 0 002-2V8.414a1 1 0 00-.293-.707l-4.414-4.414A1 1 0 0014.586 3H6a2 2 0 00-2 2v13a2 2 0 002 2z"/>',
    'seccion'       => '<path d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>',
    'configuracion' => '<path d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>',
];
$grupos = ['principal' => 'Menú principal', 'gestion' => 'Gestión'];
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-scroll">
        <div class="marca">
            <div class="marca-logo"><svg viewBox="0 0 24 24"><path d="M12 14l9-5-9-5-9 5 9 5z"/><path d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/><path d="M12 14v7"/></svg></div>
            <div>
                <div class="marca-nombre">U.E. El Itaca <span class="punto-online"></span></div>
                <div class="marca-sub">Control de personal</div>
            </div>
        </div>

        <?php foreach ($grupos as $grupo => $tituloGrupo): ?>
            <span class="menu-titulo"><?= $e($tituloGrupo) ?></span>
            <nav aria-label="<?= $e($tituloGrupo) ?>">
                <?php foreach ($secciones as $clave => $s): ?>
                    <?php if ($s['grupo'] !== $grupo) continue; ?>
                    <a href="<?= url('dashboard/index/' . $clave) ?>" class="nav-link<?= $clave === $seccionActiva ? ' active' : '' ?>"
                       data-target="<?= $e($s['id']) ?>"><svg viewBox="0 0 24 24"><?= $iconos[$clave] ?? '' ?></svg><span><?= $e($s['titulo']) ?></span>
                        <?php if ($clave === 'permisos' && ($perm['total'] ?? 0) > 0): ?><span class="insignia"><?= (int) $perm['total'] ?> vigentes</span><?php endif; ?>
                        <?php if ($clave === 'seccion' && !empty($solicitudesPendientes)): ?><span class="insignia"><?= (int) $solicitudesPendientes ?> por aprobar</span><?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </nav>
        <?php endforeach; ?>
    </div>

    <div class="sidebar-pie">
        <div class="usuario-caja">
            <div class="usuario-avatar"><?= $e($iniciales) ?></div>
            <div>
                <div class="usuario-nombre"><?= $e($nombreUsuario) ?></div>
                <div class="usuario-rol"><?= $e($rolUsuario) ?></div>
            </div>
        </div>
        <form method="POST" action="<?= url('auth/logout') ?>" class="form-cerrar-sesion">
        <?= csrf_campo() ?>
        <button type="submit" class="cerrar-sesion"><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>Cerrar sesión</button>
        </form>
    </div>
</aside>
