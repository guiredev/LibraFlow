<?php
/*
 * MAPA RAPIDO DO ARQUIVO
 * Local: public/auth/logout.php
 * Funcao: Finaliza sessao, apaga cookie de lembrar login e volta para o login.
 */
// public/auth/logout.php

session_start();

$projectRoot = dirname(__DIR__, 2);

require_once $projectRoot . '/app/config/conexao.php';
require_once $projectRoot . '/app/config/auth.php';

libraflowForgetRememberToken($conn);

session_unset();
session_destroy();

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

header('Location: /LibraFlow/public/auth/login/login.php');
exit;
