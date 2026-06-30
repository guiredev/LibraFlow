<?php
/*
 * MAPA RAPIDO DO ARQUIVO
 * Local: public/auth/cadastro/register.php
 * Funcao: Tela de cadastro de conta LibraFlow. Insere novo usuario na tabela usuarios.
 */
// public/auth/cadastro/register.php

session_start();
require $_SERVER['DOCUMENT_ROOT'] . '/LibraFlow/app/config/conexao.php';
require $_SERVER['DOCUMENT_ROOT'] . '/LibraFlow/app/config/auth.php';

if (isset($_SESSION['usuario_id'])) {
    header('Location: /LibraFlow/public/usuario/index.php');
    exit;
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome     = trim($_POST['nome']              ?? '');
    $email    = trim($_POST['email']             ?? '');
    $telefone = trim($_POST['telefone']          ?? '');
    $senha    = $_POST['senha']                  ?? '';
    $conf     = $_POST['confirmar_senha']        ?? '';

    if (!libraflowValidateCsrfToken($_POST['csrf_token'] ?? null)) {
        $erro = 'Sessao expirada. Recarregue a pagina e tente novamente.';
    } elseif (empty($nome) || empty($email) || empty($senha) || empty($conf)) {
        $erro = 'Preencha nome, e-mail e senha.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'E-mail invalido.';
    } elseif (strlen($senha) < 8) {
        $erro = 'A senha deve ter no minimo 8 caracteres.';
    } elseif ($senha !== $conf) {
        $erro = 'As senhas nao coincidem.';
    } else {
        $hash = password_hash($senha, PASSWORD_BCRYPT);

        $stmt = $conn->prepare(
            "INSERT INTO usuarios (nome, email, telefone, senha, tipo)
             VALUES (?, ?, ?, ?, 'A')"
        );

        try {
            $stmt->execute([$nome, $email, $telefone ?: null, $hash]);
            header('Location: /LibraFlow/public/auth/login/login.php?cadastro=ok');
            exit;
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $erro = 'Este e-mail ja esta cadastrado.';
            } else {
                $erro = 'Erro ao cadastrar. Tente novamente em alguns instantes.';
            }
        }
    }
}

$csrfToken = libraflowCsrfToken();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./styles.css">
    <link rel="stylesheet" href="./animations.css">
    <title>Cadastro | LibraFlow</title>
    <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400..700;1,400..700&family=Source+Sans+3:ital,wght@0,200..900;1,200..900&display=swap" rel="stylesheet">
    <style>
        body { min-height: 100vh; height: auto; }
        main { min-height: 80vh; height: auto; align-items: flex-start; padding-top: 2rem; }
        .form-main { height: auto; }
        .alerta {
            width: 30rem;
            padding: 1rem 1.5rem;
            border-radius: 1rem;
            font-size: 1.3rem;
            font-family: 'Source Sans 3', sans-serif;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        .alerta-erro { background: #fff0f0; color: #8b0000; border: 1px solid #f5c6c6; }
    </style>
    <link rel="stylesheet" href="/LibraFlow/public/admin/darkmode-btn.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <div class="conteiner-main">
        <nav>
            <div class="links-nav-login">
                <ul>
                    <li><a href="/LibraFlow/public/auth/login/login.php">Início</a></li>
                    <li><a href="#">Sobre nós</a></li>
                    <li><a href="#">Contato</a></li>
                </ul>
            </div>
            <div class="login btn">
                <a href="/LibraFlow/public/auth/login/login.php">
                    <button id="btn-log" type="button">Entrar</button>
                </a>
            </div>
        </nav>

        <main>
            <div class="text-main">
                <h1>Bem-vindo ao LibraFlow</h1>
                <p>Sua porta de entrada para um mundo de livros digitais!</p>
            </div>

            <div class="img-main">
                <img src="imgs/img-main.png" alt="imagem de livros">
            </div>

            <div class="form-main">
                <h2>Crie sua conta LibraFlow</h2>
                <p class="form-description">Depois você poderá entrar em uma escola, biblioteca, livraria ou outra organização por convite, código ou solicitação.</p>

                <?php if ($erro): ?>
                    <div class="alerta alerta-erro"><?= htmlspecialchars($erro) ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                    <input type="text" name="nome" placeholder="nome completo" value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>" required>
                    <input type="email" name="email" placeholder="e-mail" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                    <input type="tel" name="telefone" placeholder="telefone (opcional)" value="<?= htmlspecialchars($_POST['telefone'] ?? '') ?>">
                    <div class="password-field">
                        <input type="password" name="senha" placeholder="senha (min. 8 caracteres)" required>
                        <button type="button" class="password-toggle" aria-label="Mostrar senha">
                            <i class="fas fa-eye" aria-hidden="true"></i>
                        </button>
                    </div>
                    <div class="password-field">
                        <input type="password" name="confirmar_senha" placeholder="confirmar senha" required>
                        <button type="button" class="password-toggle" aria-label="Mostrar senha">
                            <i class="fas fa-eye" aria-hidden="true"></i>
                        </button>
                    </div>
                    <button type="submit">Cadastrar</button>
                </form>
            </div>
        </main>
    </div>

    <!-- Dark Mode Toggle -->
    <button id="themeToggle" class="theme-toggle-float" aria-label="Alternar tema claro/escuro">
        <span id="themeIcon"><i class="fas fa-moon" aria-hidden="true"></i></span>
        <span id="themeLabel">Escuro</span>
    </button>

    <script src="/LibraFlow/public/auth/cadastro/darkmode.js"></script>
    <script>
        document.querySelectorAll('.password-toggle').forEach(function (button) {
            button.addEventListener('click', function () {
                const input = button.parentElement.querySelector('input');
                const isPassword = input.type === 'password';
                input.type = isPassword ? 'text' : 'password';
                button.setAttribute('aria-label', isPassword ? 'Ocultar senha' : 'Mostrar senha');
                button.innerHTML = isPassword
                    ? '<i class="fas fa-eye-slash" aria-hidden="true"></i>'
                    : '<i class="fas fa-eye" aria-hidden="true"></i>';
            });
        });
    </script>
</body>
</html>
