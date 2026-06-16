<?php
// tela_Admin/arquivos/Admin.php

require $_SERVER['DOCUMENT_ROOT'] . '/LibraFlow/cadastros_e_logins/configs/auth_check.php';

if ($_SESSION['usuario_tipo'] !== 'D') {
    header('Location: /LibraFlow/Tela_de_usuario/arquivos/index.html');
    exit;
}

require $_SERVER['DOCUMENT_ROOT'] . '/LibraFlow/cadastros_e_logins/configs/conexao.php';

$conn->exec("
    UPDATE emprestimos
    SET status = 'V'
    WHERE status = 'A'
      AND data_prevista_devolucao < CURDATE()
");

// Dados do resumo
$totalEmprestimos = $conn->query("SELECT COUNT(*) FROM emprestimos WHERE status = 'A'")->fetchColumn();
$totalAtraso      = $conn->query("SELECT COUNT(*) FROM emprestimos WHERE status = 'V'")->fetchColumn();
$totalUsuarios    = $conn->query("SELECT COUNT(*) FROM usuarios WHERE tipo = 'A'")->fetchColumn();
$totalLivros      = $conn->query("SELECT COUNT(*) FROM livros")->fetchColumn();

// Histórico recente
$historico = $conn->query("
    SELECT e.data_emprestimo, l.titulo, u.nome, e.status
    FROM emprestimos e
    JOIN livros l   ON l.id = e.id_livro
    JOIN usuarios u ON u.id = e.id_usuario
    ORDER BY e.data_emprestimo DESC
    LIMIT 10
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Administrativo | LibraFlow</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="darkmode-btn.css">
    <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400..700;1,400..700&family=Source+Sans+3:ital,wght@0,200..900;1,200..900&display=swap" rel="stylesheet">
</head>
<body>
    <aside>
        <div class="logo-aside">
            <span>LibraFlow</span>
        </div>
        <ul>
            <li><a href="Admin.php" class="ativo">🏠 Início</a></li>
            <li><a href="listar_livros.php">📚 Livros</a></li>
            <li><a href="cadastrar_livro.php">➕ Cadastrar Livro</a></li>
            <li><a href="usuarios.php">👥 Usuários</a></li>
            <li><a href="emprestimos.php">📋 Empréstimos</a></li>
            <li><a href="/LibraFlow/relatorios/index.php">📈 Relatórios</a></li>
            <li><a href="visitas.php">Visitas</a></li>
            <div class="sidebar-down">
                <li><a href="/LibraFlow/cadastros_e_logins/logout/logout.php">🚪 Sair</a></li>
            </div>
        </ul>
    </aside>

    <nav>
        <div class="logo-nav">
            <span style="font-family:'Lora',serif;font-size:2rem;color:#283618;">Painel Admin</span>
        </div>
        <div class="right">
            <div class="user" style="font-size:1.4rem;color:#606C38;font-family:'Source Sans 3',sans-serif;">
                👤 <?= htmlspecialchars($_SESSION['usuario_nome']) ?>
            </div>
        </div>
    </nav>

    <header>
        <h1>Painel Administrativo</h1>
        <p>Bem-vindo ao painel do LibraFlow. Gerencie livros, usuários e empréstimos.</p>
    </header>

    <main>
        <section class="resumo">
            <div class="card-1">
                <h2>Empréstimos Ativos</h2>
                <p><?= $totalEmprestimos ?></p>
            </div>
            <div class="card-2">
                <h2>Em Atraso</h2>
                <p><?= $totalAtraso ?></p>
            </div>
            <div class="card-3">
                <h2>Alunos Cadastrados</h2>
                <p><?= $totalUsuarios ?></p>
            </div>
            <div class="card-4">
                <h2>Total de Livros</h2>
                <p><?= $totalLivros ?></p>
            </div>
        </section>

        <section class="historico">
            <h2>Histórico Recente de Empréstimos</h2>
            <table>
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Livro</th>
                        <th>Usuário</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($historico)): ?>
                        <tr><td colspan="4" style="text-align:center;padding:2rem;color:#888">Nenhum empréstimo registrado.</td></tr>
                    <?php else: ?>
                        <?php foreach ($historico as $h): ?>
                        <tr>
                            <td><?= date('d/m/Y', strtotime($h['data_emprestimo'])) ?></td>
                            <td><?= htmlspecialchars($h['titulo']) ?></td>
                            <td><?= htmlspecialchars($h['nome']) ?></td>
                            <td>
                                <?php
                                $badges = ['A' => ['Ativo','#22C55E'], 'D' => ['Devolvido','#3B82F6'], 'V' => ['Vencido','#EF4444']];
                                $b = $badges[$h['status']] ?? ['?','#999'];
                                ?>
                                <span style="padding:0.3rem 0.9rem;border-radius:2rem;background:<?= $b[1] ?>22;color:<?= $b[1] ?>;font-weight:600;font-size:1.2rem;">
                                    <?= $b[0] ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
    </main>

    <!-- Botão Dark Mode -->
    <button id="themeToggle" class="theme-toggle-float" aria-label="Alternar tema claro/escuro">
        <span id="themeIcon">🌙</span>
        <span id="themeLabel">Escuro</span>
    </button>

    <script src="darkmode.js"></script>
</body>
</html>
