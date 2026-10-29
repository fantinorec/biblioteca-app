<?php

/** Pantalla de ayuda: guía visual de uso del sistema, con capturas explicadas con flechas. */
class GuiaController
{
    public static function manejar(): void
    {
        // Cualquier usuario logueado puede ver la guía (lector o bibliotecario/administrador):
        // no es un modulo del menu con permisos propios, solo hace falta sesion activa.
        requerir_sesion();

        // Misma bandera que usa PrestamosController: separa la vista de
        // "gestiona todo" (bibliotecario/administrador) de la de un Lector,
        // que tiene un menu y unas pantallas mucho mas chicas.
        $puedeGestionarTodos = tiene_permiso('prestamos', 'editar');

        renderizar_vista('guia/index', [
            'tituloPagina'        => 'Guía de uso',
            'puedeGestionarTodos' => $puedeGestionarTodos,
        ]);
    }
}
