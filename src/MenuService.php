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

    public function mainMenuReplyKeyboard(int $userId): array
    {
        $isAdmin = $this->database->isAdminUser($userId);
        return KeyboardBuilder::mainReply(
            $isAdmin,
            $this->settings->get('referral_enabled', '1') === '1',
            $this->settings->get('agency_request_enabled', '1') === '1',
            $this->settings->get('free_test_enabled', '1') === '1',
        );
    }

    public function accountMenuReplyKeyboard(): array
    {
        return KeyboardBuilder::accountReply(
            $this->settings->get('referral_enabled', '1') === '1',
            $this->settings->get('agency_request_enabled', '1') === '1',
        );
    }

    public function profileText(int $userId): string
    {
        $user = $this->database->getUser($userId);
        if ($user === null) {
            return '⚠️ اطلاعات حساب پیدا نشد.';
        }

        $username = trim((string) ($user['username'] ?? ''));
        if ($username === '') {
            $username = '-';
        } elseif (!str_starts_with($username, '@')) {
            $username = '@' . $username;
        }

        $balance = (int) ($user['balance'] ?? 0);
        $balanceFa = $this->toPersianDigits((string) $balance);
        $userIdFa = $this->toPersianDigits((string) $userId);

        return "👤 <b>پروفایل کاربری</b>\n\n"
            . "📱 نام: " . htmlspecialchars((string) ($user['full_name'] ?? '-')) . "\n"
            . "🏷 نام کاربری: " . htmlspecialchars((string) $username) . "\n"
            . "🔢 آیدی: <code>{$userIdFa}</code>\n\n"
            . "💰 موجودی: <b>{$balanceFa}</b> تومان\n\n"
            . "<blockquote>🔐 حساب شما امن نگه داشته شده؛ برای شارژ، دعوت یا نمایندگی از دکمه‌های همین بخش استفاده کنید.</blockquote>";
    }

    private function toPersianDigits(string $value): string
    {
        return strtr($value, [
            '0' => '۰',
            '1' => '۱',
            '2' => '۲',
            '3' => '۳',
            '4' => '۴',
            '5' => '۵',
            '6' => '۶',
            '7' => '۷',
            '8' => '۸',
            '9' => '۹',
        ]);
    }

    public function supportText(): string
    {
        $username = trim($this->settings->get('support_username', ''));
        $link = trim($this->settings->get('support_link', ''));
        $linkDesc = trim($this->settings->get('support_link_desc', ''));

        $text = "🎧 <b>ارتباط با پشتیبانی</b>\n\n"
            . "آیدی پشتیبانی: " . htmlspecialchars($username !== '' ? $username : '-');

        if ($link !== '') {
            $text .= "\n🌐 لینک پشتیبانی: " . htmlspecialchars($link);
            if ($linkDesc !== '') {
                $text .= "\n📝 " . htmlspecialchars($linkDesc);
            }
        }

        return $text;
    }

    public function myConfigsText(int $userId): string
    {
        $count = $this->database->countUserPurchases($userId);
        if ($count === 0) {
            return '📭 هنوز کانفیگی برای حساب شما ثبت نشده است.';
        }

        $items = $this->database->listUserPurchasesSummary($userId, 8);
        $lines = [];
        foreach ($items as $item) {
            $packageName = trim((string) ($item['package_name'] ?? '—'));
            $serviceName = trim((string) ($item['service_name'] ?? '—'));
            $amount = (int) ($item['amount'] ?? 0);
            $createdAt = (string) ($item['created_at'] ?? '');
            $isTest = ((int) ($item['is_test'] ?? 0)) === 1 ? ' (تست)' : '';
            $lines[] = sprintf(
                "• #%d | %s | %s | %d تومان%s\n  ⏱ %s",
                (int) ($item['id'] ?? 0),
                htmlspecialchars($packageName),
                htmlspecialchars($serviceName),
                $amount,
                $isTest,
                htmlspecialchars($createdAt !== '' ? $createdAt : '-')
            );
        }

        return "📦 شما <b>{$count}</b> کانفیگ خریداری کرده‌اید.\n\n"
            . "آخرین سفارش‌ها:\n"
            . implode("\n", $lines);
    }

    public function referralText(int $userId): string
    {
        if ($this->settings->get('referral_enabled', '1') !== '1') {
            return '⚠️ سیستم دعوت دوستان در حال حاضر غیرفعال است.';
        }

        $stats = $this->database->referralStats($userId);
        $botUsername = Config::botUsername();
        $refLink = $botUsername !== '' ? "https://t.me/{$botUsername}?start=ref_{$userId}" : "ref_{$userId}";
        $totalReferralsFa = $this->toPersianDigits((string) ($stats['total_referrals'] ?? 0));
        $purchaseCountFa = $this->toPersianDigits((string) ($stats['purchase_count'] ?? 0));
        $totalPurchaseAmountFa = $this->toPersianDigits((string) ($stats['total_purchase_amount'] ?? 0));

        $banner = trim($this->settings->get('referral_banner_text', ''));
        $intro = $banner !== '' ? $banner . "\n\n" : "💼 <b>زیرمجموعه‌گیری و دعوت دوستان</b>\n\n";

        return $intro
            . "📊 زیرمجموعه‌ها: <b>{$totalReferralsFa}</b>\n"
            . "🛒 خریدهای زیرمجموعه: <b>{$purchaseCountFa}</b>\n"
            . "💵 مجموع خرید زیرمجموعه: <b>{$totalPurchaseAmountFa}</b> تومان\n\n"
            . "🔗 لینک دعوت شما:\n\n<code>{$refLink}</code>";
    }

    public function referralShareUrl(int $userId): string
    {
        $botUsername = Config::botUsername();
        if ($botUsername === '') {
            return '';
        }

        $refLink = "https://t.me/{$botUsername}?start=ref_{$userId}";
        $text = "از لینک من وارد شو:\n{$refLink}";

        return 'https://t.me/share/url?url=' . rawurlencode($refLink) . '&text=' . rawurlencode($text);
    }
}
