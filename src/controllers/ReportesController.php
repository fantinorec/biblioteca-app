<?php

class ReportesController
{
    public static function manejar(): void
    {
        requerir_modulo('reportes');

        $pdo = obtenerConexion();

        $stmt = $pdo->query(
            "SELECT DATE_FORMAT(fecha_prestamo, '%Y-%m') AS periodo, COUNT(*) AS cantidad
             FROM prestamos
             WHERE fecha_prestamo >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
               AND estado IN ('prestado', 'devuelto', 'vencido')
             GROUP BY periodo ORDER BY periodo"
        );
        $prestamosPorMes = $stmt->fetchAll();

        $stmt = $pdo->query(
            "SELECT c.nombre AS categoria, COUNT(l.id) AS cantidad
             FROM categorias c
             LEFT JOIN libros l ON l.categoria_id = c.id AND l.estado = 'activo'
             WHERE c.estado = 'activo'
             GROUP BY c.id, c.nombre ORDER BY cantidad DESC"
        );
        $librosPorCategoria = $stmt->fetchAll();

        $stmt = $pdo->query(
            "SELECT l.titulo, COUNT(p.id) AS cantidad
             FROM prestamos p INNER JOIN libros l ON l.id = p.libro_id
             WHERE p.estado IN ('prestado', 'devuelto', 'vencido')
             GROUP BY l.id, l.titulo ORDER BY cantidad DESC LIMIT 5"
        );
        $topLibros = $stmt->fetchAll();

        $stmt = $pdo->query(
            "SELECT p.nombre AS perfil, COUNT(u.id) AS cantidad
             FROM perfiles p LEFT JOIN usuarios u ON u.perfil_id = p.id AND u.estado = 'activo'
             WHERE p.estado = 'activo'
             GROUP BY p.id, p.nombre"
        );
        $usuariosPorPerfil = $stmt->fetchAll();

        renderizar_vista('reportes/index', [
            'tituloPagina'        => 'Reportes',
            'claveModuloActual'   => 'reportes',
            'scriptsExtra'        => ['assets/js/reportes.js'],
            'prestamosPorMes'     => $prestamosPorMes,
            'librosPorCategoria'  => $librosPorCategoria,
            'topLibros'           => $topLibros,
            'usuariosPorPerfil'   => $usuariosPorPerfil,
        ]);
    }
}
