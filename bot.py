# -*- coding: utf-8 -*-
"""
Meli Trackless / TracklessVPN Telegram Bot — v4
Enhanced Edition: inline menus, crypto payments, reseller, backup, channel lock, etc.

Requirements:
    pip install pyTelegramBotAPI qrcode pillow python-dotenv

Run:
    export BOT_TOKEN="YOUR_BOT_TOKEN"
    export ADMIN_IDS="123456789"
    python bot.py
"""

import io
import os
import re
import html
import shutil
import sqlite3
import threading
import traceback
from datetime import datetime, timedelta

from dotenv import load_dotenv
import qrcode
import telebot
from telebot import types

load_dotenv()

BOT_TOKEN = os.getenv("BOT_TOKEN", "").strip()
ADMIN_IDS = {
    int(item.strip())
    for item in os.getenv("ADMIN_IDS", "").split(",")
    if item.strip().isdigit()
}
DB_NAME = os.getenv("DB_NAME", "meli_trackless.db")

BRAND_TITLE = "TracklessVPN"
BOT_HANDLE = "@TracklessSell_bot"
CHANNEL_HANDLE = "@TracklessVPN"
DEFAULT_ADMIN_HANDLE = "@Tracklessvpnadmin"

# Crypto currency definitions: (setting_key_suffix, display_name, emoji)
CRYPTO_CURRENCIES = [
    ("tron",      "Tron (TRC20)",  "🔴"),
    ("ton",       "TON",           "💎"),
    ("usdt_bep20","USDT (BEP20)",  "💚"),
    ("usdc_bep20","USDC (BEP20)",  "🔵"),
    ("ltc",       "LTC",           "⚡"),
]

NOBITEX_PRICE_URL = "https://nobitex.ir/price"
CONFIGS_PER_PAGE  = 10
USERS_PER_PAGE    = 20

if not BOT_TOKEN or ":" not in BOT_TOKEN:
    raise SystemExit("BOT_TOKEN تنظیم نشده یا معتبر نیست.")
if not ADMIN_IDS:
    raise SystemExit("ADMIN_IDS تنظیم نشده است.")

bot = telebot.TeleBot(BOT_TOKEN, parse_mode="HTML", threaded=True)
USER_STATE: dict = {}
PERSIAN_DIGITS = str.maketrans("۰۱۲۳۴۵۶۷۸۹٠١٢٣٤٥٦٧٨٩", "01234567890123456789")
_backup_timer: threading.Timer | None = None


# ──────────────────────────────────────────────
# Helpers
# ──────────────────────────────────────────────
def now_str() -> str:
    return datetime.now().strftime("%Y-%m-%d %H:%M:%S")


def is_admin(user_id: int) -> bool:
    return user_id in ADMIN_IDS


def normalize_text_number(value: str) -> str:
    value = (value or "").translate(PERSIAN_DIGITS)
    return value.replace(",", "").replace("٬", "").replace(" ", "").replace("تومان", "").replace("ریال", "").strip()


def parse_int(value: str):
    cleaned = normalize_text_number(value)
    if not cleaned or not re.fullmatch(r"\d+", cleaned):
        return None
    return int(cleaned)


def fmt_price(amount) -> str:
    return f"{int(amount or 0):,}"


def display_name(tg_user) -> str:
    name = " ".join(p for p in [tg_user.first_name or "", tg_user.last_name or ""] if p).strip()
    return name or "ㅤ"


def display_username(username: str | None) -> str:
    return f"@{username}" if username else "@ ندارد"


def safe_support_url(raw_value: str) -> str | None:
    raw = (raw_value or "").strip()
    if not raw:
        return None
    if raw.startswith("http://") or raw.startswith("https://"):
        return raw
    raw = raw.replace("https://", "").replace("http://", "").replace("t.me/", "").replace("telegram.me/", "").replace("@", "").strip()
    return f"https://t.me/{raw}" if raw else None


def state_get(user_id: int):
    return USER_STATE.get(user_id)


def state_set(user_id: int, state_name: str, **data):
    USER_STATE[user_id] = {"state_name": state_name, "data": data}


def state_clear(user_id: int):
    USER_STATE.pop(user_id, None)


def state_name(user_id: int) -> str | None:
    st = USER_STATE.get(user_id)
    return st["state_name"] if st else None


def state_data(user_id: int) -> dict:
    st = USER_STATE.get(user_id)
    return st.get("data", {}) if st else {}


def esc(text) -> str:
    return html.escape(str(text or ""))


def back_button(target: str = "main"):
    kb = types.InlineKeyboardMarkup()
    kb.add(types.InlineKeyboardButton("🔙 بازگشت", callback_data=f"nav:{target}"))
    return kb


# ──────────────────────────────────────────────
# Database
# ──────────────────────────────────────────────
def get_conn() -> sqlite3.Connection:
    conn = sqlite3.connect(DB_NAME, check_same_thread=False)
    conn.row_factory = sqlite3.Row
    conn.execute("PRAGMA foreign_keys = ON")
    return conn


def init_db():
    with get_conn() as conn:
        conn.executescript("""
            CREATE TABLE IF NOT EXISTS users (
                user_id       INTEGER PRIMARY KEY,
                full_name     TEXT,
                username      TEXT,
                balance       INTEGER NOT NULL DEFAULT 0,
                joined_at     TEXT    NOT NULL,
                last_seen_at  TEXT    NOT NULL,
                first_start_notified INTEGER NOT NULL DEFAULT 0,
                is_safe       INTEGER NOT NULL DEFAULT 1,
                is_reseller   INTEGER NOT NULL DEFAULT 0
            );

            CREATE TABLE IF NOT EXISTS config_types (
                id   INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL UNIQUE
            );

            CREATE TABLE IF NOT EXISTS packages (
                id             INTEGER PRIMARY KEY AUTOINCREMENT,
                type_id        INTEGER NOT NULL,
                name           TEXT    NOT NULL,
                volume_gb      INTEGER NOT NULL,
                duration_days  INTEGER NOT NULL,
                price          INTEGER NOT NULL,
                active         INTEGER NOT NULL DEFAULT 1,
                FOREIGN KEY(type_id) REFERENCES config_types(id) ON DELETE CASCADE
            );

            CREATE TABLE IF NOT EXISTS configs (
                id                  INTEGER PRIMARY KEY AUTOINCREMENT,
                type_id             INTEGER NOT NULL,
                package_id          INTEGER NOT NULL,
                service_name        TEXT    NOT NULL,
                config_text         TEXT    NOT NULL,
                inquiry_link        TEXT,
                created_at          TEXT    NOT NULL,
                reserved_payment_id INTEGER,
                sold_to             INTEGER,
                purchase_id         INTEGER,
                sold_at             TEXT,
                ended               INTEGER NOT NULL DEFAULT 0,
                FOREIGN KEY(type_id)   REFERENCES config_types(id) ON DELETE CASCADE,
                FOREIGN KEY(package_id) REFERENCES packages(id)    ON DELETE CASCADE
            );

            CREATE TABLE IF NOT EXISTS payments (
                id              INTEGER PRIMARY KEY AUTOINCREMENT,
                kind            TEXT NOT NULL,
                user_id         INTEGER NOT NULL,
                package_id      INTEGER,
                amount          INTEGER NOT NULL,
                payment_method  TEXT    NOT NULL,
                status          TEXT    NOT NULL,
                receipt_file_id TEXT,
                receipt_text    TEXT,
                admin_note      TEXT,
                created_at      TEXT    NOT NULL,
                approved_at     TEXT,
                config_id       INTEGER,
                crypto_currency TEXT
            );

            CREATE TABLE IF NOT EXISTS purchases (
                id             INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id        INTEGER NOT NULL,
                package_id     INTEGER NOT NULL,
                config_id      INTEGER NOT NULL,
                amount         INTEGER NOT NULL,
                payment_method TEXT    NOT NULL,
                created_at     TEXT    NOT NULL,
                is_test        INTEGER NOT NULL DEFAULT 0
            );

            CREATE TABLE IF NOT EXISTS reseller_prices (
                id         INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id    INTEGER NOT NULL,
                package_id INTEGER NOT NULL,
                price      INTEGER NOT NULL,
                UNIQUE(user_id, package_id)
            );

            CREATE TABLE IF NOT EXISTS settings (
                key   TEXT PRIMARY KEY,
                value TEXT
            );
        """)

        # Default settings
        defaults = {
            "support_username":        "",
            "payment_card":            "",
            "payment_bank":            "",
            "payment_owner":           "",
            "payment_card_status":     "public",  # public | safe
            "crypto_tron":             "",
            "crypto_ton":              "",
            "crypto_usdt_bep20":       "",
            "crypto_usdc_bep20":       "",
            "crypto_ltc":              "",
            "channel_lock_enabled":    "0",
            "channel_lock_id":         "",
            "auto_backup_enabled":     "0",
            "backup_interval_hours":   "24",
            "backup_chat_id":          "",
        }
        for k, v in defaults.items():
            conn.execute("INSERT OR IGNORE INTO settings(key, value) VALUES(?,?)", (k, v))

        # Safe schema migrations
        _migrate(conn, "ALTER TABLE users ADD COLUMN first_start_notified INTEGER NOT NULL DEFAULT 0")
        _migrate(conn, "ALTER TABLE users ADD COLUMN is_safe INTEGER NOT NULL DEFAULT 1")
        _migrate(conn, "ALTER TABLE users ADD COLUMN is_reseller INTEGER NOT NULL DEFAULT 0")
        _migrate(conn, "ALTER TABLE configs ADD COLUMN ended INTEGER NOT NULL DEFAULT 0")
        _migrate(conn, "ALTER TABLE payments ADD COLUMN crypto_currency TEXT")


def _migrate(conn, sql: str):
    try:
        conn.execute(sql)
    except Exception:
        pass


# ── Settings ──
def setting_get(key: str, default: str = "") -> str:
    with get_conn() as conn:
        row = conn.execute("SELECT value FROM settings WHERE key=?", (key,)).fetchone()
    return row["value"] if row else default


def setting_set(key: str, value: str):
    with get_conn() as conn:
        conn.execute(
            "INSERT INTO settings(key,value) VALUES(?,?) ON CONFLICT(key) DO UPDATE SET value=excluded.value",
            (key, value),
        )


# ── Users ──
def ensure_user(tg_user) -> bool:
    uid = tg_user.id
    full_name = display_name(tg_user)
    username = tg_user.username or ""
    with get_conn() as conn:
        row = conn.execute("SELECT user_id FROM users WHERE user_id=?", (uid,)).fetchone()
        if row:
            conn.execute(
                "UPDATE users SET full_name=?, username=?, last_seen_at=? WHERE user_id=?",
                (full_name, username, now_str(), uid),
            )
            return False
        conn.execute(
            "INSERT INTO users(user_id,full_name,username,joined_at,last_seen_at,first_start_notified,is_safe,is_reseller) VALUES(?,?,?,?,?,0,1,0)",
            (uid, full_name, username, now_str(), now_str()),
        )
        return True


def get_user(user_id: int):
    with get_conn() as conn:
        return conn.execute("SELECT * FROM users WHERE user_id=?", (user_id,)).fetchone()


def get_user_detail(user_id: int):
    with get_conn() as conn:
        return conn.execute("""
            SELECT u.*,
                   (SELECT COUNT(*) FROM purchases p WHERE p.user_id=u.user_id) AS purchase_count,
                   (SELECT COALESCE(SUM(amount),0) FROM purchases p WHERE p.user_id=u.user_id) AS total_spent
            FROM users u WHERE u.user_id=?
        """, (user_id,)).fetchone()


def get_users(page: int = 0):
    offset = page * USERS_PER_PAGE
    with get_conn() as conn:
        rows = conn.execute("""
            SELECT u.*,
                   (SELECT COUNT(*) FROM purchases p WHERE p.user_id=u.user_id) AS purchase_count
            FROM users u ORDER BY u.user_id DESC LIMIT ? OFFSET ?
        """, (USERS_PER_PAGE, offset)).fetchall()
        total = conn.execute("SELECT COUNT(*) AS c FROM users").fetchone()["c"]
    return rows, total


def get_users_for_broadcast(has_purchase: bool | None = None):
    q = "SELECT user_id FROM users WHERE 1=1"
    if has_purchase is True:
        q += " AND EXISTS (SELECT 1 FROM purchases p WHERE p.user_id=users.user_id)"
    elif has_purchase is False:
        q += " AND NOT EXISTS (SELECT 1 FROM purchases p WHERE p.user_id=users.user_id)"
    with get_conn() as conn:
        return conn.execute(q).fetchall()


def update_balance(user_id: int, delta: int):
    with get_conn() as conn:
        conn.execute("UPDATE users SET balance=balance+? WHERE user_id=?", (delta, user_id))


def set_user_safe(user_id: int, is_safe: int):
    with get_conn() as conn:
        conn.execute("UPDATE users SET is_safe=? WHERE user_id=?", (is_safe, user_id))


def set_user_reseller(user_id: int, is_reseller: int):
    with get_conn() as conn:
        conn.execute("UPDATE users SET is_reseller=? WHERE user_id=?", (is_reseller, user_id))


def notify_first_start_if_needed(tg_user):
    uid = tg_user.id
    with get_conn() as conn:
        row = conn.execute("SELECT first_start_notified FROM users WHERE user_id=?", (uid,)).fetchone()
        if not row or row["first_start_notified"]:
            return
        conn.execute("UPDATE users SET first_start_notified=1 WHERE user_id=?", (uid,))
    text = (
        "📢 | یه گل جدید عضو ربات شد :\n\n"
        f"نام: {display_name(tg_user)}\n"
        f"نام کاربری: {display_username(tg_user.username)}\n"
        f"آیدی عددی: <code>{tg_user.id}</code>"
    )
    for aid in ADMIN_IDS:
        try:
            bot.send_message(aid, text)
        except Exception:
            pass


# ── Config Types ──
def get_all_types():
    with get_conn() as conn:
        return conn.execute("SELECT * FROM config_types ORDER BY id DESC").fetchall()


def get_type(type_id: int):
    with get_conn() as conn:
        return conn.execute("SELECT * FROM config_types WHERE id=?", (type_id,)).fetchone()


def add_type(name: str):
    with get_conn() as conn:
        conn.execute("INSERT INTO config_types(name) VALUES(?)", (name.strip(),))


def update_type(type_id: int, new_name: str):
    with get_conn() as conn:
        conn.execute("UPDATE config_types SET name=? WHERE id=?", (new_name.strip(), type_id))


def delete_type(type_id: int):
    with get_conn() as conn:
        conn.execute("DELETE FROM config_types WHERE id=?", (type_id,))


# ── Packages ──
def _package_base_query():
    return """
        SELECT p.*, t.name AS type_name,
            (SELECT COUNT(*) FROM configs c WHERE c.package_id=p.id AND c.sold_to IS NULL AND c.reserved_payment_id IS NULL) AS stock,
            (SELECT COUNT(*) FROM configs c WHERE c.package_id=p.id AND c.sold_to IS NOT NULL) AS sold_count
        FROM packages p
        JOIN config_types t ON t.id=p.type_id
    """


def get_packages(type_id=None, price_only=None, include_inactive=False):
    q = _package_base_query() + " WHERE 1=1"
    params = []
    if not include_inactive:
        q += " AND p.active=1"
    if type_id is not None:
        q += " AND p.type_id=?"
        params.append(type_id)
    if price_only is not None:
        q += " AND p.price=?"
        params.append(price_only)
    q += " ORDER BY p.id DESC"
    with get_conn() as conn:
        return conn.execute(q, params).fetchall()


def get_package(package_id: int):
    with get_conn() as conn:
        return conn.execute(
            _package_base_query() + " WHERE p.id=?", (package_id,)
        ).fetchone()


def add_package(type_id: int, name: str, volume_gb: int, duration_days: int, price: int):
    with get_conn() as conn:
        conn.execute(
            "INSERT INTO packages(type_id,name,volume_gb,duration_days,price,active) VALUES(?,?,?,?,?,1)",
            (type_id, name.strip(), volume_gb, duration_days, price),
        )


def update_package_field(pkg_id: int, field: str, value):
    allowed = {"name", "volume_gb", "duration_days", "price"}
    if field not in allowed:
        return
    with get_conn() as conn:
        conn.execute(f"UPDATE packages SET {field}=? WHERE id=?", (value, pkg_id))


def delete_package(pkg_id: int):
    with get_conn() as conn:
        conn.execute("DELETE FROM packages WHERE id=?", (pkg_id,))


# ── Configs ──
def add_config(type_id: int, package_id: int, service_name: str, config_text: str, inquiry_link: str):
    with get_conn() as conn:
        conn.execute(
            "INSERT INTO configs(type_id,package_id,service_name,config_text,inquiry_link,created_at) VALUES(?,?,?,?,?,?)",
            (type_id, package_id, service_name.strip(), config_text.strip(), inquiry_link.strip(), now_str()),
        )


def get_available_configs_for_package(package_id: int, page: int = 0, page_size: int = CONFIGS_PER_PAGE):
    offset = page * page_size
    with get_conn() as conn:
        rows = conn.execute(
            "SELECT * FROM configs WHERE package_id=? AND sold_to IS NULL AND reserved_payment_id IS NULL ORDER BY id ASC LIMIT ? OFFSET ?",
            (package_id, page_size, offset),
        ).fetchall()
        total = conn.execute(
            "SELECT COUNT(*) AS c FROM configs WHERE package_id=? AND sold_to IS NULL AND reserved_payment_id IS NULL",
            (package_id,),
        ).fetchone()["c"]
    return rows, total


def get_sold_configs_for_package(package_id: int, page: int = 0, page_size: int = CONFIGS_PER_PAGE):
    offset = page * page_size
    with get_conn() as conn:
        rows = conn.execute("""
            SELECT c.*, u.full_name AS buyer_name, u.username AS buyer_username
            FROM configs c
            LEFT JOIN users u ON u.user_id=c.sold_to
            WHERE c.package_id=? AND c.sold_to IS NOT NULL
            ORDER BY c.sold_at DESC LIMIT ? OFFSET ?
        """, (package_id, page_size, offset)).fetchall()
        total = conn.execute(
            "SELECT COUNT(*) AS c FROM configs WHERE package_id=? AND sold_to IS NOT NULL",
            (package_id,),
        ).fetchone()["c"]
    return rows, total


def get_all_available_configs(page: int = 0, page_size: int = CONFIGS_PER_PAGE):
    offset = page * page_size
    with get_conn() as conn:
        rows = conn.execute("""
            SELECT c.*, p.name AS pkg_name, t.name AS type_name
            FROM configs c
            JOIN packages p ON p.id=c.package_id
            JOIN config_types t ON t.id=c.type_id
            WHERE c.sold_to IS NULL AND c.reserved_payment_id IS NULL
            ORDER BY c.id ASC LIMIT ? OFFSET ?
        """, (page_size, offset)).fetchall()
        total = conn.execute(
            "SELECT COUNT(*) AS c FROM configs WHERE sold_to IS NULL AND reserved_payment_id IS NULL"
        ).fetchone()["c"]
    return rows, total


def get_all_sold_configs(page: int = 0, page_size: int = CONFIGS_PER_PAGE):
    offset = page * page_size
    with get_conn() as conn:
        rows = conn.execute("""
            SELECT c.*, p.name AS pkg_name, t.name AS type_name,
                   u.full_name AS buyer_name, u.username AS buyer_username
            FROM configs c
            JOIN packages p ON p.id=c.package_id
            JOIN config_types t ON t.id=c.type_id
            LEFT JOIN users u ON u.user_id=c.sold_to
            WHERE c.sold_to IS NOT NULL
            ORDER BY c.sold_at DESC LIMIT ? OFFSET ?
        """, (page_size, offset)).fetchall()
        total = conn.execute(
            "SELECT COUNT(*) AS c FROM configs WHERE sold_to IS NOT NULL"
        ).fetchone()["c"]
    return rows, total


def get_config(config_id: int):
    with get_conn() as conn:
        return conn.execute("SELECT * FROM configs WHERE id=?", (config_id,)).fetchone()


def reserve_first_config(package_id: int, payment_id: int | None = None):
    with get_conn() as conn:
        row = conn.execute(
            "SELECT id FROM configs WHERE package_id=? AND sold_to IS NULL AND reserved_payment_id IS NULL ORDER BY id ASC LIMIT 1",
            (package_id,),
        ).fetchone()
        if not row:
            return None
        if payment_id:
            conn.execute("UPDATE configs SET reserved_payment_id=? WHERE id=?", (payment_id, row["id"]))
        return row["id"]


def release_reserved_config(config_id: int):
    with get_conn() as conn:
        conn.execute("UPDATE configs SET reserved_payment_id=NULL WHERE id=?", (config_id,))


def mark_config_ended(config_id: int, ended: int):
    with get_conn() as conn:
        conn.execute("UPDATE configs SET ended=? WHERE id=?", (ended, config_id))


def assign_config_to_user(config_id: int, user_id: int, package_id: int, amount: int, payment_method: str, is_test: int = 0) -> int:
    with get_conn() as conn:
        conn.execute(
            "INSERT INTO purchases(user_id,package_id,config_id,amount,payment_method,created_at,is_test) VALUES(?,?,?,?,?,?,?)",
            (user_id, package_id, config_id, amount, payment_method, now_str(), is_test),
        )
        purchase_id = conn.execute("SELECT last_insert_rowid() AS x").fetchone()["x"]
        conn.execute(
            "UPDATE configs SET sold_to=?, purchase_id=?, sold_at=?, reserved_payment_id=NULL WHERE id=?",
            (user_id, purchase_id, now_str(), config_id),
        )
        return purchase_id


# ── Purchases ──
def get_purchase(purchase_id: int):
    with get_conn() as conn:
        return conn.execute("""
            SELECT pr.*, p.name AS package_name, p.volume_gb, p.duration_days, p.price,
                   t.name AS type_name, c.service_name, c.config_text, c.inquiry_link, c.ended
            FROM purchases pr
            JOIN packages p ON p.id=pr.package_id
            JOIN config_types t ON t.id=p.type_id
            JOIN configs c ON c.id=pr.config_id
            WHERE pr.id=?
        """, (purchase_id,)).fetchone()


def get_user_purchases(user_id: int):
    with get_conn() as conn:
        return conn.execute("""
            SELECT pr.*, p.name AS package_name, p.volume_gb, p.duration_days, p.price,
                   t.name AS type_name, c.service_name, c.config_text, c.inquiry_link, c.ended
            FROM purchases pr
            JOIN packages p ON p.id=pr.package_id
            JOIN config_types t ON t.id=p.type_id
            JOIN configs c ON c.id=pr.config_id
            WHERE pr.user_id=?
            ORDER BY pr.id DESC
        """, (user_id,)).fetchall()


def user_has_test_for_type(user_id: int, type_id: int) -> bool:
    with get_conn() as conn:
        row = conn.execute("""
            SELECT 1 FROM purchases pr
            JOIN packages p ON p.id=pr.package_id
            WHERE pr.user_id=? AND pr.is_test=1 AND p.type_id=? LIMIT 1
        """, (user_id, type_id)).fetchone()
    return bool(row)


# ── Payments ──
def create_payment(kind: str, user_id: int, package_id: int | None, amount: int,
                   payment_method: str, status: str = "pending",
                   config_id: int | None = None, crypto_currency: str | None = None) -> int:
    with get_conn() as conn:
        conn.execute("""
            INSERT INTO payments(kind,user_id,package_id,amount,payment_method,status,created_at,config_id,crypto_currency)
            VALUES(?,?,?,?,?,?,?,?,?)
        """, (kind, user_id, package_id, amount, payment_method, status, now_str(), config_id, crypto_currency))
        return conn.execute("SELECT last_insert_rowid() AS x").fetchone()["x"]


def get_payment(payment_id: int):
    with get_conn() as conn:
        return conn.execute("SELECT * FROM payments WHERE id=?", (payment_id,)).fetchone()


def update_payment_receipt(payment_id: int, file_id: str | None, text_value: str | None):
    with get_conn() as conn:
        conn.execute(
            "UPDATE payments SET receipt_file_id=?, receipt_text=? WHERE id=?",
            (file_id, text_value, payment_id),
        )


def approve_payment(payment_id: int, admin_note: str):
    with get_conn() as conn:
        conn.execute(
            "UPDATE payments SET status='approved', admin_note=?, approved_at=? WHERE id=?",
            (admin_note, now_str(), payment_id),
        )


def reject_payment(payment_id: int, admin_note: str):
    with get_conn() as conn:
        conn.execute(
            "UPDATE payments SET status='rejected', admin_note=?, approved_at=? WHERE id=?",
            (admin_note, now_str(), payment_id),
        )


def complete_payment(payment_id: int):
    with get_conn() as conn:
        conn.execute("UPDATE payments SET status='completed', approved_at=? WHERE id=?", (now_str(), payment_id))


# ── Reseller Prices ──
def get_reseller_price(user_id: int, package_id: int) -> int | None:
    with get_conn() as conn:
        row = conn.execute(
            "SELECT price FROM reseller_prices WHERE user_id=? AND package_id=?",
            (user_id, package_id),
        ).fetchone()
    return row["price"] if row else None


def set_reseller_price(user_id: int, package_id: int, price: int):
    with get_conn() as conn:
        conn.execute(
            "INSERT INTO reseller_prices(user_id,package_id,price) VALUES(?,?,?) ON CONFLICT(user_id,package_id) DO UPDATE SET price=excluded.price",
            (user_id, package_id, price),
        )


def get_effective_price(user_id: int, package_id: int, base_price: int) -> int:
    rp = get_reseller_price(user_id, package_id)
    return rp if rp is not None else base_price


# ──────────────────────────────────────────────
# Channel Lock
# ──────────────────────────────────────────────
def check_channel_membership(user_id: int) -> bool:
    if is_admin(user_id):
        return True
    if setting_get("channel_lock_enabled", "0") != "1":
        return True
    channel_id = setting_get("channel_lock_id", "").strip()
    if not channel_id:
        return True
    try:
        member = bot.get_chat_member(channel_id, user_id)
        return member.status in ("member", "creator", "administrator")
    except Exception:
        return True  # fail-open


def channel_lock_wall(target, user_id: int) -> bool:
    """Returns True if user passes (can proceed). Sends wall message if not."""
    if check_channel_membership(user_id):
        return True
    channel_id = setting_get("channel_lock_id", "").strip()
    kb = types.InlineKeyboardMarkup()
    if channel_id:
        join_url = f"https://t.me/{channel_id.lstrip('@')}"
        kb.add(types.InlineKeyboardButton("📢 عضویت در کانال", url=join_url))
    kb.add(types.InlineKeyboardButton("✅ بررسی عضویت", callback_data="check_membership"))
    send_or_edit(
        target,
        f"⚠️ <b>برای استفاده از ربات باید در کانال ما عضو باشید.</b>\n\n"
        f"پس از عضویت، روی «بررسی عضویت» بزنید.",
        kb,
    )
    return False


# ──────────────────────────────────────────────
# Telegram UI Helpers
# ──────────────────────────────────────────────
def send_or_edit(call_or_msg, text, reply_markup=None, disable_preview=True):
    try:
        if hasattr(call_or_msg, "message"):
            bot.edit_message_text(
                text,
                call_or_msg.message.chat.id,
                call_or_msg.message.message_id,
                reply_markup=reply_markup,
                disable_web_page_preview=disable_preview,
            )
        else:
            bot.send_message(call_or_msg.chat.id, text, reply_markup=reply_markup, disable_web_page_preview=disable_preview)
    except Exception:
        chat_id = call_or_msg.message.chat.id if hasattr(call_or_msg, "message") else call_or_msg.chat.id
        try:
            bot.send_message(chat_id, text, reply_markup=reply_markup, disable_web_page_preview=disable_preview)
        except Exception:
            pass


def set_bot_commands():
    try:
        bot.set_my_commands([types.BotCommand("start", "شروع ربات")])
    except Exception:
        pass


# ──────────────────────────────────────────────
# Keyboards
# ──────────────────────────────────────────────
def kb_main(user_id: int) -> types.InlineKeyboardMarkup:
    user = get_user(user_id)
    is_reseller = user and user["is_reseller"]
    kb = types.InlineKeyboardMarkup(row_width=2)
    kb.row(
        types.InlineKeyboardButton("🛒 خرید کانفیگ جدید", callback_data="buy:start"),
        types.InlineKeyboardButton("📦 کانفیگ‌های من",    callback_data="my_configs"),
    )
    kb.add(types.InlineKeyboardButton("🎁 تست رایگان", callback_data="test:start"))
    kb.row(
        types.InlineKeyboardButton("👤 حساب کاربری",  callback_data="profile"),
        types.InlineKeyboardButton("💳 شارژ کیف پول", callback_data="wallet:charge"),
    )
    kb.add(types.InlineKeyboardButton("🎧 ارتباط با پشتیبانی", callback_data="support"))
    if is_reseller:
        kb.add(types.InlineKeyboardButton("🤝 پنل نمایندگی", callback_data="reseller:panel"))
    if is_admin(user_id):
        kb.add(types.InlineKeyboardButton("⚙️ ورود به پنل مدیریت", callback_data="admin:panel"))
    return kb


def kb_admin_panel() -> types.InlineKeyboardMarkup:
    kb = types.InlineKeyboardMarkup(row_width=2)
    kb.row(
        types.InlineKeyboardButton("🧩 مدیریت نوع‌ها",    callback_data="admin:types"),
        types.InlineKeyboardButton("📦 مدیریت پکیج",      callback_data="admin:pkgs"),
    )
    kb.row(
        types.InlineKeyboardButton("📝 ثبت کانفیگ",       callback_data="admin:add_config"),
        types.InlineKeyboardButton("📊 مدیریت کانفیگ‌ها", callback_data="admin:configs"),
    )
    kb.row(
        types.InlineKeyboardButton("👥 مدیریت کاربران",   callback_data="admin:users"),
        types.InlineKeyboardButton("📣 فوروارد همگانی",   callback_data="admin:broadcast"),
    )
    kb.row(
        types.InlineKeyboardButton("⚙️ تنظیمات",          callback_data="admin:settings"),
        types.InlineKeyboardButton("💾 پشتیبان‌گیری",      callback_data="admin:backup"),
    )
    kb.add(types.InlineKeyboardButton("📢 قفل کانال", callback_data="admin:channel"))
    kb.add(types.InlineKeyboardButton("🔙 بازگشت",    callback_data="nav:main"))
    return kb


def kb_payment_method(context_type: str, context_id) -> types.InlineKeyboardMarkup:
    """context_type: 'wallet' | 'buy' ; context_id: amount (wallet) | package_id (buy)"""
    kb = types.InlineKeyboardMarkup(row_width=2)
    if context_type == "wallet":
        kb.row(
            types.InlineKeyboardButton("💳 کارت به کارت",  callback_data=f"wallet:charge:card"),
            types.InlineKeyboardButton("💰 ارز دیجیتال",  callback_data=f"wallet:charge:crypto"),
        )
        kb.add(types.InlineKeyboardButton("🔙 بازگشت", callback_data="wallet:charge"))
    else:  # buy
        kb.add(types.InlineKeyboardButton("💰 پرداخت از موجودی", callback_data=f"pay:wallet:{context_id}"))
        kb.row(
            types.InlineKeyboardButton("💳 کارت به کارت", callback_data=f"pay:card:{context_id}"),
            types.InlineKeyboardButton("💰 ارز دیجیتال",  callback_data=f"pay:crypto:{context_id}"),
        )
        kb.add(types.InlineKeyboardButton("🔙 بازگشت", callback_data=f"buy:type:back:{context_id}"))
    return kb


# ──────────────────────────────────────────────
# Display / Content Helpers
# ──────────────────────────────────────────────
def show_main_menu(target):
    uid = target.from_user.id if hasattr(target, "from_user") else target.chat.id
    text = (
        f"🌟 <b>به {BRAND_TITLE} خوش آمدید</b>\n\n"
        "لطفاً از منوی زیر، بخش مورد نظر خود را انتخاب کنید."
    )
    send_or_edit(target, text, kb_main(uid))


def show_profile(target, user_id: int):
    user = get_user(user_id)
    if not user:
        return
    text = (
        "👤 <b>پروفایل کاربری</b>\n\n"
        f"📱 نام: {esc(user['full_name'])}\n"
        f"🆔 نام کاربری: {esc(display_username(user['username']))}\n"
        f"🔢 آیدی عددی: <code>{user['user_id']}</code>\n\n"
        f"💰 موجودی: <b>{fmt_price(user['balance'])}</b> تومان"
    )
    kb = types.InlineKeyboardMarkup()
    kb.row(
        types.InlineKeyboardButton("💳 شارژ کیف پول", callback_data="wallet:charge"),
        types.InlineKeyboardButton("📦 کانفیگ‌های من", callback_data="my_configs"),
    )
    kb.add(types.InlineKeyboardButton("🔙 بازگشت", callback_data="nav:main"))
    send_or_edit(target, text, kb)


def show_support(target):
    support_raw = setting_get("support_username", DEFAULT_ADMIN_HANDLE)
    support_url = safe_support_url(support_raw)
    kb = types.InlineKeyboardMarkup()
    if support_url:
        kb.add(types.InlineKeyboardButton("💬 ورود به گفت‌وگوی پشتیبانی", url=support_url))
    kb.add(types.InlineKeyboardButton("🔙 بازگشت", callback_data="nav:main"))
    send_or_edit(target, "🎧 <b>ارتباط با پشتیبانی</b>\n\nبرای گفت‌وگو با پشتیبانی، روی دکمه زیر بزنید.", kb)


def show_my_configs(target, user_id: int):
    items = get_user_purchases(user_id)
    if not items:
        send_or_edit(target, "📭 هنوز کانفیگی برای حساب شما ثبت نشده است.", back_button("main"))
        return
    kb = types.InlineKeyboardMarkup()
    for item in items:
        ended_mark = " 🔴" if item["ended"] else ""
        title = f"{item['service_name']}{ended_mark}"
        kb.add(types.InlineKeyboardButton(title, callback_data=f"mycfg:{item['id']}"))
    kb.add(types.InlineKeyboardButton("🔙 بازگشت", callback_data="nav:main"))
    send_or_edit(target, "📦 <b>کانفیگ‌های من</b>\n\nیکی از سرویس‌ها را انتخاب کنید:", kb)


def deliver_purchase_message(chat_id: int, purchase_id: int):
    item = get_purchase(purchase_id)
    if not item:
        bot.send_message(chat_id, "❌ اطلاعات خرید یافت نشد.")
        return
    cfg = item["config_text"]
    text = (
        f"✅ <b>{'تست رایگان' if item['is_test'] else 'سرویس شما آماده است'}</b>\n\n"
        f"🔮 نام سرویس: <b>{esc(item['service_name'])}</b>\n"
        f"🧩 نوع سرویس: <b>{esc(item['type_name'])}</b>\n"
        f"🔋 حجم سرویس: <b>{item['volume_gb']}</b> گیگ\n"
        f"⏰ مدت سرویس: <b>{item['duration_days']}</b> روز\n\n"
        f"💝 <b>Config :</b>\n<code>{esc(cfg)}</code>\n\n"
        f"🔋 Volume web: {esc(item['inquiry_link'] or '-')}"
    )
    qr_img = qrcode.make(cfg)
    bio = io.BytesIO()
    qr_img.save(bio, format="PNG")
    bio.seek(0)
    bio.name = "qrcode.png"
    kb = types.InlineKeyboardMarkup()
    support_url = safe_support_url(setting_get("support_username", DEFAULT_ADMIN_HANDLE))
    if support_url:
        kb.add(types.InlineKeyboardButton("♻️ تمدید / پشتیبانی", url=support_url))
    kb.add(types.InlineKeyboardButton("🔙 بازگشت", callback_data="nav:main"))
    try:
        bot.send_photo(chat_id, bio, caption=text, reply_markup=kb)
    except Exception:
        bot.send_message(chat_id, text, reply_markup=kb)


def admin_purchase_notify(payment_method_label: str, user_row, package_row):
    text = (
        f"❗️|💳 خرید جدید ({payment_method_label})\n\n"
        f"▫️آیدی کاربر: <code>{user_row['user_id']}</code>\n"
        f"👨‍💼اسم کاربر: {esc(user_row['full_name'])}\n"
        f"⚡️ نام کاربری: {esc(user_row['username'] or 'ندارد')}\n"
        f"💰مبلغ پرداختی: {fmt_price(package_row['price'])} تومان\n"
        f"🚦سرور: {esc(package_row['type_name'])}\n"
        f"✏️ نام سرویس: {esc(package_row['name'])}\n"
        f"🔋حجم سرویس: {package_row['volume_gb']} گیگ\n"
        f"⏰ مدت سرویس: {package_row['duration_days']} روز"
    )
    for aid in ADMIN_IDS:
        try:
            bot.send_message(aid, text)
        except Exception:
            pass


def send_payment_to_admins(payment_id: int):
    payment = get_payment(payment_id)
    if not payment:
        return
    user = get_user(payment["user_id"])
    package_row = get_package(payment["package_id"]) if payment["package_id"] else None
    kind_label = "شارژ کیف پول" if payment["kind"] == "wallet_charge" else "خرید کانفیگ"
    method_label = payment["payment_method"]
    if method_label == "card":
        method_label = "💳 کارت به کارت"
    elif method_label == "crypto":
        method_label = f"💰 ارز دیجیتال ({payment.get('crypto_currency') or ''})"
    pkg_text = ""
    if package_row:
        pkg_text = (
            f"\n🧩 نوع: {esc(package_row['type_name'])}"
            f"\n📦 پکیج: {esc(package_row['name'])}"
            f"\n🔋 حجم: {package_row['volume_gb']} گیگ"
            f"\n⏰ مدت: {package_row['duration_days']} روز"
        )
    text = (
        f"📥 <b>درخواست جدید برای بررسی</b>\n\n"
        f"🧾 نوع: {kind_label} | {method_label}\n"
        f"👤 کاربر: {esc(user['full_name'])}\n"
        f"🆔 نام کاربری: {esc(display_username(user['username']))}\n"
        f"🔢 آیدی: <code>{user['user_id']}</code>\n"
        f"💰 مبلغ: <b>{fmt_price(payment['amount'])}</b> تومان"
        f"{pkg_text}\n\n"
        f"📝 توضیح کاربر:\n{esc(payment['receipt_text'] or '-')}"
    )
    kb = types.InlineKeyboardMarkup()
    kb.row(
        types.InlineKeyboardButton("✅ تأیید", callback_data=f"admin:pay:approve:{payment_id}"),
        types.InlineKeyboardButton("❌ رد",   callback_data=f"admin:pay:reject:{payment_id}"),
    )
    for aid in ADMIN_IDS:
        try:
            if payment["receipt_file_id"]:
                bot.send_photo(aid, payment["receipt_file_id"], caption=text, reply_markup=kb)
            else:
                bot.send_message(aid, text, reply_markup=kb)
        except Exception:
            pass


def finish_card_payment_approval(payment_id: int, admin_note: str, approved: bool) -> bool:
    payment = get_payment(payment_id)
    if not payment or payment["status"] not in ("pending", "approved", "rejected"):
        return False
    user_id = payment["user_id"]
    if approved:
        approve_payment(payment_id, admin_note)
        if payment["kind"] == "wallet_charge":
            update_balance(user_id, payment["amount"])
            complete_payment(payment_id)
            bot.send_message(user_id, f"✅ واریزی شما تأیید شد.\n\n{esc(admin_note)}")
        elif payment["kind"] == "config_purchase":
            config_id  = payment["config_id"]
            package_id = payment["package_id"]
            package_row = get_package(package_id)
            if not config_id:
                config_id = reserve_first_config(package_id, payment_id)
            if not config_id:
                bot.send_message(user_id, "❌ پرداخت تأیید شد ولی موجودی کانفیگ تمام شده. لطفاً با پشتیبانی تماس بگیرید.")
                return False
            purchase_id = assign_config_to_user(config_id, user_id, package_id, payment["amount"], "card")
            complete_payment(payment_id)
            bot.send_message(user_id, f"✅ واریزی شما تأیید شد.\n\n{esc(admin_note)}")
            deliver_purchase_message(user_id, purchase_id)
            admin_purchase_notify("کارت به کارت", get_user(user_id), package_row)
        return True
    else:
        reject_payment(payment_id, admin_note)
        if payment["config_id"]:
            release_reserved_config(payment["config_id"])
        bot.send_message(user_id, f"❌ رسید شما رد شد.\n\n{esc(admin_note)}")
        return True


# ──────────────────────────────────────────────
# Admin helpers – show lists
# ──────────────────────────────────────────────
def show_admin_types_again(call):
    kb = types.InlineKeyboardMarkup()
    kb.add(types.InlineKeyboardButton("➕ افزودن نوع جدید", callback_data="admin:type:add"))
    for item in get_all_types():
        kb.row(
            types.InlineKeyboardButton(f"🧩 {item['name']}", callback_data="noop"),
            types.InlineKeyboardButton("✏️", callback_data=f"admin:type:edit:{item['id']}"),
            types.InlineKeyboardButton("🗑",  callback_data=f"admin:type:del:{item['id']}"),
        )
    kb.add(types.InlineKeyboardButton("🔙 بازگشت", callback_data="admin:panel"))
    send_or_edit(call, "🧩 <b>مدیریت نوع‌ها</b>", kb)


def show_admin_packages(call, type_id: int | None = None):
    """Package management main view."""
    kb = types.InlineKeyboardMarkup()
    kb.add(types.InlineKeyboardButton("➕ افزودن پکیج جدید", callback_data="admin:add_package"))
    all_pkgs = get_packages(type_id=type_id, include_inactive=True)
    if all_pkgs:
        for p in all_pkgs:
            kb.row(
                types.InlineKeyboardButton(
                    f"📦 {p['name']} | {p['volume_gb']}GB/{p['duration_days']}روز | {fmt_price(p['price'])} ت | 🟢{p['stock']}",
                    callback_data="noop",
                ),
            )
            kb.row(
                types.InlineKeyboardButton("✏️ ویرایش", callback_data=f"admin:pkg:edit:{p['id']}"),
                types.InlineKeyboardButton("🗑 حذف",   callback_data=f"admin:pkg:del:{p['id']}"),
            )
    else:
        pass
    kb.add(types.InlineKeyboardButton("🔙 بازگشت", callback_data="admin:panel"))
    send_or_edit(call, "📦 <b>مدیریت پکیج</b>\n\nلیست تمامی پکیج‌ها:", kb)


def show_admin_configs(call):
    """Main config management view — two-column global counts + package list."""
    with get_conn() as conn:
        total_avail = conn.execute(
            "SELECT COUNT(*) AS c FROM configs WHERE sold_to IS NULL AND reserved_payment_id IS NULL"
        ).fetchone()["c"]
        total_sold = conn.execute(
            "SELECT COUNT(*) AS c FROM configs WHERE sold_to IS NOT NULL"
        ).fetchone()["c"]

    kb = types.InlineKeyboardMarkup()
    kb.row(
        types.InlineKeyboardButton(f"🟢 مانده ({total_avail})",      callback_data="admin:cfglist:all:a:0"),
        types.InlineKeyboardButton(f"🔴 فروخته شده ({total_sold})",  callback_data="admin:cfglist:all:s:0"),
    )

    # Package list below
    pkgs = get_packages(include_inactive=True)
    for p in pkgs:
        avail = p["stock"]
        sold  = p["sold_count"]
        kb.add(types.InlineKeyboardButton(
            f"📦 {p['name']} | 🟢{avail} مانده | 🔴{sold} فروخته",
            callback_data=f"admin:cfglist:pkg:{p['id']}:menu",
        ))

    kb.add(types.InlineKeyboardButton("🔙 بازگشت", callback_data="admin:panel"))
    send_or_edit(call, "📊 <b>مدیریت کانفیگ‌ها</b>\n\nانتخاب کنید:", kb)


def show_configs_page(call, scope: str, kind: str, page: int, pkg_id: int | None = None):
    """scope: 'all'|'pkg'  kind: 'a'(available)|'s'(sold)  page: int"""
    page_size = CONFIGS_PER_PAGE
    if scope == "all":
        if kind == "a":
            rows, total = get_all_available_configs(page, page_size)
        else:
            rows, total = get_all_sold_configs(page, page_size)
    else:
        if kind == "a":
            rows, total = get_available_configs_for_package(pkg_id, page, page_size)
        else:
            rows, total = get_sold_configs_for_package(pkg_id, page, page_size)

    total_pages = max(1, (total + page_size - 1) // page_size)
    kb = types.InlineKeyboardMarkup()
    for r in rows:
        if kind == "s" and "buyer_name" in r.keys():
            label = f"{'🔴 ' if r['ended'] else ''}{r['service_name']} ← {r['buyer_name'] or r['sold_to']}"
        else:
            label = f"{'🔴 ' if r.get('ended') else '🟢 '}{r['service_name']}"
        base = f"admin:cfglist:{scope}:{kind}"
        suffix = f":{pkg_id}" if scope == "pkg" else ""
        kb.add(types.InlineKeyboardButton(label, callback_data=f"admin:cfg:view:{r['id']}:{scope}:{kind}:{page}{suffix}"))

    # Pagination
    nav_row = []
    back_cb = f"admin:cfglist:{scope}:{kind}:{page-1}{(':' + str(pkg_id)) if scope=='pkg' else ''}"
    next_cb = f"admin:cfglist:{scope}:{kind}:{page+1}{(':' + str(pkg_id)) if scope=='pkg' else ''}"
    if page > 0:
        nav_row.append(types.InlineKeyboardButton("◀ قبلی", callback_data=back_cb))
    nav_row.append(types.InlineKeyboardButton(f"{page+1}/{total_pages}", callback_data="noop"))
    if page + 1 < total_pages:
        nav_row.append(types.InlineKeyboardButton("بعدی ▶", callback_data=next_cb))
    if nav_row:
        kb.row(*nav_row)

    back_target = f"admin:cfglist:pkg:{pkg_id}:menu" if scope == "pkg" else "admin:configs"
    kb.add(types.InlineKeyboardButton("🔙 بازگشت", callback_data=back_target))

    kind_label = "🟢 مانده" if kind == "a" else "🔴 فروخته شده"
    send_or_edit(call, f"📋 <b>کانفیگ‌های {kind_label}</b>\n\nتعداد کل: {total} | صفحه {page+1}/{total_pages}", kb)


def show_admin_user_detail(call, user_id: int):
    row = get_user_detail(user_id)
    if not row:
        bot.answer_callback_query(call.id, "کاربر یافت نشد.", show_alert=True)
        return
    safe_label   = "🟢 امن"  if row["is_safe"]    else "🔘 ناامن"
    reseller_lbl = "🤝 نماینده ✅" if row["is_reseller"] else "🤝 نماینده ❌"
    text = (
        "👤 <b>اطلاعات کاربر</b>\n\n"
        f"📱 نام: {esc(row['full_name'])}\n"
        f"🆔 نام کاربری: {esc(display_username(row['username']))}\n"
        f"🔢 آیدی عددی: <code>{row['user_id']}</code>\n"
        f"💰 موجودی: <b>{fmt_price(row['balance'])}</b> تومان\n"
        f"🛍 تعداد خرید: <b>{row['purchase_count']}</b>\n"
        f"💵 مجموع خرید: <b>{fmt_price(row['total_spent'])}</b> تومان\n"
        f"🔒 وضعیت: {safe_label}\n"
        f"🕒 عضویت: {esc(row['joined_at'])}"
    )
    kb = types.InlineKeyboardMarkup()
    # Safe/Unsafe toggle
    if row["is_safe"]:
        kb.add(types.InlineKeyboardButton("🔘 تغییر به ناامن", callback_data=f"admin:user:unsafe:{user_id}"))
    else:
        kb.add(types.InlineKeyboardButton("🟢 تغییر به امن",   callback_data=f"admin:user:safe:{user_id}"))
    # Reseller toggle
    if row["is_reseller"]:
        kb.add(types.InlineKeyboardButton("❌ لغو نمایندگی",  callback_data=f"admin:user:reseller:0:{user_id}"))
        kb.add(types.InlineKeyboardButton("💲 قیمت‌های نمایندگی", callback_data=f"admin:user:rprices:{user_id}"))
    else:
        kb.add(types.InlineKeyboardButton("🤝 فعال‌سازی نمایندگی", callback_data=f"admin:user:reseller:1:{user_id}"))
    kb.row(
        types.InlineKeyboardButton("💰 موجودی",          callback_data=f"admin:user:bal:{user_id}"),
        types.InlineKeyboardButton("📦 کانفیگ‌ها",       callback_data=f"admin:user:cfgs:{user_id}"),
    )
    kb.add(types.InlineKeyboardButton("➕ افزودن کانفیگ", callback_data=f"admin:user:addcfg:{user_id}"))
    kb.add(types.InlineKeyboardButton("🔙 بازگشت",        callback_data="admin:users"))
    send_or_edit(call, text, kb)


def show_admin_user_configs(call, user_id: int):
    purchases = get_user_purchases(user_id)
    if not purchases:
        send_or_edit(call, "📭 این کاربر هیچ کانفیگی ندارد.", back_button(f"admin:user:{user_id}"))
        return
    kb = types.InlineKeyboardMarkup()
    for item in purchases:
        ended = item["ended"]
        label = f"{'🔴 ' if ended else '🟢 '}{item['service_name']}"
        kb.row(
            types.InlineKeyboardButton(label, callback_data="noop"),
            types.InlineKeyboardButton("🔴 پایان" if not ended else "♻️ احیا",
                                       callback_data=f"admin:user:cfg_end:{item['config_id']}:{1 if not ended else 0}:{user_id}"),
        )
    kb.add(types.InlineKeyboardButton("🔙 بازگشت", callback_data=f"admin:user:{user_id}"))
    send_or_edit(call, f"📦 <b>کانفیگ‌های کاربر</b>", kb)


# ──────────────────────────────────────────────
# /start
# ──────────────────────────────────────────────
@bot.message_handler(commands=["start"])
def start_handler(message):
    ensure_user(message.from_user)
    notify_first_start_if_needed(message.from_user)
    state_clear(message.from_user.id)
    show_main_menu(message)


# ──────────────────────────────────────────────
# Callback dispatcher
# ──────────────────────────────────────────────
@bot.callback_query_handler(func=lambda c: True)
def on_callback(call):
    uid  = call.from_user.id
    ensure_user(call.from_user)
    data = call.data or ""

    try:
        # ── Navigation ──
        if data == "nav:main":
            state_clear(uid)
            bot.answer_callback_query(call.id)
            show_main_menu(call)
            return

        if data == "check_membership":
            bot.answer_callback_query(call.id)
            if check_channel_membership(uid):
                show_main_menu(call)
            else:
                bot.answer_callback_query(call.id, "هنوز عضو نشده‌اید!", show_alert=True)
            return

        # ── Channel lock check (non-admin) ──
        if not channel_lock_wall(call, uid):
            bot.answer_callback_query(call.id)
            return

        # ── Profile / support ──
        if data == "profile":
            bot.answer_callback_query(call.id)
            show_profile(call, uid)
            return

        if data == "support":
            bot.answer_callback_query(call.id)
            show_support(call)
            return

        if data == "my_configs":
            bot.answer_callback_query(call.id)
            show_my_configs(call, uid)
            return

        if data.startswith("mycfg:"):
            purchase_id = int(data.split(":")[1])
            item = get_purchase(purchase_id)
            if not item or item["user_id"] != uid:
                bot.answer_callback_query(call.id, "دسترسی مجاز نیست.", show_alert=True)
                return
            bot.answer_callback_query(call.id)
            deliver_purchase_message(call.message.chat.id, purchase_id)
            return

        # ── Buy flow ──
        if data == "buy:start":
            bot.answer_callback_query(call.id)
            _cb_buy_start(call, uid)
            return

        if data.startswith("buy:type:"):
            parts = data.split(":")
            # buy:type:{type_id} or buy:type:back:{package_id} (back from pkg detail)
            if parts[2] == "back":
                package_id = int(parts[3])
                p = get_package(package_id)
                bot.answer_callback_query(call.id)
                _cb_buy_type(call, uid, p["type_id"])
                return
            type_id = int(parts[2])
            bot.answer_callback_query(call.id)
            _cb_buy_type(call, uid, type_id)
            return

        if data.startswith("buy:pkg:"):
            package_id = int(data.split(":")[2])
            bot.answer_callback_query(call.id)
            _cb_buy_pkg(call, uid, package_id)
            return

        # ── Payment methods ──
        if data.startswith("pay:wallet:"):
            package_id = int(data.split(":")[2])
            bot.answer_callback_query(call.id)
            _cb_pay_wallet(call, uid, package_id)
            return

        if data.startswith("pay:card:"):
            package_id = int(data.split(":")[2])
            bot.answer_callback_query(call.id)
            _cb_pay_card(call, uid, package_id)
            return

        if data.startswith("pay:crypto:"):
            package_id = int(data.split(":")[2])
            bot.answer_callback_query(call.id)
            _cb_show_crypto_currencies(call, uid, "buy", package_id)
            return

        if data.startswith("pay:crypto_cur:"):
            # pay:crypto_cur:{currency}:{package_id}
            parts = data.split(":")
            currency = parts[2]
            package_id = int(parts[3])
            bot.answer_callback_query(call.id)
            _cb_pay_crypto_buy(call, uid, currency, package_id)
            return

        # ── Free test ──
        if data == "test:start":
            bot.answer_callback_query(call.id)
            _cb_test_start(call)
            return

        if data.startswith("test:type:"):
            type_id = int(data.split(":")[2])
            bot.answer_callback_query(call.id)
            _cb_test_type(call, uid, type_id)
            return

        # ── Wallet charge ──
        if data == "wallet:charge":
            state_set(uid, "await_wallet_amount")
            bot.answer_callback_query(call.id)
            send_or_edit(call,
                "💳 <b>شارژ کیف پول</b>\n\nلطفاً مبلغ مورد نظر را به تومان وارد کنید:",
                back_button("main"))
            return

        if data == "wallet:charge:card":
            st = state_data(uid)
            amount = st.get("amount")
            if not amount:
                bot.answer_callback_query(call.id, "ابتدا مبلغ را وارد کنید.", show_alert=True)
                return
            bot.answer_callback_query(call.id)
            _cb_wallet_card(call, uid, amount)
            return

        if data == "wallet:charge:crypto":
            st = state_data(uid)
            amount = st.get("amount")
            if not amount:
                bot.answer_callback_query(call.id, "ابتدا مبلغ را وارد کنید.", show_alert=True)
                return
            bot.answer_callback_query(call.id)
            _cb_show_crypto_currencies(call, uid, "wallet", amount)
            return

        if data.startswith("wallet:crypto_cur:"):
            # wallet:crypto_cur:{currency}
            currency = data.split(":")[2]
            st = state_data(uid)
            amount = st.get("amount")
            if not amount:
                bot.answer_callback_query(call.id, "ابتدا مبلغ را وارد کنید.", show_alert=True)
                return
            bot.answer_callback_query(call.id)
            _cb_wallet_crypto(call, uid, currency, amount)
            return

        # ── Reseller panel ──
        if data == "reseller:panel":
            bot.answer_callback_query(call.id)
            _cb_reseller_panel(call, uid)
            return

        # ── Admin ──
        if data == "admin:panel":
            if not is_admin(uid):
                bot.answer_callback_query(call.id, "اجازه دسترسی ندارید.", show_alert=True)
                return
            bot.answer_callback_query(call.id)
            send_or_edit(call, "⚙️ <b>پنل مدیریت</b>\n\nبخش مورد نظر را انتخاب کنید:", kb_admin_panel())
            return

        # Admin routes – all require admin
        if data.startswith("admin:") and not is_admin(uid):
            bot.answer_callback_query(call.id, "اجازه دسترسی ندارید.", show_alert=True)
            return

        if data == "admin:types":
            bot.answer_callback_query(call.id)
            show_admin_types_again(call)
            return

        if data == "admin:type:add":
            state_set(uid, "admin_add_type")
            bot.answer_callback_query(call.id)
            send_or_edit(call, "🧩 نام نوع جدید را ارسال کنید:", back_button("admin:types"))
            return

        if data.startswith("admin:type:edit:"):
            type_id = int(data.split(":")[3])
            row = get_type(type_id)
            if not row:
                bot.answer_callback_query(call.id, "نوع یافت نشد.", show_alert=True)
                return
            state_set(uid, "admin_edit_type", type_id=type_id)
            bot.answer_callback_query(call.id)
            send_or_edit(call, f"✏️ نام جدید برای نوع <b>{esc(row['name'])}</b> را ارسال کنید:", back_button("admin:types"))
            return

        if data.startswith("admin:type:del:"):
            type_id = int(data.split(":")[3])
            delete_type(type_id)
            bot.answer_callback_query(call.id, "نوع حذف شد.")
            show_admin_types_again(call)
            return

        # Package management
        if data == "admin:pkgs":
            bot.answer_callback_query(call.id)
            show_admin_packages(call)
            return

        if data == "admin:add_package":
            types_list = get_all_types()
            kb = types.InlineKeyboardMarkup()
            for item in types_list:
                kb.add(types.InlineKeyboardButton(f"🧩 {item['name']}", callback_data=f"admin:add_package:type:{item['id']}"))
            kb.add(types.InlineKeyboardButton("🔙 بازگشت", callback_data="admin:pkgs"))
            bot.answer_callback_query(call.id)
            send_or_edit(call, "📦 <b>افزودن پکیج</b>\n\nنوع کانفیگ را انتخاب کنید:", kb)
            return

        if data.startswith("admin:add_package:type:"):
            type_id = int(data.split(":")[3])
            type_row = get_type(type_id)
            state_set(uid, "admin_add_package_name", type_id=type_id)
            bot.answer_callback_query(call.id)
            send_or_edit(call, f"✏️ نام پکیج برای نوع <b>{esc(type_row['name'])}</b> را وارد کنید:", back_button("admin:add_package"))
            return

        if data.startswith("admin:pkg:edit:"):
            pkg_id = int(data.split(":")[3])
            p = get_package(pkg_id)
            if not p:
                bot.answer_callback_query(call.id, "پکیج یافت نشد.", show_alert=True)
                return
            kb = types.InlineKeyboardMarkup()
            kb.add(types.InlineKeyboardButton("✏️ ویرایش نام",   callback_data=f"admin:pkg:edit:name:{pkg_id}"))
            kb.add(types.InlineKeyboardButton("💰 ویرایش قیمت",  callback_data=f"admin:pkg:edit:price:{pkg_id}"))
            kb.add(types.InlineKeyboardButton("🔋 ویرایش حجم",   callback_data=f"admin:pkg:edit:vol:{pkg_id}"))
            kb.add(types.InlineKeyboardButton("⏰ ویرایش مدت",   callback_data=f"admin:pkg:edit:dur:{pkg_id}"))
            kb.add(types.InlineKeyboardButton("🔙 بازگشت",       callback_data="admin:pkgs"))
            bot.answer_callback_query(call.id)
            send_or_edit(call, f"✏️ <b>ویرایش پکیج</b>\n\n{esc(p['name'])} | {p['volume_gb']}GB | {p['duration_days']}روز | {fmt_price(p['price'])} تومان", kb)
            return

        if data.startswith("admin:pkg:edit:name:"):
            pkg_id = int(data.split(":")[4])
            state_set(uid, "admin_edit_pkg_name", pkg_id=pkg_id)
            bot.answer_callback_query(call.id)
            send_or_edit(call, "✏️ نام جدید پکیج را ارسال کنید:", back_button("admin:pkgs"))
            return

        if data.startswith("admin:pkg:edit:price:"):
            pkg_id = int(data.split(":")[4])
            state_set(uid, "admin_edit_pkg_price", pkg_id=pkg_id)
            bot.answer_callback_query(call.id)
            send_or_edit(call, "💰 قیمت جدید پکیج را به تومان وارد کنید:", back_button("admin:pkgs"))
            return

        if data.startswith("admin:pkg:edit:vol:"):
            pkg_id = int(data.split(":")[4])
            state_set(uid, "admin_edit_pkg_vol", pkg_id=pkg_id)
            bot.answer_callback_query(call.id)
            send_or_edit(call, "🔋 حجم جدید پکیج را به گیگ وارد کنید:", back_button("admin:pkgs"))
            return

        if data.startswith("admin:pkg:edit:dur:"):
            pkg_id = int(data.split(":")[4])
            state_set(uid, "admin_edit_pkg_dur", pkg_id=pkg_id)
            bot.answer_callback_query(call.id)
            send_or_edit(call, "⏰ مدت جدید پکیج را به روز وارد کنید:", back_button("admin:pkgs"))
            return

        if data.startswith("admin:pkg:del:"):
            pkg_id = int(data.split(":")[3])
            delete_package(pkg_id)
            bot.answer_callback_query(call.id, "پکیج حذف شد.")
            show_admin_packages(call)
            return

        # Config management
        if data == "admin:configs":
            bot.answer_callback_query(call.id)
            show_admin_configs(call)
            return

        if data.startswith("admin:cfglist:"):
            # admin:cfglist:all:a:0  OR  admin:cfglist:all:s:0
            # admin:cfglist:pkg:{pkg_id}:menu
            # admin:cfglist:pkg:{pkg_id}:a:0  OR  admin:cfglist:pkg:{pkg_id}:s:0
            parts = data.split(":")
            scope = parts[2]
            bot.answer_callback_query(call.id)
            if scope == "all":
                kind = parts[3]
                page = int(parts[4])
                show_configs_page(call, "all", kind, page)
            else:  # pkg
                pkg_id = int(parts[3])
                action = parts[4]
                if action == "menu":
                    p = get_package(pkg_id)
                    if not p:
                        send_or_edit(call, "پکیج یافت نشد.", back_button("admin:configs"))
                        return
                    kb = types.InlineKeyboardMarkup()
                    kb.row(
                        types.InlineKeyboardButton(f"🟢 مانده ({p['stock']})", callback_data=f"admin:cfglist:pkg:{pkg_id}:a:0"),
                        types.InlineKeyboardButton(f"🔴 فروخته ({p['sold_count']})", callback_data=f"admin:cfglist:pkg:{pkg_id}:s:0"),
                    )
                    kb.add(types.InlineKeyboardButton("🔙 بازگشت", callback_data="admin:configs"))
                    send_or_edit(call, f"📦 <b>{esc(p['name'])}</b>\n\nنوع مشاهده را انتخاب کنید:", kb)
                else:
                    kind = action
                    page = int(parts[5])
                    show_configs_page(call, "pkg", kind, page, pkg_id)
            return

        if data.startswith("admin:cfg:view:"):
            # admin:cfg:view:{cfg_id}:{scope}:{kind}:{page}[:{pkg_id}]
            parts = data.split(":")
            cfg_id = int(parts[3])
            scope  = parts[4]
            kind   = parts[5]
            page   = int(parts[6])
            pkg_id = int(parts[7]) if len(parts) > 7 else None
            row = get_config(cfg_id)
            if not row:
                bot.answer_callback_query(call.id, "یافت نشد.", show_alert=True)
                return
            text = (
                f"🔮 نام سرویس: <b>{esc(row['service_name'])}</b>\n"
                f"📅 ثبت شده: {esc(row['created_at'])}\n"
                f"{'🔴 پایان یافته\n' if row['ended'] else ''}"
                f"\n💝 Config:\n<code>{esc(row['config_text'])}</code>\n\n"
                f"🔋 Volume web: {esc(row['inquiry_link'] or '-')}"
            )
            if kind == "s":
                buyer = get_user(row["sold_to"]) if row["sold_to"] else None
                text += (
                    f"\n\n👤 خریدار: {esc(buyer['full_name'] if buyer else str(row['sold_to']))}\n"
                    f"🆔 آیدی: <code>{row['sold_to']}</code>\n"
                    f"🕒 زمان خرید: {esc(row['sold_at'] or '-')}"
                )
            back_suffix = f":{pkg_id}" if scope == "pkg" else ""
            back_cb = f"admin:cfglist:{scope}:{kind}:{page}{back_suffix}"
            kb = types.InlineKeyboardMarkup()
            kb.add(types.InlineKeyboardButton("🔙 بازگشت", callback_data=back_cb))
            bot.answer_callback_query(call.id)
            send_or_edit(call, text, kb)
            return

        # Add config
        if data == "admin:add_config":
            types_list = get_all_types()
            kb = types.InlineKeyboardMarkup()
            for item in types_list:
                kb.add(types.InlineKeyboardButton(f"🧩 {item['name']}", callback_data=f"admin:add_config:type:{item['id']}"))
            kb.add(types.InlineKeyboardButton("🔙 بازگشت", callback_data="admin:panel"))
            bot.answer_callback_query(call.id)
            send_or_edit(call, "📝 <b>ثبت کانفیگ</b>\n\nابتدا نوع را انتخاب کنید:", kb)
            return

        if data.startswith("admin:add_config:type:"):
            type_id = int(data.split(":")[3])
            packs = get_packages(type_id=type_id)
            kb = types.InlineKeyboardMarkup()
            for p in packs:
                kb.add(types.InlineKeyboardButton(f"{p['name']} | {p['volume_gb']}GB | {p['duration_days']} روز", callback_data=f"admin:add_config:pkg:{p['id']}"))
            kb.add(types.InlineKeyboardButton("🔙 بازگشت", callback_data="admin:add_config"))
            bot.answer_callback_query(call.id)
            send_or_edit(call, "📦 پکیج مربوطه را انتخاب کنید:", kb)
            return

        if data.startswith("admin:add_config:pkg:"):
            package_id = int(data.split(":")[3])
            package_row = get_package(package_id)
            state_set(uid, "admin_add_config_service", package_id=package_id, type_id=package_row["type_id"])
            bot.answer_callback_query(call.id)
            send_or_edit(call, "✏️ نام سرویس را وارد کنید:", back_button("admin:add_config"))
            return

        # User management
        if data == "admin:users":
            bot.answer_callback_query(call.id)
            _cb_admin_users(call, 0)
            return

        if data.startswith("admin:users:page:"):
            page = int(data.split(":")[3])
            bot.answer_callback_query(call.id)
            _cb_admin_users(call, page)
            return

        if data.startswith("admin:user:") and not data.startswith("admin:user:safe") \
                and not data.startswith("admin:user:unsafe") and not data.startswith("admin:user:reseller") \
                and not data.startswith("admin:user:bal") and not data.startswith("admin:user:cfgs") \
                and not data.startswith("admin:user:cfg_end") and not data.startswith("admin:user:addcfg") \
                and not data.startswith("admin:user:rprices"):
            user_id = int(data.split(":")[2])
            bot.answer_callback_query(call.id)
            show_admin_user_detail(call, user_id)
            return

        if data.startswith("admin:user:safe:") or data.startswith("admin:user:unsafe:"):
            parts = data.split(":")
            is_safe_val = 1 if parts[2] == "safe" else 0
            user_id = int(parts[3])
            set_user_safe(user_id, is_safe_val)
            bot.answer_callback_query(call.id, "وضعیت کاربر تغییر کرد.")
            show_admin_user_detail(call, user_id)
            return

        if data.startswith("admin:user:reseller:"):
            # admin:user:reseller:{0|1}:{user_id}
            parts = data.split(":")
            val     = int(parts[3])
            user_id = int(parts[4])
            set_user_reseller(user_id, val)
            bot.answer_callback_query(call.id, "وضعیت نمایندگی تغییر کرد.")
            show_admin_user_detail(call, user_id)
            return

        if data.startswith("admin:user:bal:"):
            user_id = int(data.split(":")[3])
            kb = types.InlineKeyboardMarkup()
            kb.row(
                types.InlineKeyboardButton("➕ افزایش موجودی", callback_data=f"admin:user:bal:inc:{user_id}"),
                types.InlineKeyboardButton("➖ کاهش موجودی",  callback_data=f"admin:user:bal:dec:{user_id}"),
            )
            kb.add(types.InlineKeyboardButton("🔙 بازگشت", callback_data=f"admin:user:{user_id}"))
            u = get_user(user_id)
            bot.answer_callback_query(call.id)
            send_or_edit(call, f"💰 موجودی فعلی: <b>{fmt_price(u['balance'])}</b> تومان\n\nعملیات را انتخاب کنید:", kb)
            return

        if data.startswith("admin:user:bal:inc:"):
            user_id = int(data.split(":")[4])
            state_set(uid, "admin_bal_increase", target_user_id=user_id)
            bot.answer_callback_query(call.id)
            send_or_edit(call, "💰 مبلغ افزایش موجودی را وارد کنید (تومان):", back_button(f"admin:user:{user_id}"))
            return

        if data.startswith("admin:user:bal:dec:"):
            user_id = int(data.split(":")[4])
            state_set(uid, "admin_bal_decrease", target_user_id=user_id)
            bot.answer_callback_query(call.id)
            send_or_edit(call, "💰 مبلغ کاهش موجودی را وارد کنید (تومان):", back_button(f"admin:user:{user_id}"))
            return

        if data.startswith("admin:user:cfgs:"):
            user_id = int(data.split(":")[3])
            bot.answer_callback_query(call.id)
            show_admin_user_configs(call, user_id)
            return

        if data.startswith("admin:user:cfg_end:"):
            # admin:user:cfg_end:{config_id}:{ended}:{user_id}
            parts  = data.split(":")
            cfg_id  = int(parts[3])
            ended   = int(parts[4])
            user_id = int(parts[5])
            mark_config_ended(cfg_id, ended)
            bot.answer_callback_query(call.id, "وضعیت کانفیگ تغییر کرد.")
            show_admin_user_configs(call, user_id)
            return

        if data.startswith("admin:user:addcfg:"):
            # Show packages to pick from
            user_id = int(data.split(":")[3])
            pkgs = get_packages()
            kb = types.InlineKeyboardMarkup()
            for p in pkgs:
                if p["stock"] > 0:
                    kb.add(types.InlineKeyboardButton(
                        f"📦 {p['name']} | 🟢{p['stock']} مانده",
                        callback_data=f"admin:user:addcfg:pkg:{p['id']}:{user_id}",
                    ))
            kb.add(types.InlineKeyboardButton("🔙 بازگشت", callback_data=f"admin:user:{user_id}"))
            bot.answer_callback_query(call.id)
            send_or_edit(call, "📦 پکیج مورد نظر را انتخاب کنید:", kb)
            return

        if data.startswith("admin:user:addcfg:pkg:"):
            # admin:user:addcfg:pkg:{pkg_id}:{user_id}
            parts = data.split(":")
            pkg_id  = int(parts[4])
            user_id = int(parts[5])
            cfgs, _ = get_available_configs_for_package(pkg_id, 0, 20)
            kb = types.InlineKeyboardMarkup()
            for c in cfgs:
                kb.add(types.InlineKeyboardButton(
                    f"🔮 {c['service_name']}",
                    callback_data=f"admin:user:addcfg:do:{c['id']}:{pkg_id}:{user_id}",
                ))
            kb.add(types.InlineKeyboardButton("🔙 بازگشت", callback_data=f"admin:user:addcfg:{user_id}"))
            bot.answer_callback_query(call.id)
            send_or_edit(call, "🔮 کانفیگ مورد نظر را انتخاب کنید:", kb)
            return

        if data.startswith("admin:user:addcfg:do:"):
            # admin:user:addcfg:do:{cfg_id}:{pkg_id}:{user_id}
            parts  = data.split(":")
            cfg_id  = int(parts[4])
            pkg_id  = int(parts[5])
            user_id = int(parts[6])
            # assign
            purchase_id = assign_config_to_user(cfg_id, user_id, pkg_id, 0, "admin_gift")
            bot.answer_callback_query(call.id, "کانفیگ با موفقیت انتقال یافت.")
            try:
                deliver_purchase_message(user_id, purchase_id)
            except Exception:
                pass
            show_admin_user_detail(call, user_id)
            return

        if data.startswith("admin:user:rprices:"):
            user_id = int(data.split(":")[3])
            pkgs = get_packages()
            kb = types.InlineKeyboardMarkup()
            for p in pkgs:
                rp = get_reseller_price(user_id, p["id"])
                lbl = f"📦 {p['name']} | قیمت: {fmt_price(rp if rp is not None else p['price'])}"
                kb.add(types.InlineKeyboardButton(lbl, callback_data=f"admin:user:rp:set:{p['id']}:{user_id}"))
            kb.add(types.InlineKeyboardButton("🔙 بازگشت", callback_data=f"admin:user:{user_id}"))
            bot.answer_callback_query(call.id)
            send_or_edit(call, "💲 <b>قیمت‌های نمایندگی</b>\n\nروی هر پکیج بزنید تا قیمت ویرایش شود:", kb)
            return

        if data.startswith("admin:user:rp:set:"):
            parts  = data.split(":")
            pkg_id  = int(parts[4])
            user_id = int(parts[5])
            state_set(uid, "admin_set_reseller_price", pkg_id=pkg_id, target_user_id=user_id)
            bot.answer_callback_query(call.id)
            send_or_edit(call, "💲 قیمت جدید نمایندگی را به تومان وارد کنید:", back_button(f"admin:user:rprices:{user_id}"))
            return

        # Broadcast
        if data == "admin:broadcast":
            kb = types.InlineKeyboardMarkup()
            kb.add(types.InlineKeyboardButton("📣 همه کاربران",  callback_data="admin:broadcast:all"))
            kb.add(types.InlineKeyboardButton("🛍 فقط مشتری‌ها", callback_data="admin:broadcast:customers"))
            kb.add(types.InlineKeyboardButton("🔙 بازگشت",       callback_data="admin:panel"))
            bot.answer_callback_query(call.id)
            send_or_edit(call, "📣 <b>فوروارد همگانی</b>\n\nگیرنده را انتخاب کنید:", kb)
            return

        if data == "admin:broadcast:all":
            state_set(uid, "admin_broadcast_all")
            bot.answer_callback_query(call.id)
            send_or_edit(call, "📣 پیام خود را ارسال کنید. (برای <b>همه کاربران</b>)", back_button("admin:broadcast"))
            return

        if data == "admin:broadcast:customers":
            state_set(uid, "admin_broadcast_customers")
            bot.answer_callback_query(call.id)
            send_or_edit(call, "🛍 پیام خود را ارسال کنید. (فقط <b>مشتری‌ها</b>)", back_button("admin:broadcast"))
            return

        # Settings
        if data == "admin:settings":
            _cb_admin_settings(call)
            return

        if data == "admin:set:support":
            state_set(uid, "admin_set_support")
            bot.answer_callback_query(call.id)
            send_or_edit(call, "🎧 آیدی یا لینک پشتیبانی را ارسال کنید.\nمثال: <code>@Tracklessvpnadmin</code>", back_button("admin:settings"))
            return

        if data == "admin:set:payment":
            _cb_admin_payment_settings(call)
            return

        if data == "admin:set:card":
            state_set(uid, "admin_set_card")
            bot.answer_callback_query(call.id)
            send_or_edit(call, "💳 شماره کارت را ارسال کنید:", back_button("admin:set:payment"))
            return

        if data == "admin:set:bank":
            state_set(uid, "admin_set_bank")
            bot.answer_callback_query(call.id)
            send_or_edit(call, "🏦 نام بانک را ارسال کنید:", back_button("admin:set:payment"))
            return

        if data == "admin:set:owner":
            state_set(uid, "admin_set_owner")
            bot.answer_callback_query(call.id)
            send_or_edit(call, "👤 نام صاحب کارت را ارسال کنید:", back_button("admin:set:payment"))
            return

        if data == "admin:set:card_status:public":
            setting_set("payment_card_status", "public")
            bot.answer_callback_query(call.id, "وضعیت کارت: عمومی")
            _cb_admin_payment_settings(call)
            return

        if data == "admin:set:card_status:safe":
            setting_set("payment_card_status", "safe")
            bot.answer_callback_query(call.id, "وضعیت کارت: فقط امن")
            _cb_admin_payment_settings(call)
            return

        if data == "admin:set:crypto":
            _cb_admin_crypto_settings(call)
            return

        if data.startswith("admin:set:crypto:"):
            currency = data.split(":")[3]
            state_set(uid, "admin_set_crypto", currency=currency)
            disp = next((d for k, d, _ in CRYPTO_CURRENCIES if k == currency), currency)
            bot.answer_callback_query(call.id)
            send_or_edit(call, f"💰 آدرس ولت <b>{disp}</b> را ارسال کنید:", back_button("admin:set:crypto"))
            return

        # Payment approvals
        if data.startswith("admin:pay:approve:"):
            payment_id = int(data.split(":")[3])
            state_set(uid, "admin_payment_approve_note", payment_id=payment_id)
            bot.answer_callback_query(call.id)
            send_or_edit(call, "✅ متن تأیید را برای کاربر ارسال کنید:", back_button("admin:panel"))
            return

        if data.startswith("admin:pay:reject:"):
            payment_id = int(data.split(":")[3])
            state_set(uid, "admin_payment_reject_note", payment_id=payment_id)
            bot.answer_callback_query(call.id)
            send_or_edit(call, "❌ متن رد را برای کاربر ارسال کنید:", back_button("admin:panel"))
            return

        # Backup
        if data == "admin:backup":
            _cb_admin_backup(call)
            return

        if data == "admin:backup:now":
            bot.answer_callback_query(call.id)
            do_backup(uid)
            return

        if data == "admin:backup:toggle":
            cur = setting_get("auto_backup_enabled", "0")
            setting_set("auto_backup_enabled", "0" if cur == "1" else "1")
            if setting_get("auto_backup_enabled") == "1":
                schedule_backup()
            else:
                cancel_backup()
            bot.answer_callback_query(call.id)
            _cb_admin_backup(call)
            return

        if data == "admin:backup:set_interval":
            state_set(uid, "admin_set_backup_interval")
            bot.answer_callback_query(call.id)
            send_or_edit(call, f"⏰ فاصله بکاپ خودکار را به ساعت وارد کنید (فعلی: {setting_get('backup_interval_hours', '24')}):", back_button("admin:backup"))
            return

        if data == "admin:backup:set_chat":
            state_set(uid, "admin_set_backup_chat")
            bot.answer_callback_query(call.id)
            send_or_edit(call, "📤 آیدی عددی چت/کانال دریافت بکاپ را وارد کنید:", back_button("admin:backup"))
            return

        # Channel lock
        if data == "admin:channel":
            _cb_admin_channel(call)
            return

        if data == "admin:channel:toggle":
            cur = setting_get("channel_lock_enabled", "0")
            setting_set("channel_lock_enabled", "0" if cur == "1" else "1")
            bot.answer_callback_query(call.id)
            _cb_admin_channel(call)
            return

        if data == "admin:channel:set_id":
            state_set(uid, "admin_set_channel_id")
            bot.answer_callback_query(call.id)
            send_or_edit(call,
                "📢 آیدی عددی کانال را ارسال کنید.\n\n"
                "⚠️ ابتدا ربات را ادمین کانال کنید تا بتواند عضویت را بررسی کند.\n"
                "مثال: <code>-1001234567890</code>",
                back_button("admin:channel"),
            )
            return

        if data == "noop":
            bot.answer_callback_query(call.id)
            return

        bot.answer_callback_query(call.id)

    except Exception as e:
        print("CALLBACK_ERROR:", e)
        traceback.print_exc()
        try:
            bot.answer_callback_query(call.id, "خطایی رخ داد.", show_alert=True)
        except Exception:
            pass


# ──────────────────────────────────────────────
# Callback helpers (buy / pay / etc.)
# ──────────────────────────────────────────────
def _cb_buy_start(call, uid: int):
    items = get_all_types()
    kb = types.InlineKeyboardMarkup()
    for item in items:
        kb.add(types.InlineKeyboardButton(f"🧩 {item['name']}", callback_data=f"buy:type:{item['id']}"))
    kb.add(types.InlineKeyboardButton("🔙 بازگشت", callback_data="nav:main"))
    send_or_edit(call, "🛒 <b>خرید کانفیگ جدید</b>\n\nنوع مورد نظر را انتخاب کنید:", kb)


def _cb_buy_type(call, uid: int, type_id: int):
    user = get_user(uid)
    packages = [p for p in get_packages(type_id=type_id) if p["price"] > 0 and p["stock"] > 0]
    kb = types.InlineKeyboardMarkup()
    is_reseller = bool(user and user["is_reseller"])
    if is_reseller:
        header = "🎯 <b>قیمت‌های مخصوص همکاری شماست</b>\n\n"
    else:
        header = ""
    for p in packages:
        effective_price = get_effective_price(uid, p["id"], p["price"])
        title = f"{p['name']} | {p['volume_gb']}GB | {p['duration_days']} روز | {fmt_price(effective_price)} تومان"
        kb.add(types.InlineKeyboardButton(title, callback_data=f"buy:pkg:{p['id']}"))
    kb.add(types.InlineKeyboardButton("🔙 بازگشت", callback_data="buy:start"))
    if not packages:
        send_or_edit(call, "📭 فعلاً پکیج فعالی برای این نوع ثبت نشده است.", kb)
    else:
        send_or_edit(call, f"{header}📦 یکی از پکیج‌ها را انتخاب کنید:", kb)


def _cb_buy_pkg(call, uid: int, package_id: int):
    package_row = get_package(package_id)
    if not package_row:
        bot.answer_callback_query(call.id, "پکیج یافت نشد.", show_alert=True)
        return
    user = get_user(uid)
    is_reseller = bool(user and user["is_reseller"])
    effective_price = get_effective_price(uid, package_id, package_row["price"])
    reseller_note = "\n\n💡 <i>این قیمت مخصوص همکاری شماست.</i>" if is_reseller and effective_price != package_row["price"] else ""
    text = (
        f"💳 <b>روش پرداخت</b>\n\n"
        f"🧩 نوع: {esc(package_row['type_name'])}\n"
        f"📦 نام پکیج: {esc(package_row['name'])}\n"
        f"🔋 حجم: {package_row['volume_gb']} گیگ\n"
        f"⏰ مدت: {package_row['duration_days']} روز\n"
        f"💰 قیمت: {fmt_price(effective_price)} تومان{reseller_note}"
    )
    send_or_edit(call, text, kb_payment_method("buy", package_id))


def _cb_pay_wallet(call, uid: int, package_id: int):
    package_row = get_package(package_id)
    user = get_user(uid)
    if not package_row or package_row["stock"] <= 0:
        bot.answer_callback_query(call.id, "موجودی این پکیج به پایان رسیده است.", show_alert=True)
        return
    effective_price = get_effective_price(uid, package_id, package_row["price"])
    if user["balance"] < effective_price:
        bot.answer_callback_query(call.id, "موجودی کیف پول کافی نیست.", show_alert=True)
        return
    config_id = reserve_first_config(package_id)
    if not config_id:
        bot.answer_callback_query(call.id, "فعلاً کانفیگی برای این پکیج موجود نیست.", show_alert=True)
        return
    update_balance(uid, -effective_price)
    purchase_id = assign_config_to_user(config_id, uid, package_id, effective_price, "wallet")
    pid = create_payment("config_purchase", uid, package_id, effective_price, "wallet", status="completed", config_id=config_id)
    complete_payment(pid)
    bot.answer_callback_query(call.id, "خرید با موفقیت انجام شد.")
    send_or_edit(call, "✅ خرید شما با موفقیت انجام شد.", back_button("main"))
    deliver_purchase_message(call.message.chat.id, purchase_id)
    admin_purchase_notify("کیف پول", get_user(uid), package_row)


def _cb_pay_card(call, uid: int, package_id: int):
    package_row = get_package(package_id)
    if not package_row or package_row["stock"] <= 0:
        bot.answer_callback_query(call.id, "موجودی این پکیج به پایان رسیده است.", show_alert=True)
        return
    user = get_user(uid)
    card_status = setting_get("payment_card_status", "public")
    if card_status == "safe" and not user["is_safe"]:
        bot.answer_callback_query(call.id, "درگاه کارت به کارت فقط برای کاربران تأیید شده فعال است.", show_alert=True)
        return
    card = setting_get("payment_card", "")
    bank = setting_get("payment_bank", "")
    owner = setting_get("payment_owner", "")
    if not card:
        bot.answer_callback_query(call.id, "اطلاعات پرداخت هنوز توسط ادمین ثبت نشده است.", show_alert=True)
        return
    effective_price = get_effective_price(uid, package_id, package_row["price"])
    payment_id = create_payment("config_purchase", uid, package_id, effective_price, "card", status="pending")
    config_id = reserve_first_config(package_id, payment_id=payment_id)
    if not config_id:
        bot.answer_callback_query(call.id, "فعلاً کانفیگی موجود نیست.", show_alert=True)
        return
    with get_conn() as conn:
        conn.execute("UPDATE payments SET config_id=? WHERE id=?", (config_id, payment_id))
    state_set(uid, "await_purchase_receipt", payment_id=payment_id)
    text = (
        "💳 <b>کارت به کارت</b>\n\n"
        f"لطفاً مبلغ <b>{fmt_price(effective_price)}</b> تومان را به کارت زیر واریز کنید:\n\n"
        f"🏦 {esc(bank or 'ثبت نشده')}\n"
        f"👤 {esc(owner or 'ثبت نشده')}\n"
        f"💳 <code>{esc(card)}</code>\n\n"
        "📸 پس از واریز، تصویر رسید یا شماره پیگیری را ارسال کنید."
    )
    kb = types.InlineKeyboardMarkup()
    kb.add(types.InlineKeyboardButton("🔙 بازگشت به منو", callback_data="nav:main"))
    send_or_edit(call, text, kb)


def _cb_show_crypto_currencies(call, uid: int, context_type: str, context_id):
    """context_type: 'wallet'|'buy'"""
    kb = types.InlineKeyboardMarkup()
    for key, display, emoji in CRYPTO_CURRENCIES:
        wallet_addr = setting_get(f"crypto_{key}", "")
        if wallet_addr:
            if context_type == "wallet":
                cb = f"wallet:crypto_cur:{key}"
            else:
                cb = f"pay:crypto_cur:{key}:{context_id}"
            kb.add(types.InlineKeyboardButton(f"{emoji} {display}", callback_data=cb))
    if context_type == "wallet":
        back_cb = "wallet:charge"
    else:
        back_cb = f"buy:pkg:{context_id}"
    kb.add(types.InlineKeyboardButton("🔙 بازگشت", callback_data=back_cb))
    if len(kb.keyboard) <= 1:
        send_or_edit(call, "⚠️ هیچ ارز دیجیتالی هنوز تنظیم نشده است.", kb)
    else:
        send_or_edit(call, "💰 <b>پرداخت با ارز دیجیتال</b>\n\nنوع ارز را انتخاب کنید:", kb)


def _cb_wallet_crypto(call, uid: int, currency: str, amount: int):
    disp = next((d for k, d, _ in CRYPTO_CURRENCIES if k == currency), currency)
    emoji = next((e for k, _, e in CRYPTO_CURRENCIES if k == currency), "💰")
    wallet_addr = setting_get(f"crypto_{currency}", "")
    payment_id = create_payment("wallet_charge", uid, None, amount, "crypto", status="pending", crypto_currency=currency)
    state_set(uid, "await_crypto_receipt", payment_id=payment_id)
    text = (
        f"💰 <b>پرداخت با {disp}</b>\n\n"
        f"لطفاً معادل <b>{fmt_price(amount)}</b> تومان را به آدرس زیر ارسال کنید:\n\n"
        f"{emoji} نوع ارز: <b>{disp}</b>\n"
        f"📋 آدرس ولت:\n<code>{esc(wallet_addr)}</code>\n\n"
        f"📝 پس از ارسال، هش تراکنش یا تصویر تأیید را ارسال کنید.\n\n"
        f"🔗 <a href='{NOBITEX_PRICE_URL}'>مشاهده قیمت ارز دیجیتال در نوبیتکس</a>"
    )
    kb = types.InlineKeyboardMarkup()
    kb.add(types.InlineKeyboardButton("🔙 بازگشت به منو", callback_data="nav:main"))
    send_or_edit(call, text, kb, disable_preview=False)


def _cb_pay_crypto_buy(call, uid: int, currency: str, package_id: int):
    package_row = get_package(package_id)
    if not package_row or package_row["stock"] <= 0:
        bot.answer_callback_query(call.id, "موجودی این پکیج به پایان رسیده است.", show_alert=True)
        return
    effective_price = get_effective_price(uid, package_id, package_row["price"])
    disp = next((d for k, d, _ in CRYPTO_CURRENCIES if k == currency), currency)
    emoji = next((e for k, _, e in CRYPTO_CURRENCIES if k == currency), "💰")
    wallet_addr = setting_get(f"crypto_{currency}", "")
    payment_id = create_payment("config_purchase", uid, package_id, effective_price, "crypto", status="pending", crypto_currency=currency)
    config_id  = reserve_first_config(package_id, payment_id=payment_id)
    if not config_id:
        bot.answer_callback_query(call.id, "فعلاً کانفیگی موجود نیست.", show_alert=True)
        return
    with get_conn() as conn:
        conn.execute("UPDATE payments SET config_id=? WHERE id=?", (config_id, payment_id))
    state_set(uid, "await_crypto_receipt", payment_id=payment_id)
    text = (
        f"💰 <b>پرداخت با {disp}</b>\n\n"
        f"لطفاً معادل <b>{fmt_price(effective_price)}</b> تومان را به آدرس زیر ارسال کنید:\n\n"
        f"{emoji} نوع ارز: <b>{disp}</b>\n"
        f"📋 آدرس ولت:\n<code>{esc(wallet_addr)}</code>\n\n"
        f"📝 پس از ارسال، هش تراکنش یا تصویر تأیید را ارسال کنید.\n\n"
        f"🔗 <a href='{NOBITEX_PRICE_URL}'>مشاهده قیمت ارز دیجیتال در نوبیتکس</a>"
    )
    kb = types.InlineKeyboardMarkup()
    kb.add(types.InlineKeyboardButton("🔙 بازگشت به منو", callback_data="nav:main"))
    send_or_edit(call, text, kb, disable_preview=False)


def _cb_wallet_card(call, uid: int, amount: int):
    card_status = setting_get("payment_card_status", "public")
    user = get_user(uid)
    if card_status == "safe" and not user["is_safe"]:
        bot.answer_callback_query(call.id, "درگاه کارت به کارت فقط برای کاربران تأیید شده فعال است.", show_alert=True)
        return
    card  = setting_get("payment_card", "")
    bank  = setting_get("payment_bank", "")
    owner = setting_get("payment_owner", "")
    if not card:
        bot.answer_callback_query(call.id, "اطلاعات پرداخت هنوز ثبت نشده است.", show_alert=True)
        return
    payment_id = create_payment("wallet_charge", uid, None, amount, "card", status="pending")
    state_set(uid, "await_wallet_receipt", payment_id=payment_id, amount=amount)
    text = (
        "💳 <b>کارت به کارت</b>\n\n"
        f"لطفاً مبلغ <b>{fmt_price(amount)}</b> تومان را به کارت زیر واریز کنید:\n\n"
        f"🏦 {esc(bank or 'ثبت نشده')}\n"
        f"👤 {esc(owner or 'ثبت نشده')}\n"
        f"💳 <code>{esc(card)}</code>\n\n"
        "📸 پس از واریز، تصویر رسید یا شماره پیگیری را ارسال کنید."
    )
    kb = types.InlineKeyboardMarkup()
    kb.add(types.InlineKeyboardButton("🔙 بازگشت به منو", callback_data="nav:main"))
    send_or_edit(call, text, kb)


def _cb_test_start(call):
    items = get_all_types()
    kb = types.InlineKeyboardMarkup()
    for item in items:
        kb.add(types.InlineKeyboardButton(f"🎁 {item['name']}", callback_data=f"test:type:{item['id']}"))
    kb.add(types.InlineKeyboardButton("🔙 بازگشت", callback_data="nav:main"))
    send_or_edit(call, "🎁 <b>تست رایگان</b>\n\nنوع مورد نظر را انتخاب کنید:", kb)


def _cb_test_type(call, uid: int, type_id: int):
    type_row = get_type(type_id)
    package_row = None
    for item in get_packages(type_id=type_id, price_only=0):
        if item["stock"] > 0:
            package_row = item
            break
    if not package_row:
        bot.answer_callback_query(call.id, "برای این نوع، تست رایگان موجود نیست.", show_alert=True)
        return
    if user_has_test_for_type(uid, type_id):
        bot.answer_callback_query(call.id, "شما قبلاً تست رایگان این نوع را دریافت کرده‌اید.", show_alert=True)
        return
    config_id = reserve_first_config(package_row["id"])
    if not config_id:
        bot.answer_callback_query(call.id, "تست رایگان این نوع تمام شده است.", show_alert=True)
        return
    purchase_id = assign_config_to_user(config_id, uid, package_row["id"], 0, "free_test", is_test=1)
    bot.answer_callback_query(call.id, "تست رایگان ارسال شد.")
    send_or_edit(call, f"✅ تست رایگان نوع <b>{esc(type_row['name'])}</b> آماده شد.", back_button("main"))
    deliver_purchase_message(call.message.chat.id, purchase_id)


def _cb_admin_users(call, page: int):
    rows, total = get_users(page)
    total_pages = max(1, (total + USERS_PER_PAGE - 1) // USERS_PER_PAGE)
    kb = types.InlineKeyboardMarkup()
    for row in rows:
        safe_icon = "🟢" if row["is_safe"] else "🔘"
        reseller_icon = "🤝" if row["is_reseller"] else ""
        txt = f"{safe_icon}{reseller_icon} {row['full_name']} | {display_username(row['username'])}"
        kb.add(types.InlineKeyboardButton(txt, callback_data=f"admin:user:{row['user_id']}"))
    nav = []
    if page > 0:
        nav.append(types.InlineKeyboardButton("◀ قبلی", callback_data=f"admin:users:page:{page-1}"))
    nav.append(types.InlineKeyboardButton(f"{page+1}/{total_pages}", callback_data="noop"))
    if page + 1 < total_pages:
        nav.append(types.InlineKeyboardButton("بعدی ▶", callback_data=f"admin:users:page:{page+1}"))
    if nav:
        kb.row(*nav)
    kb.add(types.InlineKeyboardButton("🔙 بازگشت", callback_data="admin:panel"))
    send_or_edit(call, f"👥 <b>مدیریت کاربران</b>\n\nتعداد کل: {total}", kb)


def _cb_admin_settings(call):
    card = setting_get("payment_card", "ثبت نشده")
    bank = setting_get("payment_bank", "ثبت نشده")
    owner = setting_get("payment_owner", "ثبت نشده")
    card_status = setting_get("payment_card_status", "public")
    card_status_label = "عمومی" if card_status == "public" else "فقط امن"
    text = (
        "⚙️ <b>تنظیمات</b>\n\n"
        f"💳 کارت: <code>{esc(card)}</code>\n"
        f"🏦 بانک: {esc(bank)}\n"
        f"👤 صاحب: {esc(owner)}\n"
        f"🔒 وضعیت نمایش کارت: {card_status_label}"
    )
    kb = types.InlineKeyboardMarkup()
    kb.row(
        types.InlineKeyboardButton("🎧 پشتیبانی",       callback_data="admin:set:support"),
        types.InlineKeyboardButton("💳 اطلاعات پرداخت", callback_data="admin:set:payment"),
    )
    kb.add(types.InlineKeyboardButton("💰 ارز دیجیتال", callback_data="admin:set:crypto"))
    kb.add(types.InlineKeyboardButton("🔙 بازگشت", callback_data="admin:panel"))
    bot.answer_callback_query(call.id)
    send_or_edit(call, text, kb)


def _cb_admin_payment_settings(call):
    card_status = setting_get("payment_card_status", "public")
    kb = types.InlineKeyboardMarkup()
    kb.row(
        types.InlineKeyboardButton("💳 شماره کارت",   callback_data="admin:set:card"),
        types.InlineKeyboardButton("🏦 نام بانک",     callback_data="admin:set:bank"),
    )
    kb.add(types.InlineKeyboardButton("👤 نام صاحب کارت", callback_data="admin:set:owner"))
    kb.row(
        types.InlineKeyboardButton(f"{'✅' if card_status=='public' else '  '} عمومی",   callback_data="admin:set:card_status:public"),
        types.InlineKeyboardButton(f"{'✅' if card_status=='safe'   else '  '} فقط امن", callback_data="admin:set:card_status:safe"),
    )
    kb.add(types.InlineKeyboardButton("🔙 بازگشت", callback_data="admin:settings"))
    bot.answer_callback_query(call.id)
    send_or_edit(call, "💳 <b>اطلاعات پرداخت</b>", kb)


def _cb_admin_crypto_settings(call):
    kb = types.InlineKeyboardMarkup()
    for key, display, emoji in CRYPTO_CURRENCIES:
        addr = setting_get(f"crypto_{key}", "")
        label = f"{emoji} {display}: {'✅' if addr else '❌'}"
        kb.add(types.InlineKeyboardButton(label, callback_data=f"admin:set:crypto:{key}"))
    kb.add(types.InlineKeyboardButton("🔙 بازگشت", callback_data="admin:settings"))
    bot.answer_callback_query(call.id)
    send_or_edit(call, "💰 <b>تنظیم آدرس‌های ارز دیجیتال</b>", kb)


def _cb_admin_backup(call):
    auto_on     = setting_get("auto_backup_enabled", "0") == "1"
    interval_h  = setting_get("backup_interval_hours", "24")
    backup_chat = setting_get("backup_chat_id", "")
    text = (
        "💾 <b>پشتیبان‌گیری</b>\n\n"
        f"🤖 بکاپ خودکار: {'✅ فعال' if auto_on else '❌ غیرفعال'}\n"
        f"⏰ فاصله: هر {interval_h} ساعت\n"
        f"📤 ارسال به: {esc(backup_chat or 'تنظیم نشده')}"
    )
    kb = types.InlineKeyboardMarkup()
    kb.add(types.InlineKeyboardButton("📤 بکاپ دستی همین الان",    callback_data="admin:backup:now"))
    kb.add(types.InlineKeyboardButton(
        f"{'⏹ غیرفعال کردن' if auto_on else '▶️ فعال کردن'} بکاپ خودکار",
        callback_data="admin:backup:toggle",
    ))
    kb.row(
        types.InlineKeyboardButton("⏰ تنظیم فاصله",  callback_data="admin:backup:set_interval"),
        types.InlineKeyboardButton("📤 تنظیم مقصد",  callback_data="admin:backup:set_chat"),
    )
    kb.add(types.InlineKeyboardButton("🔙 بازگشت", callback_data="admin:panel"))
    bot.answer_callback_query(call.id)
    send_or_edit(call, text, kb)


def _cb_admin_channel(call):
    enabled    = setting_get("channel_lock_enabled", "0") == "1"
    channel_id = setting_get("channel_lock_id", "")
    text = (
        "📢 <b>قفل کانال</b>\n\n"
        f"وضعیت: {'✅ فعال' if enabled else '❌ غیرفعال'}\n"
        f"آیدی کانال: <code>{esc(channel_id or 'تنظیم نشده')}</code>\n\n"
        "⚠️ برای کارکرد صحیح، ربات باید ادمین کانال باشد."
    )
    kb = types.InlineKeyboardMarkup()
    kb.add(types.InlineKeyboardButton(
        f"{'⏹ غیرفعال کردن' if enabled else '▶️ فعال کردن'} قفل کانال",
        callback_data="admin:channel:toggle",
    ))
    kb.add(types.InlineKeyboardButton("📢 تنظیم آیدی کانال", callback_data="admin:channel:set_id"))
    kb.add(types.InlineKeyboardButton("🔙 بازگشت", callback_data="admin:panel"))
    bot.answer_callback_query(call.id)
    send_or_edit(call, text, kb)


def _cb_reseller_panel(call, uid: int):
    user = get_user(uid)
    if not user or not user["is_reseller"]:
        bot.answer_callback_query(call.id, "دسترسی ندارید.", show_alert=True)
        return
    pkgs = get_packages()
    text = "🤝 <b>قیمت‌های ویژه نمایندگی شما</b>\n\n"
    for p in pkgs:
        rp = get_reseller_price(uid, p["id"])
        price = rp if rp is not None else p["price"]
        text += f"📦 {esc(p['name'])}: {fmt_price(price)} تومان\n"
    kb = types.InlineKeyboardMarkup()
    kb.add(types.InlineKeyboardButton("🛒 خرید کانفیگ", callback_data="buy:start"))
    kb.add(types.InlineKeyboardButton("🔙 بازگشت",      callback_data="nav:main"))
    send_or_edit(call, text, kb)


# ──────────────────────────────────────────────
# Backup
# ──────────────────────────────────────────────
def do_backup(notify_user_id: int | None = None):
    target_chat = setting_get("backup_chat_id", "").strip()
    targets = list(ADMIN_IDS)
    if target_chat and target_chat not in [str(a) for a in targets]:
        try:
            targets.append(int(target_chat))
        except Exception:
            targets.append(target_chat)

    backup_name = f"backup_{datetime.now().strftime('%Y%m%d_%H%M%S')}.db"
    try:
        shutil.copy(DB_NAME, backup_name)
        with open(backup_name, "rb") as f:
            for t in targets:
                try:
                    bot.send_document(t, f, caption=f"💾 بکاپ دیتابیس — {now_str()}")
                    f.seek(0)
                except Exception:
                    pass
        os.remove(backup_name)
        if notify_user_id:
            bot.send_message(notify_user_id, "✅ بکاپ با موفقیت ارسال شد.")
    except Exception as e:
        if notify_user_id:
            bot.send_message(notify_user_id, f"❌ خطا در بکاپ: {e}")


def schedule_backup():
    global _backup_timer
    cancel_backup()
    if setting_get("auto_backup_enabled", "0") != "1":
        return
    try:
        hours = int(setting_get("backup_interval_hours", "24"))
    except Exception:
        hours = 24
    _backup_timer = threading.Timer(hours * 3600, _run_scheduled_backup)
    _backup_timer.daemon = True
    _backup_timer.start()


def _run_scheduled_backup():
    do_backup()
    schedule_backup()  # reschedule


def cancel_backup():
    global _backup_timer
    if _backup_timer:
        _backup_timer.cancel()
        _backup_timer = None


# ──────────────────────────────────────────────
# Text / media state machine
# ──────────────────────────────────────────────
@bot.message_handler(content_types=["text", "photo", "document"])
def universal_handler(message):
    uid = message.from_user.id
    ensure_user(message.from_user)

    # Channel lock check (skip for /start and admin)
    if message.content_type == "text" and message.text and message.text.startswith("/start"):
        state_clear(uid)
        show_main_menu(message)
        return

    if not is_admin(uid) and not check_channel_membership(uid):
        channel_id = setting_get("channel_lock_id", "").strip()
        kb = types.InlineKeyboardMarkup()
        if channel_id:
            join_url = f"https://t.me/{channel_id.lstrip('@')}"
            kb.add(types.InlineKeyboardButton("📢 عضویت در کانال", url=join_url))
        kb.add(types.InlineKeyboardButton("✅ بررسی عضویت", callback_data="check_membership"))
        bot.send_message(uid, "⚠️ <b>برای استفاده از ربات باید در کانال ما عضو باشید.</b>", reply_markup=kb)
        return

    st_name = state_name(uid)
    st_data = state_data(uid)

    try:
        # ── Broadcast ──
        if st_name == "admin_broadcast_all" and is_admin(uid):
            users = get_users_for_broadcast()
            sent = sum(1 for u in users if _safe_copy(u["user_id"], message))
            state_clear(uid)
            bot.send_message(uid, f"✅ پیام برای {sent} کاربر ارسال شد.", reply_markup=kb_admin_panel())
            return

        if st_name == "admin_broadcast_customers" and is_admin(uid):
            users = get_users_for_broadcast(has_purchase=True)
            sent = sum(1 for u in users if _safe_copy(u["user_id"], message))
            state_clear(uid)
            bot.send_message(uid, f"✅ پیام برای {sent} مشتری ارسال شد.", reply_markup=kb_admin_panel())
            return

        # ── Wallet amount entry ──
        if st_name == "await_wallet_amount":
            amount = parse_int(message.text or "")
            if amount is None or amount <= 0:
                bot.send_message(uid, "⚠️ لطفاً مبلغ معتبر وارد کنید.", reply_markup=back_button("main"))
                return
            # Save amount and show payment method selection
            state_set(uid, "await_wallet_amount", amount=amount)
            kb = types.InlineKeyboardMarkup()
            kb.row(
                types.InlineKeyboardButton("💳 کارت به کارت", callback_data="wallet:charge:card"),
                types.InlineKeyboardButton("💰 ارز دیجیتال",  callback_data="wallet:charge:crypto"),
            )
            kb.add(types.InlineKeyboardButton("🔙 بازگشت", callback_data="wallet:charge"))
            bot.send_message(uid, f"💰 مبلغ <b>{fmt_price(amount)}</b> تومان ثبت شد.\nروش پرداخت را انتخاب کنید:", reply_markup=kb)
            return

        # ── Wallet card receipt ──
        if st_name == "await_wallet_receipt":
            payment_id = st_data.get("payment_id")
            file_id = None
            text_val = message.text or ""
            if message.photo:
                file_id = message.photo[-1].file_id
            elif message.document:
                file_id = message.document.file_id
            update_payment_receipt(payment_id, file_id, text_val.strip())
            state_clear(uid)
            bot.send_message(uid, "✅ رسید دریافت شد و برای بررسی ادمین ارسال گردید.", reply_markup=kb_main(uid))
            send_payment_to_admins(payment_id)
            return

        # ── Crypto receipt (wallet or purchase) ──
        if st_name == "await_crypto_receipt":
            payment_id = st_data.get("payment_id")
            file_id = None
            text_val = message.text or ""
            if message.photo:
                file_id = message.photo[-1].file_id
            elif message.document:
                file_id = message.document.file_id
            update_payment_receipt(payment_id, file_id, text_val.strip())
            state_clear(uid)
            bot.send_message(uid, "✅ اطلاعات پرداخت دریافت شد و برای بررسی ارسال گردید.", reply_markup=kb_main(uid))
            send_payment_to_admins(payment_id)
            return

        # ── Purchase card receipt ──
        if st_name == "await_purchase_receipt":
            payment_id = st_data.get("payment_id")
            file_id = None
            text_val = message.text or ""
            if message.photo:
                file_id = message.photo[-1].file_id
            elif message.document:
                file_id = message.document.file_id
            update_payment_receipt(payment_id, file_id, text_val.strip())
            state_clear(uid)
            bot.send_message(uid, "✅ رسید دریافت شد و برای بررسی ادمین ارسال گردید.", reply_markup=kb_main(uid))
            send_payment_to_admins(payment_id)
            return

        # ── Admin: type add/edit ──
        if st_name == "admin_add_type" and is_admin(uid):
            name = (message.text or "").strip()
            if not name:
                bot.send_message(uid, "⚠️ نام نوع نمی‌تواند خالی باشد.", reply_markup=back_button("admin:types"))
                return
            try:
                add_type(name)
                state_clear(uid)
                bot.send_message(uid, "✅ نوع جدید ثبت شد.", reply_markup=kb_admin_panel())
            except sqlite3.IntegrityError:
                bot.send_message(uid, "⚠️ این نوع قبلاً ثبت شده است.", reply_markup=back_button("admin:types"))
            return

        if st_name == "admin_edit_type" and is_admin(uid):
            new_name = (message.text or "").strip()
            if not new_name:
                bot.send_message(uid, "⚠️ نام معتبر وارد کنید.", reply_markup=back_button("admin:types"))
                return
            update_type(st_data["type_id"], new_name)
            state_clear(uid)
            bot.send_message(uid, "✅ نوع ویرایش شد.", reply_markup=kb_admin_panel())
            return

        # ── Admin: add package steps ──
        if st_name == "admin_add_package_name" and is_admin(uid):
            pkg_name = (message.text or "").strip()
            if not pkg_name:
                bot.send_message(uid, "⚠️ نام پکیج را صحیح وارد کنید.", reply_markup=back_button("admin:add_package"))
                return
            state_set(uid, "admin_add_package_volume", type_id=st_data["type_id"], package_name=pkg_name)
            bot.send_message(uid, "🔋 حجم پکیج را به گیگ وارد کنید:", reply_markup=back_button("admin:add_package"))
            return

        if st_name == "admin_add_package_volume" and is_admin(uid):
            volume = parse_int(message.text or "")
            if volume is None or volume < 0:
                bot.send_message(uid, "⚠️ حجم معتبر وارد کنید.", reply_markup=back_button("admin:add_package"))
                return
            state_set(uid, "admin_add_package_duration",
                      type_id=st_data["type_id"], package_name=st_data["package_name"], volume=volume)
            bot.send_message(uid, "⏰ مدت پکیج را به روز وارد کنید:", reply_markup=back_button("admin:add_package"))
            return

        if st_name == "admin_add_package_duration" and is_admin(uid):
            duration = parse_int(message.text or "")
            if duration is None or duration < 0:
                bot.send_message(uid, "⚠️ مدت معتبر وارد کنید.", reply_markup=back_button("admin:add_package"))
                return
            state_set(uid, "admin_add_package_price",
                      type_id=st_data["type_id"], package_name=st_data["package_name"],
                      volume=st_data["volume"], duration=duration)
            bot.send_message(uid, "💰 قیمت پکیج را به تومان وارد کنید. (برای تست رایگان عدد <b>0</b> بفرستید)", reply_markup=back_button("admin:add_package"))
            return

        if st_name == "admin_add_package_price" and is_admin(uid):
            price = parse_int(message.text or "")
            if price is None or price < 0:
                bot.send_message(uid, "⚠️ قیمت معتبر وارد کنید.", reply_markup=back_button("admin:add_package"))
                return
            add_package(st_data["type_id"], st_data["package_name"], st_data["volume"], st_data["duration"], price)
            state_clear(uid)
            bot.send_message(uid, "✅ پکیج با موفقیت ثبت شد.", reply_markup=kb_admin_panel())
            return

        # ── Admin: edit package fields ──
        if st_name == "admin_edit_pkg_name" and is_admin(uid):
            name = (message.text or "").strip()
            if not name:
                bot.send_message(uid, "⚠️ نام معتبر وارد کنید.", reply_markup=back_button("admin:pkgs"))
                return
            update_package_field(st_data["pkg_id"], "name", name)
            state_clear(uid)
            bot.send_message(uid, "✅ نام پکیج ویرایش شد.", reply_markup=kb_admin_panel())
            return

        if st_name == "admin_edit_pkg_price" and is_admin(uid):
            price = parse_int(message.text or "")
            if price is None or price < 0:
                bot.send_message(uid, "⚠️ قیمت معتبر وارد کنید.", reply_markup=back_button("admin:pkgs"))
                return
            update_package_field(st_data["pkg_id"], "price", price)
            state_clear(uid)
            bot.send_message(uid, "✅ قیمت پکیج ویرایش شد.", reply_markup=kb_admin_panel())
            return

        if st_name == "admin_edit_pkg_vol" and is_admin(uid):
            vol = parse_int(message.text or "")
            if vol is None or vol < 0:
                bot.send_message(uid, "⚠️ حجم معتبر وارد کنید.", reply_markup=back_button("admin:pkgs"))
                return
            update_package_field(st_data["pkg_id"], "volume_gb", vol)
            state_clear(uid)
            bot.send_message(uid, "✅ حجم پکیج ویرایش شد.", reply_markup=kb_admin_panel())
            return

        if st_name == "admin_edit_pkg_dur" and is_admin(uid):
            dur = parse_int(message.text or "")
            if dur is None or dur < 0:
                bot.send_message(uid, "⚠️ مدت معتبر وارد کنید.", reply_markup=back_button("admin:pkgs"))
                return
            update_package_field(st_data["pkg_id"], "duration_days", dur)
            state_clear(uid)
            bot.send_message(uid, "✅ مدت پکیج ویرایش شد.", reply_markup=kb_admin_panel())
            return

        # ── Admin: add config steps ──
        if st_name == "admin_add_config_service" and is_admin(uid):
            service_name = (message.text or "").strip()
            if not service_name:
                bot.send_message(uid, "⚠️ نام سرویس را وارد کنید.", reply_markup=back_button("admin:add_config"))
                return
            state_set(uid, "admin_add_config_text",
                      package_id=st_data["package_id"], type_id=st_data["type_id"], service_name=service_name)
            bot.send_message(uid, "💝 متن کانفیگ را ارسال کنید:", reply_markup=back_button("admin:add_config"))
            return

        if st_name == "admin_add_config_text" and is_admin(uid):
            config_text = (message.text or "").strip()
            if not config_text:
                bot.send_message(uid, "⚠️ متن کانفیگ را وارد کنید.", reply_markup=back_button("admin:add_config"))
                return
            state_set(uid, "admin_add_config_link",
                      package_id=st_data["package_id"], type_id=st_data["type_id"],
                      service_name=st_data["service_name"], config_text=config_text)
            bot.send_message(uid, "🔗 لینک استعلام را ارسال کنید.\nبدون لینک؟ یک خط تیره <code>-</code> بفرستید.", reply_markup=back_button("admin:add_config"))
            return

        if st_name == "admin_add_config_link" and is_admin(uid):
            inquiry_link = (message.text or "").strip()
            if inquiry_link == "-":
                inquiry_link = ""
            add_config(st_data["type_id"], st_data["package_id"], st_data["service_name"], st_data["config_text"], inquiry_link)
            state_clear(uid)
            bot.send_message(uid, "✅ کانفیگ با موفقیت ثبت شد.", reply_markup=kb_admin_panel())
            return

        # ── Admin: settings ──
        if st_name == "admin_set_support" and is_admin(uid):
            setting_set("support_username", (message.text or "").strip())
            state_clear(uid)
            bot.send_message(uid, "✅ آیدی پشتیبانی ذخیره شد.", reply_markup=kb_admin_panel())
            return

        if st_name == "admin_set_card" and is_admin(uid):
            setting_set("payment_card", normalize_text_number(message.text or ""))
            state_clear(uid)
            bot.send_message(uid, "✅ شماره کارت ذخیره شد.", reply_markup=kb_admin_panel())
            return

        if st_name == "admin_set_bank" and is_admin(uid):
            setting_set("payment_bank", (message.text or "").strip())
            state_clear(uid)
            bot.send_message(uid, "✅ نام بانک ذخیره شد.", reply_markup=kb_admin_panel())
            return

        if st_name == "admin_set_owner" and is_admin(uid):
            setting_set("payment_owner", (message.text or "").strip())
            state_clear(uid)
            bot.send_message(uid, "✅ نام صاحب کارت ذخیره شد.", reply_markup=kb_admin_panel())
            return

        if st_name == "admin_set_crypto" and is_admin(uid):
            currency = st_data.get("currency", "")
            addr = (message.text or "").strip()
            setting_set(f"crypto_{currency}", addr)
            state_clear(uid)
            disp = next((d for k, d, _ in CRYPTO_CURRENCIES if k == currency), currency)
            bot.send_message(uid, f"✅ آدرس ولت {disp} ذخیره شد.", reply_markup=kb_admin_panel())
            return

        # ── Admin: payment approvals ──
        if st_name == "admin_payment_approve_note" and is_admin(uid):
            payment_id = st_data["payment_id"]
            note = (message.text or "").strip() or "واریزی شما تأیید شد."
            finish_card_payment_approval(payment_id, note, approved=True)
            state_clear(uid)
            bot.send_message(uid, "✅ درخواست با موفقیت تأیید شد.", reply_markup=kb_admin_panel())
            return

        if st_name == "admin_payment_reject_note" and is_admin(uid):
            payment_id = st_data["payment_id"]
            note = (message.text or "").strip() or "رسید شما رد شد."
            finish_card_payment_approval(payment_id, note, approved=False)
            state_clear(uid)
            bot.send_message(uid, "✅ درخواست با موفقیت رد شد.", reply_markup=kb_admin_panel())
            return

        # ── Admin: balance ──
        if st_name == "admin_bal_increase" and is_admin(uid):
            amount = parse_int(message.text or "")
            if amount is None or amount <= 0:
                bot.send_message(uid, "⚠️ مبلغ معتبر وارد کنید.", reply_markup=back_button("admin:panel"))
                return
            target_uid = st_data["target_user_id"]
            update_balance(target_uid, amount)
            state_clear(uid)
            u = get_user(target_uid)
            bot.send_message(uid, f"✅ موجودی افزایش یافت.\nموجودی جدید: {fmt_price(u['balance'])} تومان", reply_markup=kb_admin_panel())
            try:
                bot.send_message(target_uid, f"💰 موجودی کیف پول شما <b>{fmt_price(amount)}</b> تومان افزایش یافت.")
            except Exception:
                pass
            return

        if st_name == "admin_bal_decrease" and is_admin(uid):
            amount = parse_int(message.text or "")
            if amount is None or amount <= 0:
                bot.send_message(uid, "⚠️ مبلغ معتبر وارد کنید.", reply_markup=back_button("admin:panel"))
                return
            target_uid = st_data["target_user_id"]
            update_balance(target_uid, -amount)
            state_clear(uid)
            u = get_user(target_uid)
            bot.send_message(uid, f"✅ موجودی کاهش یافت.\nموجودی جدید: {fmt_price(u['balance'])} تومان", reply_markup=kb_admin_panel())
            try:
                bot.send_message(target_uid, f"💰 موجودی کیف پول شما <b>{fmt_price(amount)}</b> تومان کاهش یافت.")
            except Exception:
                pass
            return

        # ── Admin: reseller price ──
        if st_name == "admin_set_reseller_price" and is_admin(uid):
            price = parse_int(message.text or "")
            if price is None or price < 0:
                bot.send_message(uid, "⚠️ قیمت معتبر وارد کنید.", reply_markup=back_button("admin:panel"))
                return
            set_reseller_price(st_data["target_user_id"], st_data["pkg_id"], price)
            state_clear(uid)
            bot.send_message(uid, "✅ قیمت نمایندگی ثبت شد.", reply_markup=kb_admin_panel())
            return

        # ── Admin: backup settings ──
        if st_name == "admin_set_backup_interval" and is_admin(uid):
            hours = parse_int(message.text or "")
            if hours is None or hours < 1:
                bot.send_message(uid, "⚠️ عدد معتبر وارد کنید (حداقل 1 ساعت).", reply_markup=back_button("admin:backup"))
                return
            setting_set("backup_interval_hours", str(hours))
            state_clear(uid)
            if setting_get("auto_backup_enabled") == "1":
                schedule_backup()
            bot.send_message(uid, f"✅ فاصله بکاپ به {hours} ساعت تنظیم شد.", reply_markup=kb_admin_panel())
            return

        if st_name == "admin_set_backup_chat" and is_admin(uid):
            chat_id_val = (message.text or "").strip()
            setting_set("backup_chat_id", chat_id_val)
            state_clear(uid)
            bot.send_message(uid, "✅ مقصد بکاپ ثبت شد.", reply_markup=kb_admin_panel())
            return

        # ── Admin: channel lock ──
        if st_name == "admin_set_channel_id" and is_admin(uid):
            ch_id = (message.text or "").strip()
            setting_set("channel_lock_id", ch_id)
            state_clear(uid)
            bot.send_message(uid, f"✅ آیدی کانال ذخیره شد: <code>{esc(ch_id)}</code>", reply_markup=kb_admin_panel())
            return

    except Exception as e:
        print("TEXT_HANDLER_ERROR:", e)
        traceback.print_exc()
        state_clear(uid)
        bot.send_message(uid, "⚠️ در پردازش مرحله جاری خطایی رخ داد. لطفاً دوباره از منو ادامه دهید.", reply_markup=kb_main(uid))
        return

    # Fallback
    if message.content_type == "text":
        bot.send_message(uid, "لطفاً از دکمه‌های منو استفاده کنید.", reply_markup=kb_main(uid))


def _safe_copy(to_chat_id: int, message) -> bool:
    try:
        bot.copy_message(to_chat_id, message.chat.id, message.message_id)
        return True
    except Exception:
        return False


# ──────────────────────────────────────────────
# Bootstrap
# ──────────────────────────────────────────────
def main():
    init_db()
    set_bot_commands()
    if setting_get("auto_backup_enabled", "0") == "1":
        schedule_backup()
    print("Bot v4 is running...")
    bot.infinity_polling(skip_pending=True, timeout=30, long_polling_timeout=30)


if __name__ == "__main__":
    main()

