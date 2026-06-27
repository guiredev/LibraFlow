// MAPA RAPIDO DO ARQUIVO
// Local: public/admin/darkmode.js
// Funcao: Controle de tema claro/escuro e pesquisa global da area administrativa.
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
        if (saved === 'dark' || saved === 'light') { return saved; }
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) { return 'dark'; }
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
        if (icon) { icon.innerHTML = ICONS[normalizedTheme]; }
        if (label) { label.textContent = LABELS[normalizedTheme]; }
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
        if (!button) { return; }

        button.addEventListener('click', toggleTheme);
        button.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                toggleTheme();
            }
        });
    }

    function initSystemThemeListener() {
        if (!window.matchMedia) { return; }
        const darkModeQuery = window.matchMedia('(prefers-color-scheme: dark)');
        darkModeQuery.addEventListener('change', function (event) {
            if (!localStorage.getItem(CONFIG.storageKey) && !localStorage.getItem('tema')) {
                applyTheme(event.matches ? 'dark' : 'light');
            }
        });
    }

    function adminBaseUrl() {
        return '/LibraFlow/public/admin/';
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function formatDate(value) {
        if (!value) { return ''; }
        const parts = String(value).split('-');
        if (parts.length !== 3) { return value; }
        return parts[2] + '/' + parts[1] + '/' + parts[0];
    }

    function statusClass(status) {
        if (status === 'V') { return 'status-vencido'; }
        if (status === 'D') { return 'status-devolvido'; }
        return 'status-ativo';
    }

    function resultLink(url) {
        if (!url) { return '#'; }
        if (url.indexOf('/LibraFlow/') === 0 || url.indexOf('http') === 0) { return url; }
        return adminBaseUrl() + url.replace(/^\.\//, '');
    }

    function renderSection(title, icon, itemsHtml) {
        if (!itemsHtml) { return ''; }
        return '<section class="pesquisa-global-secao"><h3><i class="fas ' + icon + '" aria-hidden="true"></i>' + title + '</h3>' + itemsHtml + '</section>';
    }

    function renderResults(data) {
        const paginas = data.paginas || [];
        const alunos = data.alunos || [];
        const livros = data.livros || [];
        const emprestimos = data.emprestimos || [];
        let html = '';

        html += renderSection('Paginas', 'fa-location-dot', paginas.map(function (pagina) {
            return '<a class="pesquisa-global-item" href="' + escapeHtml(resultLink(pagina.url)) + '">' +
                '<span class="pesquisa-global-icone"><i class="fas ' + escapeHtml(pagina.icone || 'fa-file') + '" aria-hidden="true"></i></span>' +
                '<span><strong>' + escapeHtml(pagina.titulo) + '</strong><small>' + escapeHtml(pagina.descricao) + '</small></span>' +
                '</a>';
        }).join(''));

        html += renderSection('Alunos', 'fa-users', alunos.map(function (aluno) {
            return '<a class="pesquisa-global-item" href="' + adminBaseUrl() + 'detalhe_aluno.php?id=' + encodeURIComponent(aluno.id) + '">' +
                '<span class="pesquisa-global-icone"><i class="fas fa-user-graduate" aria-hidden="true"></i></span>' +
                '<span><strong>' + escapeHtml(aluno.nome) + '</strong><small>' + escapeHtml(aluno.email || 'Sem e-mail') + ' - RM ' + escapeHtml(aluno.rm || 'nao informado') + ' - ' + escapeHtml(aluno.emprestimos_abertos || 0) + ' aberto(s)</small></span>' +
                '</a>';
        }).join(''));

        html += renderSection('Livros', 'fa-book-open', livros.map(function (livro) {
            return '<a class="pesquisa-global-item" href="' + adminBaseUrl() + 'detalhe_livro.php?id=' + encodeURIComponent(livro.id) + '">' +
                '<span class="pesquisa-global-icone"><i class="fas fa-book" aria-hidden="true"></i></span>' +
                '<span><strong>' + escapeHtml(livro.titulo) + '</strong><small>' + escapeHtml(livro.autor || 'Autor nao informado') + ' - ' + escapeHtml(livro.quantidade || 0) + ' disponivel(is)</small></span>' +
                '</a>';
        }).join(''));

        html += renderSection('Emprestimos', 'fa-clipboard-list', emprestimos.map(function (emprestimo) {
            return '<a class="pesquisa-global-item" href="' + adminBaseUrl() + 'emprestimos.php?busca=' + encodeURIComponent(emprestimo.aluno || emprestimo.livro || emprestimo.id) + '">' +
                '<span class="pesquisa-global-icone"><i class="fas fa-right-left" aria-hidden="true"></i></span>' +
                '<span><strong>' + escapeHtml(emprestimo.aluno) + '</strong><small>' + escapeHtml(emprestimo.livro) + ' - previsao ' + escapeHtml(formatDate(emprestimo.data_prevista_devolucao)) + '</small></span>' +
                '<em class="' + statusClass(emprestimo.status) + '">' + escapeHtml(emprestimo.status_nome) + '</em>' +
                '</a>';
        }).join(''));

        if (!html) {
            html = '<div class="pesquisa-global-vazia"><i class="fas fa-magnifying-glass" aria-hidden="true"></i><strong>Nenhum resultado encontrado</strong><span>Tente buscar por nome, RM, livro, autor, ISBN, emprestimo ou pagina.</span></div>';
        }

        return html;
    }

    function createGlobalSearchMarkup() {
        const wrapper = document.createElement('div');
        wrapper.className = 'pesquisa-global';
        wrapper.innerHTML =
            '<button type="button" id="abrirPesquisaGlobal" class="pesquisa-global-botao" aria-label="Abrir pesquisa global">' +
                '<i class="fas fa-magnifying-glass" aria-hidden="true"></i><span>Pesquisar</span><kbd>Ctrl K</kbd>' +
            '</button>' +
            '<div id="pesquisaGlobalOverlay" class="pesquisa-global-overlay" aria-hidden="true">' +
                '<div class="pesquisa-global-modal" role="dialog" aria-modal="true" aria-label="Pesquisa global">' +
                    '<div class="pesquisa-global-campo">' +
                        '<i class="fas fa-magnifying-glass" aria-hidden="true"></i>' +
                        '<input type="search" id="campoPesquisaGlobal" placeholder="Buscar paginas, alunos, livros ou emprestimos..." autocomplete="off">' +
                        '<button type="button" id="fecharPesquisaGlobal" aria-label="Fechar pesquisa"><i class="fas fa-xmark" aria-hidden="true"></i></button>' +
                    '</div>' +
                    '<div id="resultadoPesquisaGlobal" class="pesquisa-global-resultados">' +
                        '<div class="pesquisa-global-ajuda"><i class="fas fa-keyboard" aria-hidden="true"></i><span>Digite ao menos 2 caracteres para pesquisar em todo o admin.</span></div>' +
                    '</div>' +
                '</div>' +
            '</div>';
        document.body.appendChild(wrapper);
    }

    function initGlobalSearch() {
        if (!window.location.pathname.includes('/LibraFlow/public/admin/')) { return; }
        if (document.getElementById('abrirPesquisaGlobal')) { return; }

        createGlobalSearchMarkup();

        const openButton = document.getElementById('abrirPesquisaGlobal');
        const overlay = document.getElementById('pesquisaGlobalOverlay');
        const closeButton = document.getElementById('fecharPesquisaGlobal');
        const input = document.getElementById('campoPesquisaGlobal');
        const results = document.getElementById('resultadoPesquisaGlobal');
        let timer = null;
        let controller = null;

        function openSearch() {
            overlay.classList.add('ativo');
            overlay.setAttribute('aria-hidden', 'false');
            setTimeout(function () { input.focus(); }, 40);
        }

        function closeSearch() {
            overlay.classList.remove('ativo');
            overlay.setAttribute('aria-hidden', 'true');
        }

        function setLoading() {
            results.innerHTML = '<div class="pesquisa-global-ajuda"><i class="fas fa-spinner fa-spin" aria-hidden="true"></i><span>Pesquisando...</span></div>';
        }

        function showSearchError(message) {
            results.innerHTML = '<div class="pesquisa-global-vazia"><i class="fas fa-triangle-exclamation" aria-hidden="true"></i><strong>Nao foi possivel pesquisar agora</strong><span>' + escapeHtml(message || 'Verifique o servidor local.') + '</span></div>';
        }

        function runSearch(term) {
            if (controller) { controller.abort(); }
            controller = new AbortController();
            setLoading();

            fetch(adminBaseUrl() + 'busca_rapida.php?q=' + encodeURIComponent(term), { signal: controller.signal })
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('Resposta HTTP ' + response.status + '.');
                    }
                    return response.text();
                })
                .then(function (text) {
                    let data = null;
                    try {
                        data = JSON.parse(text);
                    } catch (error) {
                        throw new Error('O endpoint retornou uma resposta invalida.');
                    }
                    results.innerHTML = renderResults(data);
                })
                .catch(function (error) {
                    if (error.name === 'AbortError') { return; }
                    showSearchError(error.message);
                });
        }

        openButton.addEventListener('click', openSearch);
        closeButton.addEventListener('click', closeSearch);
        overlay.addEventListener('click', function (event) {
            if (event.target === overlay) { closeSearch(); }
        });

        input.addEventListener('input', function () {
            const term = input.value.trim();
            clearTimeout(timer);
            if (term.length < 2) {
                results.innerHTML = '<div class="pesquisa-global-ajuda"><i class="fas fa-keyboard" aria-hidden="true"></i><span>Digite ao menos 2 caracteres para pesquisar em todo o admin.</span></div>';
                return;
            }
            timer = setTimeout(function () { runSearch(term); }, 220);
        });

        document.addEventListener('keydown', function (event) {
            const isShortcut = (event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k';
            if (isShortcut) {
                event.preventDefault();
                openSearch();
            }
            if (event.key === 'Escape' && overlay.classList.contains('ativo')) { closeSearch(); }
        });
    }

    function onReady() {
        const savedTheme = localStorage.getItem(CONFIG.storageKey) || localStorage.getItem('tema') || getSavedTheme();
        applyTheme(savedTheme);
        initToggleButton();
        initSystemThemeListener();
        initGlobalSearch();
        setTimeout(function () { document.body.classList.add('theme-transition-enabled'); }, 100);
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