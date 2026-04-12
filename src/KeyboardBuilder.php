<?php

declare(strict_types=1);

namespace ConfigFlow\Bot;

final class KeyboardBuilder
{
    public const BTN_BUY = '🛒 خرید کانفیگ جدید';
    public const BTN_MY_CONFIGS = '📦 کانفیگ‌های من';
    public const BTN_FREE_TEST = '🎁 تست رایگان';
    public const BTN_PROFILE = '👤 حساب کاربری';
    public const BTN_WALLET = '💳 شارژ کیف پول';
    public const BTN_SUPPORT = '🎧 ارتباط با پشتیبانی';
    public const BTN_REFERRAL = '🎁 دعوت دوستان';
    public const BTN_AGENCY = '🤝 درخواست نمایندگی';
    public const BTN_ADMIN = '⚙️ ورود به پنل مدیریت';
    public const BTN_BACK_MAIN = '🏠 منوی اصلی';
    public const BTN_BACK_TYPES = '🔙 بازگشت به سرویس‌ها';
    public const BTN_BACK_PURCHASES = '🔙 بازگشت به سفارش‌ها';

    public static function main(bool $isAdmin, bool $referralEnabled, bool $agencyEnabled, bool $freeTestEnabled): array
    {
        $keyboard = [
            [
                ['text' => self::BTN_BUY, 'callback_data' => 'buy:start'],
                ['text' => self::BTN_MY_CONFIGS, 'callback_data' => 'my_configs'],
            ],
        ];

        if ($freeTestEnabled) {
            $keyboard[] = [['text' => self::BTN_FREE_TEST, 'callback_data' => 'test:start']];
        }

        $keyboard[] = [
            ['text' => self::BTN_PROFILE, 'callback_data' => 'profile'],
            ['text' => self::BTN_WALLET, 'callback_data' => 'wallet:charge'],
        ];
        $keyboard[] = [['text' => self::BTN_SUPPORT, 'callback_data' => 'support']];

        if ($referralEnabled) {
            $keyboard[] = [['text' => self::BTN_REFERRAL, 'callback_data' => 'referral:menu']];
        }

        if ($agencyEnabled) {
            $keyboard[] = [['text' => self::BTN_AGENCY, 'callback_data' => 'agency:request']];
        }

        if ($isAdmin) {
            $keyboard[] = [['text' => self::BTN_ADMIN, 'callback_data' => 'admin:panel']];
        }

        return ['inline_keyboard' => $keyboard];
    }

    public static function mainReply(bool $isAdmin, bool $referralEnabled, bool $agencyEnabled, bool $freeTestEnabled): array
    {
        $keyboard = [
            [self::BTN_BUY, self::BTN_MY_CONFIGS],
        ];

        if ($freeTestEnabled) {
            $keyboard[] = [self::BTN_FREE_TEST];
        }

        $keyboard[] = [self::BTN_PROFILE, self::BTN_WALLET];
        $keyboard[] = [self::BTN_SUPPORT];

        if ($referralEnabled) {
            $keyboard[] = [self::BTN_REFERRAL];
        }

        if ($agencyEnabled) {
            $keyboard[] = [self::BTN_AGENCY];
        }

        if ($isAdmin) {
            $keyboard[] = [self::BTN_ADMIN];
        }

        return [
            'keyboard' => $keyboard,
            'resize_keyboard' => true,
            'is_persistent' => true,
        ];
    }

    public static function backToMain(): array
    {
        return ['inline_keyboard' => [[['text' => '🔙 بازگشت', 'callback_data' => 'nav:main']]]];
    }

    public static function referral(string $shareUrl): array
    {
        $rows = [];
        if ($shareUrl !== '') {
            $rows[] = [['text' => '📤 اشتراک‌گذاری لینک دعوت', 'url' => $shareUrl]];
        }
        $rows[] = [['text' => '🔙 بازگشت', 'callback_data' => 'nav:main']];

        return ['inline_keyboard' => $rows];
    }

    public static function adminPanel(): array
    {
        return [
            'inline_keyboard' => [
                [['text' => '🧩 مدیریت نوع/پکیج', 'callback_data' => 'admin:types']],
                [['text' => '📚 مدیریت موجودی کانفیگ', 'callback_data' => 'admin:stock']],
                [['text' => '👥 مدیریت کاربران', 'callback_data' => 'admin:users']],
                [['text' => '⚙️ تنظیمات', 'callback_data' => 'admin:settings']],
                [['text' => '👮 مدیریت ادمین‌ها', 'callback_data' => 'admin:admins']],
                [['text' => '📣 فوروارد همگانی', 'callback_data' => 'admin:broadcast']],
                [['text' => '📌 پیام‌های پین', 'callback_data' => 'admin:pins']],
                [['text' => '🤝 مدیریت نمایندگان', 'callback_data' => 'admin:agents']],
                [['text' => '🖥 مدیریت پنل‌های 3x-ui', 'callback_data' => 'admin:panels']],
                [['text' => '💳 مدیریت درخواست‌های شارژ', 'callback_data' => 'admin:payments']],
                [['text' => '📦 صف تحویل سفارش‌ها', 'callback_data' => 'admin:deliveries']],
                [['text' => '🗂 مدیریت درخواست‌ها (تست/نمایندگی)', 'callback_data' => 'admin:requests']],
                [['text' => '🗃 بکاپ / تاپیک گروه', 'callback_data' => 'admin:groupops']],
                [['text' => '🔙 بازگشت', 'callback_data' => 'nav:main']],
            ],
        ];
    }

    public static function adminPanelReply(): array
    {
        return [
            'keyboard' => [
                ['🧩 مدیریت نوع/پکیج'],
                ['📚 مدیریت موجودی کانفیگ'],
                ['👥 مدیریت کاربران'],
                ['⚙️ تنظیمات'],
                ['👮 مدیریت ادمین‌ها'],
                ['📣 فوروارد همگانی'],
                ['📌 پیام‌های پین'],
                ['🤝 مدیریت نمایندگان'],
                ['🖥 مدیریت پنل‌های 3x-ui'],
                ['💳 مدیریت درخواست‌های شارژ'],
                ['📦 صف تحویل سفارش‌ها'],
                ['🗂 مدیریت درخواست‌ها (تست/نمایندگی)'],
                ['🗃 بکاپ / تاپیک گروه'],
                [self::BTN_BACK_MAIN],
            ],
            'resize_keyboard' => true,
            'is_persistent' => true,
        ];
    }
}
