USE bolosdagal;

CREATE TABLE IF NOT EXISTS vendas_clientes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    comanda_codigo VARCHAR(40) NOT NULL,
    cliente_nome VARCHAR(150) NOT NULL,
    cliente_whatsapp VARCHAR(20) NOT NULL,
    sabor_bolo VARCHAR(150) NOT NULL,
    quantidade DECIMAL(10,2) NOT NULL,
    data_compra DATETIME NOT NULL,
    cashback_acumulado DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_vendas_clientes_comanda (comanda_codigo),
    INDEX idx_vendas_clientes_whatsapp (cliente_whatsapp),
    INDEX idx_vendas_clientes_data_compra (data_compra)
);
