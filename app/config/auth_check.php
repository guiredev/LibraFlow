<?php
/*
 * MAPA RAPIDO DO ARQUIVO
 * Local: app/config/auth_check.php
 * Funcao: Middleware de protecao: inicia sessao, tenta restaurar cookie de login e bloqueia paginas sem usuario logado.
 */
// app/config/auth_check.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/conexao.php';
require_once __DIR__ . '/auth.php';

libraflowRestoreSessionFromRememberCookie($conn);

if (!isset($_SESSION['usuario_id'])) {
    header('Location: /LibraFlow/public/auth/login/login.php');
    exit;
}
