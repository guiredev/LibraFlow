<?php
/*
 * MAPA RAPIDO DO ARQUIVO
 * Local: public/admin/usuarios.php
 * Funcao: Gestao de usuarios, edicao e exclusao.
 */
// public/admin/usuarios.php

require $_SERVER['DOCUMENT_ROOT'] . '/LibraFlow/app/config/auth_check.php';

if ($_SESSION['usuario_tipo'] !== 'D') {
    header('Location: /LibraFlow/public/usuario/index.php');
    exit;
}

require $_SERVER['DOCUMENT_ROOT'] . '/LibraFlow/app/config/conexao.php';

$acao = $_GET['acao'] ?? '';
$idUsuario = $_GET['id'] ?? '';
$erro = '';
$sucesso = '';

// Endpoint AJAX para carregar alunos
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    $stmt = $conn->query("
        SELECT id, nome, email
        FROM usuarios
        WHERE tipo = 'A'
        ORDER BY nome ASC
    ");
    echo json_encode($stmt->fetchAll());
    exit;
}

// Processar formulário de edição
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $acao === 'editar') {
    $id = intval($_POST['id'] ?? 0);
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $rm = trim($_POST['rm'] ?? '');
    $endereco = trim($_POST['endereco'] ?? '');
    $idade = intval($_POST['idade'] ?? 0);

    if ($id <= 0 || empty($nome) || empty($email)) {
        $erro = 'Preencha os campos obrigatórios.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'E-mail inválido.';
    } elseif ($idade < 1 || $idade > 120) {
        $erro = 'Informe uma idade válida.';
    } else {
        try {
            $stmt = $conn->prepare("
                UPDATE usuarios
                SET nome = ?, email = ?, telefone = ?, rm = ?, endereco = ?, idade = ?
                WHERE id = ? AND tipo = 'A'
            ");
            $stmt->execute([$nome, $email, $telefone, $rm, $endereco, $idade, $id]);
            $sucesso = 'Usuário atualizado com sucesso.';
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $erro = 'Este e-mail ou RM já está cadastrado.';
            } else {
                $erro = 'Erro ao atualizar usuário.';
            }
        }
    }
}

// Excluir usuário
if ($acao === 'excluir' && $idUsuario) {
    try {
        $stmt = $conn->prepare("DELETE FROM usuarios WHERE id = ? AND tipo = 'A'");
        $stmt->execute([intval($idUsuario)]);
        $sucesso = 'Usuário excluído com sucesso.';
    } catch (PDOException $e) {
        $erro = 'Não foi possível excluir este usuário. Verifique se não há empréstimos ativos.';
    }
}

// Buscar usuário para edição
$usuarioEditar = null;
if ($acao === 'editar' && $idUsuario) {
    $stmt = $conn->prepare("SELECT * FROM usuarios WHERE id = ? AND tipo = 'A'");
    $stmt->execute([intval($idUsuario)]);
    $usuarioEditar = $stmt->fetch();
}

// Listar todos os usuários (alunos)
try {
    $stmt = $conn->query("
        SELECT id, nome, email, telefone, rm, endereco, idade, criado_em
        FROM usuarios
        WHERE tipo = 'A'
        ORDER BY nome ASC
    ");
    $usuarios = $stmt->fetchAll();
} catch (PDOException $e) {
    $usuarios = [];
    $erro = $erro ?: 'Não foi possível carregar a lista de usuários.';
}

$totalUsuarios = count($usuarios);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuários | LibraFlow Admin</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="darkmode-btn.css">
    <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400..700;1,400..700&family=Source+Sans+3:ital,wght@0,200..900;1,200..900&display=swap" rel="stylesheet">
    <style>
        .usuarios-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .usuarios-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(20rem, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card,
        .tabela-wrapper,
        .modal {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            color: var(--text-title);
            box-shadow: var(--shadow-sm);
        }

        .stat-card {
            border-radius: 1.2rem;
            padding: 1.5rem 2rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .stat-card .icon {
            font-size: 2.5rem;
            color: var(--btn-primary);
        }

        .stat-card .text h3 {
            font-size: 1.15rem;
            color: var(--text-body);
            margin-bottom: 0.3rem;
        }

        .stat-card .text p {
            font-size: 2rem;
            font-weight: 800;
            color: var(--text-title);
        }

        .tabela-wrapper {
            border-radius: 1.2rem;
            padding: 1.5rem;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead tr {
            border-bottom: 2px solid var(--border-color);
        }

        th {
            text-align: left;
            padding: 1rem;
            font-family: 'Lora', serif;
            font-size: 1.1rem;
            color: var(--text-title);
            font-weight: 600;
        }

        td {
            padding: 1rem;
            color: var(--text-body);
            border-bottom: 1px solid var(--border-color);
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        tbody tr:hover {
            background: var(--bg-page);
        }

        .acoes {
            display: flex;
            gap: 0.5rem;
        }

        .btn-tabela {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 0.5rem;
            font-size: 1rem;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s;
        }

        .btn-editar {
            background: var(--btn-primary-hover);
            color: var(--btn-primary-text);
        }

        .btn-editar:hover {
            background: var(--btn-primary);
            color: var(--btn-primary-text);
        }

        .btn-excluir {
            background: #EF4444;
            color: #FFFFFF;
        }

        .btn-excluir:hover {
            background: #DC2626;
            color: #FFFFFF;
        }

        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.55);
            z-index: 2000;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal {
            border-radius: 1.2rem;
            padding: 2rem;
            max-width: 50rem;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal h2 {
            font-family: 'Lora', serif;
            font-size: 1.8rem;
            color: var(--text-title);
            margin-bottom: 1.5rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            font-size: 1.15rem;
            color: var(--text-title);
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        .form-group input {
            width: 100%;
            padding: 0.9rem 1rem;
            background: var(--bg-page);
            border: 1px solid var(--border-color);
            border-radius: 0.6rem;
            color: var(--text-title);
            font-size: 1.2rem;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--btn-primary);
            box-shadow: 0 0 0 0.2rem rgba(188, 108, 37, 0.14);
        }

        body.dark .form-group input:focus {
            box-shadow: 0 0 0 0.2rem rgba(168, 201, 127, 0.16);
        }

        .modal-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .btn-modal {
            flex: 1;
            padding: 0.9rem;
            border: none;
            border-radius: 0.6rem;
            font-size: 1.15rem;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-salvar {
            background: var(--btn-primary);
            color: var(--btn-primary-text);
        }

        .btn-salvar:hover {
            background: var(--btn-primary-hover);
            color: var(--btn-primary-text);
        }

        .btn-cancelar {
            background: var(--bg-page);
            border: 1px solid var(--border-color);
            color: var(--text-title);
        }

        .btn-cancelar:hover {
            background: var(--bg-card);
        }

        .alerta {
            padding: 1rem 1.5rem;
            border-radius: 0.8rem;
            margin-bottom: 1.5rem;
            font-size: 1.2rem;
        }

        .alerta-erro {
            background: #FEE2E2;
            color: #991B1B;
            border: 1px solid #FECACA;
        }

        .alerta-sucesso {
            background: #D1FAE5;
            color: #065F46;
            border: 1px solid #A7F3D0;
        }

        .vazio {
            text-align: center;
            padding: 3rem;
            color: var(--text-body);
            font-size: 1.2rem;
        }

        @media (max-width: 768px) {
            .acoes,
            .modal-actions {
                flex-direction: column;
            }
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <aside>
        <div class="logo-aside"><span>LibraFlow</span></div>
        <ul>
            <li><a href="/LibraFlow/public/admin/Admin.php"><i class="fas fa-house nav-icon" aria-hidden="true"></i> Início</a></li>
            <li><a href="/LibraFlow/public/admin/listar_livros.php"><i class="fas fa-book-open nav-icon" aria-hidden="true"></i> Livros</a></li>
            <li><a href="/LibraFlow/public/admin/cadastrar_livro.php"><i class="fas fa-plus nav-icon" aria-hidden="true"></i> Cadastrar Livro</a></li>
            <li><a href="/LibraFlow/public/admin/usuarios.php" class="ativo"><i class="fas fa-users nav-icon" aria-hidden="true"></i> Usuários</a></li>
            <li><a href="/LibraFlow/public/admin/emprestimos.php"><i class="fas fa-clipboard-list nav-icon" aria-hidden="true"></i> Empréstimos</a></li>
            <li><a href="/LibraFlow/public/admin/visitas.php"><i class="fas fa-clock nav-icon" aria-hidden="true"></i> Visitas</a></li>
            <li><a href="relatorios/index.php"><i class="fas fa-chart-line nav-icon" aria-hidden="true"></i> Relatórios</a></li>
            <div class="sidebar-down">
                <li><a href="/LibraFlow/public/auth/logout.php">Sair</a></li>
            </div>
        </ul>
    </aside>

    <nav>
        <span style="font-family:'Lora',serif;font-size:2rem;color:var(--text-title);">Gestão de Usuários</span>
        <div class="right">
            <span style="font-size:1.4rem;color:var(--text-body);"><?= htmlspecialchars($_SESSION['usuario_nome']) ?></span>
        </div>
    </nav>

    <header>
        <h1>Usuários Cadastrados</h1>
        <p>Gerencie os alunos e seus dados cadastrais.</p>
    </header>

    <main>
        <?php if ($erro): ?>
            <div class="alerta alerta-erro"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>

        <?php if ($sucesso): ?>
            <div class="alerta alerta-sucesso"><?= htmlspecialchars($sucesso) ?></div>
        <?php endif; ?>

        <section class="usuarios-stats">
            <div class="stat-card">
                <div class="icon"><i class="fas fa-users" aria-hidden="true"></i></div>
                <div class="text">
                    <h3>Total de Alunos</h3>
                    <p><?= $totalUsuarios ?></p>
                </div>
            </div>
        </section>

        <section class="tabela-wrapper">
            <?php if (empty($usuarios)): ?>
                <div class="vazio">Nenhum usuário cadastrado.</div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>E-mail</th>
                            <th>Telefone</th>
                            <th>RM</th>
                            <th>Idade</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $usuario): ?>
                            <tr>
                                <td><?= htmlspecialchars($usuario['nome']) ?></td>
                                <td><?= htmlspecialchars($usuario['email']) ?></td>
                                <td><?= htmlspecialchars($usuario['telefone'] ?: '-') ?></td>
                                <td><?= htmlspecialchars($usuario['rm'] ?: '-') ?></td>
                                <td><?= htmlspecialchars($usuario['idade'] ?: '-') ?></td>
                                <td>
                                    <div class="acoes">
                                        <a href="detalhe_aluno.php?id=<?= $usuario['id'] ?>" class="btn-tabela btn-editar">Detalhes</a>
                                        <a href="usuarios.php?acao=editar&id=<?= $usuario['id'] ?>"
                                           class="btn-tabela btn-editar">Editar</a>
                                        <a href="usuarios.php?acao=excluir&id=<?= $usuario['id'] ?>"
                                           class="btn-tabela btn-excluir"
                                           onclick="return confirm('Tem certeza que deseja excluir este usuário?');">Excluir</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>
    </main>

    <!-- Modal de Edição -->
    <?php if ($usuarioEditar): ?>
    <div class="modal-overlay active" onclick="if(event.target === this) this.classList.remove('active')">
        <div class="modal">
            <h2>Editar Usuário</h2>
            <form method="POST" action="usuarios.php?acao=editar">
                <input type="hidden" name="id" value="<?= $usuarioEditar['id'] ?>">

                <div class="form-group">
                    <label for="nome">Nome completo *</label>
                    <input type="text" id="nome" name="nome"
                           value="<?= htmlspecialchars($usuarioEditar['nome']) ?>" required>
                </div>

                <div class="form-group">
                    <label for="email">E-mail *</label>
                    <input type="email" id="email" name="email"
                           value="<?= htmlspecialchars($usuarioEditar['email']) ?>" required>
                </div>

                <div class="form-group">
                    <label for="telefone">Telefone</label>
                    <input type="tel" id="telefone" name="telefone"
                           value="<?= htmlspecialchars($usuarioEditar['telefone'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="rm">RM</label>
                    <input type="text" id="rm" name="rm"
                           value="<?= htmlspecialchars($usuarioEditar['rm'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="endereco">Endereço</label>
                    <input type="text" id="endereco" name="endereco"
                           value="<?= htmlspecialchars($usuarioEditar['endereco'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="idade">Idade</label>
                    <input type="number" id="idade" name="idade"
                           min="1" max="120"
                           value="<?= htmlspecialchars($usuarioEditar['idade'] ?? '') ?>">
                </div>

                <div class="modal-actions">
                    <button type="submit" class="btn-modal btn-salvar">Salvar</button>
                    <button type="button" class="btn-modal btn-cancelar"
                            onclick="document.querySelector('.modal-overlay').classList.remove('active')">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- Botão Dark Mode -->
    <button id="themeToggle" class="theme-toggle-float" aria-label="Alternar tema claro/escuro">
        <span id="themeIcon"><i class="fas fa-moon" aria-hidden="true"></i></span>
        <span id="themeLabel">Escuro</span>
    </button>

    <script src="darkmode.js"></script>
</body>
</html>

