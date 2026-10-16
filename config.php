<?php
declare(strict_types=1);

date_default_timezone_set('Africa/Nairobi');

if (session_status() !== PHP_SESSION_ACTIVE) {
    $https = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_set_cookie_params([
        'httponly' => true,
        'secure' => $https,
        'samesite' => 'Lax',
        'path' => '/',
    ]);
    session_start();
}

function db(): PDO
{
    static $connection = null;
    if ($connection instanceof PDO) {
        return $connection;
    }

    $host = getenv('FLEETCARE_DB_HOST') ?: '127.0.0.1';
    $port = getenv('FLEETCARE_DB_PORT') ?: '3306';
    $name = getenv('FLEETCARE_DB_NAME') ?: 'fleetcare_ea';
    $user = getenv('FLEETCARE_DB_USER') ?: 'root';
    $password = getenv('FLEETCARE_DB_PASSWORD') ?: '';

    try {
        $connection = new PDO(
            "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4",
            $user,
            $password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
        $connection->exec("SET time_zone = '+03:00'");
    } catch (PDOException $exception) {
        error_log('FleetCare database connection failed: ' . $exception->getMessage());
        http_response_code(500);
        exit('Could not connect to the FleetCare database. Check the database configuration.');
    }

    return $connection;
}
