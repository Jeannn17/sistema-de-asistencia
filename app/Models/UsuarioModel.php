<?php

namespace App\Models;

use App\Core\ValidacionException;
use App\Core\Validador;
use PDO;

/**
 * Cuentas de acceso al sistema (tabla usuario).
 *
 * Reglas de negocio:
 *  - Toda cuenta nueva nace como 'administrativo' y 'pendiente': no puede iniciar sesión
 *    hasta que la directiva principal la apruebe.
 *  - El rol solo lo cambia la directiva principal (nunca el propio usuario), y el rol
 *    de los miembros de la directiva principal no se puede cambiar.
 *  - Para modificar el perfil propio se exige la contraseña actual.
 * La lista de la directiva principal la entrega el controlador (sale de config/rutas.php).
 */
class UsuarioModel
{
    public const ROL_DIRECTIVO      = 'directivo';
    public const ROL_ADMINISTRATIVO = 'administrativo';
    public const ROLES = [self::ROL_DIRECTIVO => 'Directivo', self::ROL_ADMINISTRATIVO => 'Administrativo'];

    public const ESTADO_PENDIENTE = 'pendiente';
    public const ESTADO_APROBADO  = 'aprobado';

    private const CLAVE_MIN = 8;
    private const CLAVE_MAX = 72;   // bcrypt ignora lo que pase de 72 bytes

    /** Freno a los registros masivos (bots): solicitudes por hora desde una IP y en total */
    private const MAX_REGISTROS_IP_HORA    = 3;
    private const MAX_REGISTROS_TOTAL_HORA = 20;

    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // ================================================================ CONSULTAS
    /** Busca por nombre de usuario (para el login) */
    public function obtenerUsuario(string $usuario)
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, nombre, usuario, correo, clave, rol, estado FROM usuario WHERE usuario = :usuario'
        );
        $stmt->execute([':usuario' => $usuario]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /** Datos de una cuenta por id (sin la clave). */
    public function buscarPorId(int $id)
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, nombre, usuario, correo, rol, estado, fecha_registro FROM usuario WHERE id = :id'
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /** Solicitudes de cuenta esperando aprobación, las más antiguas primero. */
    public function listarPendientes(): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, nombre, usuario, correo, rol, fecha_registro FROM usuario
             WHERE estado = :pendiente ORDER BY fecha_registro ASC, id ASC'
        );
        $stmt->execute([':pendiente' => self::ESTADO_PENDIENTE]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Cuentas aprobadas (para el panel de roles). */
    public function listarAprobados(): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, nombre, usuario, correo, rol FROM usuario
             WHERE estado = :aprobado ORDER BY rol ASC, nombre ASC'
        );
        $stmt->execute([':aprobado' => self::ESTADO_APROBADO]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ================================================================ REGISTRO
    /**
     * Valida y crea una cuenta nueva en estado 'pendiente'. Reglas:
     *  - nombre (máx. 50), usuario de 3 a 50 caracteres [A-Za-z0-9._-], correo válido
     *  - contraseña de 8 a 72 caracteres con al menos una letra y un número
     *  - usuario y correo no registrados antes (sin distinguir mayúsculas)
     *  - máximo MAX_REGISTROS_IP_HORA solicitudes por hora desde la misma IP y
     *    MAX_REGISTROS_TOTAL_HORA en total (evita que un bot llene la lista de pendientes)
     * El rol lo fija el controlador (siempre administrativo): nunca sale del formulario.
     */
    public function registrarCuenta(array $e, string $rol, string $ip): void
    {
        $this->exigirCupoRegistro($ip);

        $nombre  = $this->validarNombre($e['nombre'] ?? '');
        $usuario = $this->validarNombreUsuario($e['usuario'] ?? '');
        $correo  = Validador::correo($e['correo'] ?? '');
        $clave   = $this->validarClave($e['clave'] ?? null);

        if ($this->existeUsuarioOCorreo($usuario, $correo)) {
            throw ValidacionException::duplicado('Ese usuario o ese correo ya está registrado.');
        }

        $this->pdo->prepare(
            'INSERT INTO usuario (nombre, usuario, correo, clave, rol, estado, ip_registro) VALUES (?, ?, ?, ?, ?, ?, ?)'
        )->execute([$nombre, $usuario, $correo, password_hash($clave, PASSWORD_DEFAULT),
                    $this->validarRol($rol), self::ESTADO_PENDIENTE, mb_substr($ip, 0, 45)]);
    }

    /** Lanza ValidacionException si en la última hora ya hubo demasiadas solicitudes. */
    private function exigirCupoRegistro(string $ip): void
    {
        $stmt = $this->pdo->prepare(
            "SELECT count(*) AS total, count(*) FILTER (WHERE ip_registro = :ip) AS por_ip
             FROM usuario WHERE fecha_registro > now() - interval '1 hour'"
        );
        $stmt->execute([':ip' => $ip]);
        $f = $stmt->fetch(PDO::FETCH_ASSOC);

        if ((int) $f['por_ip'] >= self::MAX_REGISTROS_IP_HORA || (int) $f['total'] >= self::MAX_REGISTROS_TOTAL_HORA) {
            throw new ValidacionException('Se recibieron demasiadas solicitudes de cuenta. Inténtalo de nuevo en una hora.');
        }
    }

    /** ¿Ya existe ese usuario o ese correo? (sin distinguir mayúsculas) */
    public function existeUsuarioOCorreo(string $usuario, string $correo): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT 1 FROM usuario WHERE lower(usuario) = lower(:usuario) OR lower(correo) = lower(:correo) LIMIT 1'
        );
        $stmt->execute([':usuario' => $usuario, ':correo' => $correo]);
        return (bool) $stmt->fetchColumn();
    }

    // ================================================================ APROBACIÓN
    /** Aprueba una solicitud pendiente. @return string nombre de la cuenta, para el aviso */
    public function aprobar(int $id): string
    {
        $stmt = $this->pdo->prepare(
            'UPDATE usuario SET estado = :aprobado WHERE id = :id AND estado = :pendiente RETURNING nombre'
        );
        $stmt->execute([':aprobado' => self::ESTADO_APROBADO, ':pendiente' => self::ESTADO_PENDIENTE, ':id' => $id]);
        return $this->nombreOError($stmt->fetchColumn());
    }

    /**
     * Rechaza una solicitud: se elimina la cuenta. Solo se pueden eliminar cuentas PENDIENTES
     * (una cuenta aprobada puede tener historial en la tabla evento).
     */
    public function rechazar(int $id): string
    {
        $stmt = $this->pdo->prepare('DELETE FROM usuario WHERE id = :id AND estado = :pendiente RETURNING nombre');
        $stmt->execute([':pendiente' => self::ESTADO_PENDIENTE, ':id' => $id]);
        return $this->nombreOError($stmt->fetchColumn());
    }

    /**
     * Cambia el rol de una cuenta aprobada.
     * @param string[] $protegidos usuarios de la directiva principal: su rol no se puede cambiar
     * @return string nombre de la cuenta, para el aviso
     */
    public function cambiarRol(int $id, string $rol, array $protegidos): string
    {
        $rol    = $this->validarRol($rol);
        $cuenta = $this->buscarPorId($id);

        if (!$cuenta || $cuenta['estado'] !== self::ESTADO_APROBADO) {
            throw new ValidacionException('La cuenta no existe o todavía no ha sido aprobada.');
        }
        if (self::estaEn($cuenta['usuario'], $protegidos)) {
            throw new ValidacionException('El rol de la directiva principal no se puede cambiar.');
        }

        $this->pdo->prepare('UPDATE usuario SET rol = :rol WHERE id = :id')->execute([':rol' => $rol, ':id' => $id]);
        return $cuenta['nombre'];
    }

    // ================================================================ PERFIL PROPIO
    /**
     * Actualiza nombre, usuario, correo y (opcionalmente) contraseña de la cuenta $id.
     * Reglas:
     *  - siempre se exige la contraseña actual
     *  - el nuevo usuario no puede estar en uso ni ser uno de la directiva principal
     *  - el correo debe ser válido y no estar registrado en otra cuenta (sin distinguir mayúsculas)
     *  - la directiva principal no puede cambiar su nombre de usuario (su privilegio depende de él)
     *  - la contraseña nueva (si se escribe) debe confirmarse y cumplir las reglas de registro
     * El rol y el estado NO se tocan aquí.
     *
     * @param string[] $protegidos usuarios de la directiva principal
     * @return array{nombre:string, usuario:string, cambio_clave:bool, cambio_correo:bool} datos nuevos, para la sesión
     */
    public function actualizarPerfil(int $id, array $e, array $protegidos): array
    {
        $stmt = $this->pdo->prepare('SELECT usuario, correo, clave FROM usuario WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $actual = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$actual) {
            throw new ValidacionException('Tu cuenta ya no existe.');
        }

        $claveActual = is_string($e['clave_actual'] ?? null) ? $e['clave_actual'] : '';
        if ($claveActual === '' || !password_verify($claveActual, $actual['clave'])) {
            throw new ValidacionException('La contraseña actual no es correcta.');
        }

        $nombre  = $this->validarNombre($e['nombre'] ?? '');
        $usuario = $this->validarNombreUsuario($e['usuario'] ?? '');
        $cambiaUsuario = strtolower($usuario) !== strtolower($actual['usuario']);

        if ($cambiaUsuario) {
            if (self::estaEn($actual['usuario'], $protegidos)) {
                throw new ValidacionException('Los usuarios de la directiva principal no pueden cambiar su nombre de usuario.');
            }
            if (self::estaEn($usuario, $protegidos) || $this->existeUsuario($usuario, $id)) {
                throw ValidacionException::duplicado('El usuario "' . $usuario . '" ya está en uso.');
            }
        } elseif ($usuario !== $actual['usuario'] && self::estaEn($actual['usuario'], $protegidos)) {
            $usuario = $actual['usuario'];   // la directiva principal tampoco cambia mayúsculas/minúsculas
        }

        $correo = Validador::correo($e['correo'] ?? '');
        $cambiaCorreo = $correo !== mb_strtolower((string) $actual['correo']);
        if ($cambiaCorreo && $this->existeCorreo($correo, $id)) {
            throw ValidacionException::duplicado('El correo "' . $correo . '" ya está registrado en otra cuenta.');
        }

        $nueva = is_string($e['clave_nueva'] ?? null) ? $e['clave_nueva'] : '';
        $sql    = 'UPDATE usuario SET nombre = :nombre, usuario = :usuario, correo = :correo';
        $params = [':nombre' => $nombre, ':usuario' => $usuario, ':correo' => $correo, ':id' => $id];
        if ($nueva !== '') {
            $nueva = $this->validarClave($nueva);
            if ($nueva !== ($e['clave_confirmar'] ?? null)) {
                throw new ValidacionException('La contraseña nueva y su confirmación no coinciden.');
            }
            if (password_verify($nueva, $actual['clave'])) {
                throw new ValidacionException('La contraseña nueva debe ser distinta de la actual.');
            }
            $sql .= ', clave = :clave';
            $params[':clave'] = password_hash($nueva, PASSWORD_DEFAULT);
        }

        $this->pdo->prepare($sql . ' WHERE id = :id')->execute($params);
        return ['nombre' => $nombre, 'usuario' => $usuario, 'cambio_clave' => $nueva !== '', 'cambio_correo' => $cambiaCorreo];
    }

    /** Si el algoritmo de cifrado mejora con el tiempo, se actualiza el hash al iniciar sesión */
    public function actualizarClave(int $id, string $password): void
    {
        $this->pdo->prepare('UPDATE usuario SET clave = :clave WHERE id = :id')
                  ->execute([':clave' => password_hash($password, PASSWORD_DEFAULT), ':id' => $id]);
    }

    // ================================================================ REGLAS
    private function validarNombre($valor): string
    {
        return preg_replace('/\s+/u', ' ', Validador::texto($valor, 'el nombre', 50));
    }

    private function validarNombreUsuario($valor): string
    {
        $usuario = Validador::texto($valor, 'el usuario', 50);
        if (!preg_match('/^[A-Za-z0-9._-]{3,50}$/', $usuario)) {
            throw new ValidacionException('El usuario solo puede tener letras, números, punto, guion y guion bajo (mínimo 3).');
        }
        return $usuario;
    }

    /** La contraseña NO se recorta: los espacios también cuentan. */
    private function validarClave($valor): string
    {
        $clave = is_string($valor) ? $valor : '';
        if (strlen($clave) < self::CLAVE_MIN || strlen($clave) > self::CLAVE_MAX) {
            throw new ValidacionException('La contraseña debe tener entre ' . self::CLAVE_MIN . ' y ' . self::CLAVE_MAX . ' caracteres.');
        }
        if (!preg_match('/\pL/u', $clave) || !preg_match('/\d/', $clave)) {
            throw new ValidacionException('La contraseña debe combinar letras y números.');
        }
        return $clave;
    }

    private function validarRol(string $rol): string
    {
        return Validador::opcion($rol, array_keys(self::ROLES), 'un rol');
    }

    private function existeUsuario(string $usuario, int $exceptoId): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM usuario WHERE lower(usuario) = lower(:u) AND id <> :id LIMIT 1');
        $stmt->execute([':u' => $usuario, ':id' => $exceptoId]);
        return (bool) $stmt->fetchColumn();
    }

    private function existeCorreo(string $correo, int $exceptoId): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM usuario WHERE lower(correo) = lower(:c) AND id <> :id LIMIT 1');
        $stmt->execute([':c' => $correo, ':id' => $exceptoId]);
        return (bool) $stmt->fetchColumn();
    }

    private function nombreOError($nombre): string
    {
        if ($nombre === false) {
            throw new ValidacionException('Esa solicitud ya no está pendiente (quizá otro directivo ya la atendió).');
        }
        return (string) $nombre;
    }

    /** ¿$usuario está en la lista? (sin distinguir mayúsculas: "JeanM" = "jeanM") */
    public static function estaEn(string $usuario, array $lista): bool
    {
        return in_array(strtolower($usuario), array_map('strtolower', $lista), true);
    }

    // ------------------------------------------------------------------
    // Control de intentos fallidos de login (tabla login_intentos)
    // ------------------------------------------------------------------

    /** @return array{por_usuario:int, por_ip:int} fallos en los últimos $minutos */
    public function fallosRecientes(string $usuario, string $ip, int $minutos): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT count(*) FILTER (WHERE usuario = :usuario) AS por_usuario,
                    count(*) FILTER (WHERE ip = :ip) AS por_ip
             FROM login_intentos
             WHERE fecha > now() - make_interval(mins => CAST(:minutos AS integer))'
        );
        $stmt->execute([':usuario' => $usuario, ':ip' => $ip, ':minutos' => $minutos]);
        $f = $stmt->fetch(PDO::FETCH_ASSOC);
        return ['por_usuario' => (int) $f['por_usuario'], 'por_ip' => (int) $f['por_ip']];
    }

    public function registrarFallo(string $usuario, string $ip): void
    {
        $this->pdo->prepare('INSERT INTO login_intentos (usuario, ip) VALUES (:usuario, :ip)')
                  ->execute([':usuario' => mb_substr($usuario, 0, 50), ':ip' => mb_substr($ip, 0, 45)]);
        // Limpieza: no se guardan intentos de más de un día
        $this->pdo->exec("DELETE FROM login_intentos WHERE fecha < now() - interval '1 day'");
    }

    public function limpiarFallos(string $usuario): void
    {
        $this->pdo->prepare('DELETE FROM login_intentos WHERE usuario = :usuario')->execute([':usuario' => $usuario]);
    }
}
