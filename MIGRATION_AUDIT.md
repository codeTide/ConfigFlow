# Migration Audit: Python → PHP (ConfigFlow)

## 1) وضعیت کلی مهاجرت

- حجم فعلی کدها نشان می‌دهد پروژه هنوز **Python-heavy** است:
  - Python: حدود **12,297** خط در **25** فایل.
  - PHP: حدود **3,097** خط در **15** فایل.
- یعنی تقریباً در حد ~20% فایل‌ها و ~20% تا ~25% منطق‌ها به PHP منتقل شده‌اند (برآورد سطح بالا).

## 2) بخش‌هایی که در PHP پیاده‌سازی شده‌اند (Phase فعلی)

### Core routing / entry
- ورودی Webhook در PHP آماده است (`php/public/webhook.php`) و bootstrap/route انجام می‌شود.
- `UpdateRouter` پیام‌ها و callback ها را به handlerهای جداگانه می‌فرستد.

### User-facing flows
- `/start` + منوی اصلی + وضعیت ربات (`on/update/off`) پیاده شده.
- منوهای `profile`, `support`, `my_configs`, `referral:menu` پیاده‌سازی شده‌اند.
- شروع خرید (`buy:start`) + انتخاب نوع (`buy:type:*`) + انتخاب پکیج (`buy:pkg:*`) فعال است.

### Payments (در PHP)
- پرداخت کیف پول (`buy:wallet:*`) و شارژ کیف پول (`wallet:charge`) فعال است.
- مسیرهای card/crypto/tetrapay برای خرید موجودند:
  - `buy:card:*`
  - `buy:crypto:*` + انتخاب coin + ثبت TX + verify ادمین
  - `buy:tetrapay:*` + check/verify
- تایید/رد پرداخت ادمین (`pay:approve:*`, `pay:reject:*`) و صف تحویل سفارش (`admin:deliver:*`) پیاده شده.

### Admin requests
- درخواست تست رایگان و نمایندگی در PHP ثبت می‌شوند.
- لیست/فیلتر/جزئیات/تایید/رد درخواست‌ها با وضعیت‌های `pending/approved/rejected` پیاده شده.

## 3) شکاف‌ها: چه چیزهایی هنوز در PHP کامل/پیاده نشده‌اند

## A) پنل ادمین کامل (بزرگ‌ترین بخش باقی‌مانده)
در Python پنل ادمین بسیار گسترده است؛ در PHP فعلاً زیرمجموعه‌ی محدودی پیاده شده:
- مدیریت نوع سرویس و پکیج‌ها (CRUD کامل + active/order/edit) در PHP دیده نمی‌شود.
- مدیریت موجودی کانفیگ (single/bulk add، جستجو، expire/delete گروهی، pagination کامل) کامل منتقل نشده.
- مدیریت کاربران (جستجو، restricted/safe، agent flag، تغییر موجودی، ثبت کانفیگ مستقیم برای user، مشاهده/لغو انتساب) ناقص است.
- broadcast ها (all/customers/agents/admins/normal) هنوز PHP-Complete نیستند.
- تنظیمات گسترده ادمین (shop status, rules, support, channel lock, gateways visibility/range/display name...) ناقص است.

## B) Gateway matrix کامل
- Python پنج درگاه را پوشش می‌دهد: card/crypto/tetrapay/**swapwallet_crypto/tronpays_rial**.
- در PHP درگاه‌های swapwallet_crypto و tronpays_rial و callbackهای مربوطه وجود ندارند.

## C) Renewal flow
- فلو تمدید (`renew:*`, `rpay:*`) که در Python هست، در PHP فعلاً وجود ندارد.

## D) Channel lock / membership enforcement
- در Python چک عضویت کانال و پیام lock (`check_channel_membership`, `channel_lock_message`, `check_channel`) فعال است.
- در PHP این enforce به‌صورت هم‌سطح Python پیاده نشده.

## E) 3x-ui panel + worker architecture
- Python شامل مدیریت panel/packages و job queue (`xui_jobs`) و worker API (`api.py`) و worker (`worker.py`) است.
- در PHP معادل کامل worker/panel management هنوز مشاهده نمی‌شود.

## F) Backup/restore + group topic notifications
- بکاپ دستی/خودکار/restore و topic-based logging/notifications در Python وجود دارد.
- در PHP هنوز معادل کامل دیده نمی‌شود.

## G) Referral advanced logic
- در PHP منوی referral و آمار پایه هست.
- منطق‌های پیشرفته reward/payout/rules که در Python وجود دارد به‌صورت کامل منتقل نشده.

## 4) مقایسه سریع callback coverage (نشانه خوبی برای پوشش)

- در Python handler اصلی callbackها:
  - حدود **142** مسیر `==` و **110** مسیر `startswith` دارد.
- در PHP callback handler:
  - حدود **14** مسیر `===` و **22** مسیر `str_starts_with` دارد.
- نتیجه: هسته‌ی flow خرید/پرداخت/درخواست‌ها منتقل شده، اما breadth پنل ادمین Python هنوز بسیار بیشتر است.

## 5) اولویت پیشنهادی برای ادامه مهاجرت

1. **Admin CRUD حیاتی**: types/packages/config stock/users/settings (MVP parity).
2. **Renewal flow**: `renew:*` + `rpay:*`.
3. **Gateway parity**: swapwallet_crypto + tronpays_rial.
4. **Channel lock + policy gates**.
5. **Backup/restore + broadcast کامل**.
6. **Panel/Worker (3x-ui jobs)** و نهایتاً deprecate کردن Python worker path.

## 6) جمع‌بندی اجرایی

- PHP الان برای یک **MVP عملیاتی** (start/menu/buy/pay/admin-approval/delivery/request-review) قابل استفاده است.
- برای parity واقعی با نسخه Python، بیشترین کار باقی‌مانده روی **پنل ادمین پیشرفته، renewal، درگاه‌های اضافی، و worker/panel automation** است.
