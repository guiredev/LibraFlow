<?php
// cadastros_e_logins/esqueceu_a_senha/redefinir-senha.php

require $_SERVER['DOCUMENT_ROOT'] . '/LibraFlow/cadastros_e_logins/configs/conexao.php';

$token       = trim($_GET['token'] ?? '');
$erro        = '';
$sucesso     = '';
$tokenValido = false;
$registro    = null;

if (empty($token)) {
    $erro = 'Link inválido.';
} else {
    $stmt = $conn->prepare("
        SELECT r.id, r.id_usuario, u.nome
        FROM recuperacao_senha r
        JOIN usuarios u ON u.id = r.id_usuario
        WHERE r.token = ?
          AND r.expira_em > NOW()
          AND r.usado = 0
    ");
    $stmt->execute([$token]);
    $registro = $stmt->fetch();

    if (!$registro) {
        $erro = 'Este link é inválido ou já expirou. Solicite um novo.';
    } else {
        $tokenValido = true;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tokenValido) {
    $senha = $_POST['senha']           ?? '';
    $conf  = $_POST['confirmar_senha'] ?? '';

    if (empty($senha) || empty($conf)) {
        $erro = 'Preencha os dois campos.';
    } elseif (strlen($senha) < 8) {
        $erro = 'A senha deve ter no mínimo 8 caracteres.';
    } elseif ($senha !== $conf) {
        $erro = 'As senhas não coincidem.';
    } else {
        $hash = password_hash($senha, PASSWORD_BCRYPT);

        $conn->prepare("UPDATE usuarios SET senha = ? WHERE id = ?")
             ->execute([$hash, $registro['id_usuario']]);

        $conn->prepare("UPDATE recuperacao_senha SET usado = 1 WHERE token = ?")
             ->execute([$token]);

        $sucesso     = 'Senha redefinida com sucesso!';
        $tokenValido = false;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LibraFlow - Redefinir Senha</title>
    <link rel="stylesheet" href="/LibraFlow/tela_Admin/arquivos/darkmode-btn.css">
    <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400..700;1,400..700&family=Source+Sans+3:ital,wght@0,200..900;1,200..900&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            font-family: 'Source Sans 3', sans-serif;
            background: #FEFAE0;
        }
        .card {
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
        input[type="password"] {
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
        input[type="password"]:focus { border-color: #606C38; }
        button {
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
        button:hover { background-color: #BC6C25; }
        .alerta {
            padding: 1rem 1.5rem;
            border-radius: 1rem;
            font-size: 1.3rem;
            margin-bottom: 1.5rem;
        }
        .alerta-erro    { background: #fff0f0; color: #8b0000; border: 1px solid #f5c6c6; }
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
        body.dark {
            background: #1C2410;
            color: #D4E8B0;
        }
        body.dark .card {
            background: #243015;
            border: 1px solid #3A4E1E;
            box-shadow: 0 4px 24px rgba(0,0,0,.35);
        }
        body.dark h2 {
            color: #D4E8B0;
        }
        body.dark .subtitulo,
        body.dark label {
            color: #A8C97F;
        }
        body.dark input[type="password"] {
            background: #2A3318;
            border-color: #3A4E1E;
            color: #D4E8B0;
        }
        body.dark input[type="submit"] {
            background: #4A6020;
            color: #D4E8B0;
        }
        body.dark input[type="submit"]:hover {
            background: #3A4E1E;
        }
        body.dark .voltar {
            color: #A8C97F;
        }
    </style>
</head>
<body>
    <div class="card">
        <h2>Nova senha</h2>
        <p class="subtitulo">
            <?php if ($tokenValido && $registro): ?>
                Olá, <strong><?= htmlspecialchars($registro['nome']) ?></strong>! Crie sua nova senha.
            <?php else: ?>
                Redefinição de senha
            <?php endif; ?>
        </p>

        <?php if ($erro): ?>
            <div class="alerta alerta-erro"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>

        <?php if ($sucesso): ?>
            <div class="alerta alerta-sucesso"><?= htmlspecialchars($sucesso) ?></div>
            <a class="voltar" href="/LibraFlow/cadastros_e_logins/login/arquivos/login.php">← Ir para o login</a>

        <?php elseif ($tokenValido): ?>
            <form method="POST" action="">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

                <label for="senha">Nova senha</label>
                <input type="password" id="senha" name="senha" placeholder="mín. 8 caracteres" required>

                <label for="confirmar_senha">Confirmar nova senha</label>
                <input type="password" id="confirmar_senha" name="confirmar_senha" placeholder="repita a senha" required>

                <button type="submit">Salvar nova senha</button>
            </form>
            <a class="voltar" href="/LibraFlow/cadastros_e_logins/login/arquivos/login.php">← Voltar para o login</a>

        <?php else: ?>
            <a class="voltar" href="/LibraFlow/cadastros_e_logins/esqueceu_a_senha/esqueceu-a-senha.php">← Solicitar novo link</a>
        <?php endif; ?>
    </div>
    <button id="themeToggle" class="theme-toggle-float" aria-label="Alternar tema claro/escuro">
        <span id="themeIcon">🌙</span>
        <span id="themeLabel">Escuro</span>
    </button>

    <script src="/LibraFlow/cadastros_e_logins/esqueceu_a_senha/darkmode.js"></script>
</body>
</html>
