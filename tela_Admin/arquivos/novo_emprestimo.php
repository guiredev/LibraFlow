<?php
// tela_Admin/arquivos/novo_emprestimo.php

require $_SERVER['DOCUMENT_ROOT'] . '/LibraFlow/cadastros_e_logins/configs/auth_check.php';

if ($_SESSION['usuario_tipo'] !== 'D') {
    header('Location: /LibraFlow/Tela_de_usuario/arquivos/index.html');
    exit;
}

require $_SERVER['DOCUMENT_ROOT'] . '/LibraFlow/cadastros_e_logins/configs/conexao.php';

$erro = '';
$sucesso = '';

try {
    // Buscar livros disponíveis
    $stmt = $conn->query("
        SELECT id, titulo, autor, quantidade
        FROM livros
        WHERE quantidade > 0
        ORDER BY titulo ASC
    ");
    $livros = $stmt->fetchAll();

    // Buscar usuários do tipo Aluno (A)
    $stmt = $conn->query("
        SELECT id, nome, email
        FROM usuarios
        WHERE tipo = 'A'
        ORDER BY nome ASC
    ");
    $alunos = $stmt->fetchAll();

    // Processar formulário
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $idLivro = intval($_POST['id_livro'] ?? 0);
        $idUsuario = intval($_POST['id_usuario'] ?? 0);
        $dias = intval($_POST['dias'] ?? 7);

        if ($idLivro <= 0 || $idUsuario <= 0) {
            $erro = 'Selecione um livro e um aluno.';
        } else {
            $conn->beginTransaction();

            // Verificar se livro existe e tem quantidade disponível
            $stmt = $conn->prepare("
                SELECT id, titulo, quantidade
                FROM livros
                WHERE id = ? AND quantidade > 0
                FOR UPDATE
            ");
            $stmt->execute([$idLivro]);
            $livro = $stmt->fetch();

            if (!$livro) {
                $erro = 'Livro não encontrado ou sem exemplares disponíveis.';
                $conn->rollBack();
            } else {
                // Verificar se usuário existe
                $stmt = $conn->prepare("
                    SELECT id, nome
                    FROM usuarios
                    WHERE id = ? AND tipo = 'A'
                ");
                $stmt->execute([$idUsuario]);
                $usuario = $stmt->fetch();

                if (!$usuario) {
                    $erro = 'Aluno não encontrado.';
                    $conn->rollBack();
                } else {
                    // Verificar se aluno já tem este livro emprestado
                    $stmt = $conn->prepare("
                        SELECT id
                        FROM emprestimos
                        WHERE id_livro = ? AND id_usuario = ? AND status IN ('A', 'V')
                    ");
                    $stmt->execute([$idLivro, $idUsuario]);

                    if ($stmt->fetch()) {
                        $erro = 'Este aluno já possui este livro emprestado.';
                        $conn->rollBack();
                    } else {
                        // Calcular data prevista de devolução
                        $dataPrevista = date('Y-m-d', strtotime("+$dias days"));

                        // Inserir empréstimo
                        $stmt = $conn->prepare("
                            INSERT INTO emprestimos
                            (id_usuario, id_livro, data_emprestimo, data_prevista_devolucao, status)
                            VALUES (?, ?, CURDATE(), ?, 'A')
                        ");
                        $stmt->execute([$idUsuario, $idLivro, $dataPrevista]);

                        // Atualizar quantidade do livro
                        $stmt = $conn->prepare("
                            UPDATE livros
                            SET quantidade = quantidade - 1
                            WHERE id = ?
                        ");
                        $stmt->execute([$idLivro]);

                        $conn->commit();
                        $sucesso = "Empréstimo realizado com sucesso! Livro: {$livro['titulo']}, Aluno: {$usuario['nome']}, Devolução prevista: " . date('d/m/Y', strtotime($dataPrevista));

                        // Limpar seleção
                        $idLivro = 0;
                        $idUsuario = 0;
                    }
                }
            }
        }
    }
} catch (PDOException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    $erro = 'Erro ao processar empréstimo: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Novo Empréstimo | LibraFlow Admin</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400..700;1,400..700&family=Source+Sans+3:ital,wght@0,200..900;1,200..900&display=swap" rel="stylesheet">
    <style>
        .form-novo-emprestimo {
            max-width: 900rem;
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

        .form-group select,
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

        .form-group select:focus,
        .form-group input:focus {
            outline: none;
            border-color: var(--btn-primary);
            box-shadow: 0 0 0 3px rgba(221, 161, 94, 0.2);
        }

        .livro-info,
        .aluno-info {
            display: none;
            background: var(--bg-card);
            padding: 1.5rem;
            border-radius: 0.8rem;
            margin-top: 1rem;
            border-left: 4px solid var(--btn-primary);
        }

        .livro-info.ativo,
        .aluno-info.ativo {
            display: block;
        }

        .info-label {
            font-size: 1.2rem;
            color: var(--text-body);
            font-weight: 500;
        }

        .info-valor {
            font-size: 1.6rem;
            color: var(--text-title);
            font-weight: 600;
        }

        .botoes-acao {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }

        .btn-confirmar {
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

        .btn-confirmar:hover {
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

        .btn-cadastrar-aluno {
            background: #3B82F6;
            color: white;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 0.6rem;
            font-size: 1.2rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
        }

        .btn-cadastrar-aluno:hover {
            background: #2563EB;
        }
    </style>
</head>
<body>
    <aside>
        <div class="logo-aside"><span>LibraFlow</span></div>
        <ul>
           <li><a href="/LibraFlow/tela_Admin/arquivos/Admin.php">🏠 Início</a></li>
            <li><a href="/LibraFlow/tela_Admin/arquivos/listar_livros.php">📚 Livros</a></li>
            <li><a href="/LibraFlow/tela_Admin/arquivos/cadastrar_livro.php">➕ Cadastrar Livro</a></li>
            <li><a href="/LibraFlow/tela_Admin/arquivos/usuarios.php">👥 Usuários</a></li>
            <li><a href="/LibraFlow/tela_Admin/arquivos/emprestimos.php">📋 Empréstimos</a></li>
            <li><a href="/LibraFlow/tela_Admin/arquivos/visitas.php">🕒 Visitas</a></li>
            <li><a href="relatorios/index.php" class="ativo">📈 Relatórios</a></li>
            <div class="sidebar-down">
                <li><a href="/LibraFlow/cadastros_e_logins/logout/logout.php">Sair</a></li>
            </div>
        </ul>
    </aside>

    <nav>
        <span style="font-family:'Lora',serif;font-size:2rem;color:#283618;">Novo Empréstimo</span>
        <div class="right">
            <span style="font-size:1.4rem;color:#606C38;"><?= htmlspecialchars($_SESSION['usuario_nome']) ?></span>
        </div>
    </nav>

    <header>
        <h1>Realizar Empréstimo</h1>
        <p>Selecione o livro e o aluno para realizar o empréstimo.</p>
    </header>

    <main>
        <?php if ($erro): ?>
            <div class="alerta alerta-erro"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>

        <?php if ($sucesso): ?>
            <div class="alerta alerta-sucesso"><?= htmlspecialchars($sucesso) ?></div>
        <?php endif; ?>

        <form method="POST" class="form-novo-emprestimo">
            <div class="form-group">
                <label for="id_livro">Selecione o Livro *</label>
                <select id="id_livro" name="id_livro" required onchange="mostrarInfoLivro()">
                    <option value="">-- Selecione um livro --</option>
                    <?php foreach ($livros as $livro): ?>
                        <option value="<?= $livro['id'] ?>" <?= (isset($idLivro) && $idLivro == $livro['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($livro['titulo']) ?> - <?= htmlspecialchars($livro['autor']) ?> (<?= $livro['quantidade'] ?> disponível)
                        </option>
                    <?php endforeach; ?>
                </select>
                <div id="livro-info" class="livro-info">
                    <div class="info-label">Autor:</div>
                    <div class="info-valor" id="livro-autor"></div>
                </div>
            </div>

            <div class="form-group">
                <label for="id_usuario">Selecione o Aluno *</label>
                <div style="display: flex; gap: 1rem; align-items: flex-end;">
                    <div style="flex: 1;">
                        <select id="id_usuario" name="id_usuario" required onchange="mostrarInfoAluno()">
                            <option value="">-- Selecione um aluno --</option>
                            <?php foreach ($alunos as $aluno): ?>
                                <option value="<?= $aluno['id'] ?>" <?= (isset($idUsuario) && $idUsuario == $aluno['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($aluno['nome']) ?> (<?= htmlspecialchars($aluno['email']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="button" class="btn-cadastrar-aluno" onclick="window.location.href='cadastro_rapido_aluno.php'">
                        + Novo Aluno
                    </button>
                </div>
                <div id="aluno-info" class="aluno-info">
                    <div class="info-label">Email:</div>
                    <div class="info-valor" id="aluno-email"></div>
                </div>
            </div>

            <div class="form-group">
                <label for="dias">Dias para devolução</label>
                <input type="number" id="dias" name="dias" value="7" min="1" max="30" required>
                <small style="color: var(--text-body); font-size: 1.2rem;">Recomendado: 7 a 14 dias</small>
            </div>

            <div class="botoes-acao">
                <button type="submit" class="btn-confirmar">Confirmar Empréstimo</button>
                <a href="emprestimos.php" class="btn-voltar">Voltar</a>
            </div>
        </form>
    </main>

    <script>
        const dadosLivros = <?= json_encode($livros) ?>;
        const dadosAlunos = <?= json_encode($alunos) ?>;

        function mostrarInfoLivro() {
            const select = document.getElementById('id_livro');
            const infoDiv = document.getElementById('livro-info');
            const autorSpan = document.getElementById('livro-autor');

            const livroId = parseInt(select.value);
            const livro = dadosLivros.find(l => l.id === livroId);

            if (livro) {
                autorSpan.textContent = livro.autor;
                infoDiv.classList.add('ativo');
            } else {
                infoDiv.classList.remove('ativo');
            }
        }

        function mostrarInfoAluno() {
            const select = document.getElementById('id_usuario');
            const infoDiv = document.getElementById('aluno-info');
            const emailSpan = document.getElementById('aluno-email');

            const alunoId = parseInt(select.value);
            const aluno = dadosAlunos.find(a => a.id === alunoId);

            if (aluno) {
                emailSpan.textContent = aluno.email;
                infoDiv.classList.add('ativo');
            } else {
                infoDiv.classList.remove('ativo');
            }
        }

        // Inicializar informações se já houver seleção
        document.addEventListener('DOMContentLoaded', function() {
            mostrarInfoLivro();
            mostrarInfoAluno();
        });
    </script>
</body>
</html>

