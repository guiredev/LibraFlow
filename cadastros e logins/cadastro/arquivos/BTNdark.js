const btn = document.getElementById('themeBtn');
const label = document.getElementById('themeLabel');

btn.addEventListener('click', () => {
  const isDark = document.body.classList.toggle('dark');
  btn.textContent = isDark ? '🌙' : '☀️';
  label.textContent = isDark ? 'Escuro' : 'Claro';
  btn.setAttribute('aria-label', isDark ? 'Ativar tema claro' : 'Ativar tema escuro');
});