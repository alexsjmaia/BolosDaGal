<?php
if (!defined('DISABLE_SESSION_TIMEOUT')) {
    define('DISABLE_SESSION_TIMEOUT', true);
}

require __DIR__ . '/auth.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: alterar-cliente.php');
    exit;
}

require __DIR__ . '/db.php';

$acao = trim((string) ($_POST['acao'] ?? 'atualizar'));
$id = (int) ($_POST['id'] ?? 0);
$nome = trim((string) ($_POST['nome'] ?? ''));
$whatsappEntrada = trim((string) ($_POST['whatsapp'] ?? ''));
$whatsappNumerico = preg_replace('/\D+/', '', $whatsappEntrada) ?? '';

if ($id <= 0) {
    $_SESSION['alterar_cliente_erro'] = 'Cliente invalido para alteracao.';
    header('Location: alterar-cliente.php?cliente_id=' . urlencode((string) $id));
    exit;
}

try {
    $stmtCliente = $pdo->prepare(
        'SELECT id, nome, whatsapp
         FROM clientes
         WHERE id = :id
         LIMIT 1'
    );
    $stmtCliente->execute(['id' => $id]);
    $clienteAtual = $stmtCliente->fetch();

    if (!$clienteAtual) {
        $_SESSION['alterar_cliente_erro'] = 'Cliente nao encontrado para alteracao.';
        header('Location: alterar-cliente.php');
        exit;
    }

    if ($acao === 'excluir') {
        $senhaExclusao = trim((string) ($_POST['senha_exclusao'] ?? ''));
        if ($senhaExclusao !== '801973') {
            $_SESSION['alterar_cliente_erro'] = 'Senha invalida para excluir o cliente.';
            header('Location: alterar-cliente.php?cliente_id=' . urlencode((string) $id));
            exit;
        }

        $pdo->beginTransaction();

        $stmtDeleteHistorico = $pdo->prepare(
            'DELETE FROM vendas_clientes
             WHERE cliente_whatsapp = :cliente_whatsapp'
        );
        $stmtDeleteHistorico->execute([
            'cliente_whatsapp' => (string) $clienteAtual['whatsapp'],
        ]);

        $stmtDeleteCliente = $pdo->prepare(
            'DELETE FROM clientes
             WHERE id = :id'
        );
        $stmtDeleteCliente->execute(['id' => $id]);

        if ($stmtDeleteCliente->rowCount() <= 0) {
            throw new RuntimeException('Cliente nao encontrado para exclusao.');
        }

        $pdo->commit();

        if (
            isset($_SESSION['cliente_id']) &&
            (int) $_SESSION['cliente_id'] === $id
        ) {
            unset(
                $_SESSION['cliente_id'],
                $_SESSION['cliente_nome'],
                $_SESSION['cliente_whatsapp'],
                $_SESSION['cliente_saldo_cashback']
            );
        }

        $_SESSION['alterar_cliente_sucesso'] = 'Cliente excluido com sucesso.';
        header('Location: alterar-cliente.php');
        exit;
    }

    if ($nome === '' || $whatsappNumerico === '') {
        $_SESSION['alterar_cliente_erro'] = 'Preencha nome e WhatsApp para alterar o cliente.';
        header('Location: alterar-cliente.php?cliente_id=' . urlencode((string) $id));
        exit;
    }

    if (strlen($whatsappNumerico) !== 11 || substr($whatsappNumerico, 2, 1) !== '9') {
        $_SESSION['alterar_cliente_erro'] = 'Informe um WhatsApp valido com 11 numeros, incluindo DDD, e com o nono digito igual a 9.';
        header('Location: alterar-cliente.php?cliente_id=' . urlencode((string) $id));
        exit;
    }

    $tamanhoNome = function_exists('mb_strlen') ? mb_strlen($nome) : strlen($nome);
    if ($tamanhoNome < 5) {
        $_SESSION['alterar_cliente_erro'] = 'O nome precisa ser completo.';
        header('Location: alterar-cliente.php?cliente_id=' . urlencode((string) $id));
        exit;
    }

    $whatsappAntigo = (string) $clienteAtual['whatsapp'];

    $stmtDuplicado = $pdo->prepare(
        'SELECT id
         FROM clientes
         WHERE whatsapp = :whatsapp
           AND id <> :id
         LIMIT 1'
    );
    $stmtDuplicado->execute([
        'whatsapp' => $whatsappNumerico,
        'id' => $id,
    ]);

    if ($stmtDuplicado->fetch()) {
        $_SESSION['alterar_cliente_erro'] = 'Ja existe outro cliente com esse WhatsApp.';
        header('Location: alterar-cliente.php?cliente_id=' . urlencode((string) $id));
        exit;
    }

    $pdo->beginTransaction();

    $stmtUpdateCliente = $pdo->prepare(
        'UPDATE clientes
         SET nome = :nome,
             whatsapp = :whatsapp
         WHERE id = :id'
    );
    $stmtUpdateCliente->execute([
        'nome' => $nome,
        'whatsapp' => $whatsappNumerico,
        'id' => $id,
    ]);

    $stmtUpdateVendasClientes = $pdo->prepare(
        'UPDATE vendas_clientes
         SET cliente_nome = :cliente_nome,
             cliente_whatsapp = :cliente_whatsapp
         WHERE cliente_whatsapp = :cliente_whatsapp_antigo'
    );
    $stmtUpdateVendasClientes->execute([
        'cliente_nome' => $nome,
        'cliente_whatsapp' => $whatsappNumerico,
        'cliente_whatsapp_antigo' => $whatsappAntigo,
    ]);

    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $_SESSION['alterar_cliente_erro'] = 'Nao foi possivel alterar o cliente e os dados relacionados.';
    header('Location: alterar-cliente.php?cliente_id=' . urlencode((string) $id));
    exit;
}

if (
    isset($_SESSION['cliente_id']) &&
    (int) $_SESSION['cliente_id'] === $id
) {
    $_SESSION['cliente_nome'] = $nome;
    $_SESSION['cliente_whatsapp'] = $whatsappNumerico;
}

$_SESSION['alterar_cliente_sucesso'] = 'Cliente alterado com sucesso.';
header('Location: alterar-cliente.php?cliente_id=' . urlencode((string) $id));
exit;
