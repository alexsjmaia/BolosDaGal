<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

require __DIR__ . '/db.php';

$usuario = trim($_POST['usuario'] ?? '');
$senha = trim($_POST['senha'] ?? '');

if ($usuario === '' || $senha === '') {
    $_SESSION['erro'] = 'Preencha usuario e senha.';
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare('SELECT id, usuario, senha FROM usuarios WHERE usuario = :usuario LIMIT 1');
$stmt->execute(['usuario' => $usuario]);
$registro = $stmt->fetch();

if (!$registro || $registro['senha'] !== $senha) {
    $_SESSION['erro'] = 'Usuario ou senha invalidos.';
    header('Location: index.php');
    exit;
}

$_SESSION['usuario'] = $registro['usuario'];
$_SESSION['ultimo_acesso'] = time();

header('Location: dashboard.php');
exit;
