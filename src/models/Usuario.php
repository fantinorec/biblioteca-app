<?php

class Usuario
{
    public static function buscarPorEmail(string $email): ?array
    {
        $pdo = obtenerConexion();
        $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $fila = $stmt->fetch();
        return $fila ?: null;
    }

    public static function buscarPorId(int $id): ?array
    {
        $pdo = obtenerConexion();
        $stmt = $pdo->prepare(
            'SELECT u.*, p.nombre AS perfil_nombre, p.es_administrador
             FROM usuarios u INNER JOIN perfiles p ON p.id = u.perfil_id
             WHERE u.id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $fila = $stmt->fetch();
        return $fila ?: null;
    }

    public static function buscarPorTokenVerificacion(string $token): ?array
    {
        $pdo = obtenerConexion();
        $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE token_verificacion = :t LIMIT 1');
        $stmt->execute(['t' => $token]);
        $fila = $stmt->fetch();
        return $fila ?: null;
    }

    public static function buscarPorTokenReset(string $token): ?array
    {
        $pdo = obtenerConexion();
        $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE token_reset = :t LIMIT 1');
        $stmt->execute(['t' => $token]);
        $fila = $stmt->fetch();
        return $fila ?: null;
    }

    public static function existeEmail(string $email, ?int $excluirId = null): bool
    {
        $pdo = obtenerConexion();
        $sql = 'SELECT COUNT(*) FROM usuarios WHERE email = :email';
        $params = ['email' => $email];
        if ($excluirId) {
            $sql .= ' AND id <> :id';
            $params['id'] = $excluirId;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    public static function listar(?string $estado = null): array
    {
        $pdo = obtenerConexion();
        $sql = 'SELECT u.*, p.nombre AS perfil_nombre FROM usuarios u INNER JOIN perfiles p ON p.id = u.perfil_id';
        $params = [];
        if ($estado) {
            $sql .= ' WHERE u.estado = :estado';
            $params['estado'] = $estado;
        }
        $sql .= ' ORDER BY u.id DESC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function crear(array $datos): int
    {
        $pdo = obtenerConexion();
        $stmt = $pdo->prepare(
            'INSERT INTO usuarios (nombre, apellido, email, password_hash, perfil_id, email_verificado, token_verificacion, token_verificacion_expira, estado)
             VALUES (:nombre, :apellido, :email, :password_hash, :perfil_id, :email_verificado, :token, :token_expira, :estado)'
        );
        $stmt->execute([
            'nombre'            => $datos['nombre'],
            'apellido'          => $datos['apellido'],
            'email'             => $datos['email'],
            'password_hash'     => $datos['password_hash'],
            'perfil_id'         => $datos['perfil_id'],
            'email_verificado'  => $datos['email_verificado'] ?? 0,
            'token'             => $datos['token_verificacion'] ?? null,
            'token_expira'      => $datos['token_verificacion_expira'] ?? null,
            'estado'            => $datos['estado'] ?? 'activo',
        ]);
        return (int) $pdo->lastInsertId();
    }

    public static function actualizarDatos(int $id, array $datos): void
    {
        $pdo = obtenerConexion();
        $stmt = $pdo->prepare(
            'UPDATE usuarios SET nombre = :nombre, apellido = :apellido, email = :email, perfil_id = :perfil_id WHERE id = :id'
        );
        $stmt->execute([
            'nombre'    => $datos['nombre'],
            'apellido'  => $datos['apellido'],
            'email'     => $datos['email'],
            'perfil_id' => $datos['perfil_id'],
            'id'        => $id,
        ]);
    }

    public static function actualizarPassword(int $id, string $passwordHash): void
    {
        $pdo = obtenerConexion();
        $stmt = $pdo->prepare('UPDATE usuarios SET password_hash = :hash, token_reset = NULL, token_reset_expira = NULL WHERE id = :id');
        $stmt->execute(['hash' => $passwordHash, 'id' => $id]);
    }

    public static function establecerTokenReset(int $id, string $token, string $expira): void
    {
        $pdo = obtenerConexion();
        $stmt = $pdo->prepare('UPDATE usuarios SET token_reset = :token, token_reset_expira = :expira WHERE id = :id');
        $stmt->execute(['token' => $token, 'expira' => $expira, 'id' => $id]);
    }

    public static function marcarEmailVerificado(int $id): void
    {
        $pdo = obtenerConexion();
        $stmt = $pdo->prepare('UPDATE usuarios SET email_verificado = 1, token_verificacion = NULL, token_verificacion_expira = NULL WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public static function darBaja(int $id): void
    {
        $pdo = obtenerConexion();
        $stmt = $pdo->prepare('UPDATE usuarios SET estado = "baja" WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public static function reactivar(int $id): void
    {
        $pdo = obtenerConexion();
        $stmt = $pdo->prepare('UPDATE usuarios SET estado = "activo" WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public static function registrarLoginExitoso(int $id): void
    {
        $pdo = obtenerConexion();
        $stmt = $pdo->prepare('UPDATE usuarios SET ultimo_login = NOW(), intentos_fallidos = 0, bloqueado_hasta = NULL WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public static function registrarLoginFallido(int $id): void
    {
        $pdo = obtenerConexion();
        $stmt = $pdo->prepare('UPDATE usuarios SET intentos_fallidos = intentos_fallidos + 1 WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public static function contarAdministradoresActivos(): int
    {
        $pdo = obtenerConexion();
        $stmt = $pdo->query(
            'SELECT COUNT(*) FROM usuarios u
             INNER JOIN perfiles p ON p.id = u.perfil_id
             WHERE p.es_administrador = 1 AND u.estado = "activo"'
        );
        return (int) $stmt->fetchColumn();
    }

    public static function esUnicoAdministrador(int $usuarioId): bool
    {
        $usuario = self::buscarPorId($usuarioId);
        if (!$usuario || (int) $usuario['es_administrador'] !== 1 || $usuario['estado'] !== 'activo') {
            return false;
        }
        return self::contarAdministradoresActivos() <= 1;
    }
}
