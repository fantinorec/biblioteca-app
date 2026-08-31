/**
 * Modal de confirmacion propio (reemplaza a confirm() nativo) y
 * helper generico para abrir/cerrar los modales de formulario (ABM).
 */
(function () {
    function asegurarModalConfirmacion() {
        let fondo = document.getElementById('modal-confirmacion');
        if (fondo) return fondo;

        fondo = document.createElement('div');
        fondo.id = 'modal-confirmacion';
        fondo.className = 'modal-fondo';
        fondo.innerHTML =
            '<div class="modal-caja">' +
            '  <h3 id="modal-confirmacion-titulo">Confirmar accion</h3>' +
            '  <p id="modal-confirmacion-mensaje" class="texto-suave"></p>' +
            '  <div class="modal-acciones">' +
            '    <button type="button" class="btn btn-secundario" id="modal-confirmacion-cancelar">Cancelar</button>' +
            '    <button type="button" class="btn btn-peligro" id="modal-confirmacion-aceptar">Confirmar</button>' +
            '  </div>' +
            '</div>';
        document.body.appendChild(fondo);
        return fondo;
    }

    /**
     * confirmarAccion('Dar de baja', '¿Seguro que queres dar de baja este registro?', function () { ... });
     */
    window.confirmarAccion = function (titulo, mensaje, alConfirmar) {
        const fondo = asegurarModalConfirmacion();
        fondo.querySelector('#modal-confirmacion-titulo').textContent = titulo;
        fondo.querySelector('#modal-confirmacion-mensaje').textContent = mensaje;
        fondo.classList.add('visible');

        const btnAceptar = fondo.querySelector('#modal-confirmacion-aceptar');
        const btnCancelar = fondo.querySelector('#modal-confirmacion-cancelar');

        function cerrar() {
            fondo.classList.remove('visible');
            btnAceptar.removeEventListener('click', onAceptar);
            btnCancelar.removeEventListener('click', cerrar);
        }
        function onAceptar() {
            cerrar();
            alConfirmar();
        }
        btnAceptar.addEventListener('click', onAceptar);
        btnCancelar.addEventListener('click', cerrar);
    };

    /** Abre/cierra los modales de formulario marcados con data-modal="id". */
    window.abrirModal = function (id) {
        const el = document.getElementById(id);
        if (el) el.classList.add('visible');
    };
    window.cerrarModal = function (id) {
        const el = document.getElementById(id);
        if (el) el.classList.remove('visible');
    };

    document.addEventListener('click', function (evento) {
        const disparador = evento.target.closest('[data-abrir-modal]');
        if (disparador) {
            window.abrirModal(disparador.getAttribute('data-abrir-modal'));
        }
        const cierre = evento.target.closest('[data-cerrar-modal]');
        if (cierre) {
            window.cerrarModal(cierre.getAttribute('data-cerrar-modal'));
        }
    });
})();
