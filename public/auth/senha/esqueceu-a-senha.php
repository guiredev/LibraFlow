<?php
/*
 * MAPA RAPIDO DO ARQUIVO
 * Local: public/auth/senha/esqueceu-a-senha.php
 * Funcao: Tela que solicita recuperacao de senha e envia email com token.
 */
// public/auth/senha/esqueceu-a-senha.php

require_once $_SERVER['DOCUMENT_ROOT'] . '/LibraFlow/vendor/autoload.php';
require_once __DIR__ . '/../../../app/config/conexao.php';
require_once __DIR__ . '/../../../app/config/email.php';

$mensagem = '';
$tipo = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensagem = 'Digite um e-mail valido.';
        $tipo = 'erro';
    } else {
        $stmt = $conn->prepare("SELECT id, nome FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch();

        $mensagem = 'Se este e-mail estiver cadastrado, voce recebera as instrucoes em breve.';
        $tipo = 'sucesso';

        if ($usuario) {
            $token = bin2hex(random_bytes(32));
            $expira = date('Y-m-d H:i:s', strtotime('+1 hour'));
            $tokenCriado = false;

            try {
                $mailConfig = libraflowMailConfig();
                $mailConfigError = libraflowMailConfigError($mailConfig);

                if ($mailConfigError !== '') {
                    throw new RuntimeException($mailConfigError);
                }

                $conn->prepare("DELETE FROM recuperacao_senha WHERE id_usuario = ?")
                     ->execute([$usuario['id']]);

                $conn->prepare(
                    "INSERT INTO recuperacao_senha (id_usuario, token, expira_em) VALUES (?, ?, ?)"
                )->execute([$usuario['id'], $token, $expira]);

                $tokenCriado = true;

                $link = libraflowBuildPasswordResetLink($token);
                libraflowSendPasswordResetEmail($email, $usuario['nome'], $link);
            } catch (Throwable $e) {
                if ($tokenCriado) {
                    try {
                        $conn->prepare("DELETE FROM recuperacao_senha WHERE token = ?")
                             ->execute([$token]);
                    } catch (Throwable $cleanupError) {
                        error_log('[LibraFlow][password-reset] Falha ao remover token apos erro: ' . $cleanupError->getMessage());
                    }
                }

                error_log('[LibraFlow][password-reset] Falha no envio para usuario_id=' . $usuario['id'] . ': ' . $e->getMessage());

                if (libraflowIsLocalDevelopment()) {
                    $mensagem = 'Falha no envio de e-mail: ' . $e->getMessage();
                    $tipo = 'erro';
                }
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
    <link rel="stylesheet" href="estilo.css">
    <link rel="stylesheet" href="/LibraFlow/public/admin/darkmode-btn.css">
    <title>LibraFlow - Esqueceu a Senha</title>
    <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400..700;1,400..700&family=Source+Sans+3:ital,wght@0,200..900;1,200..900&display=swap" rel="stylesheet">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            font-family: 'Source Sans 3', sans-serif;
            background: #FEFAE0;
        }
        .inputs {
            background: #fff;
            padding: 3rem 2.5rem;
            border-radius: 1.5rem;
            box-shadow: 0 4px 24px rgba(0,0,0,.08);
            width: 100%;
            max-width: 420px;
            text-align: center;
        }
        h2 {
            font-family: 'Lora', serif;
            font-size: 2.2rem;
            color: #283618;
            margin-bottom: 0.5rem;
        }
        .subtitulo {
            font-size: 1.3rem;
            color: #606C38;
            margin-bottom: 2rem;
        }
        label {
            display: block;
            font-size: 1.3rem;
            font-weight: 600;
            color: #444;
            margin-bottom: 0.5rem;
            text-align: left;
        }
        input[type="email"] {
            width: 100%;
            height: 3.5rem;
            padding: 0 1rem;
            border: 0.1rem solid #BC6C25;
            border-radius: 1.5rem;
            font-size: 1.3rem;
            font-family: 'Source Sans 3', sans-serif;
            margin-bottom: 1.5rem;
            outline: none;
        }
        input[type="email"]:focus { border-color: #606C38; }
        input[type="submit"] {
            width: 100%;
            height: 3.5rem;
            background-color: #DDA15E;
            color: #FAF9F6;
            border: none;
            border-radius: 1.5rem;
            font-size: 1.5rem;
            font-weight: bold;
            cursor: pointer;
            font-family: 'Source Sans 3', sans-serif;
            transition: background 0.2s;
        }
        input[type="submit"]:hover { background-color: #BC6C25; }
        .alerta {
            padding: 1rem 1.5rem;
            border-radius: 1rem;
            font-size: 1.3rem;
            margin-bottom: 1.5rem;
        }
        .alerta-erro { background: #fff0f0; color: #8b0000; border: 1px solid #f5c6c6; }
        .alerta-sucesso { background: #f0fdf4; color: #1a4d2e; border: 1px solid #bbf7d0; }
        .voltar {
            display: block;
            margin-top: 1.5rem;
            font-size: 1.3rem;
            color: #BC6C25;
            font-weight: bold;
            text-decoration: none;
        }
        .voltar:hover { text-decoration: underline; }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <div class="inputs">
        <h2>Esqueceu a senha?</h2>
        <p class="subtitulo">Digite seu e-mail e enviaremos as instrucoes.</p>

        <?php if ($mensagem): ?>
            <div class="alerta alerta-<?= htmlspecialchars($tipo) ?>">
                <?= htmlspecialchars($mensagem) ?>
            </div>
        <?php endif; ?>

        <?php if ($tipo !== 'sucesso'): ?>
            <form method="POST" action="">
                <label for="email">E-mail cadastrado</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="seu@email.com"
                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                    required>
                <input type="submit" value="Enviar instrucoes">
            </form>
        <?php endif; ?>

        <a class="voltar" href="/LibraFlow/public/auth/login/login.php">&#8592; Voltar para o login</a>
    </div>

    <button id="themeToggle" class="theme-toggle-float" aria-label="Alternar tema claro/escuro">
        <span id="themeIcon"><i class="fas fa-moon" aria-hidden="true"></i></span>
        <span id="themeLabel">Escuro</span>
    </button>

    <script src="/LibraFlow/public/auth/senha/darkmode.js"></script>
</body>
</html>
