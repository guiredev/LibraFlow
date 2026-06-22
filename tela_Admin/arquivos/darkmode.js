/**
 * Dark Mode Toggle - LibraFlow
 * Script reutilizável para todas as telas administrativas
 */

(function() {
    'use strict';

    const CONFIG = {
        storageKey: 'libraflow_theme',
        darkClass: 'dark',
        buttonId: 'themeToggle',
        iconSelector: '#themeIcon',
        defaultTheme: 'light'
    };

    const ICONS = {
        light: '🌙',
        dark: '☀️'
    };

    function getStorage() {
        try {
            return window.localStorage;
        } catch (e) {
            return null;
        }
    }

    function getSavedTheme() {
        const storage = getStorage();
        const saved = storage ? storage.getItem(CONFIG.storageKey) : null;
        if (saved === 'dark' || saved === 'light') {
            return saved;
        }

        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            return 'dark';
        }

        return CONFIG.defaultTheme;
    }

    function saveTheme(theme) {
        const storage = getStorage();
        if (storage) {
            storage.setItem(CONFIG.storageKey, theme);
        }
    }

    function applyTheme(theme) {
        const body = document.body;
        const icon = document.querySelector(CONFIG.iconSelector);
        const button = document.getElementById(CONFIG.buttonId);

        if (!body) return;

        body.classList.toggle(CONFIG.darkClass, theme === 'dark');
        document.documentElement.classList.toggle(CONFIG.darkClass, theme === 'dark');
        body.setAttribute('data-theme', theme);

        if (icon) {
            icon.textContent = theme === 'dark' ? ICONS.dark : ICONS.light;
        }

        if (button) {
            button.setAttribute('aria-pressed', theme === 'dark' ? 'true' : 'false');
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
        let button = document.getElementById(CONFIG.buttonId);

        if (!button) {
            button = document.createElement('button');
            button.id = CONFIG.buttonId;
            button.className = 'theme-toggle-btn';
            button.type = 'button';
            button.setAttribute('aria-label', 'Alternar tema claro/escuro');
            button.innerHTML = '<span id="themeIcon">🌙</span>';

            const wrapper = document.createElement('div');
            wrapper.className = 'theme-toggle-wrapper';
            wrapper.appendChild(button);
            document.body.appendChild(wrapper);
        }

        button.addEventListener('click', function(e) {
            e.preventDefault();
            toggleTheme();
        });

        button.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                toggleTheme();
            }
        });
    }

    function initSystemThemeListener() {
        if (!window.matchMedia) {
            return;
        }

        const darkModeQuery = window.matchMedia('(prefers-color-scheme: dark)');
        if (darkModeQuery.addEventListener) {
            darkModeQuery.addEventListener('change', function(e) {
                const storage = getStorage();
                if (!storage || !storage.getItem(CONFIG.storageKey)) {
                    applyTheme(e.matches ? 'dark' : 'light');
                }
            });
        }
    }

    function init() {
        const onReady = function() {
            applyTheme(getSavedTheme());
            initToggleButton();
            initSystemThemeListener();
            document.body.classList.add('theme-transition-enabled');
        };

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', onReady);
        } else {
            onReady();
        }
    }

    init();

    window.LibraFlowTheme = {
        toggle: toggleTheme,
        setTheme: function(theme) {
            applyTheme(theme);
            saveTheme(theme);
        },
        getTheme: function() {
            return document.body.classList.contains(CONFIG.darkClass) ? 'dark' : 'light';
        }
    };
})();
