<?php
class Modulo
{
    public static function listar(?string $estado = 'activo'): array
    {
        $pdo = obtenerConexion();
        $sql = 'SELECT * FROM modulos';
        $params = [];
        if ($estado) {
            $sql .= ' WHERE estado = :estado';
            $params['estado'] = $estado;
        }
        $sql .= ' ORDER BY orden ASC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
