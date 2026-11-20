<?php

declare(strict_types=1);

require_once __DIR__ . '/http.php';

function validateRegistration(string $username, string $email, string $fullName, string $password, string $confirmation): void
{
    if (!preg_match('/^[A-Za-z0-9_]{3,50}\z/', $username)) {
        sendError('Логин должен содержать от 3 до 50 латинских букв, цифр или символов «_».', 422);
    }

    if (strlen($email) > 100 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        sendError('Укажите корректный email длиной до 100 символов.', 422);
    }

    if (!preg_match('/^.{0,100}\z/us', $fullName)) {
        sendError('Имя не должно быть длиннее 100 символов.', 422);
    }

    if (strlen($password) > 72 || !preg_match('/^.{6,}\z/us', $password) || strpos($password, "\0") !== false) {
        sendError('Пароль должен содержать от 6 символов и занимать не более 72 байт.', 422);
    }

    if ($password !== $confirmation) {
        sendError('Пароли не совпадают.', 422);
    }
}

handleRequest(function (): void {
    requireMethod('POST');

    $username = trim(postString('username'));
    $email = trim(postString('email'));
    $fullName = trim(postString('full_name'));
    $password = postString('password');
    validateRegistration($username, $email, $fullName, $password, postString('confirm_password'));

    try {
        query(
            'INSERT INTO users (username, email, password, full_name, last_login) VALUES (?, ?, ?, ?, NOW())',
            'ssss',
            [$username, $email, password_hash($password, PASSWORD_DEFAULT), $fullName === '' ? null : $fullName]
        );
    } catch (mysqli_sql_exception $error) {
        if ($error->getCode() === 1062) {
            sendError('Такой логин или email уже зарегистрирован.', 409);
        }

        throw $error;
    }

    signIn((int) database()->insert_id);
    sendJson(['success' => true, 'message' => 'Аккаунт создан.', 'redirect' => 'profile.php'], 201);
});
