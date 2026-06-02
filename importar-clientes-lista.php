<?php
declare(strict_types=1);

/**
 * Importa uma lista fixa de clientes no formato:
 * Nome|WhatsApp
 *
 * Regras:
 * - nao duplica cliente por WhatsApp
 * - normaliza telefones:
 *   - remove tudo que nao for numero
 *   - se vier com 55 + DDD + numero (13 digitos), remove o 55
 *   - se vier com 0 + DDD + numero (12 digitos), remove o 0 inicial
 * - valida WhatsApp final com 11 digitos e terceiro digito igual a 9
 *
 * Uso:
 *   php importar-clientes-lista.php
 */

date_default_timezone_set('America/Sao_Paulo');
require __DIR__ . '/db.php';

$lista = <<<'TXT'
Cleide|11998097358
Adriana Cozinha Lancho|11965717968
Adriana do Ferro|11948730243
Adriana Lancho|11947843124
Alene Silvana|11949989237
Alessandra Infantil|11982708661
Alex|11953239983
Alex Reis|11959673955
Ana Almeida|11980602882
Ana Paula|11967314153
Ana Paula Medeiros|11958809625
Anashetz|11982128644
Beth Restaurante|11971784394
Betinha|11943998266
Carol Segurança|11932220772
Cibelly Textil|011951461846
Cintia Nunes|11996701029
Clarice|11957665955
Claudia|011995304377
Daniela Alves|11958627570
Denis|964574062
Edineuza Feminino|011983256100
Edson Loja|11956850125
Edson Têxtil Estoque|11971892607
Eliana|11972858183
Elaine Alves|11952528454
Elaine Caixas|953608810
Eliane Ferman|11954433127
Eveline sousa|11940888731
Flavia Caixas|11957462010
Flavio Segurança|11984616272
Francinete|11952978850
Francinete|952978850
Geise|11969904167
Gilmaria|11958181002
Gisely Feminino|11968996673
Gledson|011974129814
Gleyciane|11967719427
Glidoelson|954481474
Iramaya|11989525911
Irene Compras|11995407469
Irmã da Cris(infantil)|11974581689
Ivonete|953674212
Jéssyka Santos Caixas|11966729338
karool marques|11920938043
Kathrim Infantil|11949948705
Kelly Recepção|11945984102
Lorimar|11948520598
Luana Perfu|11972188192
Luciene Caixas|11992535011
Lucimara|11949991107
Luiz Benone|11976001762
Magda|11964284625
Marcia Cadastro|11946795935
Marcia Nagai|11984386802
Marciana|11981001174
Marco Antônio|11940000383
Maria Almeida Bebe|11974481366
Maria Maciel Cadastro|11957584869
Maria Masculino|11979759965
Mariana Bebe|11953302982
Marileide|11960483422
Mery Souza|11962204285
Micaeli Lingerie|11991594698
Monica|968891741
Nega|11974625336
Neide Têxtil Abril|11985053988
Neta|11971789612
Niedja|11940242556
Paula Arquildes|11964179011
Priscila|11994178972
Regina Compras|11948056897
Renatinha|11963639203
Rosangela Recepção|11993512799
Rose Feminino|11949050164
Sandra Limpeza|11987854740
Sandra UD|11964479616
Sergio Santos|11975035201
Sil Amorim Lingerie|11964484841
Silvana Caixas|11958570664
Simara Lancho|11988836084
Simone Textil Abril|011986776794
Suzy|11994239205
Taninha|11958399570
Tati Setor Feminino|11995531460
Vandi Lingerie|11981857570
Veronica|11986352552
Viviane Textil|11965379185
Wellington Visual|11940857124
TXT;

function normalizarWhatsapp(string $valor): string
{
    $digitos = preg_replace('/\D+/', '', $valor) ?? '';

    if (strlen($digitos) === 13 && strpos($digitos, '55') === 0) {
        $digitos = substr($digitos, 2);
    }

    if (strlen($digitos) === 12 && strpos($digitos, '0') === 0) {
        $digitos = substr($digitos, 1);
    }

    return $digitos;
}

function whatsappValido(string $whatsapp): bool
{
    return strlen($whatsapp) === 11 && substr($whatsapp, 2, 1) === '9';
}

$linhas = preg_split('/\R/u', trim($lista)) ?: [];

$stmtExiste = $pdo->prepare(
    'SELECT 1 FROM clientes WHERE whatsapp = :whatsapp LIMIT 1'
);
$stmtInsert = $pdo->prepare(
    'INSERT INTO clientes (nome, whatsapp, saldo_cashback)
     VALUES (:nome, :whatsapp, 0.00)'
);

$totalLidos = 0;
$totalInseridos = 0;
$totalIgnorados = 0;
$avisos = [];
$whatsVistosNaLista = [];

foreach ($linhas as $indice => $linha) {
    $totalLidos++;
    $numeroLinha = $indice + 1;
    $partes = explode('|', $linha, 2);

    if (count($partes) < 2) {
        $totalIgnorados++;
        $avisos[] = "Linha {$numeroLinha}: formato invalido.";
        continue;
    }

    $nome = trim($partes[0]);
    $whatsappOriginal = trim($partes[1]);
    $whatsapp = normalizarWhatsapp($whatsappOriginal);

    if ($nome === '' || $whatsapp === '') {
        $totalIgnorados++;
        $avisos[] = "Linha {$numeroLinha}: nome ou WhatsApp vazio.";
        continue;
    }

    if (!whatsappValido($whatsapp)) {
        $totalIgnorados++;
        $avisos[] = "Linha {$numeroLinha}: WhatsApp invalido ({$whatsappOriginal}).";
        continue;
    }

    if (isset($whatsVistosNaLista[$whatsapp])) {
        $totalIgnorados++;
        $avisos[] = "Linha {$numeroLinha}: WhatsApp duplicado na lista ({$whatsapp}).";
        continue;
    }
    $whatsVistosNaLista[$whatsapp] = true;

    $stmtExiste->execute(['whatsapp' => $whatsapp]);
    if ((bool) $stmtExiste->fetchColumn()) {
        $totalIgnorados++;
        $avisos[] = "Linha {$numeroLinha}: WhatsApp ja cadastrado ({$whatsapp}).";
        continue;
    }

    try {
        $stmtInsert->execute([
            'nome' => $nome,
            'whatsapp' => $whatsapp,
        ]);
        $totalInseridos++;
    } catch (PDOException $e) {
        $totalIgnorados++;
        $avisos[] = "Linha {$numeroLinha}: erro ao inserir ({$nome} | {$whatsapp}).";
    }
}

echo "Importacao finalizada.\n";
echo "Registros lidos: {$totalLidos}\n";
echo "Inseridos: {$totalInseridos}\n";
echo "Ignorados: {$totalIgnorados}\n";

if ($avisos) {
    echo "\nPrimeiros avisos:\n";
    foreach (array_slice($avisos, 0, 30) as $aviso) {
        echo "- {$aviso}\n";
    }
}
