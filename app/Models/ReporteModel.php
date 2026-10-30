<?php

namespace App\Models;

use App\Core\ValidacionException;
use App\Core\Validador;
use DateTime;
use PDO;

/**
 * Reporte de asistencia por departamento (cargo) y período: semanal, quincenal o mensual.
 *
 * Reglas del reporte:
 *  - Días laborables: lunes a viernes del período, sin contar días futuros
 *    ni los anteriores a la fecha de ingreso del trabajador (salvo que haya marcado ese día).
 *  - Asistencia: el día tiene al menos una marcación (entrada o salida).
 *  - Retardo: la entrada pasa de inicio_p1 del turno + AsistenciaModel::TOLERANCIA_MIN,
 *    salvo que ese día tenga un permiso de llegada tarde.
 *  - Justificado: día sin marcación cubierto por un permiso o reposo.
 *  - Inasistencia: día laborable sin marcación y sin justificación.
 *  - % de asistencia = (asistencias + justificados) / días laborables.
 */
class ReporteModel
{
    public const FRECUENCIAS = [
        'semanal'   => 'Semanal',
        'quincenal' => 'Quincenal',
        'mensual'   => 'Mensual',
    ];

    /** Contenido del PDF: el resumen por trabajador o la planilla de entradas y salidas de cada día */
    public const TIPOS = [
        'resumen'   => 'Resumen de asistencia',
        'detallado' => 'Entradas y salidas por día',
    ];

    private const DIAS_SEMANA = [1 => 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];

    /** Días por tabla en la planilla detallada: el mes se parte en dos (1-15 y 16-fin) para que quepa */
    private const DIAS_POR_BLOQUE = 16;

    private const MESES = [1 => 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio',
                           'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];

    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /** Departamentos (cargos) para el selector del formulario. */
    public function departamentos(): array
    {
        return $this->db->query('SELECT id_cargo, nombre_cargo FROM cargo ORDER BY activo DESC, nombre_cargo ASC')
                        ->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Valida los filtros del formulario y devuelve:
     *   ['tipo' => 'resumen'|'detallado', 'id_cargo' => int (0 = todos), 'departamento' => string, 'frecuencia' => string,
     *    'desde' => 'Y-m-d', 'hasta' => 'Y-m-d', 'periodo' => 'texto para el título', 'incluir_detalle' => bool]
     */
    public function validarFiltros(array $e): array
    {
        $tipo       = Validador::opcion($e['tipo'] ?? 'resumen', array_keys(self::TIPOS), 'un reporte');
        $idCargo    = Validador::entero($e['id_cargo'] ?? '0', 'el departamento', false, 0) ?? 0;
        $frecuencia = Validador::opcion($e['frecuencia'] ?? '', array_keys(self::FRECUENCIAS), 'un tipo de reporte');
        $fecha      = Validador::fecha($e['fecha'] ?? '', 'la fecha del período');

        $departamento = 'Todos los departamentos';
        if ($idCargo > 0) {
            $stmt = $this->db->prepare('SELECT nombre_cargo FROM cargo WHERE id_cargo = :id');
            $stmt->execute([':id' => $idCargo]);
            $departamento = $stmt->fetchColumn();
            if ($departamento === false) {
                throw new ValidacionException('El departamento seleccionado no existe.');
            }
        }

        $periodo = self::calcularPeriodo($frecuencia, $fecha);
        if ($periodo['desde'] > date('Y-m-d')) {
            throw new ValidacionException('El período elegido todavía no ha empezado.');
        }

        return [
            'tipo'            => $tipo,
            'id_cargo'        => $idCargo,
            'departamento'    => $departamento,
            'frecuencia'      => $frecuencia,
            'desde'           => $periodo['desde'],
            'hasta'           => $periodo['hasta'],
            'periodo'         => $periodo['etiqueta'],
            'incluir_detalle' => $tipo === 'resumen' && !empty($e['detalle']),
        ];
    }

    /**
     * Período que contiene a $fecha:
     *   semanal   -> lunes a domingo
     *   quincenal -> del 1 al 15, o del 16 al último día del mes
     *   mensual   -> el mes completo
     * @return array{desde:string, hasta:string, etiqueta:string}
     */
    public static function calcularPeriodo(string $frecuencia, string $fecha): array
    {
        $d    = new DateTime($fecha);
        $mes  = self::MESES[(int) $d->format('n')] . ' ' . $d->format('Y');

        switch ($frecuencia) {
            case 'semanal':
                $desde = (clone $d)->modify('monday this week');
                $hasta = (clone $desde)->modify('+6 days');
                $etiqueta = 'Semana del ' . $desde->format('d/m/Y') . ' al ' . $hasta->format('d/m/Y');
                break;
            case 'quincenal':
                [$anio, $numMes] = [(int) $d->format('Y'), (int) $d->format('n')];
                $primera  = (int) $d->format('j') <= 15;
                $desde    = (clone $d)->setDate($anio, $numMes, $primera ? 1 : 16);
                $hasta    = $primera ? (clone $d)->setDate($anio, $numMes, 15)
                                     : (clone $d)->modify('last day of this month');
                $etiqueta = ($primera ? '1ra' : '2da') . ' quincena de ' . $mes
                          . ' (' . $desde->format('d/m') . ' al ' . $hasta->format('d/m/Y') . ')';
                break;
            case 'mensual':
                $desde    = (clone $d)->modify('first day of this month');
                $hasta    = (clone $d)->modify('last day of this month');
                $etiqueta = 'Mes de ' . $mes;
                break;
            default:
                throw new ValidacionException('Tipo de reporte desconocido.');
        }

        return ['desde' => $desde->format('Y-m-d'), 'hasta' => $hasta->format('Y-m-d'), 'etiqueta' => $etiqueta];
    }

    /**
     * Arma el reporte con los filtros ya validados.
     * @return array{departamentos: array, totales: array, sin_datos: bool}
     */
    public function generar(array $f): array
    {
        $departamentos = [];
        foreach ($this->consultarDias($f) as $fila) {
            $dep = $fila['nombre_cargo'] ?? 'Sin departamento';
            $id  = (int) $fila['id_persona'];

            if (!isset($departamentos[$dep]['trabajadores'][$id])) {
                $departamentos[$dep]['trabajadores'][$id] = [
                    'cedula'  => $fila['cedula'],
                    'nombre'  => trim($fila['apellido'] . ' ' . $fila['nombre']),
                    'turno'   => $fila['nombre_turno'] ?? '—',
                    'resumen' => $this->resumenVacio(),
                    'detalle' => [],
                ];
            }
            $t = &$departamentos[$dep]['trabajadores'][$id];
            $estado = $this->clasificarDia($fila);
            $t['resumen']['laborables']++;
            $t['resumen'][$estado['clave']]++;
            if ($estado['retardo']) {
                $t['resumen']['retardos']++;
            }
            if ($f['incluir_detalle']) {
                $t['detalle'][] = [
                    'fecha'   => $fila['fecha'],
                    'entrada' => $fila['entrada'],
                    'salida'  => $fila['salida'],
                    'estado'  => $estado['texto'],
                ];
            }
            unset($t);
        }

        // Porcentajes y totales por departamento y generales
        $general = $this->resumenVacio();
        foreach ($departamentos as &$dep) {
            $dep['totales'] = $this->resumenVacio();
            foreach ($dep['trabajadores'] as &$t) {
                $t['resumen']['porcentaje'] = $this->porcentaje($t['resumen']);
                $dep['totales'] = $this->sumar($dep['totales'], $t['resumen']);
            }
            unset($t);
            $dep['totales']['porcentaje'] = $this->porcentaje($dep['totales']);
            $dep['totales']['trabajadores'] = count($dep['trabajadores']);
            $general = $this->sumar($general, $dep['totales']);
            $general['trabajadores'] = ($general['trabajadores'] ?? 0) + $dep['totales']['trabajadores'];
        }
        unset($dep);
        $general['porcentaje']   = $this->porcentaje($general);
        $general['trabajadores'] = $general['trabajadores'] ?? 0;

        return ['departamentos' => $departamentos, 'totales' => $general, 'sin_datos' => !$departamentos];
    }

    /**
     * Planilla de entradas y salidas: una fila por trabajador y una columna por cada día del período
     * (también sábados y domingos, por si alguien marcó). Cada celda trae la entrada, la salida y su estado:
     *   asistio | retardo | justificado | falta | descanso (fin de semana sin marcar)
     * Los días anteriores al ingreso del trabajador (sin marcación) no traen celda.
     * @return array{dias: array, bloques: array, trabajadores: array, totales: array, sin_datos: bool}
     */
    public function generarDetallado(array $f): array
    {
        // Columnas: todos los días del período hasta hoy (igual que la consulta: no hay días futuros)
        $dias  = [];
        $hasta = min($f['hasta'], date('Y-m-d'));
        for ($d = $f['desde']; $d <= $hasta; $d = date('Y-m-d', strtotime($d . ' +1 day'))) {
            $dias[$d] = $this->describirDia($d);
        }

        $trabajadores = [];
        foreach ($this->consultarDias($f, false) as $fila) {
            $fecha = $fila['fecha'];
            $id    = (int) $fila['id_persona'];

            $trabajadores[$id] ??= [
                'nro_registro' => $fila['nro_registro'],
                'cedula'       => $fila['cedula'],
                'apellido'     => $fila['apellido'],
                'nombre'       => $fila['nombre'],
                'departamento' => $fila['nombre_cargo'] ?? 'Sin departamento',
                'dias'         => [],
                'resumen'      => ['asistencias' => 0, 'retardos' => 0, 'faltas' => 0],
            ];
            $t = &$trabajadores[$id];
            $t['dias'][$fecha] = $this->celdaDetallada($fila, $dias[$fecha]['fin_semana']);

            $estado = $t['dias'][$fecha]['estado'];
            if ($estado === 'asistio' || $estado === 'retardo') {
                $t['resumen']['asistencias']++;
            }
            if ($estado === 'retardo') {
                $t['resumen']['retardos']++;
            }
            if ($estado === 'falta') {
                $t['resumen']['faltas']++;
            }
            unset($t);
        }

        $totales = ['trabajadores' => count($trabajadores), 'asistencias' => 0, 'retardos' => 0, 'faltas' => 0];
        foreach ($trabajadores as $t) {
            foreach (['asistencias', 'retardos', 'faltas'] as $k) {
                $totales[$k] += $t['resumen'][$k];
            }
        }

        return [
            'dias'         => $dias,
            'bloques'      => $this->partirEnBloques(array_keys($dias)),
            'trabajadores' => array_values($trabajadores),
            'totales'      => $totales,
            'sin_datos'    => !$trabajadores,
        ];
    }

    /** ['fecha' => 'Y-m-d', 'numero' => '05', 'nombre' => 'Lun', 'fin_semana' => bool] */
    private function describirDia(string $fecha): array
    {
        $isoDia = (int) date('N', strtotime($fecha));
        return [
            'fecha'      => $fecha,
            'numero'     => date('d', strtotime($fecha)),
            'nombre'     => self::DIAS_SEMANA[$isoDia],
            'fin_semana' => $isoDia >= 6,
        ];
    }

    /** Estado de un día en la planilla (usa las mismas reglas que el resumen). */
    private function celdaDetallada(array $fila, bool $finSemana): array
    {
        $celda = ['entrada' => $fila['entrada'], 'salida' => $fila['salida'], 'justificacion' => null];
        $marco = $fila['entrada'] !== null || $fila['salida'] !== null;

        if (!$marco && $finSemana) {
            return $celda + ['estado' => 'descanso'];
        }

        $clasificado = $this->clasificarDia($fila);
        switch ($clasificado['clave']) {
            case 'justificados':
                $celda['justificacion'] = $fila['justificacion'];
                return $celda + ['estado' => 'justificado'];
            case 'inasistencias':
                return $celda + ['estado' => 'falta'];
            default:
                return $celda + ['estado' => $clasificado['retardo'] ? 'retardo' : 'asistio'];
        }
    }

    /**
     * Columnas de días por tabla: semana y quincena caben en una; el mes se parte en 1-15 y 16-fin.
     * @param string[] $fechas
     * @return string[][]
     */
    private function partirEnBloques(array $fechas): array
    {
        if (count($fechas) <= self::DIAS_POR_BLOQUE) {
            return $fechas ? [$fechas] : [];
        }
        $primera = array_values(array_filter($fechas, fn($f) => (int) substr($f, 8, 2) <= 15));
        $segunda = array_values(array_filter($fechas, fn($f) => (int) substr($f, 8, 2) > 15));
        return array_values(array_filter([$primera, $segunda]));
    }

    /**
     * Una fila por trabajador activo y día del período (por defecto solo los laborables), con su
     * primera entrada, su última salida y la justificación que cubre ese día (si hay).
     */
    private function consultarDias(array $f, bool $soloLaborables = true): array
    {
        $filtroDias = $soloLaborables ? 'WHERE extract(isodow FROM g) < 6' : '';   // lunes a viernes

        $sql = "WITH dias AS (
                    SELECT g::date AS fecha
                    FROM generate_series(CAST(:desde AS date), LEAST(CAST(:hasta AS date), CURRENT_DATE), interval '1 day') g
                    $filtroDias
                ),
                trabajadores AS (
                    SELECT p.id_persona, p.nro_registro, p.cedula, p.nombre, p.apellido, p.fecha_ingreso,
                           c.nombre_cargo, t.nombre_turno, t.inicio_p1
                    FROM personal p
                    LEFT JOIN cargo c ON c.id_cargo = p.id_cargo
                    LEFT JOIN turno t ON t.id_turno = p.id_turno
                    WHERE p.activo = true
                      AND (CAST(:cargo AS integer) = 0 OR p.id_cargo = CAST(:cargo2 AS integer))
                ),
                marcas AS (
                    SELECT a.id_persona, a.fecha_hora::date AS fecha,
                           min(a.fecha_hora) FILTER (WHERE a.tipo_movimiento = 'Entrada') AS entrada,
                           max(a.fecha_hora) FILTER (WHERE a.tipo_movimiento = 'Salida')  AS salida
                    FROM asistencia a
                    WHERE a.fecha_hora >= CAST(:desde2 AS date)
                      AND a.fecha_hora <  CAST(:hasta2 AS date) + 1
                    GROUP BY a.id_persona, a.fecha_hora::date
                )
                SELECT t.id_persona, t.nro_registro, t.cedula, t.nombre, t.apellido, t.nombre_cargo, t.nombre_turno, t.inicio_p1,
                       d.fecha,
                       to_char(m.entrada, 'HH24:MI') AS entrada,
                       to_char(m.salida,  'HH24:MI') AS salida,
                       (SELECT j.tipo FROM justificacion j
                        WHERE j.id_persona = t.id_persona AND d.fecha BETWEEN j.fecha_inicio AND j.fecha_fin
                        ORDER BY j.id_justificacion DESC LIMIT 1) AS justificacion
                FROM trabajadores t
                CROSS JOIN dias d
                LEFT JOIN marcas m ON m.id_persona = t.id_persona AND m.fecha = d.fecha
                -- Antes del ingreso no hay inasistencias, pero si marcó ese día, sí cuenta
                WHERE t.fecha_ingreso IS NULL OR d.fecha >= t.fecha_ingreso OR m.id_persona IS NOT NULL
                ORDER BY t.nombre_cargo ASC NULLS LAST, t.apellido ASC, t.nombre ASC, d.fecha ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':desde'  => $f['desde'], ':hasta'  => $f['hasta'],
            ':desde2' => $f['desde'], ':hasta2' => $f['hasta'],
            ':cargo'  => $f['id_cargo'], ':cargo2' => $f['id_cargo'],
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return array{clave:string, texto:string, retardo:bool} */
    private function clasificarDia(array $fila): array
    {
        $tipoJust  = $fila['justificacion'];
        $etiqueta  = $tipoJust !== null ? (JustificacionModel::TIPOS[$tipoJust][0] ?? $tipoJust) : null;
        $marco     = $fila['entrada'] !== null || $fila['salida'] !== null;

        if (!$marco) {
            return $tipoJust
                ? ['clave' => 'justificados', 'texto' => 'Justificado: ' . $etiqueta, 'retardo' => false]
                : ['clave' => 'inasistencias', 'texto' => 'Inasistencia', 'retardo' => false];
        }

        $retraso = $this->minutosDeRetraso($fila['entrada'], $fila['inicio_p1']);
        if ($retraso > AsistenciaModel::TOLERANCIA_MIN && $tipoJust !== 'llegada_tarde') {
            return ['clave' => 'asistencias', 'texto' => "Retardo ({$retraso} min)", 'retardo' => true];
        }

        $texto = $fila['entrada'] === null ? 'Sin marcación de entrada'
               : ($fila['salida'] === null ? 'Sin marcación de salida' : 'Asistió');
        if ($tipoJust) {
            $texto .= ' · ' . $etiqueta;
        }
        return ['clave' => 'asistencias', 'texto' => $texto, 'retardo' => false];
    }

    private function minutosDeRetraso(?string $entrada, ?string $inicioTurno): int
    {
        if ($entrada === null || $inicioTurno === null) {
            return 0;
        }
        [$he, $me] = array_map('intval', explode(':', $entrada));
        [$ht, $mt] = array_map('intval', explode(':', $inicioTurno));
        return ($he * 60 + $me) - ($ht * 60 + $mt);
    }

    private function resumenVacio(): array
    {
        return ['laborables' => 0, 'asistencias' => 0, 'retardos' => 0, 'justificados' => 0, 'inasistencias' => 0];
    }

    private function sumar(array $a, array $b): array
    {
        foreach (array_keys($this->resumenVacio()) as $k) {
            $a[$k] += $b[$k];
        }
        return $a;
    }

    private function porcentaje(array $r): ?float
    {
        return $r['laborables'] > 0
            ? round(($r['asistencias'] + $r['justificados']) * 100 / $r['laborables'], 1)
            : null;
    }
}
