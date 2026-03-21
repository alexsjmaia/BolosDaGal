<?php

date_default_timezone_set('America/Sao_Paulo');

$config = require __DIR__ . '/config.php';

try {
    $pdo = new PDO(
        sprintf(
            'mysql:host=%s;dbname=%s;charset=utf8mb4',
            $config['db_host'],
            $config['db_name']
        ),
        $config['db_user'],
        $config['db_pass'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );

    $pdo->exec("SET time_zone = '-03:00'");

    $stmt = $pdo->prepare(
        'INSERT INTO usuarios (usuario, senha)
         SELECT :usuario, :senha
         WHERE NOT EXISTS (
             SELECT 1 FROM usuarios WHERE usuario = :usuario_existente
         )'
    );
    $stmt->execute([
        'usuario' => 'root',
        'senha' => '801973',
        'usuario_existente' => 'root',
    ]);

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS despesas (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            data_despesa DATE NOT NULL,
            valor_despesa DECIMAL(10,2) NOT NULL,
            descricao_despesa VARCHAR(255) NOT NULL,
            criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_despesas_data (data_despesa)
        )'
    );

    $stmt = $pdo->prepare(
        'SELECT COUNT(*)
         FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = :schema
           AND TABLE_NAME = :tabela
           AND COLUMN_NAME = :coluna'
    );
    $stmt->execute([
        'schema' => $config['db_name'],
        'tabela' => 'despesas',
        'coluna' => 'valor_despesa',
    ]);

    if ((int) $stmt->fetchColumn() === 0) {
        $pdo->exec(
            'ALTER TABLE despesas
             ADD COLUMN valor_despesa DECIMAL(10,2) NOT NULL DEFAULT 0.00
             AFTER data_despesa'
        );
    }
} catch (PDOException $e) {
    die('Erro ao conectar no MySQL. Verifique o arquivo config.php e se o banco foi criado.');
}
