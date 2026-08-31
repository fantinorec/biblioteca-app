<?php
require_once __DIR__ . '/../src/bootstrap.php';

if (usuario_autenticado()) {
    registrar_auditoria($_SESSION['usuario_id'] ?? null, $_SESSION['email'] ?? null, 'LOGOUT', 'usuarios', $_SESSION['usuario_id'] ?? null, 'Cierre de sesión');
}

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $parametros = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $parametros['path'], $parametros['domain'], $parametros['secure'], $parametros['httponly']);
}
session_destroy();

redirigir('login.php');
