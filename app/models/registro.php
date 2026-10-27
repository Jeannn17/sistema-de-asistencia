<?php
class Registro {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }
    public function getCargos(): array {
        $stmt = $this->pdo->query('SELECT id_cargo, nombre_cargo FROM cargo WHERE activo = true ORDER BY nombre_cargo ASC');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTurnos(): array {
        $stmt = $this->pdo->query('SELECT id_turno, nombre_turno FROM turno WHERE activo = true ORDER BY nombre_turno ASC');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function all(): array {
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

    public function create(array $data): bool {
        $sql = 'INSERT INTO personal(cedula, nombre, apellido, nro_registro, id_cargo, id_turno, fecha_ingreso, activo)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)';
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            $data['cedula'],
            $data['nombre'],
            $data['apellido'],
            $data['nro_registro'] ?? null,
            $data['id_cargo'],
            $data['id_turno'],
            $data['fecha_ingreso'],
            $data['activo']
        ]);
    }

    /**
     * REGISTRO MASIVO MEDIANTE TRANSACCIÓN
     * @param array $personas Arreglo con la lista de personas a registrar
     * @return bool
     */
    public function createBatch(array $personas): bool {
        try {
            // Iniciamos la transacción
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
                    $persona['activo']
                ]);
            }

            // Confirmamos la inserción de todos los empleados
            $this->pdo->commit();
            return true;

        } catch (PDOException $e) {
            // Si ocurre algún error, se revierten todos los registros
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            // Se relanza para que el controlador decida qué mensaje mostrar
            // (antes se usaba die(), que mostraba el error de la base al usuario y cortaba todo)
            throw $e;
        }
    }

    public function find(int $id_persona) {
        $stmt = $this->pdo->prepare('SELECT * FROM personal WHERE id_persona = ?');
        $stmt->execute([$id_persona]);
        return $stmt->fetch();
    }

    public function edit(array $data): bool {
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
            $data['id_persona']
        ]);
    }
}