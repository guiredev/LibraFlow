document.addEventListener('DOMContentLoaded', function () {
    const button = document.getElementById('themeToggle');

    if (button && window.LibraFlowTheme) {
        button.addEventListener('click', window.LibraFlowTheme.toggle);
    }
});
