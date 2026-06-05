CREATE TABLE IF NOT EXISTS emprestimos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    id_livro INT NOT NULL,
    data_emprestimo DATE NOT NULL,
    data_prevista_devolucao DATE NULL,
    data_devolucao DATE NULL,
    status CHAR(1) NOT NULL DEFAULT 'A',
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_emprestimos_usuario
        FOREIGN KEY (id_usuario) REFERENCES usuarios(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_emprestimos_livro
        FOREIGN KEY (id_livro) REFERENCES livros(id)
        ON DELETE CASCADE,
    INDEX idx_emprestimos_usuario_status (id_usuario, status),
    INDEX idx_emprestimos_livro_status (id_livro, status),
    INDEX idx_emprestimos_status_prevista (status, data_prevista_devolucao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
