USE bolosdagal;

CREATE TABLE IF NOT EXISTS pedidos_clientes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT UNSIGNED NOT NULL,
    item_id INT UNSIGNED NOT NULL,
    sabor_bolo VARCHAR(150) NOT NULL,
    quantidade INT UNSIGNED NOT NULL,
    preco_unitario DECIMAL(10,2) NOT NULL,
    valor_total DECIMAL(10,2) NOT NULL,
    data_hora_entrega DATETIME NOT NULL,
    status_pedido VARCHAR(30) NOT NULL DEFAULT 'Pendente',
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_pedidos_cliente
        FOREIGN KEY (cliente_id) REFERENCES clientes(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT fk_pedidos_item
        FOREIGN KEY (item_id) REFERENCES itens(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    INDEX idx_pedidos_clientes_cliente (cliente_id),
    INDEX idx_pedidos_clientes_entrega (data_hora_entrega)
);
