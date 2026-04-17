<?php
require __DIR__ . '/cliente-auth.php';
$motivo = $_GET['motivo'] ?? '';
limparSessaoCliente();

if ($motivo === 'expirado') {
    $_SESSION['cliente_login_erro'] = 'Sessao encerrada por inatividade. Faca login novamente.';
} else {
    $_SESSION['cliente_login_sucesso'] = 'Sessao encerrada com sucesso.';
}

header('Location: cliente-login.php');
exit;
