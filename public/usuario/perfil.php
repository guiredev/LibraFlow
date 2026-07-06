<?php
/*
 * MAPA RAPIDO DO ARQUIVO
 * Local: public/usuario/perfil.php
 * Funcao: Configuracoes pessoais do usuario logado.
 */

require $_SERVER['DOCUMENT_ROOT'] . '/LibraFlow/app/config/auth_check.php';
require $_SERVER['DOCUMENT_ROOT'] . '/LibraFlow/app/config/conexao.php';
require $_SERVER['DOCUMENT_ROOT'] . '/LibraFlow/app/config/user_features.php';
require $_SERVER['DOCUMENT_ROOT'] . '/LibraFlow/app/config/uploads.php';

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
            $biografia = trim($_POST['biografia'] ?? '');
            $preferenciaNotificacoes = isset($_POST['preferencia_notificacoes']) ? 1 : 0;
            $fotoPerfil = null;

            if ($idade !== '' && (!ctype_digit($idade) || (int) $idade < 1 || (int) $idade > 120)) {
                $erro = 'Informe uma idade valida.';
            } elseif (strlen($biografia) > 600) {
                $erro = 'A biografia deve ter no maximo 600 caracteres.';
            } else {
                if (isset($_FILES['foto_perfil']) && ($_FILES['foto_perfil']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                    [$uploadOk, $uploadResult] = libraflowValidateUploadedImage($_FILES['foto_perfil'], 1048576);

                    if (!$uploadOk) {
                        $erro = $uploadResult;
                    } else {
                        $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/LibraFlow/public/usuario/fotos';
                        if (!is_dir($uploadDir)) {
                            mkdir($uploadDir, 0775, true);
                        }

                        $fotoPerfil = 'perfil_' . (int) $_SESSION['usuario_id'] . '_' . bin2hex(random_bytes(8)) . '.' . $uploadResult;
                        if (!move_uploaded_file($_FILES['foto_perfil']['tmp_name'], $uploadDir . '/' . $fotoPerfil)) {
                            $erro = 'Nao foi possivel salvar a foto de perfil.';
                            $fotoPerfil = null;
                        }
                    }
                }
            }

            if (!$erro) {
                $fotoSql = $fotoPerfil ? ', foto_perfil = ?' : '';
                $stmt = $conn->prepare("
                    UPDATE usuarios
                    SET telefone = ?,
                        endereco = ?,
                        idade = ?,
                        biografia = ?,
                        preferencia_notificacoes = ?
                        {$fotoSql}
                    WHERE id = ?
                ");
                $params = [
                    $telefone,
                    $endereco,
                    $idade === '' ? null : (int) $idade,
                    $biografia,
                    $preferenciaNotificacoes,
                ];

                if ($fotoPerfil) {
                    $params[] = $fotoPerfil;
                }

                $params[] = $_SESSION['usuario_id'];
                $stmt->execute($params);

                libraflowCriarNotificacao(
                    $conn,
                    (int) $_SESSION['usuario_id'],
                    'Configuracoes atualizadas',
                    'Suas configuracoes de perfil foram atualizadas.',
                    '/LibraFlow/public/usuario/perfil.php'
                );

                $sucesso = 'Configuracoes salvas com sucesso.';
            }
        }
    }

    $stmt = $conn->prepare("
        SELECT nome, email, telefone, rm, endereco, idade, foto_perfil, biografia, preferencia_notificacoes
        FROM usuarios
        WHERE id = ?
    ");
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
        'foto_perfil' => null,
        'biografia' => '',
        'preferencia_notificacoes' => 1,
    ];
    $erro = 'Nao foi possivel carregar ou atualizar as configuracoes.';
}

$fotoPerfilUrl = !empty($usuario['foto_perfil'])
    ? '/LibraFlow/public/usuario/fotos/' . rawurlencode($usuario['foto_perfil'])
    : null;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="/LibraFlow/public/admin/darkmode-btn.css">
    <title>Configurações | LibraFlow</title>
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
                <li><a href="/LibraFlow/public/catalogo/catalogo.php"><i class="fas fa-book-open" aria-hidden="true"></i> Catalogo</a></li>
                <li><a href="/LibraFlow/public/catalogo/meus_emprestimos.php"><i class="fas fa-bookmark" aria-hidden="true"></i> Meus emprestimos</a></li>
                <li><a href="/LibraFlow/public/auth/logout.php"><i class="fas fa-right-from-bracket" aria-hidden="true"></i> Sair</a></li>
            </ul>
        </div>
        <div class="user">
            <button type="button" class="user-menu-button" aria-expanded="false" aria-controls="userMenu">
                <span class="user-avatar">
                    <?php if ($fotoPerfilUrl): ?>
                        <img src="<?= htmlspecialchars($fotoPerfilUrl) ?>" alt="">
                    <?php else: ?>
                        <?= htmlspecialchars(substr($usuario['nome'], 0, 1)) ?>
                    <?php endif; ?>
                </span>
                <span class="user-menu-name"><?= htmlspecialchars($usuario['nome']) ?></span>
                <i class="fas fa-chevron-down" aria-hidden="true"></i>
            </button>
            <div class="user-menu" id="userMenu">
                <a href="/LibraFlow/public/usuario/perfil.php"><i class="fas fa-gear" aria-hidden="true"></i> Configurações</a>
                <a href="/LibraFlow/public/catalogo/meus_emprestimos.php"><i class="fas fa-bookmark" aria-hidden="true"></i> Meus emprestimos</a>
                <a href="/LibraFlow/public/catalogo/catalogo.php"><i class="fas fa-book-open" aria-hidden="true"></i> Catalogo</a>
                <a href="/LibraFlow/public/auth/logout.php" class="sair"><i class="fas fa-right-from-bracket" aria-hidden="true"></i> Sair</a>
            </div>
        </div>
    </nav>

    <main class="perfil-page">
        <section class="painel perfil-form-panel">
            <div class="painel-topo">
                <div>
                    <h1>Configurações</h1>
                    <p>Personalize sua conta, foto de perfil e informações públicas.</p>
                </div>
            </div>

            <?php if ($erro): ?>
                <div class="alerta alerta-erro"><?= htmlspecialchars($erro) ?></div>
            <?php endif; ?>

            <?php if ($sucesso): ?>
                <div class="alerta alerta-sucesso"><?= htmlspecialchars($sucesso) ?></div>
            <?php endif; ?>

            <form method="POST" class="perfil-form" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                <div class="perfil-foto-bloco">
                    <div class="perfil-foto-preview">
                        <?php if ($fotoPerfilUrl): ?>
                            <img src="<?= htmlspecialchars($fotoPerfilUrl) ?>" alt="Foto de perfil">
                        <?php else: ?>
                            <span><?= htmlspecialchars(substr($usuario['nome'], 0, 1)) ?></span>
                        <?php endif; ?>
                    </div>
                    <label class="foto-upload-label">
                        <span>Foto de perfil</span>
                        <input type="file" name="foto_perfil" accept="image/jpeg,image/png,image/webp">
                        <strong><i class="fas fa-camera" aria-hidden="true"></i> Escolher foto</strong>
                        <small id="fotoNome">JPG, PNG ou WEBP ate 1MB. Use uma imagem quadrada para melhor enquadramento.</small>
                    </label>
                </div>

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

                <label class="campo-largo">
                    <span>Biografia</span>
                    <textarea name="biografia" maxlength="600" rows="5" placeholder="Conte um pouco sobre voce, seus interesses de leitura ou objetivos."><?= htmlspecialchars($usuario['biografia'] ?? '') ?></textarea>
                </label>

                <label class="preferencia-check campo-largo">
                    <input type="checkbox" name="preferencia_notificacoes" value="1" <?= !empty($usuario['preferencia_notificacoes']) ? 'checked' : '' ?>>
                    <span>Receber notificacoes internas sobre emprestimos, renovacoes e atualizacoes.</span>
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
    <script src="/LibraFlow/public/usuario/user-menu.js"></script>
    <script>
        const fotoInput = document.querySelector('input[name="foto_perfil"]');
        const fotoNome = document.getElementById('fotoNome');
        if (fotoInput && fotoNome) {
            fotoInput.addEventListener('change', function () {
                fotoNome.textContent = this.files && this.files[0]
                    ? this.files[0].name
                    : 'JPG, PNG ou WEBP ate 1MB. Use uma imagem quadrada para melhor enquadramento.';
            });
        }
    </script>
</body>
</html>
