<?php

namespace App\Controllers;

use App\Core\Database;
use App\Core\Flash;
use App\Core\ValidacionException;
use App\Models\AsistenciaModel;
use App\Models\InformeBiometrico;
use Throwable;

/**
 * Flujo de importación del informe del biométrico:
 *   1. AsistenciaModel::validarArchivo     -> ¿es un Excel válido? (tamaño, extensión, firma)
 *   2. InformeBiometrico::leer             -> Excel -> lista de marcaciones
 *   3. AsistenciaModel::registrarMarcaciones -> guarda en la BD (sin repetir lo ya importado)
 * El controlador solo coordina los pasos y decide a dónde volver.
 */
class AsistenciaController
{
    /** Cuántos N° de registro sin trabajador se listan en el aviso */
    private const MAX_LISTADOS = 15;

    private AsistenciaModel $model;

    public function __construct()
    {
        $this->model = new AsistenciaModel(Database::conectar());
    }

    /** POST /asistencia/importar  (campo archivo_excel) */
    public function importar(): void
    {
        $archivo = $_FILES['archivo_excel'] ?? null;

        try {
            $tipo      = $this->model->validarArchivo($archivo);
            $informe   = (new InformeBiometrico())->leer($archivo['tmp_name'], $tipo);
            $resultado = $this->model->registrarMarcaciones($informe['marcaciones']);
        } catch (ValidacionException $e) {
            Flash::error('⚠️ ' . $e->getMessage());
            $this->redirigir('import_invalido', ['vista' => 'importar']);
        } catch (Throwable $e) {
            error_log('Error al importar asistencia: ' . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine());
            $this->redirigir('import_error', ['vista' => 'importar']);
        }

        $this->avisarResultado($informe, $resultado);
        $this->redirigir('import_ok', [
            'insertados' => $resultado['insertados'],
            'duplicados' => $resultado['duplicados'],
            'omitidos'   => count($resultado['sin_registro']), // empleados del Excel que no están en 'personal'
        ]);
    }

    /** Arma el aviso con el resumen de la importación. */
    private function avisarResultado(array $informe, array $r): void
    {
        $mensaje = sprintf(
            'Informe del %s al %s: %d marcaciones nuevas, %d ya estaban importadas.',
            date('d/m/Y', strtotime($informe['desde'])),
            date('d/m/Y', strtotime($informe['hasta'])),
            $r['insertados'],
            $r['duplicados']
        );

        $sinRegistro = $r['sin_registro'];
        if ($sinRegistro) {
            $lista = implode(', ', array_slice($sinRegistro, 0, self::MAX_LISTADOS));
            if (count($sinRegistro) > self::MAX_LISTADOS) {
                $lista .= ' y ' . (count($sinRegistro) - self::MAX_LISTADOS) . ' más';
            }
            $mensaje .= " Se omitieron los N° de registro sin trabajador activo: {$lista}."
                . ' Regístralos con ese N° y vuelve a importar el archivo.';
        }

        // Si no entró nada nuevo por falta de trabajadores, se avisa como error
        if ($r['insertados'] === 0 && $sinRegistro) {
            Flash::error('⚠️ ' . $mensaje);
        } else {
            Flash::exito('✅ ' . $mensaje);
        }
    }

    private function redirigir(string $status, array $extra = []): void
    {
        \redirigir('dashboard/index', ['status' => $status] + $extra);
    }
}
