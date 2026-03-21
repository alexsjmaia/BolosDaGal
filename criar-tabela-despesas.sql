USE bolosdagal;

CREATE TABLE IF NOT EXISTS despesas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    data_despesa DATE NOT NULL,
    valor_despesa DECIMAL(10,2) NOT NULL,
    descricao_despesa VARCHAR(255) NOT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_despesas_data (data_despesa)
);
