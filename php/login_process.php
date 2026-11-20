<?php

declare(strict_types=1);

require_once __DIR__ . '/http.php';

handleRequest(function (): void {
    requireMethod('POST');

    $login = trim(postString('login'));
    $password = postString('password');
    if ($login === '' || $password === '') {
        sendError('Заполните оба поля.', 422);
    }

    $user = query(
        'SELECT id, password FROM users WHERE username = ? OR email = ?',
        'ss',
        [$login, $login]
    )->get_result()->fetch_assoc();

    if ($user === null || !password_verify($password, $user['password'])) {
        sendError('Неверный логин или пароль.', 401);
    }

    $userId = (int) $user['id'];
    query('UPDATE users SET last_login = NOW() WHERE id = ?', 'i', [$userId]);
    signIn($userId);

    sendJson(['success' => true, 'message' => 'Вы вошли в аккаунт.', 'redirect' => 'profile.php']);
});
