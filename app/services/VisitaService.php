<?php
/* Serviço compartilhado para validação e registro das visitas individuais. */

class VisitaService
{
    public const PERIODOS = ['Manha', 'Tarde', 'Noite'];
    public const SERIES = ['1º ano', '2º ano', '3º ano'];
    public const MOTIVOS = ['Estudo', 'Pesquisa', 'Leitura', 'Empréstimo', 'Devolução', 'Trabalho escolar', 'Uso do espaço', 'Outro'];

    private const JANELA_DUPLICIDADE_MINUTOS = 5;

    public function __construct(private PDO $pdo)
    {
    }

    /** @return array{nome:string,rm:string,periodo:string,serie:string,ano:int,motivo:string,id_usuario:?int} */
    public function validarDados(array $dados): array
    {
        $nome = trim((string) ($dados['nome'] ?? ''));
        $rm = trim((string) ($dados['rm'] ?? ''));
        $periodo = (string) ($dados['periodo'] ?? '');
        $serie = (string) ($dados['serie'] ?? '');
        $ano = filter_var($dados['ano'] ?? null, FILTER_VALIDATE_INT);
        $motivo = (string) ($dados['motivo'] ?? '');
        $anoAtual = (int) date('Y');

        if ($nome === '' || mb_strlen($nome) < 3 || mb_strlen($nome) > 100 || !preg_match("/^[\\p{L}][\\p{L} .'-]*$/u", $nome)) {
            throw new InvalidArgumentException('Informe um nome completo válido.');
        }
        if (!preg_match('/^[A-Za-z0-9]{3,30}$/', $rm)) {
            throw new InvalidArgumentException('RM inválido. Use de 3 a 30 letras ou números.');
        }
        if (!in_array($periodo, self::PERIODOS, true)) {
            throw new InvalidArgumentException('Período inválido.');
        }
        if (!in_array($serie, self::SERIES, true)) {
            throw new InvalidArgumentException('Série inválida.');
        }
        if ($ano === false || $ano < $anoAtual - 1 || $ano > $anoAtual + 1) {
            throw new InvalidArgumentException('Ano letivo inválido.');
        }
        if (!in_array($motivo, self::MOTIVOS, true)) {
            throw new InvalidArgumentException('Motivo da visita inválido.');
        }

        $buscaAluno = $this->pdo->prepare("SELECT id, nome FROM usuarios WHERE rm = ? AND tipo = 'A' LIMIT 1");
        $buscaAluno->execute([$rm]);
        $aluno = $buscaAluno->fetch();

        return [
            'nome' => $aluno['nome'] ?? $nome,
            'rm' => $rm,
            'periodo' => $periodo,
            'serie' => $serie,
            'ano' => $ano,
            'motivo' => $motivo,
            'id_usuario' => isset($aluno['id']) ? (int) $aluno['id'] : null,
        ];
    }

    /** @return 'registrada'|'duplicada' */
    public function registrar(array $dados): string
    {
        $dadosValidados = $this->validarDados($dados);
        $agora = new DateTimeImmutable('now', new DateTimeZone('America/Sao_Paulo'));
        $limite = $agora->modify('-' . self::JANELA_DUPLICIDADE_MINUTOS . ' minutes');

        $duplicada = $this->pdo->prepare(
            'SELECT id FROM registros_visitas WHERE rm = ? AND criado_em >= ? LIMIT 1'
        );
        $duplicada->execute([$dadosValidados['rm'], $limite->format('Y-m-d H:i:s')]);
        if ($duplicada->fetchColumn()) {
            return 'duplicada';
        }

        $insere = $this->pdo->prepare(
            'INSERT INTO registros_visitas
                (id_usuario, nome, rm, periodo, serie, ano, motivo, data_visita, hora_visita, criado_em)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $insere->execute([
            $dadosValidados['id_usuario'], $dadosValidados['nome'], $dadosValidados['rm'],
            $dadosValidados['periodo'], $dadosValidados['serie'], $dadosValidados['ano'],
            $dadosValidados['motivo'], $agora->format('Y-m-d'), $agora->format('H:i:s'),
            $agora->format('Y-m-d H:i:s'),
        ]);

        return 'registrada';
    }
}
