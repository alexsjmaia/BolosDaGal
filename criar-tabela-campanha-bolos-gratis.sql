USE bolosdagal;

CREATE TABLE IF NOT EXISTS campanhas_bolos_gratis (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    quantidade_para_ganhar INT UNSIGNED NOT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
