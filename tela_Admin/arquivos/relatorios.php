<?php
require $_SERVER['DOCUMENT_ROOT'] . '/LibraFlow/cadastros_e_logins/configs/auth_check.php';
require $_SERVER['DOCUMENT_ROOT'] . '/LibraFlow/tela_Admin/arquivos/conexao.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatórios - LibraFlow</title>
    <link rel="stylesheet" href="style.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        .page-header {
            background: linear-gradient(135deg, #1e3a5f 0%, #2d6a4f 100%);
            color: white;
            padding: 32px 40px;
            display: flex;
            align-items: center;
            gap: 16px;
            border-radius: 12px;
            margin-bottom: 28px;
        }

        .page-header .icon { font-size: 2.2rem; }

        .page-header h1 {
            font-size: 1.8rem;
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        .page-header p {
            font-size: 0.9rem;
            opacity: 0.75;
            margin-top: 4px;
        }

        .container {
            max-width: 1100px;
        }

        /* --- Filtro de período --- */
        .filtro-card {
            background: white;
            border-radius: 12px;
            padding: 28px 32px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.07);
            margin-bottom: 36px;
            display: flex;
            flex-wrap: wrap;
            align-items: flex-end;
            gap: 20px;
        }

        .filtro-card h2 {
            width: 100%;
            font-size: 1rem;
            font-weight: 600;
            color: #4a5568;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }

        .campo-grupo {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .campo-grupo label {
            font-size: 0.82rem;
            font-weight: 600;
            color: #718096;
        }

        .campo-grupo input[type="date"] {
            padding: 10px 14px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.95rem;
            color: #2d3748;
            transition: border-color 0.2s;
            cursor: pointer;
        }

        .campo-grupo input[type="date"]:focus {
            outline: none;
            border-color: #2d6a4f;
        }

        .btn-aplicar {
            padding: 11px 28px;
            background: #2d6a4f;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s, transform 0.1s;
        }

        .btn-aplicar:hover { background: #1e4d38; transform: translateY(-1px); }

        /* --- Cards de relatório --- */
        .secao-titulo {
            font-size: 1.1rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .secao-titulo::after {
            content: '';
            flex: 1;
            height: 2px;
            background: #e2e8f0;
            border-radius: 2px;
        }

        .relatorios-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .relatorio-card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.07);
            border-left: 4px solid var(--cor);
            display: flex;
            flex-direction: column;
            gap: 12px;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .relatorio-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.12);
        }

        .card-topo {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .card-icone {
            width: 46px;
            height: 46px;
            border-radius: 10px;
            background: color-mix(in srgb, var(--cor) 15%, white);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            flex-shrink: 0;
        }

        .card-info h3 {
            font-size: 1rem;
            font-weight: 700;
            color: #1a202c;
        }

        .card-info p {
            font-size: 0.83rem;
            color: #718096;
            margin-top: 2px;
        }

        .card-acoes {
            display: flex;
            gap: 10px;
            margin-top: 4px;
        }

        .btn-gerar {
            flex: 1;
            padding: 9px 0;
            border: none;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            text-decoration: none;
        }

        .btn-pdf {
            background: #fff1f0;
            color: #c53030;
            border: 2px solid #fed7d7;
        }

        .btn-pdf:hover { background: #c53030; color: white; border-color: #c53030; }

        .btn-excel {
            background: #f0fff4;
            color: #276749;
            border: 2px solid #c6f6d5;
        }

        .btn-excel:hover { background: #276749; color: white; border-color: #276749; }

        /* Cores por relatório */
        .cor-vermelho  { --cor: #e53e3e; }
        .cor-azul      { --cor: #3182ce; }
        .cor-verde     { --cor: #38a169; }
        .cor-laranja   { --cor: #dd6b20; }
        .cor-roxo      { --cor: #805ad5; }

        /* Aluno específico */
        .campo-aluno {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }

        .campo-aluno select {
            flex: 1;
            min-width: 180px;
            padding: 9px 14px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.9rem;
            color: #2d3748;
            background: white;
        }

        .campo-aluno select:focus {
            outline: none;
            border-color: #805ad5;
        }

        /* Toast de feedback */
        .toast {
            position: fixed;
            bottom: 28px;
            right: 28px;
            background: #1a202c;
            color: white;
            padding: 14px 22px;
            border-radius: 10px;
            font-size: 0.9rem;
            box-shadow: 0 8px 24px rgba(0,0,0,0.2);
            display: none;
            z-index: 999;
            animation: slideUp 0.3s ease;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 600px) {
            .page-header { padding: 24px 20px; }
            .filtro-card { padding: 20px; }
            .relatorios-grid { grid-template-columns: 1fr; }
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
                <li><a href="/LibraFlow/cadastros_e_logins/logout/logout.php">🚪 Sair</a></li>
            </div>
        </ul>
    </aside>

    <nav>
        <div class="logo-nav">
            <span style="font-family:'Lora',serif;font-size:2rem;color:#283618;">Relatórios</span>
        </div>
        <div class="right">
            <div class="user" style="font-size:1.4rem;color:#606C38;font-family:'Source Sans 3',sans-serif;">
                👤 <?= htmlspecialchars($_SESSION['usuario_nome']) ?>
            </div>
        </div>
    </nav>

    <main>

    <div class="page-header">
        <span class="icon">📊</span>
        <div>
            <h1>Central de Relatórios</h1>
            <p>Gere e baixe relatórios da biblioteca em PDF ou Excel</p>
        </div>
    </div>

    <div class="container">

        <!-- Filtro de Período -->
        <div class="filtro-card">
            <h2>🗓️ Período dos relatórios</h2>
            <div class="campo-grupo">
                <label>Data início</label>
                <input type="date" id="data_inicio" value="<?= date('Y-m-01') ?>">
            </div>
            <div class="campo-grupo">
                <label>Data fim</label>
                <input type="date" id="data_fim" value="<?= date('Y-m-d') ?>">
            </div>
            <button class="btn-aplicar" onclick="validarPeriodo()">✔ Aplicar período</button>
        </div>

        <!-- Grid de Relatórios -->
        <p class="secao-titulo">Relatórios disponíveis</p>
        <div class="relatorios-grid">

            <!-- 1. Empréstimos vencidos -->
            <div class="relatorio-card cor-vermelho">
                <div class="card-topo">
                    <div class="card-icone">⏰</div>
                    <div class="card-info">
                        <h3>Empréstimos Vencidos</h3>
                        <p>Alunos com livros atrasados e dias de atraso</p>
                    </div>
                </div>
                <div class="card-acoes">
                    <button class="btn-gerar btn-pdf" onclick="gerar('vencidos','pdf')">📄 PDF</button>
                    <button class="btn-gerar btn-excel" onclick="gerar('vencidos','excel')">📊 Excel</button>
                </div>
            </div>

            <!-- 2. Empréstimos do período -->
            <div class="relatorio-card cor-azul">
                <div class="card-topo">
                    <div class="card-icone">📅</div>
                    <div class="card-info">
                        <h3>Empréstimos no Período</h3>
                        <p>Total de livros emprestados no período selecionado</p>
                    </div>
                </div>
                <div class="card-acoes">
                    <button class="btn-gerar btn-pdf" onclick="gerar('emprestimos_periodo','pdf')">📄 PDF</button>
                    <button class="btn-gerar btn-excel" onclick="gerar('emprestimos_periodo','excel')">📊 Excel</button>
                </div>
            </div>

            <!-- 3. Visitas à biblioteca -->
            <div class="relatorio-card cor-verde">
                <div class="card-topo">
                    <div class="card-icone">🚪</div>
                    <div class="card-info">
                        <h3>Visitas à Biblioteca</h3>
                        <p>Quantidade de visitas por dia no período</p>
                    </div>
                </div>
                <div class="card-acoes">
                    <button class="btn-gerar btn-pdf" onclick="gerar('visitas','pdf')">📄 PDF</button>
                    <button class="btn-gerar btn-excel" onclick="gerar('visitas','excel')">📊 Excel</button>
                </div>
            </div>

            <!-- 4. Livros mais populares -->
            <div class="relatorio-card cor-laranja">
                <div class="card-topo">
                    <div class="card-icone">🏆</div>
                    <div class="card-info">
                        <h3>Livros Mais Populares</h3>
                        <p>Ranking de livros por número de empréstimos</p>
                    </div>
                </div>
                <div class="card-acoes">
                    <button class="btn-gerar btn-pdf" onclick="gerar('populares','pdf')">📄 PDF</button>
                    <button class="btn-gerar btn-excel" onclick="gerar('populares','excel')">📊 Excel</button>
                </div>
            </div>

            <!-- 5. Histórico por aluno -->
            <div class="relatorio-card cor-roxo">
                <div class="card-topo">
                    <div class="card-icone">👤</div>
                    <div class="card-info">
                        <h3>Histórico por Aluno</h3>
                        <p>Todos os empréstimos de um aluno específico</p>
                    </div>
                </div>
                <!-- Seletor de aluno -->
                <div class="campo-aluno">
                    <select id="select_aluno">
                        <option value="">— Selecione o aluno —</option>
                        <?php
                        // CORRIGIDO: $conn (não $pdo) -- é a variável definida no seu conexao.php
                        $stmt = $conn->query("SELECT id, nome FROM usuarios WHERE tipo = 'A' ORDER BY nome ASC");
                        while ($row = $stmt->fetch()) {
                            echo "<option value='{$row['id']}'>" . htmlspecialchars($row['nome']) . "</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="card-acoes">
                    <button class="btn-gerar btn-pdf" onclick="gerarAluno('pdf')">📄 PDF</button>
                    <button class="btn-gerar btn-excel" onclick="gerarAluno('excel')">📊 Excel</button>
                </div>
            </div>

        </div>
    </div>

    <!-- Toast de feedback -->
    <div class="toast" id="toast">⏳ Gerando relatório...</div>

    <script>
    function validarPeriodo() {
        const ini = document.getElementById('data_inicio').value;
        const fim = document.getElementById('data_fim').value;
        if (!ini || !fim) { alert('Preencha as duas datas.'); return false; }
        if (ini > fim) { alert('A data início não pode ser maior que a data fim.'); return false; }
        mostrarToast('✔ Período aplicado!');
        return true;
    }

    // CORRIGIDO: gerar_relatorio.php está na MESMA pasta (tela_Admin/arquivos/),
    // então o caminho é direto, sem "../../"
    function gerar(tipo, formato) {
        const ini = document.getElementById('data_inicio').value;
        const fim = document.getElementById('data_fim').value;
        if (!ini || !fim) { alert('Selecione o período antes de gerar o relatório.'); return; }
        mostrarToast('⏳ Gerando relatório...');
        const url = `../relatorios/gerar_relatorio.php?tipo=${tipo}&formato=${formato}&inicio=${ini}&fim=${fim}`;
        window.open(url, '_blank');
    }

    function gerarAluno(formato) {
        const aluno_id = document.getElementById('select_aluno').value;
        if (!aluno_id) { alert('Selecione um aluno primeiro.'); return; }
        const ini = document.getElementById('data_inicio').value;
        const fim = document.getElementById('data_fim').value;
        if (!ini || !fim) { alert('Selecione o período antes de gerar o relatório.'); return; }
        mostrarToast('⏳ Gerando relatório...');
        const url = `../relatorios/gerar_relatorio.php?tipo=historico_aluno&formato=${formato}&inicio=${ini}&fim=${fim}&aluno_id=${aluno_id}`;
        window.open(url, '_blank');
    }

    function mostrarToast(msg) {
        const t = document.getElementById('toast');
        t.textContent = msg;
        t.style.display = 'block';
        setTimeout(() => t.style.display = 'none', 2500);
    }
    </script>
    </main>
</body>
</html>

