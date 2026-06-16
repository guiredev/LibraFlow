<?php
/**
 * RelatorioService.php
 * Centraliza as consultas SQL usadas pelos relatórios do LibraFlow.
 *
 * Uso:
 *   require 'RelatorioService.php';
 *   $servico = new RelatorioService($pdo);
 *   $dados = $servico->emprestimosAtrasados();
 */

class RelatorioService
{
    /** Prazo de devolução em dias, contado a partir da aprovação do empréstimo */
    const PRAZO_DIAS = 7;

    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * 1) Empréstimos em atraso
     * Considera "atrasado" todo empréstimo com status='aprovado' cuja
     * data_aprovacao + PRAZO_DIAS já passou e ainda não foi devolvido.
     */
    public function emprestimosAtrasados(): array
    {
        $sql = "
            SELECT
                e.id                   AS emprestimo_id,
                u.nome                 AS aluno_nome,
                u.email                AS aluno_email,
                l.titulo               AS livro_titulo,
                l.autor                AS livro_autor,
                e.data_emprestimo      AS data_emprestimo,
                e.data_prevista_devolucao AS data_prevista_devolucao,
                DATEDIFF(CURDATE(), e.data_prevista_devolucao) AS dias_atraso
            FROM emprestimos e
            JOIN usuarios u ON u.id = e.id_usuario
            JOIN livros   l ON l.id = e.id_livro
            WHERE e.status = 'A'
              AND e.data_prevista_devolucao < CURDATE()
            ORDER BY dias_atraso DESC
        ";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 2) Histórico geral de empréstimos
     * Opcionalmente filtrado por intervalo de datas (data_emprestimo).
     */
    public function historicoEmprestimos(?string $dataInicio = null, ?string $dataFim = null): array
    {
        $sql = "
            SELECT
                e.id              AS emprestimo_id,
                u.nome            AS aluno_nome,
                u.email           AS aluno_email,
                l.titulo          AS livro_titulo,
                l.autor           AS livro_autor,
                e.status,
                e.data_emprestimo,
                e.data_prevista_devolucao,
                e.data_devolucao
            FROM emprestimos e
            JOIN usuarios u ON u.id = e.id_usuario
            JOIN livros   l ON l.id = e.id_livro
            WHERE 1=1
        ";

        $params = [];
        if ($dataInicio) {
            $sql .= " AND e.data_emprestimo >= :inicio";
            $params[':inicio'] = $dataInicio;
        }
        if ($dataFim) {
            $sql .= " AND e.data_emprestimo <= :fim";
            $params[':fim'] = $dataFim;
        }

        $sql .= " ORDER BY e.data_emprestimo DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 3) Acervo completo (estoque de livros)
     * Mostra quantidade total, quantidade emprestada (aprovados não devolvidos)
     * e quantidade disponível.
     */
    public function acervoCompleto(): array
    {
        $sql = "
            SELECT
                l.id,
                l.titulo,
                l.subtitulo,
                l.autor,
                l.categoria,
                l.isbn,
                l.ano,
                l.quantidade AS quantidade_total,
                COALESCE(emp.qtd_emprestada, 0) AS quantidade_emprestada,
                (l.quantidade - COALESCE(emp.qtd_emprestada, 0)) AS quantidade_disponivel
            FROM livros l
            LEFT JOIN (
                SELECT id_livro, COUNT(*) AS qtd_emprestada
                FROM emprestimos
                WHERE status IN ('A', 'V')
                GROUP BY id_livro
            ) emp ON emp.id_livro = l.id
            ORDER BY l.titulo ASC
        ";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 4) Livros mais emprestados (ranking)
     * Conta o total de solicitações (qualquer status) e o total de
     * empréstimos efetivamente aprovados, ordenando pelo total geral.
     */
    public function livrosMaisEmprestados(int $limite = 20): array
    {
        $sql = "
            SELECT
                l.id,
                l.titulo,
                l.autor,
                l.categoria,
                COUNT(e.id) AS total_solicitacoes,
                SUM(CASE WHEN e.status IN ('A', 'V') THEN 1 ELSE 0 END) AS total_aprovados,
                SUM(CASE WHEN e.status = 'D' THEN 1 ELSE 0 END) AS total_devolvidos
            FROM livros l
            LEFT JOIN emprestimos e ON e.id_livro = l.id
            GROUP BY l.id, l.titulo, l.autor, l.categoria
            HAVING total_solicitacoes > 0
            ORDER BY total_solicitacoes DESC
            LIMIT :limite
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Resumo rápido (cards do painel de relatórios)
     */
    public function resumoGeral(): array
    {
        $totalLivros = (int) $this->pdo->query("SELECT COALESCE(SUM(quantidade),0) FROM livros")->fetchColumn();
        $totalAlunos = (int) $this->pdo->query("SELECT COUNT(*) FROM usuarios WHERE tipo = 'A'")->fetchColumn();
        $emprestimosAtivos = (int) $this->pdo->query("SELECT COUNT(*) FROM emprestimos WHERE status = 'A'")->fetchColumn();

        $sqlAtraso = "
            SELECT COUNT(*) FROM emprestimos
            WHERE status = 'A'
              AND data_prevista_devolucao < CURDATE()
        ";        $totalAtrasados = (int) $this->pdo->query($sqlAtraso)->fetchColumn();

        return [
            'total_livros'        => $totalLivros,
            'total_alunos'        => $totalAlunos,
            'emprestimos_ativos'  => $emprestimosAtivos,
            'emprestimos_atrasados' => $totalAtrasados,
        ];
    }
}
