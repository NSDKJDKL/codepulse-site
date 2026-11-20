<?php

declare(strict_types=1);

require_once __DIR__ . '/http.php';

handleRequest(function (): void {
    $userId = requireAuthenticatedPost();

    transaction(function () use ($userId): void {
        query('DELETE FROM test_results WHERE user_id = ?', 'i', [$userId]);
        query('DELETE FROM user_progress WHERE user_id = ?', 'i', [$userId]);
    });

    sendJson(['success' => true, 'message' => 'Прогресс сброшен.']);
});
