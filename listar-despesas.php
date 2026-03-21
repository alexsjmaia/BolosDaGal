<?php
require __DIR__ . '/auth.php';
require __DIR__ . '/db.php';

$dataInicial = trim($_GET['data_inicial'] ?? date('Y-m-d'));
$dataFinal = trim($_GET['data_final'] ?? date('Y-m-d'));
$erro = '';
$despesas = [];
$totalDespesas = 0.0;

function formatarDataExtensoDespesas(string $data): string
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
            'SELECT data_despesa, valor_despesa, descricao_despesa
             FROM despesas
             WHERE data_despesa >= :data_inicial
               AND data_despesa <= :data_final
             ORDER BY data_despesa DESC, id DESC'
        );

        $stmt->execute([
            'data_inicial' => $dataInicial,
            'data_final' => $dataFinal,
        ]);

        $despesas = $stmt->fetchAll();

        foreach ($despesas as $despesa) {
            $totalDespesas += (float) $despesa['valor_despesa'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listar Despesas</title>
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
            <h1>Listar Despesas</h1>
            <p class="lead">Informe a data inicial e a data final para listar as despesas lancadas no periodo.</p>

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
                    <button class="button" type="submit">Listar despesas</button>
                    <a class="button secondary" href="dashboard.php">Voltar ao menu</a>
                </div>
            </form>

            <?php if ($erro !== ''): ?>
                <div class="erro"><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
        </section>

        <?php if ($dataInicial !== '' && $dataFinal !== '' && $erro === ''): ?>
            <section class="card">
                <h1>Despesas por periodo</h1>
                <p class="subtitle">De <?= htmlspecialchars(formatarDataExtensoDespesas($dataInicial), ENT_QUOTES, 'UTF-8') ?> ate <?= htmlspecialchars(formatarDataExtensoDespesas($dataFinal), ENT_QUOTES, 'UTF-8') ?></p>

                <div class="actions no-print" style="margin-bottom: 20px;">
                    <button class="button" type="button" onclick="window.print()">Imprimir relatorio</button>
                </div>

                <?php if ($despesas): ?>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Valor</th>
                                    <th>Descricao</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($despesas as $despesa): ?>
                                    <tr>
                                        <td><?= htmlspecialchars(date('d/m/Y', strtotime($despesa['data_despesa'])), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="money">R$ <?= htmlspecialchars(number_format((float) $despesa['valor_despesa'], 2, ',', '.'), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= nl2br(htmlspecialchars($despesa['descricao_despesa'], ENT_QUOTES, 'UTF-8')) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="1">Total geral</td>
                                    <td class="money">R$ <?= htmlspecialchars(number_format($totalDespesas, 2, ',', '.'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="subtitle">Nenhuma despesa foi encontrada no periodo informado.</p>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </main>
</body>
<?php renderIdleLogoutScript(); ?>
</html>
