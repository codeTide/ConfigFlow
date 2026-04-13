<?php

declare(strict_types=1);

namespace ConfigFlow\Bot;

final class KeyboardBuilder
{
    public const BTN_BUY = '🛒 خرید';
    public const BTN_MY_CONFIGS = '📦 کانفیگ‌هام';
    public const BTN_FREE_TEST = '🎁 تست رایگان';
    public const BTN_PROFILE = '👤 حساب';
    public const BTN_WALLET = '💳 شارژ حساب';
    public const BTN_SUPPORT = '🎧 پشتیبانی';
    public const BTN_REFERRAL = '🎁 دعوت';
    public const BTN_AGENCY = '🤝 نمایندگی';
    public const BTN_ADMIN = '⚙️ پنل مدیریت';
    public const BTN_BACK_MAIN = '🏠 منوی اصلی';
    public const BTN_BACK_ACCOUNT = '↩️ بازگشت';
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

        $keyboard[] = [
            ['text' => self::BTN_PROFILE, 'callback_data' => 'profile'],
            ['text' => self::BTN_WALLET, 'callback_data' => 'wallet:charge'],
        ];
        $keyboard[] = [['text' => self::BTN_SUPPORT, 'callback_data' => 'support']];

        if ($referralEnabled) {
            $keyboard[] = [['text' => self::BTN_REFERRAL, 'callback_data' => 'referral:menu']];
        }

        if ($isAdmin) {
            $keyboard[] = [['text' => self::BTN_ADMIN, 'callback_data' => 'admin:panel']];
        }

        return ['inline_keyboard' => $keyboard];
    }

    public static function mainReply(bool $isAdmin, bool $referralEnabled, bool $agencyEnabled, bool $freeTestEnabled): array
    {
        $keyboard = [];
        if ($isAdmin) {
            $keyboard[] = [self::BTN_ADMIN];
        }
        $keyboard[] = [self::BTN_MY_CONFIGS, self::BTN_BUY, self::BTN_PROFILE];
        $keyboard[] = $freeTestEnabled ? [self::BTN_FREE_TEST, self::BTN_SUPPORT] : [self::BTN_SUPPORT];

        return [
            'keyboard' => $keyboard,
            'resize_keyboard' => true,
            'is_persistent' => true,
        ];
    }

    public static function accountReply(bool $referralEnabled, bool $agencyEnabled): array
    {
        $keyboard = [
            [self::BTN_WALLET, self::BTN_REFERRAL],
        ];
        if (!$referralEnabled) {
            $keyboard = [[self::BTN_WALLET]];
        }
        if ($agencyEnabled) {
            $keyboard[] = [self::BTN_AGENCY];
        }
        $keyboard[] = [self::BTN_BACK_MAIN];

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
        if ($shareUrl !== '') {
            return ['inline_keyboard' => [[['text' => '📤 اشتراک‌گذاری لینک دعوت', 'url' => $shareUrl]]]];
        }

        return [];
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
        $buttons = [
            '🧩 نوع/پکیج',
            '📚 موجودی',
            '👥 کاربران',
            '⚙️ تنظیمات',
            '🧪 تست رایگان',
            '👮 ادمین‌ها',
            '📣 همگانی',
            '📌 پین‌ها',
            '🤝 نماینده‌ها',
            '🖥 پنل‌های 3x-ui',
            '💳 شارژها',
            '📦 تحویل سفارش',
            '🗂 درخواست‌ها',
            '🗃 بکاپ/تاپیک',
        ];
        $keyboard = self::smartKeyboardRows($buttons, [3, 2, 1], 12, 20);
        $keyboard[] = [self::BTN_BACK_MAIN];

        return [
            'keyboard' => $keyboard,
            'resize_keyboard' => true,
            'is_persistent' => true,
        ];
    }

    public static function smartKeyboardRows(
        array $buttonTexts,
        array $layoutPattern = [3, 2, 1],
        int $limitThreeCols = 14,
        int $limitTwoCols = 20
    ): array {
        $threeCols = [];
        $twoCols = [];
        $singleCol = [];
        foreach ($buttonTexts as $buttonText) {
            $text = trim((string) $buttonText);
            if ($text === '') {
                continue;
            }
            $length = function_exists('mb_strlen') ? mb_strlen($text) : strlen($text);
            if ($length <= $limitThreeCols) {
                $threeCols[] = $text;
            } elseif ($length <= $limitTwoCols) {
                $twoCols[] = $text;
            } else {
                $singleCol[] = $text;
            }
        }

        $groups = [
            3 => &$threeCols,
            2 => &$twoCols,
            1 => &$singleCol,
        ];
        $rows = [];
        foreach ($layoutPattern as $cols) {
            $cols = (int) $cols;
            if (!isset($groups[$cols])) {
                continue;
            }
            while (count($groups[$cols]) >= $cols) {
                $rows[] = array_splice($groups[$cols], 0, $cols);
            }
        }

        $remaining = array_merge($threeCols, $twoCols, $singleCol);
        while ($remaining !== []) {
            $rows[] = array_splice($remaining, 0, 2);
        }

        return [
            ...$rows,
        ];
    }
}
