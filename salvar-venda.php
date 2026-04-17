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
$clienteId = (int) ($_POST['cliente_id'] ?? 0);
$usarCashback = (($_POST['usar_cashback'] ?? '') === '1');
$formaPagamentoRestante = trim($_POST['forma_pagamento_restante'] ?? '');

$_SESSION['venda_cliente_id'] = $clienteId;
$_SESSION['venda_usar_cashback'] = $usarCashback ? '1' : '0';
$_SESSION['venda_forma_pagamento_restante'] = $formaPagamentoRestante;

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
    'Cashback',
];
$formasPagamentoRestanteValidas = [
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

if ($clienteId <= 0) {
    $_SESSION['venda_erro'] = 'Selecione qual cliente esta comprando.';
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
$itensCarrinho = array_values($carrinho);

foreach ($itensCarrinho as $itemCarrinho) {
    $totalGeral += (float) $itemCarrinho['valor_total'];
}

$valorRecebidoFinal = null;
$valorTrocoFinal = null;
$cashbackGerado = 0.0;
$cashbackUsado = 0.0;
$trocoCashback = 0.0;
$saldoCashbackAnterior = 0.0;
$saldoCashbackFinal = 0.0;
$cashbackPorItem = [];

try {
    $stmtCliente = $pdo->prepare(
        'SELECT id, nome, whatsapp, saldo_cashback
         FROM clientes
         WHERE id = :id
         LIMIT 1'
    );
    $stmtCliente->execute(['id' => $clienteId]);
    $cliente = $stmtCliente->fetch();

    if (!$cliente) {
        $_SESSION['venda_erro'] = 'Cliente nao encontrado para a venda.';
        header('Location: vender-item.php');
        exit;
    }

    $saldoCashbackAnterior = (float) $cliente['saldo_cashback'];
    $aplicarCashback = $usarCashback || $formaPagamento === 'Cashback';

    if ($aplicarCashback) {
        $cashbackUsado = min($saldoCashbackAnterior, $totalGeral);
        $trocoCashback = max($saldoCashbackAnterior - $cashbackUsado, 0);
    }

    $valorRestante = max($totalGeral - $cashbackUsado, 0);
    $cashbackGerado = $cashbackUsado > 0
        ? 0.0
        : round($valorRestante * 0.10, 2);

    if ($formaPagamento === 'Cashback' && $valorRestante > 0) {
        if (!in_array($formaPagamentoRestante, $formasPagamentoRestanteValidas, true)) {
            $_SESSION['venda_erro'] = 'Selecione a forma de pagamento para o restante da compra.';
            header('Location: vender-item.php');
            exit;
        }
    }

    if (
        ($formaPagamento === 'Dinheiro' && $valorRestante > 0) ||
        ($formaPagamento === 'Cashback' && $valorRestante > 0 && $formaPagamentoRestante === 'Dinheiro')
    ) {
        if ($valorRecebido === '' || !is_numeric($valorRecebido)) {
            $_SESSION['venda_erro'] = 'Informe o valor recebido em dinheiro.';
            header('Location: vender-item.php');
            exit;
        }

        $valorRecebidoFinal = (float) $valorRecebido;

        if ($valorRecebidoFinal < $valorRestante) {
            $_SESSION['venda_erro'] = 'O valor recebido nao pode ser menor que o valor restante apos usar cashback.';
            header('Location: vender-item.php');
            exit;
        }

        $valorTrocoFinal = $valorRecebidoFinal - $valorRestante;
    }

    $formaPagamentoFinal = $formaPagamento;
    if ($formaPagamento === 'Cashback' && $valorRestante > 0) {
        $formaPagamentoFinal = 'Cashback + ' . $formaPagamentoRestante;
    } elseif ($cashbackUsado > 0 && $formaPagamento !== 'Cashback') {
        $formaPagamentoFinal = $formaPagamento . ' + Cashback';
    }

    $pdo->beginTransaction();

    $cashbackRateado = 0.0;
    $ultimoIndiceItem = count($itensCarrinho) - 1;
    foreach ($itensCarrinho as $indice => $itemCarrinho) {
        if ($cashbackUsado <= 0 || $totalGeral <= 0) {
            $cashbackPorItem[$indice] = 0.0;
            continue;
        }

        if ($indice === $ultimoIndiceItem) {
            $cashbackItem = round($cashbackUsado - $cashbackRateado, 2);
        } else {
            $cashbackItem = round((((float) $itemCarrinho['valor_total']) / $totalGeral) * $cashbackUsado, 2);
            $cashbackRateado += $cashbackItem;
        }

        if ($cashbackItem < 0) {
            $cashbackItem = 0.0;
        }

        $cashbackPorItem[$indice] = $cashbackItem;
    }

    $stmtInsert = $pdo->prepare(
        'INSERT INTO vendas (
            comanda_codigo,
            item_id,
            codigo_produto,
            descricao_produto,
            quantidade,
            preco_custo_unitario,
            preco_unitario,
            valor_total,
            cashback_utilizado_item,
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
            :preco_custo_unitario,
            :preco_unitario,
            :valor_total,
            :cashback_utilizado_item,
            :forma_pagamento,
            :valor_recebido,
            :valor_troco,
            :data_hora_venda,
            :usuario_login,
            :observacao
        )'
    );

    foreach ($itensCarrinho as $indice => $itemCarrinho) {
        $stmtInsert->execute([
            'comanda_codigo' => $comandaCodigo,
            'item_id' => $itemCarrinho['item_id'],
            'codigo_produto' => $itemCarrinho['codigo_produto'],
            'descricao_produto' => $itemCarrinho['descricao_produto'],
            'quantidade' => number_format((float) $itemCarrinho['quantidade'], 2, '.', ''),
            'preco_custo_unitario' => number_format((float) $itemCarrinho['preco_custo_unitario'], 2, '.', ''),
            'preco_unitario' => number_format((float) $itemCarrinho['preco_unitario'], 2, '.', ''),
            'valor_total' => number_format((float) $itemCarrinho['valor_total'], 2, '.', ''),
            'cashback_utilizado_item' => number_format((float) ($cashbackPorItem[$indice] ?? 0), 2, '.', ''),
            'forma_pagamento' => $formaPagamentoFinal,
            'valor_recebido' => $valorRecebidoFinal !== null ? number_format($valorRecebidoFinal, 2, '.', '') : null,
            'valor_troco' => $valorTrocoFinal !== null ? number_format($valorTrocoFinal, 2, '.', '') : null,
            'data_hora_venda' => $dataHoraVenda,
            'usuario_login' => $_SESSION['usuario'],
            'observacao' => $observacao !== '' ? $observacao : null,
        ]);
    }

    $stmtAtualizaCashback = $pdo->prepare(
        'UPDATE clientes
         SET saldo_cashback = saldo_cashback - :cashback_usado + :cashback_gerado
         WHERE id = :id'
    );
    $stmtAtualizaCashback->execute([
        'cashback_usado' => number_format($cashbackUsado, 2, '.', ''),
        'cashback_gerado' => number_format($cashbackGerado, 2, '.', ''),
        'id' => $clienteId,
    ]);

    $saldoCashbackFinal = round($saldoCashbackAnterior - $cashbackUsado + $cashbackGerado, 2);

    $stmtHistoricoCliente = $pdo->prepare(
        'INSERT INTO vendas_clientes (
            comanda_codigo,
            cliente_nome,
            cliente_whatsapp,
            sabor_bolo,
            quantidade,
            data_compra,
            cashback_acumulado
        ) VALUES (
            :comanda_codigo,
            :cliente_nome,
            :cliente_whatsapp,
            :sabor_bolo,
            :quantidade,
            :data_compra,
            :cashback_acumulado
        )'
    );

    foreach ($carrinho as $itemCarrinho) {
        $stmtHistoricoCliente->execute([
            'comanda_codigo' => $comandaCodigo,
            'cliente_nome' => $cliente['nome'],
            'cliente_whatsapp' => $cliente['whatsapp'],
            'sabor_bolo' => $itemCarrinho['descricao_produto'],
            'quantidade' => number_format((float) $itemCarrinho['quantidade'], 2, '.', ''),
            'data_compra' => $dataHoraVenda,
            'cashback_acumulado' => number_format($saldoCashbackFinal, 2, '.', ''),
        ]);
    }

    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $_SESSION['venda_erro'] = 'Nao foi possivel finalizar a venda. Verifique os dados de vendas e clientes.';
    header('Location: vender-item.php');
    exit;
}

unset($_SESSION['carrinho_venda']);
unset($_SESSION['venda_cliente_id']);
unset($_SESSION['venda_usar_cashback']);
unset($_SESSION['venda_forma_pagamento_restante']);
$_SESSION['ultima_venda_cliente'] = [
    'comanda_codigo' => $comandaCodigo,
    'cliente_nome' => $cliente['nome'],
    'cliente_whatsapp' => $cliente['whatsapp'],
    'cashback_usado' => $cashbackUsado,
    'troco_cashback' => $trocoCashback,
    'saldo_cashback_anterior' => $saldoCashbackAnterior,
    'saldo_cashback_final' => $saldoCashbackFinal,
    'cashback_gerado' => $cashbackGerado,
];
header('Location: comprovante-venda.php?comanda=' . urlencode($comandaCodigo));
exit;
