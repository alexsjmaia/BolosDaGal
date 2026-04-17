<?php
require __DIR__ . '/auth.php';
require __DIR__ . '/db.php';

$sucesso = $_SESSION['itens_catalogo_sucesso'] ?? '';
$erro = $_SESSION['itens_catalogo_erro'] ?? '';
unset($_SESSION['itens_catalogo_sucesso'], $_SESSION['itens_catalogo_erro']);

$ordenacoesPermitidas = [
    'codigo_produto' => 'codigo_produto',
    'nome_produto' => 'nome_produto',
    'preco_custo' => 'preco_custo',
    'preco_venda' => 'preco_venda',
    'lucro_bruto' => 'lucro_bruto',
    'markup' => 'markup',
];

$ordenarPor = $_GET['ordenar_por'] ?? 'lucro_bruto';
$direcao = strtolower($_GET['direcao'] ?? 'desc');

if (!isset($ordenacoesPermitidas[$ordenarPor])) {
    $ordenarPor = 'lucro_bruto';
}

if ($direcao !== 'asc' && $direcao !== 'desc') {
    $direcao = 'desc';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = trim((string) ($_POST['acao'] ?? ''));
    $itemId = (int) ($_POST['item_id'] ?? 0);
    $ordenarPorRetorno = (string) ($_POST['ordenar_por'] ?? 'lucro_bruto');
    $direcaoRetorno = strtolower((string) ($_POST['direcao'] ?? 'desc'));

    if (!isset($ordenacoesPermitidas[$ordenarPorRetorno])) {
        $ordenarPorRetorno = 'lucro_bruto';
    }

    if ($direcaoRetorno !== 'asc' && $direcaoRetorno !== 'desc') {
        $direcaoRetorno = 'desc';
    }

    if ($acao === 'alternar_catalogo' && $itemId > 0) {
        try {
            $stmtUpdate = $pdo->prepare(
                'UPDATE itens
                 SET mostrar_catalogo = CASE
                    WHEN mostrar_catalogo = 1 THEN 0
                    ELSE 1
                 END
                 WHERE id = :id'
            );
            $stmtUpdate->execute(['id' => $itemId]);

            if ($stmtUpdate->rowCount() > 0) {
                $_SESSION['itens_catalogo_sucesso'] = 'Visibilidade do item no catalogo atualizada com sucesso.';
            } else {
                $_SESSION['itens_catalogo_erro'] = 'Nao foi possivel atualizar este item.';
            }
        } catch (PDOException $e) {
            $_SESSION['itens_catalogo_erro'] = 'Erro ao atualizar a visibilidade do item no catalogo.';
        }

        header('Location: listar-itens.php?ordenar_por=' . urlencode($ordenarPorRetorno) . '&direcao=' . urlencode($direcaoRetorno));
        exit;
    }
}

$stmt = $pdo->query(
    'SELECT
        id,
        codigo_produto,
        nome_produto,
        mostrar_catalogo,
        preco_custo,
        preco_venda,
        (preco_venda - preco_custo) AS lucro_bruto,
        CASE
            WHEN preco_custo > 0 THEN preco_venda / preco_custo
            ELSE 0
        END AS markup
     FROM itens
     ORDER BY ' . $ordenacoesPermitidas[$ordenarPor] . ' ' . strtoupper($direcao) . ', nome_produto ASC'
);
$itens = $stmt->fetchAll();

function linkOrdenacao(string $colunaAtual, string $ordenarPor, string $direcao): string
{
    $novaDirecao = 'asc';

    if ($colunaAtual === $ordenarPor && $direcao === 'asc') {
        $novaDirecao = 'desc';
    }

    return 'listar-itens.php?ordenar_por=' . urlencode($colunaAtual) . '&direcao=' . urlencode($novaDirecao);
}

function indicadorOrdenacao(string $colunaAtual, string $ordenarPor, string $direcao): string
{
    if ($colunaAtual !== $ordenarPor) {
        return '';
    }

    return $direcao === 'asc' ? ' ▲' : ' ▼';
}
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

        .button.secondary {
            background: #e9d8dc;
            color: #5c4741;
        }

        .button.small {
            padding: 8px 12px;
            font-size: 0.9rem;
        }

        .status-catalogo {
            font-weight: bold;
        }

        .status-catalogo.visivel {
            color: #2b6f44;
        }

        .status-catalogo.oculto {
            color: #9f2d20;
        }

        .alert {
            margin-bottom: 18px;
            padding: 12px 14px;
            border-radius: 12px;
        }

        .alert.error {
            background: #fff0f1;
            color: #9f2d20;
        }

        .alert.success {
            background: #eef9f1;
            color: #2b6f44;
        }

        .sort-info {
            margin-bottom: 18px;
            color: var(--muted);
        }

        .sort-link {
            color: inherit;
            text-decoration: none;
        }

        .sort-link:hover {
            text-decoration: underline;
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
            <p class="sort-info">Clique no titulo de qualquer coluna para organizar a visualizacao.</p>

            <div class="actions">
                <a class="button" href="dashboard.php">Voltar ao menu</a>
            </div>

            <?php if ($erro !== ''): ?>
                <div class="alert error"><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <?php if ($sucesso !== ''): ?>
                <div class="alert success"><?= htmlspecialchars($sucesso, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <?php if ($itens): ?>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th><a class="sort-link" href="<?= htmlspecialchars(linkOrdenacao('codigo_produto', $ordenarPor, $direcao), ENT_QUOTES, 'UTF-8') ?>">Codigo<?= htmlspecialchars(indicadorOrdenacao('codigo_produto', $ordenarPor, $direcao), ENT_QUOTES, 'UTF-8') ?></a></th>
                                <th><a class="sort-link" href="<?= htmlspecialchars(linkOrdenacao('nome_produto', $ordenarPor, $direcao), ENT_QUOTES, 'UTF-8') ?>">Descricao<?= htmlspecialchars(indicadorOrdenacao('nome_produto', $ordenarPor, $direcao), ENT_QUOTES, 'UTF-8') ?></a></th>
                                <th><a class="sort-link" href="<?= htmlspecialchars(linkOrdenacao('preco_custo', $ordenarPor, $direcao), ENT_QUOTES, 'UTF-8') ?>">Preco de custo<?= htmlspecialchars(indicadorOrdenacao('preco_custo', $ordenarPor, $direcao), ENT_QUOTES, 'UTF-8') ?></a></th>
                                <th><a class="sort-link" href="<?= htmlspecialchars(linkOrdenacao('preco_venda', $ordenarPor, $direcao), ENT_QUOTES, 'UTF-8') ?>">Preco de venda<?= htmlspecialchars(indicadorOrdenacao('preco_venda', $ordenarPor, $direcao), ENT_QUOTES, 'UTF-8') ?></a></th>
                                <th><a class="sort-link" href="<?= htmlspecialchars(linkOrdenacao('lucro_bruto', $ordenarPor, $direcao), ENT_QUOTES, 'UTF-8') ?>">Lucro bruto<?= htmlspecialchars(indicadorOrdenacao('lucro_bruto', $ordenarPor, $direcao), ENT_QUOTES, 'UTF-8') ?></a></th>
                                <th><a class="sort-link" href="<?= htmlspecialchars(linkOrdenacao('markup', $ordenarPor, $direcao), ENT_QUOTES, 'UTF-8') ?>">Markup<?= htmlspecialchars(indicadorOrdenacao('markup', $ordenarPor, $direcao), ENT_QUOTES, 'UTF-8') ?></a></th>
                                <th>Catalogo</th>
                                <th>Acao</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($itens as $item): ?>
                                <?php
                                $precoCusto = (float) $item['preco_custo'];
                                $precoVenda = (float) $item['preco_venda'];
                                $lucroBruto = (float) $item['lucro_bruto'];
                                $markup = (float) $item['markup'];
                                $mostrarCatalogo = (int) ($item['mostrar_catalogo'] ?? 1) === 1;
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['codigo_produto'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($item['nome_produto'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="money">R$ <?= htmlspecialchars(number_format($precoCusto, 2, ',', '.'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="money">R$ <?= htmlspecialchars(number_format($precoVenda, 2, ',', '.'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="money">R$ <?= htmlspecialchars(number_format($lucroBruto, 2, ',', '.'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="money"><?= htmlspecialchars(number_format($markup, 2, ',', '.'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>
                                        <?php if ($mostrarCatalogo): ?>
                                            <span class="status-catalogo visivel">Visivel</span>
                                        <?php else: ?>
                                            <span class="status-catalogo oculto">Oculto</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <form method="post">
                                            <input type="hidden" name="acao" value="alternar_catalogo">
                                            <input type="hidden" name="item_id" value="<?= (int) $item['id'] ?>">
                                            <input type="hidden" name="ordenar_por" value="<?= htmlspecialchars($ordenarPor, ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="direcao" value="<?= htmlspecialchars($direcao, ENT_QUOTES, 'UTF-8') ?>">
                                            <button class="button secondary small" type="submit">
                                                <?= $mostrarCatalogo ? 'Desabilitar' : 'Habilitar' ?>
                                            </button>
                                        </form>
                                    </td>
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
