# Моніторинг доступності доменів

Веб-застосунок для облікових записів користувачів, керування списком доменів і **автоматичних HTTP-перевірок** (GET/HEAD) за розкладом. Результати зберігаються в історії; при **зміні стану** перевірки (OK ↔ не OK) можна надсилати сповіщення **email** та **Telegram**.

Детальна фіксація вимог: [docs/SPEC.md](docs/SPEC.md).

---

## Можливості

- Реєстрація та вхід **без** підтвердження email; вихід із системи.
- Ролі: **користувач** (лише свої домени) та **суперадмін** (усі домени, список користувачів, зміна прапорця суперадміна).
- **Домени** (hostname): додавання, перегляд, зміна, видалення.
- **Кілька перевірок на домен**: шлях (на кшталт `/` або `/health`), метод **GET** або **HEAD**, інтервал (60 с … 24 год), таймаут (1 … 120 с, за замовчуванням 30 с), увімкнено/вимкнено.
- **Критерій успіху**: лише відповіді **2xx**; **3xx** не вважаються успіхом, у лог пишеться примітка (редирект, `Location` у `meta`).
- Перевірка **TLS/сертифіката** (невалідний/прострочений → помилка в логах і «не OK»).
- **Історія перевірок** без автоматичного очищення (дата, OK, HTTP-код, час відповіді, текст помилки, `meta`).
- **Планувальник** (щохвилини) ставить джоби в **чергу**; воркер їх виконує.
- **Інтерфейс**: Vue 3 SPA, мови **українська** та **англійська**.
- **API** з токеном **Laravel Sanctum** (Bearer), обмеження частоти запитів на логін/реєстрацію.
- **Локалізація відповідей API**: заголовок `X-Locale: uk|en` або `Accept-Language`.

---

## Технології

| Шар        | Технології |
|-----------|------------|
| Backend   | PHP 8.3, **Laravel 12** |
| БД        | **MySQL 8** |
| HTTP API  | **Sanctum** (personal access tokens) |
| Frontend  | **Vue 3**, Vue Router, vue-i18n, Axios, **Vite 7**, Tailwind CSS 4 |
| Інфра     | **Docker Compose** (nginx, php-fpm, MySQL, queue worker, scheduler, Node/Vite для dev) |

---

## Що потрібно на машині

- **Docker Desktop** (або Docker Engine + Compose v2).
- **Не** обов’язково встановлювати PHP, Composer, Node чи MySQL на хост — усе в контейнерах.

---

## Швидкий старт (репозиторій уже з Laravel)

```bash
git clone <url> check-domain && cd check-domain

# Якщо ще немає .env — скопіюй з прикладу (див. нижче)
cp .env.example .env   # за потреби відредагуй

make build             # збірка образу PHP (перший раз / після змін Dockerfile)
make up                # MySQL, nginx, PHP-FPM, черга, планувальник
make migrate           # міграції БД
docker compose exec app php artisan db:seed   # демо-користувач (опційно)
```

Збірка фронтенду для продакшену (статика в `public/build`):

```bash
docker compose run --rm --no-deps node sh -c "npm install && npm run build"
```

Відкрий у браузері: **http://localhost:8080**

### Демо-акаунт

| Поле   | Значення          |
|--------|-------------------|
| Email  | `demo@demo.local` |
| Пароль | `demo12345`       |

Користувач має права **суперадміна**. Створюється сидером `Database\Seeders\DatabaseSeeder`.

---

## Перший клон без коду Laravel (тільки Docker-обгортка)

Якщо в репозиторії **немає** файлу `artisan`:

```bash
make init              # composer create-project Laravel ~12 у поточну директорію
make up
make migrate
docker compose exec app php artisan db:seed
docker compose run --rm --no-deps node sh -c "npm install && npm run build"
```

Команда `make init` не перезаписує `compose.yaml`, `docker/`, `docs/`, `Makefile` (див. логіку в Makefile).

---

## Адреси та порти

| Що              | За замовчуванням |
|-----------------|------------------|
| Веб (nginx)     | http://localhost:**8080** |
| MySQL з хоста   | `127.0.0.1`:**33060** → контейнерний `3306` |
| Vite (dev)      | http://localhost:**5173** (профіль `dev`) |

Змінити порт вебу: змінна оточення **`APP_PORT`** (наприклад `APP_PORT=8888 make up` — якщо передавати в compose; зазвичай у `.env` або `export APP_PORT=8888` перед `docker compose up`).

Змінити порт MySQL на хості: **`MYSQL_PORT`**.

---

## Docker Compose: сервіси

| Сервіс       | Призначення |
|-------------|-------------|
| **mysql**   | БД `check_domain`, користувач `laravel` / пароль `laravel` (як у `compose.yaml`). |
| **app**     | PHP-FPM + розширення для Laravel, Composer у образі. |
| **nginx**   | Роздає `public/`, PHP через FastCGI до `app:9000`. |
| **queue**   | `php artisan queue:work database` — джоби перевірок і **черга листів/Telegram**. |
| **scheduler** | `php artisan schedule:work` — щохвилини `domain-checks:dispatch-due`. |
| **node**    | Образ Node; команда `tail -f /dev/null`, щоб контейнер **не завершувався** після `up` (зручно для `docker compose exec node …`). |
| **vite**    | Профіль **`dev`**: `npm install` + `npm run dev` на `0.0.0.0:5173`, polling для macOS/Windows. |

Команда **`make up`** піднімає: `mysql`, `app`, `nginx`, `queue`, `scheduler` (без `node` і без `vite`).

---

## Makefile (коротко)

| Ціль | Команда |
|------|---------|
| Збірка PHP-образу | `make build` |
| Запуск основного стеку | `make up` |
| Зупинка | `make down` |
| Логи всіх сервісів | `make logs` |
| Shell у PHP | `make bash` |
| Міграції | `make migrate` |
| Composer | `make composer CMD='install'` |
| Artisan | `make artisan CMD='migrate'` |
| Лише `npm install` (одноразовий контейнер) | `make npm-install` |
| Підняти тільки Vite (потрібен уже запущений стек або MySQL+app) | `make vite` |
| Стек + Vite для розробки | `make dev` |

Приклади:

```bash
make artisan CMD='migrate:fresh --seed'
docker compose exec app php artisan route:list --path=api
```

---

## Консольні команди Artisan

Усі команди виконуються в PHP-контейнері. З хоста:

```bash
docker compose exec app php artisan <команда>
```

або через Makefile:

```bash
make artisan CMD='list'
```

### Перевірки доменів (власна команда проєкту)

| Команда | Що робить |
|---------|-----------|
| **`php artisan domain-checks:dispatch-due`** | Знаходить активні перевірки, у яких `next_run_at` у минулому або `null`, і для кожної ставить у чергу джобу `RunDomainCheckJob` (пакетами по 100 id). Сам HTTP-запит виконується вже **воркером черги**, не в цій команді. |

Приклад ручного запуску (наприклад, для дебагу, коли планувальник вимкнено):

```bash
docker compose exec app php artisan domain-checks:dispatch-due
```

У штатному режимі ця ж команда викликається **автоматично щохвилини** через Laravel Scheduler (`bootstrap/app.php` → `schedule:work` у контейнері **`scheduler`**).

Переглянути розклад:

```bash
docker compose exec app php artisan schedule:list
```

### Інші типові команди

| Команда | Призначення |
|---------|-------------|
| `php artisan migrate` / `migrate:fresh --seed` | Міграції БД |
| `php artisan db:seed` | Сидери |
| `php artisan queue:work database` | Обробка черги (у Docker зазвичай уже працює сервіс **`queue`**) |
| `php artisan schedule:work` | Планувальник у довгоживучому процесі (у Docker — сервіс **`scheduler`**) |
| `php artisan route:list` | Список маршрутів |

---

## Змінні оточення (`.env`)

Скопіюй `.env.example` у `.env` і за потреби зміни.

**Обов’язкові для роботи застосунку**

| Змінна | Опис |
|--------|------|
| `APP_KEY` | `php artisan key:generate` (у Docker: `docker compose exec app php artisan key:generate`) |
| `APP_URL` | Базовий URL застосунку, наприклад `http://localhost:8080` |
| `DB_*` | У Docker зазвичай як у `compose.yaml` / `scripts/patch-env-docker.sh` |

**Черга**

| Змінна | Опис |
|--------|------|
| `QUEUE_CONNECTION` | У compose за замовчуванням `database` |

**Фронт (розробка з Vite на іншому порту)**

| Змінна | Опис |
|--------|------|
| `VITE_API_URL` | Базовий URL API для браузера, наприклад `http://localhost:8080/api` |

**Пошта (сповіщення)**

| Змінна | Приклад |
|--------|---------|
| `MAIL_MAILER` | `log` (у лог), `smtp` (реальна відправка) |
| `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD` | Для SMTP |
| `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME` | Відправник |

**Telegram (сповіщення)**

| Змінна | Опис |
|--------|------|
| `TELEGRAM_BOT_TOKEN` | Токен бота від @BotFather |
| `TELEGRAM_CHAT_ID` | ID чату (користувача або групи), куди слати повідомлення |

Якщо `TELEGRAM_*` порожні, спроба йти в Telegram пропускається; листи залежать від `MAIL_*`.

---

## Розробка фронтенду (Vite)

1. Підніми бекенд: `make up` (або `make dev` — одразу з Vite).
2. У **`.env`** на хості (Vite читає лише `VITE_*`):

   ```env
   VITE_API_URL=http://localhost:8080/api
   ```

3. Запуск:
   - **`make dev`** — mysql, app, nginx, queue, scheduler + **vite**;
   - або **`make vite`** — лише контейнер Vite (інші сервіси вже мають бути запущені).

4. Браузер: **http://localhost:5173** — API йде на порт **8080**.

Після змін у JS/CSS для продакшену знову виконай `npm run build` у контейнері Node (див. вище).

---

## Node.js у Docker

- **Разова команда** (не потрібен запущений сервіс `node`):

  ```bash
  docker compose run --rm --no-deps node sh -c "npm install && npm run build"
  ```

- **Якщо піднято** `docker compose up -d node`, контейнер лишається в стані **Up** (через `tail -f /dev/null`), можна:

  ```bash
  docker compose exec node npm run build
  ```

---

## API (REST)

Базовий префікс: **`/api`**. Формат JSON. Для JSON-відповідей варто надсилати заголовок `Accept: application/json`.

### Автентифікація

- Після `POST /api/register` або `POST /api/login` у відповіді є **`token`** (plain text).
- Надалі: заголовок **`Authorization: Bearer <token>`**.
- Вихід: `POST /api/logout` (з тим самим заголовком).

### Локаль повідомлень про помилки

- **`X-Locale: uk`** або **`X-Locale: en`**
- або заголовок **`Accept-Language`** (наприклад `uk`, `uk-UA`).

### Обмеження частоти

- `POST /api/register` — throttling (див. `routes/api.php`).
- `POST /api/login` — throttling.

### Маршрути (скорочено)

| Метод | Шлях | Опис |
|-------|------|------|
| POST | `/api/register` | Реєстрація |
| POST | `/api/login` | Вхід |
| POST | `/api/logout` | Вихід |
| GET | `/api/user` | Поточний користувач |
| GET, POST | `/api/domains` | Список (пагінація), створення |
| GET, PATCH, DELETE | `/api/domains/{id}` | Перегляд, оновлення, видалення |
| GET, POST | `/api/domains/{id}/checks` | Список перевірок, створення |
| PATCH, DELETE | `/api/domain-checks/{id}` | Оновлення, видалення перевірки |
| GET | `/api/domain-checks/{id}/logs` | Історія (пагінація, `per_page`) |
| GET, PATCH | `/api/admin/users`, `/api/admin/users/{id}` | Лише **суперадмін** |

Для суперадміна в `POST/PATCH /api/domains` можна передавати **`user_id`**, щоб прив’язати домен до іншого користувача. У списку доменів доступний фільтр **`?user_id=`**.

---

## Логіка перевірок і сповіщень

1. Планувальник викликає команду **`domain-checks:dispatch-due`** (щохвилини).
2. У чергу потрапляють джоби для активних перевірок, у яких настав час (`next_run_at`).
3. Запит іде на **`https://{hostname}{path}`** без слідування редиректам (`allow_redirects: false`), з перевіркою TLS.
4. Оновлюються `last_ok`, `last_checked_at`, `next_run_at`; пишеться запис у **`check_logs`**.
5. Якщо **попередній** `last_ok` був відомий і **відрізняється** від нового результату — користувачу-власнику домену надсилається нотифікація (**пошта** + **Telegram**, якщо налаштовано в `.env`).

---

## Тести

```bash
docker compose exec app php artisan test
```

---

## Структура репозиторію (орієнтир)

```
app/                 # Моделі, HTTP (Api/), Jobs, Notifications, Services, Middleware
bootstrap/app.php    # Маршрути API, middleware, розклад
config/              # У т.ч. cors, sanctum
database/migrations/ # Користувачі, домени, перевірки, логи, токени Sanctum
database/seeders/
docker/              # Dockerfile PHP, nginx конфіг
docs/SPEC.md         # Специфікація вимог
lang/en|uk/          # Переклади (auth тощо)
resources/js/        # Vue SPA (сторінки, router, i18n, api)
resources/views/app.blade.php  # Точка входу Vite
routes/api.php
compose.yaml
Makefile
Dockerfile           # Продакшен-образ для PaaS (не плутати з docker/php для локального Compose)
railway.toml         # Приклад конфігу Railway (міграції при релізі, /up)
scripts/railway-worker.sh  # Черга + планувальник для другого сервісу в хмарі
```

---

## Деплой у хмару

**Покрокова інструкція (Railway + MySQL + другий сервіс для черги):** [docs/DEPLOY.md](docs/DEPLOY.md).

Коротко:

1. У репозиторії є кореневий **`Dockerfile`** (Vite + Composer + `php artisan serve` на `$PORT`) — для Railway, Fly.io та аналогів.
2. **`railway.toml`**: при релізі виконується `php artisan migrate --force`, healthcheck — `GET /up`.
3. Окремо підніми **воркер** з тієї ж збірки і командою **`sh scripts/railway-worker.sh`** (черга + `schedule:work`), інакше перевірки доменів не запускатимуться.
4. Змінні: `APP_KEY`, `APP_URL`, `DB_*` або `DB_URL`, `QUEUE_CONNECTION=database`, за потреби `LOG_CHANNEL=stderr`, пошта/Telegram — див. [docs/DEPLOY.md](docs/DEPLOY.md).

Загальна схема для будь-якого хостингу: MySQL, продакшен-змінні, міграції, зібраний фронт (`public/build`), процес вебу + процес черги/розкладу.

---

## Типові проблеми

| Симптом | Що перевірити |
|---------|----------------|
| Порожня сторінка / немає стилів | Чи зібрано фронт: `public/build`, команда `npm run build`. |
| CORS / мережа з Vite | `VITE_API_URL` має вказувати на реальний хост API (з порту **8080** при локальному Docker). |
| Перевірки не біжать | Чи запущені контейнери **`queue`** і **`scheduler`** (`docker compose ps`). |
| Листи / Telegram не приходять | `MAIL_*`, `TELEGRAM_*`, логи `storage/logs`, черга має оброблятися воркером. |
| Контейнер `node` одразу Exited | У цьому проєкті для `node` задана команда `tail -f /dev/null`; для збірки зручніше `docker compose run --rm node …`. |

---

## Ліцензія

Як у кореневому `composer.json` проєкту Laravel (MIT), якщо не зазначено інакше.
