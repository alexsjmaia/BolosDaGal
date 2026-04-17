<?php
require __DIR__ . '/db.php';
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: cliente-cadastro.php');
    exit;
}

$whatsappEntrada = trim($_POST['whatsapp'] ?? '');
$nome = trim($_POST['nome'] ?? '');
$senha = (string) ($_POST['senha'] ?? '');
$senhaConfirmacao = (string) ($_POST['senha_confirmacao'] ?? '');
$whatsapp = preg_replace('/\D+/', '', $whatsappEntrada) ?? '';

$_SESSION['cliente_cadastro_dados'] = [
    'whatsapp' => $whatsappEntrada,
    'nome' => $nome,
];

if ($whatsappEntrada === '' || $nome === '' || $senha === '' || $senhaConfirmacao === '') {
    $_SESSION['cliente_cadastro_erro'] = 'Preencha todos os campos.';
    header('Location: cliente-cadastro.php');
    exit;
}

if (strlen($whatsapp) !== 11 || substr($whatsapp, 2, 1) !== '9') {
    $_SESSION['cliente_cadastro_erro'] = 'Informe um Whatsapp valido com 11 numeros e nono digito 9.';
    header('Location: cliente-cadastro.php');
    exit;
}

$nomeLength = function_exists('mb_strlen') ? mb_strlen($nome) : strlen($nome);
if ($nomeLength < 5) {
    $_SESSION['cliente_cadastro_erro'] = 'O nome precisa ser completo.';
    header('Location: cliente-cadastro.php');
    exit;
}

if ($senha !== $senhaConfirmacao) {
    $_SESSION['cliente_cadastro_erro'] = 'As senhas precisam ser iguais.';
    header('Location: cliente-cadastro.php');
    exit;
}

$senhaHash = password_hash($senha, PASSWORD_DEFAULT);

try {
    $stmt = $pdo->prepare(
        'SELECT id, senha_hash
         FROM clientes
         WHERE whatsapp = :whatsapp
         LIMIT 1'
    );
    $stmt->execute(['whatsapp' => $whatsapp]);
    $clienteExistente = $stmt->fetch();

    if ($clienteExistente) {
        $senhaExistente = (string) ($clienteExistente['senha_hash'] ?? '');
        if (trim($senhaExistente) !== '') {
            $_SESSION['cliente_cadastro_erro_html'] = 'Este numero ja possui cadastro. Entre em contato no <a href="https://wa.me/5511973229865" target="_blank" rel="noopener noreferrer">WhatsApp 11 97322-9865</a>.';
            header('Location: cliente-cadastro.php');
            exit;
        }

        $stmtUpdate = $pdo->prepare(
            'UPDATE clientes
             SET nome = :nome,
                 senha_hash = :senha_hash
             WHERE id = :id'
        );
        $stmtUpdate->execute([
            'nome' => $nome,
            'senha_hash' => $senhaHash,
            'id' => $clienteExistente['id'],
        ]);
    } else {
        $stmtInsert = $pdo->prepare(
            'INSERT INTO clientes (nome, whatsapp, senha_hash, saldo_cashback)
             VALUES (:nome, :whatsapp, :senha_hash, 0.00)'
        );
        $stmtInsert->execute([
            'nome' => $nome,
            'whatsapp' => $whatsapp,
            'senha_hash' => $senhaHash,
        ]);
    }
} catch (PDOException $e) {
    $_SESSION['cliente_cadastro_erro'] = 'Nao foi possivel concluir o cadastro.';
    header('Location: cliente-cadastro.php');
    exit;
}

unset($_SESSION['cliente_cadastro_dados']);
$_SESSION['cliente_login_sucesso'] = 'Cadastro concluido. Agora faca login com telefone e senha.';
header('Location: cliente-login.php');
exit;
