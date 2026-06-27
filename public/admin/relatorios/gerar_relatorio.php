<?php
/*
 * MAPA RAPIDO DO ARQUIVO
 * Local: public/admin/relatorios/gerar_relatorio.php
 * Funcao: Exporta relatorios atuais em PDF ou XLSX.
 */
/**
 * gerar_relatorio.php — LibraFlow
 * Gera relatórios em PDF (TCPDF) ou Excel (PhpSpreadsheet),
 * usando os dados centralizados em RelatorioService.
 *
 * Parâmetros GET:
 *   tipo     = atrasados | historico | acervo | ranking
 *   formato  = pdf | xlsx
 *   inicio   = YYYY-MM-DD (opcional, só usado em 'historico')
 *   fim      = YYYY-MM-DD (opcional, só usado em 'historico')
 */

// Buffer de segurança: captura qualquer warning/saída acidental antes do PDF/Excel
ob_start();

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

require_once $_SERVER['DOCUMENT_ROOT'] . '/LibraFlow/app/config/auth_check.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/LibraFlow/app/config/conexao.php';
require_once __DIR__ . '/RelatorioService.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/LibraFlow/vendor/autoload.php';


// Apenas admin pode gerar relatórios
if ($_SESSION['usuario_tipo'] !== 'D') {
    http_response_code(403);
    die('Acesso não autorizado.');
}

$pdo     = $conn;
$servico = new RelatorioService($pdo);

// ── Sanitização dos parâmetros ────────────────────────────────────────────────
$tipo    = $_GET['tipo']    ?? '';
$formato = $_GET['formato'] ?? 'pdf';
$inicio  = $_GET['inicio']  ?? null;
$fim     = $_GET['fim']     ?? null;
$inicio  = $inicio === '' ? null : $inicio;
$fim     = $fim === '' ? null : $fim;

$tipos_validos    = ['atrasados', 'historico', 'acervo', 'ranking'];
$formatos_validos = ['pdf', 'xlsx'];

if (!in_array($tipo, $tipos_validos) || !in_array($formato, $formatos_validos)) {
    http_response_code(400);
    die('Parâmetros inválidos.');
}

/** Traduz a sigla de status salva no banco para um rótulo legível */
function mapStatus(string $status): string
{
    return match ($status) {
        'A' => 'Aprovado',
        'V' => 'Vencido',
        'D' => 'Devolvido',
        'P' => 'Pendente',
        'R' => 'Rejeitado',
        default => $status,
    };
}

// ── Busca de dados via RelatorioService ───────────────────────────────────────
$dados   = [];
$titulo  = '';
$colunas = [];

switch ($tipo) {

    case 'atrasados':
        $titulo  = 'Empréstimos em Atraso';
        $colunas = ['Aluno', 'E-mail', 'Livro', 'Autor', 'Data Empréstimo', 'Prazo Devolução', 'Dias de Atraso'];
        foreach ($servico->emprestimosAtrasados() as $r) {
            $dados[] = [
                $r['aluno_nome'],
                $r['aluno_email'],
                $r['livro_titulo'],
                $r['livro_autor'],
                date('d/m/Y', strtotime($r['data_emprestimo'])),
                date('d/m/Y', strtotime($r['data_prevista_devolucao'])),
                $r['dias_atraso'] . ' dias',
            ];
        }
        break;

    case 'historico':
        $titulo  = 'Histórico de Empréstimos';
        $colunas = ['Aluno', 'E-mail', 'Livro', 'Autor', 'Status', 'Data Empréstimo', 'Prazo Devolução', 'Data Devolução'];
        foreach ($servico->historicoEmprestimos($inicio, $fim) as $r) {
            $dados[] = [
                $r['aluno_nome'],
                $r['aluno_email'],
                $r['livro_titulo'],
                $r['livro_autor'],
                mapStatus($r['status']),
                date('d/m/Y', strtotime($r['data_emprestimo'])),
                $r['data_prevista_devolucao'] ? date('d/m/Y', strtotime($r['data_prevista_devolucao'])) : '—',
                $r['data_devolucao'] ? date('d/m/Y', strtotime($r['data_devolucao'])) : '—',
            ];
        }
        break;

    case 'acervo':
        $titulo  = 'Acervo Completo';
        $colunas = ['Título', 'Autor', 'Categoria', 'Ano', 'Qtd. Total', 'Emprestados', 'Disponíveis'];
        foreach ($servico->acervoCompleto() as $r) {
            $dados[] = [
                $r['titulo'],
                $r['autor'],
                $r['categoria'] ?? '—',
                $r['ano'] ?? '—',
                $r['quantidade_total'],
                $r['quantidade_emprestada'],
                $r['quantidade_disponivel'],
            ];
        }
        break;

    case 'ranking':
        $titulo  = 'Livros Mais Emprestados';
        $colunas = ['Posição', 'Título', 'Autor', 'Categoria', 'Solicitações', 'Aprovados', 'Devolvidos'];
        $pos = 1;
        foreach ($servico->livrosMaisEmprestados(20) as $r) {
            $dados[] = [
                $pos++,
                $r['titulo'],
                $r['autor'],
                $r['categoria'] ?? '—',
                $r['total_solicitacoes'],
                $r['total_aprovados'],
                $r['total_devolvidos'],
            ];
        }
        break;
}

$periodo_label = ($inicio && $fim)
    ? date('d/m/Y', strtotime($inicio)) . ' a ' . date('d/m/Y', strtotime($fim))
    : 'Todos os registros';
$total_registros = count($dados);

// ═════════════════════════════════════════════════════════════════════════════
// GERAÇÃO PDF (TCPDF)
// ═════════════════════════════════════════════════════════════════════════════
if ($formato === 'pdf') {

    class LibraFlowPDF extends TCPDF
    {
        public string $relatorioTitulo = '';
        public string $periodo = '';

        public function Header()
        {
            $this->SetFillColor(45, 106, 79);
            $this->Rect(0, 0, $this->getPageWidth(), 22, 'F');

            $this->SetFont('helvetica', 'B', 14);
            $this->SetTextColor(255, 255, 255);
            $this->SetXY(10, 5);
            $this->Cell(60, 10, 'LibraFlow', 0, 0, 'L');

            $this->SetFont('helvetica', '', 10);
            $this->SetXY(70, 5);
            $this->Cell($this->getPageWidth() - 80, 10, $this->relatorioTitulo, 0, 0, 'R');

            $this->SetFont('helvetica', 'I', 8);
            $this->SetXY(70, 13);
            $this->Cell($this->getPageWidth() - 80, 6, 'Período: ' . $this->periodo, 0, 0, 'R');

            $this->SetTextColor(0, 0, 0);
            $this->SetY(28);
        }

        public function Footer()
        {
            $this->SetY(-12);
            $this->SetFont('helvetica', 'I', 7);
            $this->SetTextColor(128, 128, 128);
            $this->Cell(0, 8, 'LibraFlow — Gerado em ' . date('d/m/Y H:i') . '   |   Pág. ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, 0, 'C');
        }
    }

    $pdf = new LibraFlowPDF('L', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->relatorioTitulo = $titulo;
    $pdf->periodo         = $periodo_label;

    $pdf->SetCreator('LibraFlow');
    $pdf->SetTitle($titulo);
    $pdf->SetMargins(12, 30, 12);
    $pdf->SetAutoPageBreak(true, 18);
    $pdf->AddPage();

    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->SetTextColor(30, 58, 95);
    $pdf->Cell(0, 10, $titulo, 0, 1, 'L');

    $pdf->SetFont('helvetica', '', 9);
    $pdf->SetTextColor(100, 100, 100);
    $pdf->Cell(0, 6, "Total de registros: {$total_registros}   |   Período: {$periodo_label}", 0, 1, 'L');
    $pdf->Ln(4);

    if (empty($dados)) {
        $pdf->SetFont('helvetica', 'I', 11);
        $pdf->SetTextColor(150, 150, 150);
        $pdf->Cell(0, 10, 'Nenhum registro encontrado.', 0, 1, 'C');
    } else {
        $num_cols = count($colunas);
        $page_w   = $pdf->getPageWidth() - 24;
        $col_w    = round($page_w / $num_cols, 1);

        $pdf->SetFillColor(45, 106, 79);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->SetLineWidth(0);

        foreach ($colunas as $col) {
            $pdf->Cell($col_w, 8, $col, 0, 0, 'C', true);
        }
        $pdf->Ln();

        $pdf->SetFont('helvetica', '', 8);
        $zebra = false;
        foreach ($dados as $linha) {
            $pdf->SetFillColor($zebra ? 240 : 255, $zebra ? 247 : 255, $zebra ? 244 : 255);
            $pdf->SetTextColor(30, 30, 30);
            foreach ($linha as $celula) {
                $pdf->Cell($col_w, 7, (string) $celula, 0, 0, 'L', true);
            }
            $pdf->Ln();
            $zebra = !$zebra;
        }
    }

    $nome_arquivo = 'Relatorio_' . $tipo . '_' . date('d-m-Y') . '.pdf';
    ob_end_clean();
    $pdf->Output($nome_arquivo, 'D');
    exit;
}

// ═════════════════════════════════════════════════════════════════════════════
// GERAÇÃO EXCEL (PhpSpreadsheet)
// ═════════════════════════════════════════════════════════════════════════════
if ($formato === 'xlsx') {

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Relatório');

    $last_col = Coordinate::stringFromColumnIndex(count($colunas));

    $sheet->setCellValue('A1', 'LibraFlow — ' . $titulo);
    $sheet->mergeCells("A1:{$last_col}1");
    $sheet->getStyle('A1')->applyFromArray([
        'font'      => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
        'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E3A5F']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    ]);
    $sheet->getRowDimension(1)->setRowHeight(26);

    $sheet->setCellValue('A2', "Período: {$periodo_label}   |   Total de registros: {$total_registros}");
    $sheet->mergeCells("A2:{$last_col}2");
    $sheet->getStyle('A2')->applyFromArray([
        'font'      => ['italic' => true, 'size' => 9, 'color' => ['rgb' => '555555']],
        'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F7FAFC']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
    ]);
    $sheet->getRowDimension(2)->setRowHeight(16);

    foreach ($colunas as $i => $col) {
        $cell_ref = Coordinate::stringFromColumnIndex($i + 1) . '3';
        $sheet->setCellValue($cell_ref, $col);
    }
    $sheet->getStyle("A3:{$last_col}3")->applyFromArray([
        'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
        'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2D6A4F']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '1E4D38']]],
    ]);
    $sheet->getRowDimension(3)->setRowHeight(18);

    if (empty($dados)) {
        $sheet->setCellValue('A4', 'Nenhum registro encontrado.');
        $sheet->mergeCells("A4:{$last_col}4");
        $sheet->getStyle('A4')->applyFromArray([
            'font'      => ['italic' => true, 'color' => ['rgb' => '999999']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
    } else {
        foreach ($dados as $row_idx => $linha) {
            $excel_row = $row_idx + 4;
            $bg_color  = ($row_idx % 2 === 0) ? 'F0F7F4' : 'FFFFFF';

            foreach ($linha as $col_idx => $valor) {
                $cell_ref = Coordinate::stringFromColumnIndex($col_idx + 1) . $excel_row;
                $sheet->setCellValue($cell_ref, $valor);
            }

            $sheet->getStyle("A{$excel_row}:{$last_col}{$excel_row}")->applyFromArray([
                'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bg_color]],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']]],
                'font'    => ['size' => 9],
            ]);
            $sheet->getRowDimension($excel_row)->setRowHeight(15);
        }
    }

    foreach (range(1, count($colunas)) as $ci) {
        $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($ci))->setAutoSize(true);
    }
    $sheet->freezePane('A4');

    $nome_arquivo = 'Relatorio_' . $tipo . '_' . date('d-m-Y') . '.xlsx';

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $nome_arquivo . '"');
    header('Cache-Control: max-age=0');

    ob_end_clean();
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}
