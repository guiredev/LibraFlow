<?php
/*
 * MAPA RAPIDO DO ARQUIVO
 * Local: public/usuario/perfil.php
 * Funcao: Edicao simples dos dados cadastrais do usuario logado.
 */

require $_SERVER['DOCUMENT_ROOT'] . '/LibraFlow/app/config/auth_check.php';
require $_SERVER['DOCUMENT_ROOT'] . '/LibraFlow/app/config/conexao.php';
require $_SERVER['DOCUMENT_ROOT'] . '/LibraFlow/app/config/user_features.php';

if ($_SESSION['usuario_tipo'] === 'D') {
    header('Location: /LibraFlow/public/admin/Admin.php');
    exit;
}

libraflowEnsureUserFeatureTables($conn);

$erro = '';
$sucesso = '';
$csrfToken = libraflowCsrfToken();

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!libraflowValidateCsrfToken($_POST['csrf_token'] ?? null)) {
            $erro = 'Sessao expirada. Recarregue a pagina e tente novamente.';
        } else {
            $telefone = trim($_POST['telefone'] ?? '');
            $endereco = trim($_POST['endereco'] ?? '');
            $idade = trim($_POST['idade'] ?? '');

            if ($idade !== '' && (!ctype_digit($idade) || (int) $idade < 1 || (int) $idade > 120)) {
                $erro = 'Informe uma idade valida.';
            } else {
                $stmt = $conn->prepare("
                    UPDATE usuarios
                    SET telefone = ?, endereco = ?, idade = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $telefone,
                    $endereco,
                    $idade === '' ? null : (int) $idade,
                    $_SESSION['usuario_id'],
                ]);

                libraflowCriarNotificacao(
                    $conn,
                    (int) $_SESSION['usuario_id'],
                    'Perfil atualizado',
                    'Seus dados cadastrais foram atualizados.',
                    '/LibraFlow/public/usuario/perfil.php'
                );

                $sucesso = 'Perfil atualizado com sucesso.';
            }
        }
    }

    $stmt = $conn->prepare("SELECT nome, email, telefone, rm, endereco, idade FROM usuarios WHERE id = ?");
    $stmt->execute([$_SESSION['usuario_id']]);
    $usuario = $stmt->fetch();
} catch (PDOException $e) {
    $usuario = [
        'nome' => $_SESSION['usuario_nome'] ?? 'Aluno',
        'email' => $_SESSION['usuario_email'] ?? '',
        'telefone' => '',
        'rm' => '',
        'endereco' => '',
        'idade' => '',
    ];
    $erro = 'Nao foi possivel carregar ou atualizar o perfil.';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="/LibraFlow/public/admin/darkmode-btn.css">
    <title>Perfil | LibraFlow</title>
    <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400..700;1,400..700&family=Source+Sans+3:ital,wght@0,200..900;1,200..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <nav>
        <div class="logo-nav">
            <img src="/LibraFlow/public/catalogo/imgs/Logo-LibraFlow.png" alt="Logo LibraFlow">
            <span>LibraFlow</span>
        </div>
        <div class="links-nav">
            <ul>
                <li><a href="/LibraFlow/public/usuario/index.php"><i class="fas fa-house" aria-hidden="true"></i> Inicio</a></li>
                <li><a class="ativo" href="/LibraFlow/public/usuario/perfil.php"><i class="fas fa-user" aria-hidden="true"></i> Perfil</a></li>
                <li><a href="/LibraFlow/public/catalogo/catalogo.php"><i class="fas fa-book-open" aria-hidden="true"></i> Catalogo</a></li>
                <li><a href="/LibraFlow/public/catalogo/meus_emprestimos.php"><i class="fas fa-bookmark" aria-hidden="true"></i> Meus emprestimos</a></li>
                <li><a href="/LibraFlow/public/auth/logout.php"><i class="fas fa-right-from-bracket" aria-hidden="true"></i> Sair</a></li>
            </ul>
        </div>
        <div class="user">
            <span><i class="fas fa-user" aria-hidden="true"></i> <?= htmlspecialchars($usuario['nome']) ?></span>
        </div>
    </nav>

    <main class="perfil-page">
        <section class="painel perfil-form-panel">
            <div class="painel-topo">
                <div>
                    <h1>Perfil</h1>
                    <p>Revise seus dados e mantenha o cadastro atualizado.</p>
                </div>
            </div>

            <?php if ($erro): ?>
                <div class="alerta alerta-erro"><?= htmlspecialchars($erro) ?></div>
            <?php endif; ?>

            <?php if ($sucesso): ?>
                <div class="alerta alerta-sucesso"><?= htmlspecialchars($sucesso) ?></div>
            <?php endif; ?>

            <form method="POST" class="perfil-form">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                <label>
                    <span>Nome</span>
                    <input type="text" value="<?= htmlspecialchars($usuario['nome']) ?>" disabled>
                </label>

                <label>
                    <span>Email</span>
                    <input type="email" value="<?= htmlspecialchars($usuario['email']) ?>" disabled>
                </label>

                <label>
                    <span>RM</span>
                    <input type="text" value="<?= htmlspecialchars($usuario['rm'] ?: '-') ?>" disabled>
                </label>

                <label>
                    <span>Telefone</span>
                    <input type="text" name="telefone" value="<?= htmlspecialchars($usuario['telefone'] ?? '') ?>" maxlength="30">
                </label>

                <label>
                    <span>Idade</span>
                    <input type="number" name="idade" value="<?= htmlspecialchars((string) ($usuario['idade'] ?? '')) ?>" min="1" max="120">
                </label>

                <label class="campo-largo">
                    <span>Endereco</span>
                    <input type="text" name="endereco" value="<?= htmlspecialchars($usuario['endereco'] ?? '') ?>" maxlength="255">
                </label>

                <div class="perfil-form-actions">
                    <button type="submit" class="btn-primary"><i class="fas fa-floppy-disk" aria-hidden="true"></i> Salvar</button>
                    <a href="/LibraFlow/public/usuario/index.php" class="btn-secondary">Voltar</a>
                </div>
            </form>
        </section>
    </main>

    <button id="themeToggle" class="theme-toggle-float" aria-label="Alternar tema claro/escuro">
        <span id="themeIcon"><i class="fas fa-moon" aria-hidden="true"></i></span>
        <span id="themeLabel">Escuro</span>
    </button>

    <script src="/LibraFlow/public/usuario/darkmode.js"></script>
</body>
</html>
