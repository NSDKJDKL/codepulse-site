<?php

declare(strict_types=1);

session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
    'cookie_secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'use_only_cookies' => true,
    'use_strict_mode' => true,
]);

function database(): mysqli
{
    static $connection;

    if ($connection === null) {
        if (!extension_loaded('mysqli')) {
            throw new RuntimeException('The mysqli extension is required.');
        }

        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $connection = new mysqli(
            getenv('CODEPULSE_DB_HOST') ?: 'localhost',
            getenv('CODEPULSE_DB_USER') ?: 'root',
            (string) getenv('CODEPULSE_DB_PASSWORD'),
            getenv('CODEPULSE_DB_NAME') ?: 'codepulse_db',
            (int) (getenv('CODEPULSE_DB_PORT') ?: 3306)
        );
        $connection->set_charset('utf8mb4');
    }

    return $connection;
}

function query(string $sql, string $types, array $parameters): mysqli_stmt
{
    $statement = database()->prepare($sql);
    $statement->bind_param($types, ...$parameters);
    $statement->execute();

    return $statement;
}

function transaction(callable $write): void
{
    $connection = database();
    $connection->begin_transaction();

    try {
        $write();
        $connection->commit();
    } catch (Throwable $error) {
        $connection->rollback();
        throw $error;
    }
}

function currentUserId(): ?int
{
    $userId = (int) ($_SESSION['user_id'] ?? 0);
    return $userId > 0 ? $userId : null;
}

function currentUser(): ?array
{
    $userId = currentUserId();
    if ($userId === null) {
        return null;
    }

    $user = query(
        'SELECT id, username, email, full_name, created_at FROM users WHERE id = ?',
        'i',
        [$userId]
    )->get_result()->fetch_assoc();

    if ($user === null) {
        $_SESSION = [];
    }

    return $user;
}

function signIn(int $userId): void
{
    session_regenerate_id(true);
    $_SESSION = ['user_id' => $userId];
}

function csrfToken(): string
{
    return $_SESSION['csrf_token'] ??= bin2hex(random_bytes(32));
}

function redirect(string $url, int $status = 302): void
{
    header('Location: ' . $url, true, $status);
    exit;
}
