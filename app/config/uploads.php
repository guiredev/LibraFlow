<?php
/*
 * MAPA RAPIDO DO ARQUIVO
 * Local: app/config/uploads.php
 * Funcao: Validacoes compartilhadas para uploads do sistema.
 */

function libraflowValidateUploadedImage(array $file, int $maxBytes = 2097152): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return [false, 'Upload invalido.'];
    }

    if (($file['size'] ?? 0) > $maxBytes) {
        return [false, 'Imagem deve ter no maximo 2MB.'];
    }

    $tmpName = $file['tmp_name'] ?? '';
    if (!is_uploaded_file($tmpName)) {
        return [false, 'Arquivo de upload invalido.'];
    }

    $mime = '';
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo) {
            $mime = finfo_file($finfo, $tmpName) ?: '';
            finfo_close($finfo);
        }
    }

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    if (!isset($allowed[$mime]) || @getimagesize($tmpName) === false) {
        return [false, 'Formato invalido. Use JPG, PNG ou WEBP.'];
    }

    return [true, $allowed[$mime]];
}
