# Деплой у хмару

Нижче — варіант на **[Railway](https://railway.app)** (MySQL, Docker, кілька сервісів). Можна адаптувати під **Fly.io**, **Render** тощо: потрібні той самий образ (кореневий `Dockerfile`) або збірка `composer` + `npm run build`, змінні оточення та окремий процес для черги/розкладу.

## Що вже є в репозиторії

| Файл | Призначення |
|------|-------------|
| `Dockerfile` | Продакшен-образ: збірка Vite, `composer install --no-dev`, `php artisan serve` (порт з `$PORT`). |
| `railway.toml` | `releaseCommand`: міграції; healthcheck: `/up`. |
| `scripts/railway-worker.sh` | Черга + `schedule:work` для другого сервісу. |

Локальна розробка **не змінюється**: як і раніше `compose.yaml` + `docker/php/Dockerfile`.

---

## Railway (покроково)

### 1. Проєкт і репозиторій

1. Зайди на [railway.app](https://railway.app), створи **New Project** → **Deploy from GitHub** (або GitLab) і вибери цей репозиторій.
2. Railway підхопить `Dockerfile` і `railway.toml`.

### 2. База MySQL

1. У проєкті **New** → **Database** → **MySQL**.
2. Відкрий плагін MySQL → **Variables** / **Connect** — скопіюй host, port, user, password, database.
3. На **сервісі веб-додатку** → **Variables** додай (назви можуть відрізнятися — головне відповідність Laravel):

| Змінна | Приклад / звідки |
|--------|------------------|
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_KEY` | Згенеруй: `php artisan key:generate --show` локально або одноразово в консолі Railway CLI. |
| `APP_URL` | Публічний URL сервісу Railway, напр. `https://your-app.up.railway.app` |
| `DB_CONNECTION` | `mysql` |
| `DB_HOST` | з MySQL-плагіна |
| `DB_PORT` | зазвичай `3306` |
| `DB_DATABASE` | ім’я БД |
| `DB_USERNAME` | користувач |
| `DB_PASSWORD` | пароль |
| `QUEUE_CONNECTION` | `database` |
| `LOG_CHANNEL` | `stderr` (зручно дивитись логи в Railway) |

Якщо Railway дає один рядок **`DATABASE_URL`** або **`MYSQL_URL`** (формат `mysql://user:pass@host:port/db`), Laravel 12 читає **`DB_URL`**. Додай:

```env
DB_URL=${{MySQL.DATABASE_URL}}
```

(підстав змінну **Reference** з плагіна MySQL в UI Railway — точна назва залежить від шаблону.)

Додатково можна виставити `DB_CONNECTION=mysql` — при наявності `DB_URL` Laravel часто підхоплює решту.

### 3. Демо-користувач після міграцій

Після першого успішного деплою в **консолі** сервісу веб (або одноразовій команді):

```bash
php artisan db:seed --force
```

Або створи користувача через `php artisan tinker`.

### 4. Фронт і API з браузера

У **Variables** веб-сервісу:

```env
VITE_API_URL=
```

Для продакшену фронт зібраний у `public/build` і віддається з того ж хосту, що й API — **залиш порожнім** або вкажи повний URL до `/api` свого `APP_URL`, наприклад:

```env
VITE_API_URL=https://your-app.up.railway.app/api
```

(Якщо порожньо, у бандлі лишиться відносний шлях `/api` — це нормально для одного домену.)

### 5. Воркер (черга + розклад перевірок)

Без цього HTTP-перевірки та листи **не** виконуються у фоні.

1. **New** → **Empty Service** (або **Duplicate** веб-сервісу).
2. Той самий репозиторій і той самий **Dockerfile** (або той самий образ, якщо Railway дозволяє).
3. **Start Command** (перевизнач):

```text
sh scripts/railway-worker.sh
```

4. Скопіюй **усі ті самі змінні**, що й у веб-сервісі (`APP_KEY`, `DB_*`, `QUEUE_CONNECTION`, …), щоб воркер бачив ту саму БД.

### 6. Сповіщення (опційно)

Додай на **обидва** сервіси (веб і воркер) однакові:

- `MAIL_*`, `TELEGRAM_BOT_TOKEN`, `TELEGRAM_CHAT_ID` — див. кореневий `README.md`.

---

## Перевірка

- Відкрий `APP_URL` — має відкритися SPA.
- `GET https://<твій-домен>/up` — має бути 200 (healthcheck).
- `POST /api/login` — перевір логін.
- Після хвилини з воркером — з’являються записи в логах перевірок (якщо є активні монітори).

---

## Інші платформи (коротко)

| Платформа | Ідея |
|-----------|------|
| **Fly.io** | `fly launch` з кореневим `Dockerfile`; окремий `fly.toml` процес для `scripts/railway-worker.sh`; MySQL — [Fly Managed](https://fly.io/docs/mpg) або зовнішній хостинг. |
| **Render** | Web Service (Docker), Background Worker (та сама команда воркера), **Cron Job** щохвилини: `php artisan schedule:run` *або* лише воркер з `schedule:work`. Безкоштовний PostgreSQL — тоді треба було б перейти на `pgsql` у коді; MySQL на Render часто платний. |
| **VPS** | Docker Compose з продакшен-override: один контейнер з nginx+php або той самий `Dockerfile` + окремо `queue` і `scheduler`, як у локальному `compose.yaml`. |

---

## Типові проблеми

| Проблема | Дія |
|----------|-----|
| 500 на старті | Перевір `APP_KEY`, `DB_*` / `DB_URL`, логи (`LOG_CHANNEL=stderr`). |
| Міграції не проходять | Дивись лог **Release** у Railway; переконайся, що БД доступна з build/release мережі. |
| Перевірки не біжать | Чи запущений другий сервіс з `railway-worker.sh`; чи спільні змінні з БД. |
| Mixed content / API | `APP_URL` і браузер — лише HTTPS; `VITE_API_URL` має бути https, якщо заданий явно. |
