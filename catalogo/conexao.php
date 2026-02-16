<?php 
include('conexao.php'); // Importa a conexão

// 1. Pegamos o que o usuário digitou na busca (se houver)
$busca = isset($_GET['txtBusca']) ? $_GET['txtBusca'] : '';

// 2. Criamos a "Pergunta" (SQL) para o banco
// Se a busca estiver vazia, ele traz tudo. Se não, filtra por título.
$sql = "SELECT * FROM livros WHERE titulo LIKE '%$busca%'";

// 3. Executamos a pergunta
$resultado = mysqli_query($conn, $sql);
?>

<?php
$host = "localhost";
$usuario = "root";
$senha = "";
$banco = "libra_flow";

// mysqli_connect é a função que abre a conexão
$conn = mysqli_connect($host, $usuario, $senha, $banco);

// Verificamos se a conexão deu certo
if (!$conn) {
    die("Erro ao conectar: " . mysqli_connect_error());
}
?>