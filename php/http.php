<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

function sendJson(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function sendError(string $message, int $status): void
{
    sendJson(['success' => false, 'message' => $message], $status);
}

function handleRequest(callable $action): void
{
    try {
        $action();
    } catch (RuntimeException $error) {
        error_log((string) $error);
        sendError('Сервис временно недоступен. Попробуйте позже.', 503);
    }
}

function postString(string $field): string
{
    $value = $_POST[$field] ?? '';
    return is_string($value) ? $value : '';
}

function requireMethod(string $method): void
{
    if ($_SERVER['REQUEST_METHOD'] !== $method) {
        header('Allow: ' . $method);
        sendError('Неверный метод запроса.', 405);
    }
}

function requireAuthenticatedPost(): int
{
    requireMethod('POST');

    $userId = currentUserId();
    if ($userId === null) {
        sendError('Войдите в аккаунт, чтобы продолжить.', 401);
    }

    $token = $_SESSION['csrf_token'] ?? null;
    if ($token === null || !hash_equals($token, postString('csrf_token'))) {
        sendError('Обновите страницу и попробуйте снова.', 403);
    }

    return $userId;
}
