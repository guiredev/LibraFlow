<?php
/*
 * MAPA RAPIDO DO ARQUIVO
 * Local: public/admin/perfil.php
 * Funcao: Configuracoes de perfil do administrador logado.
 */

require $_SERVER['DOCUMENT_ROOT'] . '/LibraFlow/app/config/auth_check.php';

if ($_SESSION['usuario_tipo'] !== 'D') {
    header('Location: /LibraFlow/public/usuario/index.php');
    exit;
}

require $_SERVER['DOCUMENT_ROOT'] . '/LibraFlow/app/config/conexao.php';
require $_SERVER['DOCUMENT_ROOT'] . '/LibraFlow/app/config/uploads.php';
require $_SERVER['DOCUMENT_ROOT'] . '/LibraFlow/app/config/user_features.php';

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
                $sucesso = 'Perfil atualizado com sucesso.';
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
        'nome' => $_SESSION['usuario_nome'] ?? 'Administrador',
        'email' => '',
        'telefone' => '',
        'rm' => '',
        'endereco' => '',
        'idade' => '',
        'foto_perfil' => null,
        'biografia' => '',
        'preferencia_notificacoes' => 1,
    ];
    $erro = 'Nao foi possivel carregar ou atualizar o perfil.';
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
    <title>Perfil Admin | LibraFlow Admin</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="darkmode-btn.css">
    <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400..700;1,400..700&family=Source+Sans+3:ital,wght@0,200..900;1,200..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <aside>
        <div class="logo-aside"><span>LibraFlow</span></div>
        <ul>
            <li class="nav-section">Principal</li><li><a href="/LibraFlow/public/admin/Admin.php"><i class="fas fa-house nav-icon" aria-hidden="true"></i> Visão geral</a></li>
            <li class="nav-section">Biblioteca</li><li><a href="/LibraFlow/public/admin/listar_livros.php"><i class="fas fa-book-open nav-icon" aria-hidden="true"></i> Acervo</a></li>
            <li><a href="/LibraFlow/public/admin/cadastrar_livro.php"><i class="fas fa-plus nav-icon" aria-hidden="true"></i> Adicionar livro</a></li>
            <li class="nav-section">Operação</li><li><a href="/LibraFlow/public/admin/emprestimos.php"><i class="fas fa-clipboard-list nav-icon" aria-hidden="true"></i> Empréstimos</a></li>
            <li><a href="/LibraFlow/public/admin/usuarios.php"><i class="fas fa-users nav-icon" aria-hidden="true"></i> Pessoas</a></li>
            <li><a href="/LibraFlow/public/admin/visitas.php"><i class="fas fa-clock nav-icon" aria-hidden="true"></i> Visitas</a></li>
            <li class="nav-section">Análises</li><li><a href="relatorios/index.php"><i class="fas fa-chart-line nav-icon" aria-hidden="true"></i> Relatórios</a></li>
            <div class="sidebar-down">
                <li><a href="/LibraFlow/public/auth/logout.php"><i class="fas fa-right-from-bracket nav-icon" aria-hidden="true"></i> Sair</a></li>
            </div>
        </ul>
    </aside>

    <nav>
        <div class="logo-nav"><span>Perfil Admin</span></div>
        <div class="right">
            <div class="admin-user-menu">
                <button type="button" class="admin-user-button" aria-expanded="false" aria-controls="adminUserMenu">
                    <span class="admin-user-avatar">
                        <?php if ($fotoPerfilUrl): ?>
                            <img src="<?= htmlspecialchars($fotoPerfilUrl) ?>" alt="">
                        <?php else: ?>
                            <?= htmlspecialchars(substr($usuario['nome'], 0, 1)) ?>
                        <?php endif; ?>
                    </span>
                    <span class="admin-user-name"><?= htmlspecialchars($usuario['nome']) ?></span>
                    <i class="fas fa-chevron-down" aria-hidden="true"></i>
                </button>
                <div class="admin-user-dropdown" id="adminUserMenu">
                    <a href="/LibraFlow/public/admin/Admin.php"><i class="fas fa-house" aria-hidden="true"></i> Painel</a>
                    <a href="/LibraFlow/public/admin/perfil.php"><i class="fas fa-user-gear" aria-hidden="true"></i> Perfil</a>
                    <a href="/LibraFlow/public/admin/usuarios.php"><i class="fas fa-users" aria-hidden="true"></i> Usuarios</a>
                    <a href="/LibraFlow/public/admin/emprestimos.php"><i class="fas fa-clipboard-list" aria-hidden="true"></i> Emprestimos</a>
                    <a href="/LibraFlow/public/auth/logout.php" class="sair"><i class="fas fa-right-from-bracket" aria-hidden="true"></i> Sair</a>
                </div>
            </div>
        </div>
    </nav>

    <header>
        <h1>Perfil do administrador</h1>
        <p>Atualize sua foto, biografia e dados de contato.</p>
    </header>

    <main>
        <div class="form-card perfil-admin-card">
            <?php if ($erro): ?>
                <div class="alerta alerta-erro"><?= htmlspecialchars($erro) ?></div>
            <?php endif; ?>

            <?php if ($sucesso): ?>
                <div class="alerta alerta-sucesso"><?= htmlspecialchars($sucesso) ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <div class="form-grid">
                    <div class="form-group full">
                        <label>Foto de perfil</label>
                        <label class="upload-area upload-area-perfil" for="fotoPerfil">
                            <span class="admin-foto-preview">
                                <?php if ($fotoPerfilUrl): ?>
                                    <img src="<?= htmlspecialchars($fotoPerfilUrl) ?>" alt="Foto de perfil">
                                <?php else: ?>
                                    <?= htmlspecialchars(substr($usuario['nome'], 0, 1)) ?>
                                <?php endif; ?>
                            </span>
                            <input type="file" name="foto_perfil" id="fotoPerfil" accept=".jpg,.jpeg,.png,.webp">
                            <span class="upload-icon"><i class="fas fa-image" aria-hidden="true"></i></span>
                            <strong><i class="fas fa-upload" aria-hidden="true"></i> Escolher foto</strong>
                            <p id="nomeFoto">JPG, PNG ou WEBP - max. 1MB. Use uma imagem quadrada.</p>
                        </label>
                    </div>

                    <div class="form-group">
                        <label>Nome</label>
                        <input type="text" value="<?= htmlspecialchars($usuario['nome']) ?>" disabled>
                    </div>

                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" value="<?= htmlspecialchars($usuario['email']) ?>" disabled>
                    </div>

                    <div class="form-group">
                        <label>Telefone</label>
                        <input type="text" name="telefone" value="<?= htmlspecialchars($usuario['telefone'] ?? '') ?>" maxlength="30">
                    </div>

                    <div class="form-group">
                        <label>Idade</label>
                        <input type="number" name="idade" value="<?= htmlspecialchars((string) ($usuario['idade'] ?? '')) ?>" min="1" max="120">
                    </div>

                    <div class="form-group full">
                        <label>Endereco</label>
                        <input type="text" name="endereco" value="<?= htmlspecialchars($usuario['endereco'] ?? '') ?>" maxlength="255">
                    </div>

                    <div class="form-group full">
                        <label>Biografia</label>
                        <textarea name="biografia" maxlength="600" rows="5"><?= htmlspecialchars($usuario['biografia'] ?? '') ?></textarea>
                    </div>

                    <label class="admin-check full">
                        <input type="checkbox" name="preferencia_notificacoes" value="1" <?= !empty($usuario['preferencia_notificacoes']) ? 'checked' : '' ?>>
                        <span>Receber notificacoes internas sobre emprestimos, renovacoes e movimentacoes.</span>
                    </label>
                </div>

                <button type="submit" class="btn-salvar">Salvar perfil</button>
                <a href="Admin.php" class="voltar" style="margin-left:2rem;"><i class="fas fa-arrow-left" aria-hidden="true"></i> Voltar</a>
            </form>
        </div>
    </main>

    <button id="themeToggle" class="theme-toggle-float" aria-label="Alternar tema claro/escuro">
        <span id="themeIcon"><i class="fas fa-moon" aria-hidden="true"></i></span>
        <span id="themeLabel">Escuro</span>
    </button>

    <script>
        const fotoInput = document.getElementById('fotoPerfil');
        const nomeFoto = document.getElementById('nomeFoto');
        if (fotoInput && nomeFoto) {
            fotoInput.addEventListener('change', function () {
                nomeFoto.textContent = this.files && this.files[0]
                    ? this.files[0].name
                    : 'JPG, PNG ou WEBP - max. 1MB. Use uma imagem quadrada.';
            });
        }
    </script>
    <script src="darkmode.js"></script>
    <script src="/LibraFlow/public/admin/admin-user-menu.js"></script>
</body>
</html>
