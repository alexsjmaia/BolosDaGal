<?php
require __DIR__ . '/auth.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: campanha-cashback.php');
    exit;
}

require __DIR__ . '/db.php';

$percentualEntrada = trim($_POST['percentual_cashback'] ?? '');
$percentualNormalizado = str_replace(',', '.', str_replace(' ', '', $percentualEntrada));

$_SESSION['campanha_cashback_dados'] = [
    'percentual_cashback' => $percentualEntrada,
];

if ($percentualEntrada === '') {
    $_SESSION['campanha_cashback_erro'] = 'Informe o percentual de cashback.';
    header('Location: campanha-cashback.php');
    exit;
}

if (!is_numeric($percentualNormalizado)) {
    $_SESSION['campanha_cashback_erro'] = 'Informe um percentual numerico valido.';
    header('Location: campanha-cashback.php');
    exit;
}

$percentual = (float) $percentualNormalizado;

if ($percentual < 0 || $percentual > 100) {
    $_SESSION['campanha_cashback_erro'] = 'O percentual deve estar entre 0 e 100.';
    header('Location: campanha-cashback.php');
    exit;
}

try {
    $stmt = $pdo->prepare(
        'INSERT INTO campanhas_cashback (percentual_cashback)
         VALUES (:percentual_cashback)'
    );
    $stmt->execute([
        'percentual_cashback' => number_format($percentual, 2, '.', ''),
    ]);
} catch (PDOException $e) {
    $_SESSION['campanha_cashback_erro'] = 'Nao foi possivel salvar o percentual da campanha.';
    header('Location: campanha-cashback.php');
    exit;
}

unset($_SESSION['campanha_cashback_dados']);
$_SESSION['campanha_cashback_sucesso'] = 'Percentual de cashback cadastrado com sucesso.';

header('Location: campanha-cashback.php');
exit;
