<?php

namespace App\Controllers;

use App\Core\Database;
use App\Core\Flash;
use App\Core\ValidacionException;
use App\Models\JustificacionModel;
use PDOException;

class PermisoController
{
    private JustificacionModel $model;

    public function __construct()
    {
        $this->model = new JustificacionModel(Database::conectar());
    }

    /** POST /permiso/guardar  (las reglas del permiso están en JustificacionModel::registrar) */
    public function guardar(): void
    {
        try {
            $this->model->registrar($_POST);
            $this->volver('permiso_ok');
        } catch (ValidacionException $e) {
            Flash::error('⚠️ ' . $e->getMessage());
            $this->volver('permiso_' . $e->motivo());
        } catch (PDOException $e) {
            error_log('Permisos (guardar): ' . $e->getMessage());
            $this->volver('permiso_error');
        }
    }

    private function volver(string $status): void
    {
        redirigir('dashboard/index', ['vista' => 'permisos', 'status' => $status]);
    }
}
