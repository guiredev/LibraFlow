<?php

use PHPMailer\PHPMailer\PHPMailer;

if (!class_exists(PHPMailer::class)) {
    require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
}

function libraflowEmailLocalConfig(): array
{
    $localFile = __DIR__ . '/email.local.php';

    if (!is_file($localFile)) {
        return [];
    }

    $config = require $localFile;

    return is_array($config) ? $config : [];
}

function libraflowConfigValue(array $config, string $key, string $default = ''): string
{
    $envValue = getenv($key);

    if ($envValue !== false && $envValue !== '') {
        return (string) $envValue;
    }

    if (isset($config[$key]) && $config[$key] !== '') {
        return (string) $config[$key];
    }

    return $default;
}

function libraflowMailConfig(): array
{
    $localConfig = libraflowEmailLocalConfig();
    $username = libraflowConfigValue($localConfig, 'LIBRAFLOW_SMTP_USER');

    return [
        'app_url' => rtrim(libraflowConfigValue($localConfig, 'LIBRAFLOW_APP_URL', 'http://localhost/LibraFlow'), '/'),
        'host' => libraflowConfigValue($localConfig, 'LIBRAFLOW_SMTP_HOST', 'smtp.gmail.com'),
        'username' => $username,
        'password' => libraflowConfigValue($localConfig, 'LIBRAFLOW_SMTP_PASS'),
        'port' => (int) libraflowConfigValue($localConfig, 'LIBRAFLOW_SMTP_PORT', '587'),
        'secure' => libraflowConfigValue($localConfig, 'LIBRAFLOW_SMTP_SECURE', PHPMailer::ENCRYPTION_STARTTLS),
        'from' => libraflowConfigValue($localConfig, 'LIBRAFLOW_MAIL_FROM', $username),
        'from_name' => libraflowConfigValue($localConfig, 'LIBRAFLOW_MAIL_FROM_NAME', 'LibraFlow'),
        'debug' => libraflowConfigValue($localConfig, 'LIBRAFLOW_SMTP_DEBUG', '0') === '1',
    ];
}

function libraflowValidateMailConfig(array $config): void
{
    $missing = [];

    foreach (['host', 'username', 'password', 'from'] as $key) {
        if (trim((string) ($config[$key] ?? '')) === '') {
            $missing[] = $key;
        }
    }

    if ($missing !== []) {
        throw new RuntimeException('Configuracao SMTP incompleta: ' . implode(', ', $missing));
    }
}

function libraflowSendEmail(string $toEmail, string $toName, string $subject, string $htmlBody, string $textBody = ''): void
{
    $config = libraflowMailConfig();
    libraflowValidateMailConfig($config);

    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = $config['host'];
    $mail->SMTPAuth = true;
    $mail->Username = $config['username'];
    $mail->Password = $config['password'];
    $mail->SMTPSecure = $config['secure'];
    $mail->Port = $config['port'];
    $mail->CharSet = 'UTF-8';
    $mail->Timeout = 20;

    if ($config['debug']) {
        $mail->SMTPDebug = 2;
        $mail->Debugoutput = static function (string $message, int $level): void {
            error_log('[LibraFlow][smtp][' . $level . '] ' . $message);
        };
    }

    $mail->setFrom($config['from'], $config['from_name']);
    $mail->addAddress($toEmail, $toName);
    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body = $htmlBody;
    $mail->AltBody = $textBody !== '' ? $textBody : trim(strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $htmlBody)));
    $mail->send();
}

function libraflowBuildPasswordResetLink(string $token): string
{
    $config = libraflowMailConfig();

    return $config['app_url']
        . '/cadastros_e_logins/esqueceu_a_senha/redefinir-senha.php?token='
        . rawurlencode($token);
}

function libraflowSendPasswordResetEmail(string $email, string $name, string $link): void
{
    $safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    $safeLink = htmlspecialchars($link, ENT_QUOTES, 'UTF-8');

    $htmlBody = "
        <div style='font-family: Arial, sans-serif; max-width: 520px; margin: auto; color: #283618;'>
            <h2 style='color: #283618;'>Redefinicao de senha</h2>
            <p>Ola, <strong>{$safeName}</strong>!</p>
            <p>Recebemos uma solicitacao para redefinir a senha da sua conta no LibraFlow.</p>
            <p>O link abaixo expira em <strong>1 hora</strong>.</p>
            <p>
                <a href='{$safeLink}'
                   style='display:inline-block; padding:12px 20px; background:#DDA15E; color:#fff;
                          border-radius:8px; text-decoration:none; font-weight:bold;'>
                    Redefinir minha senha
                </a>
            </p>
            <p style='font-size:13px; color:#666;'>Se voce nao solicitou isso, ignore este e-mail.</p>
        </div>
    ";

    $textBody = "Acesse o link para redefinir sua senha: {$link} (valido por 1 hora)";

    libraflowSendEmail($email, $name, 'Redefinicao de senha - LibraFlow', $htmlBody, $textBody);
}
