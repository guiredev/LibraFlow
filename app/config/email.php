<?php
/*
 * MAPA RAPIDO DO ARQUIVO
 * Local: app/config/email.php
 * Funcao: Configuracao e envio de emails com PHPMailer, incluindo link de recuperacao de senha.
 */
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

function libraflowIsLocalDevelopment(): bool
{
    $serverName = $_SERVER['SERVER_NAME'] ?? '';
    $remoteAddr = $_SERVER['REMOTE_ADDR'] ?? '';

    return in_array($serverName, ['localhost', '127.0.0.1', '::1'], true)
        || in_array($remoteAddr, ['127.0.0.1', '::1'], true)
        || PHP_SAPI === 'cli';
}

function libraflowMailConfigError(array $config): string
{
    $missing = [];

    foreach (['host', 'username', 'password', 'from'] as $key) {
        if (trim((string) ($config[$key] ?? '')) === '') {
            $missing[] = $key;
        }
    }

    if ($missing !== []) {
        return 'Configuracao SMTP incompleta: ' . implode(', ', $missing);
    }

    return '';
}

function libraflowValidateMailConfig(array $config): void
{
    $error = libraflowMailConfigError($config);

    if ($error !== '') {
        throw new RuntimeException($error);
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
        . '/public/auth/senha/redefinir-senha.php?token='
        . rawurlencode($token);
}

function libraflowSendPasswordResetEmail(string $email, string $name, string $link): void
{
    $safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    $safeLink = htmlspecialchars($link, ENT_QUOTES, 'UTF-8');

    $htmlBody = "
        <div style='font-family: Arial, sans-serif; max-width: 560px; margin: auto; color: #283618; line-height: 1.5;'>
            <div style='padding: 20px; background: #F5F5F0; border: 1px solid #BC6C25; border-radius: 12px;'>
                <h2 style='color: #283618; margin-top: 0;'>Recuperacao de senha - LibraFlow</h2>

                <p>Ola, <strong>{$safeName}</strong>.</p>

                <p>
                    Recebemos uma solicitacao para redefinir a senha da sua conta no LibraFlow.
                    Para criar uma nova senha, clique no botao abaixo.
                </p>

                <p style='margin: 24px 0;'>
                    <a href='{$safeLink}'
                       style='display:inline-block; padding:12px 20px; background:#DDA15E; color:#FEFAE0;
                              border-radius:8px; text-decoration:none; font-weight:bold;'>
                        Redefinir minha senha
                    </a>
                </p>

                <p>Este link expira em <strong>1 hora</strong>.</p>

                <p style='font-size:13px; color:#606C38;'>
                    Se o botao nao funcionar, copie e cole este endereco no navegador:<br>
                    <a href='{$safeLink}' style='color:#BC6C25; word-break: break-all;'>{$safeLink}</a>
                </p>

                <p style='font-size:13px; color:#606C38;'>
                    Se voce nao solicitou essa alteracao, ignore este e-mail. Sua senha atual continuara a mesma.
                </p>
            </div>
        </div>
    ";

    $textBody = "Ola, {$name}.\n\n"
        . "Recebemos uma solicitacao para redefinir a senha da sua conta no LibraFlow.\n"
        . "Acesse o link abaixo para criar uma nova senha. O link expira em 1 hora:\n\n"
        . "{$link}\n\n"
        . "Se voce nao solicitou essa alteracao, ignore este e-mail.";

    libraflowSendEmail($email, $name, 'Recuperacao de senha - LibraFlow', $htmlBody, $textBody);
}
