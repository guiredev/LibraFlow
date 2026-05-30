<?php
// cadastros_e_logins/configs/auth_check.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario_id'])) {
    header('Location: /LibraFlow/cadastros_e_logins/login/arquivos/login.php');
    exit;
}
