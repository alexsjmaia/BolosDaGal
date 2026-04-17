<?php
require __DIR__ . '/auth.php';
require __DIR__ . '/db.php';

$stmt = $pdo->query(
    'SELECT nome, whatsapp, saldo_cashback
     FROM clientes
     ORDER BY nome ASC, whatsapp ASC'
);
$clientes = $stmt->fetchAll();

function normalizeWhatsApp(string $whatsapp): string
{
    $digits = preg_replace('/\D+/', '', $whatsapp) ?? '';

    if (strlen($digits) === 11) {
        $digits = '55' . $digits;
    }

    return strlen($digits) === 13 ? $digits : '';
}

function buildWhatsAppTextLink(string $whatsapp, float $saldoCashback): string
{
    $normalized = normalizeWhatsApp($whatsapp);
    if ($normalized === '') {
        return '';
    }

    $valor = 'R$ ' . number_format($saldoCashback, 2, ',', '.');
    $mensagem = "Ei, temos uma ótima noticia! Você tem {$valor} em cashback esperando por você em nossa Loja. Que tal aproveitar para garantir aquele bolo delicioso que você esta de olho? Confira nosso cardápio.";

    return 'https://wa.me/' . $normalized . '?text=' . rawurlencode($mensagem);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listar Clientes Cadastrados</title>
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

        .whatsapp-link {
            color: var(--secondary);
            font-weight: bold;
            text-decoration: none;
        }

        .whatsapp-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <main class="layout">
        <section class="card">
            <h1>Listar Clientes Cadastrados</h1>
            <p class="lead">Veja abaixo os clientes cadastrados com WhatsApp e saldo de cashback.</p>

            <div class="actions">
                <a class="button" href="dashboard.php">Voltar ao menu</a>
            </div>

            <?php if ($clientes): ?>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>WhatsApp</th>
                                <th>Saldo de Cash back</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($clientes as $cliente): ?>
                                <?php
                                $saldo = (float) $cliente['saldo_cashback'];
                                $whatsLink = buildWhatsAppTextLink((string) $cliente['whatsapp'], $saldo);
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($cliente['nome'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>
                                        <?php if ($whatsLink !== ''): ?>
                                            <a class="whatsapp-link" href="<?= htmlspecialchars($whatsLink, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer">
                                                <?= htmlspecialchars($cliente['whatsapp'], ENT_QUOTES, 'UTF-8') ?>
                                            </a>
                                        <?php else: ?>
                                            <?= htmlspecialchars($cliente['whatsapp'], ENT_QUOTES, 'UTF-8') ?>
                                        <?php endif; ?>
                                    </td>
                                    <td class="money">R$ <?= htmlspecialchars(number_format($saldo, 2, ',', '.'), ENT_QUOTES, 'UTF-8') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="lead">Nenhum cliente cadastrado no momento.</p>
            <?php endif; ?>
        </section>
    </main>
</body>
<?php renderIdleLogoutScript(); ?>
</html>
