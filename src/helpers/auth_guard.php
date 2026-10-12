<?php

// La seguridad se decide siempre del lado del servidor, nunca confiando en el navegador.
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

function requerir_sesion(): void
{
    if (!usuario_autenticado()) {
        redirigir('login.php?motivo=sin_sesion');
    }
}

function permisos_usuario_actual(): array
{
    if (isset($_SESSION['permisos']) && is_array($_SESSION['permisos'])) {
        return $_SESSION['permisos'];
    }
    return [];
}

function tiene_permiso(string $claveModulo, string $permiso = 'ver'): bool
{
    $permisos = permisos_usuario_actual();
    return !empty($permisos[$claveModulo]["puede_{$permiso}"]);
}

function requerir_modulo(string $claveModulo): void
{
    requerir_sesion();
    if (!tiene_permiso($claveModulo, 'ver')) {
        redirigir('acceso_denegado.php');
    }
}

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
