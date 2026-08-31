<?php
class Libro
{
    public static function listar(?string $estado = null): array
    {
        $pdo = obtenerConexion();
        $sql = 'SELECT l.*, a.nombre AS autor_nombre, a.apellido AS autor_apellido,
                       c.nombre AS categoria_nombre, e.nombre AS editorial_nombre
                FROM libros l
                INNER JOIN autores a ON a.id = l.autor_id
                INNER JOIN categorias c ON c.id = l.categoria_id
                INNER JOIN editoriales e ON e.id = l.editorial_id';
        $params = [];
        if ($estado) {
            $sql .= ' WHERE l.estado = :estado';
            $params['estado'] = $estado;
        }
        $sql .= ' ORDER BY l.id DESC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function buscarPorId(int $id): ?array
    {
        $pdo = obtenerConexion();
        $stmt = $pdo->prepare('SELECT * FROM libros WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function existeIsbn(string $isbn, ?int $excluirId = null): bool
    {
        if ($isbn === '') {
            return false;
        }
        $pdo = obtenerConexion();
        $sql = 'SELECT COUNT(*) FROM libros WHERE isbn = :isbn';
        $params = ['isbn' => $isbn];
        if ($excluirId) {
            $sql .= ' AND id <> :id';
            $params['id'] = $excluirId;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    public static function crear(array $datos): int
    {
        $pdo = obtenerConexion();
        $stmt = $pdo->prepare(
            'INSERT INTO libros (titulo, isbn, autor_id, categoria_id, editorial_id, anio_publicacion, stock_total, stock_disponible)
             VALUES (:titulo, :isbn, :autor_id, :categoria_id, :editorial_id, :anio, :stock_total, :stock_total)'
        );
        $stmt->execute([
            'titulo'       => $datos['titulo'],
            'isbn'         => $datos['isbn'] ?: null,
            'autor_id'     => $datos['autor_id'],
            'categoria_id' => $datos['categoria_id'],
            'editorial_id' => $datos['editorial_id'],
            'anio'         => $datos['anio_publicacion'] ?: null,
            'stock_total'  => $datos['stock_total'],
        ]);
        return (int) $pdo->lastInsertId();
    }

    public static function actualizar(int $id, array $datos): void
    {
        $pdo = obtenerConexion();
        // Ajustamos stock_disponible proporcional a la diferencia de stock_total para no perder prestamos activos.
        $actual = self::buscarPorId($id);
        $diferencia = (int) $datos['stock_total'] - (int) $actual['stock_total'];
        $nuevoDisponible = max(0, (int) $actual['stock_disponible'] + $diferencia);

        $stmt = $pdo->prepare(
            'UPDATE libros SET titulo = :titulo, isbn = :isbn, autor_id = :autor_id, categoria_id = :categoria_id,
                    editorial_id = :editorial_id, anio_publicacion = :anio, stock_total = :stock_total, stock_disponible = :stock_disponible
             WHERE id = :id'
        );
        $stmt->execute([
            'titulo'           => $datos['titulo'],
            'isbn'             => $datos['isbn'] ?: null,
            'autor_id'         => $datos['autor_id'],
            'categoria_id'     => $datos['categoria_id'],
            'editorial_id'     => $datos['editorial_id'],
            'anio'             => $datos['anio_publicacion'] ?: null,
            'stock_total'      => $datos['stock_total'],
            'stock_disponible' => $nuevoDisponible,
            'id'               => $id,
        ]);
    }

    public static function darBaja(int $id): void
    {
        $pdo = obtenerConexion();
        $stmt = $pdo->prepare('UPDATE libros SET estado = "baja" WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public static function reactivar(int $id): void
    {
        $pdo = obtenerConexion();
        $stmt = $pdo->prepare('UPDATE libros SET estado = "activo" WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
