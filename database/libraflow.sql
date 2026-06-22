-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 08/06/2026 às 22:49
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `libraflow`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `categorias`
--

CREATE TABLE `categorias` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `descricao` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `categorias`
--

INSERT INTO `categorias` (`id`, `nome`, `descricao`) VALUES
(1, 'Romance', 'Ficção romântica e dramática'),
(2, 'Fantasia', 'Mundos fantásticos e criaturas mágicas'),
(3, 'Ciência', 'Livros científicos e técnicos'),
(4, 'História', 'Livros históricos e biografias'),
(5, 'Tecnologia', 'Programação, TI e inovação'),
(6, 'Literatura', 'Clássicos e literatura brasileira'),
(7, 'Infantojuvenil', 'Livros para crianças e jovens');

-- --------------------------------------------------------

--
-- Estrutura para tabela `emprestimos`
--

CREATE TABLE `emprestimos` (
  `id` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_livro` int(11) NOT NULL,
  `data_emprestimo` date NOT NULL,
  `data_prevista_devolucao` date DEFAULT NULL,
  `data_devolucao` date DEFAULT NULL,
  `status` char(1) NOT NULL DEFAULT 'A',
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `emprestimos`
--

INSERT INTO `emprestimos` (`id`, `id_usuario`, `id_livro`, `data_emprestimo`, `data_prevista_devolucao`, `data_devolucao`, `status`, `criado_em`) VALUES
(1, 3, 1, '2026-06-05', '2026-06-19', NULL, 'A', '2026-06-05 20:03:39');

-- --------------------------------------------------------

--
-- Estrutura para tabela `livros`
--

CREATE TABLE `livros` (
  `id` int(11) NOT NULL,
  `titulo` varchar(200) NOT NULL,
  `autor` varchar(150) NOT NULL,
  `subtitulo` varchar(200) DEFAULT NULL,
  `ano` smallint(6) DEFAULT NULL,
  `descricao` text DEFAULT NULL,
  `isbn` varchar(20) DEFAULT NULL,
  `capa` varchar(255) DEFAULT NULL,
  `quantidade` int(11) NOT NULL DEFAULT 1,
  `id_categoria` int(11) DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp()
) ;

--
-- Despejando dados para a tabela `livros`
--

INSERT INTO `livros` (`id`, `titulo`, `autor`, `subtitulo`, `ano`, `descricao`, `isbn`, `capa`, `quantidade`, `id_categoria`, `criado_em`) VALUES
(1, 'A Pedra Filosofal', 'J.K. Rowling', NULL, 1997, 'acompanha um garoto órfão que descobre ser um bruxo aos 11 anos. Convidado a estudar na Escola de Magia e Bruxaria de Hogwarts, ele embarca em um mundo mágico onde faz grandes amigos e deve impedir que o Lorde das Trevas roube um artefato que concede a imortalidade', '9780606323451', 'capa_6a22fd05d50da.jpg', 19, 2, '2026-06-05 13:44:53');

-- --------------------------------------------------------

--
-- Estrutura para tabela `recuperacao_senha`
--

CREATE TABLE `recuperacao_senha` (
  `id` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expira_em` datetime NOT NULL,
  `usado` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `recuperacao_senha`
--

INSERT INTO `recuperacao_senha` (`id`, `id_usuario`, `token`, `expira_em`, `usado`) VALUES
(4, 3, 'f48a6e19dee5d37be769933ac15b015c20c74e374d422285d38ae1b659c8ce6f', '2026-05-30 17:29:22', 0);

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `rm` varchar(30) DEFAULT NULL,
  `endereco` varchar(255) DEFAULT NULL,
  `idade` int(11) DEFAULT NULL,
  `senha` varchar(255) NOT NULL,
  `tipo` char(1) NOT NULL DEFAULT 'A' COMMENT 'A=Aluno D=Admin',
  `criado_em` datetime NOT NULL DEFAULT current_timestamp()
) ;

--
-- Despejando dados para a tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `nome`, `email`, `telefone`, `rm`, `endereco`, `idade`, `senha`, `tipo`, `criado_em`) VALUES
(1, 'Administrador', 'admin@libraflow.com', NULL, NULL, NULL, NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'D', '2026-05-30 09:19:55'),
(2, 'Guilherme', 'teste@gmail.com', NULL, NULL, NULL, NULL, '$2y$10$OyivTF0oCQc8qylTWw0FseGW.2K0sp4PQs4fwatFJu12O6V2q5GBS', 'D', '2026-05-30 09:47:35'),
(3, 'Guilherme Araujo', 'garaujo0192@gmail.com', NULL, NULL, NULL, NULL, '$2y$10$CJFg8vg57sO13Ml2b/1igOXLdxV1mymt0WRKowmziy4kwj6hpKoEO', 'A', '2026-05-30 11:15:05');

-- --------------------------------------------------------

--
-- Estrutura para tabela `visitas_biblioteca`
--

CREATE TABLE `visitas_biblioteca` (
  `id` int(11) NOT NULL,
  `data_registro` date NOT NULL,
  `periodo` varchar(20) NOT NULL,
  `quantidade` int(11) NOT NULL DEFAULT 0,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `visitas_biblioteca`
--

INSERT INTO `visitas_biblioteca` (`id`, `data_registro`, `periodo`, `quantidade`, `criado_em`, `atualizado_em`) VALUES
(1, '2026-06-08', 'Manha', 10, '2026-06-08 20:42:55', '2026-06-08 20:42:55'),
(2, '2026-06-08', 'Tarde', 10, '2026-06-08 20:42:55', '2026-06-08 20:42:55'),
(3, '2026-06-08', 'Noite', 10, '2026-06-08 20:42:55', '2026-06-08 20:42:55'),
(4, '2026-06-12', 'Manha', 20, '2026-06-08 20:43:04', '2026-06-08 20:43:39'),
(5, '2026-06-12', 'Tarde', 0, '2026-06-08 20:43:04', '2026-06-08 20:43:39'),
(6, '2026-06-12', 'Noite', 50, '2026-06-08 20:43:04', '2026-06-08 20:43:39');

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nome` (`nome`);

--
-- Índices de tabela `emprestimos`
--
ALTER TABLE `emprestimos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_emprestimos_usuario_status` (`id_usuario`,`status`),
  ADD KEY `idx_emprestimos_livro_status` (`id_livro`,`status`),
  ADD KEY `idx_emprestimos_status_prevista` (`status`,`data_prevista_devolucao`);

--
-- Índices de tabela `livros`
--
ALTER TABLE `livros`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `isbn` (`isbn`),
  ADD KEY `id_categoria` (`id_categoria`);

--
-- Índices de tabela `recuperacao_senha`
--
ALTER TABLE `recuperacao_senha`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Índices de tabela `visitas_biblioteca`
--
ALTER TABLE `visitas_biblioteca`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_visitas_data_periodo` (`data_registro`,`periodo`),
  ADD KEY `idx_visitas_data` (`data_registro`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de tabela `emprestimos`
--
ALTER TABLE `emprestimos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `livros`
--
ALTER TABLE `livros`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `recuperacao_senha`
--
ALTER TABLE `recuperacao_senha`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `visitas_biblioteca`
--
ALTER TABLE `visitas_biblioteca`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `emprestimos`
--
ALTER TABLE `emprestimos`
  ADD CONSTRAINT `fk_emprestimos_livro` FOREIGN KEY (`id_livro`) REFERENCES `livros` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_emprestimos_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `livros`
--
ALTER TABLE `livros`
  ADD CONSTRAINT `livros_ibfk_1` FOREIGN KEY (`id_categoria`) REFERENCES `categorias` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `recuperacao_senha`
--
ALTER TABLE `recuperacao_senha`
  ADD CONSTRAINT `recuperacao_senha_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
