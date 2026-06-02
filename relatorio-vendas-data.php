<?php
require __DIR__ . '/auth.php';
require __DIR__ . '/db.php';

$mensagem = $_SESSION['relatorio_vendas_data_sucesso'] ?? '';
$erroSessao = $_SESSION['relatorio_vendas_data_erro'] ?? '';
unset($_SESSION['relatorio_vendas_data_sucesso'], $_SESSION['relatorio_vendas_data_erro']);

function recalcFidelidadeCliente(PDO $pdo, int $clienteId): void
{
    $stmtCliente = $pdo->prepare('SELECT whatsapp FROM clientes WHERE id = :id LIMIT 1');
    $stmtCliente->execute(['id' => $clienteId]);
    $cliente = $stmtCliente->fetch();
    if (!$cliente) {
        return;
    }

    $stmtRegra = $pdo->query(
        'SELECT quantidade_para_ganhar, criado_em
         FROM campanhas_bolos_gratis
         ORDER BY id DESC
         LIMIT 1'
    );
    $regra = $stmtRegra->fetch();
    $quantidadeParaGanhar = (int) ($regra['quantidade_para_ganhar'] ?? 0);
    $dataInicioRegra = (string) ($regra['criado_em'] ?? '');

    if ($quantidadeParaGanhar <= 0 || $dataInicioRegra === '') {
        $stmtReset = $pdo->prepare(
            'UPDATE clientes
             SET fidelidade_quantidade_acumulada = 0.00,
                 bolos_gratis_disponiveis = 0
             WHERE id = :id'
        );
        $stmtReset->execute(['id' => $clienteId]);
        return;
    }

    $stmtSoma = $pdo->prepare(
        'SELECT COALESCE(SUM(quantidade), 0)
         FROM vendas_clientes vc
         INNER JOIN itens i
            ON i.id = vc.item_id
           AND LOWER(TRIM(COALESCE(i.categoria, \'\'))) = \'bolos\'
         WHERE vc.cliente_whatsapp = :cliente_whatsapp
           AND vc.data_compra >= :data_inicio'
    );
    $stmtSoma->execute([
        'cliente_whatsapp' => $cliente['whatsapp'],
        'data_inicio' => $dataInicioRegra,
    ]);

    $quantidadeTotal = (float) $stmtSoma->fetchColumn();
    $brindes = 0;
    $acumulado = 0.0;

    if ($quantidadeTotal > 0) {
        $brindes = (int) floor($quantidadeTotal / $quantidadeParaGanhar);
        $acumulado = $quantidadeTotal - ($brindes * $quantidadeParaGanhar);
    }

    $stmtUpdate = $pdo->prepare(
        'UPDATE clientes
         SET fidelidade_quantidade_acumulada = :acumulado,
             bolos_gratis_disponiveis = :brindes
         WHERE id = :id'
    );
    $stmtUpdate->execute([
        'acumulado' => number_format($acumulado, 2, '.', ''),
        'brindes' => $brindes,
        'id' => $clienteId,
    ]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = trim((string) ($_POST['acao'] ?? ''));
    $comandaCodigo = trim((string) ($_POST['comanda_codigo'] ?? ''));
    $senha = trim((string) ($_POST['senha_cancelamento'] ?? ''));
    $dataRedirect = trim((string) ($_POST['data_venda'] ?? date('Y-m-d')));

    if ($acao === 'cancelar_venda') {
        if ($senha !== '801973') {
            $_SESSION['relatorio_vendas_data_erro'] = 'Senha invalida para cancelar a venda.';
            header('Location: relatorio-vendas-data.php?data_venda=' . urlencode($dataRedirect));
            exit;
        }

        if ($comandaCodigo === '') {
            $_SESSION['relatorio_vendas_data_erro'] = 'Comanda invalida para cancelamento.';
            header('Location: relatorio-vendas-data.php?data_venda=' . urlencode($dataRedirect));
            exit;
        }

        try {
            $pdo->beginTransaction();

            $stmtClientesAfetados = $pdo->prepare(
                'SELECT DISTINCT c.id
                 FROM vendas_clientes vc
                 INNER JOIN clientes c ON c.whatsapp = vc.cliente_whatsapp
                 WHERE vc.comanda_codigo = :comanda'
            );
            $stmtClientesAfetados->execute(['comanda' => $comandaCodigo]);
            $clientesAfetados = array_map(
                static fn(array $row): int => (int) $row['id'],
                $stmtClientesAfetados->fetchAll()
            );

            $stmtDeleteVendas = $pdo->prepare('DELETE FROM vendas WHERE comanda_codigo = :comanda');
            $stmtDeleteVendas->execute(['comanda' => $comandaCodigo]);
            $linhasVendas = $stmtDeleteVendas->rowCount();

            if ($linhasVendas <= 0) {
                throw new RuntimeException('Comanda nao encontrada ou ja cancelada.');
            }

            $stmtDeleteHistorico = $pdo->prepare('DELETE FROM vendas_clientes WHERE comanda_codigo = :comanda');
            $stmtDeleteHistorico->execute(['comanda' => $comandaCodigo]);

            foreach ($clientesAfetados as $clienteId) {
                recalcFidelidadeCliente($pdo, $clienteId);
            }

            $pdo->commit();
            $_SESSION['relatorio_vendas_data_sucesso'] = 'Venda da comanda ' . $comandaCodigo . ' cancelada com sucesso.';
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $_SESSION['relatorio_vendas_data_erro'] = 'Nao foi possivel cancelar a venda. ' . $e->getMessage();
        }

        header('Location: relatorio-vendas-data.php?data_venda=' . urlencode($dataRedirect));
        exit;
    }
}

$dataVenda = trim((string) ($_GET['data_venda'] ?? date('Y-m-d')));
$erro = $erroSessao;
$registros = [];

$totais = [
    'quantidade' => 0.0,
    'valor_total' => 0.0,
];

if ($dataVenda !== '') {
    $partes = date_parse_from_format('Y-m-d', $dataVenda);

    if (
        $partes['error_count'] > 0 ||
        $partes['warning_count'] > 0 ||
        !checkdate((int) $partes['month'], (int) $partes['day'], (int) $partes['year'])
    ) {
        $erro = 'Informe uma data valida para gerar o relatorio.';
    } else {
        $stmt = $pdo->prepare(
            'SELECT
                comanda_codigo,
                codigo_produto,
                descricao_produto,
                quantidade,
                preco_unitario,
                valor_total,
                forma_pagamento,
                data_hora_venda,
                usuario_login
             FROM vendas
             WHERE DATE(data_hora_venda) = :data_venda
             ORDER BY data_hora_venda ASC, id ASC'
        );
        $stmt->execute(['data_venda' => $dataVenda]);
        $registros = $stmt->fetchAll();

        foreach ($registros as $item) {
            $totais['quantidade'] += (float) $item['quantidade'];
            $totais['valor_total'] += (float) $item['valor_total'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatorio de Vendas por Data</title>
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

        * { box-sizing: border-box; }

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
            margin-top: 18px;
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

        .erro {
            margin-top: 16px;
            padding: 12px 14px;
            border-radius: 12px;
            background: #fff0f1;
            color: #9f2d20;
        }

        .sucesso {
            margin-top: 16px;
            padding: 12px 14px;
            border-radius: 12px;
            background: #eef9f1;
            color: #2b6f44;
        }

        .cancel-form {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .cancel-button {
            padding: 8px 10px;
            border: 0;
            border-radius: 8px;
            background: #b84f5b;
            color: #fff;
            font-size: 0.85rem;
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
            white-space: nowrap;
        }

        th {
            color: var(--secondary);
        }

        tfoot td {
            font-weight: bold;
            color: var(--secondary);
            background: #fff7f8;
        }
    </style>
</head>
<body>
    <main class="layout">
        <section class="card">
            <h1>Relatorio de Vendas por Data</h1>
            <p class="lead">Escolha uma data para listar todas as vendas registradas no dia.</p>

            <form method="get">
                <div>
                    <label for="data_venda">Data da venda</label>
                    <input type="date" id="data_venda" name="data_venda" value="<?= htmlspecialchars($dataVenda, ENT_QUOTES, 'UTF-8') ?>" required>
                </div>

                <div class="actions">
                    <button class="button" type="submit">Gerar relatorio</button>
                    <a class="button secondary" href="dashboard.php">Voltar ao menu</a>
                </div>
            </form>

            <?php if ($erro !== ''): ?>
                <div class="erro"><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <?php if ($mensagem !== ''): ?>
                <div class="sucesso"><?= htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
        </section>

        <?php if ($erro === ''): ?>
            <section class="card">
                <h1>Vendas de <?= htmlspecialchars(date('d/m/Y', strtotime($dataVenda)), ENT_QUOTES, 'UTF-8') ?></h1>

                <?php if ($registros): ?>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Comanda</th>
                                    <th>Codigo</th>
                                    <th>Descricao</th>
                                    <th>Quantidade</th>
                                    <th>Preco unitario</th>
                                    <th>Valor total</th>
                                    <th>Pagamento</th>
                                    <th>Hora</th>
                                    <th>Usuario</th>
                                    <th>Cancelar</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($registros as $item): ?>
                                    <tr>
                                        <td><?= htmlspecialchars((string) $item['comanda_codigo'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars((string) $item['codigo_produto'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars((string) $item['descricao_produto'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars(number_format((float) $item['quantidade'], 2, ',', '.'), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td>R$ <?= htmlspecialchars(number_format((float) $item['preco_unitario'], 2, ',', '.'), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td>R$ <?= htmlspecialchars(number_format((float) $item['valor_total'], 2, ',', '.'), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars((string) $item['forma_pagamento'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars(date('H:i:s', strtotime((string) $item['data_hora_venda'])), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars((string) ($item['usuario_login'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td>
                                            <form class="cancel-form" method="post" onsubmit="return confirmarCancelamento(this);">
                                                <input type="hidden" name="acao" value="cancelar_venda">
                                                <input type="hidden" name="comanda_codigo" value="<?= htmlspecialchars((string) $item['comanda_codigo'], ENT_QUOTES, 'UTF-8') ?>">
                                                <input type="hidden" name="data_venda" value="<?= htmlspecialchars($dataVenda, ENT_QUOTES, 'UTF-8') ?>">
                                                <input type="hidden" name="senha_cancelamento" value="">
                                                <button class="cancel-button" type="submit">Cancelar venda</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3">Totais do dia</td>
                                    <td><?= htmlspecialchars(number_format($totais['quantidade'], 2, ',', '.'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td></td>
                                    <td>R$ <?= htmlspecialchars(number_format($totais['valor_total'], 2, ',', '.'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td colspan="4"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="lead">Nenhuma venda encontrada para a data selecionada.</p>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </main>
</body>
<script>
    function confirmarCancelamento(form) {
        const confirmar = window.confirm('Deseja realmente cancelar esta comanda?');
        if (!confirmar) {
            return false;
        }

        const senha = window.prompt('Informe a senha para cancelar a venda:');
        if (senha === null || senha.trim() === '') {
            return false;
        }

        const campoSenha = form.querySelector('input[name="senha_cancelamento"]');
        if (!campoSenha) {
            return false;
        }

        campoSenha.value = senha.trim();
        return true;
    }
</script>
<?php renderIdleLogoutScript(); ?>
</html>
