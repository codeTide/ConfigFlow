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
        private ?UiJsonCatalog $catalog = null,
        private ?UiMessageRenderer $messageRenderer = null,
    ) {
        $this->uiText ??= new UiTextCatalog();
        $this->uiKeyboard ??= new UiKeyboardFactory();
        $this->catalog ??= new UiJsonCatalog();
        $this->messageRenderer ??= new UiMessageRenderer($this->catalog);
    }

    public function mainMenuText(): string
    {
        $customText = trim($this->settings->get('start_text', ''));
        if ($customText !== '') {
            return $customText;
        }

        return $this->messageRenderer->render('menus.messages.main_overview');
    }

    public function mainMenuReplyKeyboard(int $userId): array
    {
        $isAdmin = $this->database->isAdminUser($userId);
        $freeTestEnabled = $this->settings->get('free_test_enabled', '1') === '1';
        $rows = [];
        if ($isAdmin) {
            $rows[] = [KeyboardBuilder::admin()];
        }
        $rows[] = [KeyboardBuilder::myConfigs(), KeyboardBuilder::buy(), KeyboardBuilder::profile()];
        $rows[] = $freeTestEnabled ? [KeyboardBuilder::freeTest(), KeyboardBuilder::support()] : [KeyboardBuilder::support()];

        return $this->uiKeyboard->replyMenu($rows);
    }

    public function accountMenuReplyKeyboard(): array
    {
        $referralEnabled = $this->settings->get('referral_enabled', '1') === '1';
        $agencyEnabled = $this->settings->get('agency_request_enabled', '1') === '1';
        $rows = [[KeyboardBuilder::wallet(), KeyboardBuilder::referralButton()]];
        if (!$referralEnabled) {
            $rows = [[KeyboardBuilder::wallet()]];
        }
        if ($agencyEnabled) {
            $rows[] = [KeyboardBuilder::agency()];
        }
        $rows[] = [UiLabels::main($this->catalog)];

        return $this->uiKeyboard->replyMenu($rows);
    }

    public function adminRootText(): string
    {
        return $this->messageRenderer->render('menus.messages.admin_overview');
    }

    public function adminRootReplyKeyboard(): array
    {
        return $this->uiKeyboard->replyMenu([
            [$this->catalog->get('buttons.admin.types_packages'), $this->catalog->get('buttons.admin.inventory'), $this->catalog->get('buttons.admin.users')],
            [$this->catalog->get('buttons.admin.settings'), $this->catalog->get('buttons.admin.free_test')],
            [$this->catalog->get('buttons.admin.admins'), $this->catalog->get('buttons.admin.broadcast'), $this->catalog->get('buttons.admin.pins')],
            [$this->catalog->get('buttons.admin.agencies')],
            [$this->catalog->get('buttons.admin.charges'), $this->catalog->get('buttons.admin.delivery'), $this->catalog->get('buttons.admin.requests')],
            [$this->catalog->get('buttons.admin.backup_topics')],
            [$this->catalog->get('buttons.admin.exit_panel')],
        ]);
    }

    public function profileText(int $userId): string
    {
        $user = $this->database->getUser($userId);
        if ($user === null) {
            return $this->uiText->warning($this->catalog->get('errors.profile_not_found'));
        }

        $username = trim((string) ($user['username'] ?? ''));
        if ($username === '') {
            $username = $this->catalog->get('messages.generic.dash');
        } elseif (!str_starts_with($username, '@')) {
            $username = '@' . $username;
        }

        $balance = (int) ($user['balance'] ?? 0);
        $balanceFa = $this->toPersianDigits((string) $balance);
        $userIdFa = $this->toPersianDigits((string) $userId);

        return $this->messageRenderer->render('menus.messages.profile_overview', [
            'full_name' => (string) ($user['full_name'] ?? $this->catalog->get('messages.generic.dash')),
            'username' => (string) $username,
            'user_id' => $userIdFa,
            'balance' => $balanceFa,
        ], ['user_id']);
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

        return $this->messageRenderer->render('menus.messages.support_overview', [
            'support_id' => $username !== '' ? $username : $this->catalog->get('messages.generic.dash'),
            'support_link_line' => $link !== '' ? $this->messageRenderer->render('menus.messages.support_link_line', ['link' => $link]) : '',
            'support_link_desc_line' => $linkDesc !== '' ? $this->messageRenderer->render('menus.messages.support_link_desc_line', ['description' => $linkDesc]) : '',
        ]);
    }

    public function myConfigsText(int $userId): string
    {
        $count = $this->database->countUserPurchases($userId);
        if ($count === 0) {
            return $this->messageRenderer->render('menus.messages.my_configs_empty');
        }

        $items = $this->database->listUserPurchasesSummary($userId, 8);
        $lines = [];
        foreach ($items as $item) {
            $packageName = trim((string) ($item['package_name'] ?? '—'));
            $serviceName = trim((string) ($item['service_name'] ?? '—'));
            $amount = (int) ($item['amount'] ?? 0);
            $createdAt = (string) ($item['created_at'] ?? '');
            $isTest = ((int) ($item['is_test'] ?? 0)) === 1 ? $this->catalog->get('menus.my_configs.test_suffix') : '';
            $lines[] = $this->catalog->get('menus.my_configs.order_row', [
                'id' => (int) ($item['id'] ?? 0),
                'package' => htmlspecialchars($packageName),
                'service' => htmlspecialchars($serviceName),
                'amount' => $amount,
                'test_suffix' => $isTest,
                'created_at' => htmlspecialchars($createdAt !== '' ? $createdAt : $this->catalog->get('messages.generic.dash')),
            ]);
        }

        // Guardrail: row-template + implode is intentionally allowed only for data-driven lists.
        return $this->messageRenderer->render('menus.messages.my_configs_overview', [
            'count' => $count,
            'orders' => implode("\n", $lines),
        ], ['orders']);
    }

    public function referralText(int $userId): string
    {
        if ($this->settings->get('referral_enabled', '1') !== '1') {
            return $this->uiText->warning($this->catalog->get('errors.referral_disabled'));
        }

        $stats = $this->database->referralStats($userId);
        $botUsername = Config::botUsername();
        $refLink = $botUsername !== '' ? "https://t.me/{$botUsername}?start=ref_{$userId}" : "ref_{$userId}";
        $totalReferralsFa = $this->toPersianDigits((string) ($stats['total_referrals'] ?? 0));
        $purchaseCountFa = $this->toPersianDigits((string) ($stats['purchase_count'] ?? 0));
        $totalPurchaseAmountFa = $this->toPersianDigits((string) ($stats['total_purchase_amount'] ?? 0));

        $banner = trim($this->settings->get('referral_banner_text', ''));
        $defaultIntro = $this->catalog->get('menus.referral.default_intro');
        $intro = $banner !== '' ? $banner . "\n\n" : $defaultIntro . "\n\n";

        $title = preg_replace('/\s+/u', ' ', trim($intro)) ?: $defaultIntro;
        return $this->messageRenderer->render('menus.messages.referral_overview', [
            'title' => $title,
            'total_referrals' => $totalReferralsFa,
            'purchase_count' => $purchaseCountFa,
            'total_purchase_amount' => $totalPurchaseAmountFa,
            'ref_link' => $refLink,
        ], ['title', 'ref_link']);
    }

    public function referralKeyboard(int $userId): array
    {
        $shareUrl = $this->referralShareUrl($userId);
        if ($shareUrl === '') {
            return [];
        }
        return $this->uiKeyboard->inlineUrl($this->catalog->get('buttons.share_referral_link'), $shareUrl);
    }

    public function referralShareUrl(int $userId): string
    {
        $botUsername = Config::botUsername();
        if ($botUsername === '') {
            return '';
        }

        $refLink = "https://t.me/{$botUsername}?start=ref_{$userId}";
        $text = $this->catalog->get('menus.referral.share_text', ['ref_link' => $refLink]);

        return 'https://t.me/share/url?url=' . rawurlencode($refLink) . '&text=' . rawurlencode($text);
    }
}
