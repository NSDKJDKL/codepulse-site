const authForm = document.querySelector('[data-auth-form]');
const formError = document.querySelector('#alertMessage');
const submitButton = authForm.querySelector('button[type="submit"]');
const password = authForm.elements.namedItem('password');
const confirmation = authForm.elements.namedItem('confirm_password');

function checkPasswordConfirmation() {
    confirmation.setCustomValidity(
        confirmation.value && confirmation.value !== password.value ? 'Пароли не совпадают.' : ''
    );
}

if (confirmation) {
    password.addEventListener('input', checkPasswordConfirmation);
    confirmation.addEventListener('input', checkPasswordConfirmation);
}

function showFormError(message) {
    formError.textContent = message;
    formError.hidden = false;
    formError.focus();
}

authForm.addEventListener('submit', async (event) => {
    event.preventDefault();
    if (submitButton.disabled) return;

    const originalLabel = submitButton.textContent;
    const formData = new FormData(authForm);
    submitButton.disabled = true;
    submitButton.textContent = authForm.dataset.pendingLabel;
    formError.hidden = true;

    try {
        const response = await fetch(authForm.action, { method: 'POST', body: formData });
        const result = await response.json();
        if (response.ok && result.success) {
            window.location.assign('profile.php');
            return;
        }
        showFormError(result.message || 'Не удалось отправить форму. Попробуйте ещё раз.');
    } catch {
        showFormError('Не удалось связаться с сервером. Попробуйте ещё раз.');
    }
    submitButton.disabled = false;
    submitButton.textContent = originalLabel;
});
