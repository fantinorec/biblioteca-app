<?php
require_once __DIR__ . '/../models/Perfil.php';

class PermisosController
{
    public static function manejar(): void
    {
        requerir_modulo('permisos');

        $perfiles = Perfil::listar('activo');
        $perfilId = (int) ($_GET['perfil_id'] ?? ($perfiles[0]['id'] ?? 0));
        $perfilActual = Perfil::buscarPorId($perfilId);

        if (!$perfilActual) {
            redirigir('perfiles.php');
        }

        $esAdministrador = (int) $perfilActual['es_administrador'] === 1;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            self::guardar($perfilId, $esAdministrador);
        }

        renderizar_vista('permisos/index', [
            'tituloPagina'      => 'Permisos',
            'claveModuloActual' => 'permisos',
            'perfiles'          => $perfiles,
            'perfilId'          => $perfilId,
            'esAdministrador'   => $esAdministrador,
            'matrizPermisos'    => Perfil::permisosCompletos($perfilId),
        ]);
    }

    private static function guardar(int $perfilId, bool $esAdministrador): void
    {
        if ($esAdministrador) {
            redirigir('permisos.php?perfil_id=' . $perfilId . '&error=1&msg=' . urlencode('El perfil Administrador siempre tiene acceso total y no puede modificarse.'));
        }

        $permisosPost = $_POST['permiso'] ?? [];
        Perfil::guardarPermisos($perfilId, $permisosPost);
        registrar_auditoria($_SESSION['usuario_id'], $_SESSION['email'], 'EDITAR', 'perfil_modulo', $perfilId, "Actualización de permisos del perfil #$perfilId");

        if ((int) $_SESSION['perfil_id'] === $perfilId) {
            refrescar_permisos_en_sesion($perfilId);
        }
        redirigir('permisos.php?perfil_id=' . $perfilId . '&ok=1&msg=' . urlencode('Permisos actualizados correctamente.'));
    }
}
