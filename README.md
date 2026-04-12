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
├── public/
│   ├── webhook.php
│   └── worker_api.php
├── scripts/
│   ├── init_db.php
│   ├── schema.sql
│   ├── migrate_sqlite_to_mysql.php
│   ├── php_worker_runtime.php
│   └── backup_runtime.php
├── src/
├── tests/
├── env.example
├── config.env.example
└── install.sh
```

## Environment

Create `.env` from sample:

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

## Installation Guide (VPS / Dedicated Server)

### 1) Requirements

Install PHP + needed extensions + MySQL:

- `php` 8.1+
- `pdo_mysql`
- `curl`
- `mbstring`
- `json`

### 2) Clone + configure

```bash
git clone https://github.com/Emadhabibnia1385/ConfigFlow.git
cd ConfigFlow
cp env.example .env
nano .env
```

### 3) Initialize database schema

```bash
php scripts/init_db.php
```

### 4) Serve webhook endpoint

#### Option A: Quick dev server

```bash
php -S 0.0.0.0:8080 -t public
```

#### Option B: Nginx/Apache (recommended production)

Point your web root to `public/` and expose `https://YOUR_DOMAIN/webhook.php`.

### 5) Set Telegram webhook

Use your bot token and public HTTPS URL:

```bash
curl -X POST "https://api.telegram.org/bot<YOUR_BOT_TOKEN>/setWebhook" \
  -d "url=https://YOUR_DOMAIN/webhook.php"
```

Verify webhook info:

```bash
curl "https://api.telegram.org/bot<YOUR_BOT_TOKEN>/getWebhookInfo"
```

### 6) Run worker runtime (optional but recommended)

```bash
php scripts/php_worker_runtime.php
```

Run it with `systemd`/`supervisor` for production.

---

## Installation Guide (Shared Hosting / cPanel / aaPanel)

This project can run on most shared hosts if they support PHP 8.1+ and MySQL.

### 1) Upload files

- Upload project files to your host (Git deploy or File Manager).
- Keep project files in a private app directory if possible.
- Expose `public/` via your domain/subdomain document root.

### 2) Configure document root

Set your domain/subdomain root to the `public` folder, for example:

- cPanel: **Domains → Manage → Document Root** => `.../ConfigFlow/public`
- aaPanel: **Website → Site Directory** => `.../ConfigFlow/public`

If you cannot point root directly to `public/`, use a subdomain that points to `public/`.

### 3) Create database + `.env`

- Create MySQL DB/user from your panel.
- Fill `.env` with those credentials.

Then run schema init from Terminal/SSH (if available):

```bash
php scripts/init_db.php
```

If SSH is not available, ask host support to execute the script once.

### 4) Set webhook URL

After your URL is live over HTTPS, set:

```bash
curl -X POST "https://api.telegram.org/bot<YOUR_BOT_TOKEN>/setWebhook" \
  -d "url=https://YOUR_DOMAIN/webhook.php"
```

### 5) Worker runtime on shared hosts

- Preferred: run `php scripts/php_worker_runtime.php` as a background process (if host allows).
- Fallback: run it through cron frequently (if long-running processes are restricted).

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
find public scripts src tests -name '*.php' -print0 | xargs -0 -n1 php -l
php tests/WorkerApiAppTest.php
```
