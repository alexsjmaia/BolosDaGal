<?php
require __DIR__ . '/auth.php';
require __DIR__ . '/db.php';

$erro = $_SESSION['despesa_erro'] ?? '';
$sucesso = $_SESSION['despesa_sucesso'] ?? '';
$dados = $_SESSION['despesa_dados'] ?? [];

unset($_SESSION['despesa_erro'], $_SESSION['despesa_sucesso'], $_SESSION['despesa_dados']);

$dataPadrao = $dados['data_despesa'] ?? date('Y-m-d');
$valorPadrao = $dados['valor_despesa'] ?? '';

if ($valorPadrao !== '' && is_numeric(str_replace(',', '.', $valorPadrao))) {
    $valorPadrao = number_format((float) str_replace(',', '.', $valorPadrao), 2, ',', '.');
}

$stmt = $pdo->query(
    'SELECT data_despesa, valor_despesa, descricao_despesa
     FROM despesas
     ORDER BY data_despesa DESC, id DESC
     LIMIT 20'
);
$despesas = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lancar Despesa - Bolos da Gal</title>
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

        .grid {
            display: grid;
            gap: 18px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: var(--secondary);
        }

        input,
        textarea {
            width: 100%;
            padding: 14px 16px;
            border: 1px solid var(--border);
            border-radius: 12px;
            font-size: 1rem;
            font-family: inherit;
        }

        textarea {
            min-height: 120px;
            resize: vertical;
        }

        input:focus,
        textarea:focus {
            outline: 2px solid #f4c7a8;
            border-color: var(--primary);
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
    </style>
</head>
<body>
    <main class="layout">
        <section class="card">
            <h1>Lancar Despesa</h1>
            <p class="lead">Informe a data, o valor e a descricao da despesa para gravar no sistema.</p>

            <?php if ($erro !== ''): ?>
                <div class="alert error"><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <?php if ($sucesso !== ''): ?>
                <div class="alert success"><?= htmlspecialchars($sucesso, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <form action="salvar-despesa.php" method="post">
                <div class="grid">
                    <div>
                        <label for="data_despesa">Data da despesa</label>
                        <input type="date" id="data_despesa" name="data_despesa" value="<?= htmlspecialchars($dataPadrao, ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>

                    <div>
                        <label for="valor_despesa">Valor da despesa</label>
                        <input type="text" id="valor_despesa" name="valor_despesa" inputmode="numeric" autocomplete="off" value="<?= htmlspecialchars($valorPadrao, ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>

                    <div>
                        <label for="descricao_despesa">Descricao da despesa</label>
                        <textarea id="descricao_despesa" name="descricao_despesa" required><?= htmlspecialchars($dados['descricao_despesa'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>
                </div>

                <div class="actions">
                    <button class="button" type="submit">Salvar despesa</button>
                    <a class="button secondary" href="dashboard.php">Voltar ao menu</a>
                </div>
            </form>
        </section>

        <section class="card">
            <h2>Ultimas despesas lancadas</h2>

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
                                    <td>R$ <?= htmlspecialchars(number_format((float) $despesa['valor_despesa'], 2, ',', '.'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= nl2br(htmlspecialchars($despesa['descricao_despesa'], ENT_QUOTES, 'UTF-8')) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="lead">Nenhuma despesa foi lancada ainda.</p>
            <?php endif; ?>
        </section>
    </main>
</body>
<script>
    (function () {
        const campoValor = document.getElementById('valor_despesa');

        if (!campoValor) {
            return;
        }

        function formatarMoeda(valor) {
            const digitos = valor.replace(/\D/g, '');

            if (digitos === '') {
                return '';
            }

            const numero = Number.parseInt(digitos, 10) / 100;

            return numero.toLocaleString('pt-BR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        campoValor.addEventListener('input', function () {
            campoValor.value = formatarMoeda(campoValor.value);
        });

        campoValor.form.addEventListener('submit', function () {
            campoValor.value = formatarMoeda(campoValor.value);
        });
    })();
</script>
<?php renderIdleLogoutScript(); ?>
</html>
