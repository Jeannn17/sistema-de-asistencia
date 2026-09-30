<?php
class registroController {
    private Registro $model;
    
    public function __construct(Registro $model) {
        $this->model = $model;
    }

    public function index(): void {
        $listado = $this->model->all();
        require_once __DIR__ . '/../views/dashboard.php';
    }

    public function create(): void {
        $cargos = $this->model->getCargos();
        $turnos = $this->model->getTurnos();
        require_once __DIR__ . '/../views/dashboard.php';
    }

    /**
     * Procesa la carga masiva de personas por departamento
     */
    public function store(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        // 1. Evitamos el "Undefined array key" validando si existen antes de usarlos
        $id_cargo = isset($_POST['id_cargo']) ? (int)$_POST['id_cargo'] : null;

        // Si turno y fecha son UN SOLO CAMPO para todo el lote (no tienen [] en el HTML):
        // Nota: Si en el HTML les pusiste [], debes recibirlos abajo en la sección de arreglos dinámicos.
        $id_turno_comun = isset($_POST['id_turno']) ? $_POST['id_turno'] : 1;
        $fecha_comun = isset($_POST['fecha_ingreso']) ? $_POST['fecha_ingreso'] : date('Y-m-d');

        // Arreglos dinámicos recibidos desde el formulario
        $cedulas       = $_POST['cedula'] ?? [];
        $nombres       = $_POST['nombre'] ?? [];
        $apellidos     = $_POST['apellido'] ?? [];
        $nro_registros = $_POST['nro_registro'] ?? [];

        $personas = [];

        // Recorremos los campos dinámicos
        foreach ($cedulas as $index => $cedula) {
            $cedulaLimpia = trim($cedula);

            // Ignoramos filas vacías
            if (!empty($cedulaLimpia)) {
                
                // Extraemos turno y fecha de forma segura (por si el HTML los envía como arreglos o como valor único)
                $turno_fila = is_array($id_turno_comun) ? ($id_turno_comun[$index] ?? 1) : $id_turno_comun;
                $fecha_fila = is_array($fecha_comun) ? ($fecha_comun[$index] ?? date('Y-m-d')) : $fecha_comun;

                $personas[] = [
                    'cedula'        => $cedulaLimpia,
                    'nombre'        => trim($nombres[$index] ?? ''),
                    'apellido'      => trim($apellidos[$index] ?? ''),
                    'nro_registro'  => !empty($nro_registros[$index]) ? (int)$nro_registros[$index] : null,
                    'id_cargo'      => $id_cargo,
                    'id_turno'      => (int)$turno_fila,
                    'fecha_ingreso' => $fecha_fila,
                    'activo'        => 1 // <-- FALTABA ESTO PARA TU MODELO
                ];
            }
        }

        // Se envía al modelo
        $exito = $this->model->createBatch($personas);

        if ($exito) {
            header('Location: index.php?action=index&status=success');
            exit;
            } else {
            header('Location: index.php?action=index&status=error');
            }
        }
    }

    public function editView(int $id_persona): void {
        $personal = $this->model->find($id_persona);
        if (!$personal) {
            header('Location: index.php?action=index');
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
                'activo'        => isset($_POST['activo'])
            ];    
            $this->model->edit($personal);
            header('Location: index.php?action=index');
            exit;
        }
    }
}