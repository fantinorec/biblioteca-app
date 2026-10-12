(function () {
    const CLAVE = 'biblioteca_usuario';

    window.guardarSesionLocal = function (usuario) {
        try {
            localStorage.setItem(CLAVE, JSON.stringify(usuario));
        } catch (e) {  }
    };

    window.limpiarSesionLocal = function () {
        try { localStorage.removeItem(CLAVE); } catch (e) {  }
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
        } catch (e) {  }
    });
})();
