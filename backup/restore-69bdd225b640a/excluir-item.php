<?php
require __DIR__ . '/auth.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: alterar-item.php');
    exit;
}

require __DIR__ . '/db.php';

$id = (int) ($_POST['id'] ?? 0);

if ($id <= 0) {
    $_SESSION['alterar_item_erro'] = 'Item invalido para exclusao.';
    header('Location: alterar-item.php');
    exit;
}

try {
    $stmt = $pdo->prepare('DELETE FROM itens WHERE id = :id');
    $stmt->execute(['id' => $id]);
} catch (PDOException $e) {
    $_SESSION['alterar_item_erro'] = 'Nao foi possivel excluir o item.';
    header('Location: alterar-item.php');
    exit;
}

$_SESSION['alterar_item_sucesso'] = 'Item excluido com sucesso.';
header('Location: alterar-item.php');
exit;
