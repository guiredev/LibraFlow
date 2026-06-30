<?php
/*
 * MAPA RAPIDO DO ARQUIVO
 * Local: public/admin/dashboard_export.php
 * Funcao: Exporta resumo do dashboard em CSV ou HTML imprimivel.
 */
require $_SERVER['DOCUMENT_ROOT'] . '/LibraFlow/app/config/auth_check.php';
if ($_SESSION['usuario_tipo'] !== 'D') { http_response_code(403); exit('Acesso negado.'); }
require $_SERVER['DOCUMENT_ROOT'] . '/LibraFlow/app/config/conexao.php';

$formato = $_GET['formato'] ?? 'html';
$resumo = [
    ['Indicador', 'Total'],
    ['Empréstimos ativos', (int) $conn->query("SELECT COUNT(*) FROM emprestimos WHERE status = 'A'")->fetchColumn()],
    ['Em atraso', (int) $conn->query("SELECT COUNT(*) FROM emprestimos WHERE status = 'V'")->fetchColumn()],
    ['Alunos cadastrados', (int) $conn->query("SELECT COUNT(*) FROM usuarios WHERE tipo = 'A'")->fetchColumn()],
    ['Total de livros', (int) $conn->query("SELECT COUNT(*) FROM livros")->fetchColumn()],
    ['Livros sem estoque', (int) $conn->query("SELECT COUNT(*) FROM livros WHERE quantidade <= 0")->fetchColumn()],
];

if ($formato === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="dashboard-libraflow.csv"');
    $out = fopen('php://output', 'w');
    foreach ($resumo as $linha) { fputcsv($out, $linha, ';'); }
    fclose($out);
    exit;
}
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Resumo do Dashboard - LibraFlow</title>
    <style>
        body { font-family: Arial, sans-serif; color: #283618; padding: 32px; }
        h1 { margin-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 24px; }
        th, td { border: 1px solid #BC6C25; padding: 10px; text-align: left; }
        th { background: #F5F5F0; }
        .print { margin-top: 24px; padding: 10px 14px; background: #DDA15E; border: 0; cursor: pointer; }
        @media print { .print { display: none; } }
    </style>
</head>
<body>
    <h1>Resumo do Dashboard - LibraFlow</h1>
    <p>Gerado em <?= date('d/m/Y H:i') ?></p>
    <table>
        <?php foreach ($resumo as $i => $linha): ?>
            <tr>
                <<?= $i === 0 ? 'th' : 'td' ?>><?= htmlspecialchars($linha[0]) ?></<?= $i === 0 ? 'th' : 'td' ?>>
                <<?= $i === 0 ? 'th' : 'td' ?>><?= htmlspecialchars((string) $linha[1]) ?></<?= $i === 0 ? 'th' : 'td' ?>>
            </tr>
        <?php endforeach; ?>
    </table>
    <button class="print" onclick="window.print()">Imprimir ou salvar em PDF</button>
</body>
</html>