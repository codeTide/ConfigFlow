<?php

declare(strict_types=1);

namespace ConfigFlow\Bot;

final class KeyboardBuilder
{
    public static function main(bool $isAdmin, bool $referralEnabled, bool $agencyEnabled, bool $freeTestEnabled): array
    {
        $keyboard = [
            [
                ['text' => '🛒 خرید کانفیگ جدید', 'callback_data' => 'buy:start'],
                ['text' => '📦 کانفیگ‌های من', 'callback_data' => 'my_configs'],
            ],
        ];

        if ($freeTestEnabled) {
            $keyboard[] = [['text' => '🎁 تست رایگان', 'callback_data' => 'test:start']];
        }

        $keyboard[] = [
            ['text' => '👤 حساب کاربری', 'callback_data' => 'profile'],
            ['text' => '💳 شارژ کیف پول', 'callback_data' => 'wallet:charge'],
        ];
        $keyboard[] = [['text' => '🎧 ارتباط با پشتیبانی', 'callback_data' => 'support']];

        if ($referralEnabled) {
            $keyboard[] = [['text' => '🎁 دعوت دوستان', 'callback_data' => 'referral:menu']];
        }

        if ($agencyEnabled) {
            $keyboard[] = [['text' => '🤝 درخواست نمایندگی', 'callback_data' => 'agency:request']];
        }

        if ($isAdmin) {
            $keyboard[] = [['text' => '⚙️ ورود به پنل مدیریت', 'callback_data' => 'admin:panel']];
        }

        return ['inline_keyboard' => $keyboard];
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
}
