<?php
date_default_timezone_set('America/Sao_Paulo');
session_start();

require $_SERVER['DOCUMENT_ROOT'] . '/LibraFlow/app/config/conexao.php';
require $_SERVER['DOCUMENT_ROOT'] . '/LibraFlow/app/config/auth.php';
require $_SERVER['DOCUMENT_ROOT'] . '/LibraFlow/app/services/VisitaService.php';

$erro = '';
$sucesso = '';
$valores = ['ano' => date('Y')];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $valores = $_POST;
    if (!libraflowValidateCsrfToken($_POST['csrf_token'] ?? null)) {
        $erro = 'Sua sessão expirou. Recarregue a página e tente novamente.';
    } elseif (trim((string) ($_POST['website'] ?? '')) !== '') {
        $erro = 'Não foi possível registrar sua visita. Tente novamente.';
    } else {
        try {
            $resultado = (new VisitaService($conn))->registrar($_POST);
            if ($resultado === 'duplicada') {
                $erro = 'Esta visita já foi registrada há poucos minutos.';
            } else {
                $sucesso = 'Visita registrada com sucesso!';
                $valores = ['ano' => date('Y')];
            }
        } catch (InvalidArgumentException $e) {
            $erro = $e->getMessage();
        } catch (PDOException $e) {
            error_log('Erro ao registrar visita: ' . $e->getMessage());
            $erro = 'Não foi possível registrar sua visita. Tente novamente.';
        }
    }
}

$csrfToken = libraflowCsrfToken();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#283618">
    <title>Registro de visita | LibraFlow</title>
    <link rel="stylesheet" href="style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Lora:wght@500;600;700&family=Source+Sans+3:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <main class="container">
        <section class="card" aria-labelledby="titulo">
            <div class="marca">📚 <span>LibraFlow</span></div>
            <h1 id="titulo">Registro de visita</h1>
            <p class="descricao">Informe seus dados para registrar sua entrada na biblioteca.</p>
            <?php if ($erro): ?><div class="alerta erro" role="alert"><?= htmlspecialchars($erro) ?></div><?php endif; ?>
            <?php if ($sucesso): ?><div class="alerta sucesso" role="status">✓ <?= htmlspecialchars($sucesso) ?></div><?php endif; ?>
            <form method="post" novalidate>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <div class="anti-spam" aria-hidden="true"><label for="website">Website</label><input id="website" name="website" tabindex="-1" autocomplete="off"></div>
                <label for="nome">Nome completo<input id="nome" name="nome" maxlength="100" required autocomplete="name" value="<?= htmlspecialchars($valores['nome'] ?? '') ?>"></label>
                <label for="rm">RM<input id="rm" name="rm" maxlength="30" required autocomplete="off" inputmode="text" value="<?= htmlspecialchars($valores['rm'] ?? '') ?>"></label>
                <div class="dupla">
                    <label for="periodo">Período<select id="periodo" name="periodo" required><option value="">Selecione</option><?php foreach (VisitaService::PERIODOS as $item): ?><option value="<?= $item ?>" <?= ($valores['periodo'] ?? '') === $item ? 'selected' : '' ?>><?= htmlspecialchars(str_replace('Manha', 'Manhã', $item)) ?></option><?php endforeach; ?></select></label>
                    <label for="serie">Série<select id="serie" name="serie" required><option value="">Selecione</option><?php foreach (VisitaService::SERIES as $item): ?><option value="<?= $item ?>" <?= ($valores['serie'] ?? '') === $item ? 'selected' : '' ?>><?= htmlspecialchars($item) ?></option><?php endforeach; ?></select></label>
                </div>
                <div class="dupla">
                    <label for="ano">Ano letivo<input id="ano" name="ano" type="number" min="<?= date('Y') - 1 ?>" max="<?= date('Y') + 1 ?>" required value="<?= htmlspecialchars((string) ($valores['ano'] ?? date('Y'))) ?>"></label>
                    <label for="motivo">Motivo<select id="motivo" name="motivo" required><option value="">Selecione</option><?php foreach (VisitaService::MOTIVOS as $item): ?><option value="<?= $item ?>" <?= ($valores['motivo'] ?? '') === $item ? 'selected' : '' ?>><?= htmlspecialchars($item) ?></option><?php endforeach; ?></select></label>
                </div>
                <button type="submit">Registrar visita</button>
            </form>
        </section>
    </main>
</body>
</html>
