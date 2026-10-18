-- ============================================================
-- SE JÁ TENS O BANCO INSTALADO, executa esta linha para
-- adicionar o perfil 'gerente':
-- ALTER TABLE utilizadores MODIFY perfil ENUM('admin','gerente','operador') DEFAULT 'operador';
-- ============================================================

-- ============================================================
-- FICHEIRO: banco_de_dados.sql
-- FUNÇÃO: Criar o banco de dados e todas as tabelas
-- COMO USAR: Abre o phpMyAdmin > clica em "SQL" > cola isto > Executar
-- ============================================================

-- 1. CRIAR O BANCO DE DADOS
CREATE DATABASE IF NOT EXISTS majo_sistema
    CHARACTER SET utf8
    COLLATE utf8_general_ci;

-- 2. SELECIONAR O BANCO DE DADOS
USE majo_sistema;

-- 3. TABELA DE UTILIZADORES (para login)
CREATE TABLE IF NOT EXISTS utilizadores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    perfil ENUM('admin', 'gerente', 'operador') DEFAULT 'operador',
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 4. TABELA DE CATEGORIAS
CREATE TABLE IF NOT EXISTS categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    descricao TEXT,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 5. TABELA DE PRODUTOS (principal)
CREATE TABLE IF NOT EXISTS produtos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(50) NOT NULL UNIQUE,
    nome VARCHAR(150) NOT NULL,
    descricao TEXT,
    categoria_id INT,
    preco_compra DECIMAL(10,2) DEFAULT 0.00,
    preco_venda DECIMAL(10,2) DEFAULT 0.00,
    margem_lucro DECIMAL(5,2) DEFAULT 0.00,
    quantidade_stock INT DEFAULT 0,
    stock_minimo INT DEFAULT 5,
    unidade_medida VARCHAR(20) DEFAULT 'un',
    imagem VARCHAR(255),
    codigo_barras VARCHAR(100),
    status ENUM('ativo', 'inativo') DEFAULT 'ativo',
    criado_por INT,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE SET NULL,
    FOREIGN KEY (criado_por) REFERENCES utilizadores(id) ON DELETE SET NULL
);

-- 6. TABELA DE HISTÓRICO / AUDITORIA
CREATE TABLE IF NOT EXISTS historico (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utilizador_id INT,
    acao VARCHAR(50) NOT NULL,
    tabela VARCHAR(50) NOT NULL,
    registo_id INT,
    descricao TEXT,
    data_hora TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (utilizador_id) REFERENCES utilizadores(id) ON DELETE SET NULL
);

-- ============================================================
-- DADOS INICIAIS (para testar)
-- ============================================================

-- Criar utilizador administrador padrão
-- NOTA: Após instalar, acede a http://localhost/majo_system/setup_senha.php
-- para definir a senha segura. Apaga o ficheiro depois!
-- Senha temporária de instalação: password (muda imediatamente!)
INSERT INTO utilizadores (nome, email, senha, perfil) VALUES
('Administrador', 'admin@majo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Criar algumas categorias de exemplo
INSERT INTO categorias (nome, descricao) VALUES
('Electrónica', 'Produtos electrónicos e tecnologia'),
('Alimentação', 'Produtos alimentares'),
('Escritório', 'Material de escritório'),
('Limpeza', 'Produtos de limpeza');

-- Criar alguns produtos de exemplo
INSERT INTO produtos (codigo, nome, descricao, categoria_id, preco_compra, preco_venda, margem_lucro, quantidade_stock, stock_minimo, unidade_medida, status, criado_por) VALUES
('PRD001', 'Computador Portátil', 'Laptop 15 polegadas 8GB RAM', 1, 150000.00, 200000.00, 33.33, 10, 3, 'un', 'ativo', 1),
('PRD002', 'Resma de Papel A4', 'Papel branco 500 folhas', 3, 1500.00, 2500.00, 66.67, 50, 10, 'resma', 'ativo', 1),
('PRD003', 'Água Mineral 1.5L', 'Água mineral natural', 2, 200.00, 350.00, 75.00, 200, 50, 'un', 'ativo', 1),
('PRD004', 'Detergente 1L', 'Detergente multiusos', 4, 500.00, 850.00, 70.00, 4, 5, 'un', 'ativo', 1);
