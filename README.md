# CodePulse

Учебный сайт по HTML и CSS, JavaScript и React. Уроки и проверка ответов доступны гостям. Аккаунт нужен для истории попыток и сохранения прогресса.

## Локальный запуск

Требуются PHP 8.1+ с расширением `mysqli` (драйвер `mysqlnd`) и MySQL 8.0.16+ или MariaDB 10.5+. В Windows расширение `mysqli` можно включить в `php.ini`. Команды ниже выполняются в PowerShell из папки проекта.

```powershell
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS codepulse_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
Get-Content -Raw -Encoding UTF8 database.sql | mysql --default-character-set=utf8mb4 -u root -p codepulse_db

$env:CODEPULSE_DB_HOST = '127.0.0.1'
$env:CODEPULSE_DB_PORT = '3306'
$env:CODEPULSE_DB_NAME = 'codepulse_db'
$env:CODEPULSE_DB_USER = 'root'
$env:CODEPULSE_DB_PASSWORD = 'пароль вашей локальной БД'
php -S 127.0.0.1:8000
```

Откройте [http://127.0.0.1:8000](http://127.0.0.1:8000). Для MariaDB вместо `mysql` также можно использовать `mariadb`. Если локальная БД без пароля, уберите `-p` и задайте пустой `CODEPULSE_DB_PASSWORD`. Встроенный сервер PHP предназначен для локальной разработки и не применяет `.htaccess`.

Для локальной проверки зарегистрируйте тестовый аккаунт в интерфейсе. `database.sql` создаёт отсутствующие таблицы и не меняет структуру уже созданных.

GitHub хранит исходники. GitHub Pages не запускает PHP, поэтому для входа и профиля нужен PHP-хостинг с MySQL или MariaDB. При размещении сайта задайте через окружение реквизиты отдельного пользователя БД и используйте HTTPS. Не помещайте настройки, копии БД и журналы в доступную из веба папку.

Bootstrap хранится в проекте. Шрифты Google Fonts и иконки Font Awesome загружаются из сети; для уроков и сохранения прогресса эти сервисы не нужны.
