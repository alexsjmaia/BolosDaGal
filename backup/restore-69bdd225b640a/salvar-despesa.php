<?php
require __DIR__ . '/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: lancar-despesa.php');
    exit;
}

require __DIR__ . '/db.php';

$dataDespesa = trim($_POST['data_despesa'] ?? '');
$valorDespesa = trim($_POST['valor_despesa'] ?? '');
$valorDespesaNormalizado = str_replace(',', '.', str_replace('.', '', $valorDespesa));
$descricaoDespesa = trim($_POST['descricao_despesa'] ?? '');

$_SESSION['despesa_dados'] = [
    'data_despesa' => $dataDespesa,
    'valor_despesa' => $valorDespesa,
    'descricao_despesa' => $descricaoDespesa,
];

if ($dataDespesa === '' || $valorDespesa === '' || $descricaoDespesa === '') {
    $_SESSION['despesa_erro'] = 'Preencha a data, o valor e a descricao da despesa.';
    header('Location: lancar-despesa.php');
    exit;
}

$dataValida = DateTime::createFromFormat('Y-m-d', $dataDespesa);

if (!$dataValida || $dataValida->format('Y-m-d') !== $dataDespesa) {
    $_SESSION['despesa_erro'] = 'Informe uma data de despesa valida.';
    header('Location: lancar-despesa.php');
    exit;
}

if (!is_numeric($valorDespesaNormalizado) || (float) $valorDespesaNormalizado < 0) {
    $_SESSION['despesa_erro'] = 'Informe um valor numerico valido para a despesa.';
    header('Location: lancar-despesa.php');
    exit;
}

try {
    $stmt = $pdo->prepare(
        'INSERT INTO despesas (data_despesa, valor_despesa, descricao_despesa)
         VALUES (:data_despesa, :valor_despesa, :descricao_despesa)'
    );

    $stmt->execute([
        'data_despesa' => $dataDespesa,
        'valor_despesa' => number_format((float) $valorDespesaNormalizado, 2, '.', ''),
        'descricao_despesa' => $descricaoDespesa,
    ]);
} catch (PDOException $e) {
    $_SESSION['despesa_erro'] = 'Nao foi possivel salvar a despesa no banco de dados.';
    header('Location: lancar-despesa.php');
    exit;
}

unset($_SESSION['despesa_dados']);
$_SESSION['despesa_sucesso'] = 'Despesa lancada com sucesso.';

header('Location: lancar-despesa.php');
exit;
