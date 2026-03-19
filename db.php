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
} catch (PDOException $e) {
    die('Erro ao conectar no MySQL. Verifique o arquivo config.php e se o banco foi criado.');
}
