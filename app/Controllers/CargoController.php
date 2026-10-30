<?php

namespace App\Controllers;

use App\Core\Database;
use App\Core\Flash;
use App\Core\ValidacionException;
use App\Models\CatalogosModel;
use PDOException;

class CargoController
{
    private CatalogosModel $model;

    public function __construct()
    {
        $this->model = new CatalogosModel(Database::conectar());
    }

    /** POST /cargo/guardar  (id_cargo = 0 crea; con id actualiza) */
    public function guardar(): void
    {
        try {
            $this->model->guardarCargo((int) ($_POST['id_cargo'] ?? 0), $_POST);
            $this->volver('cargo_ok');
        } catch (ValidacionException $e) {
            Flash::error('⚠️ ' . $e->getMessage());
            $this->volver('cargo_' . $e->motivo());
        } catch (PDOException $e) {
            error_log('Cargos (guardar): ' . $e->getMessage());
            $this->volver('cargo_error');
        }
    }

    /** POST /cargo/estado  (activar / desactivar) */
    public function estado(): void
    {
        try {
            $this->model->cambiarEstadoCargo((int) ($_POST['id_cargo'] ?? 0), ($_POST['activo'] ?? '0') === '1');
            $this->volver('cargo_estado_ok');
        } catch (ValidacionException $e) {
            Flash::error('⚠️ ' . $e->getMessage());
            $this->volver('cargo_invalido');
        } catch (PDOException $e) {
            error_log('Cargos (estado): ' . $e->getMessage());
            $this->volver('cargo_error');
        }
    }

    private function volver(string $status): void
    {
        redirigir('dashboard/index', ['vista' => 'cargos', 'status' => $status]);
    }
}
