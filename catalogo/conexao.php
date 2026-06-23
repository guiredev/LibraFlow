<?php
$host = "localhost";
$usuario = "root";
$senha = "";
$banco = "libraflow";

$conn = mysqli_connect($host, $usuario, $senha, $banco);

if (!$conn) {
    die("Erro ao conectar: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8mb4");
?>