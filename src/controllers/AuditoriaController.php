<?php

class AuditoriaController
{
    public static function manejar(): void
    {
        requerir_modulo('auditoria');

        $puedeVerTodos = tiene_permiso('auditoria', 'editar');

        $pdo = obtenerConexion();

        $filtroAccion = trim($_GET['accion'] ?? '');
        $filtroUsuario = $puedeVerTodos ? trim($_GET['usuario'] ?? '') : '';

        $sql = "SELECT a.* FROM auditoria a
                LEFT JOIN prestamos p ON a.tabla_afectada = 'prestamos' AND a.registro_id = p.id
                LEFT JOIN usuarios u ON u.id = p.usuario_id
                WHERE a.accion <> 'LOGIN_FALLIDO'";
        $params = [];

        if (!$puedeVerTodos) {
            $sql .= ' AND a.usuario_id = :usuario_id';
            $params['usuario_id'] = (int) $_SESSION['usuario_id'];
        }

        if ($filtroAccion === '__prestamos__') {
            $sql .= ' AND a.tabla_afectada = :tabla';
            $params['tabla'] = 'prestamos';
        } elseif ($filtroAccion !== '') {
            $sql .= ' AND a.accion = :accion';
            $params['accion'] = $filtroAccion;
        }

        if ($filtroUsuario !== '') {
            $sql .= ' AND (a.usuario_email LIKE :usuario_email OR u.email LIKE :usuario_email_prestamo)';
            $params['usuario_email'] = '%' . $filtroUsuario . '%';
            $params['usuario_email_prestamo'] = '%' . $filtroUsuario . '%';
        }

        $sql .= ' ORDER BY a.fecha DESC LIMIT 300';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $registros = $stmt->fetchAll();

        $acciones = $pdo->query(
            "SELECT DISTINCT accion FROM auditoria WHERE accion <> 'LOGIN_FALLIDO' ORDER BY accion"
        )->fetchAll(PDO::FETCH_COLUMN);

        renderizar_vista('auditoria/index', [
            'tituloPagina'      => $puedeVerTodos ? 'Auditoría' : 'Mis movimientos',
            'claveModuloActual' => 'auditoria',
            'puedeVerTodos'     => $puedeVerTodos,
            'filtroAccion'      => $filtroAccion,
            'filtroUsuario'     => $filtroUsuario,
            'registros'         => $registros,
            'acciones'          => $acciones,
        ]);
    }
}
