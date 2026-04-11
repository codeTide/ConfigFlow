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
            ['text' => '🎧 ارتباط با پشتیبانی', 'callback_data' => 'support'],
        ];

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
}
