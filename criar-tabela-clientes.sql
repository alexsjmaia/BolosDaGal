USE bolosdagal;

CREATE TABLE IF NOT EXISTS clientes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    whatsapp VARCHAR(20) NOT NULL,
    senha_hash VARCHAR(255) DEFAULT NULL,
    saldo_cashback DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_clientes_nome (nome),
    UNIQUE KEY uk_clientes_whatsapp (whatsapp)
);
