<?php
require __DIR__ . '/auth.php';
require __DIR__ . '/db.php';

const BACKUP_DIR = __DIR__ . '/backup';

$mensagem = $_SESSION['backup_mensagem'] ?? '';
$erro = $_SESSION['backup_erro'] ?? '';
unset($_SESSION['backup_mensagem'], $_SESSION['backup_erro']);

if (!is_dir(BACKUP_DIR)) {
    mkdir(BACKUP_DIR, 0777, true);
}

function backup_redirect(): void
{
    header('Location: backup.php');
    exit;
}

function backup_user_can_restore(): bool
{
    return currentUserIsRoot();
}

function backup_add_files(ZipArchive $zip, string $sourceDir, string $backupFile): void
{
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($sourceDir, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
        $filePath = $file->getPathname();
        $relativePath = substr($filePath, strlen($sourceDir) + 1);

        if (strpos($relativePath, 'backup' . DIRECTORY_SEPARATOR) === 0) {
            continue;
        }

        if ($filePath === $backupFile || $relativePath === 'database-backup.json') {
            continue;
        }

        $zip->addFile($filePath, str_replace('\\', '/', $relativePath));
    }
}

function backup_get_all_tables(PDO $pdo): array
{
    $tables = [];
    $stmt = $pdo->query('SHOW TABLES');

    foreach ($stmt->fetchAll(PDO::FETCH_NUM) as $row) {
        $table = (string) ($row[0] ?? '');
        if ($table !== '') {
            $tables[] = $table;
        }
    }

    return $tables;
}

function backup_order_tables_for_restore(PDO $pdo, string $schema, array $tables): array
{
    if (!$tables) {
        return [];
    }

    $uniqueTables = array_values(array_unique(array_map('strval', $tables)));
    $inDegree = [];
    $children = [];

    foreach ($uniqueTables as $table) {
        $inDegree[$table] = 0;
        $children[$table] = [];
    }

    $placeholders = implode(',', array_fill(0, count($uniqueTables), '?'));
    $params = array_merge([$schema, $schema], $uniqueTables, $uniqueTables);
    $sql = 'SELECT TABLE_NAME, REFERENCED_TABLE_NAME
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = ?
              AND REFERENCED_TABLE_SCHEMA = ?
              AND REFERENCED_TABLE_NAME IS NOT NULL
              AND TABLE_NAME IN (' . $placeholders . ')
              AND REFERENCED_TABLE_NAME IN (' . $placeholders . ')';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    foreach ($stmt->fetchAll() as $row) {
        $child = (string) ($row['TABLE_NAME'] ?? '');
        $parent = (string) ($row['REFERENCED_TABLE_NAME'] ?? '');

        if ($child === '' || $parent === '' || $child === $parent) {
            continue;
        }

        $alreadyLinked = in_array($child, $children[$parent], true);
        if (!$alreadyLinked) {
            $children[$parent][] = $child;
            $inDegree[$child]++;
        }
    }

    $queue = [];
    foreach ($inDegree as $table => $degree) {
        if ($degree === 0) {
            $queue[] = $table;
        }
    }

    sort($queue);
    $ordered = [];

    while ($queue) {
        $current = array_shift($queue);
        $ordered[] = $current;

        foreach ($children[$current] as $child) {
            $inDegree[$child]--;

            if ($inDegree[$child] === 0) {
                $queue[] = $child;
            }
        }

        sort($queue);
    }

    if (count($ordered) !== count($uniqueTables)) {
        foreach ($uniqueTables as $table) {
            if (!in_array($table, $ordered, true)) {
                $ordered[] = $table;
            }
        }
    }

    return $ordered;
}

function backup_generate_database_dump(PDO $pdo, string $schema): array
{
    $tables = backup_get_all_tables($pdo);
    $createOrder = backup_order_tables_for_restore($pdo, $schema, $tables);

    $dump = [
        'generated_at' => date('c'),
        'schema' => $schema,
        'table_order' => $tables,
        'create_order' => $createOrder,
        'tables' => [],
    ];

    foreach ($tables as $table) {
        $createStmt = $pdo->query('SHOW CREATE TABLE `' . $table . '`')->fetch();
        $rows = $pdo->query('SELECT * FROM `' . $table . '`')->fetchAll(PDO::FETCH_ASSOC);

        $dump['tables'][$table] = [
            'create_statement' => $createStmt['Create Table'] ?? '',
            'rows' => $rows,
        ];
    }

    return $dump;
}

function backup_restore_database(PDO $pdo, array $dump): void
{
    $tablesDump = is_array($dump['tables'] ?? null) ? array_keys($dump['tables']) : [];

    if (!$tablesDump) {
        throw new RuntimeException('Nenhuma tabela encontrada no dump do banco.');
    }

    $createOrder = [];
    $createOrderDump = $dump['create_order'] ?? [];

    if (is_array($createOrderDump)) {
        foreach ($createOrderDump as $table) {
            $table = (string) $table;
            if ($table !== '' && in_array($table, $tablesDump, true) && !in_array($table, $createOrder, true)) {
                $createOrder[] = $table;
            }
        }
    }

    foreach ($tablesDump as $table) {
        if (!in_array($table, $createOrder, true)) {
            $createOrder[] = $table;
        }
    }

    $dropOrder = array_reverse($createOrder);

    $pdo->exec('SET FOREIGN_KEY_CHECKS=0');

    foreach ($dropOrder as $table) {
        if (isset($dump['tables'][$table])) {
            $pdo->exec('DROP TABLE IF EXISTS `' . $table . '`');
        }
    }

    foreach ($createOrder as $table) {
        if (!isset($dump['tables'][$table])) {
            continue;
        }

        $createStatement = $dump['tables'][$table]['create_statement'] ?? '';

        if ($createStatement !== '') {
            $pdo->exec($createStatement);
        }

        $rows = $dump['tables'][$table]['rows'] ?? [];

        if (!$rows) {
            continue;
        }

        $columns = array_keys($rows[0]);
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));
        $columnSql = '`' . implode('`, `', $columns) . '`';
        $stmt = $pdo->prepare('INSERT INTO `' . $table . '` (' . $columnSql . ') VALUES (' . $placeholders . ')');

        foreach ($rows as $row) {
            $stmt->execute(array_values($row));
        }
    }

    $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
}

function backup_delete_directory(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($iterator as $item) {
        if ($item->isDir()) {
            rmdir($item->getPathname());
            continue;
        }

        unlink($item->getPathname());
    }

    rmdir($dir);
}

function backup_copy_restored_files(string $tempDir, string $projectDir): void
{
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($tempDir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $item) {
        $sourcePath = $item->getPathname();
        $relativePath = substr($sourcePath, strlen($tempDir) + 1);

        if ($relativePath === 'database-backup.json') {
            continue;
        }

        if (strpos($relativePath, 'backup' . DIRECTORY_SEPARATOR) === 0) {
            continue;
        }

        $targetPath = $projectDir . DIRECTORY_SEPARATOR . $relativePath;

        if ($item->isDir()) {
            if (!is_dir($targetPath)) {
                mkdir($targetPath, 0777, true);
            }
            continue;
        }

        if (!is_dir(dirname($targetPath))) {
            mkdir(dirname($targetPath), 0777, true);
        }

        copy($sourcePath, $targetPath);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    $schema = (string) ($config['db_name'] ?? '');

    if ($acao === 'criar_backup') {
        if (!class_exists('ZipArchive')) {
            $_SESSION['backup_erro'] = 'A extensao ZipArchive do PHP nao esta habilitada.';
            backup_redirect();
        }

        $backupName = 'backup-sistema-' . date('Ymd-His') . '.zip';
        $backupFile = BACKUP_DIR . DIRECTORY_SEPARATOR . $backupName;
        $zip = new ZipArchive();

        if ($zip->open($backupFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            $_SESSION['backup_erro'] = 'Nao foi possivel criar o arquivo de backup.';
            backup_redirect();
        }

        try {
            $dump = backup_generate_database_dump($pdo, $schema);
            $zip->addFromString('database-backup.json', json_encode($dump, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $zip->addFromString('restore-instrucoes.txt', "Backup completo do sistema Bolos da Gal.\r\nRestaure pela tela backup.php.\r\n");
            backup_add_files($zip, __DIR__, $backupFile);
            $zip->close();
        } catch (Throwable $e) {
            $zip->close();
            if (file_exists($backupFile)) {
                unlink($backupFile);
            }
            $_SESSION['backup_erro'] = 'Nao foi possivel gerar o backup completo.';
            backup_redirect();
        }

        $_SESSION['backup_mensagem'] = 'Backup criado com sucesso em ' . BACKUP_DIR . '\\' . $backupName;
        backup_redirect();
    }

    if ($acao === 'restaurar_backup') {
        if (!backup_user_can_restore()) {
            $_SESSION['backup_erro'] = 'A restauracao de backup esta disponivel apenas para o usuario root.';
            backup_redirect();
        }

        $arquivo = basename($_POST['arquivo'] ?? '');
        $backupFile = BACKUP_DIR . DIRECTORY_SEPARATOR . $arquivo;

        if ($arquivo === '' || !is_file($backupFile)) {
            $_SESSION['backup_erro'] = 'Selecione um arquivo de backup valido para restaurar.';
            backup_redirect();
        }

        if (!class_exists('ZipArchive')) {
            $_SESSION['backup_erro'] = 'A extensao ZipArchive do PHP nao esta habilitada.';
            backup_redirect();
        }

        $tempDir = BACKUP_DIR . DIRECTORY_SEPARATOR . 'restore-' . uniqid();
        mkdir($tempDir, 0777, true);

        $zip = new ZipArchive();

        if ($zip->open($backupFile) !== true) {
            $_SESSION['backup_erro'] = 'Nao foi possivel abrir o backup selecionado.';
            backup_delete_directory($tempDir);
            backup_redirect();
        }

        try {
            $zip->extractTo($tempDir);
            $zip->close();

            $dumpFile = $tempDir . DIRECTORY_SEPARATOR . 'database-backup.json';

            if (!is_file($dumpFile)) {
                throw new RuntimeException('O backup selecionado nao contem o banco de dados.');
            }

            $dump = json_decode((string) file_get_contents($dumpFile), true);

            if (!is_array($dump) || !isset($dump['tables'])) {
                throw new RuntimeException('O arquivo de banco do backup esta invalido.');
            }

            backup_restore_database($pdo, $dump);
            backup_copy_restored_files($tempDir, __DIR__);
        } catch (Throwable $e) {
            $_SESSION['backup_erro'] = 'Nao foi possivel restaurar o backup. Detalhe: ' . $e->getMessage();
            backup_delete_directory($tempDir);
            backup_redirect();
        }

        backup_delete_directory($tempDir);
        $_SESSION['backup_mensagem'] = 'Backup restaurado com sucesso a partir de ' . $arquivo;
        backup_redirect();
    }
}

$backups = glob(BACKUP_DIR . DIRECTORY_SEPARATOR . '*.zip') ?: [];
rsort($backups);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backup - Bolos da Gal</title>
    <style>
        :root {
            --primary: #d99aa5;
            --primary-dark: #c77d89;
            --secondary: #7d635c;
            --text: #4d3e39;
            --muted: #8a7670;
            --border: #f1cfd6;
            --shadow: rgba(125, 99, 92, 0.18);
            --error-bg: #fff0f1;
            --error-text: #9f2d20;
            --success-bg: #eef9f1;
            --success-text: #2b6f44;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            padding: 24px;
            font-family: Georgia, "Times New Roman", serif;
            background:
                radial-gradient(circle at top, #fbe1e7 0%, transparent 34%),
                linear-gradient(135deg, #fffafb 0%, #f8eef1 100%);
            color: var(--text);
        }

        .layout {
            width: min(980px, 100%);
            margin: 0 auto;
            display: grid;
            gap: 24px;
        }

        .card {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 28px;
            padding: 30px;
            box-shadow: 0 24px 60px var(--shadow);
        }

        h1, h2 {
            margin-top: 0;
            color: var(--secondary);
        }

        .lead {
            color: var(--muted);
        }

        .alert {
            margin-top: 18px;
            padding: 12px 14px;
            border-radius: 12px;
        }

        .alert.error {
            background: var(--error-bg);
            color: var(--error-text);
        }

        .alert.success {
            background: var(--success-bg);
            color: var(--success-text);
        }

        .actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 24px;
        }

        .button {
            padding: 14px 18px;
            border: 0;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: #fff;
            text-decoration: none;
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
        }

        .button.secondary {
            background: #fff;
            color: var(--secondary);
            border: 1px solid var(--border);
        }

        .button.danger {
            background: #b84f5b;
        }

        ul {
            padding-left: 18px;
        }

        .backup-item {
            display: grid;
            gap: 12px;
            padding: 14px 0;
            border-bottom: 1px solid var(--border);
        }

        @media (min-width: 800px) {
            .backup-item {
                grid-template-columns: 1fr auto;
                align-items: center;
            }
        }
    </style>
</head>
<body>
    <main class="layout">
        <section class="card">
            <h1>Backup do Sistema</h1>
            <p class="lead">Aqui voce pode criar e restaurar backups completos do sistema. Os arquivos ficam em <strong>C:\BolosDaGal\backup</strong>.</p>

            <form method="post">
                <input type="hidden" name="acao" value="criar_backup">
                <div class="actions">
                    <button class="button" type="submit">Criar backup completo</button>
                    <a class="button secondary" href="dashboard.php">Voltar ao menu</a>
                </div>
            </form>

            <?php if ($mensagem !== ''): ?>
                <div class="alert success"><?= htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <?php if ($erro !== ''): ?>
                <div class="alert error"><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
        </section>

        <section class="card">
            <h2>Backups disponiveis</h2>

            <?php if ($backups): ?>
                <?php foreach ($backups as $arquivo): ?>
                    <?php $nome = basename($arquivo); ?>
                    <div class="backup-item">
                        <div>
                            <strong><?= htmlspecialchars($nome, ENT_QUOTES, 'UTF-8') ?></strong><br>
                            <span class="lead">Criado em <?= htmlspecialchars(date('d/m/Y H:i:s', filemtime($arquivo)), ENT_QUOTES, 'UTF-8') ?></span>
                        </div>

                        <?php if (backup_user_can_restore()): ?>
                            <form method="post" onsubmit="return confirm('Deseja realmente restaurar este backup? Isso vai sobrescrever os arquivos e o banco atual.');">
                                <input type="hidden" name="acao" value="restaurar_backup">
                                <input type="hidden" name="arquivo" value="<?= htmlspecialchars($nome, ENT_QUOTES, 'UTF-8') ?>">
                                <button class="button danger" type="submit">Restaurar backup</button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="lead">Nenhum backup foi criado ainda.</p>
            <?php endif; ?>

            <?php if (!backup_user_can_restore()): ?>
                <p class="lead">A restauracao de backup fica visivel apenas quando o sistema esta logado com o usuario root.</p>
            <?php endif; ?>
        </section>
    </main>
</body>
<?php renderIdleLogoutScript(); ?>
</html>
