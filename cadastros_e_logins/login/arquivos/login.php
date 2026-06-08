<?php
// cadastros_e_logins/login/arquivos/login.php

session_start();
require '../../configs/conexao.php';

if (isset($_SESSION['usuario_id'])) {
    if ($_SESSION['usuario_tipo'] === 'D') {
        header('Location: /LibraFlow/tela_Admin/arquivos/Admin.php');
    } else {
        header('Location: /LibraFlow/Tela_de_usuario/arquivos/index.php');
    }
    exit;
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha']       ?? '';

    if (empty($email) || empty($senha)) {
        $erro = 'Preencha e-mail e senha.';
    } else {
        $stmt = $conn->prepare("SELECT * FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch();

        if ($usuario && password_verify($senha, $usuario['senha'])) {
            session_regenerate_id(true);

            $_SESSION['usuario_id']    = $usuario['id'];
            $_SESSION['usuario_nome']  = $usuario['nome'];
            $_SESSION['usuario_email'] = $usuario['email'];
            $_SESSION['usuario_tipo']  = $usuario['tipo'];

            if ($usuario['tipo'] === 'D') {
                header('Location: /LibraFlow/tela_Admin/arquivos/Admin.php');
            } else {
                header('Location: /LibraFlow/Tela_de_usuario/arquivos/index.php');
            }
            exit;
        } else {
            $erro = 'E-mail ou senha incorretos.';
        }
    }
}

$cadastroOk = isset($_GET['cadastro']) && $_GET['cadastro'] === 'ok';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./styles.css">
    <link rel="stylesheet" href="./animations.css">
    <link rel="shortcut icon" href="../../imgs/Logo-LibraFlow.png" type="image/x-icon">
    <title>Bem-vindo | LibraFlow</title>
    <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400..700;1,400..700&family=Source+Sans+3:ital,wght@0,200..900;1,200..900&display=swap" rel="stylesheet">
    <style>
        .alerta {
            width: 25rem;
            padding: 1rem 1.5rem;
            border-radius: 1rem;
            font-size: 1.3rem;
            font-family: 'Source Sans 3', sans-serif;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        .alerta-erro    { background: #fff0f0; color: #8b0000; border: 1px solid #f5c6c6; }
        .alerta-sucesso { background: #f0fdf4; color: #1a4d2e; border: 1px solid #bbf7d0; }

        /* Dark Mode Toggle Button */
        .theme-toggle-wrapper {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            z-index: 1000;
        }

        .theme-toggle-btn {
            width: 5.5rem;
            height: 5.5rem;
            border-radius: 50%;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            transition: all 0.3s ease;
            background: #DDA15E;
        }

        .theme-toggle-btn:hover {
            transform: scale(1.1) rotate(20deg);
            box-shadow: 0 6px 20px rgba(0,0,0,0.25);
        }

        body.dark .theme-toggle-btn {
            background: #4A6020;
        }

        @media (max-width: 768px) {
            .theme-toggle-wrapper {
                bottom: 1.5rem;
                right: 1.5rem;
            }

            .theme-toggle-btn {
                width: 4.5rem;
                height: 4.5rem;
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="conteiner-main">
        <nav>
            <div class="links-nav-login">
                <ul>
                    <li><a href="/LibraFlow/Tela_de_usuario/arquivos/catalogo.php">Início</a></li>
                    <li><a href="#">Sobre nós</a></li>
                    <li><a href="#">Contato</a></li>
                </ul>
            </div>
            <div class="login btn">
                <a href="/LibraFlow/cadastros_e_logins/cadastro/arquivos/register.php">
                    <button id="btn.log">Cadastrar</button>
                </a>
            </div>
        </nav>

        <main>
            <div class="text-main">
                <h1>Bem-vindo ao LibraFlow</h1>
                <p>Sua porta de entrada para um mundo de livros digitais!</p>
            </div>

            <div class="img-main">
                <img src="../imgs/img-main.png" alt="imagem de livros">
            </div>

            <div class="form-main">
                <h2>Faça login na sua conta</h2>

                <?php if ($cadastroOk): ?>
                    <div class="alerta alerta-sucesso">Cadastro realizado! Faça seu login.</div>
                <?php endif; ?>

                <?php if ($erro): ?>
                    <div class="alerta alerta-erro"><?= htmlspecialchars($erro) ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <input
                        type="email"
                        name="email"
                        placeholder="e-mail"
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                        required>

                    <input
                        type="password"
                        name="senha"
                        placeholder="senha"
                        required>

                    <span>
                        <a href="/LibraFlow/cadastros_e_logins/esqueceu_a_senha/esqueceu-a-senha.php">
                            Esqueceu a senha?
                        </a>
                    </span>

                    <button type="submit">Entrar</button>
                </form>

                <div class="others-forms">
                    <p>Ou</p>
                    <a href="#"><p>Entrar com Google</p></a>
                </div>
            </div>
        </main>
    </div>

    <!-- Dark Mode Toggle -->
    <div class="theme-toggle-wrapper">
        <button id="themeToggle" class="theme-toggle-btn" aria-label="Alternar tema claro/escuro">
            <span id="themeIcon">🌙</span>
        </button>
    </div>

    <script>
        // Dark Mode Toggle - Versão inline para login
        (function() {
            'use strict';

            const CONFIG = {
                storageKey: 'libraflow_theme',
                darkClass: 'dark'
            };

            const ICONS = { light: '🌙', dark: '☀️' };

            function getSavedTheme() {
                const saved = localStorage.getItem(CONFIG.storageKey);
                if (saved) return saved;
                if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                    return 'dark';
                }
                return 'light';
            }

            function saveTheme(theme) {
                localStorage.setItem(CONFIG.storageKey, theme);
            }

            function applyTheme(theme) {
                const body = document.body;
                const icon = document.getElementById('themeIcon');
                if (theme === 'dark') {
                    body.classList.add(CONFIG.darkClass);
                    if (icon) icon.textContent = ICONS.dark;
                } else {
                    body.classList.remove(CONFIG.darkClass);
                    if (icon) icon.textContent = ICONS.light;
                }
            }

            function toggleTheme() {
                const body = document.body;
                const currentTheme = body.classList.contains(CONFIG.darkClass) ? 'dark' : 'light';
                const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                applyTheme(newTheme);
                saveTheme(newTheme);
            }

            const button = document.getElementById('themeToggle');
            if (button) {
                button.addEventListener('click', toggleTheme);
                button.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        toggleTheme();
                    }
                });
            }

            applyTheme(getSavedTheme());
        })();
    </script>
</body>
</html>
