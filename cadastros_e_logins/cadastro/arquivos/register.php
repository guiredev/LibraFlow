<?php
// cadastros_e_logins/cadastro/arquivos/register.php

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require $_SERVER['DOCUMENT_ROOT'] . '/LibraFlow/cadastros_e_logins/configs/conexao.php';

if (isset($_SESSION['usuario_id'])) {
    header('Location: /LibraFlow/Tela_de_usuario/arquivos/index.php');
    exit;
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome     = trim($_POST['nome']              ?? '');
    $email    = trim($_POST['email']             ?? '');
    $telefone = trim($_POST['telefone']          ?? '');
    $rm       = trim($_POST['rm']                ?? '');
    $endereco = trim($_POST['endereco']          ?? '');
    $idade    = intval($_POST['idade']           ?? 0);
    $senha    = $_POST['senha']                  ?? '';
    $conf     = $_POST['confirmar_senha']        ?? '';

    if (empty($nome) || empty($email) || empty($telefone) || empty($rm) || empty($endereco) || empty($idade) || empty($senha) || empty($conf)) {
        $erro = 'Preencha todos os campos.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'E-mail invalido.';
    } elseif ($idade < 1 || $idade > 120) {
        $erro = 'Informe uma idade valida.';
    } elseif (strlen($senha) < 8) {
        $erro = 'A senha deve ter no minimo 8 caracteres.';
    } elseif ($senha !== $conf) {
        $erro = 'As senhas nao coincidem.';
    } else {
        $hash = password_hash($senha, PASSWORD_BCRYPT);

        $stmt = $conn->prepare(
            "INSERT INTO usuarios (nome, email, telefone, rm, endereco, idade, senha, tipo)
             VALUES (?, ?, ?, ?, ?, ?, ?, 'A')"
        );

        try {
            $stmt->execute([$nome, $email, $telefone, $rm, $endereco, $idade, $hash]);
            header('Location: /LibraFlow/cadastros_e_logins/login/arquivos/login.php?cadastro=ok');
            exit;
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $erro = 'Este e-mail ou RM ja esta cadastrado.';
            } else {
                $erro = 'Erro ao cadastrar. Verifique se os novos campos foram criados no banco.';
            }
        }
    }
}
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
        .form-main form {
            max-height: 58vh;
            overflow-y: auto;
            padding-right: 0.6rem;
        }
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
    <link rel="stylesheet" href="/LibraFlow/tela_Admin/arquivos/darkmode-btn.css">
    </style>
</head>
<body>
    <div class="conteiner-main">
        <nav>
            <div class="links-nav-login">
                <ul>
                    <li><a href="/LibraFlow/Tela_de_usuario/arquivos/index.php">Inicio</a></li>
                    <li><a href="#">Sobre nos</a></li>
                    <li><a href="#">Contato</a></li>
                </ul>
            </div>
            <div class="login btn">
                <a href="/LibraFlow/cadastros_e_logins/login/arquivos/login.php">
                    <button id="btn.log">Entrar</button>
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
                <h2>Crie sua conta!</h2>

                <?php if ($erro): ?>
                    <div class="alerta alerta-erro"><?= htmlspecialchars($erro) ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <input type="text" name="nome" placeholder="nome completo" value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>" required>
                    <input type="email" name="email" placeholder="e-mail" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                    <input type="tel" name="telefone" placeholder="telefone" value="<?= htmlspecialchars($_POST['telefone'] ?? '') ?>" required>
                    <input type="text" name="rm" placeholder="RM" value="<?= htmlspecialchars($_POST['rm'] ?? '') ?>" required>
                    <input type="text" name="endereco" placeholder="endereco" value="<?= htmlspecialchars($_POST['endereco'] ?? '') ?>" required>
                    <input type="number" name="idade" placeholder="idade" min="1" max="120" value="<?= htmlspecialchars($_POST['idade'] ?? '') ?>" required>
                    <input type="password" name="senha" placeholder="senha (min. 8 caracteres)" required>
                    <input type="password" name="confirmar_senha" placeholder="confirmar senha" required>
                    <button type="submit">Cadastrar</button>
                </form>

                <div class="others-forms">
                    <p>Ou</p>
                    <a href="#"><p>Entrar com Google</p></a>
                </div>

                <div class="page" id="page">
                </div>
            </div>
        </main>
    </div>

    <!-- Dark Mode Toggle -->
    <button id="themeToggle" class="theme-toggle-float" aria-label="Alternar tema claro/escuro">
        <span id="themeIcon">🌙</span>
        <span id="themeLabel">Escuro</span>
    </button>

    <script src="/LibraFlow/cadastros_e_logins/cadastro/arquivos/darkmode.js"></script>
</body>
</html>
