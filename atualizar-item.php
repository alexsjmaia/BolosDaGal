<?php
require __DIR__ . '/auth.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: alterar-item.php');
    exit;
}

require __DIR__ . '/db.php';

const ITEM_UPLOAD_DIR = __DIR__ . '/uploads/produtos';

$id = (int) ($_POST['id'] ?? 0);
$nomeProduto = trim($_POST['nome_produto'] ?? '');
$ncm = trim($_POST['ncm'] ?? '');
$precoCusto = str_replace(',', '.', trim($_POST['preco_custo'] ?? ''));
$precoVenda = str_replace(',', '.', trim($_POST['preco_venda'] ?? ''));
$mostrarCatalogo = (($_POST['mostrar_catalogo'] ?? '1') === '0') ? 0 : 1;

if ($id <= 0 || $nomeProduto === '' || $ncm === '' || $precoCusto === '' || $precoVenda === '') {
    $_SESSION['alterar_item_erro'] = 'Preencha todos os campos para alterar o item.';
    header('Location: alterar-item.php');
    exit;
}

if (!is_numeric($precoCusto) || !is_numeric($precoVenda)) {
    $_SESSION['alterar_item_erro'] = 'Informe valores validos para preco de custo e preco de venda.';
    header('Location: alterar-item.php');
    exit;
}

$stmtCodigo = $pdo->prepare('SELECT codigo_produto, foto_produto FROM itens WHERE id = :id LIMIT 1');
$stmtCodigo->execute(['id' => $id]);
$registro = $stmtCodigo->fetch();

if (!$registro) {
    $_SESSION['alterar_item_erro'] = 'Item nao encontrado.';
    header('Location: alterar-item.php');
    exit;
}

$codigoProduto = $registro['codigo_produto'];
$fotoProdutoAtual = $registro['foto_produto'] ?? null;
$fotoProdutoNova = $fotoProdutoAtual;

$arquivoFoto = $_FILES['foto_produto'] ?? null;
if (is_array($arquivoFoto) && (int) ($arquivoFoto['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
    if (!currentUserCanUploadPhotos()) {
        $_SESSION['alterar_item_erro'] = 'Somente os usuarios bolos e root podem alterar fotos.';
        header('Location: alterar-item.php?codigo=' . urlencode($codigoProduto));
        exit;
    }

    if ((int) $arquivoFoto['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['alterar_item_erro'] = 'Nao foi possivel enviar a foto do produto.';
        header('Location: alterar-item.php?codigo=' . urlencode($codigoProduto));
        exit;
    }

    $extensao = strtolower((string) pathinfo((string) ($arquivoFoto['name'] ?? ''), PATHINFO_EXTENSION));
    $extensoesPermitidas = ['jpg', 'jpeg', 'png', 'webp'];

    if (!in_array($extensao, $extensoesPermitidas, true)) {
        $_SESSION['alterar_item_erro'] = 'Formato de foto invalido. Use JPG, PNG ou WEBP.';
        header('Location: alterar-item.php?codigo=' . urlencode($codigoProduto));
        exit;
    }

    $tamanhoArquivo = (int) ($arquivoFoto['size'] ?? 0);
    if ($tamanhoArquivo <= 0 || $tamanhoArquivo > (5 * 1024 * 1024)) {
        $_SESSION['alterar_item_erro'] = 'A foto precisa ter no maximo 5MB.';
        header('Location: alterar-item.php?codigo=' . urlencode($codigoProduto));
        exit;
    }

    if (!is_dir(ITEM_UPLOAD_DIR)) {
        mkdir(ITEM_UPLOAD_DIR, 0777, true);
    }

    $nomeArquivo = 'produto-' . date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . $extensao;
    $destino = ITEM_UPLOAD_DIR . '/' . $nomeArquivo;

    if (!move_uploaded_file((string) $arquivoFoto['tmp_name'], $destino)) {
        $_SESSION['alterar_item_erro'] = 'Nao foi possivel salvar a foto do produto.';
        header('Location: alterar-item.php?codigo=' . urlencode($codigoProduto));
        exit;
    }

    $fotoProdutoNova = 'uploads/produtos/' . $nomeArquivo;
}

try {
    $stmt = $pdo->prepare(
        'UPDATE itens
         SET nome_produto = :nome_produto,
             ncm = :ncm,
             foto_produto = :foto_produto,
             mostrar_catalogo = :mostrar_catalogo,
             preco_custo = :preco_custo,
             preco_venda = :preco_venda
         WHERE id = :id'
    );

    $stmt->execute([
        'id' => $id,
        'nome_produto' => $nomeProduto,
        'ncm' => $ncm,
        'foto_produto' => $fotoProdutoNova,
        'mostrar_catalogo' => $mostrarCatalogo,
        'preco_custo' => number_format((float) $precoCusto, 2, '.', ''),
        'preco_venda' => number_format((float) $precoVenda, 2, '.', ''),
    ]);
} catch (PDOException $e) {
    if ($fotoProdutoNova !== null && $fotoProdutoNova !== $fotoProdutoAtual) {
        $arquivoSalvo = __DIR__ . '/' . $fotoProdutoNova;
        if (is_file($arquivoSalvo)) {
            unlink($arquivoSalvo);
        }
    }

    $_SESSION['alterar_item_erro'] = 'Nao foi possivel atualizar o item.';
    header('Location: alterar-item.php?codigo=' . urlencode($codigoProduto));
    exit;
}

if (
    $fotoProdutoAtual !== null &&
    $fotoProdutoAtual !== '' &&
    $fotoProdutoNova !== $fotoProdutoAtual
) {
    $arquivoAntigo = __DIR__ . '/' . $fotoProdutoAtual;
    if (is_file($arquivoAntigo) && strpos(str_replace('\\', '/', $fotoProdutoAtual), 'uploads/produtos/') === 0) {
        unlink($arquivoAntigo);
    }
}

$_SESSION['alterar_item_sucesso'] = 'Item alterado com sucesso.';
header('Location: alterar-item.php?codigo=' . urlencode($codigoProduto));
exit;
