<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf_token(): string
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function require_login(): array
{
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }

    $statement = db()->prepare('SELECT name, workspace_name, currency FROM users WHERE id = ?');
    $statement->execute([(int) $_SESSION['user_id']]);
    $account = $statement->fetch();
    if (!$account) {
        $_SESSION = [];
        session_regenerate_id(true);
        header('Location: login.php');
        exit;
    }

    $_SESSION['user_name'] = $account['name'];
    $_SESSION['workspace_name'] = $account['workspace_name'];
    $_SESSION['currency'] = $account['currency'];

    return [
        'id' => (int) $_SESSION['user_id'],
        'name' => (string) $account['name'],
        'workspace' => (string) $account['workspace_name'],
        'currency' => (string) $account['currency'],
    ];
}

function verify_csrf(?string $token): bool
{
    return is_string($token)
        && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}
