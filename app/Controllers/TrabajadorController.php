<?php

namespace App\Controllers;

use App\Core\Database;
use App\Core\Flash;
use App\Core\ValidacionException;
use App\Models\PersonalModel;
use PDOException;

class TrabajadorController
{
    private PersonalModel $model;

    public function __construct()
    {
        $this->model = new PersonalModel(Database::conectar());
    }

    /**
     * POST /trabajador/guardar
     * Carga masiva de trabajadores de un cargo. Termina siempre en una redirección
     * a la vista Trabajadores con ?status=success|duplicado|error
     */
    public function guardar(): void
    {
        try {
            $total = $this->model->registrarLote((int) ($_POST['id_cargo'] ?? 0), $this->filasDelFormulario());
            Flash::exito($total === 1 ? '✅ Se registró 1 trabajador.' : "✅ Se registraron {$total} trabajadores.");
            $this->volverTrabajadores('success');
        } catch (ValidacionException $e) {
            Flash::error('⚠️ ' . $e->getMessage() . ' No se guardó ningún trabajador.');
            $this->volverTrabajadores($e->motivo() === ValidacionException::DUPLICADO ? 'duplicado' : 'error');
        } catch (PDOException $e) {
            error_log('Error al registrar personal: ' . $e->getMessage());
            // 23505 = violación de UNIQUE (por si otro usuario registró la misma cédula al mismo tiempo)
            $this->volverTrabajadores((string) $e->getCode() === '23505' ? 'duplicado' : 'error');
        }
    }

    /** POST /trabajador/actualizar  (edición desde la ventana de la lista de trabajadores) */
    public function actualizar(): void
    {
        try {
            $this->model->actualizar($_POST);
            $this->volverTrabajadores('persona_ok');
        } catch (ValidacionException $e) {
            Flash::error('⚠️ ' . $e->getMessage());
            $this->volverTrabajadores('persona_' . $e->motivo());
        } catch (PDOException $e) {
            error_log('Trabajadores (actualizar): ' . $e->getMessage());
            $this->volverTrabajadores((string) $e->getCode() === '23505' ? 'persona_duplicado' : 'persona_error');
        }
    }

    /** POST /trabajador/estado  (activar / inactivar desde la lista de trabajadores) */
    public function estado(): void
    {
        $activar = ($_POST['activo'] ?? '0') === '1';
        try {
            $nombre = $this->model->cambiarEstado((int) ($_POST['id_persona'] ?? 0), $activar);
            Flash::exito($activar ? "✅ {$nombre} quedó activo." : "✅ {$nombre} quedó inactivo: ya no se importarán sus marcaciones.");
            $this->volverTrabajadores('persona_estado_ok');
        } catch (ValidacionException $e) {
            Flash::error('⚠️ ' . $e->getMessage());
            $this->volverTrabajadores('persona_invalido');
        } catch (PDOException $e) {
            error_log('Trabajadores (estado): ' . $e->getMessage());
            $this->volverTrabajadores('persona_error');
        }
    }

    /**
     * El formulario envía arreglos paralelos (cedula[], nombre[], ...): se convierten en
     * una fila por trabajador. Las filas sin cédula se ignoran (son filas vacías del formulario).
     */
    private function filasDelFormulario(): array
    {
        $campos = ['cedula', 'nombre', 'apellido', 'nro_registro', 'id_turno', 'fecha_ingreso'];
        $columnas = [];
        foreach ($campos as $campo) {
            $columnas[$campo] = is_array($_POST[$campo] ?? null) ? $_POST[$campo] : [];
        }

        $filas = [];
        foreach ($columnas['cedula'] as $i => $cedula) {
            if (!is_scalar($cedula) || trim((string) $cedula) === '') {
                continue;
            }
            $fila = [];
            foreach ($campos as $campo) {
                $fila[$campo] = $columnas[$campo][$i] ?? '';
            }
            $filas[] = $fila;
        }
        return $filas;
    }

    private function volverTrabajadores(string $status): void
    {
        redirigir('dashboard/index', ['vista' => 'trabajadores', 'status' => $status]);
    }
}
