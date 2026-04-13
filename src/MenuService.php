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
    ) {
        $this->uiText ??= new UiTextCatalog();
        $this->uiKeyboard ??= new UiKeyboardFactory();
        $this->catalog ??= new UiJsonCatalog();
    }

    public function mainMenuText(): string
    {
        $customText = trim($this->settings->get('start_text', ''));
        if ($customText !== '') {
            return $customText;
        }

        return $this->uiText->multi(new UiTextBlock(
            title: $this->catalog->get('menus.main.title'),
            lines: [
                new UiTextLine(
                    '',
                    $this->catalog->get('menus.main.guide_label'),
                    $this->catalog->get('menus.main.guide_value')
                ),
            ],
            tipBlockquote: $this->catalog->get('menus.main.tip'),
        ));
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
        return $this->uiText->multi(new UiTextBlock(
            title: $this->catalog->get('menus.admin.title'),
            lines: [
                new UiTextLine(
                    '',
                    $this->catalog->get('menus.admin.guide_label'),
                    $this->catalog->get('menus.admin.guide_value')
                ),
            ],
            tipBlockquote: $this->catalog->get('menus.admin.tip'),
        ));
    }

    public function adminRootReplyKeyboard(): array
    {
        return $this->uiKeyboard->replyMenu([
            [$this->catalog->get('buttons.admin.types_packages'), $this->catalog->get('buttons.admin.inventory'), $this->catalog->get('buttons.admin.users')],
            [$this->catalog->get('buttons.admin.settings'), $this->catalog->get('buttons.admin.free_test')],
            [$this->catalog->get('buttons.admin.admins'), $this->catalog->get('buttons.admin.broadcast'), $this->catalog->get('buttons.admin.pins')],
            [$this->catalog->get('buttons.admin.agencies'), $this->catalog->get('buttons.admin.panels')],
            [$this->catalog->get('buttons.admin.charges'), $this->catalog->get('buttons.admin.delivery'), $this->catalog->get('buttons.admin.requests')],
            [$this->catalog->get('buttons.admin.backup_topics')],
            [UiLabels::main($this->catalog), UiLabels::cancel($this->catalog)],
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

        return $this->uiText->multi(new UiTextBlock(
            title: $this->catalog->get('menus.profile.title'),
            lines: [
                new UiTextLine('', $this->catalog->get('menus.profile.name_label'), htmlspecialchars((string) ($user['full_name'] ?? $this->catalog->get('messages.generic.dash')))),
                new UiTextLine('', $this->catalog->get('menus.profile.username_label'), htmlspecialchars((string) $username)),
                new UiTextLine('', $this->catalog->get('menus.profile.user_id_label'), "<code>{$userIdFa}</code>"),
                new UiTextLine('', $this->catalog->get('menus.profile.balance_label'), $this->catalog->get('menus.profile.balance_value', ['amount' => $balanceFa])),
            ],
            tipBlockquote: $this->catalog->get('menus.profile.tip'),
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
            new UiTextLine(
                '',
                $this->catalog->get('menus.support.support_id_label'),
                htmlspecialchars($username !== '' ? $username : $this->catalog->get('messages.generic.dash'))
            ),
        ];
        if ($link !== '') {
            $lines[] = new UiTextLine('', $this->catalog->get('menus.support.support_link_label'), htmlspecialchars($link));
        }
        if ($linkDesc !== '') {
            $lines[] = new UiTextLine('', $this->catalog->get('menus.support.support_link_desc_label'), htmlspecialchars($linkDesc));
        }

        return $this->uiText->multi(new UiTextBlock(
            title: $this->catalog->get('menus.support.title'),
            lines: $lines,
            tipBlockquote: $this->catalog->get('menus.support.tip'),
        ));
    }

    public function myConfigsText(int $userId): string
    {
        $count = $this->database->countUserPurchases($userId);
        if ($count === 0) {
            return $this->catalog->get('emojis.package') . ' ' . $this->catalog->get('errors.no_configs');
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

        return $this->uiText->multi(new UiTextBlock(
            title: $this->catalog->get('menus.my_configs.title', ['count' => $count]),
            lines: [
                new UiTextLine('', $this->catalog->get('menus.my_configs.latest_orders_label'), implode("\n", $lines)),
            ],
            tipBlockquote: $this->catalog->get('menus.my_configs.tip'),
        ));
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
        return $this->uiText->multi(new UiTextBlock(
            title: $title,
            lines: [
                new UiTextLine('', $this->catalog->get('menus.referral.total_referrals_label'), "<b>{$totalReferralsFa}</b>"),
                new UiTextLine('', $this->catalog->get('menus.referral.purchase_count_label'), "<b>{$purchaseCountFa}</b>"),
                new UiTextLine('', $this->catalog->get('menus.referral.total_purchase_amount_label'), $this->catalog->get('menus.referral.total_purchase_amount_value', ['amount' => $totalPurchaseAmountFa])),
                new UiTextLine('', $this->catalog->get('menus.referral.invite_link_label'), "\n\n<code>{$refLink}</code>"),
            ],
            tipBlockquote: $this->catalog->get('menus.referral.tip'),
        ));
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
