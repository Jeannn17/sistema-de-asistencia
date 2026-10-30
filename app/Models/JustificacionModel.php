<?php

namespace App\Models;

use App\Core\ValidacionException;
use App\Core\Validador;
use DateTime;
use PDO;

/** Permisos y justificaciones del personal (reposos, accidentes, permisos por día u hora). */
class JustificacionModel
{
    /** tipo => [etiqueta, ¿es de un solo día con hora?]  (los mismos valores del CHECK de la tabla) */
    public const TIPOS = [
        'reposo'            => ['Reposo médico',              false],
        'accidente_laboral' => ['Accidente laboral',          false],
        'permiso_dia'       => ['Permiso por el día',         false],
        'salida_temprana'   => ['Permiso de salida temprana', true],
        'llegada_tarde'     => ['Permiso de llegada tarde',   true],
    ];

    /** Duración máxima de un reposo o accidente registrado de una sola vez */
    private const MAX_DIAS_RANGO = 366;

    /** Hasta cuántos días hacia atrás se puede justificar */
    private const MAX_DIAS_ATRAS = 365;

    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /** Personal activo para el selector del formulario. */
    public function obtenerPersonalActivo(): array
    {
        $sql = 'SELECT id_persona, cedula, nombre, apellido, nro_registro
                FROM personal
                WHERE activo = true
                ORDER BY apellido ASC, nombre ASC';
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Valida y registra un permiso. Reglas:
     *  - trabajador activo, tipo conocido, motivo obligatorio (máx. 500)
     *  - salida temprana / llegada tarde: un día y una hora
     *  - permiso por el día: un solo día
     *  - reposo / accidente: rango de fechas (fin >= inicio, máximo MAX_DIAS_RANGO días)
     *  - no puede cruzarse con otro permiso del mismo tipo del mismo trabajador
     *
     * @return int id de la justificación creada
     */
    public function registrar(array $e): int
    {
        $idPersona = Validador::id($e['id_persona'] ?? '', 'un trabajador de la lista');
        $tipo      = Validador::opcion($e['tipo'] ?? '', array_keys(self::TIPOS), 'un tipo de permiso');
        $motivo    = Validador::texto($e['motivo'] ?? '', 'el motivo', 500);
        $inicio    = Validador::fecha($e['fecha_inicio'] ?? '', 'la fecha de inicio');
        $hora      = null;

        if (self::TIPOS[$tipo][1]) {
            $fin  = $inicio;
            $hora = Validador::hora($e['hora'] ?? '', 'la hora del permiso', true);
        } elseif ($tipo === 'permiso_dia') {
            $fin = $inicio;
        } else {
            $fin = Validador::fecha($e['fecha_fin'] ?? '', 'la fecha de fin');
            if ($fin < $inicio) {
                throw new ValidacionException('La fecha de fin no puede ser anterior a la fecha de inicio.');
            }
            if ($this->dias($inicio, $fin) + 1 > self::MAX_DIAS_RANGO) {
                throw new ValidacionException('Un permiso puede abarcar máximo ' . self::MAX_DIAS_RANGO . ' días.');
            }
        }

        if ($this->dias($inicio, date('Y-m-d')) > self::MAX_DIAS_ATRAS) {
            throw new ValidacionException('Solo se pueden justificar fechas del último año.');
        }
        if (!$this->personaActiva($idPersona)) {
            throw new ValidacionException('El trabajador seleccionado no existe o está inactivo.');
        }
        if ($this->existeCruce($idPersona, $tipo, $inicio, $fin)) {
            throw ValidacionException::duplicado(
                'Ese trabajador ya tiene un "' . self::TIPOS[$tipo][0] . '" que se cruza con esas fechas.'
            );
        }

        return $this->crear([
            'id_persona'   => $idPersona,
            'tipo'         => $tipo,
            'fecha_inicio' => $inicio,
            'fecha_fin'    => $fin,
            'hora'         => $hora,
            'motivo'       => $motivo,
        ]);
    }

    public function personaActiva(int $idPersona): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM personal WHERE id_persona = :id AND activo = true');
        $stmt->execute([':id' => $idPersona]);
        return (bool) $stmt->fetchColumn();
    }

    /** ¿Ya existe una justificación del mismo tipo que se cruce con ese rango de fechas? */
    public function existeCruce(int $idPersona, string $tipo, string $inicio, string $fin): bool
    {
        $sql = 'SELECT 1 FROM justificacion
                WHERE id_persona = :persona AND tipo = :tipo
                  AND fecha_inicio <= :fin AND fecha_fin >= :inicio
                LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':persona' => $idPersona,
            ':tipo'    => $tipo,
            ':inicio'  => $inicio,
            ':fin'     => $fin,
        ]);
        return (bool) $stmt->fetchColumn();
    }

    /** Inserta la justificación (ya validada) y devuelve su id. */
    public function crear(array $d): int
    {
        $sql = 'INSERT INTO justificacion (id_persona, tipo, fecha_inicio, fecha_fin, hora, motivo)
                VALUES (:id_persona, :tipo, :fecha_inicio, :fecha_fin, :hora, :motivo)
                RETURNING id_justificacion';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id_persona'   => $d['id_persona'],
            ':tipo'         => $d['tipo'],
            ':fecha_inicio' => $d['fecha_inicio'],
            ':fecha_fin'    => $d['fecha_fin'],
            ':hora'         => $d['hora'],     // null si el tipo no usa hora
            ':motivo'       => $d['motivo'],
        ]);
        return (int) $stmt->fetchColumn();
    }

    /** Últimas justificaciones registradas, para mostrarlas bajo el formulario. */
    public function listarRecientes(int $limite = 30): array
    {
        $sql = 'SELECT j.id_justificacion, j.tipo, j.fecha_inicio, j.fecha_fin, j.hora, j.motivo,
                       p.cedula, p.nombre, p.apellido
                FROM justificacion j
                INNER JOIN personal p ON p.id_persona = j.id_persona
                ORDER BY j.id_justificacion DESC
                LIMIT :limite';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Días entre dos fechas 'YYYY-MM-DD' (negativo si $hasta es anterior). */
    private function dias(string $desde, string $hasta): int
    {
        return (int) (new DateTime($desde))->diff(new DateTime($hasta))->format('%r%a');
    }
}
