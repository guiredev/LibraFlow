<?php
$conn = new PDO("firebird:dbname=C:\banco\livros.fdb", "SYSDBA", "masterkey");


// CADASTRA OS LIVROS NO PHP:
include 'conexao.php';

$titulo = $_POST['titulo'];
$autor = $_POST['autor'];
$ano = $_POST['ano'];
$descricao = $_POST['descricao'];
$subtitulo = $_POST['subtitulo']

$sql = "INSERT INTO livros (titulo, autor, ano, descricao)
        VALUES (?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
$stmt->execute([$titulo, $autor, $ano, $descricao]);

echo "Livro cadastrado com sucesso!";

?>