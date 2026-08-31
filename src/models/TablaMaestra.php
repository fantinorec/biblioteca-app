<?php
/**
 * Acceso a datos generico para tablas maestras simples de un solo nivel
 * (autores, categorias, editoriales). Evita repetir el mismo CRUD tres
 * veces: la definicion de columnas por tabla vive en public/maestro.php.
 *
 * El nombre de tabla SIEMPRE viene de una lista blanca fija (nunca del
 * usuario), por lo que interpolarlo en el SQL es seguro aca.
 */
class TablaMaestra
{
    private const TABLAS_PERMITIDAS = ['autores', 'categorias', 'editoriales'];

    private static function validarTabla(string $tabla): void
    {
        if (!in_array($tabla, self::TABLAS_PERMITIDAS, true)) {
            throw new InvalidArgumentException("Tabla no permitida: $tabla");
        }
    }

    public static function listar(string $tabla, ?string $estado = null): array
    {
        self::validarTabla($tabla);
        $pdo = obtenerConexion();
        $sql = "SELECT * FROM {$tabla}";
        $params = [];
        if ($estado) {
            $sql .= ' WHERE estado = :estado';
            $params['estado'] = $estado;
        }
        $sql .= ' ORDER BY id DESC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function buscarPorId(string $tabla, int $id): ?array
    {
        self::validarTabla($tabla);
        $pdo = obtenerConexion();
        $stmt = $pdo->prepare("SELECT * FROM {$tabla} WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function existeCampoUnico(string $tabla, string $campo, string $valor, ?int $excluirId = null): bool
    {
        self::validarTabla($tabla);
        $pdo = obtenerConexion();
        $sql = "SELECT COUNT(*) FROM {$tabla} WHERE {$campo} = :valor";
        $params = ['valor' => $valor];
        if ($excluirId) {
            $sql .= ' AND id <> :id';
            $params['id'] = $excluirId;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    public static function crear(string $tabla, array $datos): int
    {
        self::validarTabla($tabla);
        $pdo = obtenerConexion();
        $columnas = array_keys($datos);
        $marcadores = array_map(fn($c) => ":$c", $columnas);
        $sql = "INSERT INTO {$tabla} (" . implode(', ', $columnas) . ') VALUES (' . implode(', ', $marcadores) . ')';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($datos);
        return (int) $pdo->lastInsertId();
    }

    public static function actualizar(string $tabla, int $id, array $datos): void
    {
        self::validarTabla($tabla);
        $pdo = obtenerConexion();
        $set = implode(', ', array_map(fn($c) => "$c = :$c", array_keys($datos)));
        $stmt = $pdo->prepare("UPDATE {$tabla} SET {$set} WHERE id = :id");
        $stmt->execute([...$datos, 'id' => $id]);
    }

    public static function darBaja(string $tabla, int $id): void
    {
        self::validarTabla($tabla);
        $pdo = obtenerConexion();
        $stmt = $pdo->prepare("UPDATE {$tabla} SET estado = 'baja' WHERE id = :id");
        $stmt->execute(['id' => $id]);
    }

    public static function reactivar(string $tabla, int $id): void
    {
        self::validarTabla($tabla);
        $pdo = obtenerConexion();
        $stmt = $pdo->prepare("UPDATE {$tabla} SET estado = 'activo' WHERE id = :id");
        $stmt->execute(['id' => $id]);
    }
}
