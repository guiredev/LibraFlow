<?php
/*
 * MAPA RAPIDO DO ARQUIVO
 * Local: app/config/auth.php
 * Funcao: Funcoes compartilhadas de autenticacao: iniciar sessao, lembrar login por cookie e redirecionar por tipo de usuario.
 */
// app/config/auth.php

const LIBRAFLOW_REMEMBER_COOKIE = 'libraflow_remember';
const LIBRAFLOW_REMEMBER_DAYS = 30;

function libraflowEnsureAuthTokenTable(PDO $conn): void
{
    static $checked = false;

    if ($checked) {
        return;
    }

    $conn->exec("
        CREATE TABLE IF NOT EXISTS login_tokens (
            id INT AUTO_INCREMENT PRIMARY KEY,
            id_usuario INT NOT NULL,
            selector CHAR(24) NOT NULL,
            token_hash CHAR(64) NOT NULL,
            expira_em DATETIME NOT NULL,
            criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            usado_em DATETIME NULL,
            UNIQUE KEY uk_login_tokens_selector (selector),
            KEY idx_login_tokens_usuario (id_usuario),
            KEY idx_login_tokens_expira (expira_em),
            CONSTRAINT fk_login_tokens_usuario
                FOREIGN KEY (id_usuario) REFERENCES usuarios (id)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");

    $checked = true;
}

function libraflowCookieOptions(int $expires): array
{
    return [
        'expires' => $expires,
        'path' => '/LibraFlow',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ];
}

function libraflowClearRememberCookie(): void
{
    setcookie(LIBRAFLOW_REMEMBER_COOKIE, '', libraflowCookieOptions(time() - 3600));
    unset($_COOKIE[LIBRAFLOW_REMEMBER_COOKIE]);
}

function libraflowStartUserSession(array $usuario): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    session_regenerate_id(true);

    $_SESSION['usuario_id'] = $usuario['id'];
    $_SESSION['usuario_nome'] = $usuario['nome'];
    $_SESSION['usuario_email'] = $usuario['email'];
    $_SESSION['usuario_tipo'] = $usuario['tipo'];
}

function libraflowRememberUser(PDO $conn, int $usuarioId): void
{
    libraflowEnsureAuthTokenTable($conn);

    $selector = bin2hex(random_bytes(12));
    $token = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $token);
    $expiresAt = new DateTimeImmutable('+' . LIBRAFLOW_REMEMBER_DAYS . ' days');

    $stmt = $conn->prepare("
        INSERT INTO login_tokens (id_usuario, selector, token_hash, expira_em)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([
        $usuarioId,
        $selector,
        $tokenHash,
        $expiresAt->format('Y-m-d H:i:s'),
    ]);

    setcookie(
        LIBRAFLOW_REMEMBER_COOKIE,
        $selector . ':' . $token,
        libraflowCookieOptions($expiresAt->getTimestamp())
    );
}

function libraflowForgetRememberToken(PDO $conn): void
{
    libraflowEnsureAuthTokenTable($conn);

    $cookie = $_COOKIE[LIBRAFLOW_REMEMBER_COOKIE] ?? '';
    if ($cookie !== '' && strpos($cookie, ':') !== false) {
        [$selector] = explode(':', $cookie, 2);

        if (preg_match('/^[a-f0-9]{24}$/', $selector)) {
            $stmt = $conn->prepare("DELETE FROM login_tokens WHERE selector = ?");
            $stmt->execute([$selector]);
        }
    }

    libraflowClearRememberCookie();
}

function libraflowRestoreSessionFromRememberCookie(PDO $conn): bool
{
    if (isset($_SESSION['usuario_id'])) {
        return true;
    }

    $cookie = $_COOKIE[LIBRAFLOW_REMEMBER_COOKIE] ?? '';
    if ($cookie === '' || strpos($cookie, ':') === false) {
        return false;
    }

    [$selector, $token] = explode(':', $cookie, 2);
    if (!preg_match('/^[a-f0-9]{24}$/', $selector) || !preg_match('/^[a-f0-9]{64}$/', $token)) {
        libraflowClearRememberCookie();
        return false;
    }

    libraflowEnsureAuthTokenTable($conn);

    $stmt = $conn->prepare("
        SELECT lt.id, lt.id_usuario, lt.token_hash, lt.expira_em,
               u.nome, u.email, u.tipo
        FROM login_tokens lt
        JOIN usuarios u ON u.id = lt.id_usuario
        WHERE lt.selector = ?
        LIMIT 1
    ");
    $stmt->execute([$selector]);
    $registro = $stmt->fetch();

    if (!$registro) {
        libraflowClearRememberCookie();
        return false;
    }

    if (strtotime($registro['expira_em']) < time()) {
        $stmt = $conn->prepare("DELETE FROM login_tokens WHERE id = ?");
        $stmt->execute([$registro['id']]);
        libraflowClearRememberCookie();
        return false;
    }

    if (!hash_equals($registro['token_hash'], hash('sha256', $token))) {
        $stmt = $conn->prepare("DELETE FROM login_tokens WHERE id_usuario = ?");
        $stmt->execute([$registro['id_usuario']]);
        libraflowClearRememberCookie();
        return false;
    }

    libraflowStartUserSession([
        'id' => $registro['id_usuario'],
        'nome' => $registro['nome'],
        'email' => $registro['email'],
        'tipo' => $registro['tipo'],
    ]);

    $stmt = $conn->prepare("DELETE FROM login_tokens WHERE id = ?");
    $stmt->execute([$registro['id']]);
    libraflowRememberUser($conn, (int) $registro['id_usuario']);

    return true;
}

function libraflowRedirectByUserType(string $tipo): void
{
    if ($tipo === 'D') {
        header('Location: /LibraFlow/public/admin/Admin.php');
    } else {
        header('Location: /LibraFlow/public/usuario/index.php');
    }
    exit;
}
