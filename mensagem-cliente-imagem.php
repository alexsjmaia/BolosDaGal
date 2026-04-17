<?php
require __DIR__ . '/auth.php';

function svgEscape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
}

function wrapText(string $text, int $maxCharsPerLine): array
{
    $words = preg_split('/\s+/', trim($text)) ?: [];
    $lines = [];
    $line = '';

    foreach ($words as $word) {
        $candidate = $line === '' ? $word : $line . ' ' . $word;
        if (strlen($candidate) <= $maxCharsPerLine) {
            $line = $candidate;
            continue;
        }

        if ($line !== '') {
            $lines[] = $line;
        }
        $line = $word;
    }

    if ($line !== '') {
        $lines[] = $line;
    }

    return $lines;
}

$nome = trim((string) ($_GET['nome'] ?? 'Cliente'));
$saldo = (float) ($_GET['saldo'] ?? 0);
$saldoFormatado = 'R$ ' . number_format($saldo, 2, ',', '.');

$mensagem = "Ei, temos uma otima noticia! Voce tem {$saldoFormatado} em cashback esperando por voce em nossa Loja. Que tal aproveitar para garantir aquele bolo delicioso que voce esta de olho?";
$linhas = wrapText($mensagem, 44);

$logoPath = __DIR__ . '/logo-bolos-da-gal.png';
$logoBase64 = '';
if (is_file($logoPath)) {
    $logoContent = file_get_contents($logoPath);
    if ($logoContent !== false) {
        $logoBase64 = base64_encode($logoContent);
    }
}

header('Content-Type: image/svg+xml; charset=UTF-8');

$width = 1080;
$height = 1080;
$lineStartY = 430;
$lineHeight = 48;

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<svg xmlns="http://www.w3.org/2000/svg" width="<?= $width ?>" height="<?= $height ?>" viewBox="0 0 <?= $width ?> <?= $height ?>">
    <defs>
        <linearGradient id="bg" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0%" stop-color="#fffafb" />
            <stop offset="100%" stop-color="#f8eef1" />
        </linearGradient>
    </defs>

    <rect width="100%" height="100%" fill="url(#bg)" />
    <circle cx="140" cy="140" r="190" fill="#fbe1e7" opacity="0.6" />
    <circle cx="950" cy="980" r="210" fill="#fbe1e7" opacity="0.55" />

    <?php if ($logoBase64 !== ''): ?>
        <image x="240" y="190" width="600" height="600"
               href="data:image/png;base64,<?= $logoBase64 ?>"
               opacity="0.09" preserveAspectRatio="xMidYMid meet"/>
    <?php endif; ?>

    <text x="540" y="130" text-anchor="middle" font-size="56" fill="#7d635c" font-family="Georgia, serif" font-weight="700">
        Bolos da Gal
    </text>
    <text x="540" y="190" text-anchor="middle" font-size="34" fill="#8a7670" font-family="Georgia, serif">
        Mensagem de Cashback
    </text>

    <rect x="95" y="265" width="890" height="640" rx="28" fill="#ffffff" opacity="0.92" stroke="#f1cfd6" stroke-width="2" />

    <text x="130" y="340" font-size="34" fill="#7d635c" font-family="Georgia, serif" font-weight="700">
        <?= svgEscape($nome) ?>
    </text>

    <?php foreach ($linhas as $index => $linha): ?>
        <text x="130" y="<?= $lineStartY + ($index * $lineHeight) ?>" font-size="36" fill="#4d3e39" font-family="Georgia, serif">
            <?= svgEscape($linha) ?>
        </text>
    <?php endforeach; ?>

    <text x="540" y="975" text-anchor="middle" font-size="28" fill="#8a7670" font-family="Georgia, serif">
        www.bolosdagal.com.br
    </text>
</svg>
