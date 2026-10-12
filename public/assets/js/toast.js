// Los errores se muestran como alerta grande en el centro para que se vean bien; el resto son avisos chicos arriba a la derecha.
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

    const ICONO_ALERTA =
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" ' +
        'stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' +
        '<path d="M12 3.2 21 19H3L12 3.2Z"/><path d="M12 9.3v4.4"/>' +
        '<circle cx="12" cy="16.6" r="1" fill="currentColor" stroke="none"/></svg>';

    window.mostrarAlertaError = function (titulo, mensaje) {
        const fondo = document.createElement('div');
        fondo.className = 'alerta-error-fondo';
        fondo.innerHTML =
            '<div class="alerta-error-caja">' +
            '<div class="alerta-error-icono">' + ICONO_ALERTA + '</div>' +
            '<div class="alerta-error-titulo">' + titulo + '</div>' +
            '<div class="alerta-error-mensaje">' + mensaje + '</div>' +
            '<button type="button" class="btn btn-primario">Entendido</button>' +
            '</div>';
        document.body.appendChild(fondo);

        function cerrar() {
            fondo.classList.add('saliendo');
            setTimeout(function () { fondo.remove(); }, 150);
        }
        fondo.querySelector('button').addEventListener('click', cerrar);

        fondo.addEventListener('click', function (evento) {
            if (evento.target === fondo) cerrar();
        });
    };

    window.mostrarToast = function (tipo, titulo, mensaje, duracionMs) {
        if (tipo === 'error') {
            window.mostrarAlertaError(titulo, mensaje);
            return;
        }

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
