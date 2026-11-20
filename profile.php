<?php

declare(strict_types=1);

require_once __DIR__ . '/php/config.php';
require_once __DIR__ . '/php/quiz.php';

function html(string $text): string
{
    return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$courses = [
    'HTML & CSS' => ['url' => 'html-css.html', 'description' => 'Соберите и оформите первую страницу.'],
    'JavaScript' => ['url' => 'js.html', 'description' => 'Добавьте на страницу кнопки и другие интерактивные элементы.'],
    'React' => ['url' => 'react.html', 'description' => 'Соберите интерфейс из компонентов.'],
];

try {
    $user = currentUser();
    if ($user === null) {
        redirect('login.html');
    }

    $userId = (int) $user['id'];
    $progress = query(
        'SELECT course_name, progress_percentage, completed, last_accessed
         FROM user_progress WHERE user_id = ? ORDER BY last_accessed DESC, id DESC',
        'i',
        [$userId]
    )->get_result()->fetch_all(MYSQLI_ASSOC);
    $attemptCount = (int) query(
        'SELECT COUNT(*) FROM test_results WHERE user_id = ?',
        'i',
        [$userId]
    )->get_result()->fetch_row()[0];
    $latestTests = query(
        'SELECT course_name, score, total_questions, completed_at
         FROM test_results WHERE user_id = ? ORDER BY completed_at DESC, id DESC LIMIT 10',
        'i',
        [$userId]
    )->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (RuntimeException $error) {
    error_log((string) $error);
    http_response_code(503);
    header('Content-Type: text/plain; charset=utf-8');
    exit('Профиль временно недоступен. Попробуйте открыть его позже.');
}

$completedCount = count(array_filter($progress, static function (array $course): bool {
    return (bool) $course['completed'];
}));
$stats = [
    'Курсов начато' => count($progress),
    'Курсов завершено' => $completedCount,
    'Попыток в тестах' => $attemptCount,
];
$displayName = $user['full_name'] ?? $user['username'];
if ($displayName === '') {
    $displayName = $user['username'];
}
$token = html(csrfToken());
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Профиль — CodePulse</title>
    <link rel="stylesheet" href="bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/navigation.css">
    <link rel="stylesheet" href="css/profile.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Oswald:wght@500;600;700&display=swap">
    <script src="bootstrap/js/bootstrap.bundle.min.js" defer></script>
    <script src="js/profile.js" defer></script>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top" aria-label="Основная навигация">
        <div class="container">
            <a class="navbar-brand" href="index.html"><img src="img/codepulse-wordmark.png" width="540" height="108" alt="CodePulse"></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Меню навигации">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="index.html">Главная</a></li>
                    <li class="nav-item"><a class="nav-link" href="home.html">Как учиться</a></li>
                    <li class="nav-item"><a class="nav-link" href="about.html">О проекте</a></li>
                    <li class="nav-item"><a class="nav-link" href="contact.html">Контакты</a></li>
                    <li class="nav-item"><a class="nav-link" href="vibor.html">Обучение</a></li>
                    <li class="nav-item"><a class="nav-link active" href="profile.php" aria-current="page">Профиль</a></li>
                    <li class="nav-item">
                        <form method="post" action="php/logout.php">
                            <input type="hidden" name="csrf_token" value="<?= $token ?>">
                            <button class="nav-link" type="submit">Выход</button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <main>
        <header class="profile-header py-4 py-md-5">
            <div class="container">
                <h1 class="fw-bold mb-2"><?= html($displayName) ?></h1>
                <?php if ($displayName !== $user['username']): ?>
                    <p class="mb-2">@<?= html($user['username']) ?></p>
                <?php endif; ?>
                <p class="mb-1"><?= html($user['email']) ?></p>
                <p class="small mb-0">Дата регистрации: <?= date('d.m.Y', strtotime($user['created_at'])) ?></p>
            </div>
        </header>

        <div class="container py-4 py-md-5">
            <dl class="row g-3 mb-5">
                <?php foreach ($stats as $label => $value): ?>
                    <div class="col-12 col-sm-4">
                        <div class="border rounded p-3 h-100">
                            <dt class="fw-medium"><?= html($label) ?></dt>
                            <dd class="fs-3 fw-bold mb-0"><?= $value ?></dd>
                        </div>
                    </div>
                <?php endforeach; ?>
            </dl>

            <section class="mb-5" aria-labelledby="progress-title">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <h2 class="fw-bold mb-0" id="progress-title">Мой прогресс</h2>
                    <?php if ($progress || $attemptCount > 0): ?>
                        <form id="reset-progress" method="post" action="php/reset_progress.php">
                            <input type="hidden" name="csrf_token" value="<?= $token ?>">
                            <button class="btn btn-outline-danger btn-sm" type="button" disabled>Сбросить прогресс</button>
                        </form>
                    <?php endif; ?>
                </div>
                <p class="profile-status text-danger" role="status" id="reset-status"></p>

                <?php if (!$progress): ?>
                    <p class="alert alert-info mb-0">Пройдите тест в конце урока. Здесь появится ваш лучший результат.</p>
                <?php endif; ?>
                <?php foreach ($progress as $course): ?>
                    <?php
                    $courseName = $course['course_name'];
                    $courseUrl = $courses[$courseName]['url'] ?? null;
                    $percent = max(0, min(100, (int) $course['progress_percentage']));
                    ?>
                    <article class="border rounded p-3 mb-3">
                        <div class="row g-3 align-items-center">
                            <div class="col-md-8">
                                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                                    <h3 class="h5 fw-bold mb-0"><?= html($courseName) ?></h3>
                                    <?php if ($course['completed']): ?>
                                        <span class="badge text-bg-success">Завершён</span>
                                    <?php endif; ?>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="progress flex-grow-1" role="progressbar" aria-label="<?= html($courseName) ?>" aria-valuenow="<?= $percent ?>" aria-valuemin="0" aria-valuemax="100">
                                        <div class="progress-bar" style="width: <?= $percent ?>%"></div>
                                    </div>
                                    <span class="small fw-bold" aria-hidden="true"><?= $percent ?>%</span>
                                </div>
                            </div>
                            <div class="col-md-4 text-md-end">
                                <p class="text-muted small mb-2"><?= date('d.m.Y H:i', strtotime($course['last_accessed'])) ?></p>
                                <?php if ($courseUrl !== null): ?>
                                    <a href="<?= html($courseUrl) ?>" class="btn btn-outline-primary btn-sm" aria-label="Открыть урок <?= html($courseName) ?>">Открыть урок</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </section>

            <section class="mb-5" aria-labelledby="results-title">
                <h2 class="fw-bold mb-3" id="results-title">Результаты тестов</h2>
                <?php if (!$latestTests): ?>
                    <p class="alert alert-info mb-0">Пока нет сохранённых попыток.</p>
                <?php else: ?>
                    <div class="table-responsive" role="region" aria-labelledby="results-title" tabindex="0">
                        <table class="table align-middle mb-0">
                            <?php if ($attemptCount > count($latestTests)): ?>
                                <caption>Последние 10 попыток</caption>
                            <?php endif; ?>
                            <thead>
                                <tr>
                                    <th scope="col">Курс</th>
                                    <th scope="col">Ответы</th>
                                    <th scope="col">Процент</th>
                                    <th scope="col">Дата</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($latestTests as $test): ?>
                                <?php $percent = quizPercentage((int) $test['score'], (int) $test['total_questions']); ?>
                                <tr>
                                    <td><?= html($test['course_name']) ?></td>
                                    <td><?= (int) $test['score'] ?> / <?= (int) $test['total_questions'] ?></td>
                                    <td><span class="badge text-bg-<?= $percent >= PASS_PERCENT ? 'success' : 'light' ?>"><?= $percent ?>%</span></td>
                                    <td><?= date('d.m.Y H:i', strtotime($test['completed_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>

            <section aria-labelledby="courses-title">
                <h2 class="fw-bold mb-3" id="courses-title">Доступные курсы</h2>
                <div class="row g-3">
                    <?php foreach ($courses as $name => $course): ?>
                        <div class="col-md-4">
                            <article class="card h-100">
                                <div class="card-body d-flex flex-column align-items-start">
                                    <h3 class="h5 fw-bold"><?= html($name) ?></h3>
                                    <p class="card-text"><?= html($course['description']) ?></p>
                                    <a href="<?= html($course['url']) ?>" class="btn btn-outline-primary mt-auto" aria-label="Открыть урок <?= html($name) ?>">Открыть урок</a>
                                </div>
                            </article>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        </div>
    </main>

    <footer class="footer py-4">
        <div class="container">
            <div class="row g-3">
                <div class="col-md-4">
                    <h2 class="h5">CodePulse</h2>
                    <p class="mb-0">Платформа для изучения веб-разработки.</p>
                </div>
                <div class="col-md-4">
                    <h2 class="h5">Быстрые ссылки</h2>
                    <ul class="list-unstyled mb-0">
                        <li><a href="vibor.html">Обучение</a></li>
                        <li><a href="about.html">О проекте</a></li>
                        <li><a href="contact.html">Контакты</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h2 class="h5">Связаться с нами</h2>
                    <ul class="list-unstyled mb-0">
                        <li><a href="mailto:vikharev2009@gmail.com">vikharev2009@gmail.com</a></li>
                        <li>Телефон не указан</li>
                    </ul>
                </div>
            </div>
            <hr>
            <p class="text-center mb-0">&copy; 2026 CodePulse</p>
        </div>
    </footer>
</body>
</html>
