<?php
$host = getenv('LIBRAFLOW_DB_HOST') ?: 'localhost';
$banco = getenv('LIBRAFLOW_DB_NAME') ?: 'libraflow';
$usuario = getenv('LIBRAFLOW_DB_USER') ?: 'root';
$senha = getenv('LIBRAFLOW_DB_PASS') ?: '';

try {
    $conn = new PDO(
        "mysql:host={$host};dbname={$banco};charset=utf8mb4",
        $usuario,
        $senha,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
    $pdo = $conn;
} catch (PDOException $e) {
    error_log('Erro na conexao com banco LibraFlow: ' . $e->getMessage());
    die('Erro ao conectar ao banco de dados.');
}
