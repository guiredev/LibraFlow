<?php
// tela_Admin/arquivos/editar_livro.php

require $_SERVER['DOCUMENT_ROOT'] . '/LibraFlow/cadastros_e_logins/configs/auth_check.php';
if ($_SESSION['usuario_tipo'] !== 'D') { header('Location: /LibraFlow/Tela_de_usuario/arquivos/index.html'); exit; }
require $_SERVER['DOCUMENT_ROOT'] . '/LibraFlow/cadastros_e_logins/configs/conexao.php';

$id = intval($_GET['id'] ?? 0);
if (!$id) { header('Location: listar_livros.php'); exit; }

$stmt = $conn->prepare("SELECT * FROM livros WHERE id = ?");
$stmt->execute([$id]);
$livro = $stmt->fetch();
if (!$livro) { header('Location: listar_livros.php'); exit; }

$categorias = $conn->query("SELECT id, nome FROM categorias ORDER BY nome")->fetchAll();
$erro    = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo      = trim($_POST['titulo']       ?? '');
    $autor       = trim($_POST['autor']        ?? '');
    $subtitulo   = trim($_POST['subtitulo']    ?? '');
    $ano         = trim($_POST['ano']          ?? '');
    $isbn        = trim($_POST['isbn']         ?? '');
    $descricao   = trim($_POST['descricao']    ?? '');
    $quantidade  = intval($_POST['quantidade'] ?? 1);
    $id_categoria = intval($_POST['id_categoria'] ?? 0);

    if (empty($titulo) || empty($autor)) {
        $erro = 'Título e autor são obrigatórios.';
    } else {
        $capa = $livro['capa'];

        if (isset($_FILES['capa']) && $_FILES['capa']['error'] === UPLOAD_ERR_OK) {
            $ext        = strtolower(pathinfo($_FILES['capa']['name'], PATHINFO_EXTENSION));
            $permitidos = ['jpg', 'jpeg', 'png', 'webp'];

            if (!in_array($ext, $permitidos)) {
                $erro = 'Formato inválido. Use JPG, PNG ou WEBP.';
            } elseif ($_FILES['capa']['size'] > 2 * 1024 * 1024) {
                $erro = 'Imagem deve ter no máximo 2MB.';
            } else {
                $nomeCapa = uniqid('capa_') . '.' . $ext;
                $destino  = $_SERVER['DOCUMENT_ROOT'] . '/LibraFlow/catalogo/capas/' . $nomeCapa;
                if (!is_dir(dirname($destino))) mkdir(dirname($destino), 0755, true);

                if (move_uploaded_file($_FILES['capa']['tmp_name'], $destino)) {
                    if ($livro['capa']) {
                        $old = $_SERVER['DOCUMENT_ROOT'] . '/LibraFlow/catalogo/capas/' . $livro['capa'];
                        if (file_exists($old)) unlink($old);
                    }
                    $capa = $nomeCapa;
                } else {
                    $erro = 'Erro ao salvar imagem.';
                }
            }
        }

        if (empty($erro)) {
            $stmt = $conn->prepare("
                UPDATE livros SET titulo=?, autor=?, subtitulo=?, ano=?, isbn=?,
                descricao=?, quantidade=?, id_categoria=?, capa=? WHERE id=?
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
                    $capa,
                    $id
                ]);
                $stmt2 = $conn->prepare("SELECT * FROM livros WHERE id = ?");
                $stmt2->execute([$id]);
                $livro   = $stmt2->fetch();
                $sucesso = 'Livro atualizado com sucesso!';
            } catch (PDOException $e) {
                $erro = $e->getCode() == 23000 ? 'ISBN já cadastrado.' : 'Erro ao atualizar.';
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
    <title>Editar Livro | LibraFlow Admin</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400..700;1,400..700&family=Source+Sans+3:ital,wght@0,200..900;1,200..900&display=swap" rel="stylesheet">
</head>
<body>
    <aside>
        <div class="logo-aside"><span>LibraFlow</span></div>
        <ul>
            <li><a href="Admin.php">🏠 Início</a></li>
            <li><a href="listar_livros.php" class="ativo">📚 Livros</a></li>
            <li><a href="cadastrar_livro.php">➕ Cadastrar Livro</a></li>
            <li><a href="usuarios.php">👥 Usuários</a></li>
            <li><a href="emprestimos.php">📋 Empréstimos</a></li>
            <li><a href="visitas.php">Visitas</a></li>
            <div class="sidebar-down">
                <li><a href="/LibraFlow/cadastros_e_logins/logout/logout.php">🚪 Sair</a></li>
            </div>
        </ul>
    </aside>
    <nav>
        <span style="font-family:'Lora',serif;font-size:2rem;color:#283618;">Editar Livro</span>
        <div class="right">
            <span style="font-size:1.4rem;color:#606C38;">👤 <?= htmlspecialchars($_SESSION['usuario_nome']) ?></span>
        </div>
    </nav>
    <header>
        <h1>Editar Livro</h1>
        <p>Altere os dados do livro abaixo.</p>
    </header>
    <main>
        <div class="form-card">

            <?php if ($erro): ?>
                <div class="alerta alerta-erro"><?= htmlspecialchars($erro) ?></div>
            <?php endif; ?>
            <?php if ($sucesso): ?>
                <div class="alerta alerta-sucesso"><?= htmlspecialchars($sucesso) ?></div>
            <?php endif; ?>

            <form method="POST" action="" enctype="multipart/form-data">
                <div class="form-grid">
                    <div class="form-group full">
                        <label for="titulo">Título *</label>
                        <input type="text" id="titulo" name="titulo" value="<?= htmlspecialchars($livro['titulo']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="autor">Autor *</label>
                        <input type="text" id="autor" name="autor" value="<?= htmlspecialchars($livro['autor']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="subtitulo">Subtítulo</label>
                        <input type="text" id="subtitulo" name="subtitulo" value="<?= htmlspecialchars($livro['subtitulo'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="ano">Ano</label>
                        <input type="number" id="ano" name="ano" min="1000" max="2099" value="<?= $livro['ano'] ?? '' ?>">
                    </div>
                    <div class="form-group">
                        <label for="isbn">ISBN</label>
                        <input type="text" id="isbn" name="isbn" value="<?= htmlspecialchars($livro['isbn'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="quantidade">Quantidade</label>
                        <input type="number" id="quantidade" name="quantidade" min="1" value="<?= $livro['quantidade'] ?>">
                    </div>
                    <div class="form-group">
                        <label for="id_categoria">Categoria</label>
                        <select id="id_categoria" name="id_categoria">
                            <option value="">Selecione...</option>
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= $livro['id_categoria'] == $cat['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group full">
                        <label>Capa</label>
                        <?php if ($livro['capa']): ?>
                            <div style="display:flex;align-items:center;gap:1.5rem;margin-bottom:1rem;">
                                <img src="/LibraFlow/catalogo/capas/<?= htmlspecialchars($livro['capa']) ?>"
                                     style="width:8rem;height:11rem;object-fit:cover;border-radius:0.6rem;border:1px solid #ddd;">
                                <span style="font-size:1.2rem;color:#888;">Capa atual — envie uma nova para substituir</span>
                            </div>
                        <?php endif; ?>
                        <div class="upload-area">
                            <input type="file" name="capa" id="inputCapa" accept=".jpg,.jpeg,.png,.webp">
                            <p>📁 Clique para selecionar nova imagem<br><small>JPG, PNG ou WEBP — máx. 2MB</small></p>
                            <img id="previewCapa" class="preview-capa" src="" alt="Preview">
                        </div>
                    </div>
                    <div class="form-group full">
                        <label for="descricao">Descrição</label>
                        <textarea id="descricao" name="descricao"><?= htmlspecialchars($livro['descricao'] ?? '') ?></textarea>
                    </div>
                </div>

                <button type="submit" class="btn-salvar">Salvar alterações</button>
                <a href="listar_livros.php" class="voltar" style="margin-left:2rem;">← Voltar</a>
            </form>
        </div>
    </main>

    <div class="theme-toggle-wrapper">
        <button id="themeToggle" class="theme-toggle-btn" aria-label="Alternar tema claro/escuro">
            <span id="themeIcon">🌙</span>
        </button>
    </div>

    <script>
        document.getElementById('inputCapa').addEventListener('change', function () {
            const file = this.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = e => {
                const img = document.getElementById('previewCapa');
                img.src = e.target.result;
                img.style.display = 'block';
            };
            reader.readAsDataURL(file);
        });
    </script>
    <script src="darkmode.js"></script>
</body>
</html>
