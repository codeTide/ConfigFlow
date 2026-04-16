<?php

declare(strict_types=1);

namespace ConfigFlow\Bot;

final class KeyboardBuilder
{
    // Legacy constants kept for backward compatibility with non-migrated flows.
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
    public const BTN_CHECK_CHANNEL = '✅ عضو شدم';

    public static function buy(): string { return self::label('buttons.buy', self::BTN_BUY); }
    public static function myConfigs(): string { return self::label('buttons.my_configs', self::BTN_MY_CONFIGS); }
    public static function freeTest(): string { return self::label('buttons.free_test', self::BTN_FREE_TEST); }
    public static function profile(): string { return self::label('buttons.profile', self::BTN_PROFILE); }
    public static function wallet(): string { return self::label('buttons.wallet', self::BTN_WALLET); }
    public static function support(): string { return self::label('buttons.support', self::BTN_SUPPORT); }
    public static function referralButton(): string { return self::label('buttons.referral', self::BTN_REFERRAL); }
    public static function agency(): string { return self::label('buttons.agency', self::BTN_AGENCY); }
    public static function admin(): string { return self::label('buttons.admin_panel', self::BTN_ADMIN); }
    public static function backMain(): string { return self::label('buttons.main_menu', self::BTN_BACK_MAIN); }
    public static function backAccount(): string { return self::label('buttons.back', self::BTN_BACK_ACCOUNT); }
    public static function backTypes(): string { return self::label('buttons.back_to_services', self::BTN_BACK_TYPES); }
    public static function backPurchases(): string { return self::label('buttons.back_to_orders', self::BTN_BACK_PURCHASES); }
    public static function checkChannel(): string { return self::label('buttons.check_channel', self::BTN_CHECK_CHANNEL); }
    public static function shareReferralLink(): string { return self::label('buttons.share_referral_link', ''); }

    public static function mainReply(bool $isAdmin, bool $referralEnabled, bool $agencyEnabled, bool $freeTestEnabled): array
    {
        $keyboard = [];
        if ($isAdmin) {
            $keyboard[] = [self::admin()];
        }
        $keyboard[] = [self::myConfigs(), self::buy(), self::profile()];
        $keyboard[] = $freeTestEnabled ? [self::freeTest(), self::support()] : [self::support()];

        return [
            'keyboard' => $keyboard,
            'resize_keyboard' => true,
            'is_persistent' => true,
        ];
    }

    public static function accountReply(bool $referralEnabled, bool $agencyEnabled): array
    {
        $keyboard = [
            [self::wallet(), self::referralButton()],
        ];
        if (!$referralEnabled) {
            $keyboard = [[self::wallet()]];
        }
        if ($agencyEnabled) {
            $keyboard[] = [self::agency()];
        }
        $keyboard[] = [self::backMain()];

        return [
            'keyboard' => $keyboard,
            'resize_keyboard' => true,
            'is_persistent' => true,
        ];
    }

    public static function backToMain(): array
    {
        error_log('Deprecated KeyboardBuilder::backToMain() used. Prefer UiKeyboardFactory reply navigation.');
        return [];
    }

    public static function referral(string $shareUrl): array
    {
        if ($shareUrl !== '') {
            return ['inline_keyboard' => [[['text' => self::shareReferralLink(), 'url' => $shareUrl]]]];
        }

        return [];
    }

    public static function adminPanel(): array
    {
        error_log('Deprecated KeyboardBuilder::adminPanel() used. Prefer reply-based admin keyboard migration.');
        return [];
    }

    public static function adminPanelReply(): array
    {
        $keyboard = [
            [self::label('buttons.admin.types_packages', ''), self::label('buttons.admin.inventory', ''), self::label('buttons.admin.users', '')],
            [self::label('buttons.admin.settings', ''), self::label('buttons.admin.free_test', '')],
            [self::label('buttons.admin.admins', ''), self::label('buttons.admin.broadcast', ''), self::label('buttons.admin.pins', '')],
            [self::label('buttons.admin.agencies', ''), self::label('buttons.admin.panels', '')],
            [self::label('buttons.admin.charges', ''), self::label('buttons.admin.delivery', ''), self::label('buttons.admin.requests', '')],
            [self::label('buttons.admin.backup_topics', '')],
        ];
        $keyboard[] = [self::backMain()];

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

    private static function label(string $key, string $fallback): string
    {
        try {
            return (new UiJsonCatalog())->get($key);
        } catch (\Throwable) {
            return $fallback;
        }
    }
}
