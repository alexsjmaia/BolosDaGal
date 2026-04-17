<?php
require __DIR__ . '/auth.php';
require __DIR__ . '/db.php';

$sucesso = $_SESSION['pedidos_sucesso'] ?? '';
$erro = $_SESSION['pedidos_erro'] ?? '';
unset($_SESSION['pedidos_sucesso'], $_SESSION['pedidos_erro']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = trim((string) ($_POST['acao'] ?? ''));
    $pedidoId = (int) ($_POST['pedido_id'] ?? 0);

    if ($acao === 'marcar_entregue' && $pedidoId > 0) {
        try {
            $stmtUpdate = $pdo->prepare(
                'UPDATE pedidos_clientes
                 SET status_pedido = :status
                 WHERE id = :id
                   AND status_pedido = :status_pendente'
            );
            $stmtUpdate->execute([
                'status' => 'Entregue',
                'id' => $pedidoId,
                'status_pendente' => 'Pendente',
            ]);

            if ($stmtUpdate->rowCount() > 0) {
                $_SESSION['pedidos_sucesso'] = 'Pedido marcado como entregue.';
            } else {
                $_SESSION['pedidos_erro'] = 'Nao foi possivel atualizar o pedido. Ele pode ja estar entregue.';
            }
        } catch (PDOException $e) {
            $_SESSION['pedidos_erro'] = 'Erro ao marcar pedido como entregue.';
        }

        header('Location: listar-pedidos-clientes.php');
        exit;
    }
}

$stmt = $pdo->query(
    'SELECT
        p.id,
        c.nome AS cliente_nome,
        c.whatsapp AS cliente_whatsapp,
        p.sabor_bolo,
        p.quantidade,
        p.preco_unitario,
        p.valor_total,
        p.data_hora_entrega,
        p.status_pedido,
        p.criado_em
     FROM pedidos_clientes p
     INNER JOIN clientes c ON c.id = p.cliente_id
     WHERE p.status_pedido = "Pendente"
     ORDER BY p.data_hora_entrega ASC, p.id ASC'
);
$pedidos = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedidos Feitos</title>
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
            width: min(1300px, 100%);
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

        .button.small {
            padding: 8px 12px;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <main class="layout">
        <section class="card">
            <h1>Pedidos Feitos</h1>
            <p class="lead">Acompanhe os pedidos pendentes e marque como entregue quando finalizados.</p>

            <div class="actions">
                <a class="button" href="dashboard.php">Voltar ao menu</a>
            </div>

            <?php if ($erro !== ''): ?>
                <div class="alert error"><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <?php if ($sucesso !== ''): ?>
                <div class="alert success"><?= htmlspecialchars($sucesso, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <?php if ($pedidos): ?>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Cliente</th>
                                <th>Whatsapp</th>
                                <th>Sabor</th>
                                <th>Quantidade</th>
                                <th>Preco Unitario</th>
                                <th>Valor Total</th>
                                <th>Entrega</th>
                                <th>Status</th>
                                <th>Criado em</th>
                                <th>Acao</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pedidos as $pedido): ?>
                                <tr>
                                    <td><?= (int) $pedido['id'] ?></td>
                                    <td><?= htmlspecialchars((string) $pedido['cliente_nome'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars((string) $pedido['cliente_whatsapp'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars((string) $pedido['sabor_bolo'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= (int) $pedido['quantidade'] ?></td>
                                    <td>R$ <?= htmlspecialchars(number_format((float) $pedido['preco_unitario'], 2, ',', '.'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>R$ <?= htmlspecialchars(number_format((float) $pedido['valor_total'], 2, ',', '.'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime((string) $pedido['data_hora_entrega'])), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars((string) $pedido['status_pedido'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime((string) $pedido['criado_em'])), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>
                                        <form method="post">
                                            <input type="hidden" name="acao" value="marcar_entregue">
                                            <input type="hidden" name="pedido_id" value="<?= (int) $pedido['id'] ?>">
                                            <button class="button small" type="submit">Marcar como entregue</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="lead">Nenhum pedido pendente no momento.</p>
            <?php endif; ?>
        </section>
    </main>
</body>
<?php renderIdleLogoutScript(); ?>
</html>
