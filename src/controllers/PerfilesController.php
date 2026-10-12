<?php
require_once __DIR__ . '/../models/Perfil.php';

class PerfilesController
{
    public static function manejar(): void
    {
        requerir_modulo('perfiles');

        $puedeCrear = tiene_permiso('perfiles', 'crear');
        $puedeEditar = tiene_permiso('perfiles', 'editar');
        $puedeEliminar = tiene_permiso('perfiles', 'eliminar');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            self::procesarAccion($puedeCrear, $puedeEditar, $puedeEliminar);
        }

        renderizar_vista('perfiles/index', [
            'tituloPagina'      => 'Perfiles',
            'claveModuloActual' => 'perfiles',
            'perfiles'          => Perfil::listar(),
            'puedeCrear'        => $puedeCrear,
            'puedeEditar'       => $puedeEditar,
            'puedeEliminar'     => $puedeEliminar,
        ]);
    }

    private static function volver(bool $ok, string $mensaje): never
    {
        redirigir('perfiles.php?' . ($ok ? 'ok' : 'error') . '=1&msg=' . urlencode($mensaje));
    }

    private static function procesarAccion(bool $puedeCrear, bool $puedeEditar, bool $puedeEliminar): void
    {
        $accion = $_POST['accion'] ?? '';
        $nombre = trim($_POST['nombre'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');

        if ($accion === 'crear' && $puedeCrear) {
            if ($nombre === '') self::volver(false, 'El nombre del perfil es obligatorio.');
            if (Perfil::existeNombre($nombre)) self::volver(false, 'Ya existe un perfil con ese nombre.');
            $id = Perfil::crear($nombre, $descripcion);
            registrar_auditoria($_SESSION['usuario_id'], $_SESSION['email'], 'CREAR', 'perfiles', $id, "Alta de perfil \"$nombre\"");
            self::volver(true, 'Perfil creado. Ahora asignale módulos desde "Permisos".');
        }

        if ($accion === 'editar' && $puedeEditar) {
            $id = (int) ($_POST['id'] ?? 0);
            $perfil = Perfil::buscarPorId($id);
            if ($perfil && (int) $perfil['es_administrador'] === 1) {
                self::volver(false, 'El perfil Administrador no puede modificarse.');
            }
            if ($nombre === '') self::volver(false, 'El nombre del perfil es obligatorio.');
            if (Perfil::existeNombre($nombre, $id)) self::volver(false, 'Ya existe un perfil con ese nombre.');
            Perfil::actualizar($id, $nombre, $descripcion);
            registrar_auditoria($_SESSION['usuario_id'], $_SESSION['email'], 'EDITAR', 'perfiles', $id, "Edición de perfil #$id");
            self::volver(true, 'Perfil actualizado.');
        }

        if ($accion === 'baja' && $puedeEliminar) {
            $id = (int) ($_POST['id'] ?? 0);
            $perfil = Perfil::buscarPorId($id);
            if ($perfil && (int) $perfil['es_administrador'] === 1) {
                self::volver(false, 'El perfil Administrador no puede darse de baja.');
            }
            if (Perfil::cantidadUsuariosConPerfil($id) > 0) {
                self::volver(false, 'No se puede dar de baja: hay usuarios activos con este perfil.');
            }
            Perfil::darBaja($id);
            registrar_auditoria($_SESSION['usuario_id'], $_SESSION['email'], 'BAJA', 'perfiles', $id, "Baja lógica de perfil #$id");
            self::volver(true, 'Perfil dado de baja.');
        }

        if ($accion === 'reactivar' && $puedeEliminar) {
            $id = (int) ($_POST['id'] ?? 0);
            Perfil::reactivar($id);
            registrar_auditoria($_SESSION['usuario_id'], $_SESSION['email'], 'EDITAR', 'perfiles', $id, "Reactivación de perfil #$id");
            self::volver(true, 'Perfil reactivado.');
        }

        self::volver(false, 'Acción no permitida.');
    }
}
