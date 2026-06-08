<?php
// tela_Admin/arquivos/emprestimos.php

require $_SERVER['DOCUMENT_ROOT'] . '/LibraFlow/cadastros_e_logins/configs/auth_check.php';

if ($_SESSION['usuario_tipo'] !== 'D') {
    header('Location: /LibraFlow/Tela_de_usuario/arquivos/index.html');
    exit;
}

require $_SERVER['DOCUMENT_ROOT'] . '/LibraFlow/cadastros_e_logins/configs/conexao.php';

$erro = '';
$sucesso = '';

try {
    $conn->exec("
        UPDATE emprestimos
        SET status = 'V'
        WHERE status = 'A'
          AND data_prevista_devolucao < CURDATE()
    ");

    if (isset($_GET['devolver'])) {
        $idEmprestimo = intval($_GET['devolver']);

        $conn->beginTransaction();

        $stmt = $conn->prepare("
            SELECT id, id_livro, status
            FROM emprestimos
            WHERE id = ?
            FOR UPDATE
        ");
        $stmt->execute([$idEmprestimo]);
        $emprestimo = $stmt->fetch();

        if (!$emprestimo) {
            $erro = 'Empréstimo não encontrado.';
        } elseif ($emprestimo['status'] === 'D') {
            $erro = 'Este empréstimo já foi devolvido.';
        } else {
            $conn->prepare("
                UPDATE emprestimos
                SET status = 'D', data_devolucao = CURDATE()
                WHERE id = ?
            ")->execute([$idEmprestimo]);

            $conn->prepare("UPDATE livros SET quantidade = quantidade + 1 WHERE id = ?")
                 ->execute([$emprestimo['id_livro']]);

            $sucesso = 'Devolução registrada com sucesso.';
        }

        $conn->commit();
    }

    $status = $_GET['status'] ?? '';
    $busca = trim($_GET['busca'] ?? '');

    $sql = "
        SELECT e.*, l.titulo, l.autor, u.nome AS usuario_nome, u.email AS usuario_email
        FROM emprestimos e
        JOIN livros l ON l.id = e.id_livro
        JOIN usuarios u ON u.id = e.id_usuario
        WHERE 1=1
    ";
    $params = [];

    if (in_array($status, ['A', 'D', 'V'], true)) {
        $sql .= " AND e.status = ?";
        $params[] = $status;
    }

    if ($busca !== '') {
        $sql .= " AND (l.titulo LIKE ? OR l.autor LIKE ? OR u.nome LIKE ? OR u.email LIKE ?)";
        $termo = "%$busca%";
        array_push($params, $termo, $termo, $termo, $termo);
    }

    $sql .= " ORDER BY e.data_emprestimo DESC, e.id DESC";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $emprestimos = $stmt->fetchAll();
} catch (PDOException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    $emprestimos = [];
    $erro = 'Não foi possível carregar os empréstimos. Verifique se a tabela foi criada.';
}

$statusInfo = [
    'A' => ['Ativo', '#22C55E'],
    'D' => ['Devolvido', '#3B82F6'],
    'V' => ['Vencido', '#EF4444'],
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Empréstimos | LibraFlow Admin</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400..700;1,400..700&family=Source+Sans+3:ital,wght@0,200..900;1,200..900&display=swap" rel="stylesheet">
    <style>
        .badge-status {
            padding: 0.3rem 0.9rem;
            border-radius: 2rem;
            font-weight: 700;
            font-size: 1.2rem;
            display: inline-block;
        }

        .btn-devolver {
            padding: 0.5rem 1.2rem;
            border-radius: 0.6rem;
            font-size: 1.2rem;
            font-weight: bold;
            text-decoration: none;
            background: #f0fdf4;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .btn-devolver:hover { background: #dcfce7; }
        .muted { color: #888; font-size: 1.2rem; }
    </style>
</head>
<body>
    <aside>
        <div class="logo-aside"><span>LibraFlow</span></div>
        <ul>
            <li><a href="Admin.php">Início</a></li>
            <li><a href="listar_livros.php">Livros</a></li>
            <li><a href="cadastrar_livro.php">Cadastrar Livro</a></li>
            <li><a href="usuarios.php">Usuários</a></li>
            <li><a href="emprestimos.php" class="ativo">Empréstimos</a></li>
            <li><a href="visitas.php">Visitas</a></li>
            <div class="sidebar-down">
                <li><a href="/LibraFlow/cadastros_e_logins/logout/logout.php">Sair</a></li>
            </div>
        </ul>
    </aside>

    <nav>
        <span style="font-family:'Lora',serif;font-size:2rem;color:#283618;">Empréstimos</span>
        <div class="right">
            <span style="font-size:1.4rem;color:#606C38;"><?= htmlspecialchars($_SESSION['usuario_nome']) ?></span>
        </div>
    </nav>

    <header>
        <h1>Empréstimos</h1>
        <p>Acompanhe retiradas, atrasos e devoluções do acervo.</p>
    </header>

    <main>
        <?php if ($erro): ?>
            <div class="alerta alerta-erro"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>

        <?php if ($sucesso): ?>
            <div class="alerta alerta-sucesso"><?= htmlspecialchars($sucesso) ?></div>
        <?php endif; ?>

        <form method="GET" class="filtros">
            <input type="text" name="busca" placeholder="Buscar livro ou usuário..." value="<?= htmlspecialchars($busca ?? '') ?>">
            <select name="status">
                <option value="">Todos os status</option>
                <option value="A" <?= ($status ?? '') === 'A' ? 'selected' : '' ?>>Ativos</option>
                <option value="V" <?= ($status ?? '') === 'V' ? 'selected' : '' ?>>Vencidos</option>
                <option value="D" <?= ($status ?? '') === 'D' ? 'selected' : '' ?>>Devolvidos</option>
            </select>
            <button type="submit" class="btn-filtrar">Filtrar</button>
            <a href="emprestimos.php" style="font-size:1.3rem;color:#BC6C25;font-weight:bold;text-decoration:none;padding:0.8rem;">Limpar</a>
        </form>

        <div class="tabela-wrapper">
            <?php if (empty($emprestimos)): ?>
                <div class="vazio-tabela">Nenhum empréstimo encontrado.</div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Livro</th>
                            <th>Usuário</th>
                            <th>Retirada</th>
                            <th>Prevista</th>
                            <th>Devolução</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($emprestimos as $emprestimo): ?>
                            <?php
                                $info = $statusInfo[$emprestimo['status']] ?? ['?', '#999'];
                                $podeDevolver = in_array($emprestimo['status'], ['A', 'V'], true);
                            ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($emprestimo['titulo']) ?></strong><br>
                                    <span class="muted"><?= htmlspecialchars($emprestimo['autor']) ?></span>
                                </td>
                                <td>
                                    <?= htmlspecialchars($emprestimo['usuario_nome']) ?><br>
                                    <span class="muted"><?= htmlspecialchars($emprestimo['usuario_email']) ?></span>
                                </td>
                                <td><?= date('d/m/Y', strtotime($emprestimo['data_emprestimo'])) ?></td>
                                <td>
                                    <?= !empty($emprestimo['data_prevista_devolucao'])
                                        ? date('d/m/Y', strtotime($emprestimo['data_prevista_devolucao']))
                                        : '-' ?>
                                </td>
                                <td>
                                    <?= !empty($emprestimo['data_devolucao'])
                                        ? date('d/m/Y', strtotime($emprestimo['data_devolucao']))
                                        : '-' ?>
                                </td>
                                <td>
                                    <span class="badge-status" style="background:<?= $info[1] ?>22;color:<?= $info[1] ?>;">
                                        <?= $info[0] ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($podeDevolver): ?>
                                        <a href="emprestimos.php?devolver=<?= $emprestimo['id'] ?>"
                                           class="btn-devolver"
                                           onclick="return confirm('Registrar devolução deste livro?')">
                                            Registrar devolução
                                        </a>
                                    <?php else: ?>
                                        <span class="muted">Finalizado</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
