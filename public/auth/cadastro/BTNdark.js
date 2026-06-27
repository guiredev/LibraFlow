// MAPA RAPIDO DO ARQUIVO
// Local: public/auth/cadastro/BTNdark.js
// Funcao: Script antigo do botao de tema escuro.
document.addEventListener('DOMContentLoaded', function () {
    const button = document.getElementById('themeToggle');

    if (button && window.LibraFlowTheme) {
        button.addEventListener('click', window.LibraFlowTheme.toggle);
    }
});
