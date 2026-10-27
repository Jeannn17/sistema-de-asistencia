<?php

/** Turnos y cargos: listar, crear, modificar y activar/desactivar. */
class catalogosModel
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    // ---------------------------------------------------------------- TURNOS
    public function listarTurnos(): array
    {
        $sql = 'SELECT t.id_turno, t.nombre_turno, t.inicio_p1, t.fin_p1, t.inicio_p2, t.fin_p2,
                       t.inicio_extra, t.fin_extra, t.activo,
                       (SELECT count(*) FROM personal p WHERE p.id_turno = t.id_turno AND p.activo = true) AS trabajadores
                FROM turno t
                ORDER BY t.activo DESC, t.nombre_turno ASC';
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function existeTurno(string $nombre, int $exceptoId = 0): bool
    {
        $stmt = $this->db->prepare(
            'SELECT 1 FROM turno WHERE lower(nombre_turno) = lower(:nombre) AND id_turno <> :id LIMIT 1'
        );
        $stmt->execute([':nombre' => $nombre, ':id' => $exceptoId]);
        return (bool) $stmt->fetchColumn();
    }

    public function crearTurno(array $d): void
    {
        $sql = 'INSERT INTO turno (nombre_turno, inicio_p1, fin_p1, inicio_p2, fin_p2, inicio_extra, fin_extra)
                VALUES (:nombre, :i1, :f1, :i2, :f2, :ie, :fe)';
        $this->db->prepare($sql)->execute($this->paramsTurno($d));
    }

    public function actualizarTurno(int $id, array $d): void
    {
        $sql = 'UPDATE turno SET nombre_turno = :nombre, inicio_p1 = :i1, fin_p1 = :f1,
                       inicio_p2 = :i2, fin_p2 = :f2, inicio_extra = :ie, fin_extra = :fe
                WHERE id_turno = :id';
        $this->db->prepare($sql)->execute($this->paramsTurno($d) + [':id' => $id]);
    }

    public function cambiarEstadoTurno(int $id, bool $activo): void
    {
        // CAST(...) evita el problema de PDO + PostgreSQL con los booleanos
        $this->db->prepare('UPDATE turno SET activo = (CAST(:a AS integer) = 1) WHERE id_turno = :id')
                 ->execute([':a' => $activo ? 1 : 0, ':id' => $id]);
    }

    private function paramsTurno(array $d): array
    {
        return [
            ':nombre' => $d['nombre_turno'],
            ':i1' => $d['inicio_p1'], ':f1' => $d['fin_p1'],
            ':i2' => $d['inicio_p2'], ':f2' => $d['fin_p2'],
            ':ie' => $d['inicio_extra'], ':fe' => $d['fin_extra'],
        ];
    }

    // ---------------------------------------------------------------- CARGOS
    public function listarCargos(): array
    {
        $sql = 'SELECT c.id_cargo, c.nombre_cargo, c.activo,
                       (SELECT count(*) FROM personal p WHERE p.id_cargo = c.id_cargo AND p.activo = true) AS trabajadores
                FROM cargo c
                ORDER BY c.activo DESC, c.nombre_cargo ASC';
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function existeCargo(string $nombre, int $exceptoId = 0): bool
    {
        $stmt = $this->db->prepare(
            'SELECT 1 FROM cargo WHERE lower(nombre_cargo) = lower(:nombre) AND id_cargo <> :id LIMIT 1'
        );
        $stmt->execute([':nombre' => $nombre, ':id' => $exceptoId]);
        return (bool) $stmt->fetchColumn();
    }

    public function crearCargo(string $nombre): void
    {
        $this->db->prepare('INSERT INTO cargo (nombre_cargo) VALUES (:nombre)')->execute([':nombre' => $nombre]);
    }

    public function actualizarCargo(int $id, string $nombre): void
    {
        $this->db->prepare('UPDATE cargo SET nombre_cargo = :nombre WHERE id_cargo = :id')
                 ->execute([':nombre' => $nombre, ':id' => $id]);
    }

    public function cambiarEstadoCargo(int $id, bool $activo): void
    {
        $this->db->prepare('UPDATE cargo SET activo = (CAST(:a AS integer) = 1) WHERE id_cargo = :id')
                 ->execute([':a' => $activo ? 1 : 0, ':id' => $id]);
    }
}
