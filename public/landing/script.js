const body = document.body;
const themeToggle = document.querySelector('.theme-toggle');
const menuToggle = document.querySelector('.menu-toggle');
const mainMenu = document.querySelector('.main-menu');
const currentYear = document.querySelector('#current-year');

if (localStorage.getItem('libraflow-theme') === 'dark') body.classList.add('dark');

function updateThemeButton() {
	const isDark = body.classList.contains('dark');
	themeToggle.innerHTML = `<i class="fas fa-${isDark ? 'sun' : 'moon'}" aria-hidden="true"></i>`;
	themeToggle.setAttribute('aria-label', isDark ? 'Ativar tema claro' : 'Ativar tema escuro');
}

themeToggle.addEventListener('click', () => {
	body.classList.toggle('dark');
	localStorage.setItem('libraflow-theme', body.classList.contains('dark') ? 'dark' : 'light');
	updateThemeButton();
});

menuToggle.addEventListener('click', () => {
	const isOpen = mainMenu.classList.toggle('is-open');
	menuToggle.setAttribute('aria-expanded', String(isOpen));
	menuToggle.setAttribute('aria-label', isOpen ? 'Fechar menu' : 'Abrir menu');
	menuToggle.innerHTML = `<i class="fas fa-${isOpen ? 'xmark' : 'bars'}" aria-hidden="true"></i>`;
});

mainMenu.querySelectorAll('a').forEach((link) =>
	link.addEventListener('click', () => {
		mainMenu.classList.remove('is-open');
		menuToggle.setAttribute('aria-expanded', 'false');
		menuToggle.innerHTML = '<i class="fas fa-bars" aria-hidden="true"></i>';
	}),
);

currentYear.textContent = new Date().getFullYear();
updateThemeButton();
