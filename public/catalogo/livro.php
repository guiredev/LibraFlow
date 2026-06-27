<?php
/*
 * MAPA RAPIDO DO ARQUIVO
 * Local: public/catalogo/livro.php
 * Funcao: Detalhe de um livro especifico e status para solicitar emprestimo.
 */
// catalogo/livro.php

require $_SERVER['DOCUMENT_ROOT'] . '/LibraFlow/app/config/auth_check.php';
require $_SERVER['DOCUMENT_ROOT'] . '/LibraFlow/app/config/conexao.php';

$id = intval($_GET['id'] ?? 0);
if (!$id) { header('Location: catalogo.php'); exit; }

$stmt = $conn->prepare("
    SELECT l.*, c.nome AS categoria_nome
    FROM livros l
    LEFT JOIN categorias c ON c.id = l.id_categoria
    WHERE l.id = ?
");
$stmt->execute([$id]);
$livro = $stmt->fetch();

if (!$livro) { header('Location: catalogo.php'); exit; }

$stmt = $conn->prepare("
    SELECT id
    FROM emprestimos
    WHERE id_usuario = ?
      AND id_livro = ?
      AND status IN ('A', 'V')
    LIMIT 1
");
$stmt->execute([$_SESSION['usuario_id'], $id]);
$jaEmprestado = (bool) $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="/LibraFlow/public/admin/darkmode-btn.css">
    <title><?= htmlspecialchars($livro['titulo']) ?> | LibraFlow</title>
    <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400..700;1,400..700&family=Source+Sans+3:ital,wght@0,200..900;1,200..900&display=swap" rel="stylesheet">
    <style>
        .livro-detalhe {
            display: flex;
            gap: 4rem;
            max-width: 900px;
            margin: 3rem auto;
            padding: 0 4rem;
            align-items: flex-start;
        }

        .capa-detalhe {
            width: 22rem;
            min-width: 22rem;
            height: 30rem;
            object-fit: cover;
            border-radius: 1.2rem;
            box-shadow: 0 8px 24px rgba(0,0,0,.15);
        }

        .sem-capa-detalhe {
            width: 22rem;
            min-width: 22rem;
            height: 30rem;
            background: linear-gradient(135deg, #FEFAE0, #DDA15E33);
            border-radius: 1.2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 8rem;
            box-shadow: 0 8px 24px rgba(0,0,0,.10);
        }

        .info-detalhe {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 1.2rem;
        }

        .badge-categoria {
            display: inline-block;
            padding: 0.3rem 1.2rem;
            background: #f0fdf4;
            color: #166534;
            border: 1px solid #bbf7d0;
            border-radius: 2rem;
            font-size: 1.2rem;
            font-weight: 600;
            font-family: 'Source Sans 3', sans-serif;
            width: fit-content;
        }

        .titulo-detalhe {
            font-family: 'Lora', serif;
            font-size: 3.2rem;
            color: #283618;
            font-weight: 700;
            line-height: 1.2;
        }

        .subtitulo-detalhe {
            font-family: 'Lora', serif;
            font-size: 1.8rem;
            color: #606C38;
            font-style: italic;
        }

        .autor-detalhe {
            font-size: 1.6rem;
            color: #BC6C25;
            font-family: 'Source Sans 3', sans-serif;
            font-weight: 600;
        }

        .meta-livro {
            display: flex;
            gap: 2rem;
            flex-wrap: wrap;
        }

        .meta-item {
            font-size: 1.3rem;
            color: #888;
            font-family: 'Source Sans 3', sans-serif;
        }

        .meta-item strong { color: #444; }

        .descricao-detalhe {
            font-size: 1.4rem;
            color: #555;
            font-family: 'Source Sans 3', sans-serif;
            line-height: 2.4rem;
            border-top: 1px solid #eee;
            padding-top: 1.5rem;
        }

        .disponibilidade {
            font-size: 1.4rem;
            font-family: 'Source Sans 3', sans-serif;
            font-weight: 600;
        }

        .disponivel   { color: #166534; }
        .indisponivel { color: #c0392b; }

        .botoes-detalhe {
            display: flex;
            gap: 1.2rem;
            margin-top: 1rem;
            flex-wrap: wrap;
        }

        .btn-solicitar {
            padding: 1.2rem 3rem;
            background: #DDA15E;
            color: #fff;
            border: none;
            border-radius: 1rem;
            font-size: 1.5rem;
            font-weight: bold;
            cursor: pointer;
            font-family: 'Source Sans 3', sans-serif;
            text-decoration: none;
            transition: background 0.2s;
        }

        .btn-solicitar:hover { background: #BC6C25; }

        .btn-solicitar.desabilitado {
            background: #ccc;
            cursor: not-allowed;
            pointer-events: none;
        }

        .btn-voltar {
            padding: 1.2rem 2rem;
            background: transparent;
            color: #BC6C25;
            border: 2px solid #BC6C25;
            border-radius: 1rem;
            font-size: 1.5rem;
            font-weight: bold;
            cursor: pointer;
            font-family: 'Source Sans 3', sans-serif;
            text-decoration: none;
            transition: all 0.2s;
        }

        .btn-voltar:hover { background: #BC6C25; color: #fff; }

        /* ==================== RESPONSIVIDADE ==================== */
        @media (max-width: 1024px) {
            .livro-container {
                padding: 3rem;
                max-width: 45rem;
            }

            .livro-capa {
                width: 18rem;
                height: 24rem;
            }

            .livro-info h1 {
                font-size: 2rem;
            }

            .livro-info p {
                font-size: 1.3rem;
            }
        }

        @media (max-width: 768px) {
            .livro-container {
                flex-direction: column;
                padding: 2rem;
                max-width: 100%;
            }

            .livro-capa {
                width: 100%;
                max-width: 25rem;
                height: 20rem;
                margin-right: 0;
                margin-bottom: 2rem;
            }

            .livro-info {
                width: 100%;
            }

            .livro-info h1 {
                font-size: 1.8rem;
                margin-bottom: 1rem;
            }

            .livro-info p {
                font-size: 1.2rem;
                margin-bottom: 1rem;
            }

            .badge-categoria {
                font-size: 1rem;
                padding: 0.3rem 0.8rem;
            }

            .botoes-acoes {
                flex-direction: column;
                gap: 1rem;
            }

            .btn-solicitar,
            .btn-voltar {
                width: 100%;
                text-align: center;
            }
        }

        @media (max-width: 480px) {
            .livro-container {
                padding: 1.5rem;
            }

            .livro-capa {
                max-width: 20rem;
                height: 18rem;
            }

            .livro-info h1 {
                font-size: 1.5rem;
            }

            .livro-info p {
                font-size: 1.1rem;
            }

            .badge-categoria {
                font-size: 0.9rem;
                padding: 0.2rem 0.6rem;
            }

            .btn-solicitar,
            .btn-voltar {
                padding: 0.8rem 1.5rem;
                font-size: 1.3rem;
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

        body.dark .livro-container {
            background: #243015;
        }

        body.dark .livro-info h1 {
            color: #D4E8B0;
        }

        body.dark .livro-info p {
            color: #A8C97F;
        }

        body.dark .badge-categoria {
            background: #2A3318;
            color: #A8C97F;
            border-color: #3A4E1E;
        }

        body.dark .info-item {
            color: #A8C97F;
        }

        body.dark .info-item strong {
            color: #D4E8B0;
        }

        body.dark .disponivel {
            color: #90EE90;
        }

        body.dark .indisponivel {
            color: #FF6B6B;
        }

        body.dark .btn-solicitar {
            background: #3A4E1E;
            color: #D4E8B0;
        }

        body.dark .btn-solicitar:hover {
            background: #4A6020;
        }

        body.dark .btn-solicitar.desabilitado {
            background: #2A3318;
            color: #4A6020;
        }

        body.dark .btn-voltar {
            border-color: #3A4E1E;
            color: #A8C97F;
        }

        body.dark .btn-voltar:hover {
            background: #3A4E1E;
            color: #D4E8B0;
        }

        body.dark .links-nav a,
        body.dark nav a {
            color: #A8C97F;
        }

        body.dark .capa-livro,
        body.dark .sem-capa {
            filter: brightness(0.9);
        }

        body.dark .sem-capa {
            background: linear-gradient(135deg, #2A3318, #3A4E1E33);
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

    <div class="livro-detalhe">
        <!-- Capa -->
        <?php if ($livro['capa']): ?>
            <img src="/LibraFlow/public/catalogo/capas/<?= htmlspecialchars($livro['capa']) ?>"
                 class="capa-detalhe" alt="Capa de <?= htmlspecialchars($livro['titulo']) ?>">
        <?php else: ?>
            <div class="sem-capa-detalhe"><i class="fas fa-book" aria-hidden="true"></i></div>
        <?php endif; ?>

        <!-- Informações -->
        <div class="info-detalhe">
            <?php if ($livro['categoria_nome']): ?>
                <span class="badge-categoria"><?= htmlspecialchars($livro['categoria_nome']) ?></span>
            <?php endif; ?>

            <h1 class="titulo-detalhe"><?= htmlspecialchars($livro['titulo']) ?></h1>

            <?php if ($livro['subtitulo']): ?>
                <p class="subtitulo-detalhe"><?= htmlspecialchars($livro['subtitulo']) ?></p>
            <?php endif; ?>

            <p class="autor-detalhe"><i class="fas fa-pen-nib" aria-hidden="true"></i> <?= htmlspecialchars($livro['autor']) ?></p>

            <div class="meta-livro">
                <?php if ($livro['ano']): ?>
                    <span class="meta-item"><strong>Ano:</strong> <?= $livro['ano'] ?></span>
                <?php endif; ?>
                <?php if ($livro['isbn']): ?>
                    <span class="meta-item"><strong>ISBN:</strong> <?= htmlspecialchars($livro['isbn']) ?></span>
                <?php endif; ?>
            </div>

            <?php if ($livro['descricao']): ?>
                <p class="descricao-detalhe"><?= nl2br(htmlspecialchars($livro['descricao'])) ?></p>
            <?php endif; ?>

            <p class="disponibilidade <?= $livro['quantidade'] > 0 ? 'disponivel' : 'indisponivel' ?>">
                <?= $livro['quantidade'] > 0
                    ? '<i class="fas fa-circle-check status-icon" aria-hidden="true"></i> ' . $livro['quantidade'] . ' exemplar' . ($livro['quantidade'] > 1 ? 'es disponíveis' : ' disponível')
                    : '<i class="fas fa-circle-xmark status-icon" aria-hidden="true"></i> Indisponível no momento' ?>
            </p>

            <div class="botoes-detalhe">
                <a href="catalogo.php" class="btn-voltar"><i class="fas fa-arrow-left" aria-hidden="true"></i> Voltar</a>
                <a href="solicitar.php?id=<?= $livro['id'] ?>"
                   class="btn-solicitar <?= ($livro['quantidade'] <= 0 || $jaEmprestado) ? 'desabilitado' : '' ?>">
                    <?= $jaEmprestado ? 'Já solicitado' : 'Solicitar empréstimo' ?>
                </a>
            </div>
        </div>

        <!-- Botão Dark Mode -->
        <button id="themeToggle" class="theme-toggle-float" aria-label="Alternar tema claro/escuro">
            <span id="themeIcon"><i class="fas fa-moon" aria-hidden="true"></i></span>
            <span id="themeLabel">Escuro</span>
        </button>
    </div>

    <script src="/LibraFlow/public/catalogo/darkmode.js"></script>
</body>
</body>
</html>
