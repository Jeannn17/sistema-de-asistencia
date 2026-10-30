<?php

namespace App\Models;

use DateTime;
use PDO;
use PDOException;

/** Consulta de asistencias (una fila por trabajador y día: entrada, salida y justificación). */
class ConsultaModel
{
    private const LIMITE = 1000;

    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /** Toma q, desde y hasta de $_GET. Si no vienen fechas, usa el mes del último día importado. */
    public function normalizarFiltros(array $get): array
    {
        $q     = mb_substr(trim((string) ($get['q'] ?? '')), 0, 60);
        $desde = $this->fecha($get['desde'] ?? '');
        $hasta = $this->fecha($get['hasta'] ?? '');

        if ($desde === null || $hasta === null) {
            try {
                $ultimo = $this->db->query('SELECT max(fecha_hora)::date FROM asistencia')->fetchColumn();
            } catch (PDOException $e) {
                $ultimo = null;
            }
            if ($ultimo) {
                $hasta = $hasta ?? $ultimo;
                $desde = $desde ?? date('Y-m-01', strtotime($ultimo));
            } else {
                $hasta = $hasta ?? date('Y-m-d');
                $desde = $desde ?? date('Y-m-01');
            }
        }
        if ($desde > $hasta) {
            [$desde, $hasta] = [$hasta, $desde];
        }
        return ['q' => $q, 'desde' => $desde, 'hasta' => $hasta];
    }

    public function asistencias(array $f): array
    {
        $params = [':desde' => $f['desde'], ':hasta' => $f['hasta']];
        // Rango sobre la columna tal cual (no fecha_hora::date) para que use el índice ix_asistencia_fecha_hora
        $where  = 'a.fecha_hora >= CAST(:desde AS date) AND a.fecha_hora < CAST(:hasta AS date) + 1';

        if ($f['q'] !== '') {
            $where .= " AND (p.cedula ILIKE :q1 OR (p.nombre || ' ' || p.apellido) ILIKE :q2
                             OR (p.apellido || ' ' || p.nombre) ILIKE :q3)";
            $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $f['q']) . '%';
            $params[':q1'] = $params[':q2'] = $params[':q3'] = $like;
        }

        // La justificación se busca sobre el resultado YA agrupado (d.fecha): dentro del GROUP BY
        // PostgreSQL no deja usar a.fecha_hora en una subconsulta ("grouping error")
        $sqlJustificacion = '(SELECT j.tipo FROM justificacion j
                              WHERE j.id_persona = d.id_persona
                                AND d.fecha BETWEEN j.fecha_inicio AND j.fecha_fin
                              ORDER BY j.id_justificacion DESC LIMIT 1)';

        // Si la migración de permisos aún no se ejecutó, se consulta sin la justificación
        foreach ([$sqlJustificacion, 'NULL::text'] as $columnaJustificacion) {
            $sql = "SELECT d.cedula, d.nombre, d.apellido, d.cargo, d.fecha, d.entrada, d.salida,
                           $columnaJustificacion AS justificacion
                    FROM (
                        SELECT p.id_persona, p.cedula, p.nombre, p.apellido, COALESCE(c.nombre_cargo, '—') AS cargo,
                               a.fecha_hora::date AS fecha,
                               to_char(min(a.fecha_hora) FILTER (WHERE a.tipo_movimiento = 'Entrada'), 'HH24:MI') AS entrada,
                               to_char(max(a.fecha_hora) FILTER (WHERE a.tipo_movimiento = 'Salida'), 'HH24:MI') AS salida
                        FROM asistencia a
                        INNER JOIN personal p ON p.id_persona = a.id_persona
                        LEFT JOIN cargo c ON c.id_cargo = p.id_cargo
                        WHERE $where
                        GROUP BY p.id_persona, p.cedula, p.nombre, p.apellido, c.nombre_cargo, a.fecha_hora::date
                    ) d
                    ORDER BY d.fecha DESC, d.apellido ASC, d.nombre ASC
                    LIMIT " . self::LIMITE;
            try {
                $stmt = $this->db->prepare($sql);
                $stmt->execute($params);
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                error_log('Consulta de asistencias: ' . $e->getMessage());
            }
        }
        return [];
    }

    public static function limite(): int
    {
        return self::LIMITE;
    }

    private function fecha($valor): ?string
    {
        $d = DateTime::createFromFormat('!Y-m-d', (string) $valor);
        return ($d && $d->format('Y-m-d') === $valor) ? $valor : null;
    }
}
