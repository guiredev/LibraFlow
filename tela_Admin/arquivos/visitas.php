<?php
// tela_Admin/arquivos/visitas.php

require $_SERVER['DOCUMENT_ROOT'] . '/LibraFlow/cadastros_e_logins/configs/auth_check.php';

if ($_SESSION['usuario_tipo'] !== 'D') {
    header('Location: /LibraFlow/Tela_de_usuario/arquivos/index.php');
    exit;
}

require $_SERVER['DOCUMENT_ROOT'] . '/LibraFlow/cadastros_e_logins/configs/conexao.php';

$periodos = ['Manha', 'Tarde', 'Noite'];
$erro = '';
$sucesso = '';
$dataHoje = date('Y-m-d');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dataRegistro = $_POST['data_registro'] ?? $dataHoje;
    $visitas = $_POST['visitas'] ?? [];

    if (!$dataRegistro) {
        $erro = 'Informe a data do registro.';
    } else {
        try {
            $stmt = $conn->prepare("
                INSERT INTO visitas_biblioteca (data_registro, periodo, quantidade)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE quantidade = VALUES(quantidade)
            ");

            foreach ($periodos as $periodo) {
                $quantidade = max(0, intval($visitas[$periodo] ?? 0));
                $stmt->execute([$dataRegistro, $periodo, $quantidade]);
            }

            $sucesso = 'Visitas registradas com sucesso.';
        } catch (PDOException $e) {
            $erro = 'Nao foi possivel salvar as visitas. Verifique se a tabela foi criada.';
        }
    }
}

$inicio = $_GET['inicio'] ?? date('Y-m-01');
$fim = $_GET['fim'] ?? $dataHoje;

try {
    $stmt = $conn->prepare("
        SELECT data_registro, periodo, quantidade
        FROM visitas_biblioteca
        WHERE data_registro BETWEEN ? AND ?
        ORDER BY data_registro DESC,
            FIELD(periodo, 'Manha', 'Tarde', 'Noite')
    ");
    $stmt->execute([$inicio, $fim]);
    $registros = $stmt->fetchAll();

    $totaisPeriodo = array_fill_keys($periodos, 0);
    $totalGeral = 0;

    foreach ($registros as $registro) {
        if (isset($totaisPeriodo[$registro['periodo']])) {
            $totaisPeriodo[$registro['periodo']] += (int) $registro['quantidade'];
        }
        $totalGeral += (int) $registro['quantidade'];
    }
} catch (PDOException $e) {
    $registros = [];
    $totaisPeriodo = array_fill_keys($periodos, 0);
    $totalGeral = 0;
    $erro = $erro ?: 'Nao foi possivel carregar os registros de visitas.';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visitas | LibraFlow Admin</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="darkmode-btn.css">
    <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400..700;1,400..700&family=Source+Sans+3:ital,wght@0,200..900;1,200..900&display=swap" rel="stylesheet">
    <style>
        .visitas-layout {
            display: grid;
            grid-template-columns: minmax(280px, 420px) 1fr;
            gap: 2rem;
            align-items: start;
        }

        /* Dark Mode Toggle Button */
        .theme-toggle-wrapper {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            z-index: 1000;
        }

        .theme-toggle-btn {
            width: 5.5rem;
            height: 5.5rem;
            border-radius: 50%;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: #DDA15E;
        }

        .theme-toggle-btn:hover {
            transform: scale(1.1) rotate(20deg);
            box-shadow: 0 6px 20px rgba(0,0,0,0.25);
        }

        .theme-toggle-btn:active {
            transform: scale(0.95);
        }

        .theme-toggle-btn:focus-visible {
            outline: 3px solid #BC6C25;
            outline-offset: 3px;
        }

        body.dark .theme-toggle-btn {
            background: #4A6020;
        }

        @media (max-width: 768px) {
            .theme-toggle-wrapper {
                bottom: 1.5rem;
                right: 1.5rem;
            }

            .theme-toggle-btn {
                width: 4.5rem;
                height: 4.5rem;
                font-size: 1.5rem;
            }
        }

        .totais-visitas {
            display: grid;
            grid-template-columns: repeat(4, minmax(14rem, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .total-card {
            background: #fff;
            border-radius: 1.2rem;
            padding: 1.8rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }

        .total-card h2 {
            font-size: 1.3rem;
            color: #4A4A4A;
            margin-bottom: 0.8rem;
        }

        .total-card p {
            font-size: 3rem;
            font-weight: 800;
            color: #283618;
        }

        @media (max-width: 900px) {
            .visitas-layout { grid-template-columns: 1fr; }
            .totais-visitas { grid-template-columns: 1fr 1fr; }
        }
    </style>
</head>
<body>
    <aside>
        <div class="logo-aside"><span>LibraFlow</span></div>
        <ul>
            <li><a href="/LibraFlow/tela_Admin/arquivos/Admin.php">🏠 Início</a></li>
            <li><a href="/LibraFlow/tela_Admin/arquivos/listar_livros.php">📚 Livros</a></li>
            <li><a href="/LibraFlow/tela_Admin/arquivos/cadastrar_livro.php">➕ Cadastrar Livro</a></li>
            <li><a href="/LibraFlow/tela_Admin/arquivos/usuarios.php">👥 Usuários</a></li>
            <li><a href="/LibraFlow/tela_Admin/arquivos/emprestimos.php">📋 Empréstimos</a></li>
            <li><a href="/LibraFlow/tela_Admin/arquivos/visitas.php">🕒 Visitas</a></li>
            <li><a href="relatorios/index.php" class="ativo">📈 Relatórios</a></li>
            <div class="sidebar-down">
                <li><a href="/LibraFlow/cadastros_e_logins/logout/logout.php">Sair</a></li>
            </div>
        </ul>
    </aside>

    <nav>
        <span style="font-family:'Lora',serif;font-size:2rem;color:#283618;">Controle de Visitas</span>
        <div class="right">
            <span style="font-size:1.4rem;color:#606C38;"><?= htmlspecialchars($_SESSION['usuario_nome']) ?></span>
        </div>
    </nav>

    <header>
        <h1>Controle de Visitas</h1>
        <p>Registre visitas por periodo e acompanhe os totais automaticamente.</p>
    </header>

    <main>
        <?php if ($erro): ?>
            <div class="alerta alerta-erro"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>

        <?php if ($sucesso): ?>
            <div class="alerta alerta-sucesso"><?= htmlspecialchars($sucesso) ?></div>
        <?php endif; ?>

        <section class="totais-visitas">
            <?php foreach ($totaisPeriodo as $periodo => $total): ?>
                <div class="total-card">
                    <h2><?= htmlspecialchars($periodo) ?></h2>
                    <p><?= $total ?></p>
                </div>
            <?php endforeach; ?>
            <div class="total-card">
                <h2>Total do periodo</h2>
                <p><?= $totalGeral ?></p>
            </div>
        </section>

        <section class="visitas-layout">
            <div class="form-card">
                <h2>Novo registro</h2>
                <form method="POST">
                    <div class="form-grid">
                        <div class="form-group full">
                            <label for="data_registro">Data</label>
                            <input type="date" id="data_registro" name="data_registro" value="<?= htmlspecialchars($_POST['data_registro'] ?? $dataHoje) ?>" required>
                        </div>

                        <?php foreach ($periodos as $periodo): ?>
                            <div class="form-group">
                                <label for="periodo_<?= htmlspecialchars($periodo) ?>"><?= htmlspecialchars($periodo) ?></label>
                                <input type="number" id="periodo_<?= htmlspecialchars($periodo) ?>" name="visitas[<?= htmlspecialchars($periodo) ?>]" min="0" value="<?= htmlspecialchars($_POST['visitas'][$periodo] ?? '0') ?>">
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="submit" class="btn-salvar">Salvar visitas</button>
                </form>
            </div>

            <div>
                <form method="GET" class="filtros">
                    <input type="date" name="inicio" value="<?= htmlspecialchars($inicio) ?>">
                    <input type="date" name="fim" value="<?= htmlspecialchars($fim) ?>">
                    <button type="submit" class="btn-filtrar">Filtrar</button>
                    <a href="visitas.php" style="font-size:1.3rem;color:#BC6C25;font-weight:bold;text-decoration:none;padding:0.8rem;">Mes atual</a>
                </form>

                <div class="tabela-wrapper">
                    <?php if (empty($registros)): ?>
                        <div class="vazio-tabela">Nenhuma visita registrada neste intervalo.</div>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Periodo</th>
                                    <th>Quantidade</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($registros as $registro): ?>
                                    <tr>
                                        <td><?= date('d/m/Y', strtotime($registro['data_registro'])) ?></td>
                                        <td><?= htmlspecialchars($registro['periodo']) ?></td>
                                        <td><?= (int) $registro['quantidade'] ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </main>

    <!-- Botão Dark Mode -->
    <button id="themeToggle" class="theme-toggle-float" aria-label="Alternar tema claro/escuro">
        <span id="themeIcon">🌙</span>
        <span id="themeLabel">Escuro</span>
    </button>

    <script src="darkmode.js"></script>
</body>
</html>

