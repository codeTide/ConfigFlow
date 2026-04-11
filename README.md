<div align="center">

# ⚡ ConfigFlow
### ربات تلگرام مدیریت و فروش کانفیگ

[![Python](https://img.shields.io/badge/Python-3.10+-3776AB?style=for-the-badge&logo=python&logoColor=white)](https://www.python.org)
[![Telebot](https://img.shields.io/badge/pyTelegramBotAPI-4.x-26A5E4?style=for-the-badge&logo=telegram&logoColor=white)](https://github.com/eternnoir/pyTelegramBotAPI)
[![SQLite](https://img.shields.io/badge/SQLite-3-003B57?style=for-the-badge&logo=sqlite&logoColor=white)](https://www.sqlite.org)
[![License](https://img.shields.io/badge/License-MIT-yellow?style=for-the-badge)](LICENSE)
[![Stars](https://img.shields.io/github/stars/Emadhabibnia1385/ConfigFlow?style=for-the-badge&logo=github)](https://github.com/Emadhabibnia1385/ConfigFlow/stargazers)

**ربات فروش خودکار کانفیگ VPN با پشتیبانی از چندین درگاه پرداخت، سیستم نمایندگی، مدیریت کامل موجودی و پنل ادمین حرفه‌ای**

[نصب سریع](#-نصب-سریع) • [ویژگی‌ها](#-ویژگیها) • [درگاه‌های پرداخت](#-درگاههای-پرداخت) • [پنل ادمین](#-پنل-ادمین) • [پشتیبانی](#-پشتیبانی)

---

</div>

## 📖 درباره ConfigFlow

ConfigFlow یک ربات تلگرام حرفه‌ای برای فروش و مدیریت کانفیگ‌های VPN است. این ربات تمام فرآیند فروش — از انتخاب سرویس و پکیج تا پرداخت و تحویل کانفیگ — را به‌صورت خودکار انجام می‌دهد و امکانات گسترده‌ای برای مدیریت کاربران، نمایندگان و موجودی در اختیار ادمین قرار می‌دهد.

### 💡 رویکرد متفاوت: فضای ذخیره‌سازی کانفیگ

> برخلاف اکثر ربات‌های فروش VPN که مستقیماً به پنل (مثل X-UI) متصل شده و هنگام خرید کانفیگ جدید می‌سازند، **ConfigFlow با مدل ذخیره‌سازی (Stock) کار می‌کند.** ادمین کانفیگ‌های از‌پیش‌ساخته را (تکی یا دسته‌ای) در ربات ثبت می‌کند و ربات از موجودی انبار به خریداران تحویل می‌دهد. این رویکرد مزایای مهمی دارد:
>
> - 🔒 **امنیت بیشتر** — ربات هیچ دسترسی مستقیمی به پنل سرور ندارد
> - 🛡️ **استقلال کامل** — قطعی پنل یا تغییر سرور، فروش را مختل نمی‌کند
> - ✅ **کنترل کیفیت** — ادمین قبل از ثبت، کانفیگ‌ها را بررسی می‌کند
> - 📊 **مدیریت دقیق موجودی** — دید کامل به تعداد موجود، فروخته‌شده و منقضی‌شده

### 🎯 مناسب برای:

- 🛒 فروشندگان VPN که نیاز به اتوماسیون فروش دارند
- 🤝 کسب‌وکارهایی با شبکه نمایندگان فروش
- 📊 مدیرانی که نیاز به کنترل کامل موجودی و مالی دارند
- ⚡ افرادی که می‌خواهند فروش ۲۴ ساعته بدون دخالت دستی داشته باشند

---

## ✨ ویژگی‌ها

<table>
<tr>
<td width="25%" align="center">

### 🛒 فروش خودکار
✅ انتخاب نوع سرویس و پکیج  
✅ ۵ درگاه پرداخت  
✅ تحویل خودکار کانفیگ + QR Code  
✅ توضیحات سرویس پس از تحویل

</td>
<td width="25%" align="center">

### 📦 مدیریت موجودی
✅ ثبت تکی و دسته‌ای کانفیگ  
✅ حذف پیشوند/پسوند از نام‌ها  
✅ جستجوی پیشرفته  
✅ مدیریت انقضا و حذف

</td>
<td width="25%" align="center">

### 🤝 سیستم نمایندگی
✅ درخواست نمایندگی توسط کاربر  
✅ تأیید/رد توسط ادمین  
✅ قیمت‌گذاری اختصاصی هر پکیج  
✅ تست رایگان ویژه نمایندگان

</td>
<td width="25%" align="center">

### 🔐 امنیت و مدیریت
✅ وضعیت امن/ناامن کاربران  
✅ قفل کانال اجباری  
✅ قوانین خرید  
✅ بکاپ خودکار و بازیابی

</td>
</tr>
</table>

---

## 🏗️ معماری

```
                  ┌──────────────────────────────┐
                  │     ConfigFlow Telegram Bot    │
                  │      (pyTelegramBotAPI)         │
                  └───────┬──────────┬─────────────┘
                          │          │
              ┌───────────┘          └──────────┐
              ▼                                 ▼
     ┌────────────────┐               ┌────────────────┐
     │    SQLite DB    │               │   درگاه‌ها      │
     │  ConfigFlow.db  │               │                │
     ├────────────────┤               │ 💳 کارت‌به‌کارت  │
     │ • users        │               │ 💎 ارز دیجیتال  │
     │ • config_types │               │ 🏦 TetraPay     │
     │ • packages     │               │ 💰 کیف پول      │
     │ • configs      │               │ 🎁 تست رایگان   │
     │ • payments     │               └────────────────┘
     │ • purchases    │
     │ • agency_prices│
     │ • settings     │
     └────────────────┘
```

### جریان خرید:
1. کاربر **نوع سرویس** (مثلاً Vless, Shadowsocks) را انتخاب می‌کند
2. **پکیج** مورد نظر را انتخاب می‌کند (حجم، مدت، قیمت)
3. **روش پرداخت** را مشخص می‌کند
4. یک کانفیگ از موجودی **رزرو** می‌شود
5. پرداخت انجام می‌شود (اتوماتیک یا تأیید دستی ادمین)
6. **کانفیگ + QR Code** تحویل داده می‌شود
7. در صورت وجود، **توضیحات سرویس** ارسال می‌شود

---

## 💳 درگاه‌های پرداخت

ConfigFlow از **۵ روش پرداخت** پشتیبانی می‌کند:

| درگاه | نوع | تأیید | توضیحات |
|-------|-----|-------|---------|
| 💳 **کارت‌به‌کارت** | ریالی | دستی (ادمین) | آپلود رسید پرداخت |
| 💎 **ارز دیجیتال** | کریپتو | دستی (ادمین) | ۵ ارز: TRON, TON, USDT, USDC, LTC — محاسبه خودکار معادل‌سازی |
| 🏦 **TetraPay** | درگاه آنلاین | خودکار (API) | پرداخت از طریق ربات [@Tetra_Pay](https://t.me/Tetra_Pay) |
| 💰 **کیف پول** | اعتباری | آنی | پرداخت از موجودی شارژ‌شده |
| 🎁 **تست رایگان** | رایگان | خودکار | با محدودیت تعداد و بازه زمانی |

> هر درگاه قابلیت **نمایش عمومی** یا **فقط برای کاربران امن** را دارد.

---

## ⚙️ پنل ادمین

### 🧩 مدیریت انواع سرویس و پکیج

- **تعریف انواع سرویس** (نوع کانفیگ) با توضیحات اختیاری
- **ویرایش نام و توضیحات** نوع سرویس
- **تعریف پکیج‌های فروش**: نام، حجم (GB)، مدت (روز)، قیمت (تومان)
- **مرتب‌سازی ترتیب نمایش** پکیج‌ها (تغییر جایگاه)
- **ویرایش تمام فیلدهای** پکیج

### 📚 مدیریت کانفیگ‌ها

- **ثبت تکی** کانفیگ: نام سرویس، متن کانفیگ، لینک استعلام (اختیاری)
- **ثبت دسته‌ای** با ویزارد ۴ مرحله‌ای:
  - تعیین تعداد → پیشوند → پسوند → ارسال کانفیگ‌ها
  - **استخراج هوشمند نام** از بعد `#` در لینک کانفیگ
  - **حذف خودکار پیشوند و پسوند** از نام
- **مشاهده موجودی**: موجود / فروخته‌شده / منقضی‌شده
- **جستجو** بر اساس: لینک کانفیگ، متن کانفیگ، نام سرویس
- **تعیین انقضا** یا **حذف** کانفیگ
- **صفحه‌بندی** (۱۰ عدد در هر صفحه)

### 👥 مدیریت کاربران

- **مشاهده اطلاعات** هر کاربر: نام، موجودی، تعداد خرید، مبلغ کل
- **تغییر وضعیت**: امن ↔ ناامن (جهت محدودکردن دسترسی به درگاه‌ها)
- **فعال/غیرفعال کردن نمایندگی**
- **افزایش / کاهش موجودی** (با اطلاع‌رسانی به کاربر)
- **ثبت کانفیگ مستقیم** برای کاربر
- **مشاهده و مدیریت** کانفیگ‌های کاربر (لغو انتساب)
- **تعیین قیمت اختصاصی** هر پکیج برای نمایندگان

### 📣 پیام‌رسانی

- **فوروارد همگانی** به تمام کاربران
- **فوروارد به مشتریان** (فقط خریداران)

### ⚙️ تنظیمات

| تنظیم | توضیحات |
|-------|---------|
| 💳 **کارت بانکی** | شماره کارت، بانک، نام صاحب حساب، فعال/غیرفعال، نمایش عمومی یا امن |
| 💎 **ارز دیجیتال** | آدرس ۵ کیف پول، فعال/غیرفعال، نمایش عمومی یا امن |
| 🏦 **TetraPay** | کلید API، حالت تلگرام، حالت وب، فعال/غیرفعال، نمایش عمومی یا امن |
| 🎧 **پشتیبانی** | آیدی تلگرام پشتیبانی + لینک خارجی (قابل انتخاب نوع نمایش) |
| 📢 **قفل کانال** | آیدی عددی کانال (عضویت اجباری) |
| ✏️ **متن استارت** | ویرایش پیام خوش‌آمدگویی (HTML) |
| 📜 **قوانین خرید** | فعال/غیرفعال + متن قوانین — کاربر باید قبل از اولین خرید قوانین را بپذیرد |
| 🎁 **تست رایگان** | روشن/خاموش + تعداد تست و بازه زمانی ویژه نمایندگان |

### 💾 بکاپ و بازیابی

- **بکاپ دستی** دریافت فایل دیتابیس
- **بکاپ خودکار** به شخص یا کانال (قابل تنظیم بازه زمانی)
- **بازیابی** از فایل بکاپ

---

## 👤 امکانات کاربران

| قابلیت | توضیحات |
|--------|---------|
| 🛒 **خرید کانفیگ** | انتخاب نوع، پکیج، پرداخت و دریافت خودکار |
| 📦 **کانفیگ‌های من** | مشاهده خریدها + QR Code + لینک استعلام |
| 🔄 **تمدید** | درخواست تمدید با انتخاب پکیج جدید از داخل «کانفیگ‌های من» |
| 💰 **کیف پول** | شارژ موجودی + مشاهده مانده |
| 🎁 **تست رایگان** | دریافت تست با محدودیت تعداد |
| 👤 **پروفایل** | مشاهده اطّلاعات حساب |
| 🤝 **درخواست نمایندگی** | ارسال درخواست با توضیحات (حجم فروش، کانال، شناسه پشتیبان) |
| 🎧 **پشتیبانی** | ارتباط با پشتیبانی (تلگرام یا لینک) |

---

## 🚀 نصب سریع

### نصب خودکار روی سرور (پیشنهادی) ⚡

```bash
sudo bash <(curl -fsSL https://raw.githubusercontent.com/Emadhabibnia1385/ConfigFlow/main/install.sh)
```

اسکریپت به‌صورت خودکار:
- پیش‌نیازها را نصب می‌کند
- پروژه را کلون می‌کند
- فایل `.env` را می‌سازد (توکن ربات و آیدی ادمین را می‌پرسد)
- سرویس systemd را راه‌اندازی می‌کند

---

### نصب دستی 🔧

```bash
# کلون پروژه
git clone https://github.com/Emadhabibnia1385/ConfigFlow.git
cd ConfigFlow

# نصب وابستگی‌ها
pip install -r requirements.txt

# تنظیم فایل محیطی
cp env.example .env
nano .env
```

فایل `.env` را ویرایش کنید:

```env
BOT_TOKEN=1234567890:YOUR_BOT_TOKEN
ADMIN_IDS=123456789
DB_NAME=ConfigFlow.db
```

> برای چند ادمین، آیدی‌ها را با کاما جدا کنید: `ADMIN_IDS=111,222,333`

```bash
# اجرای فاز ۱ نسخه PHP (Webhook)
php -S 0.0.0.0:8080 -t php/public
```

```bash
# ساخت اسکیمای MySQL (فاز ۲)
php php/scripts/init_db.php

# انتقال داده‌های اصلی از SQLite به MySQL (فاز ۲)
php php/scripts/migrate_sqlite_to_mysql.php /path/to/configflow.db
```

فاز ۳ مهاجرت (فعلی) در نسخه PHP:
- روت کردن آپدیت‌ها با `UpdateRouter`
- پشتیبانی از Callback Query برای `nav:main`, `profile`, `support`, `my_configs`
- پشتیبانی از Callback Query برای `referral:menu` + لینک اشتراک‌گذاری دعوت
- منوی اصلی داینامیک و نمایش پروفایل/پشتیبانی/کانفیگ‌های من در PHP
- فلو اولیه `wallet charge` و `buy flow` (انتخاب نوع/پکیج و پرداخت کیف پول)
- مدیریت ادمین برای تایید/رد درخواست شارژ کیف پول در PHP
- اضافه شدن مسیرهای پرداخت `card/crypto/tetrapay` (نسخه اولیه) در PHP
- تکمیل تحویل سفارش از `pending_orders` با صف تحویل ادمین
- اتصال اولیه API برای TetraPay + انتخاب ارز در پرداخت کریپتو
- Card receipt flow در PHP (state + ثبت رسید + بررسی ادمین)
- Crypto TX Hash flow در PHP + بررسی ادمین
- بهبود idempotency برای بررسی پرداخت TetraPay
- ثبت payload درگاه‌ها (TetraPay/crypto) در payment برای دیباگ و audit
- verify on-chain برای crypto در مسیر ادمین (LTC / TRON / TON / USDT(BEP20) / USDC(BEP20))
- افزودن amount/rate validation برای تایید کریپتو (با tolerance)
- استفاده از مقدار on-chain در amount-check (در صورت دسترسی) با fallback به claimed amount کاربر
- تکمیل اولیه `test:start` و `agency:request` با ثبت درخواست متنی و ارسال برای ادمین
- DB-backed tracking برای `free_test_requests` و `agency_requests` با وضعیت `pending/approved/rejected`
- نمایش خلاصه آخرین خریدها در `my_configs`

```bash
# ساخت اسکیمای MySQL (فاز ۲)
php php/scripts/init_db.php

# انتقال داده‌های اصلی از SQLite به MySQL (فاز ۲)
php php/scripts/migrate_sqlite_to_mysql.php /path/to/configflow.db
```

فاز ۳ مهاجرت (فعلی) در نسخه PHP:
- روت کردن آپدیت‌ها با `UpdateRouter`
- پشتیبانی از Callback Query برای `nav:main`, `profile`, `support`, `my_configs`
- پشتیبانی از Callback Query برای `referral:menu` + لینک اشتراک‌گذاری دعوت
- منوی اصلی داینامیک و نمایش پروفایل/پشتیبانی/کانفیگ‌های من در PHP
- فلو اولیه `wallet charge` و `buy flow` (انتخاب نوع/پکیج و پرداخت کیف پول)
- مدیریت ادمین برای تایید/رد درخواست شارژ کیف پول در PHP
- اضافه شدن مسیرهای پرداخت `card/crypto/tetrapay` (نسخه اولیه) در PHP
- تکمیل تحویل سفارش از `pending_orders` با صف تحویل ادمین
- اتصال اولیه API برای TetraPay + انتخاب ارز در پرداخت کریپتو
- Card receipt flow در PHP (state + ثبت رسید + بررسی ادمین)
- Crypto TX Hash flow در PHP + بررسی ادمین
- بهبود idempotency برای بررسی پرداخت TetraPay
- ثبت payload درگاه‌ها (TetraPay/crypto) در payment برای دیباگ و audit
- verify on-chain برای crypto در مسیر ادمین (LTC / TRON / TON / USDT(BEP20) / USDC(BEP20))
- افزودن amount/rate validation برای تایید کریپتو (با tolerance)
- استفاده از مقدار on-chain در amount-check (در صورت دسترسی) با fallback به claimed amount کاربر
- تکمیل اولیه `test:start` و `agency:request` با ثبت درخواست متنی و ارسال برای ادمین
- نمایش خلاصه آخرین خریدها در `my_configs`

```bash
# ساخت اسکیمای MySQL (فاز ۲)
php php/scripts/init_db.php

# انتقال داده‌های اصلی از SQLite به MySQL (فاز ۲)
php php/scripts/migrate_sqlite_to_mysql.php /path/to/configflow.db
```

فاز ۳ مهاجرت (فعلی) در نسخه PHP:
- روت کردن آپدیت‌ها با `UpdateRouter`
- پشتیبانی از Callback Query برای `nav:main`, `profile`, `support`, `my_configs`
- پشتیبانی از Callback Query برای `referral:menu` + لینک اشتراک‌گذاری دعوت
- منوی اصلی داینامیک و نمایش پروفایل/پشتیبانی/کانفیگ‌های من در PHP
- فلو اولیه `wallet charge` و `buy flow` (انتخاب نوع/پکیج و پرداخت کیف پول)
- مدیریت ادمین برای تایید/رد درخواست شارژ کیف پول در PHP
- اضافه شدن مسیرهای پرداخت `card/crypto/tetrapay` (نسخه اولیه) در PHP
- تکمیل تحویل سفارش از `pending_orders` با صف تحویل ادمین
- اتصال اولیه API برای TetraPay + انتخاب ارز در پرداخت کریپتو
- Card receipt flow در PHP (state + ثبت رسید + بررسی ادمین)
- Crypto TX Hash flow در PHP + بررسی ادمین
- بهبود idempotency برای بررسی پرداخت TetraPay
- ثبت payload درگاه‌ها (TetraPay/crypto) در payment برای دیباگ و audit
- verify on-chain برای crypto در مسیر ادمین (LTC / TRON / TON / USDT(BEP20) / USDC(BEP20))
- افزودن amount/rate validation برای تایید کریپتو (با tolerance)

```bash
# ساخت اسکیمای MySQL (فاز ۲)
php php/scripts/init_db.php

# انتقال داده‌های اصلی از SQLite به MySQL (فاز ۲)
php php/scripts/migrate_sqlite_to_mysql.php /path/to/configflow.db
```

فاز ۳ مهاجرت (فعلی) در نسخه PHP:
- روت کردن آپدیت‌ها با `UpdateRouter`
- پشتیبانی از Callback Query برای `nav:main`, `profile`, `support`, `my_configs`
- پشتیبانی از Callback Query برای `referral:menu` + لینک اشتراک‌گذاری دعوت
- منوی اصلی داینامیک و نمایش پروفایل/پشتیبانی/کانفیگ‌های من در PHP
- فلو اولیه `wallet charge` و `buy flow` (انتخاب نوع/پکیج و پرداخت کیف پول)
- مدیریت ادمین برای تایید/رد درخواست شارژ کیف پول در PHP
- اضافه شدن مسیرهای پرداخت `card/crypto/tetrapay` (نسخه اولیه) در PHP
- تکمیل تحویل سفارش از `pending_orders` با صف تحویل ادمین
- اتصال اولیه API برای TetraPay + انتخاب ارز در پرداخت کریپتو
- Card receipt flow در PHP (state + ثبت رسید + بررسی ادمین)
- Crypto TX Hash flow در PHP + بررسی ادمین
- بهبود idempotency برای بررسی پرداخت TetraPay
- ثبت payload درگاه‌ها (TetraPay/crypto) در payment برای دیباگ و audit
- شروع verify on-chain برای crypto (فعلاً LTC/TRON در مسیر ادمین)
- افزودن amount/rate validation برای تایید کریپتو (با tolerance)

```bash
# ساخت اسکیمای MySQL (فاز ۲)
php php/scripts/init_db.php

# انتقال داده‌های اصلی از SQLite به MySQL (فاز ۲)
php php/scripts/migrate_sqlite_to_mysql.php /path/to/configflow.db
```

فاز ۳ مهاجرت (فعلی) در نسخه PHP:
- روت کردن آپدیت‌ها با `UpdateRouter`
- پشتیبانی از Callback Query برای `nav:main`, `profile`, `support`, `my_configs`
- پشتیبانی از Callback Query برای `referral:menu` + لینک اشتراک‌گذاری دعوت
- منوی اصلی داینامیک و نمایش پروفایل/پشتیبانی/کانفیگ‌های من در PHP
- فلو اولیه `wallet charge` و `buy flow` (انتخاب نوع/پکیج و پرداخت کیف پول)
- مدیریت ادمین برای تایید/رد درخواست شارژ کیف پول در PHP
- اضافه شدن مسیرهای پرداخت `card/crypto/tetrapay` (نسخه اولیه) در PHP
- تکمیل تحویل سفارش از `pending_orders` با صف تحویل ادمین
- اتصال اولیه API برای TetraPay + انتخاب ارز در پرداخت کریپتو
- Card receipt flow در PHP (state + ثبت رسید + بررسی ادمین)
- Crypto TX Hash flow در PHP + بررسی ادمین
- بهبود idempotency برای بررسی پرداخت TetraPay
- ثبت payload درگاه‌ها (TetraPay/crypto) در payment برای دیباگ و audit
- شروع verify on-chain برای crypto (فعلاً LTC/TRON در مسیر ادمین)

```bash
# ساخت اسکیمای MySQL (فاز ۲)
php php/scripts/init_db.php

# انتقال داده‌های اصلی از SQLite به MySQL (فاز ۲)
php php/scripts/migrate_sqlite_to_mysql.php /path/to/configflow.db
```

فاز ۳ مهاجرت (فعلی) در نسخه PHP:
- روت کردن آپدیت‌ها با `UpdateRouter`
- پشتیبانی از Callback Query برای `nav:main`, `profile`, `support`, `my_configs`
- پشتیبانی از Callback Query برای `referral:menu` + لینک اشتراک‌گذاری دعوت
- منوی اصلی داینامیک و نمایش پروفایل/پشتیبانی/کانفیگ‌های من در PHP
- فلو اولیه `wallet charge` و `buy flow` (انتخاب نوع/پکیج و پرداخت کیف پول)
- مدیریت ادمین برای تایید/رد درخواست شارژ کیف پول در PHP
- اضافه شدن مسیرهای پرداخت `card/crypto/tetrapay` (نسخه اولیه) در PHP
- تکمیل تحویل سفارش از `pending_orders` با صف تحویل ادمین
- اتصال اولیه API برای TetraPay + انتخاب ارز در پرداخت کریپتو
- Card receipt flow در PHP (state + ثبت رسید + بررسی ادمین)
- Crypto TX Hash flow در PHP + بررسی ادمین
- بهبود idempotency برای بررسی پرداخت TetraPay
- ثبت payload درگاه‌ها (TetraPay/crypto) در payment برای دیباگ و audit

```bash
# ساخت اسکیمای MySQL (فاز ۲)
php php/scripts/init_db.php

# انتقال داده‌های اصلی از SQLite به MySQL (فاز ۲)
php php/scripts/migrate_sqlite_to_mysql.php /path/to/configflow.db
```

فاز ۳ مهاجرت (فعلی) در نسخه PHP:
- روت کردن آپدیت‌ها با `UpdateRouter`
- پشتیبانی از Callback Query برای `nav:main`, `profile`, `support`, `my_configs`
- پشتیبانی از Callback Query برای `referral:menu` + لینک اشتراک‌گذاری دعوت
- منوی اصلی داینامیک و نمایش پروفایل/پشتیبانی/کانفیگ‌های من در PHP
- فلو اولیه `wallet charge` و `buy flow` (انتخاب نوع/پکیج و پرداخت کیف پول)
- مدیریت ادمین برای تایید/رد درخواست شارژ کیف پول در PHP
- اضافه شدن مسیرهای پرداخت `card/crypto/tetrapay` (نسخه اولیه) در PHP
- تکمیل تحویل سفارش از `pending_orders` با صف تحویل ادمین
- اتصال اولیه API برای TetraPay + انتخاب ارز در پرداخت کریپتو
- Card receipt flow در PHP (state + ثبت رسید + بررسی ادمین)
- Crypto TX Hash flow در PHP + بررسی ادمین
- بهبود idempotency برای بررسی پرداخت TetraPay

```bash
# ساخت اسکیمای MySQL (فاز ۲)
php php/scripts/init_db.php

# انتقال داده‌های اصلی از SQLite به MySQL (فاز ۲)
php php/scripts/migrate_sqlite_to_mysql.php /path/to/configflow.db
```

فاز ۳ مهاجرت (فعلی) در نسخه PHP:
- روت کردن آپدیت‌ها با `UpdateRouter`
- پشتیبانی از Callback Query برای `nav:main`, `profile`, `support`, `my_configs`
- پشتیبانی از Callback Query برای `referral:menu` + لینک اشتراک‌گذاری دعوت
- منوی اصلی داینامیک و نمایش پروفایل/پشتیبانی/کانفیگ‌های من در PHP
- فلو اولیه `wallet charge` و `buy flow` (انتخاب نوع/پکیج و پرداخت کیف پول)
- مدیریت ادمین برای تایید/رد درخواست شارژ کیف پول در PHP
- اضافه شدن مسیرهای پرداخت `card/crypto/tetrapay` (نسخه اولیه) در PHP
- تکمیل تحویل سفارش از `pending_orders` با صف تحویل ادمین
- اتصال اولیه API برای TetraPay + انتخاب ارز در پرداخت کریپتو
- Card receipt flow در PHP (state + ثبت رسید + بررسی ادمین)

```bash
# ساخت اسکیمای MySQL (فاز ۲)
php php/scripts/init_db.php

# انتقال داده‌های اصلی از SQLite به MySQL (فاز ۲)
php php/scripts/migrate_sqlite_to_mysql.php /path/to/configflow.db
```

فاز ۳ مهاجرت (فعلی) در نسخه PHP:
- روت کردن آپدیت‌ها با `UpdateRouter`
- پشتیبانی از Callback Query برای `nav:main`, `profile`, `support`, `my_configs`
- پشتیبانی از Callback Query برای `referral:menu` + لینک اشتراک‌گذاری دعوت
- منوی اصلی داینامیک و نمایش پروفایل/پشتیبانی/کانفیگ‌های من در PHP
- فلو اولیه `wallet charge` و `buy flow` (انتخاب نوع/پکیج و پرداخت کیف پول)
- مدیریت ادمین برای تایید/رد درخواست شارژ کیف پول در PHP
- اضافه شدن مسیرهای پرداخت `card/crypto/tetrapay` (نسخه اولیه) در PHP
- تکمیل تحویل سفارش از `pending_orders` با صف تحویل ادمین
- اتصال اولیه API برای TetraPay + انتخاب ارز در پرداخت کریپتو

```bash
# ساخت اسکیمای MySQL (فاز ۲)
php php/scripts/init_db.php

# انتقال داده‌های اصلی از SQLite به MySQL (فاز ۲)
php php/scripts/migrate_sqlite_to_mysql.php /path/to/configflow.db
```

فاز ۳ مهاجرت (فعلی) در نسخه PHP:
- روت کردن آپدیت‌ها با `UpdateRouter`
- پشتیبانی از Callback Query برای `nav:main`, `profile`, `support`, `my_configs`
- پشتیبانی از Callback Query برای `referral:menu` + لینک اشتراک‌گذاری دعوت
- منوی اصلی داینامیک و نمایش پروفایل/پشتیبانی/کانفیگ‌های من در PHP
- فلو اولیه `wallet charge` و `buy flow` (انتخاب نوع/پکیج و پرداخت کیف پول)
- مدیریت ادمین برای تایید/رد درخواست شارژ کیف پول در PHP
- اضافه شدن مسیرهای پرداخت `card/crypto/tetrapay` (نسخه اولیه) در PHP
- تکمیل تحویل سفارش از `pending_orders` با صف تحویل ادمین

```bash
# ساخت اسکیمای MySQL (فاز ۲)
php php/scripts/init_db.php

# انتقال داده‌های اصلی از SQLite به MySQL (فاز ۲)
php php/scripts/migrate_sqlite_to_mysql.php /path/to/configflow.db
```

فاز ۳ مهاجرت (فعلی) در نسخه PHP:
- روت کردن آپدیت‌ها با `UpdateRouter`
- پشتیبانی از Callback Query برای `nav:main`, `profile`, `support`, `my_configs`
- پشتیبانی از Callback Query برای `referral:menu` + لینک اشتراک‌گذاری دعوت
- منوی اصلی داینامیک و نمایش پروفایل/پشتیبانی/کانفیگ‌های من در PHP
- فلو اولیه `wallet charge` و `buy flow` (انتخاب نوع/پکیج و پرداخت کیف پول)
- مدیریت ادمین برای تایید/رد درخواست شارژ کیف پول در PHP

```bash
# ساخت اسکیمای MySQL (فاز ۲)
php php/scripts/init_db.php

# انتقال داده‌های اصلی از SQLite به MySQL (فاز ۲)
php php/scripts/migrate_sqlite_to_mysql.php /path/to/configflow.db
```

فاز ۳ مهاجرت (فعلی) در نسخه PHP:
- روت کردن آپدیت‌ها با `UpdateRouter`
- پشتیبانی از Callback Query برای `nav:main`, `profile`, `support`, `my_configs`
- پشتیبانی از Callback Query برای `referral:menu` + لینک اشتراک‌گذاری دعوت
- منوی اصلی داینامیک و نمایش پروفایل/پشتیبانی/کانفیگ‌های من در PHP
- فلو اولیه `wallet charge` و `buy flow` (انتخاب نوع/پکیج و پرداخت کیف پول)

```bash
# ساخت اسکیمای MySQL (فاز ۲)
php php/scripts/init_db.php

# انتقال داده‌های اصلی از SQLite به MySQL (فاز ۲)
php php/scripts/migrate_sqlite_to_mysql.php /path/to/configflow.db
```

فاز ۳ مهاجرت (فعلی) در نسخه PHP:
- روت کردن آپدیت‌ها با `UpdateRouter`
- پشتیبانی از Callback Query برای `nav:main`, `profile`, `support`, `my_configs`
- پشتیبانی از Callback Query برای `referral:menu` + لینک اشتراک‌گذاری دعوت
- منوی اصلی داینامیک و نمایش پروفایل/پشتیبانی/کانفیگ‌های من در PHP

```bash
# ساخت اسکیمای MySQL (فاز ۲)
php php/scripts/init_db.php

# انتقال داده‌های اصلی از SQLite به MySQL (فاز ۲)
php php/scripts/migrate_sqlite_to_mysql.php /path/to/configflow.db
```

فاز ۳ مهاجرت (فعلی) در نسخه PHP:
- روت کردن آپدیت‌ها با `UpdateRouter`
- پشتیبانی از Callback Query برای `nav:main`, `profile`, `support`, `my_configs`
- منوی اصلی داینامیک و نمایش پروفایل/پشتیبانی/کانفیگ‌های من در PHP

---

## 📁 ساختار پروژه

```
ConfigFlow/
├── php/                     # نسخه درحال مهاجرت PHP (فاز ۱)
│   ├── public/
│   │   └── webhook.php      # ورودی اصلی Webhook تلگرام
│   ├── scripts/
│   │   ├── init_db.php      # ساخت جداول اولیه در MySQL
│   │   ├── migrate_sqlite_to_mysql.php
│   │   └── schema.sql
│   ├── src/
│   │   ├── Bootstrap.php    # بارگذاری env
│   │   ├── Config.php       # خواندن تنظیمات محیطی
│   │   ├── Database.php     # اتصال PDO و عملیات پایه کاربر
│   │   ├── CallbackHandler.php
│   │   ├── KeyboardBuilder.php
│   │   ├── MessageHandler.php
│   │   ├── MenuService.php
│   │   ├── PaymentGatewayService.php
│   │   ├── SettingsRepository.php
│   │   ├── StartHandler.php # معادل /start در PHP
│   │   ├── TelegramClient.php
│   │   └── UpdateRouter.php
│   └── .env.example
├── api.py                   # Flask API — سرویس Worker API
├── worker.py                # ورکر سرور ایران (اتصال به 3x-ui)
├── requirements.txt         # وابستگی‌های Python
├── env.example              # نمونه فایل محیطی ربات
├── config.env.example       # نمونه فایل محیطی ورکر
├── install.sh               # اسکریپت نصب خودکار
├── .env                     # تنظیمات ربات (ساخته می‌شود)
│
└── bot/                     # پکیج اصلی ربات
    ├── __init__.py          # ماژول‌های legacy پایتون (درحال حذف تدریجی)
    ├── config.py            # تمام ثابت‌ها و تنظیمات محیطی
    ├── helpers.py           # توابع کمکی (esc، fmt_price، is_admin، ...)
    ├── db.py                # تمام توابع دیتابیس SQLite
    ├── payments.py          # منطق انتخاب و پردازش پرداخت
    │
    ├── gateways/            # یکپارچه‌سازی درگاه‌های پرداخت
    │   ├── __init__.py
    │   ├── base.py          # بررسی دسترسی درگاه‌ها
    │   ├── crypto.py        # دریافت قیمت‌های ارز دیجیتال
    │   ├── tetrapay.py      # ایجاد و تأیید سفارش TetraPay
    │   └── swapwallet.py    # ایجاد، بررسی و نمایش صفحه SwapWallet
    │
    ├── admin/               # ابزارهای پنل ادمین
    │   ├── __init__.py
    │   ├── renderers.py     # نمایش صفحات ادمین (کاربران، پکیج‌ها، ...)
    │   └── backup.py        # بکاپ دستی و خودکار دیتابیس
    │
    └── (Python bot handlers/ui removed after PHP migration phases)
```

---

## 🔧 متغیرهای محیطی

### `.env` — تنظیمات ربات

| متغیر | توضیحات | مثال |
|-------|---------|------|
| `BOT_TOKEN` | توکن ربات از @BotFather | `123456789:ABC...` |
| `ADMIN_IDS` | آیدی عددی ادمین‌ها (با کاما جدا) | `111,222,333` |
| `DB_NAME` | نام فایل دیتابیس | `ConfigFlow.db` |

### `php/.env.example` — تنظیمات فاز ۱ PHP

| متغیر | توضیحات | مثال |
|-------|---------|------|
| `BOT_TOKEN` | توکن ربات تلگرام | `123456789:ABC...` |
| `BOT_USERNAME` | یوزرنیم ربات (برای لینک دعوت) | `MyConfigFlowBot` |
| `ADMIN_IDS` | آیدی ادمین‌ها | `111,222` |
| `DB_HOST` | آدرس MySQL | `127.0.0.1` |
| `DB_PORT` | پورت MySQL | `3306` |
| `DB_NAME` | نام دیتابیس MySQL | `configflow` |
| `DB_USER` | نام کاربری دیتابیس | `root` |
| `DB_PASS` | رمز عبور دیتابیس | `secret` |
| `TETRAPAY_CREATE_URL` | آدرس ساخت سفارش تتراپی | `https://tetra98.com/api/create_order` |
| `TETRAPAY_VERIFY_URL` | آدرس بررسی سفارش تتراپی | `https://tetra98.com/api/verify` |

### `php/.env.example` — تنظیمات فاز ۱ PHP

| متغیر | توضیحات | مثال |
|-------|---------|------|
| `BOT_TOKEN` | توکن ربات تلگرام | `123456789:ABC...` |
| `BOT_USERNAME` | یوزرنیم ربات (برای لینک دعوت) | `MyConfigFlowBot` |
| `ADMIN_IDS` | آیدی ادمین‌ها | `111,222` |
| `DB_HOST` | آدرس MySQL | `127.0.0.1` |
| `DB_PORT` | پورت MySQL | `3306` |
| `DB_NAME` | نام دیتابیس MySQL | `configflow` |
| `DB_USER` | نام کاربری دیتابیس | `root` |
| `DB_PASS` | رمز عبور دیتابیس | `secret` |
| `TETRAPAY_CREATE_URL` | آدرس ساخت سفارش تتراپی | `https://tetra98.com/api/create_order` |
| `TETRAPAY_VERIFY_URL` | آدرس بررسی سفارش تتراپی | `https://tetra98.com/api/verify` |

### `php/.env.example` — تنظیمات فاز ۱ PHP

| متغیر | توضیحات | مثال |
|-------|---------|------|
| `BOT_TOKEN` | توکن ربات تلگرام | `123456789:ABC...` |
| `BOT_USERNAME` | یوزرنیم ربات (برای لینک دعوت) | `MyConfigFlowBot` |
| `ADMIN_IDS` | آیدی ادمین‌ها | `111,222` |
| `DB_HOST` | آدرس MySQL | `127.0.0.1` |
| `DB_PORT` | پورت MySQL | `3306` |
| `DB_NAME` | نام دیتابیس MySQL | `configflow` |
| `DB_USER` | نام کاربری دیتابیس | `root` |
| `DB_PASS` | رمز عبور دیتابیس | `secret` |
| `TETRAPAY_CREATE_URL` | آدرس ساخت سفارش تتراپی | `https://tetra98.com/api/create_order` |
| `TETRAPAY_VERIFY_URL` | آدرس بررسی سفارش تتراپی | `https://tetra98.com/api/verify` |

### `php/.env.example` — تنظیمات فاز ۱ PHP

| متغیر | توضیحات | مثال |
|-------|---------|------|
| `BOT_TOKEN` | توکن ربات تلگرام | `123456789:ABC...` |
| `BOT_USERNAME` | یوزرنیم ربات (برای لینک دعوت) | `MyConfigFlowBot` |
| `ADMIN_IDS` | آیدی ادمین‌ها | `111,222` |
| `DB_HOST` | آدرس MySQL | `127.0.0.1` |
| `DB_PORT` | پورت MySQL | `3306` |
| `DB_NAME` | نام دیتابیس MySQL | `configflow` |
| `DB_USER` | نام کاربری دیتابیس | `root` |
| `DB_PASS` | رمز عبور دیتابیس | `secret` |
| `TETRAPAY_CREATE_URL` | آدرس ساخت سفارش تتراپی | `https://tetra98.com/api/create_order` |
| `TETRAPAY_VERIFY_URL` | آدرس بررسی سفارش تتراپی | `https://tetra98.com/api/verify` |

### `php/.env.example` — تنظیمات فاز ۱ PHP

| متغیر | توضیحات | مثال |
|-------|---------|------|
| `BOT_TOKEN` | توکن ربات تلگرام | `123456789:ABC...` |
| `BOT_USERNAME` | یوزرنیم ربات (برای لینک دعوت) | `MyConfigFlowBot` |
| `ADMIN_IDS` | آیدی ادمین‌ها | `111,222` |
| `DB_HOST` | آدرس MySQL | `127.0.0.1` |
| `DB_PORT` | پورت MySQL | `3306` |
| `DB_NAME` | نام دیتابیس MySQL | `configflow` |
| `DB_USER` | نام کاربری دیتابیس | `root` |
| `DB_PASS` | رمز عبور دیتابیس | `secret` |
| `TETRAPAY_CREATE_URL` | آدرس ساخت سفارش تتراپی | `https://tetra98.com/api/create_order` |
| `TETRAPAY_VERIFY_URL` | آدرس بررسی سفارش تتراپی | `https://tetra98.com/api/verify` |

### `php/.env.example` — تنظیمات فاز ۱ PHP

| متغیر | توضیحات | مثال |
|-------|---------|------|
| `BOT_TOKEN` | توکن ربات تلگرام | `123456789:ABC...` |
| `BOT_USERNAME` | یوزرنیم ربات (برای لینک دعوت) | `MyConfigFlowBot` |
| `ADMIN_IDS` | آیدی ادمین‌ها | `111,222` |
| `DB_HOST` | آدرس MySQL | `127.0.0.1` |
| `DB_PORT` | پورت MySQL | `3306` |
| `DB_NAME` | نام دیتابیس MySQL | `configflow` |
| `DB_USER` | نام کاربری دیتابیس | `root` |
| `DB_PASS` | رمز عبور دیتابیس | `secret` |
| `TETRAPAY_CREATE_URL` | آدرس ساخت سفارش تتراپی | `https://tetra98.com/api/create_order` |
| `TETRAPAY_VERIFY_URL` | آدرس بررسی سفارش تتراپی | `https://tetra98.com/api/verify` |

### `php/.env.example` — تنظیمات فاز ۱ PHP

| متغیر | توضیحات | مثال |
|-------|---------|------|
| `BOT_TOKEN` | توکن ربات تلگرام | `123456789:ABC...` |
| `BOT_USERNAME` | یوزرنیم ربات (برای لینک دعوت) | `MyConfigFlowBot` |
| `ADMIN_IDS` | آیدی ادمین‌ها | `111,222` |
| `DB_HOST` | آدرس MySQL | `127.0.0.1` |
| `DB_PORT` | پورت MySQL | `3306` |
| `DB_NAME` | نام دیتابیس MySQL | `configflow` |
| `DB_USER` | نام کاربری دیتابیس | `root` |
| `DB_PASS` | رمز عبور دیتابیس | `secret` |
| `TETRAPAY_CREATE_URL` | آدرس ساخت سفارش تتراپی | `https://tetra98.com/api/create_order` |
| `TETRAPAY_VERIFY_URL` | آدرس بررسی سفارش تتراپی | `https://tetra98.com/api/verify` |

### `php/.env.example` — تنظیمات فاز ۱ PHP

| متغیر | توضیحات | مثال |
|-------|---------|------|
| `BOT_TOKEN` | توکن ربات تلگرام | `123456789:ABC...` |
| `BOT_USERNAME` | یوزرنیم ربات (برای لینک دعوت) | `MyConfigFlowBot` |
| `ADMIN_IDS` | آیدی ادمین‌ها | `111,222` |
| `DB_HOST` | آدرس MySQL | `127.0.0.1` |
| `DB_PORT` | پورت MySQL | `3306` |
| `DB_NAME` | نام دیتابیس MySQL | `configflow` |
| `DB_USER` | نام کاربری دیتابیس | `root` |
| `DB_PASS` | رمز عبور دیتابیس | `secret` |
| `TETRAPAY_CREATE_URL` | آدرس ساخت سفارش تتراپی | `https://tetra98.com/api/create_order` |
| `TETRAPAY_VERIFY_URL` | آدرس بررسی سفارش تتراپی | `https://tetra98.com/api/verify` |

### `php/.env.example` — تنظیمات فاز ۱ PHP

| متغیر | توضیحات | مثال |
|-------|---------|------|
| `BOT_TOKEN` | توکن ربات تلگرام | `123456789:ABC...` |
| `BOT_USERNAME` | یوزرنیم ربات (برای لینک دعوت) | `MyConfigFlowBot` |
| `ADMIN_IDS` | آیدی ادمین‌ها | `111,222` |
| `DB_HOST` | آدرس MySQL | `127.0.0.1` |
| `DB_PORT` | پورت MySQL | `3306` |
| `DB_NAME` | نام دیتابیس MySQL | `configflow` |
| `DB_USER` | نام کاربری دیتابیس | `root` |
| `DB_PASS` | رمز عبور دیتابیس | `secret` |
| `TETRAPAY_CREATE_URL` | آدرس ساخت سفارش تتراپی | `https://tetra98.com/api/create_order` |
| `TETRAPAY_VERIFY_URL` | آدرس بررسی سفارش تتراپی | `https://tetra98.com/api/verify` |

### `php/.env.example` — تنظیمات فاز ۱ PHP

| متغیر | توضیحات | مثال |
|-------|---------|------|
| `BOT_TOKEN` | توکن ربات تلگرام | `123456789:ABC...` |
| `BOT_USERNAME` | یوزرنیم ربات (برای لینک دعوت) | `MyConfigFlowBot` |
| `ADMIN_IDS` | آیدی ادمین‌ها | `111,222` |
| `DB_HOST` | آدرس MySQL | `127.0.0.1` |
| `DB_PORT` | پورت MySQL | `3306` |
| `DB_NAME` | نام دیتابیس MySQL | `configflow` |
| `DB_USER` | نام کاربری دیتابیس | `root` |
| `DB_PASS` | رمز عبور دیتابیس | `secret` |

### `php/.env.example` — تنظیمات فاز ۱ PHP

| متغیر | توضیحات | مثال |
|-------|---------|------|
| `BOT_TOKEN` | توکن ربات تلگرام | `123456789:ABC...` |
| `BOT_USERNAME` | یوزرنیم ربات (برای لینک دعوت) | `MyConfigFlowBot` |
| `ADMIN_IDS` | آیدی ادمین‌ها | `111,222` |
| `DB_HOST` | آدرس MySQL | `127.0.0.1` |
| `DB_PORT` | پورت MySQL | `3306` |
| `DB_NAME` | نام دیتابیس MySQL | `configflow` |
| `DB_USER` | نام کاربری دیتابیس | `root` |
| `DB_PASS` | رمز عبور دیتابیس | `secret` |

### `php/.env.example` — تنظیمات فاز ۱ PHP

| متغیر | توضیحات | مثال |
|-------|---------|------|
| `BOT_TOKEN` | توکن ربات تلگرام | `123456789:ABC...` |
| `BOT_USERNAME` | یوزرنیم ربات (برای لینک دعوت) | `MyConfigFlowBot` |
| `ADMIN_IDS` | آیدی ادمین‌ها | `111,222` |
| `DB_HOST` | آدرس MySQL | `127.0.0.1` |
| `DB_PORT` | پورت MySQL | `3306` |
| `DB_NAME` | نام دیتابیس MySQL | `configflow` |
| `DB_USER` | نام کاربری دیتابیس | `root` |
| `DB_PASS` | رمز عبور دیتابیس | `secret` |

### `php/.env.example` — تنظیمات فاز ۱ PHP

| متغیر | توضیحات | مثال |
|-------|---------|------|
| `BOT_TOKEN` | توکن ربات تلگرام | `123456789:ABC...` |
| `BOT_USERNAME` | یوزرنیم ربات (برای لینک دعوت) | `MyConfigFlowBot` |
| `ADMIN_IDS` | آیدی ادمین‌ها | `111,222` |
| `DB_HOST` | آدرس MySQL | `127.0.0.1` |
| `DB_PORT` | پورت MySQL | `3306` |
| `DB_NAME` | نام دیتابیس MySQL | `configflow` |
| `DB_USER` | نام کاربری دیتابیس | `root` |
| `DB_PASS` | رمز عبور دیتابیس | `secret` |

### `php/.env.example` — تنظیمات فاز ۱ PHP

| متغیر | توضیحات | مثال |
|-------|---------|------|
| `BOT_TOKEN` | توکن ربات تلگرام | `123456789:ABC...` |
| `ADMIN_IDS` | آیدی ادمین‌ها | `111,222` |
| `DB_HOST` | آدرس MySQL | `127.0.0.1` |
| `DB_PORT` | پورت MySQL | `3306` |
| `DB_NAME` | نام دیتابیس MySQL | `configflow` |
| `DB_USER` | نام کاربری دیتابیس | `root` |
| `DB_PASS` | رمز عبور دیتابیس | `secret` |

### `config.env` — تنظیمات ورکر ایران

| متغیر | توضیحات | پیش‌فرض |
|-------|---------|---------|
| `BOT_API_URL` | آدرس Bot API سرور خارج | — |
| `WORKER_API_KEY` | کلید مشترک امنیتی (حداقل ۱۶ کاراکتر) | — |
| `PANEL_IP` | آدرس IP پنل 3x-ui | `127.0.0.1` |
| `PANEL_PORT` | پورت پنل | `2053` |
| `PANEL_USERNAME` | نام کاربری پنل | — |
| `PANEL_PASSWORD` | رمز عبور پنل | — |
| `INBOUND_ID` | شناسه Inbound در پنل | `1` |
| `PROTOCOL` | پروتکل VPN | `vless` |
| `POLL_INTERVAL` | فاصله بررسی job ها (ثانیه) | `10` |

---

## 🖥️ اجرا

```bash
# اجرای ورکر ایران (روی سرور ایران)
python3 worker.py

# اجرای API (برای ورکر)
python3 api.py

# اجرای webhook فاز ۱ PHP
php -S 0.0.0.0:8080 -t php/public
```

---

## 🔌 وابستگی‌ها

| پکیج | کاربرد |
|------|---------|
| `pyTelegramBotAPI` | فریم‌ورک ربات تلگرام |
| `qrcode` + `pillow` | تولید QR Code برای کانفیگ |
| `python-dotenv` | خواندن فایل `.env` |
| `flask` | Web API برای ورکر |
| `requests` | ارتباط با APIهای خارجی |

---

## 🤝 پشتیبانی

- **Developer:** [@EmadHabibnia](https://t.me/EmadHabibnia)
- **Channel:** [@Emadhabibnia](https://t.me/Emadhabibnia)
- **GitHub:** [Emadhabibnia1385/ConfigFlow](https://github.com/Emadhabibnia1385/ConfigFlow)

---

## 📄 لایسنس

این پروژه تحت [MIT License](LICENSE) منتشر شده است.
