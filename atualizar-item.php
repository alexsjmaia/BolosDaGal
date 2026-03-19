<?php
require __DIR__ . '/auth.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: alterar-item.php');
    exit;
}

require __DIR__ . '/db.php';

$id = (int) ($_POST['id'] ?? 0);
$nomeProduto = trim($_POST['nome_produto'] ?? '');
$ncm = trim($_POST['ncm'] ?? '');
$precoCusto = str_replace(',', '.', trim($_POST['preco_custo'] ?? ''));
$precoVenda = str_replace(',', '.', trim($_POST['preco_venda'] ?? ''));

if ($id <= 0 || $nomeProduto === '' || $ncm === '' || $precoCusto === '' || $precoVenda === '') {
    $_SESSION['alterar_item_erro'] = 'Preencha todos os campos para alterar o item.';
    header('Location: alterar-item.php');
    exit;
}

if (!is_numeric($precoCusto) || !is_numeric($precoVenda)) {
    $_SESSION['alterar_item_erro'] = 'Informe valores validos para preco de custo e preco de venda.';
    header('Location: alterar-item.php');
    exit;
}

$stmtCodigo = $pdo->prepare('SELECT codigo_produto FROM itens WHERE id = :id LIMIT 1');
$stmtCodigo->execute(['id' => $id]);
$registro = $stmtCodigo->fetch();

if (!$registro) {
    $_SESSION['alterar_item_erro'] = 'Item nao encontrado.';
    header('Location: alterar-item.php');
    exit;
}

$codigoProduto = $registro['codigo_produto'];

try {
    $stmt = $pdo->prepare(
        'UPDATE itens
         SET nome_produto = :nome_produto,
             ncm = :ncm,
             preco_custo = :preco_custo,
             preco_venda = :preco_venda
         WHERE id = :id'
    );

    $stmt->execute([
        'id' => $id,
        'nome_produto' => $nomeProduto,
        'ncm' => $ncm,
        'preco_custo' => number_format((float) $precoCusto, 2, '.', ''),
        'preco_venda' => number_format((float) $precoVenda, 2, '.', ''),
    ]);
} catch (PDOException $e) {
    $_SESSION['alterar_item_erro'] = 'Nao foi possivel atualizar o item.';
    header('Location: alterar-item.php?codigo=' . urlencode($codigoProduto));
    exit;
}

$_SESSION['alterar_item_sucesso'] = 'Item alterado com sucesso.';
header('Location: alterar-item.php?codigo=' . urlencode($codigoProduto));
exit;
