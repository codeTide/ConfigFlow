<?php

declare(strict_types=1);

namespace ConfigFlow\Bot;

final class MenuService
{
    public function __construct(
        private SettingsRepository $settings,
        private Database $database,
    ) {
    }

    public function mainMenuText(): string
    {
        $customText = trim($this->settings->get('start_text', ''));
        if ($customText !== '') {
            return $customText;
        }

        return "✨ <b>به فروشگاه ConfigFlow خوش آمدید!</b>\n\nاز منوی زیر بخش مورد نظر خود را انتخاب کنید.";
    }

    public function mainMenuKeyboard(int $userId): array
    {
        $isAdmin = in_array($userId, Config::adminIds(), true);
        return KeyboardBuilder::main(
            $isAdmin,
            $this->settings->get('referral_enabled', '1') === '1',
            $this->settings->get('agency_request_enabled', '1') === '1',
            $this->settings->get('free_test_enabled', '1') === '1',
        );
    }

    public function profileText(int $userId): string
    {
        $user = $this->database->getUser($userId);
        if ($user === null) {
            return '⚠️ اطلاعات حساب پیدا نشد.';
        }

        $username = $user['username'] ?? '';
        if ($username === '' || $username === null) {
            $username = '-';
        }

        $balance = (int) ($user['balance'] ?? 0);

        return "👤 <b>پروفایل کاربری</b>\n\n"
            . "📱 نام: " . htmlspecialchars((string) ($user['full_name'] ?? '-')) . "\n"
            . "🆔 نام کاربری: " . htmlspecialchars((string) $username) . "\n"
            . "🔢 آیدی: <code>{$userId}</code>\n\n"
            . "💰 موجودی: <b>{$balance}</b> تومان";
    }

    public function supportText(): string
    {
        return "🎧 <b>ارتباط با پشتیبانی</b>\n\n"
            . "آیدی پشتیبانی: " . htmlspecialchars($this->settings->get('support_username', '-'));
    }

    public function myConfigsText(int $userId): string
    {
        $count = $this->database->countUserPurchases($userId);
        if ($count === 0) {
            return '📭 هنوز کانفیگی برای حساب شما ثبت نشده است.';
        }

        return "📦 شما <b>{$count}</b> کانفیگ خریداری کرده‌اید.\n"
            . 'نمایش جزئیات کامل در فاز بعدی مهاجرت تکمیل می‌شود.';
    }
}
