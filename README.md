# ConfigFlow

ConfigFlow is a Telegram bot for selling and delivering VPN configs with stock-based inventory, payment workflows, admin moderation tools, and an optional worker API for panel automation.

> Current codebase status: **PHP-first** (legacy Python bot files were removed).

## Tech Stack

- PHP 8.1+
- MySQL 8+ (or compatible MariaDB)
- Telegram Bot API (webhook mode)

## Current Capabilities

Based on the code currently in `src/`, `public/`, and `scripts/`:

- Telegram update routing for messages and callbacks
- Start flow, main menu, profile/support/config navigation
- Product/package purchase flow with stock reservation + delivery handling
- Payment gateway orchestration (wallet/card/crypto/tetrapay paths)
- Admin-side review flows for payments and requests
- Free-test and agency request tracking tables + moderation states
- Worker API endpoints for async x-ui style jobs (`public/worker_api.php`)
- PHP worker runtime loop (`scripts/php_worker_runtime.php`)
- Backup runtime script (`scripts/backup_runtime.php`)
- SQLite -> MySQL migration helper (`scripts/migrate_sqlite_to_mysql.php`)

## Project Layout

```text
ConfigFlow/
├── public/
│   ├── webhook.php
│   └── worker_api.php
├── scripts/
│   ├── init_db.php
│   ├── upgrade_schema.php
│   ├── schema.sql
│   ├── migrate_sqlite_to_mysql.php
│   ├── php_worker_runtime.php
│   └── backup_runtime.php
├── src/
│   ├── Bootstrap.php
│   ├── Config.php
│   ├── Database.php
│   ├── UpdateRouter.php
│   ├── MessageHandler.php
│   ├── CallbackHandler.php
│   ├── StartHandler.php
│   ├── MenuService.php
│   ├── PaymentGatewayService.php
│   ├── SettingsRepository.php
│   ├── TelegramClient.php
│   ├── WorkerApiApp.php
│   ├── WorkerApiStore.php
│   ├── XuiJobState.php
│   └── PhpWorkerRuntime.php
├── tests/
│   └── WorkerApiAppTest.php
├── env.example
├── config.env.example
└── install.sh
```

## Environment Variables

Copy and edit:

```bash
cp env.example .env
```

`env.example`:

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

## Setup

1. Create database and configure `.env`.
2. Initialize schema:

```bash
php scripts/init_db.php
```

3. (Optional) Upgrade schema for existing installs:

```bash
php scripts/upgrade_schema.php
```

4. Run webhook endpoint (development server):

```bash
php -S 0.0.0.0:8080 -t public
```

5. Run worker runtime (separate process):

```bash
php scripts/php_worker_runtime.php
```

## Optional Utilities

- SQLite migration:

```bash
php scripts/migrate_sqlite_to_mysql.php /path/to/configflow.db
```

- Backup runtime:

```bash
php scripts/backup_runtime.php
```

## Basic Checks

PHP syntax check:

```bash
find public scripts src tests -name '*.php' -print0 | xargs -0 -n1 php -l
```

Run lightweight worker API test script:

```bash
php tests/WorkerApiAppTest.php
```

## Notes

- `install.sh` and `config.env.example` are currently kept for operational compatibility and can be cleaned/migrated further in next steps.
