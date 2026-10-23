<?php

class justificacionModel
{
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

    /** Inserta la justificación y devuelve su id. */
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
}
