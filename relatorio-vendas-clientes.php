<?php
require __DIR__ . '/auth.php';
require __DIR__ . '/db.php';

$clienteId = (int) ($_GET['cliente_id'] ?? 0);
$erro = '';
$registros = [];
$clientes = [];
$clienteSelecionado = null;

$stmtClientes = $pdo->query(
    'SELECT id, nome, whatsapp
     FROM clientes
     ORDER BY nome ASC, whatsapp ASC'
);
$clientes = $stmtClientes->fetchAll();

if ($clienteId > 0) {
    $stmtClienteSelecionado = $pdo->prepare(
        'SELECT id, nome, whatsapp
         FROM clientes
         WHERE id = :id
         LIMIT 1'
    );
    $stmtClienteSelecionado->execute(['id' => $clienteId]);
    $clienteSelecionado = $stmtClienteSelecionado->fetch();

    if (!$clienteSelecionado) {
        $erro = 'Cliente nao encontrado para gerar o relatorio.';
    } else {
        $stmt = $pdo->prepare(
            'SELECT
                cliente_nome,
                cliente_whatsapp,
                sabor_bolo,
                quantidade,
                data_compra,
                cashback_acumulado
             FROM vendas_clientes
             WHERE cliente_whatsapp = :cliente_whatsapp
             ORDER BY data_compra DESC, id DESC'
        );
        $stmt->execute(['cliente_whatsapp' => $clienteSelecionado['whatsapp']]);
        $registros = $stmt->fetchAll();
    }
}

$totais = [
    'quantidade' => 0.0,
    'cashback' => 0.0,
];

foreach ($registros as $registro) {
    $totais['quantidade'] += (float) $registro['quantidade'];
    $totais['cashback'] += (float) $registro['cashback_acumulado'];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatorio de Vendas por Cliente</title>
    <style>
        :root {
            --primary: #d99aa5;
            --primary-dark: #c77d89;
            --secondary: #7d635c;
            --text: #4d3e39;
            --muted: #8a7670;
            --border: #f1cfd6;
            --shadow: rgba(125, 99, 92, 0.18);
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
            width: min(1280px, 100%);
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

        .actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 22px;
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

        .grid {
            display: grid;
            gap: 18px;
        }

        @media (min-width: 760px) {
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

        select {
            width: 100%;
            padding: 14px 16px;
            border: 1px solid var(--border);
            border-radius: 12px;
            font-size: 1rem;
        }

        .erro {
            margin-top: 16px;
            padding: 12px 14px;
            border-radius: 12px;
            background: #fff0f1;
            color: #9f2d20;
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
            <h1>Relatorio de Vendas por Cliente</h1>
            <p class="lead">Selecione o cliente para gerar e imprimir o relatorio de vendas.</p>

            <form method="get">
                <div class="grid">
                    <div>
                        <label for="cliente_id">Cliente</label>
                        <select id="cliente_id" name="cliente_id" required>
                            <option value="">Selecione um cliente</option>
                            <?php foreach ($clientes as $cliente): ?>
                                <option value="<?= (int) $cliente['id'] ?>" <?= $clienteId === (int) $cliente['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cliente['nome'] . ' - ' . $cliente['whatsapp'], ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
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

        <?php if ($clienteId > 0 && $erro === ''): ?>
            <section class="card">
                <h1>Relatorio de Vendas por Cliente</h1>
                <p class="lead">
                    Cliente:
                    <strong><?= htmlspecialchars(($clienteSelecionado['nome'] ?? '') . ' - ' . ($clienteSelecionado['whatsapp'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
                </p>

                <div class="actions no-print">
                    <button class="button" type="button" onclick="window.print()">Imprimir relatorio</button>
                </div>

                <?php if ($registros): ?>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Whatsapp</th>
                                <th>Sabor do bolo</th>
                                <th>Quantidade</th>
                                <th>Data da compra</th>
                                <th>Valor do cashback acumulado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($registros as $registro): ?>
                                <tr>
                                    <td><?= htmlspecialchars($registro['cliente_nome'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($registro['cliente_whatsapp'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($registro['sabor_bolo'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars(number_format((float) $registro['quantidade'], 2, ',', '.'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars(date('d/m/Y H:i:s', strtotime($registro['data_compra'])), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="money">R$ <?= htmlspecialchars(number_format((float) $registro['cashback_acumulado'], 2, ',', '.'), ENT_QUOTES, 'UTF-8') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3">Totais gerais</td>
                                <td><?= htmlspecialchars(number_format($totais['quantidade'], 2, ',', '.'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td></td>
                                <td class="money">R$ <?= htmlspecialchars(number_format($totais['cashback'], 2, ',', '.'), ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <?php else: ?>
                    <p class="lead">Nenhuma venda foi registrada para este cliente.</p>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </main>
</body>
<?php renderIdleLogoutScript(); ?>
</html>
