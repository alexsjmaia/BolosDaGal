<?php
declare(strict_types=1);

/**
 * Importa clientes a partir de um CSV para a tabela `clientes`.
 *
 * Uso:
 *   php importar-clientes-csv.php
 *   php importar-clientes-csv.php "C:\caminho\arquivo.csv"
 */

date_default_timezone_set('America/Sao_Paulo');
require __DIR__ . '/db.php';

$csvPath = $argv[1] ?? 'C:\\Users\\AlexSoares\\Downloads\\contacts.csv';

if (!is_file($csvPath)) {
    fwrite(STDERR, "Arquivo CSV nao encontrado: {$csvPath}\n");
    exit(1);
}

$handle = fopen($csvPath, 'rb');
if ($handle === false) {
    fwrite(STDERR, "Nao foi possivel abrir o arquivo CSV.\n");
    exit(1);
}

$headerLine = fgets($handle);
if ($headerLine === false) {
    fclose($handle);
    fwrite(STDERR, "CSV vazio.\n");
    exit(1);
}

$delimiter = substr_count($headerLine, ';') >= substr_count($headerLine, ',') ? ';' : ',';
rewind($handle);

$headers = fgetcsv($handle, 0, $delimiter, '"', '\\');
if ($headers === false) {
    fclose($handle);
    fwrite(STDERR, "Nao foi possivel ler o cabecalho do CSV.\n");
    exit(1);
}

/**
 * Normaliza cabecalhos para comparacao resiliente:
 * - remove BOM UTF-8
 * - remove acentos quando possivel
 * - baixa caixa e remove caracteres fora de [a-z0-9]
 */
$normalizedHeaders = array_map(
    static function (?string $value): string {
        $header = trim((string) $value);
        $header = preg_replace('/^\xEF\xBB\xBF/', '', $header) ?? $header;

        if (function_exists('iconv')) {
            $converted = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $header);
            if ($converted !== false) {
                $header = $converted;
            }
        }

        $header = strtolower($header);
        $header = preg_replace('/[^a-z0-9]/', '', $header) ?? $header;

        return $header;
    },
    $headers
);

$indexNome = array_search('nome', $normalizedHeaders, true);
$indexNumero = array_search('numero', $normalizedHeaders, true);

if ($indexNome === false || $indexNumero === false) {
    fclose($handle);
    fwrite(STDERR, "Cabecalho invalido. Esperado: Nome e Numero.\n");
    exit(1);
}

$stmt = $pdo->prepare(
    'INSERT INTO clientes (nome, whatsapp, saldo_cashback)
     VALUES (:nome, :whatsapp, 0.00)
     ON DUPLICATE KEY UPDATE nome = VALUES(nome)'
);

$totalLidos = 0;
$totalInseridosOuAtualizados = 0;
$totalIgnorados = 0;
$erros = [];

while (($row = fgetcsv($handle, 0, $delimiter, '"', '\\')) !== false) {
    $totalLidos++;

    $nome = trim((string) ($row[$indexNome] ?? ''));
    $numeroRaw = trim((string) ($row[$indexNumero] ?? ''));
    $whatsapp = preg_replace('/\D+/', '', $numeroRaw) ?? '';

    if ($nome === '' && $whatsapp === '') {
        $totalIgnorados++;
        continue;
    }

    $nomeLength = function_exists('mb_strlen') ? mb_strlen($nome) : strlen($nome);

    if ($nomeLength < 5) {
        $totalIgnorados++;
        $erros[] = "Linha {$totalLidos}: nome invalido ({$nome}).";
        continue;
    }

    if (strlen($whatsapp) !== 11 || substr($whatsapp, 2, 1) !== '9') {
        $totalIgnorados++;
        $erros[] = "Linha {$totalLidos}: WhatsApp invalido ({$numeroRaw}).";
        continue;
    }

    try {
        $stmt->execute([
            'nome' => $nome,
            'whatsapp' => $whatsapp,
        ]);
        $totalInseridosOuAtualizados++;
    } catch (PDOException $e) {
        $totalIgnorados++;
        $erros[] = "Linha {$totalLidos}: erro ao salvar ({$nome} / {$whatsapp}).";
    }
}

fclose($handle);

echo "Importacao finalizada.\n";
echo "Arquivo: {$csvPath}\n";
echo "Registros lidos: {$totalLidos}\n";
echo "Inseridos/atualizados: {$totalInseridosOuAtualizados}\n";
echo "Ignorados: {$totalIgnorados}\n";

if ($erros) {
    echo "\nPrimeiros avisos:\n";
    foreach (array_slice($erros, 0, 20) as $erro) {
        echo "- {$erro}\n";
    }
}
