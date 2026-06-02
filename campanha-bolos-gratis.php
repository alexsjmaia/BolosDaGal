<?php
require __DIR__ . '/auth.php';
require __DIR__ . '/db.php';

$erro = $_SESSION['campanha_bolos_gratis_erro'] ?? '';
$sucesso = $_SESSION['campanha_bolos_gratis_sucesso'] ?? '';
$dados = $_SESSION['campanha_bolos_gratis_dados'] ?? [];

unset($_SESSION['campanha_bolos_gratis_erro'], $_SESSION['campanha_bolos_gratis_sucesso'], $_SESSION['campanha_bolos_gratis_dados']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = trim((string) ($_POST['acao'] ?? ''));
    $clienteId = (int) ($_POST['cliente_id'] ?? 0);

    if ($acao === 'brinde_entregue' && $clienteId > 0) {
        try {
            $stmt = $pdo->prepare(
                'UPDATE clientes
                 SET bolos_gratis_disponiveis = bolos_gratis_disponiveis - 1
                 WHERE id = :id
                   AND bolos_gratis_disponiveis > 0'
            );
            $stmt->execute(['id' => $clienteId]);

            if ($stmt->rowCount() > 0) {
                $_SESSION['campanha_bolos_gratis_sucesso'] = 'Brinde entregue registrado com sucesso.';
            } else {
                $_SESSION['campanha_bolos_gratis_erro'] = 'Nao foi possivel registrar o brinde entregue para este cliente.';
            }
        } catch (PDOException $e) {
            $_SESSION['campanha_bolos_gratis_erro'] = 'Erro ao registrar entrega de brinde.';
        }

        header('Location: campanha-bolos-gratis.php');
        exit;
    }
}

$stmtAtual = $pdo->query(
    'SELECT quantidade_para_ganhar, criado_em
     FROM campanhas_bolos_gratis
     ORDER BY id DESC
     LIMIT 1'
);
$campanhaAtual = $stmtAtual->fetch();

$stmtHistorico = $pdo->query(
    'SELECT quantidade_para_ganhar, criado_em
     FROM campanhas_bolos_gratis
     ORDER BY id DESC
     LIMIT 20'
);
$historico = $stmtHistorico->fetchAll();

$clientesFidelidade = [];
if ($campanhaAtual) {
    $quantidadeParaGanhar = (int) ($campanhaAtual['quantidade_para_ganhar'] ?? 0);
    $dataInicioCampanha = (string) ($campanhaAtual['criado_em'] ?? '');

    if ($quantidadeParaGanhar > 0 && $dataInicioCampanha !== '') {
        $stmtClientesFidelidade = $pdo->prepare(
            "SELECT
                c.id,
                c.nome,
                c.whatsapp,
                c.bolos_gratis_disponiveis,
                c.fidelidade_quantidade_acumulada,
                COALESCE(SUM(v.quantidade), 0) AS quantidade_comprada_periodo
             FROM clientes c
             INNER JOIN vendas_clientes v
                ON v.cliente_whatsapp = c.whatsapp
               AND v.data_compra >= :data_inicio
             INNER JOIN itens i
                ON i.id = v.item_id
               AND LOWER(TRIM(COALESCE(i.categoria, ''))) = 'bolos'
             WHERE c.fidelidade_quantidade_acumulada > 0
             GROUP BY
                c.id,
                c.nome,
                c.whatsapp,
                c.bolos_gratis_disponiveis,
                c.fidelidade_quantidade_acumulada
             ORDER BY
                CASE
                    WHEN c.bolos_gratis_disponiveis > 0 THEN 0
                    ELSE 1
                END ASC,
                c.bolos_gratis_disponiveis DESC,
                c.fidelidade_quantidade_acumulada DESC,
                c.nome ASC,
                c.whatsapp ASC"
        );
        $stmtClientesFidelidade->execute([
            'data_inicio' => $dataInicioCampanha,
        ]);
        $clientesFidelidade = $stmtClientesFidelidade->fetchAll();
    }
}

function normalizarWhatsappLink(string $whatsapp): string
{
    $digitos = preg_replace('/\D+/', '', $whatsapp) ?? '';

    if (strlen($digitos) === 11) {
        $digitos = '55' . $digitos;
    }

    if (strlen($digitos) === 13 && strpos($digitos, '55') === 0) {
        return $digitos;
    }

    return '';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campanha Compre X e Ganhe 1</title>
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

        h1, h2 {
            margin-top: 0;
            color: var(--secondary);
        }

        .lead {
            color: var(--muted);
            margin-bottom: 22px;
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

        .atual {
            margin-bottom: 18px;
            padding: 14px 16px;
            border: 1px solid var(--border);
            border-radius: 12px;
            background: #fff7f8;
            color: var(--secondary);
            font-weight: bold;
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
        }

        th {
            color: var(--secondary);
        }

        .table-wrap {
            overflow-x: auto;
        }

        .status-ok {
            color: #2b6f44;
            font-weight: bold;
        }

        .status-wait {
            color: var(--muted);
            font-weight: bold;
        }

        .whatsapp-link {
            color: var(--secondary);
            font-weight: bold;
            text-decoration: none;
        }

        .whatsapp-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <main class="layout">
        <section class="card">
            <h1>Campanha Compre X e Ganhe 1</h1>
            <p class="lead">Defina a quantidade de bolos comprados para o cliente ganhar 1 bolo gratis.</p>

            <?php if ($erro !== ''): ?>
                <div class="alert error"><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <?php if ($sucesso !== ''): ?>
                <div class="alert success"><?= htmlspecialchars($sucesso, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <div class="atual">
                Regra vigente:
                <?php if ($campanhaAtual): ?>
                    A cada <?= (int) $campanhaAtual['quantidade_para_ganhar'] ?> bolo(s), ganha 1 gratis
                    (desde <?= htmlspecialchars(date('d/m/Y H:i:s', strtotime((string) $campanhaAtual['criado_em'])), ENT_QUOTES, 'UTF-8') ?>)
                <?php else: ?>
                    Nenhuma regra cadastrada.
                <?php endif; ?>
            </div>

            <form action="salvar-campanha-bolos-gratis.php" method="post">
                <div>
                    <label for="quantidade_para_ganhar">Quantidade para ganhar 1 gratis</label>
                    <input type="number" id="quantidade_para_ganhar" name="quantidade_para_ganhar" min="1" step="1" value="<?= htmlspecialchars((string) ($dados['quantidade_para_ganhar'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Ex.: 10" required>
                </div>

                <div class="actions">
                    <button class="button" type="submit">Salvar regra</button>
                    <a class="button secondary" href="dashboard.php">Voltar ao menu</a>
                </div>
            </form>
        </section>

        <section class="card">
            <h2>Historico de regras</h2>

            <?php if ($historico): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Regra</th>
                            <th>Data de cadastro</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($historico as $item): ?>
                            <tr>
                                <td>A cada <?= (int) $item['quantidade_para_ganhar'] ?> bolo(s), ganha 1</td>
                                <td><?= htmlspecialchars(date('d/m/Y H:i:s', strtotime((string) $item['criado_em'])), ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="lead">Nenhuma regra cadastrada ainda.</p>
            <?php endif; ?>
        </section>

        <section class="card">
            <h2>Clientes na campanha vigente</h2>
            <?php if ($campanhaAtual): ?>
                <p class="lead">
                    Considerando compras a partir de
                    <strong><?= htmlspecialchars(date('d/m/Y H:i:s', strtotime((string) $campanhaAtual['criado_em'])), ENT_QUOTES, 'UTF-8') ?></strong>
                    com regra de
                    <strong>a cada <?= (int) $campanhaAtual['quantidade_para_ganhar'] ?> bolo(s), ganha 1</strong>.
                </p>
            <?php else: ?>
                <p class="lead">Cadastre uma regra para exibir os clientes da campanha.</p>
            <?php endif; ?>

            <?php if ($campanhaAtual && $clientesFidelidade): ?>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>WhatsApp</th>
                                <th>Comprados no periodo</th>
                                <th>Acumulado para proximo</th>
                                <th>Faltam para ganhar 1</th>
                                <th>Brindes disponiveis</th>
                                <th>Acao</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($clientesFidelidade as $cliente): ?>
                                <?php
                                $regra = max((int) ($campanhaAtual['quantidade_para_ganhar'] ?? 0), 1);
                                $acumulado = (float) ($cliente['fidelidade_quantidade_acumulada'] ?? 0);
                                $acumuladoExibicao = (int) floor($acumulado);
                                $faltam = $regra - $acumuladoExibicao;
                                if ($faltam <= 0) {
                                    $faltam = $regra;
                                }
                                $compradosNoPeriodo = (int) floor((float) $cliente['quantidade_comprada_periodo']);
                                $brindesDisponiveis = (int) ($cliente['bolos_gratis_disponiveis'] ?? 0);
                                $whatsappNumero = normalizarWhatsappLink((string) $cliente['whatsapp']);
                                if ($brindesDisponiveis > 0) {
                                    $mensagem = 'Parabens, voce comprou 10 bolos com a gente e ganhou um bolo da promocao. E ja tem ' . $acumuladoExibicao . ' bolos contando para ganhar o proximo.';
                                } elseif ($compradosNoPeriodo === 1) {
                                    $mensagem = "Ola\n\nSeu cartao fidelidade da Bolos da Gal ja comecou.\n\nSua 1a marcacao ja esta garantida e voce ja comecou a acumular para ganhar seu bolo caseiro gratis.\n\nObrigada pela confianca.";
                                } elseif ($compradosNoPeriodo === 5) {
                                    $mensagem = "Ola\n\nSeu cartao fidelidade ja chegou a metade.\n\nVoce ja acumulou 5 marcacoes e esta cada vez mais perto de ganhar seu bolo caseiro gratis.\n\nObrigada por fazer parte da Bolos da Gal.";
                                } elseif ($compradosNoPeriodo === 8) {
                                    $mensagem = "Ola\n\nSeu cartao fidelidade ja esta com 8 marcacoes.\n\nSeu bolo caseiro gratis ja esta quase garantido.\n\nMuito obrigada por continuar escolhendo a Bolos da Gal.";
                                } else {
                                    $mensagem = "Ola 😊\n\nSeu cartao fidelidade ja esta com {$compradosNoPeriodo} marcacoes 🍰❤️\n\nSeu bolo caseiro gratis esta ficando cada vez mais perto 😊\n\nMuito obrigada por continuar escolhendo a Bolos da Gal ❤️";
                                }
                                $whatsappLink = $whatsappNumero !== ''
                                    ? 'https://wa.me/' . $whatsappNumero . '?text=' . rawurlencode($mensagem)
                                    : '';
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars((string) $cliente['nome'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>
                                        <?php if ($whatsappLink !== ''): ?>
                                            <a class="whatsapp-link" href="<?= htmlspecialchars($whatsappLink, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer">
                                                <?= htmlspecialchars((string) $cliente['whatsapp'], ENT_QUOTES, 'UTF-8') ?>
                                            </a>
                                        <?php else: ?>
                                            <?= htmlspecialchars((string) $cliente['whatsapp'], ENT_QUOTES, 'UTF-8') ?>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= $compradosNoPeriodo ?></td>
                                    <td><?= $acumuladoExibicao ?></td>
                                    <td>
                                        <?php if ($brindesDisponiveis > 0): ?>
                                            <span class="status-ok">Apto para brinde</span>
                                        <?php else: ?>
                                            <span class="status-wait"><?= $faltam ?> bolo(s)</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= $brindesDisponiveis ?></td>
                                    <td>
                                        <?php if ($brindesDisponiveis > 0): ?>
                                            <form method="post">
                                                <input type="hidden" name="acao" value="brinde_entregue">
                                                <input type="hidden" name="cliente_id" value="<?= (int) $cliente['id'] ?>">
                                                <button class="button" type="submit">Brinde entregue</button>
                                            </form>
                                        <?php else: ?>
                                            <span class="status-wait">Aguardando</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php elseif ($campanhaAtual): ?>
                <p class="lead">Nenhum cliente com compras no periodo da regra vigente.</p>
            <?php endif; ?>
        </section>
    </main>
</body>
<?php renderIdleLogoutScript(); ?>
</html>
