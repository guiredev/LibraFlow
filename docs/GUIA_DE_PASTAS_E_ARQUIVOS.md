# Guia de Pastas, Arquivos e Funcionalidades - LibraFlow

Este documento serve como mapa rapido para encontrar onde fica cada parte do sistema.

## Visao Geral da Estrutura

```text
LibraFlow/
├── app/                  # Codigo interno compartilhado
├── public/               # Paginas acessadas pelo navegador
├── database/             # Banco de dados / dump SQL
├── docs/                 # Documentacao do projeto
├── Imagens/              # Imagens soltas ou materiais de apoio
├── vendor/               # Dependencias Composer
├── composer.json         # Lista de dependencias PHP
├── composer.lock         # Versoes travadas das dependencias
├── HANDOFF.md            # Resumo geral do projeto
└── README.md             # Apresentacao curta
```

## `app/`

Codigo que o navegador nao deve abrir diretamente. Use esta pasta para arquivos compartilhados entre telas.

### `app/config/`

- `conexao.php`: conexao PDO principal com o banco `libraflow`. Cria a variavel `$conn`.
- `auth.php`: funcoes de autenticacao, sessao, cookie "lembrar-me" e redirecionamento por perfil.
- `auth_check.php`: bloqueia paginas sem login e tenta restaurar sessao pelo cookie.
- `email.php`: funcoes de envio de email com PHPMailer e criacao do link de redefinicao de senha.
- `email.local.example.php`: exemplo de configuracao SMTP local.
- `email.local.php`: configuracao SMTP real da maquina local. Nao deve ir para o Git.

## `public/`

Tudo que e aberto pelo navegador fica aqui.

### `public/auth/`

Modulo de autenticacao.

- `logout.php`: encerra sessao, remove cookie de login persistente e redireciona para o login.

#### `public/auth/login/`

Tela de entrada do sistema.

- `login.php`: valida email e senha, inicia sessao e redireciona para admin ou usuario.
- `styles.css`: estilos da tela de login.
- `animations.css`: animacoes da tela de login.
- `darkmode.js`: controle do tema claro/escuro.
- `imgs/Logo-LibraFlow.png`: logo usada no login.
- `imgs/img-main.png`: imagem lateral/principal do login.

#### `public/auth/cadastro/`

Cadastro de usuarios comuns.

- `register.php`: recebe dados do formulario e cria usuario na tabela `usuarios`.
- `styles.css`: estilos da tela de cadastro.
- `animations.css`: animacoes da tela de cadastro.
- `darkmode.js`: controle do tema claro/escuro.
- `BTNdark.js`: script antigo relacionado ao botao de tema.
- `imgs/Logo-LibraFlow.png`: logo usada no cadastro.
- `imgs/img-main.png`: imagem lateral/principal do cadastro.

#### `public/auth/senha/`

Recuperacao de senha.

- `esqueceu-a-senha.php`: formulario para pedir link de recuperacao por email.
- `redefinir-senha.php`: valida token e grava nova senha.
- `estilo.css`: estilos das telas de recuperacao e redefinicao.
- `darkmode.js`: controle do tema claro/escuro.

### `public/usuario/`

Area do aluno/usuario comum.

- `index.php`: pagina inicial apos login do usuario comum.
- `index.html`: redirecionamento antigo para `index.php`.
- `styles.css`: estilos da area do usuario.
- `darkmode.js`: controle do tema claro/escuro.
- `imgs/Logo-LibraFlow.png`: logo da area do usuario.

### `public/catalogo/`

Catalogo de livros e emprestimos vistos pelo usuario.

- `catalogo.php`: lista livros, busca por texto e filtra por categoria.
- `livro.php`: mostra detalhes de um livro selecionado.
- `solicitar.php`: cria uma solicitacao/emprestimo para o usuario logado.
- `meus_emprestimos.php`: mostra emprestimos do usuario.
- `style.css`: estilos do catalogo, detalhes e emprestimos.
- `darkmode.js`: controle do tema claro/escuro.
- `conexao.php`: conexao antiga em MySQLi. Prefira `app/config/conexao.php` em codigo novo.
- `capas/`: capas dos livros cadastrados.
- `imgs/Logo-LibraFlow.png`: logo usada no catalogo.
- `imgs/img-main.png`: imagem de apoio do catalogo.

### `public/admin/`

Area administrativa. Deve ser acessada apenas por usuario do tipo admin (`tipo = 'D'`).

- `Admin.php`: dashboard inicial do administrador.
- `cadastrar_livro.php`: cadastro de livro e upload de capa.
- `editar_livro.php`: edicao de livro existente.
- `listar_livros.php`: listagem administrativa de livros.
- `emprestimos.php`: acompanhamento, aprovacao/baixa e controle de emprestimos.
- `novo_emprestimo.php`: cria emprestimo manualmente pelo administrador.
- `cadastro_rapido_aluno.php`: cadastro rapido de aluno durante fluxos administrativos.
- `usuarios.php`: listagem, edicao e remocao de usuarios.
- `visitas.php`: registro e consulta de visitas da biblioteca.
- `relatorios.php`: tela antiga/simples de relatorios.
- `gerar_relatorio.php`: gerador antigo de relatorios em PDF/Excel.
- `conexao.php`: conexao administrativa legada. Prefira `app/config/conexao.php` em codigo novo.
- `style.css`: estilos da area administrativa.
- `darkmode-btn.css`: estilo compartilhado do botao de tema.
- `darkmode.js`: controle do tema claro/escuro.
- `imgs/Logo-LibraFlow.png`: logo usada no painel admin.

#### `public/admin/relatorios/`

Relatorios administrativos atuais.

- `index.php`: tela principal de relatorios com filtros e botoes de exportacao.
- `gerar_relatorio.php`: exporta relatorios em PDF ou XLSX.
- `RelatorioService.php`: centraliza as consultas SQL usadas pelos relatorios.

## `database/`

- `libraflow.sql`: estrutura e dados iniciais do banco MariaDB/MySQL.

## `docs/`

Documentacao do projeto.

- `HANDOFF.md`: copia do handoff principal.
- `GUIA_DE_PASTAS_E_ARQUIVOS.md`: este guia separado de pastas, arquivos e funcionalidades.
- `README.md`: indice curto da documentacao.
- `guia-estilizacao/GuiadeCores-LibraFlow.md`: guia de cores, temas e identidade visual.

## `Imagens/`

Pasta de imagens soltas ou materiais de apoio. Nao parece ser usada diretamente pelas telas principais reorganizadas.

## `vendor/`

Dependencias instaladas pelo Composer.

- Nao edite arquivos desta pasta manualmente.
- Para atualizar dependencias, use Composer.

## Arquivos da Raiz

- `.gitignore`: define arquivos que nao entram no Git, como `app/config/email.local.php`.
- `composer.json`: declara dependencias PHP do projeto.
- `composer.lock`: trava as versoes exatas instaladas.
- `HANDOFF.md`: resumo geral para continuar o projeto.
- `README.md`: descricao curta e ponto de partida.

## Funcionalidades Por Area

- Login: `public/auth/login/login.php`
- Cadastro: `public/auth/cadastro/register.php`
- Recuperacao de senha: `public/auth/senha/`
- Logout: `public/auth/logout.php`
- Home do usuario: `public/usuario/index.php`
- Catalogo: `public/catalogo/catalogo.php`
- Detalhe do livro: `public/catalogo/livro.php`
- Solicitar emprestimo: `public/catalogo/solicitar.php`
- Meus emprestimos: `public/catalogo/meus_emprestimos.php`
- Dashboard admin: `public/admin/Admin.php`
- Cadastro de livros: `public/admin/cadastrar_livro.php`
- Gestao de livros: `public/admin/listar_livros.php` e `public/admin/editar_livro.php`
- Gestao de emprestimos: `public/admin/emprestimos.php` e `public/admin/novo_emprestimo.php`
- Gestao de usuarios: `public/admin/usuarios.php`
- Visitas da biblioteca: `public/admin/visitas.php`
- Relatorios atuais: `public/admin/relatorios/`

## Caminhos Importantes Para Programar

Para proteger uma pagina com login:

```php
require $_SERVER['DOCUMENT_ROOT'] . '/LibraFlow/app/config/auth_check.php';
```

Para usar o banco:

```php
require $_SERVER['DOCUMENT_ROOT'] . '/LibraFlow/app/config/conexao.php';
```

Para criar links no HTML, use caminhos absolutos a partir do projeto:

```html
<a href="/LibraFlow/public/catalogo/catalogo.php">Catalogo</a>
```