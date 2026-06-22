<?php
/**
 * gerar_relatorio.php — LibraFlow
 * Processa os dados e gera PDF (TCPDF) ou Excel (PhpSpreadsheet)
 *
 * Parâmetros GET:
 *   tipo     = vencidos | emprestimos_periodo | visitas | populares | historico_aluno
 *   formato  = pdf | excel
 *   inicio   = YYYY-MM-DD
 *   fim      = YYYY-MM-DD
 *   aluno_id = int (só para historico_aluno)
 */

require_once '../../configs/auth_check.php';
require_once 'conexao.php';
require_once '../../vendor/autoload.php';

// ── Sanitização dos parâmetros ────────────────────────────────────────────────
$tipo      = $_GET['tipo']    ?? '';
$formato   = $_GET['formato'] ?? 'pdf';
$inicio    = $_GET['inicio']  ?? date('Y-m-01');
$fim       = $_GET['fim']     ?? date('Y-m-d');
$aluno_id  = intval($_GET['aluno_id'] ?? 0);

$tipos_validos   = ['vencidos','emprestimos_periodo','visitas','populares','historico_aluno'];
$formatos_validos = ['pdf','excel'];

if (!in_array($tipo, $tipos_validos) || !in_array($formato, $formatos_validos)) {
    http_response_code(400);
    die('Parâmetros inválidos.');
}

// ── Busca de dados ────────────────────────────────────────────────────────────
$dados = [];
$titulo = '';
$colunas = [];

switch ($tipo) {

    // ── 1. Empréstimos vencidos ───────────────────────────────────────────────
    case 'vencidos':
        $titulo   = 'Empréstimos Vencidos/Atrasados';
        $colunas  = ['Aluno', 'RM', 'Livro', 'Data Empréstimo', 'Data Devolução', 'Dias de Atraso'];
        $stmt = $pdo->prepare("
            SELECT u.nome AS aluno, u.rm, l.titulo AS livro,
                   e.data_emprestimo, e.data_devolucao,
                   DATEDIFF(CURDATE(), e.data_devolucao) AS dias_atraso
            FROM emprestimos e
            JOIN usuarios u ON u.id = e.usuario_id
            JOIN livros   l ON l.id = e.livro_id
            WHERE e.status = 'ativo'
              AND e.data_devolucao < CURDATE()
            ORDER BY dias_atraso DESC
        ");
        $stmt->execute();
        while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $dados[] = [
                $r['aluno'],
                $r['rm'] ?? '—',
                $r['livro'],
                date('d/m/Y', strtotime($r['data_emprestimo'])),
                date('d/m/Y', strtotime($r['data_devolucao'])),
                $r['dias_atraso'] . ' dias',
            ];
        }
        break;

    // ── 2. Empréstimos no período ─────────────────────────────────────────────
    case 'emprestimos_periodo':
        $titulo   = 'Empréstimos no Período';
        $colunas  = ['Aluno', 'RM', 'Livro', 'Data Empréstimo', 'Data Devolução', 'Status'];
        $stmt = $pdo->prepare("
            SELECT u.nome AS aluno, u.rm, l.titulo AS livro,
                   e.data_emprestimo, e.data_devolucao, e.status
            FROM emprestimos e
            JOIN usuarios u ON u.id = e.usuario_id
            JOIN livros   l ON l.id = e.livro_id
            WHERE e.data_emprestimo BETWEEN :ini AND :fim
            ORDER BY e.data_emprestimo DESC
        ");
        $stmt->execute([':ini' => $inicio, ':fim' => $fim]);
        while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $dados[] = [
                $r['aluno'],
                $r['rm'] ?? '—',
                $r['livro'],
                date('d/m/Y', strtotime($r['data_emprestimo'])),
                $r['data_devolucao'] ? date('d/m/Y', strtotime($r['data_devolucao'])) : '—',
                ucfirst($r['status']),
            ];
        }
        break;

    // ── 3. Visitas à biblioteca ───────────────────────────────────────────────
    case 'visitas':
        $titulo  = 'Visitas à Biblioteca';
        $colunas = ['Data', 'Aluno', 'RM', 'Hora de Entrada'];
        $stmt = $pdo->prepare("
            SELECT v.data_visita, u.nome AS aluno, u.rm, v.hora_entrada
            FROM visitas v
            JOIN usuarios u ON u.id = v.usuario_id
            WHERE v.data_visita BETWEEN :ini AND :fim
            ORDER BY v.data_visita DESC, v.hora_entrada ASC
        ");
        $stmt->execute([':ini' => $inicio, ':fim' => $fim]);
        while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $dados[] = [
                date('d/m/Y', strtotime($r['data_visita'])),
                $r['aluno'],
                $r['rm'] ?? '—',
                $r['hora_entrada'] ?? '—',
            ];
        }
        break;

    // ── 4. Livros mais populares ──────────────────────────────────────────────
    case 'populares':
        $titulo  = 'Livros Mais Populares';
        $colunas = ['Posição', 'Título', 'Autor', 'Categoria', 'Total de Empréstimos'];
        $stmt = $pdo->prepare("
            SELECT l.titulo, l.autor,
                   COALESCE(c.nome, '—') AS categoria,
                   COUNT(e.id) AS total
            FROM livros l
            LEFT JOIN emprestimos e ON e.livro_id = l.id
                AND e.data_emprestimo BETWEEN :ini AND :fim
            LEFT JOIN categorias c ON c.id = l.categoria_id
            GROUP BY l.id
            ORDER BY total DESC
            LIMIT 20
        ");
        $stmt->execute([':ini' => $inicio, ':fim' => $fim]);
        $pos = 1;
        while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $dados[] = [
                $pos++,
                $r['titulo'],
                $r['autor'],
                $r['categoria'],
                $r['total'],
            ];
        }
        break;

    // ── 5. Histórico por aluno ────────────────────────────────────────────────
    case 'historico_aluno':
        // Busca nome do aluno para o título
        $sa = $pdo->prepare("SELECT nome, rm FROM usuarios WHERE id = :id LIMIT 1");
        $sa->execute([':id' => $aluno_id]);
        $aluno_row = $sa->fetch(PDO::FETCH_ASSOC);
        $nome_aluno = $aluno_row ? $aluno_row['nome'] : 'Aluno #'.$aluno_id;
        $rm_aluno   = $aluno_row['rm'] ?? '';

        $titulo  = "Histórico de Empréstimos — {$nome_aluno}" . ($rm_aluno ? " (RM: {$rm_aluno})" : '');
        $colunas = ['Livro', 'Autor', 'Data Empréstimo', 'Data Devolução', 'Status'];

        $stmt = $pdo->prepare("
            SELECT l.titulo, l.autor,
                   e.data_emprestimo, e.data_devolucao, e.status
            FROM emprestimos e
            JOIN livros l ON l.id = e.livro_id
            WHERE e.usuario_id = :uid
              AND e.data_emprestimo BETWEEN :ini AND :fim
            ORDER BY e.data_emprestimo DESC
        ");
        $stmt->execute([':uid' => $aluno_id, ':ini' => $inicio, ':fim' => $fim]);
        while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $dados[] = [
                $r['titulo'],
                $r['autor'],
                date('d/m/Y', strtotime($r['data_emprestimo'])),
                $r['data_devolucao'] ? date('d/m/Y', strtotime($r['data_devolucao'])) : '—',
                ucfirst($r['status']),
            ];
        }
        break;
}

$periodo_label = date('d/m/Y', strtotime($inicio)) . ' a ' . date('d/m/Y', strtotime($fim));
$total_registros = count($dados);

// ═════════════════════════════════════════════════════════════════════════════
// GERAÇÃO PDF
// ═════════════════════════════════════════════════════════════════════════════
if ($formato === 'pdf') {

    use TCPDF as TCPDF;

    class LibraFlowPDF extends TCPDF {
        public string $relatorio_titulo = '';
        public string $periodo          = '';

        public function Header() {
            // Barra superior verde
            $this->SetFillColor(45, 106, 79);
            $this->Rect(0, 0, 210, 22, 'F');

            // Logo texto
            $this->SetFont('helvetica', 'B', 14);
            $this->SetTextColor(255, 255, 255);
            $this->SetXY(10, 5);
            $this->Cell(60, 10, 'LibraFlow', 0, 0, 'L');

            // Título do relatório
            $this->SetFont('helvetica', '', 10);
            $this->SetXY(70, 5);
            $this->Cell(130, 10, $this->relatorio_titulo, 0, 0, 'R');

            // Período
            $this->SetFont('helvetica', 'I', 8);
            $this->SetXY(70, 13);
            $this->Cell(130, 6, 'Período: ' . $this->periodo, 0, 0, 'R');

            $this->SetTextColor(0, 0, 0);
            $this->SetY(28);
        }

        public function Footer() {
            $this->SetY(-12);
            $this->SetFont('helvetica', 'I', 7);
            $this->SetTextColor(128, 128, 128);
            $this->Cell(0, 8, 'LibraFlow — Gerado em ' . date('d/m/Y H:i') . '   |   Pág. ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, 0, 'C');
        }
    }

    $pdf = new LibraFlowPDF('L', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->relatorio_titulo = $titulo;
    $pdf->periodo          = $periodo_label;

    $pdf->SetCreator('LibraFlow');
    $pdf->SetTitle($titulo);
    $pdf->SetMargins(12, 30, 12);
    $pdf->SetAutoPageBreak(true, 18);
    $pdf->AddPage();

    // ── Título e resumo ───────────────────────────────────────────────────────
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
        $pdf->Cell(0, 10, 'Nenhum registro encontrado para o período selecionado.', 0, 1, 'C');
    } else {
        // ── Cabeçalho da tabela ───────────────────────────────────────────────
        $num_cols  = count($colunas);
        $page_w    = $pdf->getPageWidth() - 24;
        $col_w     = round($page_w / $num_cols, 1);

        $pdf->SetFillColor(45, 106, 79);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->SetLineWidth(0);

        foreach ($colunas as $col) {
            $pdf->Cell($col_w, 8, $col, 0, 0, 'C', true);
        }
        $pdf->Ln();

        // ── Linhas ────────────────────────────────────────────────────────────
        $pdf->SetFont('helvetica', '', 8);
        $zebra = false;
        foreach ($dados as $linha) {
            $pdf->SetFillColor($zebra ? 240 : 255, $zebra ? 247 : 255, $zebra ? 244 : 255);
            $pdf->SetTextColor(30, 30, 30);
            foreach ($linha as $celula) {
                $pdf->Cell($col_w, 7, $celula, 0, 0, 'L', true);
            }
            $pdf->Ln();
            $zebra = !$zebra;
        }
    }

    $nome_arquivo = 'LibraFlow_' . $tipo . '_' . date('Ymd_His') . '.pdf';
    $pdf->Output($nome_arquivo, 'D');
    exit;
}

// ═════════════════════════════════════════════════════════════════════════════
// GERAÇÃO EXCEL
// ═════════════════════════════════════════════════════════════════════════════
if ($formato === 'excel') {

    use PhpOffice\PhpSpreadsheet\Spreadsheet;
    use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
    use PhpOffice\PhpSpreadsheet\Style\Fill;
    use PhpOffice\PhpSpreadsheet\Style\Alignment;
    use PhpOffice\PhpSpreadsheet\Style\Border;
    use PhpOffice\PhpSpreadsheet\Style\Font;

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Relatório');

    // ── Linha 1: Título do relatório ──────────────────────────────────────────
    $sheet->setCellValue('A1', 'LibraFlow — ' . $titulo);
    $last_col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($colunas));
    $sheet->mergeCells("A1:{$last_col}1");
    $sheet->getStyle('A1')->applyFromArray([
        'font'      => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
        'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E3A5F']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    ]);
    $sheet->getRowDimension(1)->setRowHeight(26);

    // ── Linha 2: Período e total ──────────────────────────────────────────────
    $sheet->setCellValue('A2', "Período: {$periodo_label}   |   Total de registros: {$total_registros}");
    $sheet->mergeCells("A2:{$last_col}2");
    $sheet->getStyle('A2')->applyFromArray([
        'font'      => ['italic' => true, 'size' => 9, 'color' => ['rgb' => '555555']],
        'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F7FAFC']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
    ]);
    $sheet->getRowDimension(2)->setRowHeight(16);

    // ── Linha 3: Cabeçalho das colunas ───────────────────────────────────────
    foreach ($colunas as $i => $col) {
        $cell_ref = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1) . '3';
        $sheet->setCellValue($cell_ref, $col);
    }

    $range_header = "A3:{$last_col}3";
    $sheet->getStyle($range_header)->applyFromArray([
        'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
        'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2D6A4F']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '1E4D38']]],
    ]);
    $sheet->getRowDimension(3)->setRowHeight(18);

    // ── Dados ─────────────────────────────────────────────────────────────────
    if (empty($dados)) {
        $sheet->setCellValue('A4', 'Nenhum registro encontrado para o período selecionado.');
        $sheet->mergeCells("A4:{$last_col}4");
        $sheet->getStyle('A4')->applyFromArray([
            'font'      => ['italic' => true, 'color' => ['rgb' => '999999']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
    } else {
        foreach ($dados as $row_idx => $linha) {
            $excel_row = $row_idx + 4;
            $is_zebra  = ($row_idx % 2 === 0);
            $bg_color  = $is_zebra ? 'F0F7F4' : 'FFFFFF';

            foreach ($linha as $col_idx => $valor) {
                $cell_ref = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col_idx + 1) . $excel_row;
                $sheet->setCellValue($cell_ref, $valor);
            }

            $range_row = "A{$excel_row}:{$last_col}{$excel_row}";
            $sheet->getStyle($range_row)->applyFromArray([
                'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bg_color]],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']]],
                'font'    => ['size' => 9],
            ]);
            $sheet->getRowDimension($excel_row)->setRowHeight(15);
        }
    }

    // ── Auto-width nas colunas ────────────────────────────────────────────────
    foreach (range(1, count($colunas)) as $ci) {
        $col_letter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($ci);
        $sheet->getColumnDimension($col_letter)->setAutoSize(true);
    }

    // ── Freeze header ─────────────────────────────────────────────────────────
    $sheet->freezePane('A4');

    $nome_arquivo = 'LibraFlow_' . $tipo . '_' . date('Ymd_His') . '.xlsx';

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $nome_arquivo . '"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}
