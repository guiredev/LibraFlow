<?php
// tela_Admin/arquivos/emprestimos.php

require $_SERVER['DOCUMENT_ROOT'] . '/LibraFlow/cadastros_e_logins/configs/auth_check.php';

if ($_SESSION['usuario_tipo'] !== 'D') {
    header('Location: /LibraFlow/Tela_de_usuario/arquivos/index.html');
    exit;
}

require $_SERVER['DOCUMENT_ROOT'] . '/LibraFlow/cadastros_e_logins/configs/conexao.php';

$erro = '';
$sucesso = '';

// Processar novo empréstimo via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['ajax'])) {
    header('Content-Type: application/json');

    $idLivro = intval($_POST['id_livro'] ?? 0);
    $idUsuario = intval($_POST['id_usuario'] ?? 0);
    $dias = intval($_POST['dias'] ?? 7);

    try {
        if ($idLivro <= 0 || $idUsuario <= 0) {
            echo json_encode(['sucesso' => false, 'erro' => 'Selecione um livro e um aluno.']);
            exit;
        }

        $conn->beginTransaction();

        // Verificar livro
        $stmt = $conn->prepare("SELECT id, titulo, quantidade FROM livros WHERE id = ? AND quantidade > 0 FOR UPDATE");
        $stmt->execute([$idLivro]);
        $livro = $stmt->fetch();

        if (!$livro) {
            echo json_encode(['sucesso' => false, 'erro' => 'Livro não encontrado ou sem exemplares disponíveis.']);
            $conn->rollBack();
            exit;
        }

        // Verificar usuário
        $stmt = $conn->prepare("SELECT id, nome FROM usuarios WHERE id = ? AND tipo = 'A'");
        $stmt->execute([$idUsuario]);
        $usuario = $stmt->fetch();

        if (!$usuario) {
            echo json_encode(['sucesso' => false, 'erro' => 'Aluno não encontrado.']);
            $conn->rollBack();
            exit;
        }

        // Verificar empréstimo duplicado
        $stmt = $conn->prepare("SELECT id FROM emprestimos WHERE id_livro = ? AND id_usuario = ? AND status IN ('A', 'V')");
        $stmt->execute([$idLivro, $idUsuario]);

        if ($stmt->fetch()) {
            echo json_encode(['sucesso' => false, 'erro' => 'Este aluno já possui este livro emprestado.']);
            $conn->rollBack();
            exit;
        }

        // Realizar empréstimo
        $dataPrevista = date('Y-m-d', strtotime("+$dias days"));

        $stmt = $conn->prepare("INSERT INTO emprestimos (id_usuario, id_livro, data_emprestimo, data_prevista_devolucao, status) VALUES (?, ?, CURDATE(), ?, 'A')");
        $stmt->execute([$idUsuario, $idLivro, $dataPrevista]);

        $stmt = $conn->prepare("UPDATE livros SET quantidade = quantidade - 1 WHERE id = ?");
        $stmt->execute([$idLivro]);

        $conn->commit();

        echo json_encode([
            'sucesso' => true,
            'mensagem' => "Empréstimo realizado! Livro: {$livro['titulo']}, Aluno: {$usuario['nome']}, Devolução: " . date('d/m/Y', strtotime($dataPrevista))
        ]);
    } catch (PDOException $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        echo json_encode(['sucesso' => false, 'erro' => 'Erro ao processar empréstimo.']);
    }
    exit;
}

try {
    $conn->exec("
        UPDATE emprestimos
        SET status = 'V'
        WHERE status = 'A'
          AND data_prevista_devolucao < CURDATE()
    ");

    if (isset($_GET['devolver'])) {
        $idEmprestimo = intval($_GET['devolver']);

        $conn->beginTransaction();

        $stmt = $conn->prepare("
            SELECT id, id_livro, status
            FROM emprestimos
            WHERE id = ?
            FOR UPDATE
        ");
        $stmt->execute([$idEmprestimo]);
        $emprestimo = $stmt->fetch();

        if (!$emprestimo) {
            $erro = 'Empréstimo não encontrado.';
        } elseif ($emprestimo['status'] === 'D') {
            $erro = 'Este empréstimo já foi devolvido.';
        } else {
            $conn->prepare("
                UPDATE emprestimos
                SET status = 'D', data_devolucao = CURDATE()
                WHERE id = ?
            ")->execute([$idEmprestimo]);

            $conn->prepare("UPDATE livros SET quantidade = quantidade + 1 WHERE id = ?")
                 ->execute([$emprestimo['id_livro']]);

            $sucesso = 'Devolução registrada com sucesso.';
        }

        $conn->commit();
    }

    $status = $_GET['status'] ?? '';
    $busca = trim($_GET['busca'] ?? '');

    $sql = "
        SELECT e.*, l.titulo, l.autor, u.nome AS usuario_nome, u.email AS usuario_email
        FROM emprestimos e
        JOIN livros l ON l.id = e.id_livro
        JOIN usuarios u ON u.id = e.id_usuario
        WHERE 1=1
    ";
    $params = [];

    if (in_array($status, ['A', 'D', 'V'], true)) {
        $sql .= " AND e.status = ?";
        $params[] = $status;
    }

    if ($busca !== '') {
        $sql .= " AND (l.titulo LIKE ? OR l.autor LIKE ? OR u.nome LIKE ? OR u.email LIKE ?)";
        $termo = "%$busca%";
        array_push($params, $termo, $termo, $termo, $termo);
    }

    $sql .= " ORDER BY e.data_emprestimo DESC, e.id DESC";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $emprestimos = $stmt->fetchAll();
} catch (PDOException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    $emprestimos = [];
    $erro = 'Não foi possível carregar os empréstimos. Verifique se a tabela foi criada.';
}

$statusInfo = [
    'A' => ['Ativo', '#22C55E'],
    'D' => ['Devolvido', '#3B82F6'],
    'V' => ['Vencido', '#EF4444'],
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Empréstimos | LibraFlow Admin</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="darkmode-btn.css">
    <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400..700;1,400..700&family=Source+Sans+3:ital,wght@0,200..900;1,200..900&display=swap" rel="stylesheet">
    <style>
        .badge-status {
            padding: 0.3rem 0.9rem;
            border-radius: 2rem;
            font-weight: 700;
            font-size: 1.2rem;
            display: inline-block;
        }

        .btn-devolver {
            padding: 0.5rem 1.2rem;
            border-radius: 0.6rem;
            font-size: 1.2rem;
            font-weight: bold;
            text-decoration: none;
            background: #f0fdf4;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .btn-devolver:hover { background: #dcfce7; }
        .muted { color: #888; font-size: 1.2rem; }

        /* Modal Styles */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 2000;
            justify-content: center;
            align-items: center;
        }

        .modal-overlay.ativo {
            display: flex;
        }

        .modal-conteudo {
            background: var(--bg-page);
            border-radius: 1rem;
            width: 90%;
            max-width: 600rem;
            max-height: 90vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
        }

        .modal-header {
            padding: 2rem;
            border-bottom: 2px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h2 {
            margin: 0;
            font-size: 2rem;
            color: var(--text-title);
            font-family: 'Lora', serif;
        }

        .modal-fechar {
            background: none;
            border: none;
            font-size: 2rem;
            cursor: pointer;
            color: var(--text-body);
            padding: 0.5rem;
            line-height: 1;
        }

        .modal-fechar:hover {
            color: var(--text-link);
        }

        .modal-body {
            padding: 2rem;
            overflow-y: auto;
            flex: 1;
        }

        .selecao-item {
            padding: 1.5rem;
            border: 2px solid var(--border-color);
            border-radius: 0.8rem;
            margin-bottom: 1rem;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .selecao-item:hover {
            border-color: var(--btn-primary);
            background: var(--bg-card);
        }

        .selecao-item.selecionado {
            border-color: var(--btn-primary);
            background: var(--btn-primary);
            color: var(--btn-primary-text);
        }

        .item-nome {
            font-size: 1.5rem;
            font-weight: 600;
        }

        .item-detalhes {
            font-size: 1.2rem;
            opacity: 0.8;
        }

        .form-emprestimo {
            display: grid;
            gap: 1.5rem;
        }

        .campo-selecao {
            display: flex;
            gap: 1rem;
        }

        .campo-selecao input {
            flex: 1;
            padding: 1rem 1.2rem;
            font-size: 1.4rem;
            border: 2px solid var(--border-color);
            border-radius: 0.6rem;
            background: var(--bg-card);
            color: var(--text-body);
            cursor: pointer;
        }

        .btn-selecionar {
            background: #3B82F6;
            color: white;
            padding: 1rem 1.5rem;
            border: none;
            border-radius: 0.6rem;
            font-size: 1.4rem;
            font-weight: 600;
            cursor: pointer;
            white-space: nowrap;
        }

        .btn-selecionar:hover {
            background: #2563EB;
        }

        .btn-confirmar-emprestimo {
            background: var(--btn-primary);
            color: var(--btn-primary-text);
            padding: 1rem 2rem;
            border: none;
            border-radius: 0.8rem;
            font-size: 1.5rem;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: var(--transition);
        }

        .btn-confirmar-emprestimo:hover {
            background: var(--btn-primary-hover);
        }

        .campo-dias {
            margin-top: 1rem;
        }

        .campo-dias label {
            display: block;
            font-weight: 600;
            font-size: 1.4rem;
            margin-bottom: 0.5rem;
        }

        .campo-dias input {
            width: 100%;
            padding: 0.8rem;
            font-size: 1.4rem;
            border: 2px solid var(--border-color);
            border-radius: 0.6rem;
            background: var(--bg-card);
            color: var(--text-body);
        }

        /* Responsividade para Modais */
        @media (max-width: 768px) {
            .modal-conteudo {
                width: 95%;
                max-height: 95vh;
                border-radius: 0.8rem;
            }

            .modal-header {
                padding: 1.5rem;
            }

            .modal-header h2 {
                font-size: 1.5rem;
            }

            .modal-body {
                padding: 1.5rem;
            }

            .campo-selecao {
                flex-direction: column;
            }

            .btn-selecionar {
                width: 100%;
            }

            .selecao-item {
                padding: 1rem;
            }

            .item-nome {
                font-size: 1.3rem;
            }

            .item-detalhes {
                font-size: 1.1rem;
            }

            .filtro-header {
                flex-direction: column;
                align-items: stretch;
            }

            .filtros {
                width: 100%;
            }

            .btn-novo-emprestimo {
                width: 100%;
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
            .modal-conteudo {
                width: 100%;
                max-height: 100vh;
                border-radius: 0;
            }

            .modal-header {
                padding: 1rem;
            }

            .modal-header h2 {
                font-size: 1.3rem;
            }

            .modal-body {
                padding: 1rem;
            }

            .form-emprestimo {
                gap: 1rem;
            }

            .btn-confirmar-emprestimo {
                padding: 0.8rem 1.5rem;
                font-size: 1.3rem;
            }
        }
    </style>
</head>
<body>
    <aside>
        <div class="logo-aside"><span>LibraFlow</span></div>
        <ul>
            <li><a href="Admin.php">Início</a></li>
            <li><a href="listar_livros.php">Livros</a></li>
            <li><a href="cadastrar_livro.php">Cadastrar Livro</a></li>
            <li><a href="usuarios.php">Usuários</a></li>
            <li><a href="emprestimos.php" class="ativo">Empréstimos</a></li>
            <li><a href="visitas.php">Visitas</a></li>
            <div class="sidebar-down">
                <li><a href="/LibraFlow/cadastros_e_logins/logout/logout.php">Sair</a></li>
            </div>
        </ul>
    </aside>

    <nav>
        <span style="font-family:'Lora',serif;font-size:2rem;color:#283618;">Empréstimos</span>
        <div class="right">
            <span style="font-size:1.4rem;color:#606C38;"><?= htmlspecialchars($_SESSION['usuario_nome']) ?></span>
        </div>
    </nav>

    <header>
        <h1>Empréstimos</h1>
        <p>Acompanhe retiradas, atrasos e devoluções do acervo.</p>
    </header>

    <main>
        <?php if ($erro): ?>
            <div class="alerta alerta-erro"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>

        <?php if ($sucesso): ?>
            <div class="alerta alerta-sucesso"><?= htmlspecialchars($sucesso) ?></div>
        <?php endif; ?>

        <div class="filtro-header">
            <form method="GET" class="filtros">
                <input type="text" name="busca" placeholder="Buscar livro ou usuário..." value="<?= htmlspecialchars($busca ?? '') ?>">
                <select name="status">
                    <option value="">Todos os status</option>
                    <option value="A" <?= ($status ?? '') === 'A' ? 'selected' : '' ?>>Ativos</option>
                    <option value="V" <?= ($status ?? '') === 'V' ? 'selected' : '' ?>>Vencidos</option>
                    <option value="D" <?= ($status ?? '') === 'D' ? 'selected' : '' ?>>Devolvidos</option>
                </select>
                <button type="submit" class="btn-filtrar">Filtrar</button>
                <a href="emprestimos.php" style="font-size:1.3rem;color:#BC6C25;font-weight:bold;text-decoration:none;padding:0.8rem;">Limpar</a>
            </form>
            <button class="btn-novo-emprestimo" onclick="abrirModalEmprestimo()">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 5v14M5 12h14"/>
                </svg>
                Fazer Empréstimo
            </button>
        </div>

        <div class="tabela-wrapper">
            <?php if (empty($emprestimos)): ?>
                <div class="vazio-tabela">Nenhum empréstimo encontrado.</div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Livro</th>
                            <th>Usuário</th>
                            <th>Retirada</th>
                            <th>Prevista</th>
                            <th>Devolução</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($emprestimos as $emprestimo): ?>
                            <?php
                                $info = $statusInfo[$emprestimo['status']] ?? ['?', '#999'];
                                $podeDevolver = in_array($emprestimo['status'], ['A', 'V'], true);
                            ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($emprestimo['titulo']) ?></strong><br>
                                    <span class="muted"><?= htmlspecialchars($emprestimo['autor']) ?></span>
                                </td>
                                <td>
                                    <?= htmlspecialchars($emprestimo['usuario_nome']) ?><br>
                                    <span class="muted"><?= htmlspecialchars($emprestimo['usuario_email']) ?></span>
                                </td>
                                <td><?= date('d/m/Y', strtotime($emprestimo['data_emprestimo'])) ?></td>
                                <td>
                                    <?= !empty($emprestimo['data_prevista_devolucao'])
                                        ? date('d/m/Y', strtotime($emprestimo['data_prevista_devolucao']))
                                        : '-' ?>
                                </td>
                                <td>
                                    <?= !empty($emprestimo['data_devolucao'])
                                        ? date('d/m/Y', strtotime($emprestimo['data_devolucao']))
                                        : '-' ?>
                                </td>
                                <td>
                                    <span class="badge-status" style="background:<?= $info[1] ?>22;color:<?= $info[1] ?>;">
                                        <?= $info[0] ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($podeDevolver): ?>
                                        <a href="emprestimos.php?devolver=<?= $emprestimo['id'] ?>"
                                           class="btn-devolver"
                                           onclick="return confirm('Registrar devolução deste livro?')">
                                            Registrar devolução
                                        </a>
                                    <?php else: ?>
                                        <span class="muted">Finalizado</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </main>

    <!-- Modal de Empréstimo -->
    <div id="modalEmprestimo" class="modal-overlay">
        <div class="modal-conteudo">
            <div class="modal-header">
                <h2>📚 Novo Empréstimo</h2>
                <button class="modal-fechar" onclick="fecharModalEmprestimo()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="formEmprestimo" class="form-emprestimo">
                    <input type="hidden" id="selectedLivro" name="id_livro" value="">
                    <input type="hidden" id="selectedAluno" name="id_usuario" value="">

                    <div class="campo-selecao">
                        <input type="text" id="inputLivro" placeholder="Selecione o livro" readonly onclick="abrirModalLivros()" required>
                        <button type="button" class="btn-selecionar" onclick="abrirModalLivros()">📖 Selecionar</button>
                    </div>

                    <div class="campo-selecao">
                        <input type="text" id="inputAluno" placeholder="Selecione o aluno" readonly onclick="abrirModalAlunos()" required>
                        <button type="button" class="btn-selecionar" onclick="abrirModalAlunos()">👤 Selecionar</button>
                    </div>

                    <div class="campo-dias">
                        <label for="dias">Dias para devolução:</label>
                        <input type="number" id="dias" name="dias" value="7" min="1" max="30">
                    </div>

                    <button type="submit" class="btn-confirmar-emprestimo">✅ Confirmar Empréstimo</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal de Seleção de Livro -->
    <div id="modalLivros" class="modal-overlay">
        <div class="modal-conteudo">
            <div class="modal-header">
                <h2>📖 Selecionar Livro</h2>
                <button class="modal-fechar" onclick="fecharModalLivros()">&times;</button>
            </div>
            <div class="modal-body">
                <input type="text" id="buscaLivros" placeholder="🔍 Buscar livro..." style="width:100%;padding:1rem;margin-bottom:1rem;font-size:1.4rem;border:2px solid var(--border-color);border-radius:0.6rem;" oninput="filtrarLivros()">
                <div id="listaLivros"></div>
            </div>
        </div>
    </div>

    <!-- Modal de Seleção de Aluno -->
    <div id="modalAlunos" class="modal-overlay">
        <div class="modal-conteudo">
            <div class="modal-header">
                <h2>👤 Selecionar Aluno</h2>
                <button class="modal-fechar" onclick="fecharModalAlunos()">&times;</button>
            </div>
            <div class="modal-body">
                <input type="text" id="buscaAlunos" placeholder="🔍 Buscar aluno..." style="width:100%;padding:1rem;margin-bottom:1rem;font-size:1.4rem;border:2px solid var(--border-color);border-radius:0.6rem;" oninput="filtrarAlunos()">
                <div id="listaAlunos"></div>
                <button type="button" class="btn-novo-emprestimo" style="margin-top:1rem;width:100%;" onclick="window.location.href='usuarios.php?acao=novo'">
                    ➕ Cadastrar Novo Aluno
                </button>
            </div>
        </div>
    </div>

    <script>
        let todosLivros = [];
        let todosAlunos = [];

        // Carregar dados
        async function carregarDados() {
            try {
                // Carregar livros
                const respLivros = await fetch('listar_livros.php?ajax=1');
                todosLivros = await respLivros.json();

                // Carregar alunos
                const respAlunos = await fetch('usuarios.php?ajax=1');
                todosAlunos = await respAlunos.json();

                renderizarLivros();
                renderizarAlunos();
            } catch (erro) {
                console.error('Erro ao carregar dados:', erro);
            }
        }

        // Modal de Empréstimo
        function abrirModalEmprestimo() {
            document.getElementById('modalEmprestimo').classList.add('ativo');
            carregarDados();
        }

        function fecharModalEmprestimo() {
            document.getElementById('modalEmprestimo').classList.remove('ativo');
        }

        // Modal de Livros
        function abrirModalLivros() {
            document.getElementById('modalLivros').classList.add('ativo');
            fecharModalEmprestimo();
        }

        function fecharModalLivros() {
            document.getElementById('modalLivros').classList.remove('ativo');
            abrirModalEmprestimo();
        }

        // Modal de Alunos
        function abrirModalAlunos() {
            document.getElementById('modalAlunos').classList.add('ativo');
            fecharModalEmprestimo();
        }

        function fecharModalAlunos() {
            document.getElementById('modalAlunos').classList.remove('ativo');
            abrirModalEmprestimo();
        }

        // Renderizar livros
        function renderizarLivros(filtro = '') {
            const container = document.getElementById('listaLivros');
            const filtrados = todosLivros.filter(l =>
                l.titulo.toLowerCase().includes(filtro.toLowerCase()) ||
                l.autor.toLowerCase().includes(filtro.toLowerCase())
            );

            container.innerHTML = filtrados.map(livro => `
                <div class="selecao-item" onclick="selecionarLivro(${livro.id}, '${livro.titulo.replace(/'/g, "\\'")}')">
                    <div>
                        <div class="item-nome">${livro.titulo}</div>
                        <div class="item-detalhes">${livro.autor} • ${livro.quantidade} disponível</div>
                    </div>
                    <span>✓</span>
                </div>
            `).join('');
        }

        // Renderizar alunos
        function renderizarAlunos(filtro = '') {
            const container = document.getElementById('listaAlunos');
            const filtrados = todosAlunos.filter(a =>
                a.nome.toLowerCase().includes(filtro.toLowerCase()) ||
                a.email.toLowerCase().includes(filtro.toLowerCase())
            );

            container.innerHTML = filtrados.map(aluno => `
                <div class="selecao-item" onclick="selecionarAluno(${aluno.id}, '${aluno.nome.replace(/'/g, "\\'")}')">
                    <div>
                        <div class="item-nome">${aluno.nome}</div>
                        <div class="item-detalhes">${aluno.email}</div>
                    </div>
                    <span>✓</span>
                </div>
            `).join('');
        }

        function filtrarLivros() {
            const filtro = document.getElementById('buscaLivros').value;
            renderizarLivros(filtro);
        }

        function filtrarAlunos() {
            const filtro = document.getElementById('buscaAlunos').value;
            renderizarAlunos(filtro);
        }

        function selecionarLivro(id, titulo) {
            document.getElementById('selectedLivro').value = id;
            document.getElementById('inputLivro').value = titulo;
            fecharModalLivros();
        }

        function selecionarAluno(id, nome) {
            document.getElementById('selectedAluno').value = id;
            document.getElementById('inputAluno').value = nome;
            fecharModalAlunos();
        }

        // Submeter formulário
        document.getElementById('formEmprestimo').addEventListener('submit', async function(e) {
            e.preventDefault();

            const formData = new FormData(this);

            try {
                const resp = await fetch('emprestimos.php?ajax=1', {
                    method: 'POST',
                    body: formData
                });

                const resultado = await resp.json();

                if (resultado.sucesso) {
                    alert(resultado.mensagem);
                    fecharModalEmprestimo();
                    location.reload();
                } else {
                    alert('Erro: ' + resultado.erro);
                }
            } catch (erro) {
                console.error('Erro:', erro);
                alert('Erro ao processar empréstimo.');
            }
        });

        // Fechar modais com ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                fecharModalAlunos();
                fecharModalLivros();
                fecharModalEmprestimo();
            }
        });
    </script>

    <!-- Botão Dark Mode -->
    <button id="themeToggle" class="theme-toggle-float" aria-label="Alternar tema claro/escuro">
        <span id="themeIcon">🌙</span>
        <span id="themeLabel">Escuro</span>
    </button>

    <script src="darkmode.js"></script>
</body>
</html>
