# HANDOFF.md — LibraFlow

**Última atualização:** 2026-05-30  
**Status:** 🚧 Em desenvolvimento  
**Tipo de projeto:** Sistema PHP de gestão de livros (biblioteca digital)

---

## 📋 Visão Geral

O **LibraFlow** é um sistema web de gestão de livros digitais desenvolvido em PHP com MySQL. O sistema permite que usuários se cadastrem, façam login e gerenciem empréstimos de livros. Possui duas interfaces distintas: uma para usuários comuns e outra para administradores.

### Características Principais
- **Autenticação completa** (login, cadastro, recuperação de senha)
- **Sistema de permissões** (Admin vs Usuário comum)
- **Interface responsiva** com suporte a tema claro/escuro
- **Dashboard administrativo** para gestão do sistema
- **Catálogo de livros** com visualização pública
- **Email integration** via PHPMailer para recuperação de senha

---

## 🗂️ Estrutura do Projeto

```
LibraFlow/
├── cadastros_e_logins/          # Módulo de autenticação
│   ├── login/
│   │   └── arquivos/
│   │       ├── login.php         # Página de login (com HTML embutido)
│   │       ├── styles.css        # Estilos do login
│   │       ├── animations.css    # Animações
│   │       └── index.html        # Não utilizado
│   │   └── imgs/                  # Imagens do login
│   ├── cadastro/
│   │   └── arquivos/
│   │       ├── register.php      # Página de cadastro (com HTML embutido)
│   │       ├── styles.css        # Estilos do cadastro
│   │       ├── animations.css    # Animações
│   │       ├── BTNdark.js        # Script de tema escuro
│   │   └── imgs/                  # Imagens do cadastro
│   ├── logout/
│   │   └── logout.php            # Script de logout (redireciona para login)
│   ├── esqueceu_a_senha/
│   │   ├── esqueceu-a-senha.php  # Formulário de recuperação
│   │   ├── redefinir-senha.php   # Redefinição com token
│   │   └── estilo.css            # Estilos específicos
│   └── configs/
│       ├── conexao.php           # Conexão PDO centralizada
│       └── auth_check.php        # Middleware de autenticação
│
├── tela_Admin/                   # Interface administrativa
│   ├── arquivos/
│   │   ├── Admin.html            # Dashboard principal
│   │   ├── cadastros.livros.html # Formulário de cadastro de livros
│   │   ├── conexao.php          # Conexão (código misto - criar/insert)
│   │   └── style.css             # Estilos do admin
│   └── imgs/                      # Imagens do admin
│
├── Tela_de_usuario/              # Interface do usuário
│   ├── arquivos/
│   │   ├── index.html            # Dashboard do usuário
│   │   └── styles.css            # Estilos do usuário
│   └── imgs/                      # Imagens do usuário
│
├── catalogo/                     # Catálogo público de livros
│   ├── index.html                # Página do catálogo
│   ├── catalogo.php              # Listagem de livros (não implementado)
│   ├── conexao.php                # Conexão MySQLi (depreciado)
│   └── style.css                 # Estilos do catálogo
│
├── Guia_de_estilização/
│   └── GuiadeCores-LibraFlow.md  # Documentação completa de cores/temas
│
├── vendor/                       # Dependências Composer
│
├── composer.json                 # Dependências (PHPMailer)
├── composer.lock
└── README.md                     # Documentação básica
```

---

## 🗄️ Banco de Dados

### Conexão
- **SGBD:** MySQL
- **Banco:** `libraflow` (ou `libra_flow` - há inconsistência)
- **Charset:** `utf8mb4`
- **Usuário:** `root`
- **Senha:** vazio (desenvolvimento local)

**⚠️ ATENÇÃO:** Existem DOIS arquivos de conexão diferentes:
1. `cadastros_e_logins/configs/conexao.php` → PDO (padrão recomendado)
2. `tela_Admin/arquivos/conexao.php` → PDO com código misto
3. `catalogo/conexao.php` → MySQLi (depreciado, migrar para PDO)

### Tabelas Implementadas

#### `usuarios`
Autenticação e controle de acesso.

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id` | INT (PK, AI) | Identificador único |
| `nome` | VARCHAR | Nome completo do usuário |
| `email` | VARCHAR (UNIQUE) | E-mail de login |
| `senha` | VARCHAR | Hash (PASSWORD_BCRYPT) |
| `tipo` | CHAR(1) | 'D' = Admin, 'A' = Usuário comum |
| `token_recuperacao` | VARCHAR | Token para reset de senha |
| `token_expiracao` | DATETIME | Expiração do token |

**Índices:**
- PRIMARY KEY (`id`)
- UNIQUE KEY `email` (`email`)

#### `livros`
Cadastro de livros do sistema.

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id` | INT (PK, AI) | Identificador único |
| `titulo` | VARCHAR | Título do livro |
| `subtitulo` | VARCHAR | Subtítulo (opcional) |
| `autor` | VARCHAR | Autor do livro |
| `ano` | INT | Ano de publicação |
| `descricao` | TEXT | Descrição/sinopse |

**⚠️ PENDENTE:** Implementar campos para:
- Capa/foto do livro
- Status (disponível/empréstimo)
- Categoria/gênero
- ISBN

---

## 🔐 Sistema de Autenticação

### Fluxo de Login
```
login.php → Valida email/senha → Cria sessão → Redireciona:
  ├─ tipo 'D' → tela_Admin/arquivos/Admin.html
  └─ tipo 'A' → Tela_de_usuario/arquivos/index.html
```

### Variáveis de Sessão
Após login válido, são criadas:
- `$_SESSION['usuario_id']` - ID do usuário
- `$_SESSION['usuario_nome']` - Nome do usuário
- `$_SESSION['usuario_email']` - E-mail
- `$_SESSION['usuario_tipo']` - 'D' (Admin) ou 'A' (Usuário)

### Middleware de Autenticação
Usar `auth_check.php` para proteger páginas:

```php
require '../../configs/auth_check.php';  // Redireciona se não autenticado
```

### Recuperação de Senha
1. Usuário solicita reset em `esqueceu-a-senha.php`
2. Sistema gera token aleatório e envia por email
3. Usuário acessa link com token
4. `redefinir-senha.php` valida token e permite nova senha

---

## 🎨 Sistema de Design

### Cores e Temas
Documentação completa em: `Guia_de_estilização/GuiadeCores-LibraFlow.md`

#### Tema Claro
- Fundo: `#FFFFFF`
- Cards: `#F5F5F0`
- Títulos: `#283618`
- Links: `#BC6C25`
- Botões: `#DDA15E` (fundo), `#FEFAE0` (texto)

#### Tema Escuro
- Fundo: `#1C2410`
- Cards: `#243015`
- Títulos: `#D4E8B0`
- Links: `#A8C97F`
- Botões: `#4A6020` (fundo), `#D4E8B0` (texto)

### Tipografia
- **Títulos:** `Lora` (serifada, elegante)
- **Corpo:** `Source Sans 3` (sans-serif, legível)

### Implementação do Tema Escuro
- Arquivo: `cadastros_e_logins/cadastro/arquivos/BTNdark.js`
- Classe CSS: `body.dark` ativa o tema escuro
- Botão com ícone ☀️/🌙 alterna entre temas

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

**Uso:** Envio de emails para recuperação de senha

### Dependências Externas
- **Font Awesome** (CDN): Ícones da interface
- **Google Fonts:** Lora e Source Sans 3

---

## ✅ Funcionalidades Implementadas

### Módulo de Autenticação ✅
- [x] Login com validação de email/senha
- [x] Cadastro de novos usuários
- [x] Redirecionamento por tipo de usuário
- [x] Logout com destruição completa de sessão
- [x] Recuperação de senha com token
- [x] Middleware de verificação de autenticação
- [x] Hash de senhas com PASSWORD_BCRYPT
- [x] Proteção contra SQL injection (PDO prepared statements)

### Interface do Usuário ✅
- [x] Dashboard básico
- [x] Navegação principal
- [x] Placeholder para estatísticas (lidos, comigo, pendências)
- [x] Placeholder para livros recentes
- [x] Área para foto do usuário

### Interface Administrativa ✅
- [x] Dashboard com cards de resumo
- [x] Menu lateral com navegação
- [x] Área para notificações
- [x] Tabela de histórico de atividades
- [x] Placeholder para gráficos

### Catálogo ✅
- [x] Estrutura HTML básica
- [x] Estilos aplicados
- [ ] Listagem de livros do banco (PENDENTE)

### Sistema de Design ✅
- [x] Guia completo de cores e temas
- [x] Implementação de tema claro/escuro
- [x] Animações CSS
- [x] Responsividade básica

---

## 🚧 Funcionalidades Pendentes

### Alta Prioridade
- [ ] **Unificar conexões de banco** - Migrar tudo para PDO via `configs/conexao.php`
- [ ] **Implementar listagem de livros** no catálogo (catalogo.php)
- [ ] **CRUD completo de livros** no admin
- [ ] **Sistema de empréstimo** (regar/nova/devolução)
- [ ] **Dashboard com dados reais** (substituir placeholders)

### Média Prioridade
- [ ] Upload de capa de livros
- [ ] Sistema de notificações
- [ ] Gráficos de estatísticas no admin
- [ ] Perfil do usuário (editar dados)
- [ ] Histórico de empréstimos do usuário

### Baixa Prioridade
- [ ] Login social (Google) - apenas placeholder
- [ ] Página "Sobre nós"
- [ ] Página "Contato"
- [ ] API REST para integração mobile
- [ ] Sistema de avaliações de livros

---

## ⚠️ Problemas Conhecidos

### Inconsistências de Banco de Dados
1. **Nome do banco:** `libraflow` vs `libra_flow` - padronizar para `libraflow`
2. **Tipo de conexão:** MySQLi vs PDO - migrar tudo para PDO
3. **Localização da conexão:** 3 arquivos diferentes - usar apenas `configs/conexao.php`

### Segurança
- [ ] Implementar CSRF tokens em formulários
- [ ] Adicionar rate limiting no login
- [ ] Validar token de expiração mais rigorosamente
- [ ] Implementar proteção contra brute force

### Código
- `tela_Admin/arquivos/conexao.php` mistura HTML/PHP - separar
- `catalogo/conexao.php` usa MySQLi depreciado
- Falta padronização de nomes de arquivos (kebab-case vs camelCase)

---

## 🔧 Configuração de Ambiente

### Requisitos
- PHP 7.4+
- MySQL 5.7+ / MariaDB 10.3+
- XAMPP (ou servidor web compatível)
- Composer (para dependências)

### Instalação
```bash
# Clonar projeto
cd C:\xampp\htdocs\LibraFlow

# Instalar dependências
composer install

# Criar banco de dados
mysql -u root -e "CREATE DATABASE libraflow CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"

# Criar tabela usuarios (SQL)
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    senha VARCHAR(255) NOT NULL,
    tipo CHAR(1) DEFAULT 'A',
    token_recuperacao VARCHAR(255) NULL,
    token_expiracao DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

# Criar tabela livros (SQL)
CREATE TABLE livros (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    subtitulo VARCHAR(255) NULL,
    autor VARCHAR(255) NOT NULL,
    ano INT NULL,
    descricao TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Configurar Email (PHPMailer)
```php
// Adicionar em esqueceu_a_senha.php
$mail->isSMTP();
$mail->Host = 'smtp.gmail.com';
$mail->SMTPAuth = true;
$mail->Username = 'seu-email@gmail.com';
$mail->Password = 'sua-senha-de-app';
$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
$mail->Port = 587;
```

---

## 📝 Histórico de Commits Recentes

```
79a8b36 estruturando o login e cadastro
b105ffe Emplementação do sistema inicial de cadastro de livros
d0f824d Configuração do modo Dark e adição de um guia de cores do site.
4241ca6 termino de estilização de tela inicial do admin
443f825 iniciação da estilização do dashbord
efdfaf1 Reestruturação de dashbord do administrador.
164de7e emplementação do php
4ffac1b Apenas testes
f50265b Finalização do HTML e CSS inicial da pagina catalogo e Esqueci a senha
8047709 Adição da seção catalogo
```

---

## 🎯 Próximos Passos Sugeridos

### Imediatos (Esta Sessão)
1. **Unificar conexões de banco** para `configs/conexao.php`
2. **Implementar listagem de livros** no catálogo (catalogo.php)
3. **Testar fluxo completo** de cadastro → login → acesso

### Curto Prazo (Próximas Sessões)
1. CRUD completo de livros no admin
2. Sistema de empréstimo/devolução
3. Dashboard com dados reais do banco

### Médio Prazo
1. Sistema de notificações
2. Upload de capas de livros
3. Histórico de empréstimos por usuário

---

## 👥 Tipos de Usuário

| Tipo | Código | Acesso | Permissões |
|------|--------|--------|------------|
| **Admin** | 'D' | Dashboard administrativo | CRUD livros, gerenciar usuários, ver relatórios |
| **Aluno/Comum** | 'A' | Dashboard do usuário | Ver catálogo, solicitar empréstimo, ver histórico |

---

## 📄 Arquivos Principais

### Autenticação
- `cadastros_e_logins/login/arquivos/login.php` - Página de login
- `cadastros_e_logins/cadastro/arquivos/register.php` - Página de cadastro
- `cadastros_e_logins/configs/conexao.php` - Conexão PDO (USAR ESTE)
- `cadastros_e_logins/configs/auth_check.php` - Middleware de autenticação
- `cadastros_e_logins/logout/logout.php` - Logout

### Interface
- `tela_Admin/arquivos/Admin.html` - Dashboard admin
- `Tela_de_usuario/arquivos/index.html` - Dashboard usuário
- `catalogo/index.html` - Catálogo público

### Estilos
- `Guia_de_estilização/GuiadeCores-LibraFlow.md` - Documentação de cores
- `cadastros_e_logins/cadastro/arquivos/BTNdark.js` - Script tema escuro

---

## 🔗 Links Úteis

- **Diretório local:** `C:\xampp\htdocs\LibraFlow`
- **URL local:** `http://localhost/LibraFlow`
- **Login:** `http://localhost/LibraFlow/cadastros_e_logins/login/arquivos/login.php`
- **Admin:** `http://localhost/LibraFlow/tela_Admin/arquivos/Admin.html`
- **Usuário:** `http://localhost/LibraFlow/Tela_de_usuario/arquivos/index.html`

---

## 📞 Suporte

Em caso de dúvidas sobre o código ou funcionamento, consultar:
1. Este arquivo `HANDOFF.md`
2. `Guia_de_estilização/GuiadeCores-LibraFlow.md` para questões de design
3. `README.md` para visão geral (básico)

---

**Fim do HANDOFF.md** — Atualizar este arquivo ao finalizar cada sessão de trabalho.
