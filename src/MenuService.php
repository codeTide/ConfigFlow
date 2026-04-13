<?php

declare(strict_types=1);

namespace ConfigFlow\Bot;

final class MenuService
{
    public function __construct(
        private SettingsRepository $settings,
        private Database $database,
        private ?UiTextCatalogInterface $uiText = null,
        private ?UiKeyboardFactoryInterface $uiKeyboard = null,
    ) {
        $this->uiText ??= new UiTextCatalog();
        $this->uiKeyboard ??= new UiKeyboardFactory();
    }

    public function mainMenuText(): string
    {
        $customText = trim($this->settings->get('start_text', ''));
        if ($customText !== '') {
            return $customText;
        }

        return $this->uiText->multi(new UiTextBlock(
            title: '✨ <b>به فروشگاه ConfigFlow خوش آمدید!</b>',
            lines: [
                new UiTextLine('🧭', 'راهنما', 'از منوی زیر بخش مورد نظر خود را انتخاب کنید.'),
            ],
            tipBlockquote: '💡 اگر اولین بار است وارد ربات می‌شوید، ابتدا بخش حساب را باز کنید تا وضعیت موجودی و امکانات فعال خود را ببینید.',
        ));
    }

    public function mainMenuReplyKeyboard(int $userId): array
    {
        $isAdmin = $this->database->isAdminUser($userId);
        $freeTestEnabled = $this->settings->get('free_test_enabled', '1') === '1';
        $rows = [];
        if ($isAdmin) {
            $rows[] = [KeyboardBuilder::BTN_ADMIN];
        }
        $rows[] = [KeyboardBuilder::BTN_MY_CONFIGS, KeyboardBuilder::BTN_BUY, KeyboardBuilder::BTN_PROFILE];
        $rows[] = $freeTestEnabled ? [KeyboardBuilder::BTN_FREE_TEST, KeyboardBuilder::BTN_SUPPORT] : [KeyboardBuilder::BTN_SUPPORT];

        return $this->uiKeyboard->replyMenu($rows);
    }

    public function accountMenuReplyKeyboard(): array
    {
        $referralEnabled = $this->settings->get('referral_enabled', '1') === '1';
        $agencyEnabled = $this->settings->get('agency_request_enabled', '1') === '1';
        $rows = [[KeyboardBuilder::BTN_WALLET, KeyboardBuilder::BTN_REFERRAL]];
        if (!$referralEnabled) {
            $rows = [[KeyboardBuilder::BTN_WALLET]];
        }
        if ($agencyEnabled) {
            $rows[] = [KeyboardBuilder::BTN_AGENCY];
        }
        $rows[] = [UiLabels::BTN_MAIN];

        return $this->uiKeyboard->replyMenu($rows);
    }

    public function adminRootText(): string
    {
        return $this->uiText->multi(new UiTextBlock(
            title: '⚙️ <b>پنل مدیریت</b>',
            lines: [
                new UiTextLine('🧭', 'راهنما', 'از دکمه‌های زیر بخش مدیریتی موردنظر را انتخاب کنید.'),
            ],
            tipBlockquote: '💡 هر زمان نیاز داشتید می‌توانید با دکمه‌های بازگشت، انصراف یا منوی اصلی مسیر را کنترل کنید تا عملیات مدیریتی اشتباه ثبت نشود.',
        ));
    }

    public function adminRootReplyKeyboard(): array
    {
        return $this->uiKeyboard->replyMenu([
            ['🧩 نوع/پکیج', '📚 موجودی', '👥 کاربران'],
            ['⚙️ تنظیمات', '🧪 تست رایگان'],
            ['👮 ادمین‌ها', '📣 همگانی', '📌 پین‌ها'],
            ['🤝 نماینده‌ها', '🖥 پنل‌های 3x-ui'],
            ['💳 شارژها', '📦 تحویل سفارش', '🗂 درخواست‌ها'],
            ['🗃 بکاپ/تاپیک'],
            [UiLabels::BTN_MAIN, UiLabels::BTN_CANCEL],
        ]);
    }

    public function profileText(int $userId): string
    {
        $user = $this->database->getUser($userId);
        if ($user === null) {
            return $this->uiText->warning('اطلاعات حساب پیدا نشد.');
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

        return $this->uiText->multi(new UiTextBlock(
            title: '👤 <b>پروفایل کاربری</b>',
            lines: [
                new UiTextLine('📱', 'نام', htmlspecialchars((string) ($user['full_name'] ?? '-'))),
                new UiTextLine('🏷', 'نام کاربری', htmlspecialchars((string) $username)),
                new UiTextLine('🔢', 'آیدی', "<code>{$userIdFa}</code>"),
                new UiTextLine('💰', 'موجودی', "<b>{$balanceFa}</b> تومان"),
            ],
            tipBlockquote: '🔐 اطلاعات حساب شما ایمن نگه داشته می‌شود؛ برای شارژ، دعوت دوستان یا ثبت درخواست نمایندگی فقط از دکمه‌های همین بخش استفاده کنید تا فرآیندها دقیق و سریع انجام شوند.',
        ));
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

        $lines = [
            new UiTextLine('🆔', 'آیدی پشتیبانی', htmlspecialchars($username !== '' ? $username : '-')),
        ];
        if ($link !== '') {
            $lines[] = new UiTextLine('🌐', 'لینک پشتیبانی', htmlspecialchars($link));
        }
        if ($linkDesc !== '') {
            $lines[] = new UiTextLine('📝', 'توضیح لینک', htmlspecialchars($linkDesc));
        }

        return $this->uiText->multi(new UiTextBlock(
            title: '🎧 <b>ارتباط با پشتیبانی</b>',
            lines: $lines,
            tipBlockquote: '💡 برای اینکه درخواست شما سریع‌تر بررسی شود، مشکل را همراه با جزئیات مرحله، خطا یا شناسه سفارش ارسال کنید تا تیم پشتیبانی بتواند دقیق‌تر راهنمایی کند.',
        ));
    }

    public function myConfigsText(int $userId): string
    {
        $count = $this->database->countUserPurchases($userId);
        if ($count === 0) {
            return $this->uiText->info('هنوز کانفیگی برای حساب شما ثبت نشده است.');
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

        return $this->uiText->multi(new UiTextBlock(
            title: "📦 <b>شما {$count} کانفیگ خریداری کرده‌اید</b>",
            lines: [
                new UiTextLine('🗂', 'آخرین سفارش‌ها', implode("\n", $lines)),
            ],
            tipBlockquote: '💡 برای تمدید یا پیگیری هر سرویس، از لیست سفارش‌های همین بخش گزینه مناسب را انتخاب کنید تا مراحل به‌صورت خودکار و بدون خطا ادامه پیدا کند.',
        ));
    }

    public function referralText(int $userId): string
    {
        if ($this->settings->get('referral_enabled', '1') !== '1') {
            return $this->uiText->warning('سیستم دعوت دوستان در حال حاضر غیرفعال است.');
        }

        $stats = $this->database->referralStats($userId);
        $botUsername = Config::botUsername();
        $refLink = $botUsername !== '' ? "https://t.me/{$botUsername}?start=ref_{$userId}" : "ref_{$userId}";
        $totalReferralsFa = $this->toPersianDigits((string) ($stats['total_referrals'] ?? 0));
        $purchaseCountFa = $this->toPersianDigits((string) ($stats['purchase_count'] ?? 0));
        $totalPurchaseAmountFa = $this->toPersianDigits((string) ($stats['total_purchase_amount'] ?? 0));

        $banner = trim($this->settings->get('referral_banner_text', ''));
        $intro = $banner !== '' ? $banner . "\n\n" : "💼 <b>زیرمجموعه‌گیری و دعوت دوستان</b>\n\n";

        $title = preg_replace('/\s+/u', ' ', trim($intro)) ?: '💼 <b>زیرمجموعه‌گیری و دعوت دوستان</b>';
        return $this->uiText->multi(new UiTextBlock(
            title: $title,
            lines: [
                new UiTextLine('📊', 'زیرمجموعه‌ها', "<b>{$totalReferralsFa}</b>"),
                new UiTextLine('🛒', 'خریدهای زیرمجموعه', "<b>{$purchaseCountFa}</b>"),
                new UiTextLine('💵', 'مجموع خرید زیرمجموعه', "<b>{$totalPurchaseAmountFa}</b> تومان"),
                new UiTextLine('🔗', 'لینک دعوت', "<code>{$refLink}</code>"),
            ],
            tipBlockquote: '💡 لینک دعوت را برای مخاطبان واقعی ارسال کنید؛ ثبت‌نام و خرید زیرمجموعه‌ها به‌صورت خودکار در آمار شما محاسبه می‌شود و نیازی به پیگیری دستی نخواهد بود.',
        ));
    }

    public function referralKeyboard(int $userId): array
    {
        $shareUrl = $this->referralShareUrl($userId);
        if ($shareUrl === '') {
            return [];
        }
        return $this->uiKeyboard->inlineUrl('📤 اشتراک‌گذاری لینک دعوت', $shareUrl);
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
