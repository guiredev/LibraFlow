<?php
/*
 * MAPA RAPIDO DO ARQUIVO
 * Local: public/usuario/index.php
 * Funcao: Pagina inicial do usuario/aluno apos login.
 */
// public/usuario/index.php

require $_SERVER['DOCUMENT_ROOT'] . '/LibraFlow/app/config/auth_check.php';
require $_SERVER['DOCUMENT_ROOT'] . '/LibraFlow/app/config/conexao.php';

if ($_SESSION['usuario_tipo'] === 'D') {
    header('Location: /LibraFlow/public/admin/Admin.php');
    exit;
}

$erro = '';

try {
    $conn->prepare("
        UPDATE emprestimos
        SET status = 'V'
        WHERE id_usuario = ?
          AND status = 'A'
          AND data_prevista_devolucao < CURDATE()
    ")->execute([$_SESSION['usuario_id']]);

    $stmt = $conn->prepare("SELECT nome, email, telefone, rm, endereco, idade FROM usuarios WHERE id = ?");
    $stmt->execute([$_SESSION['usuario_id']]);
    $usuario = $stmt->fetch();

    $stmt = $conn->prepare("SELECT COUNT(*) FROM emprestimos WHERE id_usuario = ? AND status = 'D'");
    $stmt->execute([$_SESSION['usuario_id']]);
    $totalLidos = $stmt->fetchColumn();

    $stmt = $conn->prepare("SELECT COUNT(*) FROM emprestimos WHERE id_usuario = ? AND status IN ('A', 'V')");
    $stmt->execute([$_SESSION['usuario_id']]);
    $livrosComigo = $stmt->fetchColumn();

    $stmt = $conn->prepare("SELECT COUNT(*) FROM emprestimos WHERE id_usuario = ? AND status = 'V'");
    $stmt->execute([$_SESSION['usuario_id']]);
    $pendencias = $stmt->fetchColumn();

    $stmt = $conn->prepare("
        SELECT e.data_prevista_devolucao, l.titulo
        FROM emprestimos e
        JOIN livros l ON l.id = e.id_livro
        WHERE e.id_usuario = ?
          AND e.status IN ('A', 'V')
        ORDER BY e.data_prevista_devolucao ASC
        LIMIT 1
    ");
    $stmt->execute([$_SESSION['usuario_id']]);
    $proximoPrazo = $stmt->fetch();

    $stmt = $conn->prepare("
        SELECT e.*, l.titulo, l.autor, l.capa
        FROM emprestimos e
        JOIN livros l ON l.id = e.id_livro
        WHERE e.id_usuario = ?
        ORDER BY e.data_emprestimo DESC, e.id DESC
        LIMIT 4
    ");
    $stmt->execute([$_SESSION['usuario_id']]);
    $livrosRecentes = $stmt->fetchAll();
} catch (PDOException $e) {
    $usuario = [
        'nome' => $_SESSION['usuario_nome'] ?? 'Aluno',
        'email' => $_SESSION['usuario_email'] ?? '',
        'telefone' => '',
        'rm' => '',
        'endereco' => '',
        'idade' => '',
    ];
    $totalLidos = 0;
    $livrosComigo = 0;
    $pendencias = 0;
    $proximoPrazo = null;
    $livrosRecentes = [];
    $erro = 'Nao foi possivel carregar todos os dados do painel.';
}

$camposCadastro = ['telefone', 'rm', 'endereco', 'idade'];
$camposPreenchidos = 0;
foreach ($camposCadastro as $campo) {
    if (!empty($usuario[$campo])) {
        $camposPreenchidos++;
    }
}
$cadastroCompleto = count($camposCadastro) > 0
    ? (int) round(($camposPreenchidos / count($camposCadastro)) * 100)
    : 0;

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
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="/LibraFlow/public/admin/darkmode-btn.css">
    <title>Painel do Aluno | LibraFlow</title>
    <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400..700;1,400..700&family=Source+Sans+3:ital,wght@0,200..900;1,200..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <nav>
        <div class="logo-nav">
            <img src="imgs/Logo-LibraFlow.png" alt="Logo LibraFlow">
            <span>LibraFlow</span>
        </div>
        <div class="links-nav">
            <ul>
                <li><a class="ativo" href="/LibraFlow/public/usuario/index.php"><i class="fas fa-house" aria-hidden="true"></i> Inicio</a></li>
                <li><a href="/LibraFlow/public/catalogo/catalogo.php"><i class="fas fa-book-open" aria-hidden="true"></i> Catalogo</a></li>
                <li><a href="/LibraFlow/public/catalogo/meus_emprestimos.php"><i class="fas fa-bookmark" aria-hidden="true"></i> Meus livros</a></li>
                <li><a href="/LibraFlow/public/auth/logout.php"><i class="fas fa-right-from-bracket" aria-hidden="true"></i> Sair</a></li>
            </ul>
        </div>
        <div class="user-badge"><?= htmlspecialchars(substr($usuario['nome'], 0, 1)) ?></div>
    </nav>

    <header>
        <section class="perfil painel">
            <div class="avatar" aria-hidden="true"><?= htmlspecialchars(substr($usuario['nome'], 0, 1)) ?></div>
            <div class="text-header">
                <span class="eyebrow">Painel do aluno</span>
                <h1>Olá, <?= htmlspecialchars($usuario['nome']) ?></h1>
                <p>Acompanhe seus livros, prazos e pendências em um só lugar.</p>
                <div class="header-actions">
                    <a href="/LibraFlow/public/catalogo/catalogo.php" class="btn-primary"><i class="fas fa-magnifying-glass" aria-hidden="true"></i> Buscar livro</a>
                    <a href="/LibraFlow/public/catalogo/meus_emprestimos.php" class="btn-secondary"><i class="fas fa-list-check" aria-hidden="true"></i> Meus emprestimos</a>
                </div>
            </div>
        </section>
        <aside class="dados-aluno painel">
            <div class="painel-topo">
                <h2>Cadastro</h2>
                <span><?= $cadastroCompleto ?>%</span>
            </div>
            <div class="progress-bar"><span style="width: <?= $cadastroCompleto ?>%"></span></div>
            <dl>
                <div><dt>RM</dt><dd><?= htmlspecialchars($usuario['rm'] ?: '-') ?></dd></div>
                <div><dt>Telefone</dt><dd><?= htmlspecialchars($usuario['telefone'] ?: '-') ?></dd></div>
                <div><dt>Idade</dt><dd><?= htmlspecialchars($usuario['idade'] ?: '-') ?></dd></div>
                <div><dt>Endereco</dt><dd><?= htmlspecialchars($usuario['endereco'] ?: '-') ?></dd></div>
            </dl>
        </aside>
    </header>

    <main>
        <?php if ($erro): ?>
            <div class="alerta alerta-erro"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>

        <section class="estatisticas">
            <article class="stat-card stat-lidos">
                <div class="stat-icon"><i class="fas fa-circle-check" aria-hidden="true"></i></div>
                <h3>Livros devolvidos</h3>
                <span><?= $totalLidos ?></span>
            </article>
            <article class="stat-card stat-comigo">
                <div class="stat-icon"><i class="fas fa-book" aria-hidden="true"></i></div>
                <h3>Comigo agora</h3>
                <span><?= $livrosComigo ?></span>
            </article>
            <article class="stat-card stat-pendencias">
                <div class="stat-icon"><i class="fas fa-triangle-exclamation" aria-hidden="true"></i></div>
                <h3>Pendencias</h3>
                <span><?= $pendencias ?></span>
            </article>
        </section>

        <div class="conteudo-grid">
            <section class="painel acoes-rapidas">
                <div class="painel-topo">
                    <h2>Acoes rapidas</h2>
                </div>
                <a href="/LibraFlow/public/catalogo/catalogo.php">
                    <i class="fas fa-magnifying-glass" aria-hidden="true"></i>
                    <span><strong>Explorar catalogo</strong><small>Encontre novos titulos disponiveis</small></span>
                </a>
                <a href="/LibraFlow/public/catalogo/meus_emprestimos.php">
                    <i class="fas fa-bookmark" aria-hidden="true"></i>
                    <span><strong>Meus emprestimos</strong><small>Veja prazos, status e historico</small></span>
                </a>
                <div class="prazo-card">
                    <span>Proximo prazo</span>
                    <?php if ($proximoPrazo): ?>
                        <strong><?= date('d/m/Y', strtotime($proximoPrazo['data_prevista_devolucao'])) ?></strong>
                        <small><?= htmlspecialchars($proximoPrazo['titulo']) ?></small>
                    <?php else: ?>
                        <strong>Sem prazo aberto</strong>
                        <small>Nenhum livro ativo no momento</small>
                    <?php endif; ?>
                </div>
            </section>

            <section class="painel livros-recentes">
                <div class="painel-topo">
                    <h2>Livros recentes</h2>
                    <a href="/LibraFlow/public/catalogo/meus_emprestimos.php">Ver todos</a>
                </div>
                <div class="lista-livros">
                    <?php if (empty($livrosRecentes)): ?>
                        <div class="vazio">Voce ainda nao possui emprestimos. Explore o catalogo para comecar.</div>
                    <?php else: ?>
                        <?php foreach ($livrosRecentes as $livro): ?>
                            <?php $status = $statusInfo[$livro['status']] ?? ['Desconhecido', 'status-devolvido']; ?>
                            <article class="livro-card">
                                <?php if ($livro['capa']): ?>
                                    <img src="/LibraFlow/public/catalogo/capas/<?= htmlspecialchars($livro['capa']) ?>" alt="Capa de <?= htmlspecialchars($livro['titulo']) ?>">
                                <?php else: ?>
                                    <div class="sem-capa"><i class="fas fa-book" aria-hidden="true"></i></div>
                                <?php endif; ?>
                                <div class="livro-info">
                                    <h3><?= htmlspecialchars($livro['titulo']) ?></h3>
                                    <p><?= htmlspecialchars($livro['autor']) ?></p>
                                    <small>Retirado em <?= date('d/m/Y', strtotime($livro['data_emprestimo'])) ?></small>
                                </div>
                                <span class="status <?= $status[1] ?>"><?= $status[0] ?></span>
                            </article>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </main>

    <footer>
        <p>&copy; 2024 LibraFlow. Todos os direitos reservados.</p>
    </footer>

    <!-- Botão Dark Mode -->
    <button id="themeToggle" class="theme-toggle-float" aria-label="Alternar tema claro/escuro">
        <span id="themeIcon"><i class="fas fa-moon" aria-hidden="true"></i></span>
        <span id="themeLabel">Escuro</span>
    </button>

    <script src="/LibraFlow/public/usuario/darkmode.js"></script>
</body>
</html>
