<?php
require __DIR__ . '/auth.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: cadastrar-item.php');
    exit;
}

require __DIR__ . '/db.php';

$codigoProduto = trim($_POST['codigo_produto'] ?? '');
$nomeProduto = trim($_POST['nome_produto'] ?? '');
$ncm = trim($_POST['ncm'] ?? '');
$precoCusto = str_replace(',', '.', trim($_POST['preco_custo'] ?? ''));
$precoVenda = str_replace(',', '.', trim($_POST['preco_venda'] ?? ''));

$_SESSION['item_dados'] = [
    'codigo_produto' => $codigoProduto,
    'nome_produto' => $nomeProduto,
    'ncm' => $ncm,
    'preco_custo' => $precoCusto,
    'preco_venda' => $precoVenda,
];

if ($codigoProduto === '' || $nomeProduto === '' || $ncm === '' || $precoCusto === '' || $precoVenda === '') {
    $_SESSION['item_erro'] = 'Preencha todos os campos do item.';
    header('Location: cadastrar-item.php');
    exit;
}

if (!is_numeric($precoCusto) || !is_numeric($precoVenda)) {
    $_SESSION['item_erro'] = 'Informe valores numericos para preco de custo e preco de venda.';
    header('Location: cadastrar-item.php');
    exit;
}

try {
    $stmt = $pdo->prepare(
        'INSERT INTO itens (codigo_produto, nome_produto, ncm, preco_custo, preco_venda)
         VALUES (:codigo_produto, :nome_produto, :ncm, :preco_custo, :preco_venda)'
    );

    $stmt->execute([
        'codigo_produto' => $codigoProduto,
        'nome_produto' => $nomeProduto,
        'ncm' => $ncm,
        'preco_custo' => number_format((float) $precoCusto, 2, '.', ''),
        'preco_venda' => number_format((float) $precoVenda, 2, '.', ''),
    ]);
} catch (PDOException $e) {
    if ((int) $e->getCode() === 23000) {
        $_SESSION['item_erro'] = 'Ja existe um item cadastrado com esse codigo do produto.';
    } else {
        $_SESSION['item_erro'] = 'Nao foi possivel salvar o item no banco de dados.';
    }

    header('Location: cadastrar-item.php');
    exit;
}

unset($_SESSION['item_dados']);
$_SESSION['item_sucesso'] = 'Item cadastrado com sucesso.';

header('Location: cadastrar-item.php');
exit;
