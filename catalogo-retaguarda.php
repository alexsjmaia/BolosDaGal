<?php
require __DIR__ . '/auth.php';
require __DIR__ . '/db.php';

$sucesso = $_SESSION['catalogo_retaguarda_sucesso'] ?? '';
$erro = $_SESSION['catalogo_retaguarda_erro'] ?? '';
unset($_SESSION['catalogo_retaguarda_sucesso'], $_SESSION['catalogo_retaguarda_erro']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = trim((string) ($_POST['acao'] ?? ''));
    $itemId = (int) ($_POST['item_id'] ?? 0);

    if ($acao === 'alternar_catalogo' && $itemId > 0) {
        try {
            $stmt = $pdo->prepare(
                'UPDATE itens
                 SET mostrar_catalogo = CASE
                     WHEN mostrar_catalogo = 1 THEN 0
                     ELSE 1
                 END
                 WHERE id = :id'
            );
            $stmt->execute(['id' => $itemId]);

            if ($stmt->rowCount() > 0) {
                $_SESSION['catalogo_retaguarda_sucesso'] = 'Status do item no catalogo atualizado.';
            } else {
                $_SESSION['catalogo_retaguarda_erro'] = 'Nao foi possivel atualizar este item.';
            }
        } catch (PDOException $e) {
            $_SESSION['catalogo_retaguarda_erro'] = 'Erro ao atualizar o status do item.';
        }

        header('Location: catalogo-retaguarda.php');
        exit;
    }
}

$stmt = $pdo->query(
    'SELECT
        i.id,
        i.codigo_produto,
        i.nome_produto,
        i.foto_produto,
        i.preco_venda,
        i.mostrar_catalogo,
        COALESCE(vendas_item.qtd_vendida, 0) AS qtd_vendida
     FROM itens i
     LEFT JOIN (
        SELECT item_id, SUM(quantidade) AS qtd_vendida
        FROM pedidos_clientes
        GROUP BY item_id
     ) AS vendas_item ON vendas_item.item_id = i.id
     ORDER BY i.mostrar_catalogo DESC, i.preco_venda ASC, i.nome_produto ASC'
);
$itens = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catalogo com Edicao - Bolos da Gal</title>
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
            margin: 0 0 8px;
            color: var(--secondary);
        }

        .lead {
            color: var(--muted);
            margin: 0 0 20px;
        }

        .actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 18px;
        }

        .button {
            padding: 12px 16px;
            border: 0;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: #fff;
            text-decoration: none;
            font-size: 0.95rem;
            font-weight: bold;
            cursor: pointer;
        }

        .button.secondary {
            background: #e9d8dc;
            color: #5c4741;
        }

        .alert {
            margin-bottom: 16px;
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

        .grid {
            display: grid;
            gap: 16px;
        }

        @media (min-width: 760px) {
            .grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        .produto {
            border: 1px solid var(--border);
            border-radius: 16px;
            overflow: hidden;
            background: #fff;
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
            display: grid;
            gap: 10px;
        }

        .produto-titulo {
            margin: 0;
            color: var(--secondary);
            font-weight: bold;
        }

        .produto-info {
            margin: 0;
            color: var(--muted);
            font-size: 0.95rem;
        }

        .status {
            font-weight: bold;
        }

        .status.visivel { color: #2b6f44; }
        .status.oculto { color: #9f2d20; }
    </style>
</head>
<body>
    <main class="layout">
        <section class="card">
            <h1>Catalogo com Edicao</h1>
            <p class="lead">Acesse rapidamente os itens do catalogo e altere visibilidade ou dados do produto.</p>

            <div class="actions">
                <a class="button" href="dashboard.php">Voltar ao menu</a>
                <a class="button secondary" href="cadastrar-item.php">Cadastrar novo item</a>
            </div>

            <?php if ($erro !== ''): ?>
                <div class="alert error"><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <?php if ($sucesso !== ''): ?>
                <div class="alert success"><?= htmlspecialchars($sucesso, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <?php if ($itens): ?>
                <div class="grid">
                    <?php foreach ($itens as $item): ?>
                        <?php
                        $foto = trim((string) ($item['foto_produto'] ?? ''));
                        $fotoValida = $foto !== '' && is_file(__DIR__ . '/' . $foto);
                        $visivel = (int) ($item['mostrar_catalogo'] ?? 1) === 1;
                        ?>
                        <article class="produto">
                            <?php if ($fotoValida): ?>
                                <img class="produto-foto" src="<?= htmlspecialchars($foto, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars((string) $item['nome_produto'], ENT_QUOTES, 'UTF-8') ?>">
                            <?php else: ?>
                                <img class="produto-foto" src="logo-bolos-da-gal.png" alt="Bolos da Gal">
                            <?php endif; ?>

                            <div class="produto-body">
                                <p class="produto-titulo"><?= htmlspecialchars((string) $item['nome_produto'], ENT_QUOTES, 'UTF-8') ?></p>
                                <p class="produto-info">Codigo: <?= htmlspecialchars((string) $item['codigo_produto'], ENT_QUOTES, 'UTF-8') ?></p>
                                <p class="produto-info">Preco: R$ <?= htmlspecialchars(number_format((float) $item['preco_venda'], 2, ',', '.'), ENT_QUOTES, 'UTF-8') ?></p>
                                <p class="produto-info">Vendido: <?= (int) $item['qtd_vendida'] ?> unidade(s)</p>
                                <p class="produto-info">
                                    Status:
                                    <span class="status <?= $visivel ? 'visivel' : 'oculto' ?>">
                                        <?= $visivel ? 'Visivel no catalogo' : 'Oculto do catalogo' ?>
                                    </span>
                                </p>

                                <div class="actions" style="margin:0;">
                                    <a class="button" href="alterar-item.php?codigo=<?= urlencode((string) $item['codigo_produto']) ?>">Editar item</a>
                                    <form method="post">
                                        <input type="hidden" name="acao" value="alternar_catalogo">
                                        <input type="hidden" name="item_id" value="<?= (int) $item['id'] ?>">
                                        <button class="button secondary" type="submit"><?= $visivel ? 'Desabilitar no catalogo' : 'Habilitar no catalogo' ?></button>
                                    </form>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="lead">Nenhum item cadastrado no momento.</p>
            <?php endif; ?>
        </section>
    </main>
</body>
<?php renderIdleLogoutScript(); ?>
</html>
