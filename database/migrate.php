<?php

declare(strict_types=1);

/**
 * EduSasa database migration runner.
 *
 * Usage:
 *   php database/migrate.php
 *   php database/migrate.php status
 *
 * The runner loads the project's .env, connects to MySQL using PDO,
 * discovers numbered SQL files in database/migrations, and records each
 * successful migration in the migrations table. Failed migrations stop the
 * process immediately and are not recorded as applied.
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

$pdo->exec(
    'CREATE TABLE IF NOT EXISTS migrations (' .
    'id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,' .
    'migration VARCHAR(190) NOT NULL UNIQUE,' .
    'batch INT UNSIGNED NOT NULL,' .
    'executed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP' .
    ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
);

$files = glob($migrationsDir . DIRECTORY_SEPARATOR . '*.sql') ?: [];
$files = array_values(array_filter($files, static function (string $file): bool {
    return preg_match('/^[0-9]+_[A-Za-z0-9._-]+\.sql$/', basename($file)) === 1;
}));
usort($files, static fn(string $a, string $b): int => strnatcasecmp(basename($a), basename($b)));

$appliedRows = $pdo->query('SELECT migration, batch, executed_at FROM migrations ORDER BY id')->fetchAll();
$applied = [];
foreach ($appliedRows as $row) {
    $applied[$row['migration']] = $row;
}

$command = $argv[1] ?? 'migrate';

if ($command === 'status') {
    if ($files === []) {
        echo "No migration files found.\n";
        exit(0);
    }

    echo "Migration status\n-----------------\n";
    foreach ($files as $file) {
        $name = basename($file);
        if (isset($applied[$name])) {
            echo sprintf("[applied] %-60s batch %s  %s\n", $name, $applied[$name]['batch'], $applied[$name]['executed_at']);
        } else {
            echo sprintf("[pending] %s\n", $name);
        }
    }
    exit(0);
}

if ($command !== 'migrate') {
    fwrite(STDERR, "Unknown command: {$command}\nUsage: php database/migrate.php [status]\n");
    exit(1);
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

$lastBatch = (int) ($pdo->query('SELECT COALESCE(MAX(batch), 0) AS batch FROM migrations')->fetch()['batch'] ?? 0);
$batch = $lastBatch + 1;

foreach ($pending as $file) {
    $name = basename($file);
    $sql = trim((string) file_get_contents($file));

    if ($sql === '') {
        fwrite(STDERR, "Migration {$name} is empty. Aborting.\n");
        exit(1);
    }

    echo "Migrating {$name} ... ";

    try {
        // Each migration is one transaction. This works with the project's
        // InnoDB schema and prevents a failed migration from being recorded.
        $pdo->beginTransaction();
        $pdo->exec($sql);
        $statement = $pdo->prepare('INSERT INTO migrations (migration, batch) VALUES (:migration, :batch)');
        $statement->execute(['migration' => $name, 'batch' => $batch]);
        $pdo->commit();
        echo "OK\n";
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo "FAILED\n";
        fwrite(STDERR, "Migration {$name} failed: {$e->getMessage()}\n");
        exit(1);
    }
}

echo sprintf("\nApplied %d migration(s) in batch %d.\n", count($pending), $batch);
exit(0);

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
