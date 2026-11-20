<?php

declare(strict_types=1);

require_once __DIR__ . '/http.php';

handleRequest(function (): void {
    requireMethod('GET');
    $user = currentUser();

    if ($user === null) {
        sendJson(['logged_in' => false]);
    }

    sendJson([
        'logged_in' => true,
        'username' => $user['username'],
        'user_id' => (int) $user['id'],
        'csrf_token' => csrfToken(),
    ]);
});
