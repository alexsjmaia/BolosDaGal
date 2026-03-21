<?php require __DIR__ . '/auth.php'; ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel - Bolos da Gal</title>
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
            display: grid;
            place-items: center;
            font-family: Georgia, "Times New Roman", serif;
            background:
                radial-gradient(circle at top, #fbe1e7 0%, transparent 34%),
                linear-gradient(135deg, #fffafb 0%, #f8eef1 100%);
            color: var(--text);
        }

        .box {
            width: min(92vw, 700px);
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 28px;
            padding: 36px;
            box-shadow: 0 24px 60px var(--shadow);
        }

        h1 {
            margin-top: 0;
            color: var(--secondary);
            text-align: center;
        }

        .lead {
            text-align: center;
            color: var(--muted);
            margin-bottom: 28px;
        }

        .menu {
            display: grid;
            gap: 16px;
            margin-top: 28px;
        }

        @media (min-width: 760px) {
            .menu {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        .menu-item {
            display: block;
            padding: 20px 22px;
            border: 1px solid var(--border);
            border-radius: 18px;
            background: linear-gradient(135deg, #fff7f8 0%, #fff 100%);
            color: #fff;
            text-decoration: none;
            font-weight: bold;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .menu-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 16px 30px rgba(217, 154, 165, 0.18);
        }

        .menu-number {
            display: inline-block;
            min-width: 42px;
            padding: 10px 12px;
            margin-right: 14px;
            border-radius: 999px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            text-align: center;
        }

        .menu-text {
            color: var(--secondary);
            vertical-align: middle;
        }

        .logout {
            display: inline-block;
            margin-top: 24px;
            padding: 12px 18px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: #fff;
            text-decoration: none;
            font-weight: bold;
        }

        .toolbar {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            justify-content: center;
            margin-top: 24px;
        }
    </style>
</head>
<body>
    <main class="box">
        <h1>Painel do Sistema</h1>
        <p class="lead">Bem-vindo, <strong><?= htmlspecialchars($_SESSION['usuario'], ENT_QUOTES, 'UTF-8') ?></strong>. Escolha uma opcao abaixo.</p>

        <section class="menu">
            <a class="menu-item" href="vender-item.php">
                <span class="menu-number">1</span>
                <span class="menu-text">Vender Item</span>
            </a>
            <a class="menu-item" href="cadastrar-item.php">
                <span class="menu-number">2</span>
                <span class="menu-text">Cadastrar Item</span>
            </a>
            <a class="menu-item" href="alterar-item.php">
                <span class="menu-number">3</span>
                <span class="menu-text">Alterar Item</span>
            </a>
            <a class="menu-item" href="listar-itens.php">
                <span class="menu-number">4</span>
                <span class="menu-text">Listar Itens Cadastrados</span>
            </a>
            <a class="menu-item" href="relatorio-itens-vendidos.php">
                <span class="menu-number">5</span>
                <span class="menu-text">Itens Mais Vendidos</span>
            </a>
            <a class="menu-item" href="lancar-despesa.php">
                <span class="menu-number">6</span>
                <span class="menu-text">Lancar Despesa</span>
            </a>
            <a class="menu-item" href="listar-despesas.php">
                <span class="menu-number">7</span>
                <span class="menu-text">Listar Despesas</span>
            </a>
        </section>

        <div class="toolbar">
            <a class="logout" href="backup.php">Backup e Restauracao</a>
            <a class="logout" href="logout.php">Sair</a>
        </div>
    </main>
</body>
<?php renderIdleLogoutScript(); ?>
</html>
