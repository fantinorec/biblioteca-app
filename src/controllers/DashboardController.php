<?php
require_once __DIR__ . '/../models/Prestamo.php';
require_once __DIR__ . '/../models/Libro.php';

class DashboardController
{
    public static function manejar(): void
    {
        requerir_modulo('dashboard');

        Prestamo::actualizarVencidos();

        $pdo = obtenerConexion();
        $totalLibros = (int) $pdo->query('SELECT COUNT(*) FROM libros WHERE estado = "activo"')->fetchColumn();
        $totalUsuarios = (int) $pdo->query('SELECT COUNT(*) FROM usuarios WHERE estado = "activo"')->fetchColumn();
        $prestamosActivos = (int) $pdo->query('SELECT COUNT(*) FROM prestamos WHERE estado = "prestado"')->fetchColumn();
        $prestamosVencidos = (int) $pdo->query('SELECT COUNT(*) FROM prestamos WHERE estado = "vencido"')->fetchColumn();

        renderizar_vista('dashboard/index', [
            'tituloPagina'       => 'Inicio',
            'claveModuloActual'  => 'dashboard',
            'totalLibros'        => $totalLibros,
            'totalUsuarios'      => $totalUsuarios,
            'prestamosActivos'   => $prestamosActivos,
            'prestamosVencidos'  => $prestamosVencidos,
            'ultimosLibros'      => Libro::ultimosAgregados(4),
        ]);
    }
}
