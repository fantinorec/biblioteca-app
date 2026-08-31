/**
 * Sistema propio de notificaciones (reemplaza por completo a alert()).
 * Uso: mostrarToast('exito' | 'error' | 'advertencia' | 'info', 'Titulo', 'Mensaje');
 */
(function () {
    function contenedor() {
        let el = document.getElementById('toast-contenedor');
        if (!el) {
            el = document.createElement('div');
            el.id = 'toast-contenedor';
            document.body.appendChild(el);
        }
        return el;
    }

    window.mostrarToast = function (tipo, titulo, mensaje, duracionMs) {
        duracionMs = duracionMs || 4500;
        const toast = document.createElement('div');
        toast.className = 'toast toast-' + tipo;
        toast.innerHTML =
            '<span class="toast__titulo">' + titulo + '</span>' +
            '<span>' + mensaje + '</span>';
        contenedor().appendChild(toast);

        setTimeout(function () {
            toast.classList.add('saliendo');
            setTimeout(function () { toast.remove(); }, 200);
        }, duracionMs);
    };

    // Si la URL trae parametros ?ok=1&msg=... o ?error=1&msg=... los mostramos
    // como toast apenas carga la pagina (patron usado tras los redirects de los ABM).
    document.addEventListener('DOMContentLoaded', function () {
        const params = new URLSearchParams(window.location.search);
        const msg = params.get('msg');
        if (params.get('ok') && msg) {
            mostrarToast('exito', 'Listo', decodeURIComponent(msg));
        } else if (params.get('error') && msg) {
            mostrarToast('error', 'Atencion', decodeURIComponent(msg));
        }
    });
})();
