<?php
if (!defined('DISABLE_SESSION_TIMEOUT')) {
    define('DISABLE_SESSION_TIMEOUT', true);
}

require __DIR__ . '/auth.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: cadastrar-cliente.php');
    exit;
}

require __DIR__ . '/db.php';

$whatsapp = trim($_POST['whatsapp'] ?? '');
$nome = trim($_POST['nome'] ?? '');
$whatsappNumerico = preg_replace('/\D+/', '', $whatsapp);

$_SESSION['cliente_dados'] = [
    'whatsapp' => $whatsapp,
    'nome' => $nome,
];

if ($whatsapp === '' || $nome === '') {
    $_SESSION['cliente_erro'] = 'Preencha o WhatsApp e o nome do cliente.';
    header('Location: cadastrar-cliente.php');
    exit;
}

if (strlen($whatsappNumerico) !== 11 || substr($whatsappNumerico, 2, 1) !== '9') {
    $_SESSION['cliente_erro'] = 'Informe um WhatsApp valido com 11 numeros, incluindo DDD, e com o nono digito igual a 9.';
    header('Location: cadastrar-cliente.php');
    exit;
}

$tamanhoNome = function_exists('mb_strlen')
    ? mb_strlen($nome)
    : strlen($nome);

if ($tamanhoNome < 5) {
    $_SESSION['cliente_erro'] = 'O nome precisa ser completo.';
    header('Location: cadastrar-cliente.php');
    exit;
}

try {
    $stmt = $pdo->prepare(
        'INSERT INTO clientes (whatsapp, nome, saldo_cashback)
         VALUES (:whatsapp, :nome, :saldo_cashback)'
    );

    $stmt->execute([
        'whatsapp' => $whatsappNumerico,
        'nome' => $nome,
        'saldo_cashback' => number_format(0, 2, '.', ''),
    ]);
} catch (PDOException $e) {
    if ((int) $e->getCode() === 23000) {
        $_SESSION['cliente_erro'] = 'Ja existe um cliente cadastrado com esse WhatsApp.';
    } else {
        $_SESSION['cliente_erro'] = 'Nao foi possivel salvar o cliente no banco de dados.';
    }

    header('Location: cadastrar-cliente.php');
    exit;
}

unset($_SESSION['cliente_dados']);
$_SESSION['cliente_sucesso'] = 'Cliente cadastrado com sucesso.';

header('Location: cadastrar-cliente.php');
exit;
