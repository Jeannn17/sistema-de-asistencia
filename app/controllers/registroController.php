<?php
class registroController {
    private Registro $model;

    public function __construct(PDO $db) {
        $this->model = new Registro($db);
    }

    public function index(): void {
        $listado = $this->model->all();
        $turnos  = $this->model->getTurnos();
        require_once __DIR__ . '/../views/dashboard.php';
    }

    public function create(): void {
        $cargos = $this->model->getCargos();
        $turnos = $this->model->getTurnos();
        require_once __DIR__ . '/../views/dashboard.php';
    }

    /**
     * Procesa la carga masiva de personas por departamento (cargo).
     * Siempre termina en una redirección al dashboard con ?status=success|duplicado|error
     */
    public function store(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?action=dashboard');
            exit;
        }

        $id_cargo = isset($_POST['id_cargo']) ? (int)$_POST['id_cargo'] : 0;

        // Arreglos dinámicos recibidos desde el formulario (un elemento por trabajador)
        $cedulas       = (array)($_POST['cedula'] ?? []);
        $nombres       = (array)($_POST['nombre'] ?? []);
        $apellidos     = (array)($_POST['apellido'] ?? []);
        $nro_registros = (array)($_POST['nro_registro'] ?? []);
        $turnos        = (array)($_POST['id_turno'] ?? []);
        $fechas        = (array)($_POST['fecha_ingreso'] ?? []);

        $personas = [];

        foreach ($cedulas as $index => $cedula) {
            $cedula = trim((string)$cedula);

            // Ignoramos filas vacías
            if ($cedula === '') {
                continue;
            }

            $nombre   = trim((string)($nombres[$index] ?? ''));
            $apellido = trim((string)($apellidos[$index] ?? ''));
            $turno    = (int)($turnos[$index] ?? 0);
            $fecha    = (string)($fechas[$index] ?? '');
            $nro      = $nro_registros[$index] ?? '';

            // Validaciones básicas: cualquier fila inválida cancela todo el lote
            $fechaValida = DateTime::createFromFormat('Y-m-d', $fecha);
            if ($nombre === '' || $apellido === '' || $turno <= 0
                || !$fechaValida || $fechaValida->format('Y-m-d') !== $fecha
                || strlen($cedula) > 12) {
                $this->redirigir('error');
            }

            $personas[] = [
                'cedula'        => $cedula,
                'nombre'        => $nombre,
                'apellido'      => $apellido,
                'nro_registro'  => ($nro !== '' && $nro !== null) ? (int)$nro : null,
                'id_cargo'      => $id_cargo,
                'id_turno'      => $turno,
                'fecha_ingreso' => $fecha,
                'activo'        => 1
            ];
        }

        if ($id_cargo <= 0 || empty($personas)) {
            $this->redirigir('error');
        }

        try {
            $this->model->createBatch($personas);
            $this->redirigir('success');
        } catch (PDOException $e) {
            error_log('Error al registrar personal: ' . $e->getMessage());
            // 23505 = violación de UNIQUE (cédula o N° de registro repetido)
            $this->redirigir((string)$e->getCode() === '23505' ? 'duplicado' : 'error');
        }
    }

    public function editView(int $id_persona): void {
        $personal = $this->model->find($id_persona);
        if (!$personal) {
            header('Location: index.php?action=dashboard');
            exit;
        }
        $cargos = $this->model->getCargos();
        $turnos = $this->model->getTurnos();
        require_once __DIR__ . '/../views/dashboard.php';
    }

    public function update(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $personal = [
                'id_persona'    => (int)$_POST['id_persona'],
                'cedula'        => trim($_POST['cedula']),
                'nombre'        => trim($_POST['nombre']),
                'apellido'      => trim($_POST['apellido']),
                'nro_registro'  => !empty($_POST['nro_registro']) ? (int)$_POST['nro_registro'] : null,
                'id_cargo'      => (int)$_POST['id_cargo'],
                'id_turno'      => (int)$_POST['id_turno'],
                'fecha_ingreso' => $_POST['fecha_ingreso'],
                // PDO + PostgreSQL convierte false en '' y rompe el boolean: usar 1/0
                'activo'        => isset($_POST['activo']) ? 1 : 0
            ];
            $this->model->edit($personal);
            header('Location: index.php?action=dashboard');
            exit;
        }
    }

    /** Redirige al dashboard con el resultado y corta la ejecución. */
    private function redirigir(string $status): void {
        header('Location: index.php?action=dashboard&status=' . $status);
        exit;
    }
}
