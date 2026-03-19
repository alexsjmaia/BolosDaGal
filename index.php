<?php
session_start();

if (isset($_SESSION['usuario'])) {
    header('Location: dashboard.php');
    exit;
}

$erro = $_SESSION['erro'] ?? '';
unset($_SESSION['erro']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Bolos da Gal</title>
    <style>
        :root {
            --bg: #fff9fb;
            --card: #ffffff;
            --primary: #d99aa5;
            --primary-dark: #c77d89;
            --secondary: #7d635c;
            --text: #4d3e39;
            --muted: #8a7670;
            --border: #f1cfd6;
            --shadow: rgba(125, 99, 92, 0.18);
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
            font-family: Georgia, "Times New Roman", serif;
            background:
                radial-gradient(circle at top, #fbe1e7 0%, transparent 34%),
                linear-gradient(135deg, #fffafb 0%, #f8eef1 100%);
            color: var(--text);
        }

        .card {
            width: min(92vw, 420px);
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 28px;
            padding: 28px 32px 32px;
            box-shadow: 0 24px 60px var(--shadow);
        }

        .brand {
            display: grid;
            justify-items: center;
            gap: 16px;
            margin-bottom: 18px;
        }

        .logo {
            width: min(100%, 220px);
            aspect-ratio: 1;
            object-fit: contain;
            border-radius: 50%;
            border: 6px solid #f7d1d8;
            background: #fff;
            box-shadow: 0 12px 30px rgba(217, 154, 165, 0.18);
        }

        h1 {
            margin: 0 0 8px;
            font-size: 2.2rem;
            line-height: 1;
            color: var(--secondary);
            text-align: center;
        }

        p {
            margin: 0 0 24px;
            text-align: center;
            color: var(--muted);
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }

        input {
            width: 100%;
            padding: 14px 16px;
            margin-bottom: 18px;
            border: 1px solid var(--border);
            border-radius: 12px;
            font-size: 1rem;
        }

        input:focus {
            outline: 2px solid #f4c7a8;
            border-color: var(--primary);
        }

        button {
            width: 100%;
            padding: 14px 16px;
            border: 0;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: #fff;
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
            box-shadow: 0 12px 24px rgba(217, 154, 165, 0.24);
        }

        button:hover {
            background: var(--primary-dark);
        }

        .erro {
            margin-bottom: 18px;
            padding: 12px 14px;
            border-radius: 12px;
            background: var(--error-bg);
            color: var(--error-text);
            font-size: 0.95rem;
        }

        .hint {
            margin-top: 18px;
            font-size: 0.9rem;
            color: var(--secondary);
        }
    </style>
</head>
<body>
    <main class="card">
        <div class="brand">
            <img class="logo" src="logo-bolos-da-gal.png" alt="Logo Bolos da Gal">
        </div>
        <h1>Bolos da Gal</h1>
        <p>Entre com seu usuario e senha</p>

        <?php if ($erro !== ''): ?>
            <div class="erro"><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <form action="login.php" method="post">
            <label for="usuario">Usuario</label>
            <input type="text" id="usuario" name="usuario" required>

            <label for="senha">Senha</label>
            <input type="password" id="senha" name="senha" required>

            <button type="submit">Entrar</button>
        </form>
    </main>
</body>
</html>
