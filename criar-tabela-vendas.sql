USE bolosdagal;

CREATE TABLE IF NOT EXISTS vendas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    comanda_codigo VARCHAR(40) NOT NULL,
    item_id INT UNSIGNED NOT NULL,
    codigo_produto VARCHAR(50) NOT NULL,
    descricao_produto VARCHAR(150) NOT NULL,
    quantidade DECIMAL(10,2) NOT NULL,
    preco_custo_unitario DECIMAL(10,2) NOT NULL,
    preco_unitario DECIMAL(10,2) NOT NULL,
    valor_total DECIMAL(10,2) NOT NULL,
    cashback_utilizado_item DECIMAL(10,2) NOT NULL DEFAULT 0.00,
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
