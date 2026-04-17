<?php
require __DIR__ . '/auth.php';
require __DIR__ . '/db.php';

$erro = $_SESSION['campanha_cashback_erro'] ?? '';
$sucesso = $_SESSION['campanha_cashback_sucesso'] ?? '';
$dados = $_SESSION['campanha_cashback_dados'] ?? [];

unset($_SESSION['campanha_cashback_erro'], $_SESSION['campanha_cashback_sucesso'], $_SESSION['campanha_cashback_dados']);

$stmtAtual = $pdo->query(
    'SELECT percentual_cashback, criado_em
     FROM campanhas_cashback
     ORDER BY id DESC
     LIMIT 1'
);
$campanhaAtual = $stmtAtual->fetch();

$stmtHistorico = $pdo->query(
    'SELECT percentual_cashback, criado_em
     FROM campanhas_cashback
     ORDER BY id DESC
     LIMIT 20'
);
$historico = $stmtHistorico->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campanhas de Cashback</title>
    <style>
        :root {
            --primary: #d99aa5;
            --primary-dark: #c77d89;
            --secondary: #7d635c;
            --text: #4d3e39;
            --muted: #8a7670;
            --border: #f1cfd6;
            --shadow: rgba(125, 99, 92, 0.18);
            --success-bg: #eef9f1;
            --success-text: #2b6f44;
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
            width: min(980px, 100%);
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

        h1, h2 {
            margin-top: 0;
            color: var(--secondary);
        }

        .lead {
            color: var(--muted);
            margin-bottom: 22px;
        }

        .alert {
            margin-bottom: 18px;
            padding: 12px 14px;
            border-radius: 12px;
        }

        .alert.error {
            background: var(--error-bg);
            color: var(--error-text);
        }

        .alert.success {
            background: var(--success-bg);
            color: var(--success-text);
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

        .atual {
            margin-bottom: 18px;
            padding: 14px 16px;
            border: 1px solid var(--border);
            border-radius: 12px;
            background: #fff7f8;
            color: var(--secondary);
            font-weight: bold;
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
        }

        th {
            color: var(--secondary);
        }
    </style>
</head>
<body>
    <main class="layout">
        <section class="card">
            <h1>Campanhas de Cashback</h1>
            <p class="lead">Cadastre o percentual de cashback para uso em campanhas promocionais.</p>

            <?php if ($erro !== ''): ?>
                <div class="alert error"><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <?php if ($sucesso !== ''): ?>
                <div class="alert success"><?= htmlspecialchars($sucesso, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <div class="atual">
                Percentual vigente:
                <?php if ($campanhaAtual): ?>
                    <?= htmlspecialchars(number_format((float) $campanhaAtual['percentual_cashback'], 2, ',', '.'), ENT_QUOTES, 'UTF-8') ?>%
                    (desde <?= htmlspecialchars(date('d/m/Y H:i:s', strtotime($campanhaAtual['criado_em'])), ENT_QUOTES, 'UTF-8') ?>)
                <?php else: ?>
                    Nenhum percentual cadastrado.
                <?php endif; ?>
            </div>

            <form action="salvar-campanha-cashback.php" method="post">
                <div>
                    <label for="percentual_cashback">Percentual de cashback (%)</label>
                    <input type="text" id="percentual_cashback" name="percentual_cashback" value="<?= htmlspecialchars($dados['percentual_cashback'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Ex.: 10,00" required>
                </div>

                <div class="actions">
                    <button class="button" type="submit">Salvar percentual</button>
                    <a class="button secondary" href="dashboard.php">Voltar ao menu</a>
                </div>
            </form>
        </section>

        <section class="card">
            <h2>Historico de campanhas</h2>

            <?php if ($historico): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Percentual</th>
                            <th>Data de cadastro</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($historico as $item): ?>
                            <tr>
                                <td><?= htmlspecialchars(number_format((float) $item['percentual_cashback'], 2, ',', '.'), ENT_QUOTES, 'UTF-8') ?>%</td>
                                <td><?= htmlspecialchars(date('d/m/Y H:i:s', strtotime($item['criado_em'])), ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="lead">Nenhuma campanha cadastrada ainda.</p>
            <?php endif; ?>
        </section>
    </main>
</body>
<?php renderIdleLogoutScript(); ?>
</html>
