<?php
// cadastros_e_logins/esqueceu_a_senha/esqueceu-a-senha.php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require $_SERVER['DOCUMENT_ROOT'] . '/LibraFlow/vendor/autoload.php';;
require '../configs/conexao.php';

$mensagem = '';
$tipo     = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensagem = 'Digite um e-mail válido.';
        $tipo     = 'erro';
    } else {
        $stmt = $conn->prepare("SELECT id, nome FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch();

        // Resposta genérica por segurança
        $mensagem = 'Se este e-mail estiver cadastrado, você receberá as instruções em breve.';
        $tipo     = 'sucesso';

        if ($usuario) {
            $token  = bin2hex(random_bytes(32));
            $expira = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $conn->prepare("DELETE FROM recuperacao_senha WHERE id_usuario = ?")
                 ->execute([$usuario['id']]);

            $conn->prepare(
                "INSERT INTO recuperacao_senha (id_usuario, token, expira_em) VALUES (?, ?, ?)"
            )->execute([$usuario['id'], $token, $expira]);

            $link = "http://localhost/LibraFlow/cadastros_e_logins/esqueceu_a_senha/redefinir-senha.php?token=$token";

            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'nexoratecnology@gmail.com';
                $mail->Password   = 'ahjq hucu uicu mbis';
                $mail->SMTPSecure = 'tls';
                $mail->Port       = 587;
                $mail->CharSet    = 'UTF-8';

                $mail->setFrom('nexoratecnology@gmail.com', 'LibraFlow');
                $mail->addAddress($email, $usuario['nome']);

                $mail->isHTML(true);
                $mail->Subject = 'Redefinição de senha — LibraFlow';
                $mail->Body    = "
                    <div style='font-family: sans-serif; max-width: 480px; margin: auto;'>
                        <h2 style='color: #283618;'>Redefinição de senha</h2>
                        <p>Olá, <strong>{$usuario['nome']}</strong>!</p>
                        <p>Recebemos uma solicitação para redefinir a senha da sua conta no LibraFlow.</p>
                        <p>Clique no botão abaixo para criar uma nova senha. O link expira em <strong>1 hora</strong>.</p>
                        <a href='$link'
                           style='display:inline-block; margin-top:1rem; padding:0.75rem 1.5rem;
                                  background:#DDA15E; color:#fff; border-radius:1rem;
                                  text-decoration:none; font-weight:bold;'>
                            Redefinir minha senha
                        </a>
                        <p style='margin-top:1.5rem; font-size:0.85rem; color:#666;'>
                            Se você não solicitou isso, ignore este e-mail.
                        </p>
                    </div>
                ";
                $mail->AltBody = "Acesse o link para redefinir sua senha: $link (válido por 1 hora)";
                $mail->send();
            } catch (Exception $e) {
                    die("Erro ao enviar e-mail: " . $mail->ErrorInfo);
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
    </style>
</head>
<body>
    <div class="inputs">
        <h2>Esqueceu a senha?</h2>
        <p class="subtitulo">Digite seu e-mail e enviaremos as instruções.</p>

        <?php if ($mensagem): ?>
            <div class="alerta alerta-<?= $tipo ?>">
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
            <input type="submit" value="Enviar instruções">
        </form>
        <?php endif; ?>

        <a class="voltar" href="/LibraFlow/cadastros_e_logins/login/arquivos/login.php">← Voltar para o login</a>
    </div>
</body>
</html>
