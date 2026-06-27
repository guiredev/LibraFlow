<?php
/*
 * MAPA RAPIDO DO ARQUIVO
 * Local: public/admin/detalhe_livro.php
 * Funcao: Mostra dados, historico e situacao de um livro do acervo.
 */
require $_SERVER['DOCUMENT_ROOT'] . '/LibraFlow/app/config/auth_check.php';
if ($_SESSION['usuario_tipo'] !== 'D') { header('Location: /LibraFlow/public/usuario/index.php'); exit; }
require $_SERVER['DOCUMENT_ROOT'] . '/LibraFlow/app/config/conexao.php';

$id = intval($_GET['id'] ?? 0);
$stmt = $conn->prepare("SELECT l.*, c.nome AS categoria_nome FROM livros l LEFT JOIN categorias c ON c.id = l.id_categoria WHERE l.id = ?");
$stmt->execute([$id]);
$livro = $stmt->fetch();
if (!$livro) { header('Location: listar_livros.php'); exit; }

$indicadores = $conn->prepare("SELECT COUNT(*) AS total, SUM(status IN ('A','V')) AS abertos, SUM(status = 'V') AS atrasos, SUM(status = 'D') AS devolvidos FROM emprestimos WHERE id_livro = ?");
$indicadores->execute([$id]);
$indicadores = $indicadores->fetch();

$stmt = $conn->prepare("SELECT e.*, u.nome, u.email FROM emprestimos e JOIN usuarios u ON u.id = e.id_usuario WHERE e.id_livro = ? ORDER BY e.data_emprestimo DESC, e.id DESC");
$stmt->execute([$id]);
$historico = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhe do Livro | LibraFlow</title>
    <link rel="stylesheet" href="style.css"><link rel="stylesheet" href="darkmode-btn.css">
    <link href="https://fonts.googleapis.com/css2?family=Lora:wght@400;700&family=Source+Sans+3:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <aside><div class="logo-aside"><span>LibraFlow</span></div><ul>
        <li><a href="/LibraFlow/public/admin/Admin.php"><i class="fas fa-house nav-icon"></i> Início</a></li>
        <li><a href="/LibraFlow/public/admin/listar_livros.php" class="ativo"><i class="fas fa-book-open nav-icon"></i> Livros</a></li>
        <li><a href="/LibraFlow/public/admin/cadastrar_livro.php"><i class="fas fa-plus nav-icon"></i> Cadastrar Livro</a></li>
        <li><a href="/LibraFlow/public/admin/usuarios.php"><i class="fas fa-users nav-icon"></i> Usuários</a></li>
        <li><a href="/LibraFlow/public/admin/emprestimos.php"><i class="fas fa-clipboard-list nav-icon"></i> Empréstimos</a></li>
    </ul></aside>
    <nav><span style="font-family:'Lora',serif;font-size:2rem;color:var(--text-title);">Detalhe do Livro</span></nav>
    <header><h1><?= htmlspecialchars($livro['titulo']) ?></h1><p><?= htmlspecialchars($livro['autor']) ?></p></header>
    <main>
        <section class="detalhe-grid">
            <article class="detalhe-card"><h2>Acervo</h2><p><strong>Categoria:</strong> <?= htmlspecialchars($livro['categoria_nome'] ?: '-') ?></p><p><strong>ISBN:</strong> <?= htmlspecialchars($livro['isbn'] ?: '-') ?></p><p><strong>Ano:</strong> <?= htmlspecialchars($livro['ano'] ?: '-') ?></p><p><strong>Disponíveis:</strong> <?= (int) $livro['quantidade'] ?></p></article>
            <article class="detalhe-card"><h2>Uso</h2><p><strong>Total:</strong> <?= (int) $indicadores['total'] ?></p><p><strong>Emprestados:</strong> <?= (int) $indicadores['abertos'] ?></p><p><strong>Atrasos:</strong> <?= (int) $indicadores['atrasos'] ?></p><p><strong>Devolvidos:</strong> <?= (int) $indicadores['devolvidos'] ?></p></article>
        </section>
        <section class="historico"><h2>Histórico de retiradas</h2><table><thead><tr><th>Aluno</th><th>Retirada</th><th>Prevista</th><th>Devolução</th><th>Status</th></tr></thead><tbody>
        <?php foreach ($historico as $item): ?><tr><td><?= htmlspecialchars($item['nome']) ?></td><td><?= date('d/m/Y', strtotime($item['data_emprestimo'])) ?></td><td><?= $item['data_prevista_devolucao'] ? date('d/m/Y', strtotime($item['data_prevista_devolucao'])) : '-' ?></td><td><?= $item['data_devolucao'] ? date('d/m/Y', strtotime($item['data_devolucao'])) : '-' ?></td><td><?= htmlspecialchars($item['status']) ?></td></tr><?php endforeach; ?>
        <?php if (!$historico): ?><tr><td colspan="5">Nenhuma retirada registrada.</td></tr><?php endif; ?>
        </tbody></table></section>
        <a class="btn-novo" href="editar_livro.php?id=<?= $livro['id'] ?>">Editar livro</a> <a class="btn-novo" href="listar_livros.php">Voltar</a>
    </main>
    <button id="themeToggle" class="theme-toggle-float"><span id="themeIcon"><i class="fas fa-moon"></i></span><span id="themeLabel">Escuro</span></button><script src="darkmode.js"></script>
</body>
</html>