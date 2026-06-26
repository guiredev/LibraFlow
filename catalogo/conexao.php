<?php
$host = getenv('LIBRAFLOW_DB_HOST') ?: 'localhost';
$usuario = getenv('LIBRAFLOW_DB_USER') ?: 'root';
$senha = getenv('LIBRAFLOW_DB_PASS') ?: '';
$banco = getenv('LIBRAFLOW_DB_NAME') ?: 'libraflow';

$conn = mysqli_connect($host, $usuario, $senha, $banco);

if (!$conn) {
    error_log('Erro na conexao mysqli LibraFlow: ' . mysqli_connect_error());
    die('Erro ao conectar ao banco de dados.');
}

mysqli_set_charset($conn, 'utf8mb4');
