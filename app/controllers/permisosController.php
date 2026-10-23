<?php

class permisosController
{
    /** tipo => [etiqueta, ¿es de un solo día con hora?] */
    public const TIPOS = [
        'reposo'            => ['Reposo médico',              false],
        'accidente_laboral' => ['Accidente laboral',          false],
        'permiso_dia'       => ['Permiso por el día',         false],
        'salida_temprana'   => ['Permiso de salida temprana', true],
        'llegada_tarde'     => ['Permiso de llegada tarde',   true],
    ];

    private justificacionModel $model;

    public function __construct(PDO $db)
    {
        $this->model = new justificacionModel($db);
    }

    /**
     * Datos que necesita la vista del dashboard.
     * Cada consulta va por separado: si falla la lista de permisos (por ejemplo, porque aún
     * no se ejecutó la migración), el selector de trabajadores sigue funcionando.
     */
    public function datosVista(): array
    {
        $personal  = [];
        $recientes = [];

        try {
            $personal = $this->model->obtenerPersonalActivo();
        } catch (PDOException $e) {
            error_log('Permisos (personal): ' . $e->getMessage());
        }

        try {
            $recientes = $this->model->listarRecientes();
        } catch (PDOException $e) {
            error_log('Permisos (recientes): ' . $e->getMessage());
        }

        return ['personal' => $personal, 'recientes' => $recientes];
    }

    /** Acción del router: index.php?action=permiso_guardar (POST) */
    public function guardar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirigir('permiso_invalido');
        }

        $idPersona = (int) ($_POST['id_persona'] ?? 0);
        $tipo      = (string) ($_POST['tipo'] ?? '');
        $motivo    = trim((string) ($_POST['motivo'] ?? ''));
        $inicio    = $this->fechaValida($_POST['fecha_inicio'] ?? '');

        if ($idPersona <= 0 || !isset(self::TIPOS[$tipo]) || $inicio === null
            || $motivo === '' || mb_strlen($motivo) > 500) {
            $this->redirigir('permiso_invalido');
        }

        $unSoloDia = self::TIPOS[$tipo][1];
        $hora      = null;

        if ($unSoloDia) {
            // Salida temprana / llegada tarde: un día y una hora
            $fin  = $inicio;
            $hora = $this->horaValida($_POST['hora'] ?? '');
            if ($hora === null) {
                $this->redirigir('permiso_invalido');
            }
        } elseif ($tipo === 'permiso_dia') {
            $fin = $inicio;
        } else {
            // Reposo / accidente: rango de fechas
            $fin = $this->fechaValida($_POST['fecha_fin'] ?? '');
            if ($fin === null || $fin < $inicio) {
                $this->redirigir('permiso_invalido');
            }
        }

        try {
            if (!$this->model->personaActiva($idPersona)) {
                $this->redirigir('permiso_invalido');
            }
            if ($this->model->existeCruce($idPersona, $tipo, $inicio, $fin)) {
                $this->redirigir('permiso_duplicado');
            }

            $this->model->crear([
                'id_persona'   => $idPersona,
                'tipo'         => $tipo,
                'fecha_inicio' => $inicio,
                'fecha_fin'    => $fin,
                'hora'         => $hora,
                'motivo'       => $motivo,
            ]);

            $this->redirigir('permiso_ok');
        } catch (PDOException $e) {
            error_log('Permisos (guardar): ' . $e->getMessage());
            $this->redirigir('permiso_error');
        }
    }

    /** 'YYYY-MM-DD' válido o null */
    private function fechaValida($valor): ?string
    {
        $d = DateTime::createFromFormat('!Y-m-d', (string) $valor);
        return ($d && $d->format('Y-m-d') === $valor) ? $valor : null;
    }

    /** 'HH:MM' válido -> 'HH:MM:00' o null */
    private function horaValida($valor): ?string
    {
        return preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', (string) $valor) ? $valor . ':00' : null;
    }

    private function redirigir(string $status): void
    {
        header('Location: index.php?' . http_build_query([
            'action' => 'dashboard',
            'vista'  => 'permisos',
            'status' => $status,
        ]));
        exit;
    }
}
