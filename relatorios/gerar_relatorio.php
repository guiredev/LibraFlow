<?php
/**
 * gerar_relatorio.php
 * Gera e envia para download os relatórios do LibraFlow em PDF ou Excel.
 *
 * Parâmetros GET:
 *   tipo=atrasados|historico|acervo|ranking   (obrigatório)
 *   formato=pdf|xlsx                          (obrigatório)
 *   inicio=YYYY-MM-DD, fim=YYYY-MM-DD         (opcionais, usados em "historico")
 */

require_once __DIR__ . '/../cadastros_e_logins/configs/auth_check.php';
require_once __DIR__ . '/../cadastros_e_logins/configs/conexao.php';
require_once __DIR__ . '/RelatorioService.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

$pdo = $conn;

// Apenas admin pode acessar
if ($_SESSION['usuario_tipo'] !== 'D') {
    header('Location: ../../cadastros_e_logins/login/arquivos/login.php');
    exit;
}

$tipo    = $_GET['tipo']    ?? '';
$formato = $_GET['formato'] ?? '';
$inicio  = $_GET['inicio']  ?? null;
$fim     = $_GET['fim']     ?? null;

$tiposValidos    = ['atrasados', 'historico', 'acervo', 'ranking'];
$formatosValidos = ['pdf', 'xlsx'];

if (!in_array($tipo, $tiposValidos, true) || !in_array($formato, $formatosValidos, true)) {
    http_response_code(400);
    die('Parâmetros inválidos.');
}

$servico = new RelatorioService($pdo);

// ── Define título, colunas e dados de acordo com o tipo de relatório ──
switch ($tipo) {

    case 'atrasados':
        $titulo = 'Relatório de Empréstimos em Atraso';
        $colunas = ['Aluno', 'E-mail', 'Livro', 'Autor', 'Data Empréstimo', 'Prazo Final', 'Dias de Atraso'];
        $linhas = array_map(function ($r) {
            return [
                $r['aluno_nome'],
                $r['aluno_email'],
                $r['livro_titulo'],
                $r['livro_autor'],
                formatarData($r['data_emprestimo']),
                formatarData($r['data_prevista_devolucao']),
                $r['dias_atraso'] . ' dia(s)',
            ];
        }, $servico->emprestimosAtrasados());
        $subtitulo = 'Prazo de devolução: data prevista pelo empréstimo.';
        break;

    case 'historico':
        $titulo = 'Histórico de Empréstimos';
        $colunas = ['Aluno', 'E-mail', 'Livro', 'Autor', 'Status', 'Data Empréstimo', 'Prazo de Devolução', 'Data Devolução'];
        $statusLabel = [
            'A' => 'Ativo',
            'V' => 'Vencido',
            'D' => 'Devolvido',
        ];
        $linhas = array_map(function ($r) use ($statusLabel) {
            return [
                $r['aluno_nome'],
                $r['aluno_email'],
                $r['livro_titulo'],
                $r['livro_autor'],
                $statusLabel[$r['status']] ?? $r['status'],
                formatarData($r['data_emprestimo']),
                formatarData($r['data_prevista_devolucao']),
                formatarData($r['data_devolucao']),
            ];
        }, $servico->historicoEmprestimos($inicio, $fim));

        $subtitulo = 'Período: ';
        $subtitulo .= $inicio ? formatarData($inicio) : 'início';
        $subtitulo .= ' até ';
        $subtitulo .= $fim ? formatarData($fim) : 'hoje';
        break;

    case 'acervo':
        $titulo = 'Relatório do Acervo';
        $colunas = ['Título', 'Subtítulo', 'Autor', 'Categoria', 'ISBN', 'Ano', 'Total', 'Emprestados', 'Disponíveis'];
        $linhas = array_map(function ($r) {
            return [
                $r['titulo'],
                $r['subtitulo'] ?? '—',
                $r['autor'],
                $r['categoria'] ?? '—',
                $r['isbn'] ?? '—',
                $r['ano'] ?? '—',
                $r['quantidade_total'],
                $r['quantidade_emprestada'],
                $r['quantidade_disponivel'],
            ];
        }, $servico->acervoCompleto());
        $subtitulo = 'Total de títulos: ' . count($linhas);
        break;

    case 'ranking':
        $titulo = 'Livros Mais Emprestados';
        $colunas = ['#', 'Título', 'Autor', 'Categoria', 'Total de Solicitações', 'Aprovados', 'Devolvidos'];
        $dados = $servico->livrosMaisEmprestados();
        $linhas = [];
        foreach ($dados as $i => $r) {
            $linhas[] = [
                $i + 1,
                $r['titulo'],
                $r['autor'],
                $r['categoria'] ?? '—',
                $r['total_solicitacoes'],
                $r['total_aprovados'],
                $r['total_devolvidos'],
            ];
        }
        $subtitulo = 'Ranking baseado no total de solicitações de empréstimo.';
        break;
}

$dataGeracao = date('d/m/Y H:i');
$nomeArquivo = $tipo . '_' . date('Y-m-d');

// ============================================================
// Geração em EXCEL (PhpSpreadsheet)
// ============================================================
if ($formato === 'xlsx') {

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Relatório');

    // Título e metadados
    $sheet->setCellValue('A1', $titulo);
    $sheet->mergeCells('A1:' . colunaLetra(count($colunas)) . '1');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

    $sheet->setCellValue('A2', $subtitulo);
    $sheet->mergeCells('A2:' . colunaLetra(count($colunas)) . '2');
    $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(9);

    $sheet->setCellValue('A3', 'Gerado em: ' . $dataGeracao);
    $sheet->mergeCells('A3:' . colunaLetra(count($colunas)) . '3');
    $sheet->getStyle('A3')->getFont()->setSize(9)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF888888'));

    // Cabeçalho (linha 5)
    $linhaCabecalho = 5;
    foreach ($colunas as $i => $col) {
        $cell = colunaLetra($i + 1) . $linhaCabecalho;
        $sheet->setCellValue($cell, $col);
    }
    $rangeCabecalho = 'A' . $linhaCabecalho . ':' . colunaLetra(count($colunas)) . $linhaCabecalho;
    $sheet->getStyle($rangeCabecalho)->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
    $sheet->getStyle($rangeCabecalho)->getFill()
        ->setFillType(Fill::FILL_SOLID)
        ->getStartColor()->setRGB('283618');

    // Dados
    $linhaAtual = $linhaCabecalho + 1;
    if (empty($linhas)) {
        $sheet->setCellValue('A' . $linhaAtual, 'Nenhum registro encontrado.');
        $sheet->mergeCells('A' . $linhaAtual . ':' . colunaLetra(count($colunas)) . $linhaAtual);
        $sheet->getStyle('A' . $linhaAtual)->getFont()->setItalic(true);
    } else {
        foreach ($linhas as $linha) {
            foreach ($linha as $i => $valor) {
                $sheet->setCellValue(colunaLetra($i + 1) . $linhaAtual, $valor);
            }
            $linhaAtual++;
        }
    }

    // Linhas zebradas + bordas
    $ultimaLinha = $linhaAtual - 1;
    if ($ultimaLinha >= $linhaCabecalho + 1) {
        $range = 'A' . ($linhaCabecalho + 1) . ':' . colunaLetra(count($colunas)) . $ultimaLinha;
        $sheet->getStyle($range)->getBorders()->getAllBorders()
            ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)
            ->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFD9CFC3'));

        for ($l = $linhaCabecalho + 1; $l <= $ultimaLinha; $l++) {
            if (($l - $linhaCabecalho) % 2 === 0) {
                $sheet->getStyle('A' . $l . ':' . colunaLetra(count($colunas)) . $l)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('F5F5F0');
            }
        }
    }

    // Auto-size de colunas
    foreach (range(1, count($colunas)) as $i) {
        $sheet->getColumnDimension(colunaLetra($i))->setAutoSize(true);
    }

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $nomeArquivo . '.xlsx"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

// ============================================================
// Geração em PDF (Dompdf)
// ============================================================
if ($formato === 'pdf') {

    $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>
        @page { margin: 28px 32px; }
        body { font-family: "DejaVu Sans", sans-serif; color: #283618; font-size: 10px; }
        h1 { font-size: 16px; margin: 0 0 4px; color: #283618; }
        .subtitulo { font-size: 9px; color: #6b7a52; margin-bottom: 2px; }
        .gerado { font-size: 8px; color: #999; margin-bottom: 14px; }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        th { background: #283618; color: #FEFAE0; padding: 6px 8px; text-align: left; font-size: 9px; }
        td { padding: 5px 8px; border-bottom: 1px solid #D9CFC3; font-size: 9px; }
        tr:nth-child(even) td { background: #F5F5F0; }
        .vazio { padding: 14px 8px; font-style: italic; color: #888; }
        .rodape { margin-top: 18px; font-size: 8px; color: #aaa; text-align: right; }
    </style></head><body>';

    $html .= '<h1>' . htmlspecialchars($titulo) . '</h1>';
    $html .= '<div class="subtitulo">' . htmlspecialchars($subtitulo) . '</div>';
    $html .= '<div class="gerado">Gerado em: ' . $dataGeracao . ' — LibraFlow</div>';

    $html .= '<table><thead><tr>';
    foreach ($colunas as $col) {
        $html .= '<th>' . htmlspecialchars($col) . '</th>';
    }
    $html .= '</tr></thead><tbody>';

    if (empty($linhas)) {
        $html .= '<tr><td class="vazio" colspan="' . count($colunas) . '">Nenhum registro encontrado.</td></tr>';
    } else {
        foreach ($linhas as $linha) {
            $html .= '<tr>';
            foreach ($linha as $valor) {
                $html .= '<td>' . htmlspecialchars((string) $valor) . '</td>';
            }
            $html .= '</tr>';
        }
    }

    $html .= '</tbody></table>';
    $html .= '<div class="rodape">LibraFlow — Sistema de Gestão de Biblioteca</div>';
    $html .= '</body></html>';

    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', false);

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'landscape');
    $dompdf->render();

    $dompdf->stream($nomeArquivo . '.pdf', ['Attachment' => true]);
    exit;
}

// ============================================================
// Funções auxiliares
// ============================================================

/** Converte índice de coluna (1, 2, 3...) em letra (A, B, C...) */
function colunaLetra(int $indice): string
{
    return \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($indice);
}

/** Formata uma data/datetime do MySQL para dd/mm/aaaa. Retorna '—' se vazio/nulo. */
function formatarData(?string $data): string
{
    if (empty($data) || $data === '0000-00-00' || $data === '0000-00-00 00:00:00') {
        return '—';
    }
    $timestamp = strtotime($data);
    if ($timestamp === false) {
        return '—';
    }
    return date('d/m/Y', $timestamp);
}
