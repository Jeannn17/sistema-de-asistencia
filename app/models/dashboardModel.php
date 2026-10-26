<?php

/**
 * Datos reales para la pantalla de Inicio del dashboard.
 * Cada bloque va en su propio try/catch: si falla uno (por ejemplo, la migración de
 * permisos aún no se ha ejecutado), el resto del inicio se sigue mostrando.
 */
class dashboardModel
{
    /** Minutos de tolerancia sobre el inicio del turno para considerar "Puntual" */
    private const TOLERANCIA_MIN = 5;

    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function obtenerResumen(): array
    {
        $r = [
            'total' => 0, 'activos' => 0, 'inactivos' => 0, 'nuevos_mes' => 0,
            'fecha_ref' => null, 'presentes' => 0, 'pct_asistencia' => null,
            'por_cargo' => [],
            'permisos' => ['total' => 0, 'reposo' => 0, 'accidente' => 0, 'otros' => 0],
            'ultimos' => [],
        ];

        // ---- Personal ----
        try {
            $f = $this->db->query(
                "SELECT count(*) AS total,
                        count(*) FILTER (WHERE activo) AS activos,
                        count(*) FILTER (WHERE fecha_ingreso >= date_trunc('month', CURRENT_DATE)) AS nuevos
                 FROM personal"
            )->fetch(PDO::FETCH_ASSOC);
            $r['total']      = (int) $f['total'];
            $r['activos']    = (int) $f['activos'];
            $r['inactivos']  = $r['total'] - $r['activos'];
            $r['nuevos_mes'] = (int) $f['nuevos'];
        } catch (PDOException $e) {
            error_log('Dashboard (personal): ' . $e->getMessage());
        }

        // ---- Asistencia del último día con marcaciones ----
        try {
            $fecha = $this->db->query('SELECT max(fecha_hora)::date FROM asistencia')->fetchColumn();
            if ($fecha) {
                $r['fecha_ref'] = $fecha;

                $stmt = $this->db->prepare(
                    'SELECT count(DISTINCT a.id_persona)
                     FROM asistencia a INNER JOIN personal p ON p.id_persona = a.id_persona
                     WHERE a.fecha_hora::date = :f AND p.activo = true'
                );
                $stmt->execute([':f' => $fecha]);
                $r['presentes'] = (int) $stmt->fetchColumn();

                if ($r['activos'] > 0) {
                    $r['pct_asistencia'] = round($r['presentes'] * 100 / $r['activos'], 1);
                }

                $stmt = $this->db->prepare(
                    "SELECT COALESCE(c.nombre_cargo, 'Sin cargo') AS cargo,
                            count(DISTINCT p.id_persona) AS total,
                            count(DISTINCT a.id_persona) AS presentes
                     FROM personal p
                     LEFT JOIN cargo c ON c.id_cargo = p.id_cargo
                     LEFT JOIN asistencia a ON a.id_persona = p.id_persona AND a.fecha_hora::date = :f
                     WHERE p.activo = true
                     GROUP BY c.nombre_cargo
                     ORDER BY total DESC, cargo ASC"
                );
                $stmt->execute([':f' => $fecha]);
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $c) {
                    $total = (int) $c['total'];
                    $pres  = (int) $c['presentes'];
                    $r['por_cargo'][] = [
                        'cargo'     => $c['cargo'],
                        'total'     => $total,
                        'presentes' => $pres,
                        'pct'       => $total > 0 ? round($pres * 100 / $total, 1) : 0,
                    ];
                }
            }
        } catch (PDOException $e) {
            error_log('Dashboard (asistencia): ' . $e->getMessage());
        }

        // ---- Justificaciones vigentes hoy ----
        try {
            $rows = $this->db->query(
                'SELECT tipo, count(*) AS n FROM justificacion
                 WHERE CURRENT_DATE BETWEEN fecha_inicio AND fecha_fin
                 GROUP BY tipo'
            )->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $row) {
                $n = (int) $row['n'];
                $r['permisos']['total'] += $n;
                if ($row['tipo'] === 'reposo') {
                    $r['permisos']['reposo'] += $n;
                } elseif ($row['tipo'] === 'accidente_laboral') {
                    $r['permisos']['accidente'] += $n;
                } else {
                    $r['permisos']['otros'] += $n;
                }
            }
        } catch (PDOException $e) {
            error_log('Dashboard (permisos): ' . $e->getMessage());
        }

        // ---- Últimas marcaciones ----
        try {
            $rows = $this->db->query(
                "SELECT a.fecha_hora, a.tipo_movimiento, p.nombre, p.apellido, p.cedula,
                        COALESCE(c.nombre_cargo, '—') AS cargo, t.inicio_p1
                 FROM asistencia a
                 INNER JOIN personal p ON p.id_persona = a.id_persona
                 LEFT JOIN cargo c ON c.id_cargo = p.id_cargo
                 LEFT JOIN turno t ON t.id_turno = p.id_turno
                 ORDER BY a.fecha_hora DESC, a.id_asistencia DESC
                 LIMIT 8"
            )->fetchAll(PDO::FETCH_ASSOC);

            foreach ($rows as $m) {
                $ts    = strtotime($m['fecha_hora']);
                $estado = ['texto' => 'Salida', 'clase' => 'neutro'];

                if ($m['tipo_movimiento'] === 'Entrada') {
                    $estado = ['texto' => 'Sin turno', 'clase' => 'neutro'];
                    if (!empty($m['inicio_p1'])) {
                        $inicio = strtotime(date('Y-m-d', $ts) . ' ' . $m['inicio_p1']);
                        $retraso = (int) round(($ts - $inicio) / 60);
                        $estado = $retraso <= self::TOLERANCIA_MIN
                            ? ['texto' => 'Puntual', 'clase' => 'ok']
                            : ['texto' => 'Retardo (' . $retraso . ' min)', 'clase' => 'alerta'];
                    }
                }

                $r['ultimos'][] = [
                    'nombre'   => trim($m['apellido'] . ' ' . $m['nombre']),
                    'cedula'   => $m['cedula'],
                    'cargo'    => $m['cargo'],
                    'fecha'    => date('d/m/Y', $ts),
                    'hora'     => date('H:i', $ts),
                    'tipo'     => $m['tipo_movimiento'],
                    'estado'   => $estado,
                    'iniciales' => mb_strtoupper(mb_substr($m['nombre'], 0, 1) . mb_substr($m['apellido'], 0, 1)),
                ];
            }
        } catch (PDOException $e) {
            error_log('Dashboard (ultimos): ' . $e->getMessage());
        }

        return $r;
    }
}
