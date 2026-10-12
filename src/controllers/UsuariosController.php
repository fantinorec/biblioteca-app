<?php
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../models/Perfil.php';

class UsuariosController
{
    public static function manejar(): void
    {
        requerir_modulo('usuarios');

        $puedeCrear = tiene_permiso('usuarios', 'crear');
        $puedeEditar = tiene_permiso('usuarios', 'editar');
        $puedeEliminar = tiene_permiso('usuarios', 'eliminar');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            self::procesarAccion($puedeCrear, $puedeEditar, $puedeEliminar);
        }

        renderizar_vista('usuarios/index', [
            'tituloPagina'      => 'Usuarios',
            'claveModuloActual' => 'usuarios',
            'usuarios'          => Usuario::listar(),
            'perfiles'          => Perfil::listar('activo'),
            'puedeCrear'        => $puedeCrear,
            'puedeEditar'       => $puedeEditar,
            'puedeEliminar'     => $puedeEliminar,
        ]);
    }

    private static function volver(bool $ok, string $mensaje): never
    {
        redirigir('usuarios.php?' . ($ok ? 'ok' : 'error') . '=1&msg=' . urlencode($mensaje));
    }

    private static function procesarAccion(bool $puedeCrear, bool $puedeEditar, bool $puedeEliminar): void
    {
        $accion = $_POST['accion'] ?? '';

        if ($accion === 'crear' && $puedeCrear) {
            self::crear();
        }

        if ($accion === 'editar' && $puedeEditar) {
            self::editar();
        }

        if ($accion === 'baja' && $puedeEliminar) {
            $id = (int) ($_POST['id'] ?? 0);
            if (Usuario::esUnicoAdministrador($id)) {
                self::volver(false, 'No se puede dar de baja al único administrador existente.');
            }
            if ($id === (int) $_SESSION['usuario_id']) {
                self::volver(false, 'No podés darte de baja a vos mismo.');
            }
            Usuario::darBaja($id);
            registrar_auditoria($_SESSION['usuario_id'], $_SESSION['email'], 'BAJA', 'usuarios', $id, "Baja lógica de usuario #$id");
            self::volver(true, 'Usuario dado de baja.');
        }

        if ($accion === 'reactivar' && $puedeEliminar) {
            $id = (int) ($_POST['id'] ?? 0);
            Usuario::reactivar($id);
            registrar_auditoria($_SESSION['usuario_id'], $_SESSION['email'], 'EDITAR', 'usuarios', $id, "Reactivación de usuario #$id");
            self::volver(true, 'Usuario reactivado.');
        }

        self::volver(false, 'Acción no permitida.');
    }

    private static function crear(): void
    {
        $nombre = trim($_POST['nombre'] ?? '');
        $apellido = trim($_POST['apellido'] ?? '');
        $email = trim(strtolower($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';
        $perfilId = (int) ($_POST['perfil_id'] ?? 0);

        // Si la contraseña no cumple las reglas, no se guarda nada del formulario: o se guarda todo, o no se guarda nada.
        $errores = validar_campos_requeridos($_POST, ['nombre' => 'Nombre', 'apellido' => 'Apellido', 'email' => 'Email', 'password' => 'Contraseña', 'perfil_id' => 'Perfil']);
        if (empty($errores['email']) && !validar_email($email)) $errores['email'] = 'Formato invalido';
        if (empty($errores['email']) && Usuario::existeEmail($email)) $errores['email'] = 'Ya existe';
        if (empty($errores['password']) && !validar_password($password)) $errores['password'] = mensaje_password_especifico($password);

        if (!empty($errores)) {
            self::volver(false, 'Revisá los datos: ' . implode(' ', $errores));
        }

        $id = Usuario::crear([
            'nombre' => $nombre, 'apellido' => $apellido, 'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
            'perfil_id' => $perfilId, 'email_verificado' => 1, 'estado' => 'activo',
        ]);
        registrar_auditoria($_SESSION['usuario_id'], $_SESSION['email'], 'CREAR', 'usuarios', $id, "Alta de usuario $email desde ABM");
        self::volver(true, 'Usuario creado correctamente.');
    }

    private static function editar(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');
        $apellido = trim($_POST['apellido'] ?? '');
        $email = trim(strtolower($_POST['email'] ?? ''));
        $perfilId = (int) ($_POST['perfil_id'] ?? 0);

        $errores = validar_campos_requeridos($_POST, ['nombre' => 'Nombre', 'apellido' => 'Apellido', 'email' => 'Email', 'perfil_id' => 'Perfil']);
        if (empty($errores['email']) && !validar_email($email)) $errores['email'] = 'Formato invalido';
        if (empty($errores['email']) && Usuario::existeEmail($email, $id)) $errores['email'] = 'Ya existe';

        if (!empty($_POST['password']) && !validar_password($_POST['password'])) {
            $errores['password'] = mensaje_password_especifico($_POST['password']);
        }
        if (!empty($errores)) {
            self::volver(false, 'Revisá los datos: ' . implode(' ', $errores));
        }

        $actual = Usuario::buscarPorId($id);
        if ($actual && Usuario::esUnicoAdministrador($id) && $perfilId !== (int) $actual['perfil_id']) {
            self::volver(false, 'No se puede modificar el perfil del único administrador existente.');
        }

        Usuario::actualizarDatos($id, ['nombre' => $nombre, 'apellido' => $apellido, 'email' => $email, 'perfil_id' => $perfilId]);
        registrar_auditoria($_SESSION['usuario_id'], $_SESSION['email'], 'EDITAR', 'usuarios', $id, "Edición de usuario #$id");

        if (!empty($_POST['password'])) {
            Usuario::actualizarPassword($id, password_hash($_POST['password'], PASSWORD_BCRYPT));
            registrar_auditoria($_SESSION['usuario_id'], $_SESSION['email'], 'EDITAR', 'usuarios', $id, "Reseteo manual de contraseña de usuario #$id");
        }
        self::volver(true, 'Usuario actualizado correctamente.');
    }
}
