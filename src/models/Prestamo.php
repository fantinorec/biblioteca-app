<?php

class Prestamo
{
    private const MESES_PLAZO = 3;

    public static function calcularFechaDevolucionEstimada(): string
    {
        return date('Y-m-d', strtotime('+' . self::MESES_PLAZO . ' months'));
    }

    public static function listar(?string $estado = null, ?int $usuarioId = null, ?string $busqueda = null): array
    {
        $pdo = obtenerConexion();
        $sql = 'SELECT pr.*, l.titulo AS libro_titulo, u.nombre AS usuario_nombre, u.apellido AS usuario_apellido, u.email AS usuario_email
                FROM prestamos pr
                INNER JOIN libros l ON l.id = pr.libro_id
                INNER JOIN usuarios u ON u.id = pr.usuario_id
                WHERE 1 = 1';
        $params = [];
        if ($estado) {
            $sql .= ' AND pr.estado = :estado';
            $params['estado'] = $estado;
        }
        if ($usuarioId) {
            $sql .= ' AND pr.usuario_id = :usuario_id';
            $params['usuario_id'] = $usuarioId;
        }
        if ($busqueda !== null && $busqueda !== '') {
            $sql .= ' AND (u.nombre LIKE :busqueda_nombre
                           OR u.apellido LIKE :busqueda_apellido
                           OR CONCAT(u.nombre, " ", u.apellido) LIKE :busqueda_completo
                           OR u.email LIKE :busqueda_email)';
            $comodin = '%' . $busqueda . '%';
            $params['busqueda_nombre'] = $comodin;
            $params['busqueda_apellido'] = $comodin;
            $params['busqueda_completo'] = $comodin;
            $params['busqueda_email'] = $comodin;
        }
        $sql .= ' ORDER BY pr.id DESC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function buscarPorId(int $id): ?array
    {
        $pdo = obtenerConexion();
        $stmt = $pdo->prepare('SELECT * FROM prestamos WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function obtenerDetallado(int $id): ?array
    {
        $pdo = obtenerConexion();
        $stmt = $pdo->prepare(
            'SELECT pr.*, l.titulo AS libro_titulo, u.nombre AS usuario_nombre, u.apellido AS usuario_apellido, u.email AS usuario_email
             FROM prestamos pr
             INNER JOIN libros l ON l.id = pr.libro_id
             INNER JOIN usuarios u ON u.id = pr.usuario_id
             WHERE pr.id = :id'
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function registrar(int $usuarioId, int $libroId, ?int $usuarioRegistroId, ?string $observaciones): int
    {
        $fechaDevolucionEstimada = self::calcularFechaDevolucionEstimada();
        $pdo = obtenerConexion();
        $pdo->beginTransaction();
        try {
            // Bloqueamos esa fila del libro para que dos préstamos al mismo tiempo no dejen el stock mal contado.
            $stmt = $pdo->prepare('SELECT stock_disponible FROM libros WHERE id = :id FOR UPDATE');
            $stmt->execute(['id' => $libroId]);
            $libro = $stmt->fetch();

            if (!$libro || (int) $libro['stock_disponible'] < 1) {
                throw new RuntimeException('No hay ejemplares disponibles de este libro.');
            }

            $update = $pdo->prepare('UPDATE libros SET stock_disponible = stock_disponible - 1 WHERE id = :id');
            $update->execute(['id' => $libroId]);

            $insert = $pdo->prepare(
                'INSERT INTO prestamos (usuario_id, libro_id, usuario_registro_id, fecha_devolucion_estimada, observaciones, estado)
                 VALUES (:usuario_id, :libro_id, :usuario_registro_id, :fecha_dev, :observaciones, "prestado")'
            );
            $insert->execute([
                'usuario_id'          => $usuarioId,
                'libro_id'            => $libroId,
                'usuario_registro_id' => $usuarioRegistroId,
                'fecha_dev'           => $fechaDevolucionEstimada,
                'observaciones'       => $observaciones,
            ]);
            $prestamoId = (int) $pdo->lastInsertId();

            $pdo->commit();
            return $prestamoId;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function solicitar(int $usuarioId, int $libroId, ?string $observaciones): int
    {
        $fechaDevolucionEstimada = self::calcularFechaDevolucionEstimada();
        $pdo = obtenerConexion();
        $stmt = $pdo->prepare(
            'INSERT INTO prestamos (usuario_id, libro_id, usuario_registro_id, fecha_devolucion_estimada, observaciones, estado)
             VALUES (:usuario_id, :libro_id, NULL, :fecha_dev, :observaciones, "pendiente")'
        );
        $stmt->execute([
            'usuario_id'    => $usuarioId,
            'libro_id'      => $libroId,
            'fecha_dev'     => $fechaDevolucionEstimada,
            'observaciones' => $observaciones,
        ]);
        return (int) $pdo->lastInsertId();
    }

    public static function aprobar(int $prestamoId, int $usuarioRegistroId): void
    {
        $pdo = obtenerConexion();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('SELECT * FROM prestamos WHERE id = :id FOR UPDATE');
            $stmt->execute(['id' => $prestamoId]);
            $prestamo = $stmt->fetch();

            if (!$prestamo || $prestamo['estado'] !== 'pendiente') {
                throw new RuntimeException('La solicitud no existe o ya fue resuelta.');
            }

            $stmtLibro = $pdo->prepare('SELECT stock_disponible FROM libros WHERE id = :id FOR UPDATE');
            $stmtLibro->execute(['id' => $prestamo['libro_id']]);
            $libro = $stmtLibro->fetch();

            if (!$libro || (int) $libro['stock_disponible'] < 1) {
                throw new RuntimeException('No hay ejemplares disponibles de este libro para aprobar la solicitud.');
            }

            $pdo->prepare('UPDATE libros SET stock_disponible = stock_disponible - 1 WHERE id = :id')
                ->execute(['id' => $prestamo['libro_id']]);

            $pdo->prepare('UPDATE prestamos SET estado = "prestado", usuario_registro_id = :registro, fecha_devolucion_estimada = :fecha_dev WHERE id = :id')
                ->execute([
                    'registro'  => $usuarioRegistroId,
                    'fecha_dev' => self::calcularFechaDevolucionEstimada(),
                    'id'        => $prestamoId,
                ]);

            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function rechazar(int $prestamoId, int $usuarioRegistroId): void
    {
        $pdo = obtenerConexion();
        $stmt = $pdo->prepare(
            'UPDATE prestamos SET estado = "rechazado", usuario_registro_id = :registro
             WHERE id = :id AND estado = "pendiente"'
        );
        $stmt->execute(['registro' => $usuarioRegistroId, 'id' => $prestamoId]);

        if ($stmt->rowCount() === 0) {
            throw new RuntimeException('La solicitud no existe o ya fue resuelta.');
        }
    }

    public static function cancelar(int $prestamoId): void
    {
        $pdo = obtenerConexion();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('SELECT * FROM prestamos WHERE id = :id FOR UPDATE');
            $stmt->execute(['id' => $prestamoId]);
            $prestamo = $stmt->fetch();

            if (!$prestamo || !in_array($prestamo['estado'], ['prestado', 'rechazado'], true)) {
                throw new RuntimeException('Este préstamo no se puede cancelar (ya está pendiente, fue devuelto, o venció).');
            }

            if ($prestamo['estado'] === 'prestado') {
                $pdo->prepare('UPDATE libros SET stock_disponible = stock_disponible + 1 WHERE id = :id')
                    ->execute(['id' => $prestamo['libro_id']]);
            }

            $pdo->prepare('UPDATE prestamos SET estado = "pendiente", usuario_registro_id = NULL WHERE id = :id')
                ->execute(['id' => $prestamoId]);

            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function registrarDevolucion(int $prestamoId): void
    {
        $pdo = obtenerConexion();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('SELECT * FROM prestamos WHERE id = :id FOR UPDATE');
            $stmt->execute(['id' => $prestamoId]);
            $prestamo = $stmt->fetch();

            if (!$prestamo || !in_array($prestamo['estado'], ['prestado', 'vencido'], true)) {
                throw new RuntimeException('El préstamo no existe, no fue aprobado todavía, o ya fue devuelto.');
            }

            $update = $pdo->prepare('UPDATE prestamos SET estado = "devuelto", fecha_devolucion_real = NOW() WHERE id = :id');
            $update->execute(['id' => $prestamoId]);

            $stock = $pdo->prepare('UPDATE libros SET stock_disponible = stock_disponible + 1 WHERE id = :id');
            $stock->execute(['id' => $prestamo['libro_id']]);

            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function actualizarVencidos(): void
    {
        $pdo = obtenerConexion();
        $stmt = $pdo->prepare(
            'UPDATE prestamos SET estado = "vencido" WHERE estado = "prestado" AND fecha_devolucion_estimada < CURDATE()'
        );
        $stmt->execute();
    }
}
