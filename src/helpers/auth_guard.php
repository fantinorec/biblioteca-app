<?php
/**
 * Manejo de sesiones y control de acceso por perfil/modulo.
 *
 * Importante: toda decision de seguridad se toma SIEMPRE contra la
 * sesion de PHP (server-side). El localStorage del navegador solo se usa
 * para mostrar comodamente el nombre del usuario en pantalla; nunca se
 * usa para autorizar nada.
 */

function iniciar_sesion_segura(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_name('BIBLIOTECA_SESSION');
    session_start();
}

function usuario_autenticado(): bool
{
    return !empty($_SESSION['usuario_id']);
}

/** Corta la ejecucion y redirige al login si no hay sesion activa. */
function requerir_sesion(): void
{
    if (!usuario_autenticado()) {
        redirigir('login.php?motivo=sin_sesion');
    }
}

/**
 * Carga (y cachea en sesion) el mapa de permisos del perfil actual:
 * ['libros' => ['ver'=>1,'crear'=>1,'editar'=>0,'eliminar'=>0], ...]
 */
function permisos_usuario_actual(): array
{
    if (isset($_SESSION['permisos']) && is_array($_SESSION['permisos'])) {
        return $_SESSION['permisos'];
    }
    return [];
}

/** Verifica si el usuario logueado tiene un permiso puntual sobre un modulo. */
function tiene_permiso(string $claveModulo, string $permiso = 'ver'): bool
{
    $permisos = permisos_usuario_actual();
    return !empty($permisos[$claveModulo]["puede_{$permiso}"]);
}

/**
 * Guard para usar al principio de cada pantalla protegida:
 * exige sesion activa Y permiso de "ver" sobre el modulo indicado.
 */
function requerir_modulo(string $claveModulo): void
{
    requerir_sesion();
    if (!tiene_permiso($claveModulo, 'ver')) {
        redirigir('acceso_denegado.php');
    }
}

/** Reconstruye en sesion los datos de permisos de un usuario (tras login o cambios de permisos). */
function refrescar_permisos_en_sesion(int $perfilId): void
{
    $pdo = obtenerConexion();
    $stmt = $pdo->prepare(
        'SELECT m.clave, pm.puede_ver, pm.puede_crear, pm.puede_editar, pm.puede_eliminar
         FROM perfil_modulo pm
         INNER JOIN modulos m ON m.id = pm.modulo_id AND m.estado = "activo"
         WHERE pm.perfil_id = :perfil_id AND pm.puede_ver = 1'
    );
    $stmt->execute(['perfil_id' => $perfilId]);

    $permisos = [];
    foreach ($stmt->fetchAll() as $fila) {
        $permisos[$fila['clave']] = [
            'puede_ver'      => (int) $fila['puede_ver'],
            'puede_crear'    => (int) $fila['puede_crear'],
            'puede_editar'   => (int) $fila['puede_editar'],
            'puede_eliminar' => (int) $fila['puede_eliminar'],
        ];
    }
    $_SESSION['permisos'] = $permisos;
}

/** Devuelve el listado de modulos visibles (para armar el menu lateral) respetando el orden. */
function modulos_visibles_usuario(): array
{
    $pdo = obtenerConexion();
    $perfilId = $_SESSION['perfil_id'] ?? 0;
    $stmt = $pdo->prepare(
        'SELECT m.* FROM modulos m
         INNER JOIN perfil_modulo pm ON pm.modulo_id = m.id
         WHERE pm.perfil_id = :perfil_id AND pm.puede_ver = 1 AND m.estado = "activo"
         ORDER BY m.orden ASC'
    );
    $stmt->execute(['perfil_id' => $perfilId]);
    return $stmt->fetchAll();
}
