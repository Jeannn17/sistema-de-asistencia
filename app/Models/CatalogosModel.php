<?php

namespace App\Models;

use App\Core\ValidacionException;
use App\Core\Validador;
use PDO;

/**
 * Turnos y cargos: listar, crear, modificar y activar/desactivar.
 * Las reglas de los datos (formato, horarios coherentes, nombres únicos) viven aquí:
 * los métodos guardar* y cambiarEstado* lanzan ValidacionException si algo no se cumple.
 */
class CatalogosModel
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

    /** Valida y guarda el turno: $id = 0 lo crea, con id lo actualiza. */
    public function guardarTurno(int $id, array $entrada): void
    {
        $datos = $this->validarTurno($entrada);

        if ($this->existeTurno($datos['nombre_turno'], $id)) {
            throw ValidacionException::duplicado('Ya existe un turno llamado "' . $datos['nombre_turno'] . '".');
        }
        if ($id > 0) {
            if (!$this->actualizarTurno($id, $datos)) {
                throw new ValidacionException('El turno que intentas modificar ya no existe.');
            }
        } else {
            $this->crearTurno($datos);
        }
    }

    /**
     * Reglas de un turno:
     *  - nombre obligatorio (máx. 60)
     *  - periodo 1 obligatorio; periodo 2 y horario extra completos (inicio y fin) o vacíos
     *  - ningún periodo puede empezar y terminar a la misma hora
     * (se permite fin < inicio: es un turno nocturno que cruza la medianoche)
     */
    public function validarTurno(array $e): array
    {
        $d = [
            'nombre_turno' => preg_replace('/\s+/u', ' ', Validador::texto($e['nombre_turno'] ?? '', 'el nombre del turno', 60)),
            'inicio_p1'    => Validador::hora($e['inicio_p1'] ?? '', 'la hora de inicio del periodo 1', true),
            'fin_p1'       => Validador::hora($e['fin_p1'] ?? '', 'la hora de fin del periodo 1', true),
            'inicio_p2'    => Validador::hora($e['inicio_p2'] ?? '', 'la hora de inicio del periodo 2'),
            'fin_p2'       => Validador::hora($e['fin_p2'] ?? '', 'la hora de fin del periodo 2'),
            'inicio_extra' => Validador::hora($e['inicio_extra'] ?? '', 'la hora de inicio del horario extra'),
            'fin_extra'    => Validador::hora($e['fin_extra'] ?? '', 'la hora de fin del horario extra'),
        ];

        foreach (['p1' => 'periodo 1', 'p2' => 'periodo 2', 'extra' => 'horario extra'] as $clave => $etiqueta) {
            $inicio = $d['inicio_' . $clave];
            $fin    = $d['fin_' . $clave];
            if (($inicio === null) !== ($fin === null)) {
                throw new ValidacionException("El {$etiqueta} debe tener hora de inicio y de fin, o dejarse vacío.");
            }
            if ($inicio !== null && $inicio === $fin) {
                throw new ValidacionException("El {$etiqueta} no puede empezar y terminar a la misma hora.");
            }
        }

        return $d;
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

    /** @return bool false si el turno no existe */
    public function actualizarTurno(int $id, array $d): bool
    {
        $sql = 'UPDATE turno SET nombre_turno = :nombre, inicio_p1 = :i1, fin_p1 = :f1,
                       inicio_p2 = :i2, fin_p2 = :f2, inicio_extra = :ie, fin_extra = :fe
                WHERE id_turno = :id';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($this->paramsTurno($d) + [':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public function cambiarEstadoTurno(int $id, bool $activo): void
    {
        if ($id <= 0) {
            throw new ValidacionException('Selecciona un turno.');
        }
        // CAST(...) evita el problema de PDO + PostgreSQL con los booleanos
        $stmt = $this->db->prepare('UPDATE turno SET activo = (CAST(:a AS integer) = 1) WHERE id_turno = :id');
        $stmt->execute([':a' => $activo ? 1 : 0, ':id' => $id]);
        if ($stmt->rowCount() === 0) {
            throw new ValidacionException('El turno ya no existe.');
        }
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

    /** Valida y guarda el cargo: $id = 0 lo crea, con id lo actualiza. */
    public function guardarCargo(int $id, array $entrada): void
    {
        $nombre = preg_replace('/\s+/u', ' ', Validador::texto($entrada['nombre_cargo'] ?? '', 'el nombre del cargo', 60));

        if ($this->existeCargo($nombre, $id)) {
            throw ValidacionException::duplicado('Ya existe un cargo llamado "' . $nombre . '".');
        }
        if ($id > 0) {
            if (!$this->actualizarCargo($id, $nombre)) {
                throw new ValidacionException('El cargo que intentas modificar ya no existe.');
            }
        } else {
            $this->crearCargo($nombre);
        }
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

    /** @return bool false si el cargo no existe */
    public function actualizarCargo(int $id, string $nombre): bool
    {
        $stmt = $this->db->prepare('UPDATE cargo SET nombre_cargo = :nombre WHERE id_cargo = :id');
        $stmt->execute([':nombre' => $nombre, ':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public function cambiarEstadoCargo(int $id, bool $activo): void
    {
        if ($id <= 0) {
            throw new ValidacionException('Selecciona un cargo.');
        }
        $stmt = $this->db->prepare('UPDATE cargo SET activo = (CAST(:a AS integer) = 1) WHERE id_cargo = :id');
        $stmt->execute([':a' => $activo ? 1 : 0, ':id' => $id]);
        if ($stmt->rowCount() === 0) {
            throw new ValidacionException('El cargo ya no existe.');
        }
    }
}
