<?php
$host = 'localhost';
$banco = 'libra_flow';
$usuario = 'root';
$senha = '';

try {
    $conn = new PDO(
        "mysql:host=$host;dbname=$banco;charset=utf8mb4",
        $usuario,
        $senha,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    die("Erro na conexão: " . $e->getMessage());
}

$titulo    = $_POST['titulo']    ?? '';
$subtitulo = $_POST['subtitulo'] ?? '';
$autor     = $_POST['autor']     ?? '';
$ano       = $_POST['ano']       ?? '';
$descricao = $_POST['descricao'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sql = "INSERT INTO livros (titulo, subtitulo, autor, ano, descricao)
            VALUES (?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->execute([$titulo, $subtitulo, $autor, $ano, $descricao]);

    echo "Livro cadastrado com sucesso!";
}
?>