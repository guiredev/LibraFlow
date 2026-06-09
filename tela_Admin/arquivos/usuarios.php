<?php
// tela_Admin/arquivos/usuarios.php

require $_SERVER['DOCUMENT_ROOT'] . '/LibraFlow/cadastros_e_logins/configs/auth_check.php';

if ($_SESSION['usuario_tipo'] !== 'D') {
    header('Location: /LibraFlow/Tela_de_usuario/arquivos/index.php');
    exit;
}

require $_SERVER['DOCUMENT_ROOT'] . '/LibraFlow/cadastros_e_logins/configs/conexao.php';

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

        /* Dark Mode Toggle Button */
        .theme-toggle-wrapper {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            z-index: 1000;
        }

        .theme-toggle-btn {
            width: 5.5rem;
            height: 5.5rem;
            border-radius: 50%;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: #DDA15E;
        }

        .theme-toggle-btn:hover {
            transform: scale(1.1) rotate(20deg);
            box-shadow: 0 6px 20px rgba(0,0,0,0.25);
        }

        .theme-toggle-btn:active {
            transform: scale(0.95);
        }

        .theme-toggle-btn:focus-visible {
            outline: 3px solid #BC6C25;
            outline-offset: 3px;
        }

        body.dark .theme-toggle-btn {
            background: #4A6020;
        }

        @media (max-width: 768px) {
            .theme-toggle-wrapper {
                bottom: 1.5rem;
                right: 1.5rem;
            }

            .theme-toggle-btn {
                width: 4.5rem;
                height: 4.5rem;
                font-size: 1.5rem;
            }
        }

        .usuarios-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: #fff;
            border-radius: 1.2rem;
            padding: 1.5rem 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .stat-card .icon {
            font-size: 2.5rem;
        }

        .stat-card .text h3 {
            font-size: 1rem;
            color: #666;
            margin-bottom: 0.3rem;
        }

        .stat-card .text p {
            font-size: 2rem;
            font-weight: 800;
            color: #283618;
        }

        .tabela-wrapper {
            background: #fff;
            border-radius: 1.2rem;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead tr {
            border-bottom: 2px solid #F5F5F0;
        }

        th {
            text-align: left;
            padding: 1rem;
            font-family: 'Lora', serif;
            font-size: 1.1rem;
            color: #283618;
            font-weight: 600;
        }

        td {
            padding: 1rem;
            border-bottom: 1px solid #F5F5F0;
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        tbody tr:hover {
            background: #F5F5F0;
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
            background: #BC6C25;
            color: #FEFAE0;
        }

        .btn-editar:hover {
            background: #A05A1F;
        }

        .btn-excluir {
            background: #EF4444;
            color: #fff;
        }

        .btn-excluir:hover {
            background: #DC2626;
        }

        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal {
            background: #fff;
            border-radius: 1.2rem;
            padding: 2rem;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal h2 {
            font-family: 'Lora', serif;
            font-size: 1.5rem;
            color: #283618;
            margin-bottom: 1.5rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            font-size: 1rem;
            color: #283618;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        .form-group input {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 0.5rem;
            font-size: 1rem;
        }

        .form-group input:focus {
            outline: none;
            border-color: #BC6C25;
        }

        .modal-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .btn-modal {
            flex: 1;
            padding: 0.8rem;
            border: none;
            border-radius: 0.5rem;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-salvar {
            background: #BC6C25;
            color: #FEFAE0;
        }

        .btn-salvar:hover {
            background: #A05A1F;
        }

        .btn-cancelar {
            background: #ccc;
            color: #333;
        }

        .btn-cancelar:hover {
            background: #bbb;
        }

        .alerta {
            padding: 1rem 1.5rem;
            border-radius: 0.8rem;
            margin-bottom: 1.5rem;
            font-size: 1rem;
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
            color: #999;
            font-size: 1.1rem;
        }

        @media (max-width: 768px) {
            .acoes {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <aside>
        <div class="logo-aside"><span>LibraFlow</span></div>
        <ul>
            <li><a href="Admin.php">Inicio</a></li>
            <li><a href="listar_livros.php">Livros</a></li>
            <li><a href="cadastrar_livro.php">Cadastrar Livro</a></li>
            <li><a href="usuarios.php" class="ativo">Usuarios</a></li>
            <li><a href="emprestimos.php">Emprestimos</a></li>
            <li><a href="visitas.php">Visitas</a></li>
            <div class="sidebar-down">
                <li><a href="/LibraFlow/cadastros_e_logins/logout/logout.php">Sair</a></li>
            </div>
        </ul>
    </aside>

    <nav>
        <span style="font-family:'Lora',serif;font-size:2rem;color:#283618;">Gestão de Usuários</span>
        <div class="right">
            <span style="font-size:1.4rem;color:#606C38;"><?= htmlspecialchars($_SESSION['usuario_nome']) ?></span>
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
                <div class="icon">👥</div>
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
        <span id="themeIcon">🌙</span>
        <span id="themeLabel">Escuro</span>
    </button>

    <script src="darkmode.js"></script>
</body>
</html>
