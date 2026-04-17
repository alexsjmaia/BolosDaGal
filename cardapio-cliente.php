<?php
require __DIR__ . '/cliente-auth.php';
require __DIR__ . '/db.php';
exigirClienteLogado();

$pedidoErro = $_SESSION['cliente_pedido_erro'] ?? '';
$pedidoSucesso = $_SESSION['cliente_pedido_sucesso'] ?? '';
$pedidoDados = $_SESSION['cliente_pedido_dados'] ?? [];
unset($_SESSION['cliente_pedido_erro'], $_SESSION['cliente_pedido_sucesso'], $_SESSION['cliente_pedido_dados']);
const LIMITE_UNIDADES_DIA = 10;

if (!isset($_SESSION['cliente_carrinho']) || !is_array($_SESSION['cliente_carrinho'])) {
    $_SESSION['cliente_carrinho'] = [];
}
$carrinho = $_SESSION['cliente_carrinho'];

$stmt = $pdo->prepare(
    'SELECT nome, whatsapp, saldo_cashback
     FROM clientes
     WHERE id = :id
     LIMIT 1'
);
$stmt->execute(['id' => (int) $_SESSION['cliente_id']]);
$cliente = $stmt->fetch();

if (!$cliente) {
    limparSessaoCliente();
    header('Location: cliente-login.php');
    exit;
}

$_SESSION['cliente_nome'] = $cliente['nome'];
$_SESSION['cliente_whatsapp'] = $cliente['whatsapp'];
$_SESSION['cliente_saldo_cashback'] = (float) $cliente['saldo_cashback'];

$stmtItens = $pdo->query(
    'SELECT
        i.id,
        i.nome_produto,
        i.foto_produto,
        i.preco_venda,
        COALESCE(vendas_item.qtd_vendida, 0) AS qtd_vendida
     FROM itens i
     LEFT JOIN (
        SELECT item_id, SUM(quantidade) AS qtd_vendida
        FROM pedidos_clientes
        GROUP BY item_id
     ) AS vendas_item ON vendas_item.item_id = i.id
     WHERE i.mostrar_catalogo = 1
     ORDER BY i.preco_venda ASC, i.nome_produto ASC'
);
$itensCardapio = $stmtItens->fetchAll();

$dataEntregaPadrao = (string) ($pedidoDados['data_entrega'] ?? '');
$horaEntregaPadrao = (string) ($pedidoDados['hora_entrega'] ?? '');

$stmtCapacidade = $pdo->query(
    'SELECT DATE(data_hora_entrega) AS data_entrega, COALESCE(SUM(quantidade), 0) AS qtd_total
     FROM pedidos_clientes
     WHERE data_hora_entrega >= CURDATE()
     GROUP BY DATE(data_hora_entrega)'
);
$capacidadePorData = [];
foreach ($stmtCapacidade->fetchAll() as $linhaCapacidade) {
    $capacidadePorData[(string) $linhaCapacidade['data_entrega']] = (int) $linhaCapacidade['qtd_total'];
}

$opcoesDataEntrega = [];
$hoje = new DateTime('today');
for ($i = 0; $i <= 60; $i++) {
    $data = (clone $hoje)->modify("+{$i} day");
    $chave = $data->format('Y-m-d');
    $qtdNoDia = (int) ($capacidadePorData[$chave] ?? 0);
    $disponivel = $qtdNoDia < LIMITE_UNIDADES_DIA;
    $opcoesDataEntrega[] = [
        'valor' => $chave,
        'label' => $data->format('d/m/Y'),
        'qtd' => $qtdNoDia,
        'disponivel' => $disponivel,
    ];
}

$itensMap = [];
foreach ($itensCardapio as $item) {
    $itensMap[(int) $item['id']] = $item;
}

$carrinhoDetalhes = [];
$totalCarrinho = 0.0;
foreach ($carrinho as $itemId => $quantidade) {
    $itemId = (int) $itemId;
    $quantidade = (int) $quantidade;
    if ($quantidade <= 0 || !isset($itensMap[$itemId])) {
        continue;
    }

    $item = $itensMap[$itemId];
    $preco = (float) $item['preco_venda'];
    $totalItem = $preco * $quantidade;
    $totalCarrinho += $totalItem;

    $carrinhoDetalhes[] = [
        'id' => $itemId,
        'nome_produto' => $item['nome_produto'],
        'quantidade' => $quantidade,
        'preco_unitario' => $preco,
        'valor_total' => $totalItem,
    ];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cardapio - Bolos da Gal</title>
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

        h1 {
            margin: 0 0 8px;
            color: var(--secondary);
        }

        .lead {
            color: var(--muted);
            margin: 0 0 18px;
        }

        .tag {
            margin-top: 14px;
            padding: 14px 16px;
            border-radius: 12px;
            border: 1px solid var(--border);
            background: #fff7f8;
            color: var(--secondary);
            font-weight: bold;
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

        .alert {
            margin-bottom: 18px;
            padding: 12px 14px;
            border-radius: 12px;
        }

        .alert.error {
            background: #fff0f1;
            color: #9f2d20;
        }

        .alert.success {
            background: #eef9f1;
            color: #2b6f44;
        }

        .form-grid {
            display: grid;
            gap: 14px;
        }

        @media (min-width: 760px) {
            .form-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
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
            padding: 12px 14px;
            border: 1px solid var(--border);
            border-radius: 12px;
            font-size: 1rem;
        }

        .pedido-total {
            margin-top: 10px;
            padding: 12px 14px;
            border: 1px solid var(--border);
            border-radius: 12px;
            background: #fff7f8;
            color: var(--secondary);
            font-weight: bold;
        }

        .card-mini {
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 14px;
            background: #fff7f8;
        }

        .cart-table-wrap {
            overflow-x: auto;
        }

        .cart-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
        }

        .cart-table th,
        .cart-table td {
            text-align: left;
            padding: 10px;
            border-bottom: 1px solid var(--border);
        }

        .section-title {
            margin: 0 0 14px;
            color: var(--secondary);
        }

        .produtos {
            display: grid;
            gap: 16px;
        }

        @media (min-width: 760px) {
            .produtos {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        .produto-card {
            border: 1px solid var(--border);
            border-radius: 16px;
            overflow: hidden;
            background: #fff;
        }

        .produto-media {
            position: relative;
        }

        .produto-foto {
            width: 100%;
            aspect-ratio: 1 / 1;
            object-fit: cover;
            background: #f9f2f4;
            display: block;
        }

        .produto-body {
            padding: 14px;
        }

        .produto-nome {
            margin: 0 0 8px;
            color: var(--secondary);
            font-weight: bold;
        }

        .produto-preco {
            margin: 0;
            color: var(--text);
            font-weight: bold;
        }

        .produto-actions {
            margin-top: 12px;
            display: grid;
            gap: 10px;
        }

        .produto-qty {
            width: 100%;
        }

        .button.small {
            width: 100%;
            padding: 10px 12px;
            font-size: 0.95rem;
        }
    </style>
</head>
<body>
    <main class="layout">
        <section class="card">
            <h1>Cardapio Bolos da Gal</h1>
            <p class="lead">Bem-vindo, <strong><?= htmlspecialchars((string) $cliente['nome'], ENT_QUOTES, 'UTF-8') ?></strong>.</p>
            <p class="lead">Telefone: <?= htmlspecialchars((string) $cliente['whatsapp'], ENT_QUOTES, 'UTF-8') ?></p>

            <div class="tag">
                Seu cashback atual: R$ <?= htmlspecialchars(number_format((float) $cliente['saldo_cashback'], 2, ',', '.'), ENT_QUOTES, 'UTF-8') ?>
            </div>

            <div class="actions">
                <a class="button" href="cliente-logout.php">Sair</a>
            </div>
        </section>

        <section class="card">
            <h2 class="section-title">Seu Pedido</h2>

            <?php if ($pedidoErro !== ''): ?>
                <div class="alert error"><?= htmlspecialchars($pedidoErro, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <?php if ($pedidoSucesso !== ''): ?>
                <div class="alert success"><?= htmlspecialchars($pedidoSucesso, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <?php if ($carrinhoDetalhes): ?>
                <div class="cart-table-wrap">
                    <table class="cart-table">
                        <thead>
                            <tr>
                                <th>Sabor</th>
                                <th>Qtd</th>
                                <th>Unitario</th>
                                <th>Total</th>
                                <th>Acao</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($carrinhoDetalhes as $linha): ?>
                                <tr>
                                    <td><?= htmlspecialchars((string) $linha['nome_produto'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= (int) $linha['quantidade'] ?></td>
                                    <td>R$ <?= htmlspecialchars(number_format((float) $linha['preco_unitario'], 2, ',', '.'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>R$ <?= htmlspecialchars(number_format((float) $linha['valor_total'], 2, ',', '.'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>
                                        <form action="salvar-pedido-cliente.php" method="post">
                                            <input type="hidden" name="acao" value="remover">
                                            <input type="hidden" name="item_id" value="<?= (int) $linha['id'] ?>">
                                            <button class="button small" type="submit">Remover</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="pedido-total">
                    Total do pedido: R$ <?= htmlspecialchars(number_format($totalCarrinho, 2, ',', '.'), ENT_QUOTES, 'UTF-8') ?>
                </div>

                <form action="salvar-pedido-cliente.php" method="post" style="margin-top: 16px;">
                    <input type="hidden" name="acao" value="finalizar">
                    <div class="form-grid">
                        <div>
                            <label for="data_entrega">Data de entrega</label>
                            <select id="data_entrega" name="data_entrega" required>
                                <option value="">Selecione a data</option>
                                <?php foreach ($opcoesDataEntrega as $opcao): ?>
                                    <?php
                                    $selecionado = $dataEntregaPadrao === $opcao['valor'];
                                    $indisponivel = !$opcao['disponivel'];
                                    ?>
                                    <option
                                        value="<?= htmlspecialchars($opcao['valor'], ENT_QUOTES, 'UTF-8') ?>"
                                        <?= $selecionado ? 'selected' : '' ?>
                                        <?= $indisponivel ? 'disabled' : '' ?>
                                    >
                                        <?= htmlspecialchars($opcao['label'] . ($indisponivel ? ' - Indisponivel (limite atingido)' : ' - Disponivel (' . $opcao['qtd'] . '/' . LIMITE_UNIDADES_DIA . ')'), ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="hora_entrega">Horario de entrega</label>
                            <select id="hora_entrega" name="hora_entrega" required>
                                <option value="">Selecione o horario</option>
                                <?php for ($h = 8; $h <= 20; $h++): ?>
                                    <?php foreach (['00', '30'] as $m): ?>
                                        <?php $hora = str_pad((string) $h, 2, '0', STR_PAD_LEFT) . ':' . $m; ?>
                                        <option value="<?= $hora ?>" <?= $horaEntregaPadrao === $hora ? 'selected' : '' ?>><?= $hora ?></option>
                                    <?php endforeach; ?>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                    <div class="actions">
                        <button class="button" type="submit">Finalizar pedido</button>
                    </div>
                </form>
            <?php else: ?>
                <div class="card-mini">Seu pedido esta vazio. Adicione sabores abaixo.</div>
            <?php endif; ?>
        </section>

        <section class="card">
            <h2 class="section-title">Produtos</h2>

            <?php if ($itensCardapio): ?>
                <div class="produtos">
                    <?php foreach ($itensCardapio as $item): ?>
                        <?php
                        $foto = trim((string) ($item['foto_produto'] ?? ''));
                        $fotoValida = $foto !== '' && is_file(__DIR__ . '/' . $foto);
                        ?>
                        <article class="produto-card">
                            <div class="produto-media">
                                <?php if ($fotoValida): ?>
                                    <img class="produto-foto" src="<?= htmlspecialchars($foto, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars((string) $item['nome_produto'], ENT_QUOTES, 'UTF-8') ?>">
                                <?php else: ?>
                                    <img class="produto-foto" src="logo-bolos-da-gal.png" alt="Bolos da Gal">
                                <?php endif; ?>
                            </div>
                            <div class="produto-body">
                                <p class="produto-nome"><?= htmlspecialchars((string) $item['nome_produto'], ENT_QUOTES, 'UTF-8') ?></p>
                                <p class="produto-preco">R$ <?= htmlspecialchars(number_format((float) $item['preco_venda'], 2, ',', '.'), ENT_QUOTES, 'UTF-8') ?></p>
                                <form class="produto-actions" action="salvar-pedido-cliente.php" method="post">
                                    <input type="hidden" name="acao" value="adicionar">
                                    <input type="hidden" name="item_id" value="<?= (int) $item['id'] ?>">
                                    <input class="produto-qty" type="number" name="quantidade" min="1" step="1" value="1" required>
                                    <button class="button small" type="submit">Adicionar ao pedido</button>
                                </form>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="lead">Nenhum produto cadastrado no momento.</p>
            <?php endif; ?>
        </section>
    </main>

</body>
<?php renderClienteIdleLogoutScript(); ?>
</html>
