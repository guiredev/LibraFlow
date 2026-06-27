<?php
/*
 * MAPA RAPIDO DO ARQUIVO
 * Local: public/catalogo/catalogo.php
 * Funcao: Lista livros do catalogo com busca/categoria e botoes para detalhes ou solicitacao.
 */
// catalogo/catalogo.php

require $_SERVER['DOCUMENT_ROOT'] . '/LibraFlow/app/config/auth_check.php';
require $_SERVER['DOCUMENT_ROOT'] . '/LibraFlow/app/config/conexao.php';

$busca     = trim($_GET['txtBusca']    ?? '');
$filtrocat = intval($_GET['categoria'] ?? 0);

$sql    = "SELECT l.*, c.nome AS categoria_nome FROM livros l LEFT JOIN categorias c ON c.id = l.id_categoria WHERE 1=1";
$params = [];
if ($busca)     { $sql .= " AND (l.titulo LIKE ? OR l.autor LIKE ?)"; $params[] = "%$busca%"; $params[] = "%$busca%"; }
if ($filtrocat) { $sql .= " AND l.id_categoria = ?"; $params[] = $filtrocat; }
$sql .= " ORDER BY l.titulo ASC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$livros = $stmt->fetchAll();

$categorias = $conn->query("SELECT id, nome FROM categorias ORDER BY nome")->fetchAll();

$stmt = $conn->prepare("
    SELECT id_livro
    FROM emprestimos
    WHERE id_usuario = ?
      AND status IN ('A', 'V')
");
$stmt->execute([$_SESSION['usuario_id']]);
$emprestimosAtivos = array_flip($stmt->fetchAll(PDO::FETCH_COLUMN));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="/LibraFlow/public/admin/darkmode-btn.css">
    <title>Catálogo | LibraFlow</title>
    <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400..700;1,400..700&family=Source+Sans+3:ital,wght@0,200..900;1,200..900&display=swap" rel="stylesheet">
    <style>
        .filtro-categorias {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
            margin: 2rem 0;
            padding: 0 4rem;
        }

        .filtro-categorias a {
            padding: 0.6rem 1.5rem;
            border-radius: 2rem;
            font-size: 1.3rem;
            font-weight: 600;
            text-decoration: none;
            border: 0.15rem solid #BC6C25;
            color: #BC6C25;
            font-family: 'Source Sans 3', sans-serif;
            transition: all 0.2s;
        }

        .filtro-categorias a:hover,
        .filtro-categorias a.ativo { background: #BC6C25; color: #fff; }

        .grid-livros {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(20rem, 1fr));
            gap: 2.5rem;
            padding: 2rem 4rem 4rem;
            width: 100%;
        }

        .card-livro {
            background: #fff;
            border-radius: 1.2rem;
            box-shadow: 0 2px 12px rgba(0,0,0,.07);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .card-livro:hover { transform: translateY(-4px); box-shadow: 0 8px 24px rgba(0,0,0,.12); }

        .capa-livro {
            width: 100%;
            height: 24rem;
            object-fit: cover;
        }

        .sem-capa {
            width: 100%;
            height: 24rem;
            background: linear-gradient(135deg, #FEFAE0, #DDA15E33);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 6rem;
        }

        .info-livro {
            padding: 1.5rem;
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 0.6rem;
        }

        .badge-categoria {
            display: inline-block;
            padding: 0.2rem 0.9rem;
            background: #f0fdf4;
            color: #166534;
            border: 1px solid #bbf7d0;
            border-radius: 2rem;
            font-size: 1.1rem;
            font-weight: 600;
            font-family: 'Source Sans 3', sans-serif;
            width: fit-content;
        }

        .titulo-livro {
            font-family: 'Lora', serif;
            font-size: 1.5rem;
            color: #283618;
            font-weight: 700;
            line-height: 1.3;
        }

        .autor-livro { font-size: 1.2rem; color: #606C38; font-family: 'Source Sans 3', sans-serif; }

        .disponibilidade { font-size: 1.1rem; font-family: 'Source Sans 3', sans-serif; margin-top: auto; padding-top: 0.5rem; }
        .disponivel   { color: #166534; }
        .indisponivel { color: #c0392b; }

        .card-botoes {
            display: flex;
            gap: 0.8rem;
            padding: 0 1.5rem 1.5rem;
        }

        .btn-ver-mais {
            flex: 1;
            padding: 0.9rem;
            background: #606C38;
            color: #fff;
            border: none;
            border-radius: 1rem;
            font-size: 1.2rem;
            font-weight: bold;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            font-family: 'Source Sans 3', sans-serif;
            transition: background 0.2s;
        }

        .btn-ver-mais:hover { background: #283618; }

        .btn-solicitar {
            flex: 1;
            padding: 0.9rem;
            background: #DDA15E;
            color: #fff;
            border: none;
            border-radius: 1rem;
            font-size: 1.2rem;
            font-weight: bold;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            font-family: 'Source Sans 3', sans-serif;
            transition: background 0.2s;
        }

        .btn-solicitar:hover { background: #BC6C25; }

        .btn-solicitar.desabilitado {
            background: #ccc;
            cursor: not-allowed;
            pointer-events: none;
        }

        .vazio {
            grid-column: 1 / -1;
            text-align: center;
            padding: 6rem 0;
            color: #888;
            font-size: 1.6rem;
        }

        .total-resultados {
            padding: 0 4rem;
            font-size: 1.3rem;
            color: #888;
            font-family: 'Source Sans 3', sans-serif;
        }

        /* ==================== RESPONSIVIDADE ==================== */
        @media (max-width: 1024px) {
            .filtro-categorias {
                padding: 0 2rem;
            }

            .grid-livros {
                grid-template-columns: repeat(auto-fill, minmax(18rem, 1fr));
                padding: 2rem 2rem 4rem;
            }

            .capa-livro,
            .sem-capa {
                height: 20rem;
            }
        }

        @media (max-width: 768px) {
            .filtro-categorias {
                padding: 0 1.5rem;
                gap: 0.8rem;
            }

            .filtro-categorias a {
                padding: 0.5rem 1.2rem;
                font-size: 1.2rem;
            }

            .grid-livros {
                grid-template-columns: repeat(auto-fill, minmax(15rem, 1fr));
                padding: 1.5rem 1.5rem 3rem;
                gap: 1.5rem;
            }

            .capa-livro,
            .sem-capa {
                height: 18rem;
            }

            .card-livro {
                border-radius: 1rem;
            }

            .info-livro {
                padding: 1.2rem;
            }

            .titulo-livro {
                font-size: 1.3rem;
            }

            .autor-livro {
                font-size: 1.1rem;
            }

            .card-botoes {
                padding: 0 1.2rem 1.2rem;
            }

            .total-resultados {
                padding: 0 1.5rem;
            }
        }

        @media (max-width: 480px) {
            .filtro-categorias {
                padding: 0 1rem;
                gap: 0.5rem;
            }

            .filtro-categorias a {
                padding: 0.4rem 1rem;
                font-size: 1.1rem;
            }

            .grid-livros {
                grid-template-columns: 1fr;
                padding: 1rem 1rem 2rem;
                gap: 1rem;
            }

            .capa-livro,
            .sem-capa {
                height: 16rem;
            }

            .sem-capa {
                font-size: 4rem;
            }

            .info-livro {
                padding: 1rem;
            }

            .titulo-livro {
                font-size: 1.2rem;
            }

            .autor-livro {
                font-size: 1rem;
            }

            .disponibilidade {
                font-size: 1rem;
            }

            .card-botoes {
                flex-direction: column;
                padding: 0 1rem 1rem;
            }

            .card-botoes button {
                width: 100%;
            }

            .total-resultados {
                padding: 0 1rem;
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

        body.dark .text-header h1,
        body.dark .text-header p {
            color: #D4E8B0;
        }

        body.dark .text-header p {
            color: #A8C97F;
        }

        body.dark input {
            background: #243015;
            border-color: #3A4E1E;
            color: #D4E8B0;
        }

        body.dark button {
            background: #3A4E1E;
            color: #D4E8B0;
        }

        body.dark button:hover {
            background: #4A6020;
        }

        body.dark .links-nav a,
        body.dark nav a {
            color: #A8C97F;
        }

        body.dark .filtro-categorias a {
            border-color: #3A4E1E;
            color: #A8C97F;
        }

        body.dark .filtro-categorias a:hover,
        body.dark .filtro-categorias a.ativo {
            background: #3A4E1E;
            color: #D4E8B0;
        }

        body.dark .card-livro {
            background: #243015;
        }

        body.dark .capa-livro {
            filter: brightness(0.9);
        }

        body.dark .sem-capa {
            background: linear-gradient(135deg, #3a3060, #3A4E1E33);
        }

        body.dark .info-livro {
            background: #243015;
        }

        body.dark .titulo-livro {
            color: #D4E8B0;
        }

        body.dark .autor-livro {
            color: #A8C97F;
        }

        body.dark .badge-categoria {
            background: #3a3060;
            color: #A8C97F;
            border-color: #3A4E1E;
        }

        body.dark .disponivel {
            color: #90EE90;
        }

        body.dark .indisponivel {
            color: #FF6B6B;
        }

        body.dark .total-resultados {
            color: #A8C97F;
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <nav>
        <div class="logo-nav">
            <img src="" alt="Logo LibraFlow">
        </div>
        <div class="links-nav">
            <ul>
                <li><a href="/LibraFlow/public/usuario/index.php">Início</a></li>
                <li><a href="/LibraFlow/public/catalogo/catalogo.php">Catálogo</a></li>
                <li><a href="/LibraFlow/public/catalogo/meus_emprestimos.php">Meus empréstimos</a></li>
                <li><a href="/LibraFlow/public/auth/logout.php">Sair</a></li>
            </ul>
        </div>
        <div class="user">
            <span style="font-size:1.3rem;color:#606C38;font-family:'Source Sans 3',sans-serif;">
                <i class="fas fa-user" aria-hidden="true"></i> <?= htmlspecialchars($_SESSION['usuario_nome']) ?>
            </span>
        </div>
    </nav>

    <header>
        <div class="text-header">
            <h1>Catálogo de Livros</h1>
            <p>Explore nossa vasta coleção e encontre seu próximo livro favorito.</p>
        </div>
        <div class="search-header">
            <form action="catalogo.php" method="GET" style="display:flex;gap:1rem;">
                <input type="text" name="txtBusca" placeholder="Pesquisar livros..."
                    value="<?= htmlspecialchars($busca) ?>">
                <?php if ($filtrocat): ?>
                    <input type="hidden" name="categoria" value="<?= $filtrocat ?>">
                <?php endif; ?>
                <button type="submit" class="btn-buscar">Buscar</button>
            </form>
        </div>
    </header>

    <!-- Filtro por categoria -->
    <div class="filtro-categorias">
        <a href="catalogo.php<?= $busca ? '?txtBusca='.urlencode($busca) : '' ?>"
           class="<?= !$filtrocat ? 'ativo' : '' ?>">Todos</a>
        <?php foreach ($categorias as $cat): ?>
            <a href="catalogo.php?categoria=<?= $cat['id'] ?><?= $busca ? '&txtBusca='.urlencode($busca) : '' ?>"
               class="<?= $filtrocat == $cat['id'] ? 'ativo' : '' ?>">
                <?= htmlspecialchars($cat['nome']) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <p class="total-resultados">
        <?= count($livros) ?> livro<?= count($livros) !== 1 ? 's' : '' ?> encontrado<?= count($livros) !== 1 ? 's' : '' ?>
    </p>

    <main>
        <div class="grid-livros">
            <?php if (empty($livros)): ?>
                <div class="vazio"><i class="fas fa-book-open" aria-hidden="true"></i> Nenhum livro encontrado.</div>
            <?php else: ?>
                <?php foreach ($livros as $livro): ?>
                <?php $jaEmprestado = isset($emprestimosAtivos[$livro['id']]); ?>
                <div class="card-livro">
                    <?php if ($livro['capa']): ?>
                        <img src="/LibraFlow/public/catalogo/capas/<?= htmlspecialchars($livro['capa']) ?>"
                             class="capa-livro" alt="Capa de <?= htmlspecialchars($livro['titulo']) ?>">
                    <?php else: ?>
                        <div class="sem-capa"><i class="fas fa-book" aria-hidden="true"></i></div>
                    <?php endif; ?>

                    <div class="info-livro">
                        <?php if ($livro['categoria_nome']): ?>
                            <span class="badge-categoria"><?= htmlspecialchars($livro['categoria_nome']) ?></span>
                        <?php endif; ?>
                        <p class="titulo-livro"><?= htmlspecialchars($livro['titulo']) ?></p>
                        <p class="autor-livro"><?= htmlspecialchars($livro['autor']) ?></p>
                        <p class="disponibilidade <?= $livro['quantidade'] > 0 ? 'disponivel' : 'indisponivel' ?>">
                            <?= $livro['quantidade'] > 0
                                ? '<i class="fas fa-circle-check status-icon" aria-hidden="true"></i> ' . $livro['quantidade'] . ' disponível' . ($livro['quantidade'] > 1 ? 'is' : '')
                                : '<i class="fas fa-circle-xmark status-icon" aria-hidden="true"></i> Indisponível' ?>
                        </p>
                    </div>

                    <div class="card-botoes">
                        <a href="livro.php?id=<?= $livro['id'] ?>" class="btn-ver-mais">Ver mais</a>
                        <a href="solicitar.php?id=<?= $livro['id'] ?>"
                           class="btn-solicitar <?= ($livro['quantidade'] <= 0 || $jaEmprestado) ? 'desabilitado' : '' ?>">
                            <?= $jaEmprestado ? 'Já solicitado' : 'Solicitar' ?>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Botão Dark Mode -->
        <button id="themeToggle" class="theme-toggle-float" aria-label="Alternar tema claro/escuro">
            <span id="themeIcon"><i class="fas fa-moon" aria-hidden="true"></i></span>
            <span id="themeLabel">Escuro</span>
        </button>
    </main>

    <script src="/LibraFlow/public/catalogo/darkmode.js"></script>
</body>
</body>
</html>
