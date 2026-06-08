/**
 * Dark Mode Toggle - LibraFlow
 * Script reutilizável para todas as telas
 * Segue guia de estilização
 */

(function() {
    'use strict';

    console.log('🌙 Dark Mode script carregado');

    // Configurações
    const CONFIG = {
        storageKey: 'libraflow_theme',
        darkClass: 'dark',
        buttonId: 'themeToggle',
        iconSelector: '#themeIcon',
        labelSelector: '#themeLabel',
        defaultTheme: 'light'
    };

    // Ícones
    const ICONS = {
        light: '🌙',
        dark: '☀️'
    };

    // Labels
    const LABELS = {
        light: 'Escuro',
        dark: 'Claro'
    };

    /**
     * Obtém o tema salvo ou detecta preferência do sistema
     */
    function getSavedTheme() {
        const saved = localStorage.getItem(CONFIG.storageKey);
        if (saved) {
            console.log('✅ Tema salvo:', saved);
            return saved;
        }

        // Detectar preferência do sistema
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            console.log('🔍 Sistema prefere dark mode');
            return 'dark';
        }

        console.log('🔍 Usando tema padrão:', CONFIG.defaultTheme);
        return CONFIG.defaultTheme;
    }

    /**
     * Salva a preferência de tema
     */
    function saveTheme(theme) {
        localStorage.setItem(CONFIG.storageKey, theme);
        console.log('💾 Tema salvo:', theme);
    }

    /**
     * Aplica o tema ao documento
     */
    function applyTheme(theme) {
        const body = document.body;
        const icon = document.querySelector(CONFIG.iconSelector);
        const label = document.querySelector(CONFIG.labelSelector);

        console.log('🎨 Aplicando tema:', theme);

        if (theme === 'dark') {
            body.classList.add(CONFIG.darkClass);
            if (icon) icon.textContent = ICONS.dark;
            if (label) label.textContent = LABELS.dark;
            console.log('✅ Dark mode ativado');
        } else {
            body.classList.remove(CONFIG.darkClass);
            if (icon) icon.textContent = ICONS.light;
            if (label) label.textContent = LABELS.light;
            console.log('✅ Light mode ativado');
        }
    }

    /**
     * Alterna entre temas claro e escuro
     */
    function toggleTheme() {
        const body = document.body;
        const currentTheme = body.classList.contains(CONFIG.darkClass) ? 'dark' : 'light';
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';

        console.log('🔄 Alternando tema:', currentTheme, '→', newTheme);

        applyTheme(newTheme);
        saveTheme(newTheme);

        // Disparar evento customizado para outros scripts escutarem
        window.dispatchEvent(new CustomEvent('themeChanged', { detail: { theme: newTheme } }));
    }

    /**
     * Inicializa o botão de toggle
     */
    function initToggleButton() {
        const button = document.getElementById(CONFIG.buttonId);
        if (!button) {
            console.warn('⚠️ Botão de tema não encontrado (ID: ' + CONFIG.buttonId + ')');
            return;
        }

        console.log('✅ Botão de tema encontrado');

        button.addEventListener('click', toggleTheme);

        // Adicionar suporte a teclado (Enter e Space)
        button.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                toggleTheme();
            }
        });
    }

    /**
     * Ouvir mudanças no tema do sistema
     */
    function initSystemThemeListener() {
        if (window.matchMedia) {
            const darkModeQuery = window.matchMedia('(prefers-color-scheme: dark)');

            darkModeQuery.addEventListener('change', function(e) {
                // Aplicar tema do sistema apenas se o usuário não tiver preferência salva
                if (!localStorage.getItem(CONFIG.storageKey)) {
                    const theme = e.matches ? 'dark' : 'light';
                    applyTheme(theme);
                    console.log('🔍 Tema do sistema mudado para:', theme);
                }
            });
        }
    }

    /**
     * Inicialização
     */
    function init() {
        console.log('🚀 Iniciando Dark Mode...');

        // Aguardar DOM estar pronto
        if (document.readyState === 'loading') {
            console.log('⏳ Aguardando DOM...');
            document.addEventListener('DOMContentLoaded', onDOMReady);
        } else {
            console.log('✅ DOM já está pronto');
            onDOMReady();
        }
    }

    function onDOMReady() {
        console.log('🎯 DOM Ready - Configurando tema');

        // Aplicar tema salvo ao carregar
        const savedTheme = getSavedTheme();
        applyTheme(savedTheme);

        // Inicializar botão
        initToggleButton();

        // Ouvir mudanças no tema do sistema
        initSystemThemeListener();

        // Adicionar classe de transição após carregamento inicial
        // para evitar transição durante o carregamento da página
        setTimeout(() => {
            document.body.classList.add('theme-transition-enabled');
            console.log('✅ Transições habilitadas');
        }, 100);

        console.log('✅ Dark Mode inicializado com sucesso!');
    }

    // Inicializar imediatamente
    init();

    // Expor função globalmente para uso em outros scripts
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

    console.log('🔧 API exposta: window.LibraFlowTheme');
})();
