<?php
/* QR Code estático para o formulário público de visitas. */
require $_SERVER['DOCUMENT_ROOT'] . '/LibraFlow/app/config/auth_check.php';

if ($_SESSION['usuario_tipo'] !== 'D') {
    header('Location: /LibraFlow/public/usuario/index.php');
    exit;
}

require $_SERVER['DOCUMENT_ROOT'] . '/LibraFlow/vendor/tecnickcom/tcpdf/tcpdf_barcodes_2d.php';

$configLocal = [];
$arquivoConfigLocal = $_SERVER['DOCUMENT_ROOT'] . '/LibraFlow/app/config/email.local.php';
if (is_file($arquivoConfigLocal)) {
    $configCarregada = require $arquivoConfigLocal;
    $configLocal = is_array($configCarregada) ? $configCarregada : [];
}
$baseUrl = getenv('LIBRAFLOW_APP_URL') ?: ($configLocal['LIBRAFLOW_APP_URL'] ?? 'http://localhost/LibraFlow');
$url = rtrim($baseUrl, '/') . '/public/visita/';
$svgQrCode = (new TCPDF2DBarcode($url, 'QRCODE,H'))->getBarcodeSVGcode(8, 8, '#283618');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>QR Code de visitas | LibraFlow</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .qr-card { max-width: 620px; margin: 4rem auto; text-align: center; }
        .qr-code { display: inline-block; max-width: 100%; padding: 1rem; background: #fff; }
        .qr-code svg { display: block; width: min(100%, 360px); height: auto; }
        .url { overflow-wrap: anywhere; background: var(--bg-page); padding: 1rem; border-radius: .8rem; }
        .acoes { display: flex; justify-content: center; gap: 1rem; flex-wrap: wrap; margin-top: 1.5rem; }
        @media print { .acoes { display: none; } .qr-card { margin: 0; } }
    </style>
</head>
<body>
    <main class="qr-card form-card">
        <h1>QR Code para registro de visitas</h1>
        <p>Imprima este código e deixe-o visível na biblioteca.</p>
        <div class="qr-code" role="img" aria-label="QR Code para <?= htmlspecialchars($url) ?>"><?= $svgQrCode ?></div>
        <p class="url"><?= htmlspecialchars($url) ?></p>
        <div class="acoes">
            <a class="btn-novo" href="visitas.php">Voltar às visitas</a>
            <button type="button" onclick="window.print()">Imprimir</button>
        </div>
    </main>
</body>
</html>
