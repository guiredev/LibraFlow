<?php
/*
 * MAPA RAPIDO DO ARQUIVO
 * Local: public/auth/login/login.php
 * Funcao: Tela de login. Valida email/senha, cria sessao e chama o redirecionamento por tipo de usuario.
 */
// public/auth/login/login.php

session_start();
require '../../../app/config/conexao.php';
require '../../../app/config/auth.php';

libraflowRestoreSessionFromRememberCookie($conn);

if (isset($_SESSION['usuario_id'])) {
    libraflowRedirectByUserType($_SESSION['usuario_tipo']);
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha']       ?? '';

    if (!libraflowValidateCsrfToken($_POST['csrf_token'] ?? null)) {
        $erro = 'Sessão expirada. Recarregue a página e tente novamente.';
    } elseif (empty($email) || empty($senha)) {
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

            if (!empty($_POST['lembrar'])) {
                libraflowRememberUser($conn, (int) $usuario['id']);
            } else {
                libraflowForgetRememberToken($conn);
            }

            libraflowRedirectByUserType($usuario['tipo']);
        } else {
            $erro = 'E-mail ou senha incorretos.';
        }
    }
}

$cadastroOk = isset($_GET['cadastro']) && $_GET['cadastro'] === 'ok';
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
    <title>Bem-vindo | LibraFlow</title>
    <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400..700;1,400..700&family=Source+Sans+3:ital,wght@0,200..900;1,200..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <div class="conteiner-main">
        <nav class="auth-nav">
            <a class="brand-link" href="/LibraFlow/public/catalogo/catalogo.php" aria-label="LibraFlow">
                <img src="imgs/Logo-LibraFlow.png" alt="" aria-hidden="true">
                <span>LibraFlow</span>
            </a>
            <div class="links-nav-login">
                <ul>
                    <li><a href="/LibraFlow/public/catalogo/catalogo.php">Início</a></li>
                    <li><a href="#">Sobre nós</a></li>
                    <li><a href="#">Contato</a></li>
                </ul>
            </div>
            <div class="login btn">
                <a href="/LibraFlow/public/auth/cadastro/register.php">
                    <button id="btn-log" type="button">Cadastrar</button>
                </a>
            </div>
        </nav>

        <main>
            <section class="text-main" aria-labelledby="auth-title">
                <span class="eyebrow">Biblioteca digital escolar</span>
                <h1>Bem-vindo ao LibraFlow</h1>
                <p>Acesse seus empréstimos, favoritos, prazos e solicitações em um painel simples de acompanhar.</p>
                <div class="auth-highlights" aria-label="Recursos do LibraFlow">
                    <span><i class="fas fa-book-open" aria-hidden="true"></i> Catálogo organizado</span>
                    <span><i class="fas fa-clock" aria-hidden="true"></i> Controle de prazos</span>
                    <span><i class="fas fa-bell" aria-hidden="true"></i> Avisos importantes</span>
                </div>
            </section>

            <div class="img-main">
                <img src="imgs/img-main.png" alt="imagem de livros">
            </div>

            <div class="form-main">
                <span class="form-kicker">Acesso</span>
                <h2 id="auth-title">Faça login na sua conta</h2>

                <?php if ($cadastroOk): ?>
                    <div class="alerta alerta-sucesso">Cadastro realizado! Faça seu login.</div>
                <?php endif; ?>

                <?php if ($erro): ?>
                    <div class="alerta alerta-erro"><?= htmlspecialchars($erro) ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                    <label class="field-group" for="email">
                        <span>E-mail</span>
                        <span class="field-control">
                            <i class="fas fa-envelope" aria-hidden="true"></i>
                            <input
                                id="email"
                                type="email"
                                name="email"
                                placeholder="seu@email.com"
                                autocomplete="email"
                                value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                                required>
                        </span>
                    </label>

                    <label class="field-group" for="senha">
                        <span>Senha</span>
                        <span class="field-control password-field">
                            <i class="fas fa-lock" aria-hidden="true"></i>
                            <input
                                id="senha"
                                type="password"
                                name="senha"
                                placeholder="Digite sua senha"
                                autocomplete="current-password"
                                required>
                            <button type="button" class="password-toggle" aria-label="Mostrar senha">
                                <i class="fas fa-eye" aria-hidden="true"></i>
                            </button>
                        </span>
                    </label>

                    <div class="form-options">
                        <label class="checkbox-option">
                            <input type="checkbox" name="lembrar" value="1">
                            <span>Lembrar-me neste dispositivo</span>
                        </label>

                        <a href="/LibraFlow/public/auth/senha/esqueceu-a-senha.php">
                            Esqueceu a senha?
                        </a>
                    </div>

                    <button type="submit" class="primary-action">Entrar</button>
                </form>
            </div>
        </main>
    </div>

    <button id="themeToggle" class="theme-toggle-float" aria-label="Alternar tema claro/escuro">
        <span id="themeIcon"><i class="fas fa-moon" aria-hidden="true"></i></span>
        <span id="themeLabel">Escuro</span>
    </button>

    <script src="/LibraFlow/public/auth/login/darkmode.js?v=20260706-auth5"></script>
    <script src="/LibraFlow/public/auth/auth-form.js?v=20260706-auth5"></script>
</body>
</html>
