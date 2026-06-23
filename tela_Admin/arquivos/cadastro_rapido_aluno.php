<?php
// tela_Admin/arquivos/cadastro_rapido_aluno.php

require $_SERVER['DOCUMENT_ROOT'] . '/LibraFlow/cadastros_e_logins/configs/auth_check.php';

if ($_SESSION['usuario_tipo'] !== 'D') {
    header('Location: /LibraFlow/Tela_de_usuario/arquivos/index.html');
    exit;
}

require $_SERVER['DOCUMENT_ROOT'] . '/LibraFlow/cadastros_e_logins/configs/conexao.php';

$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = trim($_POST['senha'] ?? '');
    $senha_confirm = trim($_POST['senha_confirm'] ?? '');

    // Validações
    if ($nome === '') {
        $erro = 'O nome do aluno é obrigatório.';
    } elseif ($email === '') {
        $erro = 'O e-mail é obrigatório.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'E-mail inválido.';
    } elseif ($senha === '') {
        $erro = 'A senha é obrigatória.';
    } elseif (strlen($senha) < 6) {
        $erro = 'A senha deve ter no mínimo 6 caracteres.';
    } elseif ($senha !== $senha_confirm) {
        $erro = 'As senhas não coincidem.';
    } else {
        try {
            // Verificar se e-mail já existe
            $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);

            if ($stmt->fetch()) {
                $erro = 'Este e-mail já está cadastrado.';
            } else {
                // Inserir novo usuário do tipo Aluno (A)
                $senhaHash = password_hash($senha, PASSWORD_BCRYPT);
                $stmt = $conn->prepare("
                    INSERT INTO usuarios (nome, email, senha, tipo)
                    VALUES (?, ?, ?, 'A')
                ");
                $stmt->execute([$nome, $email, $senhaHash]);

                $sucesso = 'Aluno cadastrado com sucesso! Agora você pode selecioná-lo para o empréstimo.';
            }
        } catch (PDOException $e) {
            $erro = 'Erro ao cadastrar aluno: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro Rápido de Aluno | LibraFlow Admin</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400..700;1,400..700&family=Source+Sans+3:ital,wght@0,200..900;1,200..900&display=swap" rel="stylesheet">
    <style>
        .cadastro-rapido {
            max-width: 700rem;
            margin: 0 auto;
        }

        .form-group {
            margin-bottom: 2rem;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            font-size: 1.4rem;
            color: var(--text-title);
            margin-bottom: 0.8rem;
        }

        .form-group input {
            width: 100%;
            padding: 1rem 1.2rem;
            font-size: 1.4rem;
            border: 2px solid var(--border-color);
            border-radius: 0.6rem;
            background: var(--bg-card);
            color: var(--text-body);
            transition: var(--transition);
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--btn-primary);
            box-shadow: 0 0 0 3px rgba(221, 161, 94, 0.2);
        }

        .info-box {
            background: var(--bg-card);
            padding: 1.5rem;
            border-radius: 0.8rem;
            margin-bottom: 2rem;
            border-left: 4px solid #3B82F6;
        }

        .info-box h3 {
            font-size: 1.5rem;
            color: var(--text-title);
            margin-bottom: 0.5rem;
        }

        .info-box p {
            font-size: 1.3rem;
            color: var(--text-body);
        }

        .senha-info {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }

        .botoes-acao {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }

        .btn-salvar {
            flex: 1;
            background: var(--btn-primary);
            color: var(--btn-primary-text);
            padding: 1rem 2rem;
            border: none;
            border-radius: 0.8rem;
            font-size: 1.5rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
        }

        .btn-salvar:hover {
            background: var(--btn-primary-hover);
            transform: translateY(-2px);
        }

        .btn-voltar {
            background: transparent;
            color: var(--text-link);
            padding: 1rem 2rem;
            border: 2px solid var(--text-link);
            border-radius: 0.8rem;
            font-size: 1.5rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: var(--transition);
        }

        .btn-voltar:hover {
            background: var(--text-link);
            color: var(--btn-primary-text);
        }
    </style>
</head>
<body>
    <aside>
        <div class="logo-aside"><span>LibraFlow</span></div>
        <ul>
            <li><a href="/LibraFlow/tela_Admin/arquivos/Admin.php">Início</a></li>
            <li><a href="/LibraFlow/tela_Admin/arquivos/listar_livros.php">Livros</a></li>
            <li><a href="/LibraFlow/tela_Admin/arquivos/cadastrar_livro.php">Cadastrar Livro</a></li>
            <li><a href="/LibraFlow/tela_Admin/arquivos/usuarios.php">Usuários</a></li>
            <li><a href="/LibraFlow/tela_Admin/arquivos/emprestimos.php">Empréstimos</a></li>
            <li><a href="/LibraFlow/relatorios/index.php">📈 Relatórios</a></li>
            <li><a href="/LibraFlow/tela_Admin/arquivos/visitas.php">Visitas</a></li>
            <div class="sidebar-down">
                <li><a href="/LibraFlow/cadastros_e_logins/logout/logout.php">Sair</a></li>
            </div>
        </ul>
    </aside>

    <nav>
        <span style="font-family:'Lora',serif;font-size:2rem;color:#283618;">Cadastro Rápido</span>
        <div class="right">
            <span style="font-size:1.4rem;color:#606C38;"><?= htmlspecialchars($_SESSION['usuario_nome']) ?></span>
        </div>
    </nav>

    <header>
        <h1>Cadastrar Novo Aluno</h1>
        <p>Cadastre rapidamente um aluno para realizar o empréstimo.</p>
    </header>

    <main>
        <?php if ($erro): ?>
            <div class="alerta alerta-erro"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>

        <?php if ($sucesso): ?>
            <div class="alerta alerta-sucesso">
                <?= htmlspecialchars($sucesso) ?>
                <div style="margin-top: 1rem;">
                    <a href="novo_emprestimo.php" style="color: var(--btn-primary-text); font-weight: 600; text-decoration: underline;">
                        Ir para Empréstimo →
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <div class="info-box">
            <h3>📘 Informações Importantes</h3>
            <p>O aluno será cadastrado como tipo <strong>Aluno (A)</strong> e terá acesso ao catálogo de livros.</p>
            <p>A senha é obrigatória para que o aluno possa acessar o sistema posteriormente.</p>
        </div>

        <form method="POST" class="cadastro-rapido">
            <div class="form-group">
                <label for="nome">Nome Completo do Aluno *</label>
                <input type="text" id="nome" name="nome" required
                       placeholder="Ex: João Silva Santos"
                       value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="email">E-mail *</label>
                <input type="email" id="email" name="email" required
                       placeholder="Ex: joao.silva@escola.com"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="senha">Senha *</label>
                <input type="password" id="senha" name="senha" required
                       placeholder="Mínimo 6 caracteres"
                       minlength="6">
                <div class="senha-info">
                    <small style="color: var(--text-body);">Mínimo 6 caracteres</small>
                </div>
            </div>

            <div class="form-group">
                <label for="senha_confirm">Confirmar Senha *</label>
                <input type="password" id="senha_confirm" name="senha_confirm" required
                       placeholder="Digite a senha novamente"
                       minlength="6">
            </div>

            <div class="botoes-acao">
                <button type="submit" class="btn-salvar">Cadastrar Aluno</button>
                <a href="novo_emprestimo.php" class="btn-voltar">Voltar</a>
            </div>
        </form>
    </main>

    <script>
        // Validar senhas em tempo real
        document.getElementById('senha_confirm').addEventListener('input', function() {
            const senha = document.getElementById('senha').value;
            const confirm = this.value;

            if (senha !== confirm && confirm !== '') {
                this.style.borderColor = '#EF4444';
            } else {
                this.style.borderColor = 'var(--border-color)';
            }
        });
    </script>
</body>
</html>

