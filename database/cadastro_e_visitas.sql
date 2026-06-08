ALTER TABLE usuarios
    ADD COLUMN telefone VARCHAR(20) NULL AFTER email,
    ADD COLUMN rm VARCHAR(30) NULL AFTER telefone,
    ADD COLUMN endereco VARCHAR(255) NULL AFTER rm,
    ADD COLUMN idade INT NULL AFTER endereco;

CREATE TABLE IF NOT EXISTS visitas_biblioteca (
    id INT AUTO_INCREMENT PRIMARY KEY,
    data_registro DATE NOT NULL,
    periodo VARCHAR(20) NOT NULL,
    quantidade INT NOT NULL DEFAULT 0,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_visitas_data_periodo (data_registro, periodo),
    INDEX idx_visitas_data (data_registro)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
