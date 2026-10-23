<?php

class asistenciaModel
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Devuelve [nro_registro => id_persona] de todo el personal activo.
     * Se carga una sola vez por importación (antes se hacía una consulta por marcaje).
     */
    public function obtenerMapaPersonal(): array
    {
        $sql = 'SELECT nro_registro, id_persona
                FROM personal
                WHERE activo = true AND nro_registro IS NOT NULL';
        return $this->db->query($sql)->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    /**
     * Guarda las marcaciones en la tabla 'asistencia' dentro de una transacción.
     *
     * Cada marcación debe venir así:
     *   ['nro_registro' => 12, 'fecha_hora' => '2026-02-03 07:07:00', 'tipo' => 'Entrada'|'Salida']
     *
     * @return array{insertados:int, duplicados:int, sin_registro:int[]}
     */
    public function registrarMarcaciones(array $marcaciones): array
    {
        $mapa        = $this->obtenerMapaPersonal();
        $insertados  = 0;
        $duplicados  = 0;
        $sinRegistro = [];

        // ON CONFLICT usa el índice único ux_asistencia_marcacion (ver migracion_asistencia.sql)
        $sql = 'INSERT INTO asistencia (id_persona, fecha_hora, tipo_movimiento)
                VALUES (:id_persona, :fecha_hora, :tipo)
                ON CONFLICT (id_persona, fecha_hora, tipo_movimiento) DO NOTHING';

        try {
            $this->db->beginTransaction();
            $stmt = $this->db->prepare($sql);

            foreach ($marcaciones as $m) {
                $nro = (int) $m['nro_registro'];

                if (!isset($mapa[$nro])) {
                    // El N° de registro del Excel no existe (o está inactivo) en 'personal'
                    $sinRegistro[$nro] = $nro;
                    continue;
                }

                $stmt->execute([
                    ':id_persona' => $mapa[$nro],
                    ':fecha_hora' => $m['fecha_hora'],
                    ':tipo'       => $m['tipo'],
                ]);

                if ($stmt->rowCount() === 1) {
                    $insertados++;
                } else {
                    $duplicados++;
                }
            }

            $this->db->commit();
        } catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            // Se relanza para que el controlador decida el mensaje (igual que Registro::createBatch)
            throw $e;
        }

        return [
            'insertados'   => $insertados,
            'duplicados'   => $duplicados,
            'sin_registro' => array_values($sinRegistro),
        ];
    }
}
