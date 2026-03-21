CREATE DATABASE IF NOT EXISTS bolosdagal
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE bolosdagal;

CREATE TABLE IF NOT EXISTS usuarios (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(50) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS itens (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    codigo_produto VARCHAR(50) NOT NULL UNIQUE,
    nome_produto VARCHAR(150) NOT NULL,
    ncm VARCHAR(20) NOT NULL,
    preco_custo DECIMAL(10,2) NOT NULL,
    preco_venda DECIMAL(10,2) NOT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS vendas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    comanda_codigo VARCHAR(40) NOT NULL,
    item_id INT UNSIGNED NOT NULL,
    codigo_produto VARCHAR(50) NOT NULL,
    descricao_produto VARCHAR(150) NOT NULL,
    quantidade DECIMAL(10,2) NOT NULL,
    preco_unitario DECIMAL(10,2) NOT NULL,
    valor_total DECIMAL(10,2) NOT NULL,
    forma_pagamento VARCHAR(30) NOT NULL,
    valor_recebido DECIMAL(10,2) DEFAULT NULL,
    valor_troco DECIMAL(10,2) DEFAULT NULL,
    data_hora_venda DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    usuario_login VARCHAR(50) DEFAULT NULL,
    observacao VARCHAR(255) DEFAULT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_vendas_item
        FOREIGN KEY (item_id) REFERENCES itens(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    INDEX idx_vendas_comanda_codigo (comanda_codigo),
    INDEX idx_vendas_codigo_produto (codigo_produto),
    INDEX idx_vendas_data_hora (data_hora_venda)
);

CREATE TABLE IF NOT EXISTS despesas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    data_despesa DATE NOT NULL,
    valor_despesa DECIMAL(10,2) NOT NULL,
    descricao_despesa VARCHAR(255) NOT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_despesas_data (data_despesa)
);

INSERT INTO usuarios (usuario, senha)
SELECT 'bolos', 'dagal'
WHERE NOT EXISTS (
    SELECT 1 FROM usuarios WHERE usuario = 'bolos'
);

INSERT INTO usuarios (usuario, senha)
SELECT 'root', '801973'
WHERE NOT EXISTS (
    SELECT 1 FROM usuarios WHERE usuario = 'root'
);
