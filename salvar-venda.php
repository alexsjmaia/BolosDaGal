<?php
require __DIR__ . '/auth.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: vender-item.php');
    exit;
}

require __DIR__ . '/db.php';

$carrinho = $_SESSION['carrinho_venda'] ?? [];
$observacao = trim($_POST['observacao'] ?? '');
$formaPagamento = trim($_POST['forma_pagamento'] ?? '');
$valorRecebido = str_replace(',', '.', trim($_POST['valor_recebido'] ?? ''));
$dataVenda = trim($_POST['data_venda'] ?? '');

if (!$carrinho) {
    $_SESSION['venda_erro'] = 'Adicione pelo menos um item ao carrinho antes de finalizar a venda.';
    header('Location: vender-item.php');
    exit;
}

$formasPagamentoValidas = [
    'Dinheiro',
    'Pix',
    'Cartao de credito',
    'Cartao de debito',
];

if (!in_array($formaPagamento, $formasPagamentoValidas, true)) {
    $_SESSION['venda_erro'] = 'Selecione uma forma de pagamento valida.';
    header('Location: vender-item.php');
    exit;
}

$dataHoraVenda = date('Y-m-d H:i:s');

if ($dataVenda !== '') {
    $partes = date_parse_from_format('Y-m-d', $dataVenda);

    if (
        $partes['error_count'] > 0 ||
        $partes['warning_count'] > 0 ||
        !checkdate($partes['month'], $partes['day'], $partes['year'])
    ) {
        $_SESSION['venda_erro'] = 'Informe uma data de venda valida.';
        header('Location: vender-item.php');
        exit;
    }

    $dataHoraVenda = $dataVenda . ' ' . date('H:i:s');
}

$comandaCodigo = 'CMD-' . date('YmdHis') . '-' . str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
$totalGeral = 0.0;

foreach ($carrinho as $itemCarrinho) {
    $totalGeral += (float) $itemCarrinho['valor_total'];
}

$valorRecebidoFinal = null;
$valorTrocoFinal = null;

if ($formaPagamento === 'Dinheiro') {
    if ($valorRecebido === '' || !is_numeric($valorRecebido)) {
        $_SESSION['venda_erro'] = 'Informe o valor recebido em dinheiro.';
        header('Location: vender-item.php');
        exit;
    }

    $valorRecebidoFinal = (float) $valorRecebido;

    if ($valorRecebidoFinal < $totalGeral) {
        $_SESSION['venda_erro'] = 'O valor recebido nao pode ser menor que o total da comanda.';
        header('Location: vender-item.php');
        exit;
    }

    $valorTrocoFinal = $valorRecebidoFinal - $totalGeral;
}

try {
    $pdo->beginTransaction();

    $stmtInsert = $pdo->prepare(
        'INSERT INTO vendas (
            comanda_codigo,
            item_id,
            codigo_produto,
            descricao_produto,
            quantidade,
            preco_unitario,
            valor_total,
            forma_pagamento,
            valor_recebido,
            valor_troco,
            data_hora_venda,
            usuario_login,
            observacao
        ) VALUES (
            :comanda_codigo,
            :item_id,
            :codigo_produto,
            :descricao_produto,
            :quantidade,
            :preco_unitario,
            :valor_total,
            :forma_pagamento,
            :valor_recebido,
            :valor_troco,
            :data_hora_venda,
            :usuario_login,
            :observacao
        )'
    );

    foreach ($carrinho as $itemCarrinho) {
        $stmtInsert->execute([
            'comanda_codigo' => $comandaCodigo,
            'item_id' => $itemCarrinho['item_id'],
            'codigo_produto' => $itemCarrinho['codigo_produto'],
            'descricao_produto' => $itemCarrinho['descricao_produto'],
            'quantidade' => number_format((float) $itemCarrinho['quantidade'], 2, '.', ''),
            'preco_unitario' => number_format((float) $itemCarrinho['preco_unitario'], 2, '.', ''),
            'valor_total' => number_format((float) $itemCarrinho['valor_total'], 2, '.', ''),
            'forma_pagamento' => $formaPagamento,
            'valor_recebido' => $valorRecebidoFinal !== null ? number_format($valorRecebidoFinal, 2, '.', '') : null,
            'valor_troco' => $valorTrocoFinal !== null ? number_format($valorTrocoFinal, 2, '.', '') : null,
            'data_hora_venda' => $dataHoraVenda,
            'usuario_login' => $_SESSION['usuario'],
            'observacao' => $observacao !== '' ? $observacao : null,
        ]);
    }

    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $_SESSION['venda_erro'] = 'Nao foi possivel finalizar a venda. Verifique se a tabela vendas esta atualizada.';
    header('Location: vender-item.php');
    exit;
}

unset($_SESSION['carrinho_venda']);
header('Location: comprovante-venda.php?comanda=' . urlencode($comandaCodigo));
exit;
