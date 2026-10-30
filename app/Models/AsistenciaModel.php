<?php

namespace App\Models;

use App\Core\ValidacionException;
use PDO;
use PDOException;

class AsistenciaModel
{
    /** Los mismos valores del CHECK de la columna tipo_movimiento */
    public const TIPOS_MOVIMIENTO = ['Entrada', 'Salida'];

    /** Minutos de tolerancia sobre el inicio del turno para considerar la entrada "Puntual" */
    public const TOLERANCIA_MIN = 5;

    /** Tamaño máximo del archivo subido */
    public const MAX_BYTES = 10 * 1024 * 1024;   // 10 MB

    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Revisa el archivo subido (campo de $_FILES) y devuelve su tipo REAL: 'Xls' o 'Xlsx'.
     * No basta con la extensión: se comprueba la firma del archivo.
     */
    public function validarArchivo($file): string
    {
        if (!is_array($file) || !is_string($file['name'] ?? null) || $file['name'] === ''
            || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            throw new ValidacionException('Selecciona el archivo Excel del biométrico.');
        }

        switch ($file['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                throw new ValidacionException(
                    'El archivo supera el tamaño que permite el servidor (' . ini_get('upload_max_filesize') . ').'
                );
            case UPLOAD_ERR_PARTIAL:
                throw new ValidacionException('El archivo se subió incompleto. Inténtalo de nuevo.');
            default:
                error_log('Importar asistencia: error de subida ' . $file['error']);
                throw new ValidacionException('El servidor no pudo recibir el archivo. Inténtalo de nuevo.');
        }

        if (!is_uploaded_file($file['tmp_name'])) {
            throw new ValidacionException('El servidor no pudo recibir el archivo. Inténtalo de nuevo.');
        }
        if ($file['size'] <= 0) {
            throw new ValidacionException('El archivo está vacío.');
        }
        if ($file['size'] > self::MAX_BYTES) {
            throw new ValidacionException('El archivo supera el tamaño máximo permitido (' . (self::MAX_BYTES / 1048576) . ' MB).');
        }

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, ['xlsx', 'xls'], true)) {
            throw new ValidacionException('Formato incorrecto: sube un archivo .xlsx o .xls.');
        }

        $f = fopen($file['tmp_name'], 'rb');
        $cabecera = $f ? (string) fread($f, 8) : '';
        if ($f) {
            fclose($f);
        }
        if ($cabecera === "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1") {
            return 'Xls';    // Excel 97-2003
        }
        if (strncmp($cabecera, "PK\x03\x04", 4) === 0) {
            return 'Xlsx';   // Excel moderno (zip)
        }
        throw new ValidacionException('El archivo no es un Excel válido (¿se exportó como HTML o CSV?).');
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
     * Las que ya estaban importadas se cuentan como duplicadas (el archivo se puede subir dos veces sin problema).
     *
     * Cada marcación debe venir así:
     *   ['nro_registro' => 12, 'fecha_hora' => '2026-02-03 07:07:00', 'tipo' => 'Entrada'|'Salida']
     *
     * @return array{insertados:int, duplicados:int, sin_registro:int[]}
     */
    public function registrarMarcaciones(array $marcaciones): array
    {
        if (!$marcaciones) {
            throw new ValidacionException('El informe no trae marcaciones en su rango de fechas.');
        }
        foreach ($marcaciones as $m) {
            $this->validarMarcacion($m);
        }

        $mapa        = $this->obtenerMapaPersonal();
        $insertados  = 0;
        $duplicados  = 0;
        $sinRegistro = [];

        // ON CONFLICT usa el índice único ux_asistencia_marcacion
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
            // Se relanza para que el controlador decida el mensaje (igual que PersonalModel::createBatch)
            throw $e;
        }

        sort($sinRegistro);
        return [
            'insertados'   => $insertados,
            'duplicados'   => $duplicados,
            'sin_registro' => array_values($sinRegistro),
        ];
    }

    private function validarMarcacion($m): void
    {
        $ok = is_array($m)
            && is_int($m['nro_registro'] ?? null) && $m['nro_registro'] > 0
            && in_array($m['tipo'] ?? null, self::TIPOS_MOVIMIENTO, true)
            && is_string($m['fecha_hora'] ?? null)
            && preg_match('/^\d{4}-\d{2}-\d{2} ([01]\d|2[0-3]):[0-5]\d:[0-5]\d$/', $m['fecha_hora']);

        if (!$ok) {
            throw new ValidacionException('El informe contiene una marcación con formato inválido.');
        }
    }
}
