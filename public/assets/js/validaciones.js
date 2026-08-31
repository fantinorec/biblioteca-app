/**
 * Validaciones de formato en el navegador (complementan, NUNCA reemplazan,
 * a las validaciones del servidor). Todo se muestra en <span class="form-error">
 * debajo de cada campo; no se usa alert() en ningun caso.
 */
(function () {
    window.validarEmailFormato = function (email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    };

    window.validarPasswordPolitica = function (password) {
        return password.length >= 8 && /[A-Z]/.test(password) && /[^A-Za-z0-9]/.test(password);
    };

    function mostrarError(input, mensaje) {
        const contenedor = input.closest('.form-grupo');
        if (!contenedor) return;
        const span = contenedor.querySelector('.form-error');
        if (span) span.textContent = mensaje || '';
    }

    /** Engancha validacion en vivo de email + politica de password + coincidencia de contraseñas en un formulario. */
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

        if (campoPass) {
            campoPass.addEventListener('input', function () {
                if (campoPass.value && !validarPasswordPolitica(campoPass.value)) {
                    mostrarError(campoPass, 'Debe tener 8+ caracteres, una mayuscula y un caracter especial.');
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
            if (campoEmail && !validarEmailFormato(campoEmail.value)) {
                mostrarError(campoEmail, 'Ingresa un email con formato valido.');
                valido = false;
            }
            if (campoPass && !validarPasswordPolitica(campoPass.value)) {
                mostrarError(campoPass, 'Debe tener 8+ caracteres, una mayuscula y un caracter especial.');
                valido = false;
            }
            if (campoPassConfirma && campoPassConfirma.value !== campoPass.value) {
                mostrarError(campoPassConfirma, 'Las contraseñas no coinciden.');
                valido = false;
            }
            if (!valido) {
                evento.preventDefault();
                mostrarToast('error', 'Revisa el formulario', 'Hay campos con datos invalidos.');
            }
        });
    };

    /** Menu lateral responsive (hamburguesa) */
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
