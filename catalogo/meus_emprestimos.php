<?php
// catalogo/meus_emprestimos.php

require $_SERVER['DOCUMENT_ROOT'] . '/LibraFlow/cadastros_e_logins/configs/auth_check.php';
require $_SERVER['DOCUMENT_ROOT'] . '/LibraFlow/cadastros_e_logins/configs/conexao.php';

try {
    $conn->prepare("
        UPDATE emprestimos
        SET status = 'V'
        WHERE id_usuario = ?
          AND status = 'A'
          AND data_prevista_devolucao < CURDATE()
    ")->execute([$_SESSION['usuario_id']]);

    $stmt = $conn->prepare("
        SELECT e.*, l.titulo, l.autor, l.capa
        FROM emprestimos e
        JOIN livros l ON l.id = e.id_livro
        WHERE e.id_usuario = ?
        ORDER BY e.data_emprestimo DESC, e.id DESC
    ");
    $stmt->execute([$_SESSION['usuario_id']]);
    $emprestimos = $stmt->fetchAll();
} catch (PDOException $e) {
    $emprestimos = [];
    $erro = 'Não foi possível carregar seus empréstimos.';
}

$statusInfo = [
    'A' => ['Ativo', 'status-ativo'],
    'D' => ['Devolvido', 'status-devolvido'],
    'V' => ['Vencido', 'status-vencido'],
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="/LibraFlow/tela_Admin/arquivos/darkmode-btn.css">
    <title>Meus Empréstimos | LibraFlow</title>
    <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400..700;1,400..700&family=Source+Sans+3:ital,wght@0,200..900;1,200..900&display=swap" rel="stylesheet">
    <style>
        .lista-emprestimos {
            width: 100%;
            max-width: 960px;
            padding: 2rem 4rem 5rem;
        }

        .emprestimo-card {
            display: grid;
            grid-template-columns: 7rem 1fr auto;
            gap: 1.6rem;
            align-items: center;
            background: #fff;
            border-radius: 1.2rem;
            box-shadow: 0 2px 12px rgba(0,0,0,.07);
            padding: 1.4rem;
            margin-bottom: 1.2rem;
            font-family: 'Source Sans 3', sans-serif;
        }

        .emprestimo-card img,
        .emprestimo-card .sem-capa-lista {
            width: 7rem;
            height: 9.5rem;
            border-radius: 0.6rem;
            object-fit: cover;
            background: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
        }

        .emprestimo-info h2 {
            font-family: 'Lora', serif;
            color: #283618;
            font-size: 1.8rem;
            margin-bottom: 0.3rem;
        }

        .emprestimo-info p {
            color: #606C38;
            font-size: 1.3rem;
            line-height: 2.2rem;
        }

        .status {
            padding: 0.5rem 1rem;
            border-radius: 2rem;
            font-size: 1.2rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .status-ativo { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
        .status-devolvido { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }
        .status-vencido { background: #fff0f0; color: #b91c1c; border: 1px solid #fecaca; }

        .vazio {
            background: #fff;
            border-radius: 1.2rem;
            padding: 4rem;
            text-align: center;
            color: #888;
            font-size: 1.5rem;
            font-family: 'Source Sans 3', sans-serif;
        }

        @media (max-width: 700px) {
            .emprestimo-card { grid-template-columns: 6rem 1fr; }
            .status { grid-column: 1 / -1; width: fit-content; }
        }

        @media (max-width: 480px) {
            .meus-emprestimos {
                padding: 1.5rem 1rem;
                max-width: 100%;
            }

            .meus-emprestimos h2 {
                font-size: 1.5rem;
                margin-bottom: 1.5rem;
            }

            .vazio {
                font-size: 1.2rem;
                padding: 2rem 1rem;
            }

            .emprestimo-card {
                grid-template-columns: 1fr;
                grid-template-rows: auto auto auto;
                gap: 0.8rem;
                padding: 1rem;
            }

            .livro-info h3 {
                font-size: 1.1rem;
            }

            .livro-info p {
                font-size: 1rem;
            }

            .data-info {
                font-size: 0.9rem;
            }

            .status {
                grid-column: 1;
                grid-row: auto;
                padding: 0.3rem 0.8rem;
                font-size: 0.9rem;
            }

            .botoes-card {
                grid-column: 1;
            }

            .btn-devolver {
                padding: 0.6rem 1.2rem;
                font-size: 1.1rem;
            }
        }

        /* ==================== DARK MODE ==================== */
        body.dark {
            background: #1C2410;
            color: #D4E8B0;
        }

        body.dark nav {
            background: #1C2410;
        }

        body.dark .meus-emprestimos h2 {
            color: #D4E8B0;
        }

        body.dark .emprestimo-card {
            background: #243015;
        }

        body.dark .livro-info h3 {
            color: #D4E8B0;
        }

        body.dark .livro-info p {
            color: #A8C97F;
        }

        body.dark .data-info {
            color: #A8C97F;
        }

        body.dark .status {
            background: #2A3318;
            color: #A8C97F;
            border-color: #3A4E1E;
        }

        body.dark .status.ativo {
            background: #1a4a1a;
            color: #90EE90;
            border-color: #2a6a2a;
        }

        body.dark .status.vencido {
            background: #1a4a1a;
            color: #FF6B6B;
            border-color: #2a6a2a;
        }

        body.dark .btn-devolver {
            background: #3A4E1E;
            color: #D4E8B0;
        }

        body.dark .btn-devolver:hover {
            background: #4A6020;
        }

        body.dark .vazio {
            color: #A8C97F;
        }

        body.dark .total-resultados {
            color: #A8C97F;
        }

        body.dark .links-nav a,
        body.dark nav a {
            color: #A8C97F;
        }
    </style>
</head>
<body>
    <nav>
        <div class="logo-nav">
            <img src="/LibraFlow/assets/logo.png" alt="Logo LibraFlow">
        </div>
        <div class="links-nav">
            <ul>
                <li><a href="/LibraFlow/Tela_de_usuario/arquivos/index.html">Início</a></li>
                <li><a href="/LibraFlow/catalogo/catalogo.php">Catálogo</a></li>
                <li><a href="/LibraFlow/catalogo/meus_emprestimos.php">Meus empréstimos</a></li>
                <li><a href="/LibraFlow/cadastros_e_logins/logout/logout.php">Sair</a></li>
            </ul>
        </div>
        <div class="user">
            <span style="font-size:1.3rem;color:#606C38;font-family:'Source Sans 3',sans-serif;">
                <?= htmlspecialchars($_SESSION['usuario_nome']) ?>
            </span>
        </div>
    </nav>

    <header>
        <div class="text-header">
            <h1>Meus Empréstimos</h1>
            <p>Acompanhe os livros que estão com você e as datas de devolução.</p>
        </div>
    </header>

    <main class="lista-emprestimos">
        <?php if (!empty($erro)): ?>
            <div class="vazio"><?= htmlspecialchars($erro) ?></div>
        <?php elseif (empty($emprestimos)): ?>
            <div class="vazio">Você ainda não possui empréstimos.</div>
        <?php else: ?>
            <?php foreach ($emprestimos as $emprestimo): ?>
                <?php $info = $statusInfo[$emprestimo['status']] ?? ['Desconhecido', 'status-devolvido']; ?>
                <article class="emprestimo-card">
                    <?php if ($emprestimo['capa']): ?>
                        <img src="/LibraFlow/catalogo/capas/<?= htmlspecialchars($emprestimo['capa']) ?>" alt="Capa de <?= htmlspecialchars($emprestimo['titulo']) ?>">
                    <?php else: ?>
                        <div class="sem-capa-lista">Livro</div>
                    <?php endif; ?>

                    <div class="emprestimo-info">
                        <h2><?= htmlspecialchars($emprestimo['titulo']) ?></h2>
                        <p><?= htmlspecialchars($emprestimo['autor']) ?></p>
                        <p>
                            Retirado em <?= date('d/m/Y', strtotime($emprestimo['data_emprestimo'])) ?>
                            <?php if (!empty($emprestimo['data_prevista_devolucao'])): ?>
                                · devolver até <?= date('d/m/Y', strtotime($emprestimo['data_prevista_devolucao'])) ?>
                            <?php endif; ?>
                        </p>
                    </div>

                    <span class="status <?= $info[1] ?>"><?= $info[0] ?></span>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- Botão Dark Mode -->
        <button id="themeToggle" class="theme-toggle-float" aria-label="Alternar tema claro/escuro">
            <span id="themeIcon">🌙</span>
            <span id="themeLabel">Escuro</span>
        </button>
    </main>

    <script src="/LibraFlow/catalogo/darkmode.js"></script>
</body>
    </main>
</body>
</html>
