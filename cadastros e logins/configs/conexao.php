<?php
// cadastros e logins/configs/conexao.php

try {
    $conn = new PDO(
        'firebird:dbname=C:\\xampp\\htdocs\\LibraFlow\\Banco\\LIBRAFLOW-DATABASE.FDB;charset=UTF8',
        'SYSDBA',
        'masterkey',
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    die("Erro na conexão: " . $e->getMessage());
}
