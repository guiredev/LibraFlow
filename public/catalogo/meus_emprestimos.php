<?php
/*
 * MAPA RAPIDO DO ARQUIVO
 * Local: public/catalogo/meus_emprestimos.php
 * Funcao: Lista emprestimos ativos/historico do usuario logado.
 */
// catalogo/meus_emprestimos.php

require $_SERVER['DOCUMENT_ROOT'] . '/LibraFlow/app/config/auth_check.php';
require $_SERVER['DOCUMENT_ROOT'] . '/LibraFlow/app/config/conexao.php';
require $_SERVER['DOCUMENT_ROOT'] . '/LibraFlow/app/config/user_features.php';

libraflowEnsureUserFeatureTables($conn);

$statusFiltro = $_GET['status'] ?? '';
$busca = trim($_GET['busca'] ?? '');
$statusPermitidos = ['A', 'V', 'D'];
$csrfToken = libraflowCsrfToken();
$erroAcao = '';
$sucessoAcao = ($_GET['renovado'] ?? '') === '1'
    ? 'Solicitacao de renovacao enviada para analise.'
    : '';

try {
    $conn->prepare("
        UPDATE emprestimos
        SET status = 'V'
        WHERE id_usuario = ?
          AND status = 'A'
          AND data_prevista_devolucao < CURDATE()
    ")->execute([$_SESSION['usuario_id']]);

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'renovar') {
        if (!libraflowValidateCsrfToken($_POST['csrf_token'] ?? null)) {
            $erroAcao = 'Sessao expirada. Recarregue a pagina e tente novamente.';
        } else {
            $idEmprestimo = (int) ($_POST['id_emprestimo'] ?? 0);

            if ($idEmprestimo <= 0) {
                $erroAcao = 'Emprestimo invalido.';
            } else {
                $conn->beginTransaction();

                $stmt = $conn->prepare("
                    SELECT id, status, data_prevista_devolucao
                    FROM emprestimos
                    WHERE id = ?
                      AND id_usuario = ?
                    FOR UPDATE
                ");
                $stmt->execute([$idEmprestimo, $_SESSION['usuario_id']]);
                $emprestimoRenovar = $stmt->fetch();

                if (!$emprestimoRenovar) {
                    $erroAcao = 'Emprestimo nao encontrado.';
                } elseif ($emprestimoRenovar['status'] !== 'A' || empty($emprestimoRenovar['data_prevista_devolucao'])) {
                    $erroAcao = 'Este emprestimo nao pode ser renovado.';
                } else {
                    $stmt = $conn->prepare("
                        SELECT id
                        FROM renovacoes_emprestimos
                        WHERE id_emprestimo = ?
                          AND status = 'P'
                        LIMIT 1
                    ");
                    $stmt->execute([$idEmprestimo]);

                    $hoje = new DateTimeImmutable('today');
                    $prazo = new DateTimeImmutable($emprestimoRenovar['data_prevista_devolucao']);
                    $dias = (int) $hoje->diff($prazo)->format('%r%a');

                    if ($stmt->fetch()) {
                        $erroAcao = 'Ja existe uma solicitacao de renovacao pendente para este emprestimo.';
                    } elseif ($dias < 0) {
                        $erroAcao = 'Emprestimos vencidos precisam ser regularizados na biblioteca.';
                    } elseif ($dias > 2) {
                        $erroAcao = 'A renovacao fica disponivel quando faltarem 2 dias ou menos para vencer.';
                    } else {
                        $stmt = $conn->prepare("
                            INSERT INTO renovacoes_emprestimos (id_emprestimo, id_usuario, dias_solicitados)
                            VALUES (?, ?, 7)
                        ");
                        $stmt->execute([$idEmprestimo, $_SESSION['usuario_id']]);

                        libraflowCriarNotificacao(
                            $conn,
                            (int) $_SESSION['usuario_id'],
                            'Renovacao solicitada',
                            'Seu pedido de renovacao foi enviado para a biblioteca.',
                            '/LibraFlow/public/catalogo/meus_emprestimos.php?status=A'
                        );

                        $conn->commit();
                        header('Location: meus_emprestimos.php?status=A&renovado=1');
                        exit;
                    }
                }

                $conn->commit();
            }
        }
    }

    $stmt = $conn->prepare("
        SELECT
            COUNT(*) AS total,
            SUM(status = 'A') AS ativos,
            SUM(status = 'V') AS vencidos,
            SUM(status = 'D') AS devolvidos
        FROM emprestimos
        WHERE id_usuario = ?
    ");
    $stmt->execute([$_SESSION['usuario_id']]);
    $resumo = $stmt->fetch() ?: ['total' => 0, 'ativos' => 0, 'vencidos' => 0, 'devolvidos' => 0];

    $sql = "
        SELECT e.*, l.titulo, l.autor, l.capa
        FROM emprestimos e
        JOIN livros l ON l.id = e.id_livro
        WHERE e.id_usuario = ?
    ";
    $params = [$_SESSION['usuario_id']];

    if (in_array($statusFiltro, $statusPermitidos, true)) {
        $sql .= " AND e.status = ?";
        $params[] = $statusFiltro;
    }

    if ($busca !== '') {
        $sql .= " AND (l.titulo LIKE ? OR l.autor LIKE ?)";
        $termo = "%$busca%";
        $params[] = $termo;
        $params[] = $termo;
    }

    $sql .= " ORDER BY e.data_emprestimo DESC, e.id DESC";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $emprestimos = $stmt->fetchAll();
    $renovacoesPendentes = libraflowRenovacoesPendentesDoUsuario($conn, (int) $_SESSION['usuario_id']);
} catch (PDOException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    $emprestimos = [];
    $renovacoesPendentes = [];
    $resumo = ['total' => 0, 'ativos' => 0, 'vencidos' => 0, 'devolvidos' => 0];
    $erro = 'Não foi possível carregar seus empréstimos.';
}

$resumo = [
    'total' => (int) ($resumo['total'] ?? 0),
    'ativos' => (int) ($resumo['ativos'] ?? 0),
    'vencidos' => (int) ($resumo['vencidos'] ?? 0),
    'devolvidos' => (int) ($resumo['devolvidos'] ?? 0),
];

$statusInfo = [
    'A' => ['Ativo', 'status-ativo'],
    'D' => ['Devolvido', 'status-devolvido'],
    'V' => ['Vencido', 'status-vencido'],
];

function libraflowPrazoEmprestimo(array $emprestimo): array
{
    if (($emprestimo['status'] ?? '') === 'D' || empty($emprestimo['data_prevista_devolucao'])) {
        return ['Finalizado', 'prazo-ok'];
    }

    $hoje = new DateTimeImmutable('today');
    $prazo = new DateTimeImmutable($emprestimo['data_prevista_devolucao']);
    $dias = (int) $hoje->diff($prazo)->format('%r%a');

    if (($emprestimo['status'] ?? '') === 'V' || $dias < 0) {
        $atraso = abs($dias);
        return ['Vencido há ' . $atraso . ' dia' . ($atraso === 1 ? '' : 's'), 'prazo-critico'];
    }

    if ($dias === 0) {
        return ['Vence hoje', 'prazo-alerta'];
    }

    if ($dias <= 2) {
        return ['Vence em ' . $dias . ' dia' . ($dias === 1 ? '' : 's'), 'prazo-alerta'];
    }

    return ['Em dia', 'prazo-ok'];
}

function libraflowPodeRenovar(array $emprestimo): bool
{
    if (($emprestimo['status'] ?? '') !== 'A' || empty($emprestimo['data_prevista_devolucao'])) {
        return false;
    }

    $hoje = new DateTimeImmutable('today');
    $prazo = new DateTimeImmutable($emprestimo['data_prevista_devolucao']);
    $dias = (int) $hoje->diff($prazo)->format('%r%a');

    return $dias >= 0 && $dias <= 2;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="/LibraFlow/public/admin/darkmode-btn.css">
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
    <link rel="stylesheet" href="/LibraFlow/public/catalogo/usuario-flow.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <nav>
        <div class="logo-nav">
            <img src="/LibraFlow/public/catalogo/imgs/Logo-LibraFlow.png" alt="Logo LibraFlow">
            <span>LibraFlow</span>
        </div>
        <div class="links-nav">
            <ul>
                <li><a href="/LibraFlow/public/usuario/index.php"><i class="fas fa-house" aria-hidden="true"></i> Inicio</a></li>
                <li><a href="/LibraFlow/public/usuario/perfil.php"><i class="fas fa-user" aria-hidden="true"></i> Perfil</a></li>
                <li><a href="/LibraFlow/public/catalogo/catalogo.php"><i class="fas fa-book-open" aria-hidden="true"></i> Catalogo</a></li>
                <li><a class="ativo" href="/LibraFlow/public/catalogo/meus_emprestimos.php"><i class="fas fa-bookmark" aria-hidden="true"></i> Meus emprestimos</a></li>
                <li><a href="/LibraFlow/public/auth/logout.php"><i class="fas fa-right-from-bracket" aria-hidden="true"></i> Sair</a></li>
            </ul>
        </div>
        <div class="user">
            <span><i class="fas fa-user" aria-hidden="true"></i> <?= htmlspecialchars($_SESSION['usuario_nome']) ?></span>
        </div>
    </nav>

    <header>
        <div class="text-header">
            <h1>Meus Empréstimos</h1>
            <p>Acompanhe os livros que estão com você e as datas de devolução.</p>
        </div>
    </header>

    <main class="lista-emprestimos">
        <?php if ($sucessoAcao): ?>
            <div class="alerta alerta-sucesso"><?= htmlspecialchars($sucessoAcao) ?></div>
        <?php endif; ?>

        <?php if ($erroAcao): ?>
            <div class="alerta alerta-erro"><?= htmlspecialchars($erroAcao) ?></div>
        <?php endif; ?>

        <section class="emprestimos-filtros">
            <div class="filtro-status">
                <a href="meus_emprestimos.php<?= $busca !== '' ? '?busca=' . urlencode($busca) : '' ?>"
                   class="<?= $statusFiltro === '' ? 'ativo' : '' ?>">
                    Todos <span><?= $resumo['total'] ?></span>
                </a>
                <a href="meus_emprestimos.php?status=A<?= $busca !== '' ? '&busca=' . urlencode($busca) : '' ?>"
                   class="<?= $statusFiltro === 'A' ? 'ativo' : '' ?>">
                    Ativos <span><?= $resumo['ativos'] ?></span>
                </a>
                <a href="meus_emprestimos.php?status=V<?= $busca !== '' ? '&busca=' . urlencode($busca) : '' ?>"
                   class="<?= $statusFiltro === 'V' ? 'ativo' : '' ?>">
                    Vencidos <span><?= $resumo['vencidos'] ?></span>
                </a>
                <a href="meus_emprestimos.php?status=D<?= $busca !== '' ? '&busca=' . urlencode($busca) : '' ?>"
                   class="<?= $statusFiltro === 'D' ? 'ativo' : '' ?>">
                    Devolvidos <span><?= $resumo['devolvidos'] ?></span>
                </a>
            </div>

            <form method="GET" action="meus_emprestimos.php" class="busca-emprestimos">
                <?php if (in_array($statusFiltro, $statusPermitidos, true)): ?>
                    <input type="hidden" name="status" value="<?= htmlspecialchars($statusFiltro) ?>">
                <?php endif; ?>
                <input type="text" name="busca" placeholder="Buscar por titulo ou autor" value="<?= htmlspecialchars($busca) ?>">
                <button type="submit"><i class="fas fa-magnifying-glass" aria-hidden="true"></i> Buscar</button>
                <?php if ($busca !== '' || $statusFiltro !== ''): ?>
                    <a href="meus_emprestimos.php" class="limpar-filtros">Limpar</a>
                <?php endif; ?>
            </form>
        </section>

        <?php if (!empty($erro)): ?>
            <div class="vazio"><?= htmlspecialchars($erro) ?></div>
        <?php elseif (empty($emprestimos)): ?>
            <div class="vazio">Nenhum emprestimo encontrado para os filtros selecionados.</div>
        <?php else: ?>
            <?php foreach ($emprestimos as $emprestimo): ?>
                <?php $info = $statusInfo[$emprestimo['status']] ?? ['Desconhecido', 'status-devolvido']; ?>
                <?php $prazo = libraflowPrazoEmprestimo($emprestimo); ?>
                <?php $renovacaoPendente = isset($renovacoesPendentes[$emprestimo['id']]); ?>
                <article class="emprestimo-card">
                    <?php if ($emprestimo['capa']): ?>
                        <img src="/LibraFlow/public/catalogo/capas/<?= htmlspecialchars($emprestimo['capa']) ?>" alt="Capa de <?= htmlspecialchars($emprestimo['titulo']) ?>">
                    <?php else: ?>
                        <div class="sem-capa-lista">Livro</div>
                    <?php endif; ?>

                    <div class="emprestimo-info">
                        <h2><?= htmlspecialchars($emprestimo['titulo']) ?></h2>
                        <p><?= htmlspecialchars($emprestimo['autor']) ?></p>
                        <p>
                            Retirado em <?= date('d/m/Y', strtotime($emprestimo['data_emprestimo'])) ?>
                            <?php if (!empty($emprestimo['data_prevista_devolucao'])): ?>
                                &middot; devolver até <?= date('d/m/Y', strtotime($emprestimo['data_prevista_devolucao'])) ?>
                            <?php endif; ?>
                        </p>
                    </div>

                    <div class="emprestimo-tags">
                        <span class="status <?= $info[1] ?>"><?= $info[0] ?></span>
                        <?php if ($emprestimo['status'] !== 'D'): ?>
                            <span class="prazo-tag <?= $prazo[1] ?>"><?= htmlspecialchars($prazo[0]) ?></span>
                        <?php endif; ?>
                        <?php if ($renovacaoPendente): ?>
                            <span class="renovacao-pendente">Em analise</span>
                        <?php elseif (libraflowPodeRenovar($emprestimo)): ?>
                            <form method="POST" action="meus_emprestimos.php" class="renovar-form">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                <input type="hidden" name="acao" value="renovar">
                                <input type="hidden" name="id_emprestimo" value="<?= (int) $emprestimo['id'] ?>">
                                <button type="submit">
                                    <i class="fas fa-rotate-right" aria-hidden="true"></i>
                                    Renovar
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- Botão Dark Mode -->
        <button id="themeToggle" class="theme-toggle-float" aria-label="Alternar tema claro/escuro">
            <span id="themeIcon"><i class="fas fa-moon" aria-hidden="true"></i></span>
            <span id="themeLabel">Escuro</span>
        </button>
    </main>

    <script src="/LibraFlow/public/catalogo/darkmode.js"></script>
</body>
</html>
