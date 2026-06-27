# HANDOFF.md - LibraFlow

Ultima atualizacao: 2026-06-27
Projeto: sistema PHP/MySQL para gestao de biblioteca.

## Como navegar

A estrutura foi reorganizada para separar codigo compartilhado, paginas acessadas no navegador e documentacao.

```text
LibraFlow/
├── app/                  # codigo interno compartilhado
│   └── config/           # banco, autenticacao, sessao, email
├── public/               # paginas abertas pelo navegador
│   ├── auth/             # login, cadastro, recuperacao de senha, logout
│   ├── admin/            # painel administrativo
│   ├── catalogo/         # catalogo e emprestimos do usuario
│   └── usuario/          # pagina inicial do usuario comum
├── database/             # dump SQL do banco
├── docs/                 # documentacao e guia visual
├── Imagens/              # imagens soltas/apoio do projeto
├── vendor/               # dependencias Composer, nao editar manualmente
├── composer.json         # dependencias PHP
└── README.md             # resumo curto do projeto
```

## Rotas principais no navegador

- Login: `http://localhost/LibraFlow/public/auth/login/login.php`
- Cadastro: `http://localhost/LibraFlow/public/auth/cadastro/register.php`
- Recuperar senha: `http://localhost/LibraFlow/public/auth/senha/esqueceu-a-senha.php`
- Usuario: `http://localhost/LibraFlow/public/usuario/index.php`
- Catalogo: `http://localhost/LibraFlow/public/catalogo/catalogo.php`
- Admin: `http://localhost/LibraFlow/public/admin/Admin.php`
- Relatorios atuais: `http://localhost/LibraFlow/public/admin/relatorios/index.php`

## Banco de dados

- Dump principal: `database/libraflow.sql`
- Banco esperado: `libraflow`
- Conexao recomendada: `app/config/conexao.php`
- Variaveis aceitas: `LIBRAFLOW_DB_HOST`, `LIBRAFLOW_DB_NAME`, `LIBRAFLOW_DB_USER`, `LIBRAFLOW_DB_PASS`

## Arquivos por pasta

### `app/config/`

- `conexao.php`: cria `$conn` como PDO para o banco `libraflow`. Use este arquivo em codigo novo.
- `auth.php`: funcoes de sessao, cookie "lembrar-me", token persistente e redirecionamento por tipo de usuario.
- `auth_check.php`: protecao de paginas logadas. Redireciona para login quando nao ha sessao valida.
- `email.php`: configuracao e envio de email com PHPMailer. Monta o link de redefinicao de senha.
- `email.local.example.php`: modelo de configuracao SMTP local.
- `email.local.php`: configuracao SMTP local real. Esta no `.gitignore`.

### `public/auth/login/`

- `login.php`: recebe email/senha, valida usuario, inicia sessao e redireciona admin/aluno.
- `styles.css`: estilo da tela de login.
- `animations.css`: animacoes da tela de login.
- `darkmode.js`: aplica e persiste tema claro/escuro.
- `imgs/`: logo e imagem principal usadas na tela.

### `public/auth/cadastro/`

- `register.php`: cadastra usuario comum na tabela `usuarios`.
- `styles.css`: estilo da tela de cadastro.
- `animations.css`: animacoes da tela de cadastro.
- `BTNdark.js`: script antigo de botao de tema.
- `darkmode.js`: script atual de tema.
- `imgs/`: logo e imagem principal usadas na tela.

### `public/auth/senha/`

- `esqueceu-a-senha.php`: formulario para solicitar recuperacao de senha.
- `redefinir-senha.php`: valida token e grava nova senha.
- `estilo.css`: estilo das telas de senha.
- `darkmode.js`: script de tema.

### `public/auth/`

- `logout.php`: encerra sessao, apaga cookie persistente e volta para o login.

### `public/catalogo/`

- `catalogo.php`: lista livros, busca por texto e filtra por categoria.
- `livro.php`: mostra detalhes de um livro.
- `solicitar.php`: cria solicitacao/emprestimo para o usuario logado.
- `meus_emprestimos.php`: lista emprestimos do usuario.
- `style.css`: estilos do catalogo e paginas relacionadas.
- `darkmode.js`: script de tema.
- `conexao.php`: conexao MySQLi antiga. Mantida por compatibilidade; prefira PDO de `app/config/conexao.php`.
- `capas/`: imagens de capa dos livros.
- `imgs/`: logo e imagem principal do modulo.

### `public/admin/`

- `Admin.php`: dashboard inicial do administrador.
- `cadastrar_livro.php`: cadastro de livro e upload de capa.
- `editar_livro.php`: edicao de livro existente.
- `listar_livros.php`: listagem administrativa de livros.
- `emprestimos.php`: acompanhamento e baixa de emprestimos.
- `novo_emprestimo.php`: cria emprestimo manual.
- `cadastro_rapido_aluno.php`: cadastra aluno durante o fluxo administrativo.
- `usuarios.php`: lista, edita e remove usuarios.
- `visitas.php`: registra visitas da biblioteca.
- `relatorios.php`: tela antiga de relatorios.
- `gerar_relatorio.php`: gerador antigo de PDF/Excel.
- `conexao.php`: conexao administrativa legada.
- `style.css`: estilos da area administrativa.
- `darkmode-btn.css`: estilo do botao de tema usado por varios modulos.
- `darkmode.js`: script de tema.
- `imgs/`: logo do admin.

### `public/admin/relatorios/`

- `index.php`: tela atual de relatorios.
- `gerar_relatorio.php`: exporta relatorios em PDF/XLSX.
- `RelatorioService.php`: centraliza consultas SQL dos relatorios.

### `public/usuario/`

- `index.php`: tela inicial do usuario comum.
- `index.html`: redirecionamento antigo para `index.php`.
- `styles.css`: estilos da tela do usuario.
- `darkmode.js`: script de tema.
- `imgs/`: logo da area do usuario.

### `database/`

- `libraflow.sql`: schema e dados iniciais do banco.

### `docs/`

- `README.md`: indice curto da documentacao.
- `guia-estilizacao/GuiadeCores-LibraFlow.md`: guia visual de cores e temas.

## Regras praticas para editar

- Para nova pagina protegida, comece com `require $_SERVER['DOCUMENT_ROOT'] . '/LibraFlow/app/config/auth_check.php';`.
- Para acessar banco, use `require $_SERVER['DOCUMENT_ROOT'] . '/LibraFlow/app/config/conexao.php';` e depois `$conn`.
- Para caminho no navegador, use `/LibraFlow/public/...`.
- Nao edite `vendor/`; rode Composer quando precisar atualizar dependencia.
- Nao coloque senha real em arquivo versionado. Use `app/config/email.local.php` para SMTP local.
## Guia detalhado de pastas

Para ver apenas o mapa de pastas, arquivos e funcionalidades, consulte docs/GUIA_DE_PASTAS_E_ARQUIVOS.md.
