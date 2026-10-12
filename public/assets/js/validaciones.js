// Repite en el navegador las mismas reglas de contraseña que ya valida el servidor.
(function () {
    window.validarEmailFormato = function (email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    };

    window.faltantesPassword = function (password) {
        const faltantes = [];
        if (password.length < 8) faltantes.push('al menos 8 caracteres');
        if (!/[A-Z]/.test(password)) faltantes.push('una letra mayúscula');
        if (!/[^A-Za-z0-9]/.test(password)) faltantes.push('un carácter especial (por ejemplo: ! @ # $ % &)');
        return faltantes;
    };

    window.mensajePasswordEspecifico = function (password) {
        const faltantes = window.faltantesPassword(password);
        if (faltantes.length === 0) return '';
        if (faltantes.length === 1) return 'A tu contraseña le falta ' + faltantes[0] + '.';
        const ultimo = faltantes.pop();
        return 'A tu contraseña le falta ' + faltantes.join(', ') + ' y ' + ultimo + '.';
    };

    window.validarPasswordPolitica = function (password) {
        return window.faltantesPassword(password).length === 0;
    };

    function mostrarError(input, mensaje) {
        const contenedor = input.closest('.form-grupo');
        if (!contenedor) return;
        const span = contenedor.querySelector('.form-error');
        if (span) span.textContent = mensaje || '';
    }

    window.activarValidacionFormulario = function (formId) {
        const form = document.getElementById(formId);
        if (!form) return;

        const campoEmail = form.querySelector('[data-validar="email"]');
        const campoPass = form.querySelector('[data-validar="password"]');
        const campoPassConfirma = form.querySelector('[data-validar="password-confirmacion"]');

        if (campoEmail) {
            campoEmail.addEventListener('blur', function () {
                if (campoEmail.value && !validarEmailFormato(campoEmail.value)) {
                    mostrarError(campoEmail, 'Ingresa un email con formato valido (ej: usuario@dominio.com).');
                } else {
                    mostrarError(campoEmail, '');
                }
            });
        }

        function passwordEsValida() {
            if (!campoPass.value && !campoPass.required) return true;
            return validarPasswordPolitica(campoPass.value);
        }

        if (campoPass) {
            campoPass.addEventListener('input', function () {
                if (!passwordEsValida()) {
                    mostrarError(campoPass, mensajePasswordEspecifico(campoPass.value));
                } else {
                    mostrarError(campoPass, '');
                }
                if (campoPassConfirma && campoPassConfirma.value) {
                    campoPassConfirma.dispatchEvent(new Event('input'));
                }
            });
        }

        if (campoPassConfirma) {
            campoPassConfirma.addEventListener('input', function () {
                if (campoPass && campoPassConfirma.value !== campoPass.value) {
                    mostrarError(campoPassConfirma, 'Las contraseñas no coinciden.');
                } else {
                    mostrarError(campoPassConfirma, '');
                }
            });
        }

        form.addEventListener('submit', function (evento) {
            let valido = true;

            let detalleAviso = 'Hay campos con datos invalidos.';
            if (campoEmail && !validarEmailFormato(campoEmail.value)) {
                mostrarError(campoEmail, 'Ingresa un email con formato valido.');
                valido = false;
            }
            if (campoPass && !passwordEsValida()) {
                const mensajePass = mensajePasswordEspecifico(campoPass.value);
                mostrarError(campoPass, mensajePass);
                detalleAviso = mensajePass;
                valido = false;
            }
            if (campoPassConfirma && campoPassConfirma.value !== campoPass.value) {
                mostrarError(campoPassConfirma, 'Las contraseñas no coinciden.');
                valido = false;
            }
            if (!valido) {
                evento.preventDefault();
                mostrarToast('error', 'Revisa el formulario', detalleAviso);
            }
        });
    };

    document.addEventListener('DOMContentLoaded', function () {
        const boton = document.getElementById('botonMenu');
        const sidebar = document.getElementById('sidebarPrincipal');
        if (boton && sidebar) {
            boton.addEventListener('click', function () {
                sidebar.classList.toggle('abierta');
            });
        }
    });
})();
