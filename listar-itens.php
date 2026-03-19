<?php
require __DIR__ . '/auth.php';
require __DIR__ . '/db.php';

$stmt = $pdo->query(
    'SELECT
        codigo_produto,
        nome_produto,
        preco_custo,
        preco_venda,
        (preco_venda - preco_custo) AS lucro_bruto,
        CASE
            WHEN preco_custo > 0 THEN preco_venda / preco_custo
            ELSE 0
        END AS markup
     FROM itens
     ORDER BY lucro_bruto DESC, nome_produto ASC'
);
$itens = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listar Itens Cadastrados</title>
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
            width: min(1180px, 100%);
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
    </style>
</head>
<body>
    <main class="layout">
        <section class="card">
            <h1>Listar Itens Cadastrados</h1>
            <p class="lead">Veja abaixo os itens cadastrados com preco de custo, preco de venda e markup.</p>

            <div class="actions">
                <a class="button" href="dashboard.php">Voltar ao menu</a>
            </div>

            <?php if ($itens): ?>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Codigo</th>
                                <th>Descricao</th>
                                <th>Preco de custo</th>
                                <th>Preco de venda</th>
                                <th>Lucro bruto</th>
                                <th>Markup</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($itens as $item): ?>
                                <?php
                                $precoCusto = (float) $item['preco_custo'];
                                $precoVenda = (float) $item['preco_venda'];
                                $lucroBruto = (float) $item['lucro_bruto'];
                                $markup = (float) $item['markup'];
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['codigo_produto'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($item['nome_produto'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="money">R$ <?= htmlspecialchars(number_format($precoCusto, 2, ',', '.'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="money">R$ <?= htmlspecialchars(number_format($precoVenda, 2, ',', '.'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="money">R$ <?= htmlspecialchars(number_format($lucroBruto, 2, ',', '.'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="money"><?= htmlspecialchars(number_format($markup, 2, ',', '.'), ENT_QUOTES, 'UTF-8') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="lead">Nenhum item cadastrado no momento.</p>
            <?php endif; ?>
        </section>
    </main>
</body>
<?php renderIdleLogoutScript(); ?>
</html>
