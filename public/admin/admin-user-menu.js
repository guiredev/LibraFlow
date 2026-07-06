/*
 * MAPA RAPIDO DO ARQUIVO
 * Local: public/admin/admin-user-menu.js
 * Funcao: Controla o menu do avatar na area administrativa.
 */

(function () {
    const menu = document.querySelector('.admin-user-menu');
    const button = document.querySelector('.admin-user-button');

    if (!menu || !button) {
        return;
    }

    function setOpen(open) {
        menu.classList.toggle('aberto', open);
        button.setAttribute('aria-expanded', open ? 'true' : 'false');
    }

    button.addEventListener('click', function (event) {
        event.stopPropagation();
        setOpen(!menu.classList.contains('aberto'));
    });

    document.addEventListener('click', function (event) {
        if (!menu.contains(event.target)) {
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
