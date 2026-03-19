<?php
require __DIR__ . '/auth.php';
require __DIR__ . '/db.php';

$erro = $_SESSION['venda_erro'] ?? '';
$sucesso = $_SESSION['venda_sucesso'] ?? '';
unset($_SESSION['venda_erro'], $_SESSION['venda_sucesso']);

$stmt = $pdo->query(
    'SELECT id, codigo_produto, nome_produto, preco_venda
     FROM itens
     ORDER BY nome_produto, codigo_produto'
);
$itens = $stmt->fetchAll();

$carrinho = $_SESSION['carrinho_venda'] ?? [];
$totalGeral = 0.0;

foreach ($carrinho as $itemCarrinho) {
    $totalGeral += (float) $itemCarrinho['valor_total'];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vender Item - Bolos da Gal</title>
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

        h1, h2 {
            margin-top: 0;
            color: var(--secondary);
        }

        .lead {
            margin-bottom: 20px;
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

        .split {
            display: grid;
            gap: 24px;
        }

        @media (min-width: 980px) {
            .split {
                grid-template-columns: 400px 1fr;
                align-items: start;
            }
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

        select,
        input,
        textarea {
            width: 100%;
            padding: 14px 16px;
            border: 1px solid var(--border);
            border-radius: 12px;
            font-size: 1rem;
        }

        textarea {
            min-height: 88px;
            resize: vertical;
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

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 12px 10px;
            border-bottom: 1px solid var(--border);
            text-align: left;
            vertical-align: middle;
        }

        th {
            color: var(--secondary);
        }

        .total-box {
            margin-top: 18px;
            padding: 16px 18px;
            border-radius: 16px;
            background: #fff7f8;
            border: 1px solid var(--border);
            color: var(--secondary);
            font-weight: bold;
        }

        .mini-form {
            display: inline;
        }

        .mini-button {
            padding: 8px 12px;
            border: 0;
            border-radius: 10px;
            background: var(--danger);
            color: #fff;
            cursor: pointer;
            font-weight: bold;
        }

        .empty {
            color: var(--muted);
        }

        .info {
            margin-top: 18px;
            color: var(--muted);
            font-size: 0.95rem;
        }

        .payment-extra {
            display: none;
        }

        .troco-box {
            padding: 14px 16px;
            border: 1px solid var(--border);
            border-radius: 12px;
            background: #fff7f8;
            color: var(--secondary);
            font-weight: bold;
        }
    </style>
</head>
<body>
    <main class="layout">
        <section class="card">
            <h1>Vender Item</h1>
            <p class="lead">Monte a comanda adicionando varios itens ao carrinho e finalize tudo de uma vez.</p>

            <?php if ($erro !== ''): ?>
                <div class="alert error"><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <?php if ($sucesso !== ''): ?>
                <div class="alert success"><?= htmlspecialchars($sucesso, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <div class="split">
                <section>
                    <h2>Adicionar item</h2>
                    <form action="adicionar-ao-carrinho.php" method="post">
                        <div class="grid">
                            <div>
                                <label for="item_id">Item</label>
                                <select id="item_id" name="item_id" required>
                                    <option value="">Selecione um item</option>
                                    <?php foreach ($itens as $item): ?>
                                        <option value="<?= (int) $item['id'] ?>">
                                            <?= htmlspecialchars($item['nome_produto'] . ' - Cod. ' . $item['codigo_produto'] . ' - R$ ' . number_format((float) $item['preco_venda'], 2, ',', '.'), ENT_QUOTES, 'UTF-8') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div>
                                <label for="quantidade">Quantidade</label>
                                <input type="text" id="quantidade" name="quantidade" value="1" required>
                            </div>
                        </div>

                        <div class="actions">
                            <button class="button" type="submit">Adicionar ao carrinho</button>
                            <a class="button secondary" href="dashboard.php">Voltar ao menu</a>
                        </div>
                    </form>
                </section>

                <section>
                    <h2>Carrinho da comanda</h2>

                    <?php if ($carrinho): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Codigo</th>
                                    <th>Descricao</th>
                                    <th>Qtd</th>
                                    <th>Unitario</th>
                                    <th>Total</th>
                                    <th>Acao</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($carrinho as $itemCarrinho): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($itemCarrinho['codigo_produto'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($itemCarrinho['descricao_produto'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars(number_format((float) $itemCarrinho['quantidade'], 2, ',', '.'), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td>R$ <?= htmlspecialchars(number_format((float) $itemCarrinho['preco_unitario'], 2, ',', '.'), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td>R$ <?= htmlspecialchars(number_format((float) $itemCarrinho['valor_total'], 2, ',', '.'), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td>
                                            <form class="mini-form" action="remover-do-carrinho.php" method="post">
                                                <input type="hidden" name="item_id" value="<?= (int) $itemCarrinho['item_id'] ?>">
                                                <button class="mini-button" type="submit">Remover</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <div class="total-box">
                            Total da comanda: R$ <?= htmlspecialchars(number_format($totalGeral, 2, ',', '.'), ENT_QUOTES, 'UTF-8') ?>
                        </div>

                        <form action="salvar-venda.php" method="post">
                            <div class="grid" style="margin-top: 20px;">
                                <div>
                                    <label for="forma_pagamento">Forma de pagamento</label>
                                    <select id="forma_pagamento" name="forma_pagamento" required>
                                        <option value="">Selecione a forma de pagamento</option>
                                        <option value="Dinheiro">Dinheiro</option>
                                        <option value="Pix">Pix</option>
                                        <option value="Cartao de credito">Cartao de credito</option>
                                        <option value="Cartao de debito">Cartao de debito</option>
                                    </select>
                                </div>

                                <div>
                                    <label for="data_venda">Data da venda</label>
                                    <input type="date" id="data_venda" name="data_venda">
                                </div>

                                <div id="dinheiro_fields" class="payment-extra">
                                    <label for="valor_recebido">Valor recebido</label>
                                    <input type="text" id="valor_recebido" name="valor_recebido" placeholder="Ex.: 150,00">
                                </div>

                                <div id="troco_box" class="troco-box payment-extra">
                                    Troco: R$ <span id="valor_troco_preview">0,00</span>
                                </div>

                                <div>
                                    <label for="observacao">Observacao da comanda</label>
                                    <textarea id="observacao" name="observacao" placeholder="Campo opcional"></textarea>
                                </div>
                            </div>

                            <div class="actions">
                                <button class="button" type="submit">Finalizar venda</button>
                            </div>
                        </form>
                    <?php else: ?>
                        <p class="empty">Nenhum item foi adicionado ao carrinho ainda.</p>
                    <?php endif; ?>
                </section>
            </div>

            <p class="info">Se a tabela `vendas` ja existir no seu banco, execute [alterar-tabela-vendas-comanda.sql](C:\BolosDaGal\alterar-tabela-vendas-comanda.sql). Se ainda nao existir, execute [criar-tabela-vendas.sql](C:\BolosDaGal\criar-tabela-vendas.sql).</p>
        </section>
    </main>

    <script>
        const formaPagamento = document.getElementById('forma_pagamento');
        const valorRecebido = document.getElementById('valor_recebido');
        const dinheiroFields = document.getElementById('dinheiro_fields');
        const trocoBox = document.getElementById('troco_box');
        const valorTrocoPreview = document.getElementById('valor_troco_preview');
        const totalComanda = <?= json_encode(number_format($totalGeral, 2, '.', '')) ?>;

        function parseCurrency(value) {
            if (!value) {
                return 0;
            }

            return parseFloat(String(value).replace(',', '.')) || 0;
        }

        function formatCurrency(value) {
            return Number(value).toLocaleString('pt-BR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        function updatePagamento() {
            const isDinheiro = formaPagamento.value === 'Dinheiro';
            dinheiroFields.style.display = isDinheiro ? 'block' : 'none';
            trocoBox.style.display = isDinheiro ? 'block' : 'none';

            if (!isDinheiro) {
                valorRecebido.value = '';
                valorTrocoPreview.textContent = '0,00';
                return;
            }

            const recebido = parseCurrency(valorRecebido.value);
            const troco = Math.max(recebido - parseFloat(totalComanda), 0);
            valorTrocoPreview.textContent = formatCurrency(troco);
        }

        formaPagamento.addEventListener('change', updatePagamento);
        valorRecebido.addEventListener('input', updatePagamento);
        updatePagamento();
    </script>
</body>
<?php renderIdleLogoutScript(); ?>
</html>
