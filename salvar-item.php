<?php
require __DIR__ . '/auth.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: cadastrar-item.php');
    exit;
}

require __DIR__ . '/db.php';

const ITEM_UPLOAD_DIR = __DIR__ . '/uploads/produtos';

$codigoProduto = trim($_POST['codigo_produto'] ?? '');
$nomeProduto = trim($_POST['nome_produto'] ?? '');
$ncm = trim($_POST['ncm'] ?? '');
$precoCusto = str_replace(',', '.', trim($_POST['preco_custo'] ?? ''));
$precoVenda = str_replace(',', '.', trim($_POST['preco_venda'] ?? ''));
$mostrarCatalogo = (($_POST['mostrar_catalogo'] ?? '1') === '0') ? '0' : '1';

$_SESSION['item_dados'] = [
    'codigo_produto' => $codigoProduto,
    'nome_produto' => $nomeProduto,
    'ncm' => $ncm,
    'preco_custo' => $precoCusto,
    'preco_venda' => $precoVenda,
    'mostrar_catalogo' => $mostrarCatalogo,
];

if ($codigoProduto === '' || $nomeProduto === '' || $ncm === '' || $precoCusto === '' || $precoVenda === '') {
    $_SESSION['item_erro'] = 'Preencha todos os campos do item.';
    header('Location: cadastrar-item.php');
    exit;
}

if (!is_numeric($precoCusto) || !is_numeric($precoVenda)) {
    $_SESSION['item_erro'] = 'Informe valores numericos para preco de custo e preco de venda.';
    header('Location: cadastrar-item.php');
    exit;
}

$fotoProdutoPath = null;
$arquivoFoto = $_FILES['foto_produto'] ?? null;

if (is_array($arquivoFoto) && (int) ($arquivoFoto['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
    if (!currentUserCanUploadPhotos()) {
        $_SESSION['item_erro'] = 'Somente os usuarios bolos e root podem carregar fotos.';
        header('Location: cadastrar-item.php');
        exit;
    }

    if ((int) $arquivoFoto['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['item_erro'] = 'Nao foi possivel enviar a foto do produto.';
        header('Location: cadastrar-item.php');
        exit;
    }

    $extensao = strtolower((string) pathinfo((string) ($arquivoFoto['name'] ?? ''), PATHINFO_EXTENSION));
    $extensoesPermitidas = ['jpg', 'jpeg', 'png', 'webp'];

    if (!in_array($extensao, $extensoesPermitidas, true)) {
        $_SESSION['item_erro'] = 'Formato de foto invalido. Use JPG, PNG ou WEBP.';
        header('Location: cadastrar-item.php');
        exit;
    }

    $tamanhoArquivo = (int) ($arquivoFoto['size'] ?? 0);
    if ($tamanhoArquivo <= 0 || $tamanhoArquivo > (5 * 1024 * 1024)) {
        $_SESSION['item_erro'] = 'A foto precisa ter no maximo 5MB.';
        header('Location: cadastrar-item.php');
        exit;
    }

    if (!is_dir(ITEM_UPLOAD_DIR)) {
        mkdir(ITEM_UPLOAD_DIR, 0777, true);
    }

    $nomeArquivo = 'produto-' . date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . $extensao;
    $destino = ITEM_UPLOAD_DIR . '/' . $nomeArquivo;

    if (!move_uploaded_file((string) $arquivoFoto['tmp_name'], $destino)) {
        $_SESSION['item_erro'] = 'Nao foi possivel salvar a foto do produto.';
        header('Location: cadastrar-item.php');
        exit;
    }

    $fotoProdutoPath = 'uploads/produtos/' . $nomeArquivo;
}

try {
    $stmt = $pdo->prepare(
        'INSERT INTO itens (codigo_produto, nome_produto, ncm, foto_produto, preco_custo, preco_venda, mostrar_catalogo)
         VALUES (:codigo_produto, :nome_produto, :ncm, :foto_produto, :preco_custo, :preco_venda, :mostrar_catalogo)'
    );

    $stmt->execute([
        'codigo_produto' => $codigoProduto,
        'nome_produto' => $nomeProduto,
        'ncm' => $ncm,
        'foto_produto' => $fotoProdutoPath,
        'preco_custo' => number_format((float) $precoCusto, 2, '.', ''),
        'preco_venda' => number_format((float) $precoVenda, 2, '.', ''),
        'mostrar_catalogo' => (int) $mostrarCatalogo,
    ]);
} catch (PDOException $e) {
    if ($fotoProdutoPath !== null) {
        $arquivoSalvo = __DIR__ . '/' . $fotoProdutoPath;
        if (is_file($arquivoSalvo)) {
            unlink($arquivoSalvo);
        }
    }

    if ((int) $e->getCode() === 23000) {
        $_SESSION['item_erro'] = 'Ja existe um item cadastrado com esse codigo do produto.';
    } else {
        $_SESSION['item_erro'] = 'Nao foi possivel salvar o item no banco de dados.';
    }

    header('Location: cadastrar-item.php');
    exit;
}

unset($_SESSION['item_dados']);
$_SESSION['item_sucesso'] = 'Item cadastrado com sucesso.';

header('Location: cadastrar-item.php');
exit;
