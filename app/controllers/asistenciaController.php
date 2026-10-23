<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

class asistenciaController
{
    /** Hoja del informe del biométrico que trae TODAS las marcaciones crudas por día */
    private const HOJA_REGISTROS = 'Registros de asistencia';

    /**
     * Cuando un día trae una sola marcación no se sabe si es entrada o salida.
     * Antes de esta hora se toma como Entrada; desde esta hora, como Salida.
     */
    private const HORA_CORTE = 12;

    private asistenciaModel $model;

    public function __construct(PDO $db)
    {
        $this->model = new asistenciaModel($db);
    }

    public function validarSubidaExcel(?array $file): array
    {
        if (!$file || empty($file['name'])) {
            return ['valido' => false, 'mensaje' => 'No se ha seleccionado ningún archivo'];
        }
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['valido' => false, 'mensaje' => 'Error al subir el archivo. Error=' . $file['error']];
        }

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, ['xlsx', 'xls'], true)) {
            return ['valido' => false, 'mensaje' => 'Formato incorrecto. Sube un archivo .xlsx o .xls.'];
        }

        return ['valido' => true, 'mensaje' => 'Archivo válido'];
    }

    /** Acción del router: index.php?action=asistencia (POST con archivo_excel) */
    public function importar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirigir('import_error');
        }

        $file       = $_FILES['archivo_excel'] ?? null;
        $validacion = $this->validarSubidaExcel($file);
        if (!$validacion['valido']) {
            $this->redirigir('import_invalido');
        }

        try {
            $marcaciones = $this->leerMarcaciones($file['tmp_name']);

            if (empty($marcaciones)) {
                $this->redirigir('import_vacio');
            }

            $resultado = $this->model->registrarMarcaciones($marcaciones);

            $this->redirigir('import_ok', [
                'insertados' => $resultado['insertados'],
                'duplicados' => $resultado['duplicados'],
                'omitidos'   => count($resultado['sin_registro']), // empleados del Excel que no están en 'personal'
            ]);
        } catch (Throwable $e) {
            error_log('Error al importar asistencia: ' . $e->getMessage());
            $this->redirigir('import_error');
        }
    }

    /**
     * Lee la hoja "Registros de asistencia" y la convierte en una lista de marcaciones.
     *
     * Estructura de la hoja: un bloque de 4 filas por empleado
     *   fila 0: "ID." | <nro> | "Nombre" | ... | "Depart." | ...
     *   fila 1: números de día (1..31)
     *   fila 2: nombre del día (Dom, Lun...)
     *   fila 3: marcaciones de cada día, ej. "07:07\n18:09" (una hora por línea)
     *
     * @return array<int, array{nro_registro:int, fecha_hora:string, tipo:string}>
     */
    private function leerMarcaciones(string $ruta): array
    {
        $reader = IOFactory::createReaderForFile($ruta);
        $reader->setReadDataOnly(true);                       // más rápido y menos memoria
        $reader->setLoadSheetsOnly([self::HOJA_REGISTROS]);   // el informe trae ~54 hojas, solo se necesita una

        $spreadsheet = $reader->load($ruta);
        $hoja        = $spreadsheet->getSheetByName(self::HOJA_REGISTROS);
        if ($hoja === null) {
            throw new RuntimeException('El archivo no contiene la hoja "' . self::HOJA_REGISTROS . '".');
        }

        // Valores crudos, índices numéricos desde 0
        $filas = $hoja->toArray(null, false, false, false);
        [$anio, $mes] = $this->obtenerAnioMes($filas);

        $marcaciones = [];
        $total       = count($filas);

        for ($i = 0; $i < $total; $i++) {
            if (trim((string) ($filas[$i][0] ?? '')) !== 'ID.') {
                continue; // no es el inicio de un bloque de empleado
            }

            // El N° de registro es el primer número de la fila "ID."
            $nro = null;
            foreach ($filas[$i] as $col => $valor) {
                if ($col > 0 && is_numeric($valor)) {
                    $nro = (int) $valor;
                    break;
                }
            }
            if ($nro === null) {
                continue;
            }

            $filaDias   = $filas[$i + 1] ?? [];
            $filaMarcas = $filas[$i + 3] ?? [];

            foreach ($filaDias as $col => $dia) {
                if (!is_numeric($dia)) {
                    continue;
                }
                $dia   = (int) $dia;
                $celda = trim((string) ($filaMarcas[$col] ?? ''));

                if ($celda === '' || !checkdate($mes, $dia, $anio)) {
                    continue;
                }

                $horas = $this->extraerHoras($celda);
                if (empty($horas)) {
                    continue;
                }

                $fecha = sprintf('%04d-%02d-%02d', $anio, $mes, $dia);

                foreach ($this->clasificarMarcaciones($horas) as $tipo => $hora) {
                    $marcaciones[] = [
                        'nro_registro' => $nro,
                        'fecha_hora'   => $fecha . ' ' . $hora . ':00',
                        'tipo'         => $tipo,
                    ];
                }
            }
        }

        return $marcaciones;
    }

    /** Lee el rango "01/02/2026 ~ 28/02/2026" (dd/mm/aaaa) de las primeras filas. */
    private function obtenerAnioMes(array $filas): array
    {
        foreach (array_slice($filas, 0, 5) as $fila) {
            foreach ($fila as $valor) {
                if (is_string($valor) && preg_match('#(\d{1,2})/(\d{1,2})/(\d{4})\s*~#', $valor, $m)) {
                    return [(int) $m[3], (int) $m[2]];
                }
            }
        }
        throw new RuntimeException('No se encontró el rango de fechas del informe.');
    }

    /** "07:07\n18:09" -> ['07:07', '18:09'] (ordenadas y sin repetidas) */
    private function extraerHoras(string $texto): array
    {
        preg_match_all('/\b([01]?\d|2[0-3]):([0-5]\d)\b/', $texto, $m, PREG_SET_ORDER);

        $horas = [];
        foreach ($m as $par) {
            $horas[] = sprintf('%02d:%02d', $par[1], $par[2]);
        }
        $horas = array_values(array_unique($horas));
        sort($horas);
        return $horas;
    }

    /**
     * 2 o más marcaciones: la primera es Entrada y la última Salida (las intermedias se ignoran).
     * 1 sola marcación: se decide por la hora (HORA_CORTE).
     *
     * @return array<string,string> ['Entrada' => 'HH:MM', 'Salida' => 'HH:MM']
     */
    private function clasificarMarcaciones(array $horas): array
    {
        if (count($horas) >= 2) {
            return ['Entrada' => $horas[0], 'Salida' => end($horas)];
        }

        $hora = (int) substr($horas[0], 0, 2);
        return $hora < self::HORA_CORTE
            ? ['Entrada' => $horas[0]]
            : ['Salida'  => $horas[0]];
    }

    private function redirigir(string $status, array $extra = []): void
    {
        $query = http_build_query(['action' => 'dashboard', 'status' => $status] + $extra);
        header('Location: index.php?' . $query);
        exit;
    }
}
