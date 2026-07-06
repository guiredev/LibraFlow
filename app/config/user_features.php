<?php
/*
 * MAPA RAPIDO DO ARQUIVO
 * Local: app/config/user_features.php
 * Funcao: Recursos auxiliares do fluxo do usuario: favoritos, notificacoes e renovacoes.
 */

function libraflowEnsureUserFeatureTables(PDO $conn): void
{
    $conn->exec("
        CREATE TABLE IF NOT EXISTS favoritos_livros (
            id INT AUTO_INCREMENT PRIMARY KEY,
            id_usuario INT NOT NULL,
            id_livro INT NOT NULL,
            criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uk_favoritos_usuario_livro (id_usuario, id_livro),
            KEY idx_favoritos_usuario (id_usuario),
            KEY idx_favoritos_livro (id_livro),
            CONSTRAINT fk_favoritos_usuario
                FOREIGN KEY (id_usuario) REFERENCES usuarios(id)
                ON DELETE CASCADE,
            CONSTRAINT fk_favoritos_livro
                FOREIGN KEY (id_livro) REFERENCES livros(id)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");

    $conn->exec("
        CREATE TABLE IF NOT EXISTS notificacoes_usuario (
            id INT AUTO_INCREMENT PRIMARY KEY,
            id_usuario INT NOT NULL,
            titulo VARCHAR(160) NOT NULL,
            mensagem VARCHAR(255) NOT NULL,
            link VARCHAR(255) DEFAULT NULL,
            lida TINYINT(1) NOT NULL DEFAULT 0,
            criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_notificacoes_usuario_lida (id_usuario, lida, criado_em),
            CONSTRAINT fk_notificacoes_usuario
                FOREIGN KEY (id_usuario) REFERENCES usuarios(id)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");

    $conn->exec("
        CREATE TABLE IF NOT EXISTS renovacoes_emprestimos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            id_emprestimo INT NOT NULL,
            id_usuario INT NOT NULL,
            status CHAR(1) NOT NULL DEFAULT 'P',
            dias_solicitados INT NOT NULL DEFAULT 7,
            observacao VARCHAR(255) DEFAULT NULL,
            criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            analisado_em DATETIME DEFAULT NULL,
            id_admin INT DEFAULT NULL,
            KEY idx_renovacoes_emprestimo_status (id_emprestimo, status),
            KEY idx_renovacoes_status (status, criado_em),
            KEY idx_renovacoes_usuario (id_usuario, criado_em),
            CONSTRAINT fk_renovacoes_emprestimo
                FOREIGN KEY (id_emprestimo) REFERENCES emprestimos(id)
                ON DELETE CASCADE,
            CONSTRAINT fk_renovacoes_usuario
                FOREIGN KEY (id_usuario) REFERENCES usuarios(id)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");
}

function libraflowCriarNotificacao(PDO $conn, int $idUsuario, string $titulo, string $mensagem, ?string $link = null): void
{
    $stmt = $conn->prepare("
        INSERT INTO notificacoes_usuario (id_usuario, titulo, mensagem, link)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$idUsuario, $titulo, $mensagem, $link]);
}

function libraflowContarNotificacoesNaoLidas(PDO $conn, int $idUsuario): int
{
    $stmt = $conn->prepare("SELECT COUNT(*) FROM notificacoes_usuario WHERE id_usuario = ? AND lida = 0");
    $stmt->execute([$idUsuario]);
    return (int) $stmt->fetchColumn();
}

function libraflowFavoritosDoUsuario(PDO $conn, int $idUsuario): array
{
    $stmt = $conn->prepare("SELECT id_livro FROM favoritos_livros WHERE id_usuario = ?");
    $stmt->execute([$idUsuario]);
    return array_flip($stmt->fetchAll(PDO::FETCH_COLUMN));
}

function libraflowRenovacoesPendentesDoUsuario(PDO $conn, int $idUsuario): array
{
    $stmt = $conn->prepare("
        SELECT id_emprestimo
        FROM renovacoes_emprestimos
        WHERE id_usuario = ?
          AND status = 'P'
    ");
    $stmt->execute([$idUsuario]);
    return array_flip($stmt->fetchAll(PDO::FETCH_COLUMN));
}
