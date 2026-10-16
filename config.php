<?php
declare(strict_types=1);

define('DB_HOST', getenv('JENGA_DB_HOST') ?: '127.0.0.1');
define('DB_NAME', getenv('JENGA_DB_NAME') ?: 'jengaestimate');
define('DB_USER', getenv('JENGA_DB_USER') ?: 'root');
define('DB_PASS', getenv('JENGA_DB_PASS') ?: '');
define('BASE_URL', rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/'));

$isSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
session_set_cookie_params([
    'httponly' => true,
    'secure' => $isSecure,
    'samesite' => 'Lax',
]);
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function db(): PDO
{
    static $pdo;
    if (!$pdo instanceof PDO) {
        $databaseUrl = getenv('DATABASE_URL');
        if ($databaseUrl !== false && $databaseUrl !== '') {
            $connection = parse_url($databaseUrl);
            if (
                $connection === false
                || !in_array($connection['scheme'] ?? '', ['postgres', 'postgresql'], true)
                || empty($connection['host'])
                || empty($connection['path'])
            ) {
                throw new RuntimeException('DATABASE_URL must be a valid PostgreSQL connection URL.');
            }
            $query = [];
            parse_str($connection['query'] ?? '', $query);
            $dsn = 'pgsql:host=' . $connection['host']
                . ';port=' . ($connection['port'] ?? 5432)
                . ';dbname=' . rawurldecode(ltrim($connection['path'], '/'))
                . ';sslmode=' . ($query['sslmode'] ?? 'require');
            $username = rawurldecode($connection['user'] ?? '');
            $password = rawurldecode($connection['pass'] ?? '');
        } else {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
            $username = DB_USER;
            $password = DB_PASS;
        }
        $pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
    return $pdo;
}

function inserted_id(PDO $pdo, string $table): int
{
    $sequence = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql'
        ? $table . '_id_seq'
        : null;
    return (int) ($sequence === null ? $pdo->lastInsertId() : $pdo->lastInsertId($sequence));
}
