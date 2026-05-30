<?php
// cadastros_e_logins/logout/logout.php

session_start();
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

header('Location: /LibraFlow/cadastros_e_logins/login/arquivos/login.php');
exit;
