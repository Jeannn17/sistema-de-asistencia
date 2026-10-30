<?php

namespace App\Controllers;

use App\Core\Database;
use App\Core\Flash;
use App\Core\Permisos;
use App\Core\ValidacionException;
use App\Models\UsuarioModel;
use PDOException;

/**
 * Módulo "Sección": perfil propio y, para la directiva principal, aprobación de cuentas y roles.
 * La vista es app/Views/dashboard/seccion.php (la carga DashboardController::datosSeccion).
 *
 * Seguridad (además de lo que ya revisa public/index.php: sesión activa, rol, POST y token CSRF):
 *  - perfil:  cada quien solo puede editar SU cuenta (el id sale de la sesión, nunca del formulario)
 *  - aprobar, rechazar y rol: solo la directiva principal (Permisos::esDirectivaPrincipal)
 */
class SeccionController
{
    private UsuarioModel $model;

    public function __construct()
    {
        $this->model = new UsuarioModel(Database::conectar());
    }

    /** POST /seccion/perfil  (nombre, usuario, correo y, opcionalmente, contraseña nueva) */
    public function perfil(): void
    {
        try {
            $nuevo = $this->model->actualizarPerfil((int) $_SESSION['usuario_id'], $_POST, Permisos::directivaPrincipal());
            $_SESSION['nombre']  = $nuevo['nombre'];
            $_SESSION['usuario'] = $nuevo['usuario'];
            if ($nuevo['cambio_clave']) {
                session_regenerate_id(true);   // la sesión vieja deja de servir tras cambiar la clave
            }
            $incluidos = array_keys(array_filter(['el correo' => $nuevo['cambio_correo'], 'la contraseña' => $nuevo['cambio_clave']]));
            $detalle   = $incluidos ? ' (' . (count($incluidos) > 1 ? 'se cambiaron ' : 'se cambió ') . implode(' y ', $incluidos) . ')' : '';
            Flash::exito('✅ Tus datos se actualizaron' . $detalle . '.');
        } catch (ValidacionException $e) {
            Flash::error('⚠️ ' . $e->getMessage());
        } catch (PDOException $e) {
            $this->errorBD('perfil', $e, (string) $e->getCode() === '23505' ? 'Ese usuario o ese correo ya está en uso.' : null);
        }
        $this->volver();
    }

    /** POST /seccion/aprobar  (id de una cuenta pendiente) */
    public function aprobar(): void
    {
        $this->exigirDirectivaPrincipal();
        try {
            $nombre = $this->model->aprobar((int) ($_POST['id'] ?? 0));
            Flash::exito("✅ Se aprobó la cuenta de {$nombre}: ya puede iniciar sesión como administrativo.");
        } catch (ValidacionException $e) {
            Flash::error('⚠️ ' . $e->getMessage());
        } catch (PDOException $e) {
            $this->errorBD('aprobar', $e);
        }
        $this->volver();
    }

    /** POST /seccion/rechazar  (elimina una solicitud pendiente) */
    public function rechazar(): void
    {
        $this->exigirDirectivaPrincipal();
        try {
            $nombre = $this->model->rechazar((int) ($_POST['id'] ?? 0));
            Flash::exito("✅ Se rechazó y eliminó la solicitud de {$nombre}.");
        } catch (ValidacionException $e) {
            Flash::error('⚠️ ' . $e->getMessage());
        } catch (PDOException $e) {
            $this->errorBD('rechazar', $e);
        }
        $this->volver();
    }

    /** POST /seccion/rol  (id + rol nuevo: directivo | administrativo) */
    public function rol(): void
    {
        $this->exigirDirectivaPrincipal();
        $id = (int) ($_POST['id'] ?? 0);
        try {
            if ($id === (int) $_SESSION['usuario_id']) {
                throw new ValidacionException('No puedes cambiar tu propio rol.');
            }
            $rol    = (string) ($_POST['rol'] ?? '');
            $nombre = $this->model->cambiarRol($id, $rol, Permisos::directivaPrincipal());
            Flash::exito("✅ {$nombre} ahora es " . strtolower(UsuarioModel::ROLES[$rol]) . '.');
        } catch (ValidacionException $e) {
            Flash::error('⚠️ ' . $e->getMessage());
        } catch (PDOException $e) {
            $this->errorBD('rol', $e);
        }
        $this->volver();
    }

    // ---------------------------------------------------------------- AUXILIARES
    /** Aunque la ruta ya exige rol directivo, estas acciones son solo para la directiva principal. */
    private function exigirDirectivaPrincipal(): void
    {
        if (!Permisos::esDirectivaPrincipal()) {
            error_log('Sección: intento de acción de directiva por ' . ($_SESSION['usuario'] ?? '?'));
            redirigir('dashboard/index', ['vista' => 'seccion', 'status' => 'sin_permiso']);
        }
    }

    private function errorBD(string $accion, PDOException $e, ?string $mensaje = null): void
    {
        error_log("Sección ({$accion}): " . $e->getMessage());
        Flash::error('❌ ' . ($mensaje ?? 'No se pudo guardar el cambio. Inténtalo de nuevo.'));
    }

    private function volver(): void
    {
        redirigir('dashboard/index', ['vista' => 'seccion']);
    }
}
