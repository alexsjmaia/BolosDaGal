<?php
require __DIR__ . '/auth.php';
require __DIR__ . '/db.php';

$dataInicial = trim($_GET['data_inicial'] ?? date('Y-m-d'));
$dataFinal = trim($_GET['data_final'] ?? date('Y-m-d'));
$erro = '';
$registros = [];
$totais = [
    'custo' => 0.0,
    'venda' => 0.0,
    'lucro' => 0.0,
    'despesas' => 0.0,
    'saldo' => 0.0,
];

function formatarDataExtenso(string $data): string
{
    $meses = [
        1 => 'janeiro',
        2 => 'fevereiro',
        3 => 'marco',
        4 => 'abril',
        5 => 'maio',
        6 => 'junho',
        7 => 'julho',
        8 => 'agosto',
        9 => 'setembro',
        10 => 'outubro',
        11 => 'novembro',
        12 => 'dezembro',
    ];

    $timestamp = strtotime($data);

    return date('d', $timestamp) . ' de ' . $meses[(int) date('n', $timestamp)] . ' de ' . date('Y', $timestamp);
}

if ($dataInicial !== '' || $dataFinal !== '') {
    if ($dataInicial === '' || $dataFinal === '') {
        $erro = 'Informe a data inicial e a data final.';
    } elseif ($dataInicial > $dataFinal) {
        $erro = 'A data inicial nao pode ser maior que a data final.';
    } else {
        $stmt = $pdo->prepare(
            'SELECT
                v.codigo_produto,
                v.descricao_produto,
                SUM(v.quantidade) AS qtd_vendida,
                SUM(v.quantidade * COALESCE(v.preco_custo_unitario, 0)) AS valor_total_custo,
                SUM(v.valor_total - COALESCE(v.cashback_utilizado_item, 0)) AS valor_total_venda,
                SUM((v.valor_total - COALESCE(v.cashback_utilizado_item, 0)) - (v.quantidade * COALESCE(v.preco_custo_unitario, 0))) AS lucro_bruto,
                GROUP_CONCAT(DISTINCT NULLIF(v.observacao, "") SEPARATOR " | ") AS observacao
             FROM vendas v
             WHERE v.data_hora_venda >= :data_inicial
               AND v.data_hora_venda < DATE_ADD(:data_final, INTERVAL 1 DAY)
             GROUP BY v.codigo_produto, v.descricao_produto
             ORDER BY qtd_vendida DESC, valor_total_venda DESC, v.descricao_produto ASC'
        );

        $stmt->execute([
            'data_inicial' => $dataInicial,
            'data_final' => $dataFinal,
        ]);

        $registros = $stmt->fetchAll();

        foreach ($registros as $registro) {
            $totais['custo'] += (float) $registro['valor_total_custo'];
            $totais['venda'] += (float) $registro['valor_total_venda'];
            $totais['lucro'] += (float) $registro['lucro_bruto'];
        }

        $stmtDespesas = $pdo->prepare(
            'SELECT COALESCE(SUM(valor_despesa), 0) AS total_despesas
             FROM despesas
             WHERE data_despesa >= :data_inicial
               AND data_despesa <= :data_final'
        );

        $stmtDespesas->execute([
            'data_inicial' => $dataInicial,
            'data_final' => $dataFinal,
        ]);

        $totais['despesas'] = (float) $stmtDespesas->fetchColumn();
        $totais['saldo'] = $totais['venda'] - $totais['despesas'];
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatorio de Itens Mais Vendidos</title>
    <style>
        :root {
            --primary: #d99aa5;
            --primary-dark: #c77d89;
            --secondary: #7d635c;
            --text: #4d3e39;
            --muted: #8a7670;
            --border: #f1cfd6;
            --shadow: rgba(125, 99, 92, 0.18);
            --error-bg: #fff0f1;
            --error-text: #9f2d20;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            padding: 24px;
            font-family: Georgia, "Times New Roman", serif;
            background:
                radial-gradient(circle at top, #fbe1e7 0%, transparent 34%),
                linear-gradient(135deg, #fffafb 0%, #f8eef1 100%);
            color: var(--text);
        }

        .layout {
            width: min(1200px, 100%);
            margin: 0 auto;
            display: grid;
            gap: 24px;
        }

        .card {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 28px;
            padding: 30px;
            box-shadow: 0 24px 60px var(--shadow);
        }

        h1 {
            margin-top: 0;
            color: var(--secondary);
        }

        .lead {
            color: var(--muted);
            margin-bottom: 22px;
        }

        .grid {
            display: grid;
            gap: 18px;
        }

        @media (min-width: 700px) {
            .grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: var(--secondary);
        }

        input {
            width: 100%;
            padding: 14px 16px;
            border: 1px solid var(--border);
            border-radius: 12px;
            font-size: 1rem;
        }

        .actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 24px;
        }

        .button {
            padding: 14px 18px;
            border: 0;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: #fff;
            text-decoration: none;
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
        }

        .button.secondary {
            background: #fff;
            color: var(--secondary);
            border: 1px solid var(--border);
        }

        .erro {
            margin-top: 16px;
            padding: 12px 14px;
            border-radius: 12px;
            background: var(--error-bg);
            color: var(--error-text);
        }

        .negative {
            color: var(--error-text);
            font-weight: bold;
        }

        .subtitle {
            margin: 0 0 20px;
            color: var(--muted);
        }

        .table-wrap {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 12px 10px;
            border-bottom: 1px solid var(--border);
            text-align: left;
            vertical-align: top;
        }

        th {
            color: var(--secondary);
            white-space: nowrap;
        }

        td.money {
            white-space: nowrap;
        }

        tfoot td {
            font-weight: bold;
            color: var(--secondary);
            background: #fff7f8;
        }

        @media print {
            body {
                background: #fff;
                padding: 0;
            }

            .no-print {
                display: none !important;
            }

            .layout,
            .card {
                width: 100%;
                margin: 0;
                box-shadow: none;
                border: 0;
                border-radius: 0;
                padding: 0;
            }

            table {
                font-size: 12px;
            }
        }
    </style>
</head>
<body>
    <main class="layout">
        <section class="card no-print">
            <h1>Relatorio de Itens Mais Vendidos</h1>
            <p class="lead">Informe a data inicial e a data final para ver o ranking dos itens mais vendidos no periodo.</p>

            <form method="get">
                <div class="grid">
                    <div>
                        <label for="data_inicial">Data inicial</label>
                        <input type="date" id="data_inicial" name="data_inicial" value="<?= htmlspecialchars($dataInicial, ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>

                    <div>
                        <label for="data_final">Data final</label>
                        <input type="date" id="data_final" name="data_final" value="<?= htmlspecialchars($dataFinal, ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>
                </div>

                <div class="actions">
                    <button class="button" type="submit">Gerar relatorio</button>
                    <a class="button secondary" href="dashboard.php">Voltar ao menu</a>
                </div>
            </form>

            <?php if ($erro !== ''): ?>
                <div class="erro"><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
        </section>

        <?php if ($dataInicial !== '' && $dataFinal !== '' && $erro === ''): ?>
            <section class="card">
                <h1>Relatorio de Itens Mais Vendidos por periodo</h1>
                <p class="subtitle">De <?= htmlspecialchars(formatarDataExtenso($dataInicial), ENT_QUOTES, 'UTF-8') ?> ate <?= htmlspecialchars(formatarDataExtenso($dataFinal), ENT_QUOTES, 'UTF-8') ?></p>

                <div class="actions no-print" style="margin-bottom: 20px;">
                    <button class="button" type="button" onclick="window.print()">Imprimir relatorio</button>
                </div>

                <?php if ($registros): ?>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Codigo do item</th>
                                    <th>Descricao</th>
                                    <th>Observacao</th>
                                    <th>Qtd vendida</th>
                                    <th>Valor total do custo</th>
                                    <th>Valor total de venda</th>
                                    <th>Lucro venda - custo - cashback</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($registros as $registro): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($registro['codigo_produto'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($registro['descricao_produto'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($registro['observacao'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars((string) (int) round((float) $registro['qtd_vendida']), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="money">R$ <?= htmlspecialchars(number_format((float) $registro['valor_total_custo'], 2, ',', '.'), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="money">R$ <?= htmlspecialchars(number_format((float) $registro['valor_total_venda'], 2, ',', '.'), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="money">R$ <?= htmlspecialchars(number_format((float) $registro['lucro_bruto'], 2, ',', '.'), ENT_QUOTES, 'UTF-8') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="4">Totais gerais</td>
                                    <td class="money">R$ <?= htmlspecialchars(number_format($totais['custo'], 2, ',', '.'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="money">R$ <?= htmlspecialchars(number_format($totais['venda'], 2, ',', '.'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="money">R$ <?= htmlspecialchars(number_format($totais['lucro'], 2, ',', '.'), ENT_QUOTES, 'UTF-8') ?></td>
                                </tr>
                                <tr>
                                    <td colspan="5">Total das despesas do periodo</td>
                                    <td class="money">R$ <?= htmlspecialchars(number_format($totais['despesas'], 2, ',', '.'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td colspan="5">Saldo do periodo (faturamento - despesas)</td>
                                    <td class="money<?= $totais['saldo'] < 0 ? ' negative' : '' ?>">R$ <?= htmlspecialchars(number_format($totais['saldo'], 2, ',', '.'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="subtitle">Nenhuma venda foi encontrada no periodo informado.</p>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </main>
</body>
<?php renderIdleLogoutScript(); ?>
</html>


