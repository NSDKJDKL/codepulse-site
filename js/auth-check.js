async function updateAccountMenu() {
    const menu = document.querySelector('#navbarNav .navbar-nav');
    const loginLink = menu?.querySelector('a[href="login.html"]');
    const registerLink = menu?.querySelector('a[href="register.html"]');
    if (!loginLink || !registerLink) return;

    try {
        const response = await fetch('php/check_session.php');
        if (!response.ok) return;

        const session = await response.json();
        if (!session.logged_in || !session.csrf_token) return;

        loginLink.href = 'profile.php';
        loginLink.textContent = 'Профиль';
        loginLink.classList.remove('active');
        loginLink.removeAttribute('aria-current');

        const logoutForm = document.createElement('form');
        logoutForm.action = 'php/logout.php';
        logoutForm.method = 'post';

        const token = document.createElement('input');
        token.type = 'hidden';
        token.name = 'csrf_token';
        token.value = session.csrf_token;

        const logoutButton = document.createElement('button');
        logoutButton.type = 'submit';
        logoutButton.className = 'nav-link';
        logoutButton.textContent = 'Выход';

        logoutForm.append(token, logoutButton);
        registerLink.replaceWith(logoutForm);
    } catch (error) {
        console.warn('Не удалось проверить сеанс.', error);
    }
}

updateAccountMenu();
