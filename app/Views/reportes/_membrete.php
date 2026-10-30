<?php
/**
 * Membrete y pie que se repiten en todas las páginas de los reportes PDF.
 * Usa $institucion, $generadoPor, $generadoEl y $e (los define la plantilla que lo incluye).
 * Los estilos header, footer y .membrete van en la plantilla.
 */

// Logo opcional (config/institucion.php): se incrusta para que Dompdf no lea rutas externas
$logo = null;
if (!empty($institucion['logo']) && is_file(RAIZ . '/public/' . $institucion['logo'])) {
    $ruta = RAIZ . '/public/' . $institucion['logo'];
    $logo = 'data:' . mime_content_type($ruta) . ';base64,' . base64_encode(file_get_contents($ruta));
}
?>
<header>
    <table class="membrete">
        <tr>
            <td class="logo"><?php if ($logo): ?><img src="<?= $logo ?>" alt=""><?php endif; ?></td>
            <td class="textos">
                <?php foreach ($institucion['encabezado'] as $linea): ?>
                    <div class="linea"><?= $e($linea) ?></div>
                <?php endforeach; ?>
                <div class="nombre"><?= $e($institucion['nombre']) ?></div>
                <div class="sistema"><?= $e($institucion['sistema']) ?></div>
            </td>
            <td class="logo"></td>
        </tr>
    </table>
</header>

<footer>
    Generado el <?= $e($generadoEl) ?><?= $generadoPor !== '' ? ' por ' . $e($generadoPor) : '' ?>
</footer>
