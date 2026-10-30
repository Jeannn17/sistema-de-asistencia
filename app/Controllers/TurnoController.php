<?php

namespace App\Controllers;

use App\Core\Database;
use App\Core\Flash;
use App\Core\ValidacionException;
use App\Models\CatalogosModel;
use PDOException;

class TurnoController
{
    private CatalogosModel $model;

    public function __construct()
    {
        $this->model = new CatalogosModel(Database::conectar());
    }

    /** POST /turno/guardar  (id_turno = 0 crea; con id actualiza) */
    public function guardar(): void
    {
        try {
            $this->model->guardarTurno((int) ($_POST['id_turno'] ?? 0), $_POST);
            $this->volver('turno_ok');
        } catch (ValidacionException $e) {
            Flash::error('⚠️ ' . $e->getMessage());
            $this->volver('turno_' . $e->motivo());
        } catch (PDOException $e) {
            error_log('Turnos (guardar): ' . $e->getMessage());
            $this->volver('turno_error');
        }
    }

    /** POST /turno/estado  (activar / desactivar) */
    public function estado(): void
    {
        try {
            $this->model->cambiarEstadoTurno((int) ($_POST['id_turno'] ?? 0), ($_POST['activo'] ?? '0') === '1');
            $this->volver('turno_estado_ok');
        } catch (ValidacionException $e) {
            Flash::error('⚠️ ' . $e->getMessage());
            $this->volver('turno_invalido');
        } catch (PDOException $e) {
            error_log('Turnos (estado): ' . $e->getMessage());
            $this->volver('turno_error');
        }
    }

    private function volver(string $status): void
    {
        redirigir('dashboard/index', ['vista' => 'turnos', 'status' => $status]);
    }
}
