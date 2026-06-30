<?php
/*
 * MAPA RAPIDO DO ARQUIVO
 * Local: public/catalogo/solicitar.php
 * Funcao: Fluxo de solicitacao de emprestimo pelo usuario logado.
 */
// catalogo/solicitar.php

require $_SERVER['DOCUMENT_ROOT'] . '/LibraFlow/app/config/auth_check.php';
require $_SERVER['DOCUMENT_ROOT'] . '/LibraFlow/app/config/conexao.php';

$idLivro = intval($_GET['id'] ?? 0);

if (!$idLivro) {
    header('Location: catalogo.php');
    exit;
}

$erro = '';
$sucesso = '';
$livro = null;
$prazoDias = 14;

try {
    $conn->beginTransaction();

    $stmt = $conn->prepare("SELECT id, titulo, quantidade FROM livros WHERE id = ? FOR UPDATE");
    $stmt->execute([$idLivro]);
    $livro = $stmt->fetch();

    if (!$livro) {
        $erro = 'Livro não encontrado.';
    } elseif ((int) $livro['quantidade'] <= 0) {
        $erro = 'Este livro está indisponível no momento.';
    } else {
        $stmt = $conn->prepare("
            SELECT id
            FROM emprestimos
            WHERE id_usuario = ?
              AND id_livro = ?
              AND status IN ('A', 'V')
            LIMIT 1
        ");
        $stmt->execute([$_SESSION['usuario_id'], $idLivro]);

        if ($stmt->fetch()) {
            $erro = 'Você já possui um empréstimo ativo deste livro.';
        } else {
            $stmt = $conn->prepare("
                INSERT INTO emprestimos
                    (id_usuario, id_livro, data_emprestimo, data_prevista_devolucao, status)
                VALUES
                    (?, ?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL {$prazoDias} DAY), 'A')
            ");
            $stmt->execute([$_SESSION['usuario_id'], $idLivro]);

            $stmt = $conn->prepare("UPDATE livros SET quantidade = quantidade - 1 WHERE id = ?");
            $stmt->execute([$idLivro]);

            $sucesso = 'Empréstimo solicitado com sucesso!';
        }
    }

    $conn->commit();
} catch (PDOException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    $erro = 'Não foi possível solicitar o empréstimo. Verifique se a tabela de empréstimos foi criada.';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="/LibraFlow/public/admin/darkmode-btn.css">
    <title>Solicitar Empréstimo | LibraFlow</title>
    <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400..700;1,400..700&family=Source+Sans+3:ital,wght@0,200..900;1,200..900&display=swap" rel="stylesheet">
    <style>
        .retorno {
            max-width: 640px;
            margin: 5rem auto;
            padding: 3rem;
            border-radius: 1.2rem;
            background: #fff;
            box-shadow: 0 2px 16px rgba(0,0,0,.08);
            text-align: center;
            font-family: 'Source Sans 3', sans-serif;
        }

        .retorno h1 {
            font-family: 'Lora', serif;
            font-size: 2.8rem;
            color: #283618;
            margin-bottom: 1rem;
        }

        .retorno p {
            font-size: 1.5rem;
            color: #606C38;
            line-height: 2.4rem;
            margin-bottom: 2rem;
        }

        .alerta {
            padding: 1.2rem 1.5rem;
            border-radius: 0.8rem;
            font-size: 1.4rem;
            margin-bottom: 2rem;
        }

        .alerta-erro { background: #fff0f0; color: #8b0000; border: 1px solid #f5c6c6; }
        .alerta-sucesso { background: #f0fdf4; color: #1a4d2e; border: 1px solid #bbf7d0; }

        .acoes-retorno {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .acoes-retorno a {
            padding: 1rem 2rem;
            border-radius: 1rem;
            font-size: 1.3rem;
            font-weight: 700;
            text-decoration: none;
            font-family: 'Source Sans 3', sans-serif;
        }

        .btn-principal { background: #DDA15E; color: #fff; }
        .btn-secundario { border: 2px solid #BC6C25; color: #BC6C25; }

        /* ==================== RESPONSIVIDADE ==================== */
        @media (max-width: 768px) {
            .solicitar-container {
                padding: 2rem;
                max-width: 100%;
            }

            .livro-detalhes {
                flex-direction: column;
                gap: 2rem;
            }

            .livro-capa {
                width: 100%;
                max-width: 25rem;
                height: auto;
            }

            .livro-info {
                width: 100%;
            }

            .livro-info h1 {
                font-size: 1.8rem;
            }

            .livro-info p {
                font-size: 1.2rem;
            }

            .info-item {
                font-size: 1.1rem;
            }

            .botoes-solicitar {
                flex-direction: column;
                gap: 1rem;
            }

            .btn-principal,
            .btn-secundario {
                width: 100%;
                text-align: center;
            }
        }

        @media (max-width: 480px) {
            .solicitar-container {
                padding: 1.5rem 1rem;
            }

            .livro-capa {
                max-width: 20rem;
            }

            .livro-info h1 {
                font-size: 1.5rem;
            }

            .livro-info p {
                font-size: 1.1rem;
            }

            .info-item {
                font-size: 1rem;
            }

            .btn-principal,
            .btn-secundario {
                padding: 0.8rem 1.5rem;
                font-size: 1.2rem;
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

        body.dark .solicitar-container {
            background: #243015;
        }

        body.dark .livro-info h1 {
            color: #D4E8B0;
        }

        body.dark .livro-info p {
            color: #A8C97F;
        }

        body.dark .info-item {
            color: #A8C97F;
        }

        body.dark .info-item strong {
            color: #D4E8B0;
        }

        body.dark .badge-categoria {
            background: #2A3318;
            color: #A8C97F;
            border-color: #3A4E1E;
        }

        body.dark .btn-principal {
            background: #3A4E1E;
            color: #D4E8B0;
        }

        body.dark .btn-principal:hover {
            background: #4A6020;
        }

        body.dark .btn-secundario {
            border-color: #3A4E1E;
            color: #A8C97F;
        }

        body.dark .btn-secundario:hover {
            background: #3A4E1E;
            color: #D4E8B0;
        }

        body.dark .links-nav a,
        body.dark nav a {
            color: #A8C97F;
        }

        body.dark .livro-capa {
            filter: brightness(0.9);
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
                <?= htmlspecialchars($_SESSION['usuario_nome']) ?>
            </span>
        </div>
    </nav>

    <main>
        <section class="retorno">
            <h1>Solicitação de empréstimo</h1>

            <?php if ($sucesso): ?>
                <div class="alerta alerta-sucesso"><?= htmlspecialchars($sucesso) ?></div>
                <p>
                    O livro <strong><?= htmlspecialchars($livro['titulo']) ?></strong> foi reservado para você.
                    A devolução prevista é em <?= $prazoDias ?> dias.
                </p>
            <?php else: ?>
                <div class="alerta alerta-erro"><?= htmlspecialchars($erro ?: 'Não foi possível concluir a solicitação.') ?></div>
            <?php endif; ?>

            <div class="acoes-retorno">
                <a href="catalogo.php" class="btn-secundario">Voltar ao catálogo</a>
                <a href="meus_emprestimos.php" class="btn-principal">Ver meus empréstimos</a>
            </div>
        </section>

        <!-- Botão Dark Mode -->
        <button id="themeToggle" class="theme-toggle-float" aria-label="Alternar tema claro/escuro">
            <span id="themeIcon"><i class="fas fa-moon" aria-hidden="true"></i></span>
            <span id="themeLabel">Escuro</span>
        </button>
    </main>

    <script src="/LibraFlow/public/catalogo/darkmode.js"></script>
</body>
</html>
