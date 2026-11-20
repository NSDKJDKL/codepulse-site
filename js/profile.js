const resetForm = document.querySelector('#reset-progress');

if (resetForm) {
    const resetButton = resetForm.querySelector('button');
    const resetStatus = document.querySelector('#reset-status');

    resetButton.disabled = false;
    resetButton.addEventListener('click', async () => {
        if (!window.confirm('Удалить результаты всех тестов и прогресс по курсам?')) {
            return;
        }

        resetButton.disabled = true;
        resetStatus.textContent = '';

        try {
            const response = await fetch(resetForm.action, {
                method: 'POST',
                body: new FormData(resetForm),
            });
            const result = await response.json();

            if (response.ok && result.success) {
                window.location.reload();
                return;
            }

            resetStatus.textContent = result.message || 'Не удалось сбросить прогресс.';
        } catch {
            resetStatus.textContent = 'Не удалось сбросить прогресс. Попробуйте ещё раз.';
        }

        resetButton.disabled = false;
    });
}
