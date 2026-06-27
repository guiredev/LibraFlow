// MAPA RAPIDO DO ARQUIVO
// Local: public/usuario/darkmode.js
// Funcao: Controle de tema claro/escuro da area do usuario.
/**
 * Dark Mode Toggle - LibraFlow
 * Shared behavior for all screens.
 */
(function () {
    'use strict';

    const CONFIG = {
        storageKey: 'libraflow_theme',
        darkClass: 'dark',
        buttonId: 'themeToggle',
        iconSelector: '#themeIcon',
        labelSelector: '#themeLabel',
        defaultTheme: 'light',
    };

    const ICONS = {
        light: '<i class="fas fa-moon" aria-hidden="true"></i>',
        dark: '<i class="fas fa-sun" aria-hidden="true"></i>',
    };

    const LABELS = {
        light: 'Escuro',
        dark: 'Claro',
    };

    function getSavedTheme() {
        const saved = localStorage.getItem(CONFIG.storageKey);

        if (saved === 'dark' || saved === 'light') {
            return saved;
        }

        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            return 'dark';
        }

        return CONFIG.defaultTheme;
    }

    function saveTheme(theme) {
        localStorage.setItem(CONFIG.storageKey, theme);
        localStorage.setItem('tema', theme);
    }

    function applyTheme(theme) {
        const normalizedTheme = theme === 'dark' ? 'dark' : 'light';
        const icon = document.querySelector(CONFIG.iconSelector);
        const label = document.querySelector(CONFIG.labelSelector);

        document.body.classList.toggle(CONFIG.darkClass, normalizedTheme === 'dark');

        if (icon) {
            icon.innerHTML = ICONS[normalizedTheme];
        }

        if (label) {
            label.textContent = LABELS[normalizedTheme];
        }
    }

    function toggleTheme() {
        const currentTheme = document.body.classList.contains(CONFIG.darkClass) ? 'dark' : 'light';
        const nextTheme = currentTheme === 'dark' ? 'light' : 'dark';

        applyTheme(nextTheme);
        saveTheme(nextTheme);
        window.dispatchEvent(new CustomEvent('themeChanged', { detail: { theme: nextTheme } }));
    }

    function initToggleButton() {
        const button = document.getElementById(CONFIG.buttonId);

        if (!button) {
            return;
        }

        button.addEventListener('click', toggleTheme);
        button.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                toggleTheme();
            }
        });
    }

    function initSystemThemeListener() {
        if (!window.matchMedia) {
            return;
        }

        const darkModeQuery = window.matchMedia('(prefers-color-scheme: dark)');
        darkModeQuery.addEventListener('change', function (event) {
            if (!localStorage.getItem(CONFIG.storageKey) && !localStorage.getItem('tema')) {
                applyTheme(event.matches ? 'dark' : 'light');
            }
        });
    }

    function onReady() {
        const savedTheme = localStorage.getItem(CONFIG.storageKey) || localStorage.getItem('tema') || getSavedTheme();

        applyTheme(savedTheme);
        initToggleButton();
        initSystemThemeListener();

        setTimeout(function () {
            document.body.classList.add('theme-transition-enabled');
        }, 100);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', onReady);
    } else {
        onReady();
    }

    window.LibraFlowTheme = {
        toggle: toggleTheme,
        setTheme: function (theme) {
            const normalizedTheme = theme === 'dark' ? 'dark' : 'light';
            applyTheme(normalizedTheme);
            saveTheme(normalizedTheme);
        },
        getTheme: function () {
            return document.body.classList.contains(CONFIG.darkClass) ? 'dark' : 'light';
        },
    };
})();
