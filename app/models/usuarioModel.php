<?php
class UsuarioModel {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    // 1. Obtener todos los usuarios (READ - Listar)
    public function obtenerTodos() {
        $sql = "SELECT id, nombre, usuario, correo, rol, created_at FROM usuario ORDER BY id DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 2. Obtener un usuario específico por su ID (READ - Ver detalle)
    public function obtenerPorId($id) {
        $sql = "SELECT id, cedula, nombre, usuario, correo, rol FROM usuario WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // 3. Buscar usuario por Nombre de Usuario o Cédula (Especial para el LOGIN)
    public function obtenerUsuario($identificador) {
        $sql = "SELECT * FROM usuario WHERE usuario = :usuario";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':usuario' => $identificador]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // 4. Registrar nuevo usuario (CREATE)
    public function registrar($nombre, $usuario, $correo, $password, $rol) {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO usuario (nombre, usuario, correo, clave, rol) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$nombre, $usuario, $correo, $passwordHash, $rol]);
    }

    // 5. Actualizar datos de usuario (UPDATE)
    public function actualizar($id, $nombre, $usuario, $correo, $rol) {
        $sql = "UPDATE usuario SET nombre = ?, usuario = ?, correo = ?, rol = ? WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$nombre, $usuario, $correo, $rol, $id]);
    }

    // 6. Actualizar contraseña de forma independiente (UPDATE password)
    public function actualizarPassword($id, $password) {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $sql = "UPDATE usuario SET clave = ? WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$passwordHash, $id]);
    }

    // 7. Eliminar usuario (DELETE)
    public function eliminar($id) {
        $sql = "DELETE FROM usuario WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$id]);
    }
}
?>