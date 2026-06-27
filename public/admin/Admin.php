<?php
/*es-operacionais
 * MAPA RAPIDO DO ARQUIVO
 * Local: public/admin/Admin.php
 * Funcao: Dashboard inicial do administrador com atalhos e indicadores.
 */
// public/admin/Admin.php

require $_SERVER['DOCUMENT_ROOT'] . '/LibraFlow/app/config/auth_check.php';

if ($_SESSION['usuario_tipo'] !== 'D') {
    header('Location: /LibraFlow/public/usuario/index.php');
    exit;
}

require $_SERVER['DOCUMENT_ROOT'] . '/LibraFlow/app/config/conexao.php';

$conn->exec("
    UPDATE emprestimos
    SET status = 'V'
    WHERE status = 'A'
      AND data_prevista_devolucao < CURDATE()
");

// Dados do resumo
$totalEmprestimos = $conn->query("SELECT COUNT(*) FROM emprestimos WHERE status = 'A'")->fetchColumn();
$totalAtraso      = $conn->query("SELECT COUNT(*) FROM emprestimos WHERE status = 'V'")->fetchColumn();
$totalUsuarios    = $conn->query("SELECT COUNT(*) FROM usuarios WHERE tipo = 'A'")->fetchColumn();
$totalLivros      = $conn->query("SELECT COUNT(*) FROM livros")->fetchColumn();
$totalVisitasMes  = $conn->query("SELECT COALESCE(SUM(quantidade), 0) FROM visitas_biblioteca WHERE data_registro >= DATE_FORMAT(CURDATE(), '%Y-%m-01')")->fetchColumn();

// Dados dos graficos do painel inicial.
$emprestimosPorMes = $conn->query("
    SELECT DATE_FORMAT(data_emprestimo, '%m/%Y') AS rotulo, COUNT(*) AS total
    FROM emprestimos
    WHERE data_emprestimo >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 5 MONTH), '%Y-%m-01')
    GROUP BY YEAR(data_emprestimo), MONTH(data_emprestimo)
    ORDER BY YEAR(data_emprestimo), MONTH(data_emprestimo)
")->fetchAll();

$statusEmprestimos = $conn->query("
    SELECT
        CASE status
            WHEN 'A' THEN 'Ativos'
            WHEN 'D' THEN 'Devolvidos'
            WHEN 'V' THEN 'Vencidos'
            ELSE 'Outros'
        END AS rotulo,
        COUNT(*) AS total
    FROM emprestimos
    GROUP BY status
    ORDER BY FIELD(status, 'A', 'D', 'V')
")->fetchAll();

$livrosMaisEmprestados = $conn->query("
    SELECT l.titulo AS rotulo, COUNT(e.id) AS total
    FROM emprestimos e
    JOIN livros l ON l.id = e.id_livro
    GROUP BY l.id, l.titulo
    ORDER BY total DESC, l.titulo ASC
    LIMIT 5
")->fetchAll();

$categoriasMaisProcuradas = $conn->query("
    SELECT COALESCE(c.nome, 'Sem categoria') AS rotulo, COUNT(e.id) AS total
    FROM emprestimos e
    JOIN livros l ON l.id = e.id_livro
    LEFT JOIN categorias c ON c.id = l.id_categoria
    GROUP BY c.id, c.nome
    ORDER BY total DESC, rotulo ASC
    LIMIT 6
")->fetchAll();

$visitasPorPeriodo = $conn->query("
    SELECT periodo AS rotulo, COALESCE(SUM(quantidade), 0) AS total
    FROM visitas_biblioteca
    WHERE data_registro >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
    GROUP BY periodo
    ORDER BY FIELD(periodo, 'Manha', 'Tarde', 'Noite'), periodo
")->fetchAll();


// Pendencias operacionais para a secao Atencao do Dia.
$emprestimosVencemHoje = $conn->query("
    SELECT e.id, l.titulo, u.nome, e.data_prevista_devolucao
    FROM emprestimos e
    JOIN livros l ON l.id = e.id_livro
    JOIN usuarios u ON u.id = e.id_usuario
    WHERE e.status = 'A'
      AND e.data_prevista_devolucao = CURDATE()
    ORDER BY u.nome ASC, l.titulo ASC
    LIMIT 3
")->fetchAll();

$emprestimosAtrasados = $conn->query("
    SELECT e.id, l.titulo, u.nome, e.data_prevista_devolucao
    FROM emprestimos e
    JOIN livros l ON l.id = e.id_livro
    JOIN usuarios u ON u.id = e.id_usuario
    WHERE e.status = 'V'
    ORDER BY e.data_prevista_devolucao ASC
    LIMIT 3
")->fetchAll();

$livrosSemEstoque = $conn->query("
    SELECT id, titulo, autor, quantidade
    FROM livros
    WHERE quantidade <= 0
    ORDER BY titulo ASC
    LIMIT 3
")->fetchAll();

$usuariosCadastroIncompleto = $conn->query("
    SELECT id, nome, email
    FROM usuarios
    WHERE tipo = 'A'
      AND (
          telefone IS NULL OR telefone = '' OR
          rm IS NULL OR rm = '' OR
          endereco IS NULL OR endereco = '' OR
          idade IS NULL OR idade <= 0
      )
    ORDER BY nome ASC
    LIMIT 3
")->fetchAll();

$totalVencemHoje = $conn->query("SELECT COUNT(*) FROM emprestimos WHERE status = 'A' AND data_prevista_devolucao = CURDATE()")->fetchColumn();
$totalSemEstoque = $conn->query("SELECT COUNT(*) FROM livros WHERE quantidade <= 0")->fetchColumn();
$totalCadastrosIncompletos = $conn->query("
    SELECT COUNT(*)
    FROM usuarios
    WHERE tipo = 'A'
      AND (
          telefone IS NULL OR telefone = '' OR
          rm IS NULL OR rm = '' OR
          endereco IS NULL OR endereco = '' OR
          idade IS NULL OR idade <= 0
      )
")->fetchColumn();

$atencaoDia = [
    [
        'icone' => 'fa-calendar-day',
        'classe' => 'info',
        'titulo' => 'Vencem hoje',
        'total' => (int) $totalVencemHoje,
        'descricao' => 'empréstimos precisam ser acompanhados hoje',
        'link' => '/LibraFlow/public/admin/emprestimos.php?status=A',
        'acao' => 'Ver empréstimos',
        'itens' => array_map(static function ($item) {
            return $item['nome'] . ' - ' . $item['titulo'];
        }, $emprestimosVencemHoje),
    ],
    [
        'icone' => 'fa-triangle-exclamation',
        'classe' => 'alerta',
        'titulo' => 'Em atraso',
        'total' => (int) $totalAtraso,
        'descricao' => 'empréstimos vencidos aguardam devolução',
        'link' => '/LibraFlow/public/admin/emprestimos.php?status=V',
        'acao' => 'Regularizar',
        'itens' => array_map(static function ($item) {
            return $item['nome'] . ' - ' . $item['titulo'];
        }, $emprestimosAtrasados),
    ],
    [
        'icone' => 'fa-box-open',
        'classe' => 'estoque',
        'titulo' => 'Sem estoque',
        'total' => (int) $totalSemEstoque,
        'descricao' => 'livros estão sem exemplares disponíveis',
        'link' => '/LibraFlow/public/admin/listar_livros.php',
        'acao' => 'Revisar acervo',
        'itens' => array_map(static function ($item) {
            return $item['titulo'];
        }, $livrosSemEstoque),
    ],
    [
        'icone' => 'fa-user-pen',
        'classe' => 'cadastro',
        'titulo' => 'Cadastro incompleto',
        'total' => (int) $totalCadastrosIncompletos,
        'descricao' => 'alunos precisam completar dados cadastrais',
        'link' => '/LibraFlow/public/admin/usuarios.php',
        'acao' => 'Conferir usuários',
        'itens' => array_map(static function ($item) {
            return $item['nome'];
        }, $usuariosCadastroIncompleto),
    ],
];

$sugestoesReposicao = $conn->query("
    SELECT l.id, l.titulo, l.quantidade, COUNT(e.id) AS total_emprestimos
    FROM livros l
    LEFT JOIN emprestimos e ON e.id_livro = l.id
    GROUP BY l.id, l.titulo, l.quantidade
    HAVING l.quantidade <= 1 OR total_emprestimos >= 3
    ORDER BY l.quantidade ASC, total_emprestimos DESC, l.titulo ASC
    LIMIT 5
")->fetchAll();

$notificacoes = [];
if ((int) $totalAtraso > 0) {
    $notificacoes[] = ['tipo' => 'critica', 'texto' => $totalAtraso . ' empréstimo(s) em atraso'];
}
if ((int) $totalVencemHoje > 0) {
    $notificacoes[] = ['tipo' => 'aviso', 'texto' => $totalVencemHoje . ' devolução(ões) vencem hoje'];
}
if ((int) $totalSemEstoque > 0) {
    $notificacoes[] = ['tipo' => 'aviso', 'texto' => $totalSemEstoque . ' livro(s) sem estoque'];
}
if ((int) $totalCadastrosIncompletos > 0) {
    $notificacoes[] = ['tipo' => 'info', 'texto' => $totalCadastrosIncompletos . ' cadastro(s) incompleto(s)'];
}
if (!$notificacoes) {
    $notificacoes[] = ['tipo' => 'ok', 'texto' => 'Nenhuma pendência crítica no momento'];
}
$dashboardGraficos = [
    'emprestimosPorMes' => $emprestimosPorMes,
    'statusEmprestimos' => $statusEmprestimos,
    'livrosMaisEmprestados' => $livrosMaisEmprestados,
    'categoriasMaisProcuradas' => $categoriasMaisProcuradas,
    'visitasPorPeriodo' => $visitasPorPeriodo,
];

// Histórico recente
$historico = $conn->query("
    SELECT e.data_emprestimo, l.titulo, u.nome, e.status
    FROM emprestimos e
    JOIN livros l   ON l.id = e.id_livro
    JOIN usuarios u ON u.id = e.id_usuario
    ORDER BY e.data_emprestimo DESC
    LIMIT 10
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Administrativo | LibraFlow</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="darkmode-btn.css">
    <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400..700;1,400..700&family=Source+Sans+3:ital,wght@0,200..900;1,200..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="admin-home">
    <aside>
        <div class="logo-aside">
            <span>LibraFlow</span>
        </div>
        <ul>
             <li><a href="/LibraFlow/public/admin/Admin.php" class="ativo"><i class="fas fa-house nav-icon" aria-hidden="true"></i> Início</a></li>
            <li><a href="/LibraFlow/public/admin/listar_livros.php"><i class="fas fa-book-open nav-icon" aria-hidden="true"></i> Livros</a></li>
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
        <div class="logo-nav">
            <span style="font-family:'Lora',serif;font-size:2rem;color:#283618;">Painel Admin</span>
        </div>
        <div class="right">
            <div class="notificacoes-admin">
                <button type="button" id="btnNotificacoes" aria-label="Abrir notificações"><i class="fas fa-bell" aria-hidden="true"></i><span><?= count($notificacoes) ?></span></button>
                <div id="painelNotificacoes" class="notificacoes-painel">
                    <?php foreach ($notificacoes as $notificacao): ?>
                        <div class="notificacao-item notificacao-<?= htmlspecialchars($notificacao['tipo']) ?>"><?= htmlspecialchars($notificacao['texto']) ?></div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="user" style="font-size:1.4rem;color:#606C38;font-family:'Source Sans 3',sans-serif;">
                <i class="fas fa-user" aria-hidden="true"></i> <?= htmlspecialchars($_SESSION['usuario_nome']) ?>
            </div>
        </div>
    </nav>

    <header>
        <h1>Painel Administrativo</h1>
        <p>Bem-vindo ao painel do LibraFlow. Gerencie livros, usuários e empréstimos.</p>
    </header>

    <main>
        <section class="busca-rapida-admin" aria-label="Busca rápida">
            <div>
                <h2>Busca rápida</h2>
                <p>Encontre alunos e livros sem sair do painel.</p>
            </div>
            <div class="busca-rapida-campo">
                <i class="fas fa-magnifying-glass" aria-hidden="true"></i>
                <input type="search" id="buscaRapida" placeholder="Buscar aluno, e-mail, RM, livro, autor ou ISBN...">
            </div>
            <div id="resultadoBuscaRapida" class="busca-rapida-resultados"></div>
        </section>
        <section class="resumo">
            <div class="card-1">
                <h2>Empréstimos Ativos</h2>
                <p><?= $totalEmprestimos ?></p>
            </div>
            <div class="card-2">
                <h2>Em Atraso</h2>
                <p><?= $totalAtraso ?></p>
            </div>
            <div class="card-3">
                <h2>Alunos Cadastrados</h2>
                <p><?= $totalUsuarios ?></p>
            </div>
            <div class="card-4">
                <h2>Total de Livros</h2>
                <p><?= $totalLivros ?></p>
            </div>
        </section>


        <section class="atencao-dia" aria-label="Atenção do dia">
            <div class="atencao-dia-topo">
                <div>
                    <h2>Atenção do dia</h2>
                    <p>Pendências e oportunidades que merecem ação rápida do bibliotecário.</p>
                </div>
                <a href="/LibraFlow/public/admin/novo_emprestimo.php" class="atencao-acao-principal">
                    <i class="fas fa-plus" aria-hidden="true"></i> Fazer empréstimo
                </a>
            </div>

            <div class="atencao-grid">
                <?php foreach ($atencaoDia as $card): ?>
                    <article class="atencao-card atencao-<?= htmlspecialchars($card['classe']) ?>">
                        <div class="atencao-card-topo">
                            <span class="atencao-icone"><i class="fas <?= htmlspecialchars($card['icone']) ?>" aria-hidden="true"></i></span>
                            <div>
                                <h3><?= htmlspecialchars($card['titulo']) ?></h3>
                                <strong><?= $card['total'] ?></strong>
                            </div>
                        </div>
                        <p><?= htmlspecialchars($card['descricao']) ?></p>

                        <?php if ($card['itens']): ?>
                            <ul>
                                <?php foreach ($card['itens'] as $item): ?>
                                    <li><?= htmlspecialchars($item) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <div class="atencao-ok">Nenhuma pendência encontrada.</div>
                        <?php endif; ?>

                        <a class="atencao-link" href="<?= htmlspecialchars($card['link']) ?>">
                            <?= htmlspecialchars($card['acao']) ?> <i class="fas fa-arrow-right" aria-hidden="true"></i>
                        </a>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="acoes-operacionais">
            <article class="operacao-card">
                <div class="operacao-card-topo"><h2>Devolução rápida</h2><a href="emprestimos.php?status=V">Ver todos</a></div>
                <?php if ($emprestimosAtrasados): ?>
                    <div class="lista-operacional">
                        <?php foreach ($emprestimosAtrasados as $item): ?>
                            <form method="POST" class="linha-operacional">
                                <input type="hidden" name="acao" value="devolver_rapido">
                                <input type="hidden" name="id_emprestimo" value="<?= (int) $item['id'] ?>">
                                <span><strong><?= htmlspecialchars($item['nome']) ?></strong><small><?= htmlspecialchars($item['titulo']) ?></small></span>
                                <button type="submit">Registrar devolução</button>
                            </form>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="operacao-vazia">Nenhum empréstimo atrasado para baixa rápida.</p>
                <?php endif; ?>
            </article>

            <article class="operacao-card">
                <div class="operacao-card-topo"><h2>Sugestões de reposição</h2><a href="listar_livros.php">Acervo</a></div>
                <?php if ($sugestoesReposicao): ?>
                    <div class="lista-operacional">
                        <?php foreach ($sugestoesReposicao as $item): ?>
                            <a class="linha-operacional" href="detalhe_livro.php?id=<?= (int) $item['id'] ?>">
                                <span><strong><?= htmlspecialchars($item['titulo']) ?></strong><small><?= (int) $item['quantidade'] ?> disponível(is), <?= (int) $item['total_emprestimos'] ?> retirada(s)</small></span>
                                <i class="fas fa-arrow-right" aria-hidden="true"></i>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="operacao-vazia">Nenhuma reposição sugerida no momento.</p>
                <?php endif; ?>
            </article>
        </section>

        <section class="painel-graficos" aria-label="Painel de graficos da biblioteca">
            <div class="painel-graficos-topo">
                <div>
                    <h2>Painel de gráficos</h2>
                    <p>Comparativos rápidos para acompanhar empréstimos, acervo e movimento da biblioteca.</p>
                    <div class="dashboard-exportacoes"><a href="dashboard_export.php?formato=html" target="_blank"><i class="fas fa-file-pdf" aria-hidden="true"></i> PDF</a><a href="dashboard_export.php?formato=csv"><i class="fas fa-file-excel" aria-hidden="true"></i> Excel</a></div>
                </div>
                <div class="painel-graficos-meta">
                    <span><?= (int) $totalVisitasMes ?></span>
                    <small>visitas no mês</small>
                </div>
            </div>

            <div class="graficos-grid">
                <article class="grafico-card grafico-card-wide">
                    <div class="grafico-card-header">
                        <h3>Empréstimos por mês</h3>
                        <span>últimos 6 meses</span>
                    </div>
                    <div class="grafico-canvas"><canvas id="graficoEmprestimosMes"></canvas></div>
                </article>

                <article class="grafico-card">
                    <div class="grafico-card-header">
                        <h3>Status dos empréstimos</h3>
                        <span>situação atual</span>
                    </div>
                    <div class="grafico-canvas"><canvas id="graficoStatus"></canvas></div>
                </article>

                <article class="grafico-card">
                    <div class="grafico-card-header">
                        <h3>Livros mais emprestados</h3>
                        <span>top 5</span>
                    </div>
                    <div class="grafico-canvas"><canvas id="graficoLivros"></canvas></div>
                </article>

                <article class="grafico-card">
                    <div class="grafico-card-header">
                        <h3>Categorias mais procuradas</h3>
                        <span>por empréstimos</span>
                    </div>
                    <div class="grafico-canvas"><canvas id="graficoCategorias"></canvas></div>
                </article>

                <article class="grafico-card">
                    <div class="grafico-card-header">
                        <h3>Visitas por período</h3>
                        <span>mês atual</span>
                    </div>
                    <div class="grafico-canvas"><canvas id="graficoVisitas"></canvas></div>
                </article>
            </div>
        </section>

        <section class="historico">
            <h2>Histórico Recente de Empréstimos</h2>
            <table>
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Livro</th>
                        <th>Usuário</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($historico)): ?>
                        <tr><td colspan="4" style="text-align:center;padding:2rem;color:#888">Nenhum empréstimo registrado.</td></tr>
                    <?php else: ?>
                        <?php foreach ($historico as $h): ?>
                        <tr>
                            <td><?= date('d/m/Y', strtotime($h['data_emprestimo'])) ?></td>
                            <td><?= htmlspecialchars($h['titulo']) ?></td>
                            <td><?= htmlspecialchars($h['nome']) ?></td>
                            <td>
                                <?php
                                $badges = ['A' => ['Ativo','#22C55E'], 'D' => ['Devolvido','#3B82F6'], 'V' => ['Vencido','#EF4444']];
                                $b = $badges[$h['status']] ?? ['?','#999'];
                                ?>
                                <span style="padding:0.3rem 0.9rem;border-radius:2rem;background:<?= $b[1] ?>22;color:<?= $b[1] ?>;font-weight:600;font-size:1.2rem;">
                                    <?= $b[0] ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
    </main>

    <!-- Botão Dark Mode -->
    <button id="themeToggle" class="theme-toggle-float" aria-label="Alternar tema claro/escuro">
        <span id="themeIcon"><i class="fas fa-moon" aria-hidden="true"></i></span>
        <span id="themeLabel">Escuro</span>
    </button>

    <script>
        window.LibraFlowDashboard = <?= json_encode($dashboardGraficos, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK) ?>;
    </script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        $(function () {
            let dados = window.LibraFlowDashboard || {};
            let graficos = {};
            const cores = ['#606C38', '#DDA15E', '#BC6C25', '#283618', '#A8C97F', '#3D405B'];
            const statusCores = ['#22C55E', '#3B82F6', '#EF4444', '#BC6C25'];

            function labels(lista) { return (lista || []).map(item => item.rotulo); }
            function valores(lista) { return (lista || []).map(item => Number(item.total || 0)); }
            function semDados(lista) { return !lista || lista.length === 0 || valores(lista).every(valor => valor === 0); }

            function resetCanvas(canvasId) {
                const canvas = $('#' + canvasId);
                canvas.show();
                canvas.closest('.grafico-canvas').removeClass('sem-dados').find('.grafico-vazio').remove();
                if (graficos[canvasId]) {
                    graficos[canvasId].destroy();
                    delete graficos[canvasId];
                }
            }

            function mostrarSemDados(canvasId) {
                const canvas = $('#' + canvasId);
                canvas.closest('.grafico-canvas').addClass('sem-dados').append('<div class="grafico-vazio">Sem dados suficientes</div>');
                canvas.hide();
            }

            function criarGrafico(canvasId, tipo, lista, opcoes) {
                resetCanvas(canvasId);
                if (semDados(lista)) { mostrarSemDados(canvasId); return; }
                graficos[canvasId] = new Chart(document.getElementById(canvasId), {
                    type: tipo,
                    data: { labels: labels(lista), datasets: [{ label: opcoes.label, data: valores(lista), backgroundColor: opcoes.backgroundColor, borderColor: opcoes.borderColor || opcoes.backgroundColor, borderWidth: 2, tension: 0.35, fill: opcoes.fill || false }] },
                    options: { responsive: true, maintainAspectRatio: false, indexAxis: opcoes.indexAxis || 'x', plugins: { legend: { display: opcoes.legend !== false }, tooltip: { displayColors: true } }, scales: opcoes.scales === false ? {} : { y: { beginAtZero: true, ticks: { precision: 0 } }, x: { ticks: { maxRotation: 0, autoSkip: true } } } }
                });
            }

            function desenharGraficos() {
                criarGrafico('graficoEmprestimosMes', 'line', dados.emprestimosPorMes, { label: 'Empréstimos', backgroundColor: 'rgba(188, 108, 37, 0.16)', borderColor: '#BC6C25', fill: true, legend: false });
                criarGrafico('graficoStatus', 'doughnut', dados.statusEmprestimos, { label: 'Empréstimos', backgroundColor: statusCores, scales: false });
                criarGrafico('graficoLivros', 'bar', dados.livrosMaisEmprestados, { label: 'Empréstimos', backgroundColor: '#606C38', indexAxis: 'y', legend: false });
                criarGrafico('graficoCategorias', 'bar', dados.categoriasMaisProcuradas, { label: 'Empréstimos', backgroundColor: cores, legend: false });
                criarGrafico('graficoVisitas', 'bar', dados.visitasPorPeriodo, { label: 'Visitas', backgroundColor: ['#606C38', '#DDA15E', '#BC6C25'], legend: false });
            }

            $('#periodoGraficos').on('change', function () {
                $.getJSON('dashboard_dados.php', { periodo: this.value })
                    .done(function (resposta) { dados = resposta; desenharGraficos(); });
            });

            let timerBusca = null;
            $('#buscaRapida').on('input', function () {
                const termo = this.value.trim();
                clearTimeout(timerBusca);
                if (termo.length < 2) { $('#resultadoBuscaRapida').empty().removeClass('ativo'); return; }
                timerBusca = setTimeout(function () {
                    $.getJSON('busca_rapida.php', { q: termo }).done(function (resposta) {
                        const alunos = resposta.alunos || [];
                        const livros = resposta.livros || [];
                        let html = '';
                        if (alunos.length) {
                            html += '<div><h3>Alunos</h3>' + alunos.map(a => `<a href="detalhe_aluno.php?id=${a.id}"><strong>${a.nome}</strong><span>${a.email || ''} · ${a.emprestimos_abertos || 0} aberto(s) · ${a.atrasos || 0} atraso(s)</span></a>`).join('') + '</div>';
                        }
                        if (livros.length) {
                            html += '<div><h3>Livros</h3>' + livros.map(l => `<a href="detalhe_livro.php?id=${l.id}"><strong>${l.titulo}</strong><span>${l.autor || ''} · ${l.quantidade || 0} disponível(is)</span></a>`).join('') + '</div>';
                        }
                        $('#resultadoBuscaRapida').html(html || '<div class="busca-vazia">Nenhum resultado encontrado.</div>').addClass('ativo');
                    });
                }, 250);
            });

            $('#btnNotificacoes').on('click', function () { $('#painelNotificacoes').toggleClass('ativo'); });
            $(document).on('click', function (event) {
                if (!$(event.target).closest('.notificacoes-admin').length) { $('#painelNotificacoes').removeClass('ativo'); }
            });

            desenharGraficos();
        });
    </script>
    <script src="darkmode.js"></script>
</body>
</html>
