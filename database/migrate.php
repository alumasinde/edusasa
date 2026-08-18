<?php

declare(strict_types=1);

/**
 * EduSasa database migration runner.
 *
 * Usage:
 *   php database/migrate.php
 *   php database/migrate.php status
 *
 * The runner loads .env, connects to MySQL, discovers numbered SQL files in
 * database/migrations, and records successful migrations in the canonical
 * schema_migrations table created by 000_schema_migrations.sql.
 *
 * Migrations are intentionally NOT wrapped in a PDO transaction. MySQL DDL
 * statements such as CREATE TABLE and ALTER TABLE can implicitly commit, so
 * pretending each migration is transactional produces misleading rollback
 * errors. A migration is recorded only after its SQL completes successfully.
 */

$root = dirname(__DIR__);
$migrationsDir = $root . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'migrations';

if (!is_dir($migrationsDir)) {
    fwrite(STDERR, "Migration directory not found: {$migrationsDir}\n");
    exit(1);
}

loadEnv($root . DIRECTORY_SEPARATOR . '.env');

$host = (string) env('DB_HOST', '127.0.0.1');
$port = (string) env('DB_PORT', '3306');
$database = (string) env('DB_DATABASE', 'edusasa');
$username = (string) env('DB_USERNAME', 'root');
$password = (string) env('DB_PASSWORD', '');
$charset = (string) env('DB_CHARSET', 'utf8mb4');

try {
    $pdo = new PDO(
        sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', $host, $port, $database, $charset),
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (Throwable $e) {
    fwrite(STDERR, "Database connection failed: {$e->getMessage()}\n");
    exit(1);
}

$files = glob($migrationsDir . DIRECTORY_SEPARATOR . '*.sql') ?: [];
$files = array_values(array_filter($files, static function (string $file): bool {
    return preg_match('/^[0-9]+_[A-Za-z0-9._-]+\.sql$/', basename($file)) === 1;
}));
usort($files, static fn(string $a, string $b): int => strnatcasecmp(basename($a), basename($b)));

if ($files === []) {
    echo "No migration files found.\n";
    exit(0);
}

$command = $argv[1] ?? 'migrate';

if ($command !== 'migrate' && $command !== 'status') {
    fwrite(STDERR, "Unknown command: {$command}\nUsage: php database/migrate.php [status]\n");
    exit(1);
}

// 000_schema_migrations.sql creates this registry. On a fresh database it
// must be allowed to run before we can query the registry.
$registry = 'schema_migrations';
$registryExists = tableExists($pdo, $registry);

if (!$registryExists) {
    $firstFile = basename($files[0]);
    if ($firstFile !== '000_schema_migrations.sql') {
        fwrite(STDERR, "Canonical migration registry is missing and the first migration is {$firstFile}.\n");
        fwrite(STDERR, "Expected database/migrations/000_schema_migrations.sql to run first.\n");
        exit(1);
    }

    if ($command === 'status') {
        echo "Migration status\n-----------------\n";
        echo "[pending] 000_schema_migrations.sql (migration registry not created yet)\n";
        foreach (array_slice($files, 1) as $file) {
            echo sprintf("[pending] %s\n", basename($file));
        }
        exit(0);
    }

    echo "Migrating 000_schema_migrations.sql ... ";
    try {
        $sql = readMigration($files[0]);
        $pdo->exec($sql);
        recordMigration($pdo, '000_schema_migrations.sql', $sql);
        echo "OK\n";
    } catch (Throwable $e) {
        echo "FAILED\n";
        fwrite(STDERR, "Migration 000_schema_migrations.sql failed: {$e->getMessage()}\n");
        exit(1);
    }
}

if (!tableExists($pdo, $registry)) {
    fwrite(STDERR, "Migration registry table {$registry} does not exist after running 000_schema_migrations.sql.\n");
    exit(1);
}

$appliedRows = $pdo->query('SELECT migration, checksum, applied_at FROM schema_migrations ORDER BY id')->fetchAll();
$applied = [];
foreach ($appliedRows as $row) {
    $applied[$row['migration']] = $row;
}

if ($command === 'status') {
    echo "Migration status\n-----------------\n";
    foreach ($files as $file) {
        $name = basename($file);
        if (isset($applied[$name])) {
            $currentChecksum = hash_file('sha256', $file) ?: '';
            $checksumState = empty($applied[$name]['checksum'])
                ? 'checksum missing'
                : ($applied[$name]['checksum'] === $currentChecksum ? 'checksum ok' : 'CHECKSUM CHANGED');
            echo sprintf("[applied] %-60s %s  %s\n", $name, $applied[$name]['applied_at'], $checksumState);
        } else {
            echo sprintf("[pending] %s\n", $name);
        }
    }
    exit(0);
}

$pending = [];
foreach ($files as $file) {
    $name = basename($file);
    if (!isset($applied[$name])) {
        $pending[] = $file;
    }
}

if ($pending === []) {
    echo "Database is up to date.\n";
    exit(0);
}

$appliedCount = 0;
foreach ($pending as $file) {
    $name = basename($file);
    $sql = readMigration($file);

    echo "Migrating {$name} ... ";

    try {
        $pdo->exec($sql);

        // Only record the migration after all of its SQL has succeeded.
        // This is deliberately outside a transaction because MySQL DDL may
        // implicitly commit before/after the statement.
        recordMigration($pdo, $name, $sql);
        $appliedCount++;
        echo "OK\n";
    } catch (Throwable $e) {
        echo "FAILED\n";
        fwrite(STDERR, "Migration {$name} failed: {$e->getMessage()}\n");
        fwrite(STDERR, "Fix the migration/database state and rerun the command.\n");
        exit(1);
    }
}

echo sprintf("\nApplied %d migration(s).\n", $appliedCount);
exit(0);

function tableExists(PDO $pdo, string $table): bool
{
    $statement = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :table'
    );
    $statement->execute(['table' => $table]);
    return (int) $statement->fetchColumn() > 0;
}

function recordMigration(PDO $pdo, string $name, string $sql): void
{
    $statement = $pdo->prepare(
        'INSERT INTO schema_migrations (migration, checksum) VALUES (:migration, :checksum)'
    );
    $statement->execute([
        'migration' => $name,
        'checksum' => hash('sha256', $sql),
    ]);
}

function readMigration(string $file): string
{
    $sql = file_get_contents($file);
    if ($sql === false || trim($sql) === '') {
        throw new RuntimeException('Migration file is empty or unreadable.');
    }
    return $sql;
}

function loadEnv(string $path): void
{
    if (!is_file($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$name, $value] = explode('=', $line, 2);
        $name = trim($name);
        $value = trim(trim($value), "\"'");
        if ($name === '') {
            continue;
        }

        if (getenv($name) === false) {
            putenv($name . '=' . $value);
        }
        $_ENV[$name] = $_ENV[$name] ?? $value;
    }
}

function env(string $key, mixed $default = null): mixed
{
    $value = $_ENV[$key] ?? getenv($key);
    return ($value === false || $value === null || $value === '') ? $default : $value;
}
