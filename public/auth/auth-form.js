// MAPA RAPIDO DO ARQUIVO
// Local: public/auth/auth-form.js
// Funcao: Interacoes compartilhadas de formularios de autenticacao.
(function () {
    'use strict';

    function setupPasswordToggles() {
        document.querySelectorAll('.password-toggle').forEach(function (button) {
            button.addEventListener('click', function () {
                const input = button.parentElement.querySelector('input');

                if (!input) {
                    return;
                }

                const isPassword = input.type === 'password';
                input.type = isPassword ? 'text' : 'password';
                button.setAttribute('aria-label', isPassword ? 'Ocultar senha' : 'Mostrar senha');
                button.innerHTML = isPassword
                    ? '<i class="fas fa-eye-slash" aria-hidden="true"></i>'
                    : '<i class="fas fa-eye" aria-hidden="true"></i>';
            });
        });
    }

    function scorePassword(value) {
        let score = 0;

        if (value.length >= 8) {
            score += 1;
        }

        if (/[A-Z]/.test(value) && /[a-z]/.test(value)) {
            score += 1;
        }

        if (/\d/.test(value)) {
            score += 1;
        }

        if (/[^A-Za-z0-9]/.test(value)) {
            score += 1;
        }

        return score;
    }

    function setupPasswordStrength() {
        const input = document.querySelector('[data-password-strength]');
        const meter = document.querySelector('.password-meter');

        if (!input || !meter) {
            return;
        }

        const label = meter.querySelector('small');

        input.addEventListener('input', function () {
            const value = input.value;
            const score = scorePassword(value);

            if (!value) {
                meter.removeAttribute('data-strength');
                if (label) {
                    label.textContent = 'Use letras, números e símbolos para uma senha mais forte.';
                }
                return;
            }

            if (score <= 1) {
                meter.dataset.strength = 'weak';
                if (label) {
                    label.textContent = 'Senha fraca. Use no mínimo 8 caracteres.';
                }
            } else if (score <= 3) {
                meter.dataset.strength = 'medium';
                if (label) {
                    label.textContent = 'Senha média. Adicione letras maiúsculas ou símbolos.';
                }
            } else {
                meter.dataset.strength = 'strong';
                if (label) {
                    label.textContent = 'Senha forte.';
                }
            }
        });
    }

    function setupSubmittingState() {
        document.querySelectorAll('form').forEach(function (form) {
            form.addEventListener('submit', function () {
                const button = form.querySelector('.primary-action');

                if (!button) {
                    return;
                }

                button.disabled = true;
                button.textContent = 'Aguarde...';
            });
        });
    }

    function init() {
        setupPasswordToggles();
        setupPasswordStrength();
        setupSubmittingState();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
