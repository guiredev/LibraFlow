<?php
/* Inicialização idempotente do schema para instalações novas do LibraFlow. */

function libraflowEnsureDatabaseTables(PDO $conn): void
{
    static $checked = false;
    if ($checked) {
        return;
    }

    $conn->exec("CREATE TABLE IF NOT EXISTS categorias (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(100) NOT NULL,
        descricao VARCHAR(255) DEFAULT NULL,
        UNIQUE KEY uk_categorias_nome (nome)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

    $conn->exec("INSERT INTO categorias (nome, descricao) VALUES
        ('Romance', 'Ficção romântica e dramática'),
        ('Fantasia', 'Mundos fantásticos e criaturas mágicas'),
        ('Ciência', 'Livros científicos e técnicos'),
        ('História', 'Livros históricos e biografias'),
        ('Tecnologia', 'Programação, TI e inovação'),
        ('Literatura', 'Clássicos e literatura brasileira'),
        ('Infantojuvenil', 'Livros para crianças e jovens')
        ON DUPLICATE KEY UPDATE nome = VALUES(nome)");

    $conn->exec("CREATE TABLE IF NOT EXISTS usuarios (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(100) NOT NULL,
        email VARCHAR(150) NOT NULL,
        telefone VARCHAR(20) DEFAULT NULL,
        rm VARCHAR(30) DEFAULT NULL,
        endereco VARCHAR(255) DEFAULT NULL,
        idade INT DEFAULT NULL,
        senha VARCHAR(255) NOT NULL,
        tipo CHAR(1) NOT NULL DEFAULT 'A' COMMENT 'A=Aluno D=Admin',
        criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uk_usuarios_email (email)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

    $conn->exec("CREATE TABLE IF NOT EXISTS livros (
        id INT AUTO_INCREMENT PRIMARY KEY,
        titulo VARCHAR(200) NOT NULL,
        autor VARCHAR(150) NOT NULL,
        subtitulo VARCHAR(200) DEFAULT NULL,
        ano SMALLINT DEFAULT NULL,
        descricao TEXT DEFAULT NULL,
        isbn VARCHAR(20) DEFAULT NULL,
        capa VARCHAR(255) DEFAULT NULL,
        quantidade INT NOT NULL DEFAULT 1,
        id_categoria INT DEFAULT NULL,
        criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        KEY idx_livros_categoria (id_categoria),
        CONSTRAINT fk_livros_categoria FOREIGN KEY (id_categoria) REFERENCES categorias(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

    $conn->exec("CREATE TABLE IF NOT EXISTS emprestimos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        id_usuario INT NOT NULL,
        id_livro INT NOT NULL,
        data_emprestimo DATE NOT NULL,
        data_prevista_devolucao DATE DEFAULT NULL,
        data_devolucao DATE DEFAULT NULL,
        status CHAR(1) NOT NULL DEFAULT 'A',
        criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        KEY idx_emprestimos_usuario_status (id_usuario, status),
        KEY idx_emprestimos_livro_status (id_livro, status),
        KEY idx_emprestimos_status_prevista (status, data_prevista_devolucao),
        CONSTRAINT fk_emprestimos_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id) ON DELETE CASCADE,
        CONSTRAINT fk_emprestimos_livro FOREIGN KEY (id_livro) REFERENCES livros(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

    $conn->exec("CREATE TABLE IF NOT EXISTS recuperacao_senha (
        id INT AUTO_INCREMENT PRIMARY KEY,
        id_usuario INT NOT NULL,
        token VARCHAR(64) NOT NULL,
        expira_em DATETIME NOT NULL,
        usado TINYINT(1) NOT NULL DEFAULT 0,
        UNIQUE KEY uk_recuperacao_token (token),
        KEY idx_recuperacao_usuario (id_usuario),
        CONSTRAINT fk_recuperacao_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

    $conn->exec("CREATE TABLE IF NOT EXISTS visitas_biblioteca (
        id INT AUTO_INCREMENT PRIMARY KEY,
        data_registro DATE NOT NULL,
        periodo VARCHAR(20) NOT NULL,
        quantidade INT NOT NULL DEFAULT 0,
        criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uk_visitas_data_periodo (data_registro, periodo),
        KEY idx_visitas_data (data_registro)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

    $conn->exec("CREATE TABLE IF NOT EXISTS registros_visitas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        id_usuario INT DEFAULT NULL,
        nome VARCHAR(100) NOT NULL,
        rm VARCHAR(30) NOT NULL,
        periodo ENUM('Manha','Tarde','Noite') NOT NULL,
        serie VARCHAR(20) NOT NULL,
        ano SMALLINT NOT NULL,
        motivo ENUM('Estudo','Pesquisa','Leitura','Empréstimo','Devolução','Trabalho escolar','Uso do espaço','Outro') NOT NULL,
        data_visita DATE NOT NULL,
        hora_visita TIME NOT NULL,
        criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        KEY idx_registros_visitas_data (data_visita),
        KEY idx_registros_visitas_rm (rm),
        KEY idx_registros_visitas_usuario (id_usuario),
        KEY idx_registros_visitas_periodo (periodo),
        CONSTRAINT fk_registros_visitas_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

    $conn->exec("CREATE TABLE IF NOT EXISTS login_tokens (
        id INT AUTO_INCREMENT PRIMARY KEY,
        id_usuario INT NOT NULL,
        selector CHAR(24) NOT NULL,
        token_hash CHAR(64) NOT NULL,
        expira_em DATETIME NOT NULL,
        criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        usado_em DATETIME DEFAULT NULL,
        UNIQUE KEY uk_login_tokens_selector (selector),
        KEY idx_login_tokens_usuario (id_usuario),
        KEY idx_login_tokens_expira (expira_em),
        CONSTRAINT fk_login_tokens_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

    $checked = true;
}
