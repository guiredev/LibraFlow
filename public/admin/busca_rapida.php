<?php
/*
 * MAPA RAPIDO DO ARQUIVO
 * Local: public/admin/busca_rapida.php
 * Funcao: Endpoint AJAX para pesquisa global do painel admin.
 * Retorna paginas do sistema, alunos, livros e emprestimos relacionados ao termo pesquisado.
 */
require $_SERVER['DOCUMENT_ROOT'] . '/LibraFlow/app/config/auth_check.php';
if ($_SESSION['usuario_tipo'] !== 'D') {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['erro' => 'Acesso negado']);
    exit;
}
require $_SERVER['DOCUMENT_ROOT'] . '/LibraFlow/app/config/conexao.php';

header('Content-Type: application/json; charset=utf-8');

function lf_texto_minusculo($valor) {
    $valor = (string) $valor;
    if (function_exists('mb_strtolower')) {
        return mb_strtolower($valor, 'UTF-8');
    }
    return strtolower($valor);
}

function lf_texto_contem($texto, $termo) {
    if ($termo === '') {
        return true;
    }
    if (function_exists('mb_strpos')) {
        return mb_strpos($texto, $termo) !== false;
    }
    return strpos($texto, $termo) !== false;
}

$q = trim($_GET['q'] ?? '');
if (strlen($q) < 2) {
    echo json_encode([
        'paginas' => [],
        'alunos' => [],
        'livros' => [],
        'emprestimos' => [],
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$termo = '%' . $q . '%';
$qNormalizado = lf_texto_minusculo($q);

// Paginas fixas ajudam o bibliotecario a navegar sem procurar no menu lateral.
$paginasDisponiveis = [
    ['titulo' => 'Inicio do admin', 'descricao' => 'Resumo, graficos e pendencias do dia', 'url' => '/LibraFlow/public/admin/Admin.php', 'icone' => 'fa-house', 'palavras' => 'inicio dashboard painel graficos resumo admin'],
    ['titulo' => 'Livros', 'descricao' => 'Listagem, filtros, detalhes e exclusao de livros', 'url' => '/LibraFlow/public/admin/listar_livros.php', 'icone' => 'fa-book-open', 'palavras' => 'livros acervo exemplares estoque isbn autor'],
    ['titulo' => 'Cadastrar livro', 'descricao' => 'Adicionar novo livro ao acervo', 'url' => '/LibraFlow/public/admin/cadastrar_livro.php', 'icone' => 'fa-plus', 'palavras' => 'cadastrar livro adicionar novo capa isbn'],
    ['titulo' => 'Usuarios', 'descricao' => 'Alunos cadastrados, edicao e cadastro rapido', 'url' => '/LibraFlow/public/admin/usuarios.php', 'icone' => 'fa-users', 'palavras' => 'usuarios alunos estudantes rm email telefone cadastro'],
    ['titulo' => 'Cadastro rapido de aluno', 'descricao' => 'Registrar aluno com dados basicos', 'url' => '/LibraFlow/public/admin/cadastro_rapido_aluno.php', 'icone' => 'fa-user-plus', 'palavras' => 'cadastro rapido aluno usuario novo'],
    ['titulo' => 'Emprestimos', 'descricao' => 'Controle de emprestimos ativos, atrasados e devolvidos', 'url' => '/LibraFlow/public/admin/emprestimos.php', 'icone' => 'fa-clipboard-list', 'palavras' => 'emprestimos devolucao atraso vencido ativo'],
    ['titulo' => 'Novo emprestimo', 'descricao' => 'Registrar retirada de livro para aluno', 'url' => '/LibraFlow/public/admin/novo_emprestimo.php', 'icone' => 'fa-right-left', 'palavras' => 'novo emprestimo retirada livro aluno'],
    ['titulo' => 'Visitas', 'descricao' => 'Registro de movimento da biblioteca', 'url' => '/LibraFlow/public/admin/visitas.php', 'icone' => 'fa-clock', 'palavras' => 'visitas biblioteca movimento periodo'],
    ['titulo' => 'Relatorios', 'descricao' => 'Relatorios administrativos e exportacoes', 'url' => '/LibraFlow/public/admin/relatorios/index.php', 'icone' => 'fa-chart-line', 'palavras' => 'relatorios pdf excel csv indicadores exportar'],
];

$paginas = array_values(array_filter($paginasDisponiveis, static function ($pagina) use ($qNormalizado) {
    $texto = lf_texto_minusculo($pagina['titulo'] . ' ' . $pagina['descricao'] . ' ' . $pagina['palavras']);
    return lf_texto_contem($texto, $qNormalizado);
}));
$paginas = array_slice($paginas, 0, 5);

$stmt = $conn->prepare("
    SELECT u.id, u.nome, u.email, u.rm,
           COUNT(CASE WHEN e.status IN ('A', 'V') THEN 1 END) AS emprestimos_abertos,
           COUNT(CASE WHEN e.status = 'V' THEN 1 END) AS atrasos
    FROM usuarios u
    LEFT JOIN emprestimos e ON e.id_usuario = u.id
    WHERE u.tipo = 'A'
      AND (u.nome LIKE ? OR u.email LIKE ? OR u.rm LIKE ? OR u.telefone LIKE ?)
    GROUP BY u.id, u.nome, u.email, u.rm
    ORDER BY u.nome ASC
    LIMIT 6
");
$stmt->execute([$termo, $termo, $termo, $termo]);
$alunos = $stmt->fetchAll();

$stmt = $conn->prepare("
    SELECT l.id, l.titulo, l.autor, l.isbn, l.quantidade,
           COUNT(CASE WHEN e.status IN ('A', 'V') THEN 1 END) AS emprestados
    FROM livros l
    LEFT JOIN emprestimos e ON e.id_livro = l.id
    WHERE l.titulo LIKE ? OR l.autor LIKE ? OR l.isbn LIKE ? OR l.subtitulo LIKE ?
    GROUP BY l.id, l.titulo, l.autor, l.isbn, l.quantidade
    ORDER BY l.titulo ASC
    LIMIT 6
");
$stmt->execute([$termo, $termo, $termo, $termo]);
$livros = $stmt->fetchAll();

$stmt = $conn->prepare("
    SELECT e.id, e.status, e.data_emprestimo, e.data_prevista_devolucao,
           u.id AS id_usuario, u.nome AS aluno,
           l.id AS id_livro, l.titulo AS livro,
           CASE e.status
               WHEN 'A' THEN 'Ativo'
               WHEN 'D' THEN 'Devolvido'
               WHEN 'V' THEN 'Vencido'
               ELSE 'Indefinido'
           END AS status_nome
    FROM emprestimos e
    JOIN usuarios u ON u.id = e.id_usuario
    JOIN livros l ON l.id = e.id_livro
    WHERE u.nome LIKE ? OR u.email LIKE ? OR u.rm LIKE ? OR l.titulo LIKE ? OR l.autor LIKE ? OR CAST(e.id AS CHAR) LIKE ?
    ORDER BY FIELD(e.status, 'V', 'A', 'D'), e.data_emprestimo DESC, e.id DESC
    LIMIT 8
");
$stmt->execute([$termo, $termo, $termo, $termo, $termo, $termo]);
$emprestimos = $stmt->fetchAll();

$jsonFlags = JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK;
if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
    $jsonFlags |= JSON_INVALID_UTF8_SUBSTITUTE;
}

echo json_encode([
    'paginas' => $paginas,
    'alunos' => $alunos,
    'livros' => $livros,
    'emprestimos' => $emprestimos,
], $jsonFlags);