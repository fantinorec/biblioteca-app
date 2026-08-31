<?php
/**
 * Prestamos de libros. Las operaciones que tocan mas de una tabla
 * (prestamos + stock de libros) se hacen dentro de una transaccion PDO
 * con rollback automatico si algo falla, tal como pide la consigna.
 */
class Prestamo
{
    public static function listar(?string $estado = null, ?int $usuarioId = null): array
    {
        $pdo = obtenerConexion();
        $sql = 'SELECT pr.*, l.titulo AS libro_titulo, u.nombre AS usuario_nombre, u.apellido AS usuario_apellido
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

    /**
     * Registra un prestamo: descuenta stock del libro e inserta el registro
     * dentro de la MISMA transaccion. Si el libro no tiene stock disponible
     * o cualquier paso falla, se hace ROLLBACK y no queda nada aplicado.
     *
     * @throws RuntimeException si no hay stock disponible
     */
    public static function registrar(int $usuarioId, int $libroId, string $fechaDevolucionEstimada, ?int $usuarioRegistroId, ?string $observaciones): int
    {
        $pdo = obtenerConexion();
        $pdo->beginTransaction();
        try {
            // Bloqueamos la fila del libro para evitar condiciones de carrera entre prestamos simultaneos.
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

    /** Registra la devolucion de un prestamo, repone stock, todo en una transaccion. */
    public static function registrarDevolucion(int $prestamoId): void
    {
        $pdo = obtenerConexion();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('SELECT * FROM prestamos WHERE id = :id FOR UPDATE');
            $stmt->execute(['id' => $prestamoId]);
            $prestamo = $stmt->fetch();

            if (!$prestamo || $prestamo['estado'] === 'devuelto') {
                throw new RuntimeException('El prestamo no existe o ya fue devuelto.');
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

    /** Marca como "vencido" los prestamos activos cuya fecha estimada ya paso. */
    public static function actualizarVencidos(): void
    {
        $pdo = obtenerConexion();
        $stmt = $pdo->prepare(
            'UPDATE prestamos SET estado = "vencido" WHERE estado = "prestado" AND fecha_devolucion_estimada < CURDATE()'
        );
        $stmt->execute();
    }
}
