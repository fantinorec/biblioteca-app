/**
 * Cache liviano de la sesion en localStorage: SOLO se usa para mostrar
 * comodamente el nombre del usuario arriba a la derecha sin pedirlo de
 * nuevo al servidor en cada pantalla. La seguridad real (que modulos
 * puede ver, si esta logueado, etc.) siempre se valida en el servidor
 * contra la sesion de PHP; el localStorage nunca se usa para eso.
 */
(function () {
    const CLAVE = 'biblioteca_usuario';

    window.guardarSesionLocal = function (usuario) {
        try {
            localStorage.setItem(CLAVE, JSON.stringify(usuario));
        } catch (e) { /* almacenamiento no disponible, seguimos sin cache */ }
    };

    window.limpiarSesionLocal = function () {
        try { localStorage.removeItem(CLAVE); } catch (e) { /* noop */ }
    };

    function iniciales(nombre, apellido) {
        return ((nombre || '?')[0] + (apellido || '')[0]).toUpperCase();
    }

    document.addEventListener('DOMContentLoaded', function () {
        const span = document.getElementById('nombreUsuarioActual');
        const avatar = document.getElementById('avatarUsuarioActual');
        if (!span) return;

        try {
            const dato = localStorage.getItem(CLAVE);
            if (dato) {
                const usuario = JSON.parse(dato);
                span.textContent = usuario.nombre + ' ' + usuario.apellido + ' · ' + usuario.perfil;
                if (avatar) avatar.textContent = iniciales(usuario.nombre, usuario.apellido);
            }
        } catch (e) { /* si falla, el nombre ya viene renderizado desde PHP como respaldo */ }
    });
})();
