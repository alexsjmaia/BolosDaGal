<?php
require __DIR__ . '/auth.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: vender-item.php');
    exit;
}

require __DIR__ . '/db.php';

$itemId = (int) ($_POST['item_id'] ?? 0);
$quantidade = str_replace(',', '.', trim($_POST['quantidade'] ?? ''));

if ($itemId <= 0 || $quantidade === '') {
    $_SESSION['venda_erro'] = 'Selecione um item e informe a quantidade.';
    header('Location: vender-item.php');
    exit;
}

if (!is_numeric($quantidade) || (float) $quantidade <= 0) {
    $_SESSION['venda_erro'] = 'Informe uma quantidade valida maior que zero.';
    header('Location: vender-item.php');
    exit;
}

$stmt = $pdo->prepare(
    'SELECT id, codigo_produto, nome_produto, preco_venda
     FROM itens
     WHERE id = :id
     LIMIT 1'
);
$stmt->execute(['id' => $itemId]);
$item = $stmt->fetch();

if (!$item) {
    $_SESSION['venda_erro'] = 'Item nao encontrado para adicionar ao carrinho.';
    header('Location: vender-item.php');
    exit;
}

$quantidadeValor = (float) $quantidade;
$precoUnitario = (float) $item['preco_venda'];

if (!isset($_SESSION['carrinho_venda'])) {
    $_SESSION['carrinho_venda'] = [];
}

if (isset($_SESSION['carrinho_venda'][$itemId])) {
    $_SESSION['carrinho_venda'][$itemId]['quantidade'] += $quantidadeValor;
    $_SESSION['carrinho_venda'][$itemId]['valor_total'] =
        $_SESSION['carrinho_venda'][$itemId]['quantidade'] * $_SESSION['carrinho_venda'][$itemId]['preco_unitario'];
} else {
    $_SESSION['carrinho_venda'][$itemId] = [
        'item_id' => $item['id'],
        'codigo_produto' => $item['codigo_produto'],
        'descricao_produto' => $item['nome_produto'],
        'quantidade' => $quantidadeValor,
        'preco_unitario' => $precoUnitario,
        'valor_total' => $quantidadeValor * $precoUnitario,
    ];
}

$_SESSION['venda_sucesso'] = 'Item adicionado ao carrinho.';
header('Location: vender-item.php');
exit;
