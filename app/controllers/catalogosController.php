<?php

/** Acciones de turnos y cargos (solo las puede ejecutar quien tenga la capacidad; lo valida index.php). */
class catalogosController
{
    private catalogosModel $model;

    public function __construct(PDO $db)
    {
        $this->model = new catalogosModel($db);
    }

    // ---------------------------------------------------------------- TURNOS
    public function turnoGuardar(): void
    {
        $this->soloPost('turnos');

        $id     = (int) ($_POST['id_turno'] ?? 0);
        $nombre = trim((string) ($_POST['nombre_turno'] ?? ''));

        $i1 = $this->hora($_POST['inicio_p1'] ?? '', true);
        $f1 = $this->hora($_POST['fin_p1'] ?? '', true);
        $i2 = $this->hora($_POST['inicio_p2'] ?? '');
        $f2 = $this->hora($_POST['fin_p2'] ?? '');
        $ie = $this->hora($_POST['inicio_extra'] ?? '');
        $fe = $this->hora($_POST['fin_extra'] ?? '');

        $horas = [$i1, $f1, $i2, $f2, $ie, $fe];
        $horaInvalida = in_array(false, $horas, true);
        // El periodo 2 y el extra se llenan completos (inicio y fin) o se dejan vacíos
        $paresIncompletos = ($i2 === null) !== ($f2 === null) || ($ie === null) !== ($fe === null);

        if ($nombre === '' || mb_strlen($nombre) > 60 || $horaInvalida || $paresIncompletos) {
            $this->redirigir('turnos', 'turno_invalido');
        }

        try {
            if ($this->model->existeTurno($nombre, $id)) {
                $this->redirigir('turnos', 'turno_duplicado');
            }
            $datos = [
                'nombre_turno' => $nombre,
                'inicio_p1' => $i1, 'fin_p1' => $f1,
                'inicio_p2' => $i2, 'fin_p2' => $f2,
                'inicio_extra' => $ie, 'fin_extra' => $fe,
            ];
            $id > 0 ? $this->model->actualizarTurno($id, $datos) : $this->model->crearTurno($datos);
            $this->redirigir('turnos', 'turno_ok');
        } catch (PDOException $e) {
            error_log('Turnos (guardar): ' . $e->getMessage());
            $this->redirigir('turnos', 'turno_error');
        }
    }

    public function turnoEstado(): void
    {
        $this->soloPost('turnos');
        $id = (int) ($_POST['id_turno'] ?? 0);
        if ($id <= 0) {
            $this->redirigir('turnos', 'turno_invalido');
        }
        try {
            $this->model->cambiarEstadoTurno($id, ($_POST['activo'] ?? '0') === '1');
            $this->redirigir('turnos', 'turno_estado_ok');
        } catch (PDOException $e) {
            error_log('Turnos (estado): ' . $e->getMessage());
            $this->redirigir('turnos', 'turno_error');
        }
    }

    // ---------------------------------------------------------------- CARGOS
    public function cargoGuardar(): void
    {
        $this->soloPost('cargos');

        $id     = (int) ($_POST['id_cargo'] ?? 0);
        $nombre = trim((string) ($_POST['nombre_cargo'] ?? ''));

        if ($nombre === '' || mb_strlen($nombre) > 60) {
            $this->redirigir('cargos', 'cargo_invalido');
        }

        try {
            if ($this->model->existeCargo($nombre, $id)) {
                $this->redirigir('cargos', 'cargo_duplicado');
            }
            $id > 0 ? $this->model->actualizarCargo($id, $nombre) : $this->model->crearCargo($nombre);
            $this->redirigir('cargos', 'cargo_ok');
        } catch (PDOException $e) {
            error_log('Cargos (guardar): ' . $e->getMessage());
            $this->redirigir('cargos', 'cargo_error');
        }
    }

    public function cargoEstado(): void
    {
        $this->soloPost('cargos');
        $id = (int) ($_POST['id_cargo'] ?? 0);
        if ($id <= 0) {
            $this->redirigir('cargos', 'cargo_invalido');
        }
        try {
            $this->model->cambiarEstadoCargo($id, ($_POST['activo'] ?? '0') === '1');
            $this->redirigir('cargos', 'cargo_estado_ok');
        } catch (PDOException $e) {
            error_log('Cargos (estado): ' . $e->getMessage());
            $this->redirigir('cargos', 'cargo_error');
        }
    }

    // ---------------------------------------------------------------- AUXILIARES
    /**
     * 'HH:MM' -> 'HH:MM:00'. Vacío -> null (o false si era obligatoria). Inválido -> false.
     */
    private function hora($valor, bool $obligatoria = false)
    {
        $valor = trim((string) $valor);
        if ($valor === '') {
            return $obligatoria ? false : null;
        }
        return preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $valor) ? $valor . ':00' : false;
    }

    private function soloPost(string $vista): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirigir($vista, '');
        }
    }

    private function redirigir(string $vista, string $status): void
    {
        $params = ['action' => 'dashboard', 'vista' => $vista];
        if ($status !== '') {
            $params['status'] = $status;
        }
        header('Location: index.php?' . http_build_query($params));
        exit;
    }
}
