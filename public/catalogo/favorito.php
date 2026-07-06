<?php
/*
 * MAPA RAPIDO DO ARQUIVO
 * Local: public/catalogo/favorito.php
 * Funcao: Alterna livro favorito do usuario logado.
 */

require $_SERVER['DOCUMENT_ROOT'] . '/LibraFlow/app/config/auth_check.php';
require $_SERVER['DOCUMENT_ROOT'] . '/LibraFlow/app/config/conexao.php';
require $_SERVER['DOCUMENT_ROOT'] . '/LibraFlow/app/config/user_features.php';

libraflowEnsureUserFeatureTables($conn);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !libraflowValidateCsrfToken($_POST['csrf_token'] ?? null)) {
    header('Location: catalogo.php');
    exit;
}

$idLivro = (int) ($_POST['id_livro'] ?? 0);
$redirect = $_POST['redirect'] ?? 'catalogo.php';

if (!is_string($redirect) || preg_match('/^https?:\/\//i', $redirect) || str_starts_with($redirect, '//')) {
    $redirect = 'catalogo.php';
}

if ($idLivro <= 0) {
    header('Location: ' . $redirect);
    exit;
}

try {
    $stmt = $conn->prepare("SELECT id FROM favoritos_livros WHERE id_usuario = ? AND id_livro = ?");
    $stmt->execute([$_SESSION['usuario_id'], $idLivro]);

    if ($stmt->fetch()) {
        $stmt = $conn->prepare("DELETE FROM favoritos_livros WHERE id_usuario = ? AND id_livro = ?");
        $stmt->execute([$_SESSION['usuario_id'], $idLivro]);
    } else {
        $stmt = $conn->prepare("INSERT IGNORE INTO favoritos_livros (id_usuario, id_livro) VALUES (?, ?)");
        $stmt->execute([$_SESSION['usuario_id'], $idLivro]);
    }
} catch (PDOException $e) {
    // Mantem o fluxo do usuario mesmo se a acao falhar.
}

header('Location: ' . $redirect);
exit;
