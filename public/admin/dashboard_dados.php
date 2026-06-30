<?php
/*
 * MAPA RAPIDO DO ARQUIVO
 * Local: public/admin/dashboard_dados.php
 * Funcao: Endpoint AJAX com dados filtraveis para graficos do dashboard admin.
 */
require $_SERVER['DOCUMENT_ROOT'] . '/LibraFlow/app/config/auth_check.php';
if ($_SESSION['usuario_tipo'] !== 'D') {
    http_response_code(403);
    echo json_encode(['erro' => 'Acesso negado']);
    exit;
}
require $_SERVER['DOCUMENT_ROOT'] . '/LibraFlow/app/config/conexao.php';

header('Content-Type: application/json; charset=utf-8');

$periodo = $_GET['periodo'] ?? '6m';
$mapa = [
    '7d' => ['DATE_SUB(CURDATE(), INTERVAL 6 DAY)', '%d/%m', 'DATE(data_emprestimo)'],
    'mes' => ["DATE_FORMAT(CURDATE(), '%Y-%m-01')", '%d/%m', 'DATE(data_emprestimo)'],
    '6m' => ["DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 5 MONTH), '%Y-%m-01')", '%m/%Y', "DATE_FORMAT(data_emprestimo, '%Y-%m')"],
    'ano' => ["DATE_FORMAT(CURDATE(), '%Y-01-01')", '%m/%Y', "DATE_FORMAT(data_emprestimo, '%Y-%m')"],
];
[$dataInicioSql, $formato, $grupoPeriodo] = $mapa[$periodo] ?? $mapa['6m'];

$emprestimosPorPeriodo = $conn->query("
    SELECT DATE_FORMAT(MIN(data_emprestimo), '{$formato}') AS rotulo, COUNT(*) AS total
    FROM emprestimos
    WHERE data_emprestimo >= {$dataInicioSql}
    GROUP BY {$grupoPeriodo}
    ORDER BY MIN(data_emprestimo)
")->fetchAll();

$statusEmprestimos = $conn->query("
    SELECT CASE status WHEN 'A' THEN 'Ativos' WHEN 'D' THEN 'Devolvidos' WHEN 'V' THEN 'Vencidos' ELSE 'Outros' END AS rotulo,
           COUNT(*) AS total
    FROM emprestimos
    WHERE data_emprestimo >= {$dataInicioSql}
    GROUP BY status
    ORDER BY FIELD(status, 'A', 'D', 'V')
")->fetchAll();

$livrosMaisEmprestados = $conn->query("
    SELECT l.titulo AS rotulo, COUNT(e.id) AS total
    FROM emprestimos e
    JOIN livros l ON l.id = e.id_livro
    WHERE e.data_emprestimo >= {$dataInicioSql}
    GROUP BY l.id, l.titulo
    ORDER BY total DESC, l.titulo ASC
    LIMIT 5
")->fetchAll();

$categoriasMaisProcuradas = $conn->query("
    SELECT COALESCE(c.nome, 'Sem categoria') AS rotulo, COUNT(e.id) AS total
    FROM emprestimos e
    JOIN livros l ON l.id = e.id_livro
    LEFT JOIN categorias c ON c.id = l.id_categoria
    WHERE e.data_emprestimo >= {$dataInicioSql}
    GROUP BY c.id, c.nome
    ORDER BY total DESC, rotulo ASC
    LIMIT 6
")->fetchAll();

$visitasPorPeriodo = $conn->query("
    SELECT periodo AS rotulo, COALESCE(SUM(quantidade), 0) AS total
    FROM visitas_biblioteca
    WHERE data_registro >= {$dataInicioSql}
    GROUP BY periodo
    ORDER BY FIELD(periodo, 'Manha', 'Tarde', 'Noite'), periodo
")->fetchAll();

echo json_encode([
    'emprestimosPorMes' => $emprestimosPorPeriodo,
    'statusEmprestimos' => $statusEmprestimos,
    'livrosMaisEmprestados' => $livrosMaisEmprestados,
    'categoriasMaisProcuradas' => $categoriasMaisProcuradas,
    'visitasPorPeriodo' => $visitasPorPeriodo,
], JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);