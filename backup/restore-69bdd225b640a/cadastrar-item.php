<?php
require __DIR__ . '/auth.php';
require __DIR__ . '/db.php';

$erro = $_SESSION['item_erro'] ?? '';
$sucesso = $_SESSION['item_sucesso'] ?? '';
$dados = $_SESSION['item_dados'] ?? [];

unset($_SESSION['item_erro'], $_SESSION['item_sucesso'], $_SESSION['item_dados']);

$stmt = $pdo->query('SELECT codigo_produto FROM itens');
$codigosExistentes = $stmt->fetchAll(PDO::FETCH_COLUMN);
$codigosNumericos = [];

foreach ($codigosExistentes as $codigoExistente) {
    if (ctype_digit((string) $codigoExistente)) {
        $codigosNumericos[(int) $codigoExistente] = true;
    }
}

$proximoCodigoDisponivel = 1;

while (isset($codigosNumericos[$proximoCodigoDisponivel])) {
    $proximoCodigoDisponivel++;
}

$codigoSugerido = $dados['codigo_produto'] ?? (string) $proximoCodigoDisponivel;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar Item - Bolos da Gal</title>
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
            display: grid;
            place-items: center;
            padding: 24px;
            font-family: Georgia, "Times New Roman", serif;
            background:
                radial-gradient(circle at top, #fbe1e7 0%, transparent 34%),
                linear-gradient(135deg, #fffafb 0%, #f8eef1 100%);
            color: var(--text);
        }

        .card {
            width: min(100%, 680px);
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 28px;
            padding: 34px;
            box-shadow: 0 24px 60px var(--shadow);
        }

        h1 {
            margin-top: 0;
            margin-bottom: 8px;
            color: var(--secondary);
            text-align: center;
        }

        .lead {
            margin-bottom: 28px;
            text-align: center;
            color: var(--muted);
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

        input {
            width: 100%;
            padding: 14px 16px;
            border: 1px solid var(--border);
            border-radius: 12px;
            font-size: 1rem;
        }

        input:focus {
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
            box-shadow: 0 12px 24px rgba(217, 154, 165, 0.24);
        }

        .button.secondary {
            background: #fff;
            color: var(--secondary);
            border: 1px solid var(--border);
            box-shadow: none;
        }
    </style>
</head>
<body>
    <main class="card">
        <h1>Cadastrar Item</h1>
        <p class="lead">Informe os dados do produto para gravar no banco de dados.</p>

        <?php if ($erro !== ''): ?>
            <div class="alert error"><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <?php if ($sucesso !== ''): ?>
            <div class="alert success"><?= htmlspecialchars($sucesso, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <form action="salvar-item.php" method="post">
            <div class="grid">
                <div>
                    <label for="codigo_produto">Codigo do produto</label>
                    <input type="text" id="codigo_produto" name="codigo_produto" value="<?= htmlspecialchars($codigoSugerido, ENT_QUOTES, 'UTF-8') ?>" required>
                </div>

                <div>
                    <label for="nome_produto">Nome do produto</label>
                    <input type="text" id="nome_produto" name="nome_produto" value="<?= htmlspecialchars($dados['nome_produto'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                </div>

                <div>
                    <label for="ncm">NCM</label>
                    <input type="text" id="ncm" name="ncm" value="<?= htmlspecialchars($dados['ncm'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                </div>

                <div>
                    <label for="preco_custo">Preco de custo</label>
                    <input type="number" id="preco_custo" name="preco_custo" step="0.01" min="0" value="<?= htmlspecialchars($dados['preco_custo'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                </div>

                <div>
                    <label for="preco_venda">Preco de venda</label>
                    <input type="number" id="preco_venda" name="preco_venda" step="0.01" min="0" value="<?= htmlspecialchars($dados['preco_venda'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                </div>
            </div>

            <div class="actions">
                <button class="button" type="submit">Salvar item</button>
                <a class="button secondary" href="dashboard.php">Voltar ao menu</a>
            </div>
        </form>
    </main>
</body>
<?php renderIdleLogoutScript(); ?>
</html>
