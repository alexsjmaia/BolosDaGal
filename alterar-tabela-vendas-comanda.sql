USE bolosdagal;

ALTER TABLE vendas
ADD COLUMN comanda_codigo VARCHAR(40) NOT NULL AFTER id,
ADD COLUMN preco_custo_unitario DECIMAL(10,2) DEFAULT NULL AFTER quantidade,
ADD COLUMN cashback_utilizado_item DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER valor_total,
ADD COLUMN forma_pagamento VARCHAR(30) NOT NULL AFTER valor_total,
ADD COLUMN valor_recebido DECIMAL(10,2) DEFAULT NULL AFTER forma_pagamento,
ADD COLUMN valor_troco DECIMAL(10,2) DEFAULT NULL AFTER valor_recebido,
ADD INDEX idx_vendas_comanda_codigo (comanda_codigo);


