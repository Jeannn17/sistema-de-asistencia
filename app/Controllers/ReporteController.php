<?php

namespace App\Controllers;

use App\Core\Database;
use App\Core\Flash;
use App\Core\ValidacionException;
use App\Core\View;
use App\Models\ReporteModel;
use Dompdf\Dompdf;
use Dompdf\Options;
use PDOException;
use Throwable;

/**
 * Reportes de asistencia en PDF:
 *   1. ReporteModel::validarFiltros -> tipo, departamento, frecuencia y período
 *   2. ReporteModel::generar           (resumen)   -> datos y totales por trabajador
 *      ReporteModel::generarDetallado  (detallado) -> entrada y salida de cada día
 *   3. View::capturar               -> HTML de app/Views/reportes/<plantilla>.php
 *   4. Dompdf                       -> PDF descargable
 */
class ReporteController
{
    private ReporteModel $model;

    public function __construct()
    {
        $this->model = new ReporteModel(Database::conectar());
    }

    /** Por tipo de reporte: plantilla, orientación de la hoja y prefijo del archivo */
    private const PLANTILLAS = [
        'resumen'   => ['vista' => 'reportes/asistencia_pdf',        'hoja' => 'portrait',  'archivo' => 'asistencia'],
        'detallado' => ['vista' => 'reportes/entradas_salidas_pdf',  'hoja' => 'landscape', 'archivo' => 'entradas_salidas'],
    ];

    /** GET /reporte/pdf?tipo=..&id_cargo=..&frecuencia=..&fecha=..[&detalle=1]  (solo lee datos: no necesita POST) */
    public function pdf(): void
    {
        try {
            $filtros = $this->model->validarFiltros($_GET);
            $reporte = $filtros['tipo'] === 'detallado'
                ? $this->model->generarDetallado($filtros)
                : $this->model->generar($filtros);
        } catch (ValidacionException $e) {
            Flash::error('⚠️ ' . $e->getMessage());
            redirigir('dashboard/index', ['vista' => 'reportes']);
        } catch (PDOException $e) {
            error_log('Reporte de asistencia: ' . $e->getMessage());
            Flash::error('❌ No se pudo consultar la asistencia para el reporte.');
            redirigir('dashboard/index', ['vista' => 'reportes']);
        }

        if ($reporte['sin_datos']) {
            Flash::error('⚠️ No hay trabajadores activos en ' . $filtros['departamento'] . ' para ese período.');
            redirigir('dashboard/index', ['vista' => 'reportes']);
        }

        $plantilla = self::PLANTILLAS[$filtros['tipo']];
        $html = View::capturar($plantilla['vista'], [
            'filtros'     => $filtros,
            'reporte'     => $reporte,
            'institucion' => require RAIZ . '/config/institucion.php',
            'generadoPor' => $_SESSION['nombre'] ?? '',
            'generadoEl'  => date('d/m/Y h:i a'),
        ]);

        try {
            $this->descargar($html, $this->nombreArchivo($filtros, $plantilla['archivo']), $plantilla['hoja']);
        } catch (Throwable $e) {
            error_log('Reporte de asistencia (PDF): ' . $e->getMessage());
            Flash::error('❌ No se pudo generar el PDF. Revisa que Dompdf esté instalado (composer install).');
            redirigir('dashboard/index', ['vista' => 'reportes']);
        }
    }

    private function descargar(string $html, string $archivo, string $orientacion): void
    {
        $opciones = new Options();
        $opciones->set('defaultFont', 'DejaVu Sans');   // incluye acentos y la ñ
        $opciones->set('isRemoteEnabled', false);       // el PDF nunca descarga recursos de internet
        $opciones->set('chroot', RAIZ . '/public');     // solo puede leer imágenes de public/

        $dompdf = new Dompdf($opciones);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('letter', $orientacion);
        $dompdf->render();

        // Número de página en el pie de todas las hojas (esquina inferior derecha, vertical u horizontal)
        $hoja = $dompdf->getCanvas();
        $hoja->page_text($hoja->get_width() - 92, $hoja->get_height() - 32, 'Página {PAGE_NUM} de {PAGE_COUNT}', 'DejaVu Sans', 7, [0.39, 0.45, 0.55]);

        $dompdf->stream($archivo, ['Attachment' => true]);
        exit;
    }

    /** ej. asistencia_mensual_todos_2026-02-01.pdf  o  entradas_salidas_semanal_obrero_2026-02-02.pdf */
    private function nombreArchivo(array $f, string $prefijo): string
    {
        $departamento = $f['id_cargo'] > 0
            ? preg_replace('/[^a-z0-9]+/', '-', strtolower(iconv('UTF-8', 'ASCII//TRANSLIT', $f['departamento']) ?: 'departamento'))
            : 'todos';
        return sprintf('%s_%s_%s_%s.pdf', $prefijo, $f['frecuencia'], trim($departamento, '-'), $f['desde']);
    }
}
