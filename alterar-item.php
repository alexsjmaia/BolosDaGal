<?php
require __DIR__ . '/auth.php';
require __DIR__ . '/db.php';

$erro = $_SESSION['alterar_item_erro'] ?? '';
$sucesso = $_SESSION['alterar_item_sucesso'] ?? '';
unset($_SESSION['alterar_item_erro'], $_SESSION['alterar_item_sucesso']);
$podeCarregarFoto = currentUserCanUploadPhotos();

$codigoSelecionado = trim($_GET['codigo'] ?? '');

$stmtLista = $pdo->query('SELECT codigo_produto, nome_produto FROM itens ORDER BY codigo_produto');
$itens = $stmtLista->fetchAll();

$item = null;

if ($codigoSelecionado !== '') {
    $stmtItem = $pdo->prepare(
        'SELECT id, codigo_produto, nome_produto, ncm, foto_produto, mostrar_catalogo, preco_custo, preco_venda
         FROM itens
         WHERE codigo_produto = :codigo_produto
         LIMIT 1'
    );
    $stmtItem->execute(['codigo_produto' => $codigoSelecionado]);
    $item = $stmtItem->fetch();

    if (!$item) {
        $erro = 'Item nao encontrado para o codigo informado.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alterar Item - Bolos da Gal</title>
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
            --danger: #b84f5b;
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

        h1, h2 {
            margin-top: 0;
            color: var(--secondary);
        }

        .lead {
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

        .selector {
            display: grid;
            gap: 12px;
            align-items: end;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: var(--secondary);
        }

        select,
        input {
            width: 100%;
            padding: 14px 16px;
            border: 1px solid var(--border);
            border-radius: 12px;
            font-size: 1rem;
        }

        input[readonly] {
            background: #f8f4f5;
            color: var(--muted);
        }

        .grid {
            display: grid;
            gap: 18px;
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

        .button.danger {
            background: var(--danger);
            box-shadow: none;
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
    </style>
</head>
<body>
    <main class="layout">
        <section class="card">
            <h1>Alterar Item</h1>
            <p class="lead">Escolha um item cadastrado para alterar os dados ou excluir o registro.</p>

            <?php if ($erro !== ''): ?>
                <div class="alert error"><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <?php if ($sucesso !== ''): ?>
                <div class="alert success"><?= htmlspecialchars($sucesso, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <div class="split">
                <section>
                    <h2>Selecionar item</h2>
                    <form method="get" class="selector">
                        <div>
                            <label for="codigo">Item cadastrado</label>
                            <select id="codigo" name="codigo" required>
                                <option value="">Selecione um item</option>
                                <?php foreach ($itens as $registro): ?>
                                    <?php $codigo = $registro['codigo_produto']; ?>
                                    <option value="<?= htmlspecialchars($codigo, ENT_QUOTES, 'UTF-8') ?>" <?= $codigoSelecionado === $codigo ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($codigo . ' - ' . $registro['nome_produto'], ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button class="button" type="submit">Carregar item</button>
                    </form>
                </section>

                <section>
                    <h2>Dados do item</h2>

                    <?php if ($item): ?>
                        <form action="atualizar-item.php" method="post" enctype="multipart/form-data">
                            <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">

                            <div class="grid">
                                <div>
                                    <label for="codigo_produto">Codigo do produto</label>
                                    <input type="text" id="codigo_produto" value="<?= htmlspecialchars($item['codigo_produto'], ENT_QUOTES, 'UTF-8') ?>" readonly>
                                </div>

                                <div>
                                    <label for="nome_produto">Nome do produto</label>
                                    <input type="text" id="nome_produto" name="nome_produto" value="<?= htmlspecialchars($item['nome_produto'], ENT_QUOTES, 'UTF-8') ?>" required>
                                </div>

                                <div>
                                    <label for="ncm">NCM</label>
                                    <input type="text" id="ncm" name="ncm" value="<?= htmlspecialchars($item['ncm'], ENT_QUOTES, 'UTF-8') ?>" required>
                                </div>

                                <div>
                                    <label for="preco_custo">Preco de custo</label>
                                    <input type="text" id="preco_custo" name="preco_custo" value="<?= htmlspecialchars(number_format((float) $item['preco_custo'], 2, '.', ''), ENT_QUOTES, 'UTF-8') ?>" required>
                                </div>

                                <div>
                                    <label for="preco_venda">Preco de venda</label>
                                    <input type="text" id="preco_venda" name="preco_venda" value="<?= htmlspecialchars(number_format((float) $item['preco_venda'], 2, '.', ''), ENT_QUOTES, 'UTF-8') ?>" required>
                                </div>

                                <?php if ($podeCarregarFoto): ?>
                                    <div>
                                        <label for="foto_produto">Foto do produto</label>
                                        <input type="file" id="foto_produto" name="foto_produto" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                                    </div>
                                <?php else: ?>
                                    <div>
                                        <label>Foto do produto</label>
                                        <input type="text" value="Somente usuarios bolos e root podem alterar foto." readonly>
                                    </div>
                                <?php endif; ?>

                                <div>
                                    <label for="mostrar_catalogo">Mostrar no catalogo</label>
                                    <select id="mostrar_catalogo" name="mostrar_catalogo">
                                        <option value="1" <?= ((string) ($item['mostrar_catalogo'] ?? '1') === '1') ? 'selected' : '' ?>>Sim</option>
                                        <option value="0" <?= ((string) ($item['mostrar_catalogo'] ?? '1') === '0') ? 'selected' : '' ?>>Nao</option>
                                    </select>
                                </div>

                                <?php if (!empty($item['foto_produto'])): ?>
                                    <div>
                                        <label>Foto atual</label>
                                        <img src="<?= htmlspecialchars((string) $item['foto_produto'], ENT_QUOTES, 'UTF-8') ?>" alt="Foto do produto" style="max-width: 220px; border-radius: 12px; border: 1px solid var(--border);">
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="actions">
                                <button class="button" type="submit">Salvar alteracoes</button>
                                <a class="button secondary" href="dashboard.php">Voltar ao menu</a>
                            </div>
                        </form>

                        <form action="excluir-item.php" method="post" onsubmit="return confirm('Deseja realmente excluir este item?');">
                            <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                            <div class="actions">
                                <button class="button danger" type="submit">Excluir item</button>
                            </div>
                        </form>
                    <?php else: ?>
                        <p class="lead">Selecione um item para liberar a edicao.</p>
                        <div class="actions">
                            <a class="button secondary" href="dashboard.php">Voltar ao menu</a>
                        </div>
                    <?php endif; ?>
                </section>
            </div>
        </section>
    </main>
</body>
<?php renderIdleLogoutScript(); ?>
</html>
