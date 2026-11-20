function setupCopyButton(button) {
    const label = button.querySelector('[data-copy-label]');
    const code = document.getElementById(button.dataset.copyCode);
    let resetLabel;

    button.addEventListener('click', async () => {
        try {
            await navigator.clipboard.writeText(code.textContent);
            label.textContent = 'Скопировано';
        } catch {
            label.textContent = 'Выделите код вручную';
        }
        clearTimeout(resetLabel);
        resetLabel = setTimeout(() => {
            label.textContent = 'Копировать';
        }, 3000);
    });
}

async function loadAnswerKey(course) {
    const response = await fetch('data/quiz-answers.json', { signal: AbortSignal.timeout(8000) });
    if (!response.ok) {
        throw new Error('Не удалось загрузить ключ ответов.');
    }

    const keys = await response.json();
    return Object.entries(keys[course]);
}

async function saveQuizResult(course, answers, result) {
    const sessionResponse = await fetch('php/check_session.php', {
        cache: 'no-store',
        signal: AbortSignal.timeout(8000),
    });
    const session = await sessionResponse.json();
    if (!sessionResponse.ok) {
        throw new Error('Не удалось проверить вход.');
    }
    if (!session.logged_in) {
        const login = document.createElement('a');
        login.href = 'login.html';
        login.textContent = 'Войдите';
        result.append(' ', login, ', чтобы сохранять результаты следующих попыток.');
        return;
    }

    const data = new URLSearchParams({
        course_name: course,
        csrf_token: session.csrf_token,
    });
    for (const [name, choice] of answers) {
        data.set(`answers[${name}]`, choice);
    }
    const response = await fetch('php/save_test_result.php', {
        method: 'POST',
        body: data,
        signal: AbortSignal.timeout(8000),
    });
    const saved = await response.json();
    if (!response.ok || !saved.success) {
        throw new Error(saved.message || 'Ошибка сохранения.');
    }
    result.textContent = describeScore(saved.score, saved.total, saved.percentage) + ' Результат сохранён в профиле.';
}

function describeScore(score, total, percentage) {
    const advice = percentage >= 70
        ? 'Тест пройден.'
        : 'Для прохождения нужно набрать 70%. Разберите ошибки и попробуйте ещё раз.';
    return `Верных ответов: ${score} из ${total} (${percentage}%). ${advice}`;
}

function showQuestionFeedback(form, answerKey, answers) {
    let score = 0;
    for (const [name, correctChoice] of answerKey) {
        const choices = Array.from(form.elements.namedItem(name));
        const correctInput = choices.find(input => input.value === correctChoice);
        const isCorrect = answers.get(name) === correctChoice;

        const feedback = document.createElement('p');
        feedback.className = 'quiz-feedback';
        if (isCorrect) {
            score++;
            feedback.textContent = 'Верно.';
        } else {
            const label = correctInput.labels[0].textContent.trim();
            feedback.textContent = `Правильный ответ: ${label}`;
        }
        correctInput.closest('fieldset').append(feedback);
    }
    return score;
}

function setupQuiz(form) {
    const modal = form.closest('.modal');
    const submit = modal.querySelector('button[type="submit"]');
    const reset = modal.querySelector('button[type="reset"]');
    const result = modal.querySelector('[data-quiz-result]');
    const fieldsets = form.querySelectorAll('fieldset');
    const course = form.dataset.quizCourse;
    let running = false;

    function clearResult() {
        result.textContent = '';
        form.querySelectorAll('.quiz-feedback').forEach(feedback => feedback.remove());
        submit.disabled = false;
    }

    form.addEventListener('change', clearResult);
    form.addEventListener('reset', clearResult);
    form.addEventListener('submit', async event => {
        event.preventDefault();
        if (running) {
            return;
        }
        const answers = new FormData(form);
        clearResult();
        running = true;
        submit.disabled = true;
        reset.disabled = true;
        fieldsets.forEach(fieldset => { fieldset.disabled = true; });
        result.textContent = 'Проверяем ответы…';

        let scoreMessage = '';
        try {
            const answerKey = await loadAnswerKey(course);
            const score = showQuestionFeedback(form, answerKey, answers);
            const total = answerKey.length;
            scoreMessage = describeScore(score, total, Math.round(score / total * 100));
            result.textContent = scoreMessage + ' Проверяем, можно ли сохранить результат…';
            await saveQuizResult(course, answers, result);
        } catch {
            submit.disabled = false;
            result.textContent = scoreMessage
                ? scoreMessage + ' Сохранение не подтверждено. Проверьте профиль перед повторной отправкой.'
                : 'Не удалось проверить ответы. Попробуйте ещё раз.';
        } finally {
            running = false;
            reset.disabled = false;
            fieldsets.forEach(fieldset => { fieldset.disabled = false; });
        }
    });
}

document.querySelectorAll('[data-copy-code]').forEach(setupCopyButton);
document.querySelectorAll('[data-quiz-course]').forEach(setupQuiz);
