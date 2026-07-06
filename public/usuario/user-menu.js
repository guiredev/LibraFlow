/*
 * MAPA RAPIDO DO ARQUIVO
 * Local: public/usuario/user-menu.js
 * Funcao: Controla abertura e fechamento do menu do avatar.
 */

(function () {
    const user = document.querySelector('.user');
    const button = document.querySelector('.user-menu-button');
    const menu = document.querySelector('.user-menu');

    if (!user || !button || !menu) {
        return;
    }

    function setOpen(open) {
        user.classList.toggle('aberto', open);
        button.setAttribute('aria-expanded', open ? 'true' : 'false');
    }

    button.addEventListener('click', function (event) {
        event.stopPropagation();
        setOpen(!user.classList.contains('aberto'));
    });

    document.addEventListener('click', function (event) {
        if (!user.contains(event.target)) {
            setOpen(false);
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            setOpen(false);
            button.focus();
        }
    });
})();
