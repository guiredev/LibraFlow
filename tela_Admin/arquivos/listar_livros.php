<?php
// tela_Admin/arquivos/listar_livros.php

require $_SERVER['DOCUMENT_ROOT'] . '/LibraFlow/cadastros_e_logins/configs/auth_check.php';
if ($_SESSION['usuario_tipo'] !== 'D') { header('Location: /LibraFlow/Tela_de_usuario/arquivos/index.html'); exit; }
require $_SERVER['DOCUMENT_ROOT'] . '/LibraFlow/cadastros_e_logins/configs/conexao.php';

// Excluir
if (isset($_GET['excluir'])) {
    $id = intval($_GET['excluir']);
    $l = $conn->prepare("SELECT capa FROM livros WHERE id = ?");
    $l->execute([$id]);
    $dados = $l->fetch();
    if ($dados && $dados['capa']) {
        $f = $_SERVER['DOCUMENT_ROOT'] . '/LibraFlow/catalogo/capas/' . $dados['capa'];
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
    <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400..700;1,400..700&family=Source+Sans+3:ital,wght@0,200..900;1,200..900&display=swap" rel="stylesheet">
</head>
<body>
    <aside>
        <div class="logo-aside"><span>LibraFlow</span></div>
        <ul>
            <li><a href="Admin.php">🏠 Início</a></li>
            <li><a href="listar_livros.php" class="ativo">📚 Livros</a></li>
            <li><a href="cadastrar_livro.php">➕ Cadastrar Livro</a></li>
            <li><a href="usuarios.php">👥 Usuários</a></li>
            <li><a href="emprestimos.php">📋 Empréstimos</a></li>
            <li><a href="visitas.php">Visitas</a></li>
            <div class="sidebar-down">
                <li><a href="/LibraFlow/cadastros_e_logins/logout/logout.php">🚪 Sair</a></li>
            </div>
        </ul>
    </aside>
    <nav>
        <span style="font-family:'Lora',serif;font-size:2rem;color:#283618;">Acervo de Livros</span>
        <div class="right">
            <span style="font-size:1.4rem;color:#606C38;">👤 <?= htmlspecialchars($_SESSION['usuario_nome']) ?></span>
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
            <h2>📚 <?= count($livros) ?> livro<?= count($livros) !== 1 ? 's' : '' ?> encontrado<?= count($livros) !== 1 ? 's' : '' ?></h2>
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
                                <img src="/LibraFlow/catalogo/capas/<?= htmlspecialchars($livro['capa']) ?>" class="capa-thumb" alt="Capa">
                            <?php else: ?>
                                <div class="sem-capa">📖</div>
                            <?php endif; ?>
                        </td>
                        <td><strong><?= htmlspecialchars($livro['titulo']) ?></strong></td>
                        <td><?= htmlspecialchars($livro['autor']) ?></td>
                        <td><?= $livro['categoria_nome'] ? '<span class="badge-cat">'.htmlspecialchars($livro['categoria_nome']).'</span>' : '—' ?></td>
                        <td><?= $livro['ano'] ?: '—' ?></td>
                        <td><?= $livro['quantidade'] ?></td>
                        <td>
                            <div class="acoes">
                                <a href="editar_livro.php?id=<?= $livro['id'] ?>" class="btn-editar">Editar</a>
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
</body>
</html>
