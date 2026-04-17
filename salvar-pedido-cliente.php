<?php
require __DIR__ . '/cliente-auth.php';
require __DIR__ . '/db.php';
exigirClienteLogado();
const LIMITE_UNIDADES_DIA = 10;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: cardapio-cliente.php');
    exit;
}

$acao = trim((string) ($_POST['acao'] ?? ''));

if (!isset($_SESSION['cliente_carrinho']) || !is_array($_SESSION['cliente_carrinho'])) {
    $_SESSION['cliente_carrinho'] = [];
}

if ($acao === 'adicionar') {
    $itemId = (int) ($_POST['item_id'] ?? 0);
    $quantidade = (int) ($_POST['quantidade'] ?? 0);

    if ($itemId <= 0 || $quantidade <= 0) {
        $_SESSION['cliente_pedido_erro'] = 'Selecione um sabor e informe uma quantidade valida.';
        header('Location: cardapio-cliente.php');
        exit;
    }

    $stmtItem = $pdo->prepare(
        'SELECT id
         FROM itens
         WHERE id = :id
         LIMIT 1'
    );
    $stmtItem->execute(['id' => $itemId]);
    $item = $stmtItem->fetch();

    if (!$item) {
        $_SESSION['cliente_pedido_erro'] = 'Sabor nao encontrado.';
        header('Location: cardapio-cliente.php');
        exit;
    }

    $quantidadeAtual = (int) ($_SESSION['cliente_carrinho'][$itemId] ?? 0);
    $_SESSION['cliente_carrinho'][$itemId] = $quantidadeAtual + $quantidade;
    $_SESSION['cliente_pedido_sucesso'] = 'Sabor adicionado ao pedido.';
    header('Location: cardapio-cliente.php');
    exit;
}

if ($acao === 'remover') {
    $itemId = (int) ($_POST['item_id'] ?? 0);
    if ($itemId > 0) {
        unset($_SESSION['cliente_carrinho'][$itemId]);
        $_SESSION['cliente_pedido_sucesso'] = 'Item removido do pedido.';
    }
    header('Location: cardapio-cliente.php');
    exit;
}

if ($acao === 'finalizar') {
    $dataEntrega = trim((string) ($_POST['data_entrega'] ?? ''));
    $horaEntrega = trim((string) ($_POST['hora_entrega'] ?? ''));
    $_SESSION['cliente_pedido_dados'] = [
        'data_entrega' => $dataEntrega,
        'hora_entrega' => $horaEntrega,
    ];

    $carrinho = $_SESSION['cliente_carrinho'] ?? [];
    if (!$carrinho) {
        $_SESSION['cliente_pedido_erro'] = 'Adicione pelo menos um sabor antes de finalizar o pedido.';
        header('Location: cardapio-cliente.php');
        exit;
    }

    if ($dataEntrega === '' || $horaEntrega === '') {
        $_SESSION['cliente_pedido_erro'] = 'Informe a data e o horario de entrega.';
        header('Location: cardapio-cliente.php');
        exit;
    }

    if (!preg_match('/^\d{2}:\d{2}$/', $horaEntrega) || !in_array(substr($horaEntrega, 3, 2), ['00', '30'], true)) {
        $_SESSION['cliente_pedido_erro'] = 'Horario invalido. Selecione um horario de 30 em 30 minutos.';
        header('Location: cardapio-cliente.php');
        exit;
    }

    $dataHoraEntregaSql = $dataEntrega . ' ' . $horaEntrega . ':00';
    $dataHoraEntregaObj = DateTime::createFromFormat('Y-m-d H:i:s', $dataHoraEntregaSql);
    if (!$dataHoraEntregaObj || $dataHoraEntregaObj->format('Y-m-d H:i:s') !== $dataHoraEntregaSql) {
        $_SESSION['cliente_pedido_erro'] = 'Data ou horario de entrega invalido.';
        header('Location: cardapio-cliente.php');
        exit;
    }

    $idsItens = array_map('intval', array_keys($carrinho));
    $placeholders = implode(',', array_fill(0, count($idsItens), '?'));
    $stmtItens = $pdo->prepare(
        "SELECT id, nome_produto, preco_venda
         FROM itens
         WHERE id IN ({$placeholders})"
    );
    $stmtItens->execute($idsItens);
    $itens = $stmtItens->fetchAll();

    if (!$itens) {
        $_SESSION['cliente_pedido_erro'] = 'Nao foi possivel localizar os sabores selecionados.';
        header('Location: cardapio-cliente.php');
        exit;
    }

    $mapItens = [];
    foreach ($itens as $item) {
        $mapItens[(int) $item['id']] = $item;
    }

    $quantidadePedido = 0;
    foreach ($carrinho as $qtdCarrinho) {
        $quantidadePedido += (int) $qtdCarrinho;
    }

    $stmtCapacidade = $pdo->prepare(
        'SELECT COALESCE(SUM(quantidade), 0)
         FROM pedidos_clientes
         WHERE DATE(data_hora_entrega) = :data_entrega'
    );
    $stmtCapacidade->execute(['data_entrega' => $dataEntrega]);
    $quantidadeNoDia = (int) $stmtCapacidade->fetchColumn();

    if (($quantidadeNoDia + $quantidadePedido) > LIMITE_UNIDADES_DIA) {
        $disponivel = max(LIMITE_UNIDADES_DIA - $quantidadeNoDia, 0);
        $_SESSION['cliente_pedido_erro'] = 'Data indisponivel. Restam apenas ' . $disponivel . ' unidade(s) para este dia.';
        header('Location: cardapio-cliente.php');
        exit;
    }

    try {
        $pdo->beginTransaction();

        $stmtInsert = $pdo->prepare(
            'INSERT INTO pedidos_clientes (
                cliente_id,
                item_id,
                sabor_bolo,
                quantidade,
                preco_unitario,
                valor_total,
                data_hora_entrega,
                status_pedido
            ) VALUES (
                :cliente_id,
                :item_id,
                :sabor_bolo,
                :quantidade,
                :preco_unitario,
                :valor_total,
                :data_hora_entrega,
                :status_pedido
            )'
        );

        foreach ($carrinho as $itemId => $quantidade) {
            $itemId = (int) $itemId;
            $quantidade = (int) $quantidade;

            if ($quantidade <= 0 || !isset($mapItens[$itemId])) {
                continue;
            }

            $item = $mapItens[$itemId];
            $precoUnitario = (float) $item['preco_venda'];
            $valorTotal = round($precoUnitario * $quantidade, 2);

            $stmtInsert->execute([
                'cliente_id' => (int) $_SESSION['cliente_id'],
                'item_id' => $itemId,
                'sabor_bolo' => (string) $item['nome_produto'],
                'quantidade' => $quantidade,
                'preco_unitario' => number_format($precoUnitario, 2, '.', ''),
                'valor_total' => number_format($valorTotal, 2, '.', ''),
                'data_hora_entrega' => $dataHoraEntregaSql,
                'status_pedido' => 'Pendente',
            ]);
        }

        $pdo->commit();
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $_SESSION['cliente_pedido_erro'] = 'Nao foi possivel finalizar o pedido.';
        header('Location: cardapio-cliente.php');
        exit;
    }

    $_SESSION['cliente_carrinho'] = [];
    unset($_SESSION['cliente_pedido_dados']);
    $_SESSION['cliente_pedido_sucesso'] = 'Pedido registrado com sucesso.';
    header('Location: cardapio-cliente.php');
    exit;
}

$_SESSION['cliente_pedido_erro'] = 'Acao invalida para o pedido.';
header('Location: cardapio-cliente.php');
exit;
