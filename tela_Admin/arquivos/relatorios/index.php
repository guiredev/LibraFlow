<?php
require_once __DIR__ . '/../../../cadastros_e_logins/configs/auth_check.php';
require_once __DIR__ . '/../../../cadastros_e_logins/configs/conexao.php';
require_once __DIR__ . '/RelatorioService.php';

// Apenas admin pode acessar
if ($_SESSION['usuario_tipo'] !== 'D') {
    header('Location: /LibraFlow/cadastros_e_logins/login/arquivos/login.php');
    exit;
}

$pdo = $conn;
$servico = new RelatorioService($pdo);
$resumo = $servico->resumoGeral();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatórios — LibraFlow</title>
    <link rel="stylesheet" href="/LibraFlow/tela_Admin/arquivos/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Lora:wght@400;600;700&family=Source+Sans+3:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --bg:        #FFFFFF;
            --bg-card:   #F5F5F0;
            --bg-input:  #FFFFFF;
            --titulo:    #283618;
            --texto:     #3D405B;
            --link:      #BC6C25;
            --btn-bg:    #DDA15E;
            --btn-text:  #FEFAE0;
            --borda:     #D9CFC3;
            --sombra:    rgba(40,54,24,.08);
            --danger:    #C0392B;
            --success:   #27AE60;
            --warning:   #E0A100;
            --sidebar-w: 240px;
        }
        body.dark {
            --bg:        #1C2410;
            --bg-card:   #243015;
            --bg-input:  #1C2410;
            --titulo:    #D4E8B0;
            --texto:     #B8C9A0;
            --link:      #A8C97F;
            --btn-bg:    #4A6020;
            --btn-text:  #D4E8B0;
            --borda:     #3A4C20;
            --sombra:    rgba(0,0,0,.25);
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Source Sans 3', sans-serif;
            background: var(--bg);
            color: var(--texto);
            display: flex;
            min-height: 100vh;
            transition: background .3s, color .3s;
        }

        /* ── Sidebar ── */
        .sidebar {
            width: var(--sidebar-w);
            background: var(--bg-card);
            border-right: 1px solid var(--borda);
            padding: 28px 0;
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0; left: 0; bottom: 0;
            z-index: 100;
        }
        .sidebar-logo {
            font-family: 'Lora', serif;
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--titulo);
            padding: 0 24px 28px;
            border-bottom: 1px solid var(--borda);
            margin-bottom: 16px;
        }
        .sidebar-logo span { color: var(--link); }
        .sidebar-nav a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 11px 24px;
            color: var(--texto);
            text-decoration: none;
            font-size: .92rem;
            font-weight: 500;
            border-left: 3px solid transparent;
            transition: all .2s;
        }
        .sidebar-nav a:hover,
        .sidebar-nav a.ativo {
            color: var(--titulo);
            background: var(--bg);
            border-left-color: var(--btn-bg);
        }
        .sidebar-nav a i { width: 18px; text-align: center; }

        /* ── Main ── */
        .main {
            margin-left: var(--sidebar-w);
            flex: 1;
            padding: 40px 48px;
            max-width: 1100px;
        }

        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 32px;
        }
        .page-header h1 {
            font-family: 'Lora', serif;
            font-size: 1.7rem;
            font-weight: 700;
            color: var(--titulo);
        }
        .page-header p {
            font-size: .9rem;
            color: var(--texto);
            opacity: .7;
            margin-top: 4px;
        }
        .btn-tema {
            background: none;
            border: 1px solid var(--borda);
            border-radius: 8px;
            padding: 8px 12px;
            cursor: pointer;
            color: var(--texto);
            font-size: 1rem;
            transition: all .2s;
        }
        .btn-tema:hover { background: var(--bg-card); }

        /* ── Cards de resumo ── */
        .cards-resumo {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 18px;
            margin-bottom: 36px;
        }
        .card-resumo {
            background: var(--bg-card);
            border: 1px solid var(--borda);
            border-radius: 14px;
            padding: 20px 22px;
            box-shadow: 0 4px 16px var(--sombra);
        }
        .card-resumo .valor {
            font-family: 'Lora', serif;
            font-size: 2rem;
            font-weight: 700;
            color: var(--titulo);
        }
        .card-resumo .rotulo {
            font-size: .82rem;
            opacity: .75;
            margin-top: 4px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .card-resumo.alerta .valor { color: var(--danger); }
        .card-resumo.alerta .rotulo i { color: var(--danger); }

        /* ── Lista de relatórios ── */
        .secao-titulo {
            font-family: 'Lora', serif;
            font-size: 1.15rem;
            font-weight: 600;
            color: var(--titulo);
            margin-bottom: 16px;
        }
        .lista-relatorios {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 18px;
        }
        .relatorio-card {
            background: var(--bg-card);
            border: 1px solid var(--borda);
            border-radius: 14px;
            padding: 22px 24px;
            box-shadow: 0 4px 16px var(--sombra);
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .relatorio-card .icone {
            width: 44px; height: 44px;
            border-radius: 10px;
            background: var(--btn-bg);
            color: var(--btn-text);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.15rem;
        }
        .relatorio-card h3 {
            font-family: 'Lora', serif;
            font-size: 1.05rem;
            color: var(--titulo);
            font-weight: 600;
        }
        .relatorio-card p {
            font-size: .87rem;
            opacity: .75;
            line-height: 1.5;
        }
        .relatorio-card .filtros {
            display: flex;
            gap: 10px;
            margin-top: 4px;
            flex-wrap: wrap;
        }
        .relatorio-card .filtros input {
            background: var(--bg-input);
            border: 1.5px solid var(--borda);
            border-radius: 8px;
            padding: 8px 10px;
            font-size: .85rem;
            color: var(--texto);
            font-family: inherit;
        }
        .relatorio-card .acoes {
            display: flex;
            gap: 10px;
            margin-top: auto;
            padding-top: 10px;
        }
        .btn-relatorio {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 14px;
            border-radius: 9px;
            font-size: .88rem;
            font-weight: 600;
            text-decoration: none;
            border: 1.5px solid var(--borda);
            color: var(--texto);
            transition: all .2s;
        }
        .btn-relatorio:hover { transform: translateY(-1px); }
        .btn-pdf:hover  { border-color: var(--danger); color: var(--danger); }
        .btn-xlsx:hover { border-color: var(--success); color: var(--success); }
        .btn-pdf i  { color: var(--danger); }
        .btn-xlsx i { color: var(--success); }

        @media (max-width: 900px) {
            .main { margin-left: 0; padding: 24px 20px; }
            .sidebar { display: none; }
            .cards-resumo { grid-template-columns: repeat(2, 1fr); }
            .lista-relatorios { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<aside>
        <div class="logo-aside">
            <span>LibraFlow</span>
        </div>
        <ul>
            <li><a href="/LibraFlow/tela_Admin/arquivos/Admin.php">🏠 Início</a></li>
            <li><a href="/LibraFlow/tela_Admin/arquivos/listar_livros.php">📚 Livros</a></li>
            <li><a href="/LibraFlow/tela_Admin/arquivos/cadastrar_livro.php">➕ Cadastrar Livro</a></li>
            <li><a href="/LibraFlow/tela_Admin/arquivos/usuarios.php">👥 Usuários</a></li>
            <li><a href="/LibraFlow/tela_Admin/arquivos/emprestimos.php">📋 Empréstimos</a></li>
            <li><a href="/LibraFlow/tela_Admin/arquivos/relatorios/index.php" class="ativo">📈 Relatórios</a></li>
            <li><a href="/LibraFlow/tela_Admin/arquivos/visitas.php">Visitas</a></li>
            <div class="sidebar-down">
                <li><a href="/LibraFlow/cadastros_e_logins/logout/logout.php">🚪 Sair</a></li>
            </div>
        </ul>
    </aside>

<main class="main">
    <div class="page-header">
        <div>
            <h1><i class="fas fa-chart-column" style="font-size:1.3rem;margin-right:8px;color:var(--link)"></i>Relatórios</h1>
            <p>Visualize dados do acervo e baixe relatórios em PDF ou Excel.</p>
        </div>
        <button class="btn-tema" id="btnTema" title="Alternar tema">🌙</button>
    </div>

    <!-- Cards resumo -->
    <div class="cards-resumo">
        <div class="card-resumo">
            <div class="valor"><?= $resumo['total_livros'] ?></div>
            <div class="rotulo"><i class="fas fa-book"></i> Exemplares no acervo</div>
        </div>
        <div class="card-resumo">
            <div class="valor"><?= $resumo['total_alunos'] ?></div>
            <div class="rotulo"><i class="fas fa-user-graduate"></i> Alunos cadastrados</div>
        </div>
        <div class="card-resumo">
            <div class="valor"><?= $resumo['emprestimos_ativos'] ?></div>
            <div class="rotulo"><i class="fas fa-arrow-right-arrow-left"></i> Empréstimos ativos</div>
        </div>
        <div class="card-resumo <?= $resumo['emprestimos_atrasados'] > 0 ? 'alerta' : '' ?>">
            <div class="valor"><?= $resumo['emprestimos_atrasados'] ?></div>
            <div class="rotulo"><i class="fas fa-triangle-exclamation"></i> Em atraso</div>
        </div>
    </div>

    <!-- Lista de relatórios -->
    <div class="secao-titulo">Relatórios Disponíveis</div>
    <div class="lista-relatorios">

        <!-- Empréstimos em atraso -->
        <div class="relatorio-card">
            <div class="icone"><i class="fas fa-triangle-exclamation"></i></div>
            <h3>Empréstimos em Atraso</h3>
            <p>Lista de alunos com livros em atraso (prazo de <?= \RelatorioService::PRAZO_DIAS ?> dias),
               incluindo dados de contato e dias de atraso.</p>
            <div class="acoes">
                <a class="btn-relatorio btn-pdf"  href="gerar_relatorio.php?tipo=atrasados&formato=pdf"><i class="fas fa-file-pdf"></i> PDF</a>
                <a class="btn-relatorio btn-xlsx" href="gerar_relatorio.php?tipo=atrasados&formato=xlsx"><i class="fas fa-file-excel"></i> Excel</a>
            </div>
        </div>

        <!-- Histórico geral -->
        <div class="relatorio-card">
            <div class="icone"><i class="fas fa-clock-rotate-left"></i></div>
            <h3>Histórico de Empréstimos</h3>
            <p>Todas as solicitações de empréstimo registradas, com status, datas
               de solicitação, aprovação e devolução. Filtre por período (opcional).</p>
            <form class="filtros" method="GET" action="gerar_relatorio.php" target="_blank" id="form-historico-pdf">
                <input type="hidden" name="tipo" value="historico">
                <input type="hidden" name="formato" value="pdf">
                <input type="date" name="inicio" title="Data inicial">
                <input type="date" name="fim" title="Data final">
            </form>
            <div class="acoes">
                <a class="btn-relatorio btn-pdf"  href="#" onclick="return enviarComFiltro('form-historico-pdf','pdf')"><i class="fas fa-file-pdf"></i> PDF</a>
                <a class="btn-relatorio btn-xlsx" href="#" onclick="return enviarComFiltro('form-historico-pdf','xlsx')"><i class="fas fa-file-excel"></i> Excel</a>
            </div>
        </div>

        <!-- Acervo completo -->
        <div class="relatorio-card">
            <div class="icone"><i class="fas fa-books"></i></div>
            <h3>Acervo Completo</h3>
            <p>Estoque atual de livros: total de exemplares, quantidade emprestada
               e quantidade disponível por título.</p>
            <div class="acoes">
                <a class="btn-relatorio btn-pdf"  href="gerar_relatorio.php?tipo=acervo&formato=pdf"><i class="fas fa-file-pdf"></i> PDF</a>
                <a class="btn-relatorio btn-xlsx" href="gerar_relatorio.php?tipo=acervo&formato=xlsx"><i class="fas fa-file-excel"></i> Excel</a>
            </div>
        </div>

        <!-- Ranking -->
        <div class="relatorio-card">
            <div class="icone"><i class="fas fa-ranking-star"></i></div>
            <h3>Livros Mais Emprestados</h3>
            <p>Ranking dos títulos com maior número de solicitações de
               empréstimo, com totais de aprovações e devoluções.</p>
            <div class="acoes">
                <a class="btn-relatorio btn-pdf"  href="gerar_relatorio.php?tipo=ranking&formato=pdf"><i class="fas fa-file-pdf"></i> PDF</a>
                <a class="btn-relatorio btn-xlsx" href="gerar_relatorio.php?tipo=ranking&formato=xlsx"><i class="fas fa-file-excel"></i> Excel</a>
            </div>
        </div>

    </div>
</main>

<script>
    const btnTema = document.getElementById('btnTema');
    if (localStorage.getItem('tema') === 'dark') {
        document.body.classList.add('dark');
        btnTema.textContent = '☀️';
    }
    btnTema.addEventListener('click', () => {
        document.body.classList.toggle('dark');
        const isDark = document.body.classList.contains('dark');
        localStorage.setItem('tema', isDark ? 'dark' : 'light');
        btnTema.textContent = isDark ? '☀️' : '🌙';
    });

    // Envia o formulário de filtro de data com o formato escolhido
    function enviarComFiltro(idForm, formato) {
        const form = document.getElementById(idForm);
        form.querySelector('input[name="formato"]').value = formato;
        form.submit();
        return false;
    }
</script>
</body>
</html>
