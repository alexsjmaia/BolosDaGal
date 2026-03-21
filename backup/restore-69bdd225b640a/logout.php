<?php
session_start();
$motivo = $_GET['motivo'] ?? '';
session_unset();
session_destroy();

if ($motivo === 'expirado') {
    session_start();
    $_SESSION['erro'] = 'Sessao encerrada por inatividade. Faca login novamente.';
}

header('Location: index.php');
exit;
