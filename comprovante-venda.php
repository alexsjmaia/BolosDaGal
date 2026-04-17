<?php
require __DIR__ . '/auth.php';
require __DIR__ . '/db.php';

$comandaCodigo = trim($_GET['comanda'] ?? '');

if ($comandaCodigo === '') {
    header('Location: vender-item.php');
    exit;
}

$stmt = $pdo->prepare(
    'SELECT
        comanda_codigo,
        codigo_produto,
        descricao_produto,
        quantidade,
        preco_unitario,
        valor_total,
        forma_pagamento,
        valor_recebido,
        valor_troco,
        data_hora_venda,
        observacao
     FROM vendas
     WHERE comanda_codigo = :comanda_codigo
     ORDER BY id'
);
$stmt->execute(['comanda_codigo' => $comandaCodigo]);
$vendas = $stmt->fetchAll();

if (!$vendas) {
    $_SESSION['venda_erro'] = 'Comprovante nao encontrado para a comanda informada.';
    header('Location: vender-item.php');
    exit;
}

$primeiraVenda = $vendas[0];
$totalProdutos = 0.0;
$resumoCliente = $_SESSION['ultima_venda_cliente'] ?? null;

if (
    is_array($resumoCliente) &&
    (($resumoCliente['comanda_codigo'] ?? '') !== $comandaCodigo)
) {
    $resumoCliente = null;
}

foreach ($vendas as $venda) {
    $totalProdutos += (float) $venda['valor_total'];
}

function cupom_center(string $text, int $width = 38): string
{
    $text = trim($text);
    $len = strlen($text);

    if ($len >= $width) {
        return substr($text, 0, $width);
    }

    $left = intdiv($width - $len, 2);
    return str_repeat(' ', $left) . $text;
}

function cupom_line(string $left, string $right = '', int $width = 38): string
{
    $space = $width - strlen($left) - strlen($right);

    if ($space < 1) {
        return substr($left . ' ' . $right, 0, $width);
    }

    return $left . str_repeat(' ', $space) . $right;
}

function cupom_date(string $datetime): string
{
    $timestamp = strtotime($datetime);
    $dias = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sab'];
    $meses = [1 => 'JAN', 'FEV', 'MAR', 'ABR', 'MAI', 'JUN', 'JUL', 'AGO', 'SET', 'OUT', 'NOV', 'DEZ'];

    return sprintf(
        '%s %s %s %s %s',
        $dias[(int) date('w', $timestamp)],
        date('d', $timestamp),
        $meses[(int) date('n', $timestamp)],
        date('Y', $timestamp),
        date('H:i:s', $timestamp)
    );
}

function cupom_money(float $value): string
{
    return number_format($value, 2, ',', '.');
}

function cupom_qty(float $value): string
{
    return rtrim(rtrim(number_format($value, 2, ',', '.'), '0'), ',');
}

function cupom_item_line(array $venda): array
{
    $descricao = substr($venda['descricao_produto'], 0, 16);
    $qtd = str_pad(cupom_qty((float) $venda['quantidade']), 3, ' ', STR_PAD_LEFT);
    $preco = str_pad(cupom_money((float) $venda['preco_unitario']), 5, ' ', STR_PAD_LEFT);
    $sub = str_pad(cupom_money((float) $venda['valor_total']), 5, ' ', STR_PAD_LEFT);
    $ref = str_pad(substr($venda['codigo_produto'], 0, 4), 4, ' ', STR_PAD_RIGHT);

    return [
        $ref . ' ' . str_pad($descricao, 16, ' ', STR_PAD_RIGHT) . ' ' . $qtd . ' ' . $preco . ' ' . $sub,
    ];
}

$linhasItens = [];
foreach ($vendas as $venda) {
    $linhasItens = array_merge($linhasItens, cupom_item_line($venda));
}

$pagamentoRegistrado = (string) $primeiraVenda['forma_pagamento'];
$formaPagamentoLabel = $pagamentoRegistrado === 'Dinheiro'
    ? 'Pagamento em Dinheiro'
    : 'Pagamento ' . $pagamentoRegistrado;
$pagamentoTemDinheiro = strpos($pagamentoRegistrado, 'Dinheiro') !== false;

$larguraCupom = 38;
$blocoCliente = '';

if (is_array($resumoCliente)) {
    $linhasCashback = [];
    $saldoCashbackAnterior = (float) ($resumoCliente['saldo_cashback_anterior'] ?? 0);
    $cashbackUsado = (float) ($resumoCliente['cashback_usado'] ?? 0);
    $trocoCashback = (float) ($resumoCliente['troco_cashback'] ?? 0);
    $cashbackGerado = (float) ($resumoCliente['cashback_gerado'] ?? 0);
    $saldoCashbackFinal = (float) ($resumoCliente['saldo_cashback_final'] ?? 0);

    if ($saldoCashbackAnterior > 0) {
        $linhasCashback[] = cupom_line('Saldo cashback', 'R$ ' . cupom_money($saldoCashbackAnterior), $larguraCupom);
    }

    if ($cashbackUsado > 0) {
        $linhasCashback[] = cupom_line('Cashback usado', 'R$ ' . cupom_money($cashbackUsado), $larguraCupom);
    }

    if ($trocoCashback > 0) {
        $linhasCashback[] = cupom_line('Troco cashback', 'R$ ' . cupom_money($trocoCashback), $larguraCupom);
    }

    if ($cashbackGerado > 0) {
        $linhasCashback[] = cupom_line('Cashback gerado', 'R$ ' . cupom_money($cashbackGerado), $larguraCupom);
    }

    if ($saldoCashbackFinal > 0) {
        $linhasCashback[] = cupom_line('Saldo Atual de cashback', 'R$ ' . cupom_money($saldoCashbackFinal), $larguraCupom);
    }

    $blocoCliente =
        cupom_line('Cliente', substr((string) $resumoCliente['cliente_nome'], 0, 22), $larguraCupom) . "\n" .
        cupom_line('WhatsApp', substr((string) $resumoCliente['cliente_whatsapp'], 0, 22), $larguraCupom) . "\n" .
        ($linhasCashback ? implode("\n", $linhasCashback) . "\n" : '') .
        str_repeat('-', $larguraCupom) . "\n";
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comprovante - <?= htmlspecialchars($comandaCodigo, ENT_QUOTES, 'UTF-8') ?></title>
    <style>
        body {
            margin: 0;
            padding: 24px;
            background: #f4f4f4;
            font-family: Consolas, "Courier New", monospace;
        }

        .actions {
            max-width: 360px;
            margin: 0 auto 16px;
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .button {
            padding: 10px 16px;
            border: 0;
            border-radius: 10px;
            background: #333;
            color: #fff;
            text-decoration: none;
            font-weight: bold;
            cursor: pointer;
        }

        .receipt {
            width: 80mm;
            max-width: 100%;
            margin: 0 auto;
            padding: 12px 10px;
            background: #fff;
            color: #000;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.12);
            white-space: pre-wrap;
            font-size: 13px;
            line-height: 1.35;
        }

        @media print {
            body {
                background: #fff;
                padding: 0;
            }

            .actions {
                display: none;
            }

            .receipt {
                width: auto;
                margin: 0;
                box-shadow: none;
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <div class="actions">
        <button class="button" type="button" onclick="window.print()">Imprimir</button>
        <a class="button" href="vender-item.php">Nova venda</a>
    </div>

    <pre class="receipt"><?=
htmlspecialchars(
str_repeat('=', $larguraCupom) . "\n" .
cupom_center('BOLOS DA GAL', $larguraCupom) . "\n" .
str_repeat('=', $larguraCupom) . "\n" .
'Cnpj 61.189.063/0001-22' . "\n" .
'Cep 03026-001' . "\n" .
'Rua Silva Teles, 671' . "\n" .
str_repeat('-', $larguraCupom) . "\n" .
cupom_date($primeiraVenda['data_hora_venda']) . "\n\n" .
$blocoCliente .
'Ref  Descricao         Qtd   Pre   Sub' . "\n" .
implode("\n", $linhasItens) . "\n\n" .
cupom_line('Total dos produtos', 'R$ ' . cupom_money($totalProdutos), $larguraCupom) . "\n" .
cupom_line($formaPagamentoLabel, 'R$ ' . cupom_money((float) ($pagamentoTemDinheiro ? $primeiraVenda['valor_recebido'] : $totalProdutos)), $larguraCupom) . "\n" .
(
    $pagamentoTemDinheiro
        ? cupom_line('Troco', 'R$ ' . cupom_money((float) $primeiraVenda['valor_troco']), $larguraCupom) . "\n"
        : ''
) .
str_repeat('-', $larguraCupom) . "\n" .
'Chave PIX 11973229865' . "\n" .
'GLAUCIENE FELIX DE SOUSA' . "\n" .
'NUBANK' . "\n" .
str_repeat('-', $larguraCupom) . "\n",
ENT_QUOTES,
'UTF-8'
) ?></pre>

    <script>
        const originalTitle = document.title;

        window.addEventListener('beforeprint', function () {
            document.title = '';
        });

        window.addEventListener('afterprint', function () {
            document.title = originalTitle;
        });

        window.addEventListener('load', function () {
            document.title = '';
            window.print();
            setTimeout(function () {
                document.title = originalTitle;
            }, 1000);
        });
    </script>
</body>
<?php renderIdleLogoutScript(); ?>
</html>
