<?php

declare(strict_types=1);

require_once __DIR__ . '/http.php';
require_once __DIR__ . '/quiz.php';

handleRequest(function (): void {
    $userId = requireAuthenticatedPost();
    $course = postString('course_name');
    $answers = $_POST['answers'] ?? null;
    $result = is_array($answers) ? gradeQuiz($course, $answers) : null;

    if ($result === null) {
        sendError('Не удалось проверить ответы. Убедитесь, что ответили на все вопросы.', 422);
    }

    $percentage = $result['percentage'];
    $completed = (int) ($percentage >= PASS_PERCENT);

    transaction(function () use ($userId, $course, $result, $percentage, $completed): void {
        query(
            'INSERT INTO test_results (user_id, course_name, score, total_questions) VALUES (?, ?, ?, ?)',
            'isii',
            [$userId, $course, $result['score'], $result['total']]
        );

        query(
            'INSERT INTO user_progress (user_id, course_name, progress_percentage, completed)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE progress_percentage = GREATEST(progress_percentage, ?),
                                     completed = GREATEST(completed, ?), last_accessed = NOW()',
            'isiiii',
            [$userId, $course, $percentage, $completed, $percentage, $completed]
        );
    });

    sendJson(['success' => true, 'message' => 'Результат сохранён.'] + $result);
});
