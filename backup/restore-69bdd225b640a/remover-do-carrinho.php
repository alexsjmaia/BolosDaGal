<?php
require __DIR__ . '/auth.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: vender-item.php');
    exit;
}

$itemId = (int) ($_POST['item_id'] ?? 0);

if ($itemId > 0 && isset($_SESSION['carrinho_venda'][$itemId])) {
    unset($_SESSION['carrinho_venda'][$itemId]);
    $_SESSION['venda_sucesso'] = 'Item removido do carrinho.';
}

header('Location: vender-item.php');
exit;
