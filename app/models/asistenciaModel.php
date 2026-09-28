<?php
class Registro{
    private PDO $db; // Encapsulación: solo este clase toca la conexión

    public function __construct(PDO $db) {
        $this->db = $db; // Inyección de dependencias clara
    }

    public function obtenerIdPersonaPorRegistro($nroRegistro) {
        $sql = "SELECT id_persona FROM personal WHERE nro_registro = :nro_registro AND activo = true";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':nro_registro' => $nroRegistro]);
        $resultado = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $resultado ? $resultado['id_persona'] : null;
    }
    /**
     * Registra las entradas y salidas en la tabla 'asistencia'
     * 
     * @param array $asistencias Lote de registros extraídos del Excel
     * @param string|null $mesAno Fecha base en formato YYYY-MM (ej: '2026-02')
     */

    public function registrarAsistenciaMasiva(array $asistencias, $mesAno = null) {
        // Si no se especifica año/mes, toma el mes actual
        if (!$mesAno) {
            $mesAno = date('Y-m'); 
        }

        try {
            $this->db->beginTransaction();

            // Consulta INSERT para la tabla 'asistencia'
            $sql = "INSERT INTO asistencia (id_persona, fecha_hora, tipo_movimiento) 
                    VALUES (:id_persona, :fecha_hora, :tipo_movimiento)";

            $stmt = $this->db->prepare($sql);

            foreach ($asistencias as $registro) {
                // 1. Buscamos el id_persona que tenga este nro_registro
                $idPersona = $this->obtenerIdPersonaPorRegistro($registro['id_personal']);

                if (!$idPersona) {
                    continue; // Si el nro_registro no está registrado en el colegio, lo ignora
                }

                // 2. Construimos la fecha en formato YYYY-MM-DD
                $diaFormateado = str_pad($registro['dia'], 2, '0', STR_PAD_LEFT);
                $fechaBase = $mesAno . '-' . $diaFormateado;

                // 3. Insertar marcaje de ENTRADA si existe
                if (!empty($registro['entrada'])) {
                    $fechaHoraEntrada = $fechaBase . ' ' . trim($registro['entrada']) . ':00';
                    
                    $stmt->execute([
                        ':id_persona'      => $idPersona,
                        ':fecha_hora'      => $fechaHoraEntrada,
                        ':tipo_movimiento' => 'ENTRADA'
                    ]);
                }

                // 4. Insertar marcaje de SALIDA si existe
                if (!empty($registro['salida'])) {
                    $fechaHoraSalida = $fechaBase . ' ' . trim($registro['salida']) . ':00';
                    
                    $stmt->execute([
                        ':id_persona'      => $idPersona,
                        ':fecha_hora'      => $fechaHoraSalida,
                        ':tipo_movimiento' => 'SALIDA'
                    ]);
                }
            }

            // Confirmar transacción
            $this->db->commit();
            return true;

        } catch (\PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw new \Exception("Error al registrar la asistencia en PostgreSQL: " . $e->getMessage());
        }
    }
}
?>