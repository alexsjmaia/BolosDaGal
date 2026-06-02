<?php
if (!defined('DISABLE_SESSION_TIMEOUT')) {
    define('DISABLE_SESSION_TIMEOUT', true);
}

require __DIR__ . '/auth.php';
require __DIR__ . '/db.php';

$erro = $_SESSION['alterar_cliente_erro'] ?? '';
$sucesso = $_SESSION['alterar_cliente_sucesso'] ?? '';
unset($_SESSION['alterar_cliente_erro'], $_SESSION['alterar_cliente_sucesso']);

$clienteIdSelecionado = (int) ($_GET['cliente_id'] ?? 0);

$stmtLista = $pdo->query(
    'SELECT id, nome, whatsapp
     FROM clientes
     ORDER BY nome ASC, whatsapp ASC'
);
$clientes = $stmtLista->fetchAll();

$cliente = null;
if ($clienteIdSelecionado > 0) {
    $stmtCliente = $pdo->prepare(
        'SELECT id, nome, whatsapp
         FROM clientes
         WHERE id = :id
         LIMIT 1'
    );
    $stmtCliente->execute(['id' => $clienteIdSelecionado]);
    $cliente = $stmtCliente->fetch();

    if (!$cliente) {
        $erro = 'Cliente nao encontrado para o ID informado.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alterar Cliente - Bolos da Gal</title>
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
            padding: 24px;
            font-family: Georgia, "Times New Roman", serif;
            background:
                radial-gradient(circle at top, #fbe1e7 0%, transparent 34%),
                linear-gradient(135deg, #fffafb 0%, #f8eef1 100%);
            color: var(--text);
        }

        .layout {
            width: min(1100px, 100%);
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

        h1, h2 { margin-top: 0; color: var(--secondary); }
        .lead { color: var(--muted); }

        .alert {
            margin-bottom: 18px;
            padding: 12px 14px;
            border-radius: 12px;
        }

        .alert.error { background: var(--error-bg); color: var(--error-text); }
        .alert.success { background: var(--success-bg); color: var(--success-text); }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: var(--secondary);
        }

        select, input {
            width: 100%;
            padding: 14px 16px;
            border: 1px solid var(--border);
            border-radius: 12px;
            font-size: 1rem;
        }

        .split {
            display: grid;
            gap: 24px;
        }

        @media (min-width: 900px) {
            .split {
                grid-template-columns: 360px 1fr;
                align-items: start;
            }
        }

        .grid {
            display: grid;
            gap: 16px;
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

        .button.danger {
            background: #b84f5b;
        }
    </style>
</head>
<body>
    <main class="layout">
        <section class="card">
            <h1>Alterar Cliente</h1>
            <p class="lead">Altere nome e WhatsApp do cliente e atualize os dados relacionados.</p>

            <?php if ($erro !== ''): ?>
                <div class="alert error"><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
            <?php if ($sucesso !== ''): ?>
                <div class="alert success"><?= htmlspecialchars($sucesso, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <div class="split">
                <section>
                    <h2>Selecionar cliente</h2>
                    <form method="get">
                        <div class="grid">
                            <div>
                                <label for="cliente_id">Cliente cadastrado</label>
                                <select id="cliente_id" name="cliente_id" required>
                                    <option value="">Selecione um cliente</option>
                                    <?php foreach ($clientes as $registro): ?>
                                        <option value="<?= (int) $registro['id'] ?>" <?= $clienteIdSelecionado === (int) $registro['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars((string) $registro['nome'] . ' - ' . (string) $registro['whatsapp'], ENT_QUOTES, 'UTF-8') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="actions">
                            <button class="button" type="submit">Carregar cliente</button>
                        </div>
                    </form>
                </section>

                <section>
                    <h2>Dados do cliente</h2>
                    <?php if ($cliente): ?>
                        <form action="salvar-alteracao-cliente.php" method="post">
                            <input type="hidden" name="id" value="<?= (int) $cliente['id'] ?>">
                            <div class="grid">
                                <div>
                                    <label for="nome">Nome</label>
                                    <input type="text" id="nome" name="nome" value="<?= htmlspecialchars((string) $cliente['nome'], ENT_QUOTES, 'UTF-8') ?>" required>
                                </div>
                                <div>
                                    <label for="whatsapp">WhatsApp</label>
                                    <input type="text" id="whatsapp" name="whatsapp" value="<?= htmlspecialchars((string) $cliente['whatsapp'], ENT_QUOTES, 'UTF-8') ?>" required>
                                </div>
                            </div>
                            <div class="actions">
                                <button class="button" type="submit">Salvar alteracoes</button>
                                <a class="button secondary" href="dashboard.php">Voltar ao menu</a>
                            </div>
                        </form>

                        <form action="salvar-alteracao-cliente.php" method="post" onsubmit="return confirmarExclusaoCliente(this);" style="margin-top: 10px;">
                            <input type="hidden" name="acao" value="excluir">
                            <input type="hidden" name="id" value="<?= (int) $cliente['id'] ?>">
                            <input type="hidden" name="senha_exclusao" value="">
                            <div class="actions">
                                <button class="button danger" type="submit">Excluir cliente</button>
                            </div>
                        </form>
                    <?php else: ?>
                        <p class="lead">Selecione um cliente para liberar a edicao.</p>
                        <div class="actions">
                            <a class="button secondary" href="dashboard.php">Voltar ao menu</a>
                        </div>
                    <?php endif; ?>
                </section>
            </div>
        </section>
    </main>
</body>
<script>
    function confirmarExclusaoCliente(form) {
        const confirmar = window.confirm('Deseja realmente excluir este cliente?');
        if (!confirmar) {
            return false;
        }

        const senha = window.prompt('Informe a senha para excluir o cliente:');
        if (senha === null || senha.trim() === '') {
            return false;
        }

        const campoSenha = form.querySelector('input[name="senha_exclusao"]');
        if (!campoSenha) {
            return false;
        }

        campoSenha.value = senha.trim();
        return true;
    }
</script>
<?php renderIdleLogoutScript(); ?>
</html>
