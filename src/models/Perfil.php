<?php
class Perfil
{
    public static function listar(?string $estado = null): array
    {
        $pdo = obtenerConexion();
        $sql = 'SELECT * FROM perfiles';
        $params = [];
        if ($estado) {
            $sql .= ' WHERE estado = :estado';
            $params['estado'] = $estado;
        }
        $sql .= ' ORDER BY id ASC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function buscarPorId(int $id): ?array
    {
        $pdo = obtenerConexion();
        $stmt = $pdo->prepare('SELECT * FROM perfiles WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function existeNombre(string $nombre, ?int $excluirId = null): bool
    {
        $pdo = obtenerConexion();
        $sql = 'SELECT COUNT(*) FROM perfiles WHERE nombre = :nombre';
        $params = ['nombre' => $nombre];
        if ($excluirId) {
            $sql .= ' AND id <> :id';
            $params['id'] = $excluirId;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    public static function crear(string $nombre, ?string $descripcion): int
    {
        $pdo = obtenerConexion();
        $stmt = $pdo->prepare('INSERT INTO perfiles (nombre, descripcion, es_administrador, estado) VALUES (:nombre, :descripcion, 0, "activo")');
        $stmt->execute(['nombre' => $nombre, 'descripcion' => $descripcion]);
        return (int) $pdo->lastInsertId();
    }

    public static function actualizar(int $id, string $nombre, ?string $descripcion): void
    {
        $pdo = obtenerConexion();
        $stmt = $pdo->prepare('UPDATE perfiles SET nombre = :nombre, descripcion = :descripcion WHERE id = :id AND es_administrador = 0');
        $stmt->execute(['nombre' => $nombre, 'descripcion' => $descripcion, 'id' => $id]);
    }

    public static function darBaja(int $id): void
    {
        $pdo = obtenerConexion();
        // Nunca se permite dar de baja el perfil marcado como administrador.
        $stmt = $pdo->prepare('UPDATE perfiles SET estado = "baja" WHERE id = :id AND es_administrador = 0');
        $stmt->execute(['id' => $id]);
    }

    public static function reactivar(int $id): void
    {
        $pdo = obtenerConexion();
        $stmt = $pdo->prepare('UPDATE perfiles SET estado = "activo" WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public static function cantidadUsuariosConPerfil(int $id): int
    {
        $pdo = obtenerConexion();
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM usuarios WHERE perfil_id = :id AND estado = "activo"');
        $stmt->execute(['id' => $id]);
        return (int) $stmt->fetchColumn();
    }

    /** Permisos de un perfil sobre todos los modulos (incluyendo los que NO tiene asignados, en 0). */
    public static function permisosCompletos(int $perfilId): array
    {
        $pdo = obtenerConexion();
        $stmt = $pdo->prepare(
            'SELECT m.id AS modulo_id, m.nombre, m.clave,
                    COALESCE(pm.puede_ver, 0) AS puede_ver,
                    COALESCE(pm.puede_crear, 0) AS puede_crear,
                    COALESCE(pm.puede_editar, 0) AS puede_editar,
                    COALESCE(pm.puede_eliminar, 0) AS puede_eliminar
             FROM modulos m
             LEFT JOIN perfil_modulo pm ON pm.modulo_id = m.id AND pm.perfil_id = :perfil_id
             WHERE m.estado = "activo"
             ORDER BY m.orden ASC'
        );
        $stmt->execute(['perfil_id' => $perfilId]);
        return $stmt->fetchAll();
    }

    /** Guarda de una sola vez la matriz de permisos de un perfil (reemplaza lo anterior). */
    public static function guardarPermisos(int $perfilId, array $permisosPorModulo): void
    {
        $pdo = obtenerConexion();
        $pdo->beginTransaction();
        try {
            $del = $pdo->prepare('DELETE FROM perfil_modulo WHERE perfil_id = :perfil_id');
            $del->execute(['perfil_id' => $perfilId]);

            $ins = $pdo->prepare(
                'INSERT INTO perfil_modulo (perfil_id, modulo_id, puede_ver, puede_crear, puede_editar, puede_eliminar)
                 VALUES (:perfil_id, :modulo_id, :ver, :crear, :editar, :eliminar)'
            );
            foreach ($permisosPorModulo as $moduloId => $permiso) {
                $ver = !empty($permiso['ver']);
                if (!$ver) {
                    continue; // si no puede ver, no guardamos fila (equivale a sin permiso)
                }
                $ins->execute([
                    'perfil_id' => $perfilId,
                    'modulo_id' => (int) $moduloId,
                    'ver'       => 1,
                    'crear'     => !empty($permiso['crear']) ? 1 : 0,
                    'editar'    => !empty($permiso['editar']) ? 1 : 0,
                    'eliminar'  => !empty($permiso['eliminar']) ? 1 : 0,
                ]);
            }
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
