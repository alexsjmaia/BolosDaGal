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

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS clientes (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            nome VARCHAR(150) NOT NULL,
            whatsapp VARCHAR(20) NOT NULL,
            senha_hash VARCHAR(255) DEFAULT NULL,
            saldo_cashback DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_clientes_nome (nome),
            UNIQUE KEY uk_clientes_whatsapp (whatsapp)
        )'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS vendas_clientes (
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
        )'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS campanhas_cashback (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            percentual_cashback DECIMAL(5,2) NOT NULL,
            criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS pedidos_clientes (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            cliente_id INT UNSIGNED NOT NULL,
            item_id INT UNSIGNED NOT NULL,
            sabor_bolo VARCHAR(150) NOT NULL,
            quantidade INT UNSIGNED NOT NULL,
            preco_unitario DECIMAL(10,2) NOT NULL,
            valor_total DECIMAL(10,2) NOT NULL,
            data_hora_entrega DATETIME NOT NULL,
            status_pedido VARCHAR(30) NOT NULL DEFAULT \'Pendente\',
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

    $stmtTabelaVendas = $pdo->prepare(
        'SELECT COUNT(*)
         FROM INFORMATION_SCHEMA.TABLES
         WHERE TABLE_SCHEMA = :schema
           AND TABLE_NAME = :tabela'
    );
    $stmtTabelaVendas->execute([
        'schema' => $config['db_name'],
        'tabela' => 'vendas',
    ]);

    if ((int) $stmtTabelaVendas->fetchColumn() > 0) {
        $stmtColunaCashback = $pdo->prepare(
            'SELECT COUNT(*)
             FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = :schema
               AND TABLE_NAME = :tabela
               AND COLUMN_NAME = :coluna'
        );
        $stmtColunaCashback->execute([
            'schema' => $config['db_name'],
            'tabela' => 'vendas',
            'coluna' => 'cashback_utilizado_item',
        ]);

        if ((int) $stmtColunaCashback->fetchColumn() === 0) {
            $pdo->exec(
                'ALTER TABLE vendas
                 ADD COLUMN cashback_utilizado_item DECIMAL(10,2) NOT NULL DEFAULT 0.00
                 AFTER valor_total'
            );
        }
    }

    $stmtColunaSenhaHash = $pdo->prepare(
        'SELECT COUNT(*)
         FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = :schema
           AND TABLE_NAME = :tabela
           AND COLUMN_NAME = :coluna'
    );
    $stmtColunaSenhaHash->execute([
        'schema' => $config['db_name'],
        'tabela' => 'clientes',
        'coluna' => 'senha_hash',
    ]);

    if ((int) $stmtColunaSenhaHash->fetchColumn() === 0) {
        $pdo->exec(
            'ALTER TABLE clientes
             ADD COLUMN senha_hash VARCHAR(255) DEFAULT NULL
            AFTER whatsapp'
        );
    }

    $stmtColunaFotoItem = $pdo->prepare(
        'SELECT COUNT(*)
         FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = :schema
           AND TABLE_NAME = :tabela
           AND COLUMN_NAME = :coluna'
    );
    $stmtColunaFotoItem->execute([
        'schema' => $config['db_name'],
        'tabela' => 'itens',
        'coluna' => 'foto_produto',
    ]);

    if ((int) $stmtColunaFotoItem->fetchColumn() === 0) {
        $pdo->exec(
            'ALTER TABLE itens
             ADD COLUMN foto_produto VARCHAR(255) DEFAULT NULL
            AFTER ncm'
        );
    }

    $stmtColunaMostrarCatalogo = $pdo->prepare(
        'SELECT COUNT(*)
         FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = :schema
           AND TABLE_NAME = :tabela
           AND COLUMN_NAME = :coluna'
    );
    $stmtColunaMostrarCatalogo->execute([
        'schema' => $config['db_name'],
        'tabela' => 'itens',
        'coluna' => 'mostrar_catalogo',
    ]);

    if ((int) $stmtColunaMostrarCatalogo->fetchColumn() === 0) {
        $pdo->exec(
            'ALTER TABLE itens
             ADD COLUMN mostrar_catalogo TINYINT(1) NOT NULL DEFAULT 1
             AFTER foto_produto'
        );
    }
} catch (PDOException $e) {
    die('Erro ao conectar no MySQL. Verifique o arquivo config.php e se o banco foi criado.');
}
