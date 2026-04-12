# ConfigFlow

ConfigFlow is a PHP Telegram bot for VPN config sales and delivery, with stock-based inventory, payment workflows, admin moderation tools, and an optional worker API for panel automation.

## Stack

- PHP 8.1+
- MySQL 8+ (MariaDB compatible)
- Telegram Bot API (webhook mode)

## Features (current codebase)

- Telegram update routing (`message` + `callback_query`)
- Start/menu/profile/support/config navigation
- Package purchase flow with stock reservation and delivery handling
- Payment gateway orchestration (wallet, card, crypto, tetrapay)
- Admin review flows for requests and payments
- Free-test and agency request tracking
- Worker API endpoints for async x-ui style jobs (`public/worker_api.php`)
- Runtime worker loop (`scripts/php_worker_runtime.php`)
- Backup runtime and SQLite migration helpers

## Project Structure

```text
ConfigFlow/
├── webhook.php
├── install.php
├── public/
│   └── worker_api.php
├── scripts/
│   ├── init_db.php
│   ├── schema.sql
│   ├── migrate_sqlite_to_mysql.php
│   ├── php_worker_runtime.php
│   └── backup_runtime.php
├── src/
├── tests/
└── env.example
```

## Environment Files

- `env.example` is a **template** only.
- Real runtime config is `.env` (created manually or by installer).

Create manually:

```bash
cp env.example .env
```

Main variables:

```env
BOT_TOKEN=
BOT_USERNAME=
ADMIN_IDS=
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=configflow
DB_USER=root
DB_PASS=
TETRAPAY_CREATE_URL=https://tetra98.com/api/create_order
TETRAPAY_VERIFY_URL=https://tetra98.com/api/verify
```

---

## Quick Install Wizard (Recommended)

Run:

```bash
php install.php
```

The installer will:

1. Ask for `.env` values (bot token, DB credentials, admin IDs, etc.)
2. Generate `.env`
3. Connect to MySQL and initialize schema (`scripts/init_db.php`)
4. Optionally set Telegram webhook automatically

---

## Installation Guide (VPS / Dedicated Server)

### 1) Requirements

Install PHP + required extensions + MySQL:

- `php` 8.1+
- `pdo_mysql`
- `curl`
- `mbstring`
- `json`

### 2) Clone

```bash
git clone https://github.com/Emadhabibnia1385/ConfigFlow.git
cd ConfigFlow
```

### 3) Install using wizard (or manual)

Wizard:

```bash
php install.php
```

Manual schema init (if `.env` already exists):

```bash
php scripts/init_db.php
```

### 4) Serve webhook endpoint

Dev server:

```bash
php -S 0.0.0.0:8080
```

Production (Nginx/Apache): expose `https://YOUR_DOMAIN/webhook.php`.

### 5) Set webhook (if not done by installer)

```bash
curl -X POST "https://api.telegram.org/bot<YOUR_BOT_TOKEN>/setWebhook" \
  -d "url=https://YOUR_DOMAIN/webhook.php"
```

Check status:

```bash
curl "https://api.telegram.org/bot<YOUR_BOT_TOKEN>/getWebhookInfo"
```

### 6) Run worker runtime (optional but recommended)

```bash
php scripts/php_worker_runtime.php
```

---

## Installation Guide (Shared Hosting / cPanel / aaPanel)

This project can run on shared hosting if PHP 8.1+ and MySQL are available.

1. Upload project files.
2. Keep your usual document root (no special root change needed for webhook).
3. Ensure `https://YOUR_DOMAIN/webhook.php` is reachable.
4. Run `php install.php` from Terminal/SSH (or ask host support to run it once).
5. If webhook was skipped in installer, set it manually via Telegram API.
6. For worker runtime, use background process if allowed, otherwise cron.

---

## Utilities

SQLite to MySQL migration:

```bash
php scripts/migrate_sqlite_to_mysql.php /path/to/configflow.db
```

Backup runtime:

```bash
php scripts/backup_runtime.php
```

## Checks

```bash
find . -maxdepth 2 -type f -name '*.php' -print0 | xargs -0 -n1 php -l
php tests/WorkerApiAppTest.php
```
