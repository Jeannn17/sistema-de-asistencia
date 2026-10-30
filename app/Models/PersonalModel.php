<?php

namespace App\Models;

use App\Core\ValidacionException;
use App\Core\Validador;
use PDO;
use PDOException;

/**
 * Trabajadores (tabla personal).
 * Reglas de los datos: cédula y N° de registro únicos, nombres solo con letras,
 * cargo y turno existentes y activos, fecha de ingreso no futura.
 */
class PersonalModel
{
    /** Máximo de trabajadores en una sola carga masiva */
    public const MAX_POR_LOTE = 100;

    /** Mayor N° de registro aceptado (el ID del reloj biométrico) */
    private const MAX_NRO_REGISTRO = 99999999;

    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getCargos(): array
    {
        $stmt = $this->pdo->query('SELECT id_cargo, nombre_cargo FROM cargo WHERE activo = true ORDER BY nombre_cargo ASC');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTurnos(): array
    {
        $stmt = $this->pdo->query('SELECT id_turno, nombre_turno FROM turno WHERE activo = true ORDER BY nombre_turno ASC');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function all(): array
    {
        $sql = 'SELECT
                    p.id_persona,
                    p.cedula,
                    p.nombre,
                    p.apellido,
                    p.nro_registro,
                    p.fecha_ingreso,
                    p.activo,
                    p.id_cargo,
                    p.id_turno,
                    c.nombre_cargo,
                    t.nombre_turno
                FROM personal p
                INNER JOIN cargo c ON p.id_cargo = c.id_cargo
                INNER JOIN turno t ON p.id_turno = t.id_turno
                ORDER BY p.id_persona DESC';

        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find(int $id_persona)
    {
        $stmt = $this->pdo->prepare('SELECT * FROM personal WHERE id_persona = ?');
        $stmt->execute([$id_persona]);
        return $stmt->fetch();
    }

    // ---------------------------------------------------------------- REGLAS
    /**
     * Valida los datos de UN trabajador y los devuelve limpios.
     * No consulta la base de datos (eso lo hacen registrarLote() y actualizar()).
     */
    public function validar(array $e): array
    {
        $datos = [
            'cedula'        => Validador::cedula($e['cedula'] ?? ''),
            'nombre'        => Validador::nombrePropio($e['nombre'] ?? '', 'el nombre'),
            'apellido'      => Validador::nombrePropio($e['apellido'] ?? '', 'el apellido'),
            'nro_registro'  => Validador::entero($e['nro_registro'] ?? '', 'el N° de registro', false, 1, self::MAX_NRO_REGISTRO),
            'id_cargo'      => Validador::id($e['id_cargo'] ?? '', 'un cargo'),
            'id_turno'      => Validador::id($e['id_turno'] ?? '', 'un turno'),
            'fecha_ingreso' => Validador::fecha($e['fecha_ingreso'] ?? '', 'la fecha de ingreso'),
        ];

        if ($datos['fecha_ingreso'] > date('Y-m-d')) {
            throw new ValidacionException('La fecha de ingreso no puede ser posterior a hoy.');
        }
        return $datos;
    }

    /**
     * Registro masivo de trabajadores de un mismo cargo. Todo o nada:
     * si una fila no cumple las reglas, no se guarda ninguna.
     *
     * @param array<int, array> $filas una fila por trabajador (cedula, nombre, apellido, nro_registro, id_turno, fecha_ingreso)
     * @return int cantidad de trabajadores registrados
     */
    public function registrarLote(int $idCargo, array $filas): int
    {
        if (!$filas) {
            throw new ValidacionException('Agrega al menos un trabajador con su cédula.');
        }
        if (count($filas) > self::MAX_POR_LOTE) {
            throw new ValidacionException('Puedes registrar máximo ' . self::MAX_POR_LOTE . ' trabajadores a la vez.');
        }
        $this->exigirCatalogoActivo('cargo', $idCargo);

        $personas = [];
        $cedulas  = [];   // cédula => n° de fila, para detectar repetidas dentro del mismo formulario
        $nros     = [];
        $turnosOk = [];

        foreach (array_values($filas) as $i => $fila) {
            $n = $i + 1;
            try {
                $p = $this->validar(['id_cargo' => $idCargo] + $fila);

                if (isset($cedulas[$p['cedula']])) {
                    throw ValidacionException::duplicado("La cédula {$p['cedula']} ya está en la fila {$cedulas[$p['cedula']]}.");
                }
                if ($p['nro_registro'] !== null && isset($nros[$p['nro_registro']])) {
                    throw ValidacionException::duplicado("El N° de registro {$p['nro_registro']} ya está en la fila {$nros[$p['nro_registro']]}.");
                }
                if (!isset($turnosOk[$p['id_turno']])) {
                    $this->exigirCatalogoActivo('turno', $p['id_turno']);
                    $turnosOk[$p['id_turno']] = true;
                }
                $this->exigirSinDuplicados($p['cedula'], $p['nro_registro']);
            } catch (ValidacionException $e) {
                throw $e->conPrefijo("Fila {$n}: ");
            }

            $cedulas[$p['cedula']] = $n;
            if ($p['nro_registro'] !== null) {
                $nros[$p['nro_registro']] = $n;
            }
            $personas[] = $p + ['activo' => 1];
        }

        $this->createBatch($personas);
        return count($personas);
    }

    /** Modifica un trabajador existente (ventana de edición de la lista). */
    public function actualizar(array $entrada): void
    {
        $id = Validador::id($entrada['id_persona'] ?? '', 'un trabajador');
        $actual = $this->find($id);
        if (!$actual) {
            throw new ValidacionException('El trabajador que intentas modificar ya no existe.');
        }

        $p = $this->validar($entrada);

        // Se permite conservar un cargo/turno que fue desactivado después; cambiar a uno inactivo, no
        if ($p['id_cargo'] !== (int) $actual['id_cargo']) {
            $this->exigirCatalogoActivo('cargo', $p['id_cargo']);
        }
        if ($p['id_turno'] !== (int) $actual['id_turno']) {
            $this->exigirCatalogoActivo('turno', $p['id_turno']);
        }
        $this->exigirSinDuplicados($p['cedula'], $p['nro_registro'], $id);

        // PDO + PostgreSQL convierte false en '' y rompe el boolean: usar 1/0
        $this->edit($p + ['activo' => !empty($entrada['activo']) ? 1 : 0, 'id_persona' => $id]);
    }

    /**
     * Activa o inactiva a un trabajador (no se borra: su historial de asistencia se conserva).
     * Un trabajador inactivo no aparece en permisos ni recibe marcaciones al importar.
     * @return string nombre del trabajador, para el aviso
     */
    public function cambiarEstado(int $id, bool $activo): string
    {
        if ($id <= 0) {
            throw new ValidacionException('Selecciona un trabajador.');
        }
        $stmt = $this->pdo->prepare(
            'UPDATE personal SET activo = (CAST(:a AS integer) = 1) WHERE id_persona = :id
             RETURNING apellido, nombre'
        );
        $stmt->execute([':a' => $activo ? 1 : 0, ':id' => $id]);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$fila) {
            throw new ValidacionException('El trabajador ya no existe.');
        }
        return trim($fila['apellido'] . ' ' . $fila['nombre']);
    }

    /** La cédula y el N° de registro no pueden pertenecer a otro trabajador. */
    private function exigirSinDuplicados(string $cedula, ?int $nroRegistro, int $exceptoId = 0): void
    {
        $stmt = $this->pdo->prepare(
            'SELECT cedula, nro_registro, nombre, apellido FROM personal
             WHERE (cedula = :cedula OR nro_registro = CAST(:nro AS integer)) AND id_persona <> :id
             LIMIT 1'
        );
        $stmt->execute([':cedula' => $cedula, ':nro' => $nroRegistro, ':id' => $exceptoId]);
        $otro = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$otro) {
            return;
        }

        $quien = trim($otro['apellido'] . ' ' . $otro['nombre']);
        throw ValidacionException::duplicado($otro['cedula'] === $cedula
            ? "La cédula {$cedula} ya pertenece a {$quien}."
            : "El N° de registro {$nroRegistro} ya pertenece a {$quien}.");
    }

    /** El cargo o turno debe existir y estar activo. */
    private function exigirCatalogoActivo(string $tabla, int $id): void
    {
        $sql = [
            'cargo' => 'SELECT activo FROM cargo WHERE id_cargo = :id',
            'turno' => 'SELECT activo FROM turno WHERE id_turno = :id',
        ][$tabla];

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $activo = $stmt->fetchColumn();

        if ($activo === false) {
            throw new ValidacionException("El {$tabla} seleccionado no existe.");
        }
        if (!$activo) {
            throw new ValidacionException("El {$tabla} seleccionado está desactivado.");
        }
    }

    // ---------------------------------------------------------------- ESCRITURA
    /**
     * REGISTRO MASIVO MEDIANTE TRANSACCIÓN
     * @param array $personas Arreglo con la lista de personas (ya validadas) a registrar
     */
    public function createBatch(array $personas): bool
    {
        try {
            $this->pdo->beginTransaction();

            $sql = 'INSERT INTO personal (cedula, nombre, apellido, nro_registro, id_cargo, id_turno, fecha_ingreso, activo)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)';
            $stmt = $this->pdo->prepare($sql);

            foreach ($personas as $persona) {
                $stmt->execute([
                    $persona['cedula'],
                    $persona['nombre'],
                    $persona['apellido'],
                    $persona['nro_registro'],
                    $persona['id_cargo'],
                    $persona['id_turno'],
                    $persona['fecha_ingreso'],
                    $persona['activo'],
                ]);
            }

            $this->pdo->commit();
            return true;
        } catch (PDOException $e) {
            // Si ocurre algún error, se revierten todos los registros
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            // Se relanza para que el controlador decida qué mensaje mostrar
            throw $e;
        }
    }

    public function edit(array $data): bool
    {
        $sql = 'UPDATE personal SET cedula = ?, nombre = ?, apellido = ?, nro_registro = ?, id_cargo = ?, id_turno = ?, fecha_ingreso = ?, activo = ?
                WHERE id_persona = ?';
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            $data['cedula'],
            $data['nombre'],
            $data['apellido'],
            $data['nro_registro'] ?? null,
            $data['id_cargo'],
            $data['id_turno'],
            $data['fecha_ingreso'],
            $data['activo'],
            $data['id_persona'],
        ]);
    }
}
