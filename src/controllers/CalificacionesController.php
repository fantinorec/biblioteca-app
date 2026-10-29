<?php
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../models/Calificacion.php';

/**
 * Pantalla de consulta (solo bibliotecario/administrador): buscar un lector
 * y ver su calificación actual por estrellas, y si bajó de 5, el historial
 * con el motivo de cada cambio (para poder explicarle si reclama).
 */
class CalificacionesController
{
    public static function manejar(): void
    {
        requerir_modulo('calificaciones');

        $busqueda = trim($_GET['buscar'] ?? '');
        $usuarios = $busqueda !== '' ? Usuario::buscarPorTexto($busqueda) : [];

        $usuarioSeleccionado = null;
        $historial = [];
        $usuarioId = (int) ($_GET['usuario_id'] ?? 0);
        if ($usuarioId > 0) {
            $usuarioSeleccionado = Usuario::buscarPorId($usuarioId);
            if ($usuarioSeleccionado) {
                $historial = Calificacion::historialDeUsuario($usuarioId);
            }
        }

        renderizar_vista('calificaciones/index', [
            'tituloPagina'        => 'Calificaciones',
            'claveModuloActual'   => 'calificaciones',
            'busqueda'            => $busqueda,
            'usuarios'            => $usuarios,
            'usuarioSeleccionado' => $usuarioSeleccionado,
            'historial'           => $historial,
        ]);
    }
}
