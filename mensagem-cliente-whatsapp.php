<?php
require __DIR__ . '/auth.php';

$nome = trim((string) ($_GET['nome'] ?? 'Cliente'));
$whatsapp = preg_replace('/\D+/', '', (string) ($_GET['whatsapp'] ?? '')) ?? '';
$saldo = (float) ($_GET['saldo'] ?? 0);

if (strlen($whatsapp) !== 13) {
    $whatsapp = '';
}

$imageUrlPath = 'mensagem-cliente-imagem.php?nome=' . urlencode($nome) . '&saldo=' . urlencode(number_format($saldo, 2, '.', ''));

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost:8000';
$baseUrl = $scheme . '://' . $host;
$imageAbsoluteUrl = $baseUrl . '/' . $imageUrlPath;

$mensagemWhatsApp = "Oi! Preparamos uma mensagem especial de cashback para voce: {$imageAbsoluteUrl}";
$whatsLink = $whatsapp !== ''
    ? 'https://wa.me/' . $whatsapp . '?text=' . rawurlencode($mensagemWhatsApp)
    : '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mensagem para WhatsApp</title>
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
            width: min(1200px, 100%);
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
            margin-bottom: 20px;
        }

        .actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 20px;
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

        .preview {
            width: min(100%, 720px);
            border-radius: 18px;
            border: 1px solid var(--border);
            box-shadow: 0 16px 34px rgba(125, 99, 92, 0.12);
        }
    </style>
</head>
<body>
    <main class="layout">
        <section class="card">
            <h1>Mensagem em Imagem para Cliente</h1>
            <p class="lead">
                Cliente: <strong><?= htmlspecialchars($nome, ENT_QUOTES, 'UTF-8') ?></strong><br>
                WhatsApp: <strong><?= htmlspecialchars($whatsapp, ENT_QUOTES, 'UTF-8') ?></strong>
            </p>

            <img class="preview" src="<?= htmlspecialchars($imageUrlPath, ENT_QUOTES, 'UTF-8') ?>" alt="Mensagem de cashback em imagem">

            <div class="actions">
                <?php if ($whatsLink !== ''): ?>
                    <a class="button" href="<?= htmlspecialchars($whatsLink, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer">Abrir WhatsApp com Link da Imagem</a>
                <?php endif; ?>
                <a class="button secondary" href="<?= htmlspecialchars($imageUrlPath, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer">Abrir Imagem</a>
                <a class="button secondary" href="listar-clientes.php">Voltar para Clientes</a>
            </div>
        </section>
    </main>
</body>
<?php renderIdleLogoutScript(); ?>
</html>
