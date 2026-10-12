<?php

class AccesoDenegadoController
{
    public static function manejar(): void
    {
        requerir_sesion();
        renderizar_vista('auth/acceso_denegado', []);
    }
}
