<?php
require __DIR__ . '/cliente-auth.php';
require __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: cliente-login.php');
    exit;
}

$whatsappEntrada = trim($_POST['whatsapp'] ?? '');
$senha = (string) ($_POST['senha'] ?? '');
$whatsapp = preg_replace('/\D+/', '', $whatsappEntrada) ?? '';

$_SESSION['cliente_login_dados'] = [
    'whatsapp' => $whatsappEntrada,
];

if ($whatsappEntrada === '' || $senha === '') {
    $_SESSION['cliente_login_erro'] = 'Informe telefone e senha.';
    header('Location: cliente-login.php');
    exit;
}

if (strlen($whatsapp) !== 11 || substr($whatsapp, 2, 1) !== '9') {
    $_SESSION['cliente_login_erro'] = 'Informe um telefone valido com 11 numeros e nono digito 9.';
    header('Location: cliente-login.php');
    exit;
}

$stmt = $pdo->prepare(
    'SELECT id, nome, whatsapp, senha_hash, saldo_cashback
     FROM clientes
     WHERE whatsapp = :whatsapp
     LIMIT 1'
);
$stmt->execute(['whatsapp' => $whatsapp]);
$cliente = $stmt->fetch();

if (!$cliente || empty($cliente['senha_hash']) || !password_verify($senha, $cliente['senha_hash'])) {
    $_SESSION['cliente_login_erro'] = 'Telefone ou senha invalidos.';
    header('Location: cliente-login.php');
    exit;
}

$_SESSION['cliente_id'] = (int) $cliente['id'];
$_SESSION['cliente_nome'] = $cliente['nome'];
$_SESSION['cliente_whatsapp'] = $cliente['whatsapp'];
$_SESSION['cliente_saldo_cashback'] = (float) $cliente['saldo_cashback'];
unset($_SESSION['cliente_login_dados']);

header('Location: cardapio-cliente.php');
exit;

