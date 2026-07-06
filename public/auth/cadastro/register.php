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
        $erro = 'Sessão expirada. Recarregue a página e tente novamente.';
    } elseif (empty($nome) || empty($email) || empty($senha) || empty($conf)) {
        $erro = 'Preencha nome, e-mail e senha.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'E-mail inválido.';
    } elseif (strlen($senha) < 8) {
        $erro = 'A senha deve ter no mínimo 8 caracteres.';
    } elseif ($senha !== $conf) {
        $erro = 'As senhas não coincidem.';
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
                $erro = 'Este e-mail já está cadastrado.';
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
    <link rel="stylesheet" href="./styles.css?v=20260706-auth5">
    <link rel="stylesheet" href="./animations.css?v=20260706-auth5">
    <link rel="stylesheet" href="/LibraFlow/public/admin/darkmode-btn.css">
    <link rel="shortcut icon" href="imgs/Logo-LibraFlow.png" type="image/x-icon">
    <title>Cadastro | LibraFlow</title>
    <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400..700;1,400..700&family=Source+Sans+3:ital,wght@0,200..900;1,200..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <div class="conteiner-main">
        <nav class="auth-nav">
            <a class="brand-link" href="/LibraFlow/public/auth/login/login.php" aria-label="LibraFlow">
                <img src="imgs/Logo-LibraFlow.png" alt="" aria-hidden="true">
                <span>LibraFlow</span>
            </a>
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
            <section class="text-main" aria-labelledby="register-title">
                <span class="eyebrow">Conta de estudante</span>
                <h1>Comece com uma conta LibraFlow</h1>
                <p>Depois do cadastro, seu acesso fica pronto para catálogo, reservas, favoritos e acompanhamento de empréstimos.</p>
                <div class="auth-highlights" aria-label="Dados usados no cadastro">
                    <span><i class="fas fa-user-check" aria-hidden="true"></i> Identificação clara</span>
                    <span><i class="fas fa-envelope-circle-check" aria-hidden="true"></i> E-mail de acesso</span>
                    <span><i class="fas fa-shield-halved" aria-hidden="true"></i> Senha protegida</span>
                </div>
            </section>

            <div class="img-main">
                <img src="imgs/img-main.png" alt="imagem de livros">
            </div>

            <div class="form-main">
                <span class="form-kicker">Cadastro</span>
                <h2 id="register-title">Crie sua conta</h2>
                <p class="form-description">Use dados reais para facilitar reservas, avisos de prazo e atendimento da biblioteca.</p>

                <?php if ($erro): ?>
                    <div class="alerta alerta-erro"><?= htmlspecialchars($erro) ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                    <label class="field-group" for="nome">
                        <span>Nome completo</span>
                        <span class="field-control">
                            <i class="fas fa-user" aria-hidden="true"></i>
                            <input id="nome" type="text" name="nome" placeholder="Seu nome completo" autocomplete="name" value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>" required>
                        </span>
                    </label>

                    <label class="field-group" for="email">
                        <span>E-mail</span>
                        <span class="field-control">
                            <i class="fas fa-envelope" aria-hidden="true"></i>
                            <input id="email" type="email" name="email" placeholder="seu@email.com" autocomplete="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                        </span>
                    </label>

                    <label class="field-group" for="telefone">
                        <span>Telefone <small>opcional</small></span>
                        <span class="field-control">
                            <i class="fas fa-phone" aria-hidden="true"></i>
                            <input id="telefone" type="tel" name="telefone" placeholder="(00) 00000-0000" autocomplete="tel" value="<?= htmlspecialchars($_POST['telefone'] ?? '') ?>">
                        </span>
                    </label>

                    <label class="field-group" for="senha">
                        <span>Senha</span>
                        <span class="field-control password-field">
                            <i class="fas fa-lock" aria-hidden="true"></i>
                            <input id="senha" type="password" name="senha" placeholder="Mínimo de 8 caracteres" autocomplete="new-password" data-password-strength required>
                            <button type="button" class="password-toggle" aria-label="Mostrar senha">
                                <i class="fas fa-eye" aria-hidden="true"></i>
                            </button>
                        </span>
                    </label>

                    <div class="password-meter" aria-live="polite">
                        <span class="password-meter-bar"></span>
                        <small>Use letras, números e símbolos para uma senha mais forte.</small>
                    </div>

                    <label class="field-group" for="confirmar_senha">
                        <span>Confirmar senha</span>
                        <span class="field-control password-field">
                            <i class="fas fa-lock" aria-hidden="true"></i>
                            <input id="confirmar_senha" type="password" name="confirmar_senha" placeholder="Repita a senha" autocomplete="new-password" required>
                            <button type="button" class="password-toggle" aria-label="Mostrar senha">
                                <i class="fas fa-eye" aria-hidden="true"></i>
                            </button>
                        </span>
                    </label>

                    <button type="submit" class="primary-action">Cadastrar</button>
                </form>
            </div>
        </main>
    </div>

    <button id="themeToggle" class="theme-toggle-float" aria-label="Alternar tema claro/escuro">
        <span id="themeIcon"><i class="fas fa-moon" aria-hidden="true"></i></span>
        <span id="themeLabel">Escuro</span>
    </button>

    <script src="/LibraFlow/public/auth/cadastro/darkmode.js?v=20260706-auth5"></script>
    <script src="/LibraFlow/public/auth/auth-form.js?v=20260706-auth5"></script>
</body>
</html>
