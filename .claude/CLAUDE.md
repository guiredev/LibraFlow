# CLAUDE.md

Este arquivo fornece orientação para o Claude Code (claude.ai/code) ao trabalhar neste repositório.

---

## 📋 Visão Geral

**LibraFlow** é um sistema web de gestão de bibliotecas desenvolvido em **PHP puro** com **MySQL**. O sistema permite gerenciamento completo de livros, usuários e empréstimos, com duas interfaces distintas: administrativa e de usuário.

**Stack Tecnológico:**
- PHP 7.4+ (puro, sem framework)
- MySQL/MariaDB com PDO
- JavaScript puro (vanilla)
- CSS puro com suporte a tema claro/escuro
- PHPMailer para envio de emails

---

## 🏗️ Arquitetura do Sistema

### Camada de Autenticação e Autorização

**Middleware Central:** `cadastros_e_logins/configs/auth_check.php`

```php
require $_SERVER['DOCUMENT_ROOT'] . '/LibraFlow/cadastros_e_logins/configs/auth_check.php';

// Redireciona automaticamente se não autenticado
// Verifica $_SESSION['usuario_tipo'] para permissões
```

**Variáveis de Sessão:**
- `$_SESSION['usuario_id']` - ID do usuário
- `$_SESSION['usuario_nome']` - Nome do usuário
- `$_SESSION['usuario_email']` - E-mail
- `$_SESSION['usuario_tipo']` - 'D' (Admin) ou 'A' (Aluno/Comum)

### Conexão com Banco de Dados

**Conexão Padrão (PDO):** `cadastros_e_logins/configs/conexao.php`

```php
$conn = new PDO(
    'mysql:host=localhost;dbname=libraflow;charset=utf8mb4',
    'root',
    '',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
```

**⚠️ IMPORTANTE:** Sempre usar `cadastros_e_logins/configs/conexao.php` como conexão padrão. Outros arquivos de conexão estão depreciados.

### Padrão de Endpoints AJAX

Para modais e seleções dinâmicas, usar padrão:

```php
// Detectar requisição AJAX
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    // Retornar JSON
    echo json_encode($dados);
    exit;
}
```

**Exemplos implementados:**
- `listar_livros.php?ajax=1` - Retorna livros disponíveis
- `usuarios.php?ajax=1` - Retorna alunos (tipo 'A')
- `emprestimos.php?ajax=1` - Processa empréstimo via POST

---

## 🗄️ Banco de Dados

### Tabelas Principais

**`usuarios`** - Autenticação e controle de acesso
- `id` (PK, AI), `nome`, `email` (UNIQUE), `senha` (HASH)
- `tipo` - 'D' (Admin) ou 'A' (Aluno)
- `telefone`, `rm`, `endereco`, `idade` - Informações adicionais
- `token_recuperacao`, `token_expiracao` - Reset de senha

**`livros`** - Cadastro de livros
- `id` (PK, AI), `titulo`, `subtitulo`, `autor`, `ano`
- `isbn`, `descricao`, `quantidade`, `capa`, `id_categoria`
- `criado_em` - Timestamp de cadastro

**`emprestimos`** - Controle de empréstimos
- `id` (PK, AI), `id_usuario` (FK), `id_livro` (FK)
- `data_emprestimo`, `data_prevista_devolucao`, `data_devolucao`
- `status` - 'A' (Ativo), 'D' (Devolvido), 'V' (Vencido)
- `criado_em` - Timestamp

**`categorias`** - Categorização de livros
- `id` (PK, AI), `nome` - Nome da categoria

**`visitas_biblioteca`** - Controle de visitas
- `id` (PK, AI), `data_registro`, `periodo`, `quantidade`
- `criado_em`, `atualizado_em` - Timestamps

**Índices importantes:**
- `emprestimos`: `(id_usuario, status)`, `(id_livro, status)`, `(status, data_prevista_devolucao)`

---

## 🔧 Comandos de Desenvolvimento

### Instalação de Dependências
```bash
cd C:\xampp\htdocs\LibraFlow
composer install
```

### Servidor Local (XAMPP)
```bash
# Iniciar Apache e MySQL via XAMPP Control Panel
# Acessar: http://localhost/LibraFlow
```

### Verificação de Estrutura
```bash
# Verificar arquivos PHP principais
ls -la tela_Admin/arquivos/*.php

# Verificar conexões de banco
ls -la cadastros_e_logins/configs/*.php

# Verificar scripts SQL
ls -la database/*.sql
```

---

## 🔄 Fluxos de Trabalho Principais

### Fluxo de Autenticação

```
login.php → Validar email/senha → Criar sessão → Redirecionar:
  ├─ tipo 'D' → tela_Admin/arquivos/Admin.php
  └─ tipo 'A' → Tela_de_usuario/arquivos/index.php
```

### Fluxo de Empréstimo Administrativo (Implementado)

**Tela:** `tela_Admin/arquivos/emprestimos.php`

```
┌─────────────────────────────────────────────────────────┐
│ 1. Admin clica "Fazer Empréstimo"                       │
│    → Abre modal de empréstimo                          │
├─────────────────────────────────────────────────────────┤
│ 2. Selecionar Livro                                     │
│    → Abre modal com livros disponíveis (quantidade > 0)│
│    → AJAX: listar_livros.php?ajax=1                    │
│    → Usuário busca e seleciona                         │
├─────────────────────────────────────────────────────────┤
│ 3. Selecionar Aluno                                     │
│    → Abre modal com alunos (tipo 'A')                  │
│    → AJAX: usuarios.php?ajax=1                         │
│    → Ou "Cadastrar Novo Aluno" → cadastro_rapido_aluno.php│
├─────────────────────────────────────────────────────────┤
│ 4. Confirmar Empréstimo                                 │
│    → POST: emprestimos.php?ajax=1                      │
│    → Valida: livro disponível, aluno existe           │
│    → Valida: sem empréstimo duplicado                   │
│    → INSERT emprestimos + UPDATE livros.quantidade      │
└─────────────────────────────────────────────────────────┘
```

### Fluxo de Catálogo Público (Implementado)

**Telas:** `catalogo/catalogo.php`, `catalogo/livro.php`, `catalogo/solicitar.php`

```
┌─────────────────────────────────────────────────────────┐
│ 1. Usuário acessa catálogo público                      │
│    → Visualiza todos os livros disponíveis             │
│    → Filtra por categoria ou busca por título/autor   │
├─────────────────────────────────────────────────────────┤
│ 2. Clica em livro para detalhes                         │
│    → livro.php?id=X mostra informações completas      │
├─────────────────────────────────────────────────────────┤
│ 3. Solicita empréstimo                                  │
│    → solicitar.php?id_livro=X                         │
│    → Valida se usuário já tem empréstimo ativo         │
│    → Se válido: cria empréstimo e diminui quantidade   │
└─────────────────────────────────────────────────────────┘
```

---

## 🎨 Sistema de Design

### Temas (Claro/Escuro)

**Implementado via JavaScript:** `darkmode.js` ativa classe `body.dark`

**Cores Tema Claro:**
- Fundo: `#FFFFFF`
- Cards: `#F5F5F0`
- Títulos: `#283618`
- Links: `#BC6C25`
- Botões: `#DDA15E` → `#BC6C25` (hover)

**Cores Tema Escuro:**
- Fundo: `#1C2410`
- Cards: `#243015`
- Títulos: `#D4E8B0`
- Links: `#A8C97F`
- Botões: `#4A6020` → `#3A4E1E` (hover)

**CSS Variables** em `style.css` facilitam manutenção de temas.

### Tipografia
- **Títulos:** `Lora` (serifada, elegante)
- **Corpo:** `Source Sans 3` (sans-serif, legível)

---

## 📝 Padrões de Código

### PHP
- **Variáveis:** `$nomeVariavel` (camelCase)
- **Banco de dados:** Sempre usar prepared statements PDO
- **JSON:** `header('Content-Type: application/json')` antes de `echo json_encode()`
- **Exit:** Sempre `exit` após `echo json_encode()` em endpoints AJAX

### JavaScript
- **Vanilla:** Sem frameworks
- **Async/Await:** Para fetch API
- **Modais:** Usar classes CSS `.ativo` para mostrar/esconder

### CSS
- **Variáveis:** `--nome-variavel` (kebab-case)
- **Responsividade:** Mobile-first
- **Temas:** CSS variables em `:root` e `body.dark`

---

## 🔗 URLs Importantes

- **Local:** `http://localhost/LibraFlow`
- **Login Admin:** `http://localhost/LibraFlow/cadastros_e_logins/login/arquivos/login.php`
- **Dashboard Admin:** `http://localhost/LibraFlow/tela_Admin/arquivos/Admin.php`
- **Catálogo:** `http://localhost/LibraFlow/catalogo/catalogo.php`
- **Empréstimos:** `http://localhost/LibraFlow/tela_Admin/arquivos/emprestimos.php`

---

## 🎯 Funcionalidades Implementadas

### ✅ Completas
- **Autenticação completa** (login, cadastro, logout, recuperação de senha)
- **Sistema de permissões** (Admin vs Aluno)
- **Tema claro/escuro funcional** em todas as telas
- **CRUD completo de livros** (cadastrar, editar, listar, excluir)
- **CRUD completo de usuários** (listar, editar, excluir)
- **Sistema de empréstimo administrativo com modais**
- **Sistema de catálogo público funcional**
- **Solicitação de empréstimo por usuário comum**
- **Gestão de empréstimos** (registrar devolução, status)
- **Controle de visitas da biblioteca**
- **Upload de capas de livros**

### 🚧 Em Desenvolvimento
- Dashboard com dados reais (parcialmente implementado)
- Melhorias na responsividade de algumas telas

---

## 🗂️ Estrutura do Projeto

```
LibraFlow/
├── cadastros_e_logins/          # Autenticação e autorização
│   ├── configs/                 # Conexão PDO e middleware
│   ├── login/arquivos/          # Login
│   ├── cadastro/arquivos/       # Cadastro de usuários
│   ├── esqueceu_a_senha/        # Recuperação de senha
│   └── logout/                  # Logout
│
├── tela_Admin/arquivos/         # Interface administrativa
│   ├── Admin.php                # Dashboard admin
│   ├── emprestimos.php          # Gestão de empréstimos com modal
│   ├── listar_livros.php        # Listagem de livros (AJAX endpoint)
│   ├── usuarios.php             # Gestão de usuários (AJAX endpoint)
│   ├── cadastrar_livro.php     # CRUD de livros
│   ├── editar_livro.php         # Edição de livros
│   ├── visitas.php              # Controle de visitas
│   ├── cadastro_rapido_aluno.php # Cadastro rápido no modal
│   ├── novo_emprestimo.php       # Novo empréstimo via modal
│   ├── style.css                # Estilos globais (responsivo + dark mode)
│   └── darkmode.js              # Script de tema escuro
│
├── Tela_de_usuario/arquivos/    # Interface do usuário comum
│   └── index.php               # Dashboard do usuário
│
├── catalogo/                    # Catálogo público de livros
│   ├── catalogo.php             # Visualização de livros
│   ├── livro.php                # Detalhes do livro
│   ├── solicitar.php            # Solicitação de empréstimo
│   ├── meus_emprestimos.php     # Empréstimos do usuário
│   ├── capas/                   # Upload de capas de livros
│   ├── style.css                # Estilos do catálogo
│   └── darkmode.js              # Script de tema escuro
│
├── database/                    # Scripts SQL
│   ├── emprestimos.sql          # Tabela de empréstimos
│   └── cadastro_e_visitas.sql   # Tabelas auxiliares
│
├── Guia_de_estilização/         # Documentação de cores
│   └── GuiadeCores-LibraFlow.md # Guia completo de cores
│
└── vendor/                      # Dependências Composer (PHPMailer)
```

---

## 📦 Dependências

### Composer
```json
{
    "require": {
        "phpmailer/phpmailer": "^7.1"
    }
}
```

### Externas (CDN)
- **Font Awesome** - Ícones da interface
- **Google Fonts** - Lora (serifada), Source Sans 3 (sans-serif)

---

## 🔧 Debug e Testes

### PHP Errors
```php
// Em desenvolvimento, habilitar erros
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Verificar conexão
try {
    $conn = new PDO(...);
} catch (PDOException $e) {
    die("Erro na conexão: " . $e->getMessage());
}
```

### Console JavaScript
```javascript
// Verificar endpoints AJAX
fetch('listar_livros.php?ajax=1')
    .then(r => r.json())
    .then(d => console.log(d))
    .catch(e => console.error(e));
```

### Testar Empréstimo
1. Acessar `emprestimos.php`
2. Abrir DevTools (F12) → Network
3. Clicar "Fazer Empréstimo"
4. Verificar requisições AJAX em `listar_livros.php?ajax=1`
5. Verificar POST em `emprestimos.php?ajax=1`

---

## 📄 Arquivos Chave

### Autenticação
- `cadastros_e_logins/configs/conexao.php` - Conexão PDO padrão
- `cadastros_e_logins/configs/auth_check.php` - Middleware de autenticação
- `cadastros_e_logins/login/arquivos/login.php` - Login

### Interface Admin
- `tela_Admin/arquivos/emprestimos.php` - Sistema de empréstimos com modais
- `tela_Admin/arquivos/listar_livros.php` - Livros (com AJAX endpoint)
- `tela_Admin/arquivos/usuarios.php` - Usuários (com AJAX endpoint)
- `tela_Admin/arquivos/Admin.php` - Dashboard com dados reais
- `tela_Admin/arquivos/style.css` - Estilos globais responsivos

### Catálogo Público
- `catalogo/catalogo.php` - Listagem pública de livros
- `catalogo/livro.php` - Detalhes do livro
- `catalogo/solicitar.php` - Solicitação de empréstimo
- `catalogo/meus_emprestimos.php` - Empréstimos do usuário

### Estilo
- `Guia_de_estilização/GuiadeCores-LibraFlow.md` - Documentação de cores
- `tela_Admin/arquivos/darkmode.js` - Script de tema escuro

---

## ⚠️ Problemas Conhecidos e Inconsistências

### Banco de Dados
- **Nome do banco:** `libraflow` vs `libra_flow` - padronizar para `libraflow`
- **Conexões duplicadas:** 3 arquivos diferentes - usar apenas `configs/conexao.php`
- **Tipo de conexão:** Migrar tudo para PDO (MySQLi depreciado)

### Segurança
- [ ] Implementar CSRF tokens em formulários
- [ ] Adicionar rate limiting no login
- [ ] Validar token de expiração mais rigorosamente
- [ ] Implementar proteção contra brute force

### Código
- `conexao.php` mistura HTML/PHP - separar lógica
- Falta padronização de nomes (kebab-case vs camelCase)
- Modais precisam de melhor acessibilidade (ARIA, teclado)

---

## 🚀 Próximos Passos Sugeridos

### Imediatos
1. Unificar conexões de banco para `configs/conexao.php`
2. Adicionar CSRF tokens em formulários POST
3. Melhorar acessibilidade dos modais (ARIA, teclado)

### Curto Prazo
1. Implementar rate limiting no login
2. Adicionar validação mais rigorosa de tokens
3. Melhorar responsividade das telas de autenticação

### Médio Prazo
1. Sistema de notificações
2. Histórico de atividades detalhado
3. Relatórios administrativos avançados