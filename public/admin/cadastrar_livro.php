<?php
/*
 * MAPA RAPIDO DO ARQUIVO
 * Local: public/admin/cadastrar_livro.php
 * Funcao: Cadastro de livros e upload de capa para public/catalogo/capas.
 */
// public/admin/cadastrar_livro.php

require $_SERVER['DOCUMENT_ROOT'] . '/LibraFlow/app/config/auth_check.php';

if ($_SESSION['usuario_tipo'] !== 'D') {
    header('Location: /LibraFlow/public/usuario/index.php');
    exit;
}

require $_SERVER['DOCUMENT_ROOT'] . '/LibraFlow/app/config/conexao.php';

$categorias = $conn->query("SELECT id, nome FROM categorias ORDER BY nome")->fetchAll();
$erro    = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo      = trim($_POST['titulo']      ?? '');
    $autor       = trim($_POST['autor']       ?? '');
    $subtitulo   = trim($_POST['subtitulo']   ?? '');
    $ano         = trim($_POST['ano']         ?? '');
    $isbn        = trim($_POST['isbn']        ?? '');
    $descricao   = trim($_POST['descricao']   ?? '');
    $quantidade  = intval($_POST['quantidade'] ?? 1);
    $id_categoria = intval($_POST['id_categoria'] ?? 0);

    if (empty($titulo) || empty($autor)) {
        $erro = 'Título e autor são obrigatórios.';
    } else {
        $capa = null;

        if (isset($_FILES['capa']) && $_FILES['capa']['error'] === UPLOAD_ERR_OK) {
            $ext        = strtolower(pathinfo($_FILES['capa']['name'], PATHINFO_EXTENSION));
            $permitidos = ['jpg', 'jpeg', 'png', 'webp'];

            if (!in_array($ext, $permitidos)) {
                $erro = 'Formato inválido. Use JPG, PNG ou WEBP.';
            } elseif ($_FILES['capa']['size'] > 2 * 1024 * 1024) {
                $erro = 'A imagem deve ter no máximo 2MB.';
            } else {
                $nomeCapa = uniqid('capa_') . '.' . $ext;
                $destino  = $_SERVER['DOCUMENT_ROOT'] . '/LibraFlow/public/catalogo/capas/' . $nomeCapa;

                if (!is_dir(dirname($destino))) mkdir(dirname($destino), 0755, true);

                if (move_uploaded_file($_FILES['capa']['tmp_name'], $destino)) {
                    $capa = $nomeCapa;
                } else {
                    $erro = 'Erro ao salvar a imagem.';
                }
            }
        }

        if (empty($erro)) {
            $stmt = $conn->prepare("
                INSERT INTO livros (titulo, autor, subtitulo, ano, isbn, descricao, quantidade, id_categoria, capa)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            try {
                $stmt->execute([
                    $titulo, $autor,
                    $subtitulo    ?: null,
                    $ano          ?: null,
                    $isbn         ?: null,
                    $descricao    ?: null,
                    $quantidade,
                    $id_categoria ?: null,
                    $capa
                ]);
                $sucesso = 'Livro cadastrado com sucesso!';
            } catch (PDOException $e) {
                $erro = $e->getCode() == 23000 ? 'ISBN já cadastrado.' : 'Erro ao cadastrar livro.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar Livro | LibraFlow Admin</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="darkmode-btn.css">
    <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400..700;1,400..700&family=Source+Sans+3:ital,wght@0,200..900;1,200..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <aside>
        <div class="logo-aside"><span>LibraFlow</span></div>
        <ul>
             <li><a href="/LibraFlow/public/admin/Admin.php"><i class="fas fa-house nav-icon" aria-hidden="true"></i> Início</a></li>
            <li><a href="/LibraFlow/public/admin/listar_livros.php"><i class="fas fa-book-open nav-icon" aria-hidden="true"></i> Livros</a></li>
            <li><a href="/LibraFlow/public/admin/cadastrar_livro.php" class="ativo"><i class="fas fa-plus nav-icon" aria-hidden="true"></i> Cadastrar Livro</a></li>
            <li><a href="/LibraFlow/public/admin/usuarios.php"><i class="fas fa-users nav-icon" aria-hidden="true"></i> Usuários</a></li>
            <li><a href="/LibraFlow/public/admin/emprestimos.php"><i class="fas fa-clipboard-list nav-icon" aria-hidden="true"></i> Empréstimos</a></li>
            <li><a href="/LibraFlow/public/admin/visitas.php"><i class="fas fa-clock nav-icon" aria-hidden="true"></i> Visitas</a></li>
            <li><a href="relatorios/index.php"><i class="fas fa-chart-line nav-icon" aria-hidden="true"></i> Relatórios</a></li>
            <div class="sidebar-down">
                <li><a href="/LibraFlow/public/auth/logout.php"><i class="fas fa-right-from-bracket nav-icon" aria-hidden="true"></i> Sair</a></li>
            </div>
        </ul>
    </aside>

    <nav>
        <span style="font-family:'Lora',serif;font-size:2rem;color:#283618;">Cadastrar Livro</span>
        <div class="right">
            <span style="font-size:1.4rem;color:#606C38;"><i class="fas fa-user" aria-hidden="true"></i> <?= htmlspecialchars($_SESSION['usuario_nome']) ?></span>
        </div>
    </nav>

    <header>
        <h1>Cadastrar Livro</h1>
        <p>Preencha os dados do livro para adicioná-lo ao acervo.</p>
    </header>

    <main>
        <div class="form-card">

            <?php if ($erro): ?>
                <div class="alerta alerta-erro"><?= htmlspecialchars($erro) ?></div>
            <?php endif; ?>

            <?php if ($sucesso): ?>
                <div class="alerta alerta-sucesso">
                    <?= htmlspecialchars($sucesso) ?>
                    — <a href="listar_livros.php" style="color:#166534;font-weight:bold;">Ver todos os livros</a>
                </div>
            <?php endif; ?>

            <form method="POST" action="" enctype="multipart/form-data">
                <div class="form-grid">

                    <div class="form-group full">
                        <label for="titulo">Título *</label>
                        <input type="text" id="titulo" name="titulo"
                            value="<?= htmlspecialchars($_POST['titulo'] ?? '') ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="autor">Autor *</label>
                        <input type="text" id="autor" name="autor"
                            value="<?= htmlspecialchars($_POST['autor'] ?? '') ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="subtitulo">Subtítulo</label>
                        <input type="text" id="subtitulo" name="subtitulo"
                            value="<?= htmlspecialchars($_POST['subtitulo'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label for="ano">Ano de publicação</label>
                        <input type="number" id="ano" name="ano" min="1000" max="2099"
                            value="<?= htmlspecialchars($_POST['ano'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label for="isbn">ISBN</label>
                        <input type="text" id="isbn" name="isbn"
                            value="<?= htmlspecialchars($_POST['isbn'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label for="quantidade">Quantidade disponível</label>
                        <input type="number" id="quantidade" name="quantidade" min="1"
                            value="<?= htmlspecialchars($_POST['quantidade'] ?? '1') ?>">
                    </div>

                    <div class="form-group">
                        <label for="id_categoria">Categoria</label>
                        <select id="id_categoria" name="id_categoria">
                            <option value="">Selecione...</option>
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?= $cat['id'] ?>"
                                    <?= (($_POST['id_categoria'] ?? '') == $cat['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group full">
                        <label>Capa do livro</label>
                        <div class="upload-area">
                            <input type="file" name="capa" id="inputCapa" accept=".jpg,.jpeg,.png,.webp">
                            <p><i class="fas fa-folder-open" aria-hidden="true"></i> Clique para selecionar uma imagem<br><small>JPG, PNG ou WEBP — máx. 2MB</small></p>
                            <img id="previewCapa" class="preview-capa" src="" alt="Preview da capa">
                        </div>
                    </div>

                    <div class="form-group full">
                        <label for="descricao">Descrição / Sinopse</label>
                        <textarea id="descricao" name="descricao"><?= htmlspecialchars($_POST['descricao'] ?? '') ?></textarea>
                    </div>

                </div>

                <button type="submit" class="btn-salvar">Cadastrar Livro</button>
                <a href="listar_livros.php" class="voltar" style="margin-left:2rem;"><i class="fas fa-arrow-left" aria-hidden="true"></i> Cancelar</a>
            </form>
        </div>
    </main>

    <script>
        document.getElementById('inputCapa').addEventListener('change', function () {
            const file = this.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = e => {
                const img = document.getElementById('previewCapa');
                img.src = e.target.result;
                img.style.display = 'block';
                document.querySelector('.upload-area p').style.display = 'none';
            };
            reader.readAsDataURL(file);
        });
    </script>

    <!-- Botão Dark Mode -->
    <button id="themeToggle" class="theme-toggle-float" aria-label="Alternar tema claro/escuro">
        <span id="themeIcon"><i class="fas fa-moon" aria-hidden="true"></i></span>
        <span id="themeLabel">Escuro</span>
    </button>

    <script src="darkmode.js"></script>
</body>
</html>

