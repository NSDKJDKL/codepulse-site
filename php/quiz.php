<?php

declare(strict_types=1);

const PASS_PERCENT = 70;

function quizPercentage(int $score, int $total): int
{
    if ($total <= 0) {
        return 0;
    }

    return min(100, max(0, (int) round($score * 100 / $total)));
}

function gradeQuiz(string $course, array $answers): ?array
{
    $json = @file_get_contents(__DIR__ . '/../data/quiz-answers.json');
    $answerKeys = $json === false ? null : json_decode($json, true);
    if (!is_array($answerKeys)) {
        throw new RuntimeException('The quiz answer keys could not be loaded.');
    }

    $answerKey = $answerKeys[$course] ?? [];
    $total = count($answerKey);
    if ($total === 0 || count($answers) !== $total) {
        return null;
    }

    $score = 0;
    foreach ($answerKey as $question => $correctAnswer) {
        $answer = $answers[$question] ?? null;
        if (!in_array($answer, ['a', 'b', 'c'], true)) {
            return null;
        }

        if ($answer === $correctAnswer) {
            $score++;
        }
    }

    return [
        'score' => $score,
        'total' => $total,
        'percentage' => quizPercentage($score, $total),
    ];
}
