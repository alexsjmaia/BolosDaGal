<?php
require __DIR__ . '/auth.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: campanha-bolos-gratis.php');
    exit;
}

require __DIR__ . '/db.php';

$quantidadeEntrada = trim((string) ($_POST['quantidade_para_ganhar'] ?? ''));

$_SESSION['campanha_bolos_gratis_dados'] = [
    'quantidade_para_ganhar' => $quantidadeEntrada,
];

if ($quantidadeEntrada === '') {
    $_SESSION['campanha_bolos_gratis_erro'] = 'Informe a quantidade de bolos para liberar 1 gratis.';
    header('Location: campanha-bolos-gratis.php');
    exit;
}

if (!ctype_digit($quantidadeEntrada)) {
    $_SESSION['campanha_bolos_gratis_erro'] = 'Informe uma quantidade inteira valida.';
    header('Location: campanha-bolos-gratis.php');
    exit;
}

$quantidadeParaGanhar = (int) $quantidadeEntrada;

if ($quantidadeParaGanhar <= 0) {
    $_SESSION['campanha_bolos_gratis_erro'] = 'A quantidade precisa ser maior que zero.';
    header('Location: campanha-bolos-gratis.php');
    exit;
}

try {
    $stmt = $pdo->prepare(
        'INSERT INTO campanhas_bolos_gratis (quantidade_para_ganhar)
         VALUES (:quantidade_para_ganhar)'
    );
    $stmt->execute([
        'quantidade_para_ganhar' => $quantidadeParaGanhar,
    ]);
} catch (PDOException $e) {
    $_SESSION['campanha_bolos_gratis_erro'] = 'Nao foi possivel salvar a regra da campanha.';
    header('Location: campanha-bolos-gratis.php');
    exit;
}

unset($_SESSION['campanha_bolos_gratis_dados']);
$_SESSION['campanha_bolos_gratis_sucesso'] = 'Regra da campanha cadastrada com sucesso.';

header('Location: campanha-bolos-gratis.php');
exit;
