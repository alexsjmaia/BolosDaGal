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

$_SESSION['venda_cliente_id'] = $clienteId;

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
$totalQuantidadeItensPromocao = 0.0;
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
$saldoCashbackDisponivel = 0.0;
$saldoCashbackFinal = 0.0;
$cashbackPorItem = [];
$bonusExpiraEmAtual = null;
$bonusExpiraEmFinal = null;
$fidelidadeQuantidadeAnterior = 0.0;
$fidelidadeQuantidadeFinal = 0.0;
$bolosGratisAnterior = 0;
$bolosGratisFinal = 0;
$novosBolosGratis = 0;
$avisoFidelidade = '';

try {
    $stmtCliente = $pdo->prepare(
        'SELECT
            id,
            nome,
            whatsapp,
            saldo_cashback,
            bonus_expira_em,
            fidelidade_quantidade_acumulada,
            bolos_gratis_disponiveis
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
    $fidelidadeQuantidadeAnterior = (float) ($cliente['fidelidade_quantidade_acumulada'] ?? 0);
    $bolosGratisAnterior = (int) ($cliente['bolos_gratis_disponiveis'] ?? 0);
    $bonusExpiraEmAtual = $cliente['bonus_expira_em'] ?? null;
    $saldoCashbackDisponivel = $saldoCashbackAnterior;

    if (
        $bonusExpiraEmAtual !== null &&
        $bonusExpiraEmAtual !== '' &&
        strtotime((string) $bonusExpiraEmAtual) < strtotime($dataHoraVenda)
    ) {
        $saldoCashbackDisponivel = 0.0;
        $bonusExpiraEmAtual = null;
    }

    $valorRestante = $totalGeral;
    $cashbackGerado = 0.0;

    if (
        ($formaPagamento === 'Dinheiro' && $valorRestante > 0)
    ) {
        if ($valorRecebido === '') {
            $valorRecebidoFinal = $valorRestante;
        } elseif (!is_numeric($valorRecebido)) {
            $_SESSION['venda_erro'] = 'Informe um valor numerico valido para pagamento em dinheiro.';
            header('Location: vender-item.php');
            exit;
        } else {
            $valorRecebidoFinal = (float) $valorRecebido;
        }

        if ($valorRecebidoFinal < $valorRestante) {
            $_SESSION['venda_erro'] = 'O valor recebido nao pode ser menor que o valor total.';
            header('Location: vender-item.php');
            exit;
        }

        $valorTrocoFinal = $valorRecebidoFinal - $valorRestante;
    }

    $formaPagamentoFinal = $formaPagamento;

    $pdo->beginTransaction();

    $idsItens = array_values(array_unique(array_map(
        static fn(array $item): int => (int) ($item['item_id'] ?? 0),
        $itensCarrinho
    )));
    $idsItens = array_values(array_filter($idsItens, static fn(int $id): bool => $id > 0));

    $categoriasPorItem = [];
    if ($idsItens) {
        $placeholders = implode(',', array_fill(0, count($idsItens), '?'));
        $stmtCategoriasItens = $pdo->prepare(
            "SELECT id, categoria
             FROM itens
             WHERE id IN ({$placeholders})"
        );
        $stmtCategoriasItens->execute($idsItens);

        foreach ($stmtCategoriasItens->fetchAll() as $rowCategoria) {
            $categoriaNormalizada = trim((string) ($rowCategoria['categoria'] ?? ''));
            if (function_exists('mb_strtolower')) {
                $categoriaNormalizada = mb_strtolower($categoriaNormalizada, 'UTF-8');
            } else {
                $categoriaNormalizada = strtolower($categoriaNormalizada);
            }
            $categoriasPorItem[(int) $rowCategoria['id']] = $categoriaNormalizada;
        }
    }

    foreach ($itensCarrinho as $itemCarrinho) {
        $itemIdCarrinho = (int) ($itemCarrinho['item_id'] ?? 0);
        $categoriaItem = (string) ($categoriasPorItem[$itemIdCarrinho] ?? '');

        if ($categoriaItem === 'bolos') {
            $totalQuantidadeItensPromocao += (float) $itemCarrinho['quantidade'];
        }
    }

    $stmtCampanhaBolosGratis = $pdo->query(
        'SELECT quantidade_para_ganhar
         FROM campanhas_bolos_gratis
         ORDER BY id DESC
         LIMIT 1'
    );
    $campanhaBolosGratis = $stmtCampanhaBolosGratis->fetch();
    $quantidadeParaGanhar = (int) ($campanhaBolosGratis['quantidade_para_ganhar'] ?? 0);

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

    $saldoCashbackFinal = round($saldoCashbackDisponivel - $cashbackUsado + $cashbackGerado, 2);

    if ($saldoCashbackFinal <= 0) {
        $bonusExpiraEmFinal = null;
    } else {
        $bonusExpiraEmFinal = $bonusExpiraEmAtual;
    }

    $stmtHistoricoCliente = $pdo->prepare(
        'INSERT INTO vendas_clientes (
            comanda_codigo,
            item_id,
            cliente_nome,
            cliente_whatsapp,
            sabor_bolo,
            quantidade,
            data_compra,
            cashback_acumulado
        ) VALUES (
            :comanda_codigo,
            :item_id,
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
            'item_id' => (int) $itemCarrinho['item_id'],
            'cliente_nome' => $cliente['nome'],
            'cliente_whatsapp' => $cliente['whatsapp'],
            'sabor_bolo' => $itemCarrinho['descricao_produto'],
            'quantidade' => number_format((float) $itemCarrinho['quantidade'], 2, '.', ''),
            'data_compra' => $dataHoraVenda,
            'cashback_acumulado' => number_format($saldoCashbackFinal, 2, '.', ''),
        ]);
    }

    $bolosGratisFinal = 0;
    $fidelidadeQuantidadeFinal = 0.0;
    $quantidadeBolosPeriodo = 0.0;

    if ($quantidadeParaGanhar > 0) {
        $stmtQuantidadeBolosPeriodo = $pdo->prepare(
            "SELECT COALESCE(SUM(vc.quantidade), 0)
             FROM vendas_clientes vc
             INNER JOIN itens i
                ON i.id = vc.item_id
               AND LOWER(TRIM(COALESCE(i.categoria, ''))) = 'bolos'
             INNER JOIN campanhas_bolos_gratis cbg
                ON cbg.id = (SELECT id FROM campanhas_bolos_gratis ORDER BY id DESC LIMIT 1)
             WHERE vc.cliente_whatsapp = :cliente_whatsapp
               AND vc.data_compra >= cbg.criado_em"
        );
        $stmtQuantidadeBolosPeriodo->execute([
            'cliente_whatsapp' => $cliente['whatsapp'],
        ]);
        $quantidadeBolosPeriodo = (float) $stmtQuantidadeBolosPeriodo->fetchColumn();

        if ($quantidadeBolosPeriodo > 0) {
            $bolosGratisFinal = (int) floor($quantidadeBolosPeriodo / $quantidadeParaGanhar);
            $fidelidadeQuantidadeFinal = $quantidadeBolosPeriodo - ($bolosGratisFinal * $quantidadeParaGanhar);
        }
    }

    $novosBolosGratis = max($bolosGratisFinal - $bolosGratisAnterior, 0);

    if ($quantidadeParaGanhar > 0) {
        $faltamParaProximo = $quantidadeParaGanhar - (int) floor($fidelidadeQuantidadeFinal);

        if ($faltamParaProximo <= 0) {
            $faltamParaProximo = $quantidadeParaGanhar;
        }

        if ($novosBolosGratis > 0) {
            $avisoFidelidade =
                'Campanha ativa: cliente ganhou ' . $novosBolosGratis . ' bolo(s) gratis. ' .
                'Ja tem ' . rtrim(rtrim(number_format($fidelidadeQuantidadeFinal, 2, ',', '.'), '0'), ',') .
                ' acumulado(s) e faltam ' . $faltamParaProximo . ' para o proximo premio.';
        }
    }

    $stmtAtualizaCashback = $pdo->prepare(
        'UPDATE clientes
         SET saldo_cashback = :saldo_cashback,
             bonus_expira_em = :bonus_expira_em,
             fidelidade_quantidade_acumulada = :fidelidade_quantidade_acumulada,
             bolos_gratis_disponiveis = :bolos_gratis_disponiveis
         WHERE id = :id'
    );
    $stmtAtualizaCashback->execute([
        'saldo_cashback' => number_format($saldoCashbackFinal, 2, '.', ''),
        'bonus_expira_em' => $bonusExpiraEmFinal,
        'fidelidade_quantidade_acumulada' => number_format($fidelidadeQuantidadeFinal, 2, '.', ''),
        'bolos_gratis_disponiveis' => $bolosGratisFinal,
        'id' => $clienteId,
    ]);

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
$_SESSION['ultima_venda_cliente'] = [
    'comanda_codigo' => $comandaCodigo,
    'cliente_nome' => $cliente['nome'],
    'cliente_whatsapp' => $cliente['whatsapp'],
    'cashback_usado' => $cashbackUsado,
    'troco_cashback' => $trocoCashback,
    'saldo_cashback_anterior' => $saldoCashbackDisponivel,
    'saldo_cashback_final' => $saldoCashbackFinal,
    'cashback_gerado' => $cashbackGerado,
    'bonus_expira_em' => $bonusExpiraEmFinal,
    'bolos_gratis_anterior' => $bolosGratisAnterior,
    'bolos_gratis_final' => $bolosGratisFinal,
    'novos_bolos_gratis' => $novosBolosGratis,
    'aviso_fidelidade' => $avisoFidelidade,
];
header('Location: comprovante-venda.php?comanda=' . urlencode($comandaCodigo));
exit;
