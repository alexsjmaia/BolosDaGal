<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$erro = $_SESSION['cliente_cadastro_erro'] ?? '';
$erroHtml = $_SESSION['cliente_cadastro_erro_html'] ?? '';
$sucesso = $_SESSION['cliente_cadastro_sucesso'] ?? '';
$dados = $_SESSION['cliente_cadastro_dados'] ?? [];
unset(
    $_SESSION['cliente_cadastro_erro'],
    $_SESSION['cliente_cadastro_erro_html'],
    $_SESSION['cliente_cadastro_sucesso'],
    $_SESSION['cliente_cadastro_dados']
);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Cliente - Bolos da Gal</title>
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

        * { box-sizing: border-box; }

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
            width: min(100%, 700px);
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 28px;
            padding: 34px;
            box-shadow: 0 24px 60px var(--shadow);
        }

        h1 {
            margin: 0 0 8px;
            text-align: center;
            color: var(--secondary);
        }

        .lead {
            margin: 0 0 24px;
            text-align: center;
            color: var(--muted);
        }

        .alert {
            margin-bottom: 18px;
            padding: 12px 14px;
            border-radius: 12px;
        }

        .alert.error { background: var(--error-bg); color: var(--error-text); }
        .alert.success { background: var(--success-bg); color: var(--success-text); }
        .alert a { color: inherit; font-weight: bold; }

        .grid { display: grid; gap: 18px; }
        label { display: block; margin-bottom: 8px; font-weight: bold; color: var(--secondary); }

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
    </style>
</head>
<body>
    <main class="card">
        <h1>Cadastro de Cliente</h1>
        <p class="lead">Informe seus dados para acessar o cardapio.</p>

        <?php if ($erro !== ''): ?>
            <div class="alert error"><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <?php if ($erroHtml !== ''): ?>
            <div class="alert error"><?= $erroHtml ?></div>
        <?php endif; ?>

        <?php if ($sucesso !== ''): ?>
            <div class="alert success"><?= htmlspecialchars($sucesso, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <form action="salvar-cliente-autocadastro.php" method="post">
            <div class="grid">
                <div>
                    <label for="whatsapp">Whatsapp com ddd</label>
                    <input type="text" id="whatsapp" name="whatsapp" value="<?= htmlspecialchars($dados['whatsapp'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                </div>

                <div>
                    <label for="nome">Nome</label>
                    <input type="text" id="nome" name="nome" value="<?= htmlspecialchars($dados['nome'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                </div>

                <div>
                    <label for="senha">Senha</label>
                    <input type="password" id="senha" name="senha" required>
                </div>

                <div>
                    <label for="senha_confirmacao">Repita a senha</label>
                    <input type="password" id="senha_confirmacao" name="senha_confirmacao" required>
                </div>
            </div>

            <div class="actions">
                <button class="button" type="submit">Cadastrar</button>
                <a class="button secondary" href="clientes/">Voltar</a>
            </div>
        </form>
    </main>
</body>
</html>
