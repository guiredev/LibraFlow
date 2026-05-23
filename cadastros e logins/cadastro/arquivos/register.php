<?php
// cadastros e logins/cadastro/arquivos/register.php
// configs está em:  ../../configs/conexao.php

session_start();
require '../../configs/conexao.php';

// Se já estiver logado, redireciona
if (isset($_SESSION['usuario_id'])) {
    header('Location: /LibraFlow/Tela de usuario/index.html');
    exit;
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome  = trim($_POST['nome']           ?? '');
    $email = trim($_POST['email']          ?? '');
    $senha = $_POST['senha']               ?? '';
    $conf  = $_POST['confirmar_senha']     ?? '';

    if (empty($nome) || empty($email) || empty($senha) || empty($conf)) {
        $erro = 'Preencha todos os campos.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'E-mail inválido.';
    } elseif (strlen($senha) < 8) {
        $erro = 'A senha deve ter no mínimo 8 caracteres.';
    } elseif ($senha !== $conf) {
        $erro = 'As senhas não coincidem.';
    } else {
        $hash = password_hash($senha, PASSWORD_BCRYPT);

        $stmt = $conn->prepare(
            "INSERT INTO USUARIOS (NOME, EMAIL, SENHA, TIPO) VALUES (?, ?, ?, 'A')"
        );

        try {
            $stmt->execute([$nome, $email, $hash]);
            header('Location: /LibraFlow/cadastros e logins/login/arquivos/login.php?cadastro=ok');
            exit;
        } catch (PDOException $e) {
            if (str_contains($e->getMessage(), '-803')) {
                $erro = 'Este e-mail já está cadastrado.';
            } else {
                $erro = 'Erro ao cadastrar. Tente novamente.';
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
        .alerta {
            width: 25rem;
            padding: 1rem 1.5rem;
            border-radius: 1rem;
            font-size: 1.3rem;
            font-family: 'Source Sans 3', sans-serif;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        .alerta-erro { background: #fff0f0; color: #8b0000; border: 1px solid #f5c6c6; }
    </style>
</head>
<body>
    <div class="conteiner-main">
        <nav>
            <div class="links-nav-login">
                <ul>
                    <li><a href="/LibraFlow/Tela de usuario/index.html">Início</a></li>
                    <li><a href="#">Sobre nós</a></li>
                    <li><a href="#">Contato</a></li>
                </ul>
            </div>
            <div class="login btn">
                <a href="/LibraFlow/cadastros e logins/login/arquivos/login.php">
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
                <img src="../../imgs/img-main.png" alt="imagem de livros">
            </div>

            <div class="form-main">
                <h2>Crie sua conta!</h2>

                <?php if ($erro): ?>
                    <div class="alerta alerta-erro"><?= htmlspecialchars($erro) ?></div>
                <?php endif; ?>

                <form method="POST" action="register.php">
                    <input
                        type="text"
                        name="nome"
                        placeholder="nome completo"
                        value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>"
                        required>

                    <input
                        type="email"
                        name="email"
                        placeholder="e-mail"
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                        required>

                    <input
                        type="password"
                        name="senha"
                        placeholder="senha (mín. 8 caracteres)"
                        required>

                    <input
                        type="password"
                        name="confirmar_senha"
                        placeholder="confirmar senha"
                        required>

                    <button type="submit">Cadastrar</button>
                </form>

                <div class="others-forms">
                    <p>Ou</p>
                    <a href="#"><p>Entrar com Google</p></a>
                </div>

                <div class="page" id="page">
                    <div class="theme-btn-wrapper">
                        <button class="theme-btn" id="themeBtn" aria-label="Ativar tema escuro">☀️</button>
                        <span class="theme-label" id="themeLabel">Claro</span>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="BTNdark.js" defer></script>
</body>
</html>
