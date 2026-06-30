<?php
/*
 * MAPA RAPIDO DO ARQUIVO
 * Local: public/admin/listar_livros.php
 * Funcao: Listagem administrativa de livros com acesso a editar/excluir.
 */
// public/admin/listar_livros.php

require $_SERVER['DOCUMENT_ROOT'] . '/LibraFlow/app/config/auth_check.php';
if ($_SESSION['usuario_tipo'] !== 'D') { header('Location: /LibraFlow/public/usuario/index.php'); exit; }
require $_SERVER['DOCUMENT_ROOT'] . '/LibraFlow/app/config/conexao.php';

// Endpoint AJAX para carregar livros
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    $stmt = $conn->query("
        SELECT id, titulo, autor, quantidade
        FROM livros
        WHERE quantidade > 0
        ORDER BY titulo ASC
    ");
    echo json_encode($stmt->fetchAll());
    exit;
}

// Excluir
if (isset($_GET['excluir'])) {
    $id = intval($_GET['excluir']);
    $l = $conn->prepare("SELECT capa FROM livros WHERE id = ?");
    $l->execute([$id]);
    $dados = $l->fetch();
    if ($dados && $dados['capa']) {
        $f = $_SERVER['DOCUMENT_ROOT'] . '/LibraFlow/public/catalogo/capas/' . $dados['capa'];
        if (file_exists($f)) unlink($f);
    }
    $conn->prepare("DELETE FROM livros WHERE id = ?")->execute([$id]);
    header('Location: listar_livros.php?deletado=1');
    exit;
}

$busca     = trim($_GET['busca']     ?? '');
$filtrocat = intval($_GET['categoria'] ?? 0);

$sql    = "SELECT l.*, c.nome AS categoria_nome FROM livros l LEFT JOIN categorias c ON c.id = l.id_categoria WHERE 1=1";
$params = [];
if ($busca)     { $sql .= " AND (l.titulo LIKE ? OR l.autor LIKE ?)"; $params[] = "%$busca%"; $params[] = "%$busca%"; }
if ($filtrocat) { $sql .= " AND l.id_categoria = ?"; $params[] = $filtrocat; }
$sql .= " ORDER BY l.criado_em DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$livros = $stmt->fetchAll();

$categorias = $conn->query("SELECT id, nome FROM categorias ORDER BY nome")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Livros | LibraFlow Admin</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="darkmode-btn.css">
    <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400..700;1,400..700&family=Source+Sans+3:ital,wght@0,200..900;1,200..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <aside>
        <div class="logo-aside"><span>LibraFlow</span></div>
        <ul>
            <li><a href="/LibraFlow/public/admin/Admin.php"><i class="fas fa-house nav-icon" aria-hidden="true"></i> Início</a></li>
            <li><a href="/LibraFlow/public/admin/listar_livros.php" class="ativo"><i class="fas fa-book-open nav-icon" aria-hidden="true"></i> Livros</a></li>
            <li><a href="/LibraFlow/public/admin/cadastrar_livro.php"><i class="fas fa-plus nav-icon" aria-hidden="true"></i> Cadastrar Livro</a></li>
            <li><a href="/LibraFlow/public/admin/usuarios.php"><i class="fas fa-users nav-icon" aria-hidden="true"></i> Usuários</a></li>
            <li><a href="/LibraFlow/public/admin/emprestimos.php"><i class="fas fa-clipboard-list nav-icon" aria-hidden="true"></i> Empréstimos</a></li>
            <li><a href="/LibraFlow/public/admin/visitas.php"><i class="fas fa-clock nav-icon" aria-hidden="true"></i> Visitas</a></li>
            <li><a href="relatorios/index.php"><i class="fas fa-chart-line nav-icon" aria-hidden="true"></i> Relatórios</a></li>
            <div class="sidebar-down">
                <li><a href="/LibraFlow/public/auth/logout.php"><i class="fas fa-right-from-bracket nav-icon" aria-hidden="true"></i> Sair</a></li>
            </div>
        </ul>
    </aside>
    <nav>
        <span style="font-family:'Lora',serif;font-size:2rem;color:#283618;">Acervo de Livros</span>
        <div class="right">
            <span style="font-size:1.4rem;color:#606C38;"><i class="fas fa-user" aria-hidden="true"></i> <?= htmlspecialchars($_SESSION['usuario_nome']) ?></span>
        </div>
    </nav>
    <header>
        <h1>Acervo de Livros</h1>
        <p>Gerencie todos os livros cadastrados no sistema.</p>
    </header>
    <main>
        <?php if (isset($_GET['deletado'])): ?>
            <div class="alerta alerta-sucesso">Livro excluído com sucesso.</div>
        <?php endif; ?>

        <div class="topo-lista">
            <h2><i class="fas fa-book-open" aria-hidden="true"></i> <?= count($livros) ?> livro<?= count($livros) !== 1 ? 's' : '' ?> encontrado<?= count($livros) !== 1 ? 's' : '' ?></h2>
            <a href="cadastrar_livro.php" class="btn-novo">+ Novo Livro</a>
        </div>

        <form method="GET" class="filtros">
            <input type="text" name="busca" placeholder="Buscar título ou autor..." value="<?= htmlspecialchars($busca) ?>">
            <select name="categoria">
                <option value="">Todas as categorias</option>
                <?php foreach ($categorias as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= $filtrocat == $cat['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn-filtrar">Filtrar</button>
            <a href="listar_livros.php" style="font-size:1.3rem;color:#BC6C25;font-weight:bold;text-decoration:none;padding:0.8rem;">Limpar</a>
        </form>

        <div class="tabela-wrapper">
            <?php if (empty($livros)): ?>
                <div class="vazio-tabela">Nenhum livro encontrado.</div>
            <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Capa</th>
                        <th>Título</th>
                        <th>Autor</th>
                        <th>Categoria</th>
                        <th>Ano</th>
                        <th>Qtd.</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($livros as $livro): ?>
                    <tr>
                        <td>
                            <?php if ($livro['capa']): ?>
                                <img src="/LibraFlow/public/catalogo/capas/<?= htmlspecialchars($livro['capa']) ?>" class="capa-thumb" alt="Capa">
                            <?php else: ?>
                                <div class="sem-capa"><i class="fas fa-book" aria-hidden="true"></i></div>
                            <?php endif; ?>
                        </td>
                        <td><strong><?= htmlspecialchars($livro['titulo']) ?></strong></td>
                        <td><?= htmlspecialchars($livro['autor']) ?></td>
                        <td><?= $livro['categoria_nome'] ? '<span class="badge-cat">'.htmlspecialchars($livro['categoria_nome']).'</span>' : '—' ?></td>
                        <td><?= $livro['ano'] ?: '—' ?></td>
                        <td><?= $livro['quantidade'] ?></td>
                        <td>
                            <div class="acoes">
                                <a href="detalhe_livro.php?id=<?= $livro['id'] ?>" class="btn-editar">Detalhes</a><a href="editar_livro.php?id=<?= $livro['id'] ?>" class="btn-editar">Editar</a>
                                <a href="listar_livros.php?excluir=<?= $livro['id'] ?>" class="btn-excluir"
                                   onclick="return confirm('Excluir este livro permanentemente?')">Excluir</a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </main>

    <!-- Botão Dark Mode -->
    <button id="themeToggle" class="theme-toggle-float" aria-label="Alternar tema claro/escuro">
        <span id="themeIcon"><i class="fas fa-moon" aria-hidden="true"></i></span>
        <span id="themeLabel">Escuro</span>
    </button>

    <script src="darkmode.js"></script>
</body>
</html>

