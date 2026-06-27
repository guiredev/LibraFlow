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
    $livrosRecentes = [];
    $erro = 'Nao foi possivel carregar todos os dados do painel.';
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
        </div>
        <div class="links-nav">
            <ul>
                <li><a href="/LibraFlow/public/usuario/index.php">Inicio</a></li>
                <li><a href="/LibraFlow/public/catalogo/meus_emprestimos.php">Meus Livros</a></li>
                <li><a href="/LibraFlow/public/catalogo/catalogo.php">Catalogo</a></li>
                <li><a href="/LibraFlow/public/auth/logout.php">Sair</a></li>
            </ul>
        </div>
        <div class="user-badge"><?= htmlspecialchars(substr($usuario['nome'], 0, 1)) ?></div>
    </nav>

    <header>
        <div class="perfil">
            <div class="avatar"><?= htmlspecialchars(substr($usuario['nome'], 0, 1)) ?></div>
            <div class="text-header">
                <h1>Bem-vindo de volta, <span><?= htmlspecialchars($usuario['nome']) ?></span>!</h1>
                <p>Acompanhe seus emprestimos, prazos e informacoes de cadastro.</p>
            </div>
        </div>
        <div class="dados-aluno">
            <p><strong>RM:</strong> <?= htmlspecialchars($usuario['rm'] ?: '-') ?></p>
            <p><strong>Telefone:</strong> <?= htmlspecialchars($usuario['telefone'] ?: '-') ?></p>
            <p><strong>Idade:</strong> <?= htmlspecialchars($usuario['idade'] ?: '-') ?></p>
            <p><strong>Endereco:</strong> <?= htmlspecialchars($usuario['endereco'] ?: '-') ?></p>
        </div>
    </header>

    <main>
        <?php if ($erro): ?>
            <div class="alerta alerta-erro"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>

        <section class="estatisticas">
            <div class="estatistica-1">
                <h3>Lidos</h3>
                <span><?= $totalLidos ?></span>
            </div>
            <div class="estatistica-2">
                <h3>Livros Comigo</h3>
                <span><?= $livrosComigo ?></span>
            </div>
            <div class="estatistica-3">
                <h3>Pendencias</h3>
                <span><?= $pendencias ?></span>
            </div>
        </section>

        <section class="acoes-rapidas">
            <a href="/LibraFlow/public/catalogo/catalogo.php">Buscar livros</a>
            <a href="/LibraFlow/public/catalogo/meus_emprestimos.php">Ver emprestimos</a>
        </section>

        <section class="livros-mais-lidos">
            <h2>Livros Recentes</h2>
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
                                <div class="sem-capa">Livro</div>
                            <?php endif; ?>
                            <div>
                                <h3><?= htmlspecialchars($livro['titulo']) ?></h3>
                                <p><?= htmlspecialchars($livro['autor']) ?></p>
                                <p>Retirado em <?= date('d/m/Y', strtotime($livro['data_emprestimo'])) ?></p>
                            </div>
                            <span class="status <?= $status[1] ?>"><?= $status[0] ?></span>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
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
