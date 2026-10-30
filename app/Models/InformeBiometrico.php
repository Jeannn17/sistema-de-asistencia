<?php

namespace App\Models;

use App\Core\ValidacionException;
use DateTime;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Exception as LecturaException;
use RuntimeException;

/**
 * Lee el informe Excel que exporta el reloj biométrico y lo convierte en marcaciones.
 * Aquí viven las reglas del FORMATO del informe; guardarlas en la base de datos
 * es trabajo de AsistenciaModel.
 *
 * Estructura de la hoja "Registros de asistencia":
 *   primeras filas: el rango del informe, ej. "01/02/2026 ~ 28/02/2026" (dd/mm/aaaa)
 *   luego, un bloque de 4 filas por empleado:
 *     fila 0: "ID." | <nro> | "Nombre" | ... | "Depart." | ...
 *     fila 1: números de día (1..31)
 *     fila 2: nombre del día (Dom, Lun...)
 *     fila 3: marcaciones de cada día, ej. "07:07\n18:09" (una hora por línea)
 */
class InformeBiometrico
{
    /** Hoja del informe que trae TODAS las marcaciones crudas por día */
    public const HOJA_REGISTROS = 'Registros de asistencia';

    /**
     * Cuando un día trae una sola marcación no se sabe si es entrada o salida.
     * Antes de esta hora se toma como Entrada; desde esta hora, como Salida.
     */
    private const HORA_CORTE = 12;

    /** Las columnas del informe son números de día: más de 31 días serían ambiguos */
    private const MAX_DIAS = 31;

    /**
     * @param string $tipo 'Xls' o 'Xlsx' (el tipo REAL, detectado por AsistenciaModel::validarArchivo)
     * @return array{marcaciones: array<int, array{nro_registro:int, fecha_hora:string, tipo:string}>,
     *               desde:string, hasta:string, empleados:int}
     */
    public function leer(string $ruta, string $tipo): array
    {
        $filas      = $this->leerHoja($ruta, $tipo);
        $calendario = $this->calendario($filas);

        $marcaciones = [];
        $empleados   = 0;
        $total       = count($filas);

        for ($i = 0; $i < $total; $i++) {
            if (trim((string) ($filas[$i][0] ?? '')) !== 'ID.') {
                continue; // no es el inicio de un bloque de empleado
            }

            $nro = $this->nroRegistro($filas[$i]);
            if ($nro === null) {
                continue;
            }
            $empleados++;

            $filaDias   = $filas[$i + 1] ?? [];
            $filaMarcas = $filas[$i + 3] ?? [];

            foreach ($filaDias as $col => $dia) {
                if (!is_numeric($dia) || !isset($calendario[(int) $dia])) {
                    continue;
                }
                $horas = $this->extraerHoras((string) ($filaMarcas[$col] ?? ''));
                if (!$horas) {
                    continue;
                }

                $fecha = $calendario[(int) $dia];
                foreach ($this->clasificarMarcaciones($horas) as $tipoMovimiento => $hora) {
                    $marcaciones[] = [
                        'nro_registro' => $nro,
                        'fecha_hora'   => $fecha . ' ' . $hora . ':00',
                        'tipo'         => $tipoMovimiento,
                    ];
                }
            }
        }

        if ($empleados === 0) {
            throw new ValidacionException(
                'La hoja "' . self::HOJA_REGISTROS . '" no tiene bloques de empleados (filas que empiezan con "ID.").'
            );
        }

        return [
            'marcaciones' => $marcaciones,
            'desde'       => reset($calendario),
            'hasta'       => end($calendario),
            'empleados'   => $empleados,
        ];
    }

    /** Abre solo la hoja necesaria (el informe trae ~54) y la devuelve como arreglo de valores crudos. */
    private function leerHoja(string $ruta, string $tipo): array
    {
        if (!class_exists(IOFactory::class)) {
            throw new RuntimeException('Falta la librería PhpSpreadsheet: ejecuta "composer install" en la raíz del proyecto.');
        }
        if ($tipo === 'Xlsx' && !class_exists('ZipArchive')) {
            throw new ValidacionException(
                'Este servidor no puede abrir archivos .xlsx porque la extensión "zip" de PHP está desactivada '
                . '(actívala en php.ini con extension=zip y reinicia Apache). Mientras tanto, guarda el informe como .xls.'
            );
        }

        $reader = IOFactory::createReader($tipo);
        $reader->setReadDataOnly(true);                       // más rápido y menos memoria
        $reader->setLoadSheetsOnly([self::HOJA_REGISTROS]);

        try {
            $spreadsheet = $reader->load($ruta);
        } catch (LecturaException $e) {
            error_log('Informe biométrico (lectura): ' . $e->getMessage());
            throw new ValidacionException('No se pudo leer el Excel: puede estar dañado o protegido con contraseña.');
        }

        $hoja = $spreadsheet->getSheetByName(self::HOJA_REGISTROS);
        if ($hoja === null) {
            throw new ValidacionException(
                'El archivo no tiene la hoja "' . self::HOJA_REGISTROS . '". ¿Es el informe exportado del biométrico?'
            );
        }

        // Valores crudos, índices numéricos desde 0
        $filas = $hoja->toArray(null, false, false, false);
        $spreadsheet->disconnectWorksheets();   // libera la memoria del libro
        return $filas;
    }

    /**
     * Lee el rango del informe y devuelve [número de día => 'YYYY-MM-DD'].
     * Soporta informes que cruzan de un mes a otro (ej. 25/01/2026 ~ 24/02/2026).
     */
    private function calendario(array $filas): array
    {
        [$desde, $hasta] = $this->rango($filas);

        if ($hasta < $desde) {
            throw new ValidacionException('El rango de fechas del informe está invertido.');
        }
        if ((int) $desde->diff($hasta)->format('%a') + 1 > self::MAX_DIAS) {
            throw new ValidacionException('El informe abarca más de ' . self::MAX_DIAS . ' días: expórtalo mes por mes.');
        }

        $calendario = [];
        for ($d = clone $desde; $d <= $hasta; $d->modify('+1 day')) {
            $calendario[(int) $d->format('j')] = $d->format('Y-m-d');
        }
        return $calendario;
    }

    /** @return DateTime[] [desde, hasta] */
    private function rango(array $filas): array
    {
        $fecha = '(\d{1,2})/(\d{1,2})/(\d{4})';   // dd/mm/aaaa

        foreach (array_slice($filas, 0, 5) as $fila) {
            foreach ($fila as $valor) {
                if (!is_string($valor) || !preg_match("#{$fecha}\s*~\s*(?:{$fecha})?#", $valor, $m)) {
                    continue;
                }
                $desde = $this->crearFecha((int) $m[3], (int) $m[2], (int) $m[1]);
                // Si no viene la fecha final, se toma hasta el último día del mes inicial
                $hasta = isset($m[6])
                    ? $this->crearFecha((int) $m[6], (int) $m[5], (int) $m[4])
                    : (clone $desde)->modify('last day of this month');
                return [$desde, $hasta];
            }
        }
        throw new ValidacionException('No se encontró el rango de fechas del informe (ej. "01/02/2026 ~ 28/02/2026").');
    }

    private function crearFecha(int $anio, int $mes, int $dia): DateTime
    {
        if (!checkdate($mes, $dia, $anio)) {
            throw new ValidacionException(sprintf('El informe tiene una fecha inválida: %02d/%02d/%04d.', $dia, $mes, $anio));
        }
        return new DateTime(sprintf('%04d-%02d-%02d', $anio, $mes, $dia));
    }

    /** El N° de registro es el primer número de la fila "ID." */
    private function nroRegistro(array $fila): ?int
    {
        foreach ($fila as $col => $valor) {
            if ($col > 0 && is_numeric($valor) && (int) $valor > 0) {
                return (int) $valor;
            }
        }
        return null;
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
}
