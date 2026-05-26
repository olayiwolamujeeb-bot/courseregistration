<?php

$defaultSqlitePath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'database.sqlite';
$dbConnection = strtolower((string) ($_ENV['DB_CONNECTION'] ?? getenv('DB_CONNECTION') ?: 'sqlite'));

if ($dbConnection === 'sqlite') {
    $sqlitePath = (string) ($_ENV['DB_DATABASE'] ?? getenv('DB_DATABASE') ?: $defaultSqlitePath);
    $sqliteDirectory = dirname($sqlitePath);

    if (!is_dir($sqliteDirectory)) {
        mkdir($sqliteDirectory, 0777, true);
    }

    if (!file_exists($sqlitePath)) {
        touch($sqlitePath);
    }

    $pdo = new PDO('sqlite:' . $sqlitePath, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    $pdo->exec('PRAGMA foreign_keys = ON');
} else {
    $host = (string) ($_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: '127.0.0.1');
    $db = (string) ($_ENV['DB_DATABASE'] ?? getenv('DB_DATABASE') ?: 'course_registration');
    $user = (string) ($_ENV['DB_USERNAME'] ?? getenv('DB_USERNAME') ?: 'root');
    $pass = (string) ($_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?: '');
    $charset = (string) ($_ENV['DB_CHARSET'] ?? getenv('DB_CHARSET') ?: 'utf8mb4');
    $port = (string) ($_ENV['DB_PORT'] ?? getenv('DB_PORT') ?: '3306');

    $dsn = "mysql:host={$host};port={$port};dbname={$db};charset={$charset}";

    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}

$driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
$idColumn = $driver === 'sqlite' ? 'INTEGER PRIMARY KEY AUTOINCREMENT' : 'BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY';
$timestampDefault = $driver === 'sqlite' ? 'DEFAULT CURRENT_TIMESTAMP' : 'DEFAULT CURRENT_TIMESTAMP';
$timestampUpdate = $driver === 'sqlite' ? '' : ' ON UPDATE CURRENT_TIMESTAMP';

$pdo->exec("
    CREATE TABLE IF NOT EXISTS courses (
        id {$idColumn},
        title TEXT NOT NULL,
        code TEXT NOT NULL UNIQUE,
        level TEXT NOT NULL,
        semester TEXT NOT NULL,
        unit INTEGER NOT NULL,
        type TEXT NOT NULL,
        created_at DATETIME {$timestampDefault},
        updated_at DATETIME {$timestampDefault}{$timestampUpdate}
    )
");

$pdo->exec("
    CREATE TABLE IF NOT EXISTS students (
        id {$idColumn},
        name TEXT NOT NULL,
        level TEXT NOT NULL,
        email TEXT NULL,
        matric TEXT NULL,
        created_at DATETIME {$timestampDefault},
        updated_at DATETIME {$timestampDefault}{$timestampUpdate}
    )
");

$pdo->exec("
    CREATE TABLE IF NOT EXISTS registrations (
        id {$idColumn},
        student_id INTEGER NOT NULL,
        course_ids TEXT NOT NULL,
        created_at DATETIME {$timestampDefault},
        updated_at DATETIME {$timestampDefault}{$timestampUpdate},
        FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
    )
");

return $pdo;
