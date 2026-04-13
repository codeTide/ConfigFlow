<?php

declare(strict_types=1);

namespace ConfigFlow\Bot;

final class CallbackHandler
{
    private const DEPRECATED_USER_CALLBACK_EXACT = [
        'profile',
        'support',
        'my_configs',
        'wallet:charge',
        'buy:start',
    ];

    private const DEPRECATED_USER_CALLBACK_PREFIX = [
        'referral:',
        'buy:',
        'renew:',
        'rpay:',
        'pay:swapwallet_crypto:',
        'pay:tronpays_rial:',
    ];

    public function __construct(
        private Database $database,
        private TelegramClient $telegram,
        private SettingsRepository $settings,
        private MenuService $menus,
        private PaymentGatewayService $gateways,
    ) {
    }

    public function handle(array $update): void
    {
        $callback = $update['callback_query'] ?? null;
        if (!is_array($callback)) {
            return;
        }

        $data = (string) ($callback['data'] ?? '');
        $message = $callback['message'] ?? [];
        $fromUser = $callback['from'] ?? [];

        $chatId = (int) ($message['chat']['id'] ?? 0);
        $messageId = (int) ($message['message_id'] ?? 0);
        $userId = (int) ($fromUser['id'] ?? 0);
        $callbackId = (string) ($callback['id'] ?? '');

        if ($chatId === 0 || $messageId === 0 || $userId === 0 || $callbackId === '') {
            return;
        }

        $this->database->ensureUser($fromUser);

        if ($this->database->userStatus($userId) === 'restricted') {
            $this->telegram->answerCallbackQuery($callbackId, 'دسترسی شما محدود شده است.');
            return;
        }

        if ($data === 'noop') {
            $this->telegram->answerCallbackQuery($callbackId);
            return;
        }

        if ($data === 'check_channel') {
            if ($this->checkChannelMembership($userId)) {
                $this->telegram->answerCallbackQuery($callbackId, '✅ عضویت تأیید شد!');
                $this->telegram->editMessageText($chatId, $messageId, $this->menus->mainMenuText());
                $this->telegram->sendMessage($chatId, 'منوی اصلی:', $this->menus->mainMenuReplyKeyboard($userId));
            } else {
                $this->telegram->answerCallbackQuery($callbackId, '❌ هنوز عضو کانال نشده‌اید.');
                $this->telegram->editMessageText($chatId, $messageId, $this->channelLockText(), $this->channelLockKeyboard());
                $this->telegram->sendMessage($chatId, 'بعد از عضویت، از دکمه معمولی زیر استفاده کنید:', $this->channelLockReplyKeyboard());
            }
            return;
        }

        if (!$this->checkChannelMembership($userId)) {
            $this->telegram->answerCallbackQuery($callbackId);
            $this->telegram->editMessageText($chatId, $messageId, $this->channelLockText(), $this->channelLockKeyboard());
            $this->telegram->sendMessage($chatId, 'بعد از عضویت، از دکمه معمولی زیر استفاده کنید:', $this->channelLockReplyKeyboard());
            return;
        }

        if ($this->isDeprecatedUserInlineAction($data)) {
            error_log('Deprecated user callback route used. uid=' . $userId . ' data=' . $data);
            $this->database->clearUserState($userId);
            $this->telegram->answerCallbackQuery($callbackId, '⚠️ مسیر قبلی منقضی شده است. لطفاً از منوی اصلی دوباره شروع کنید.');
            $this->telegram->sendMessage($chatId, $this->menus->mainMenuText(), $this->menus->mainMenuReplyKeyboard($userId));
            return;
        }

        if (str_starts_with($data, 'admin:') || str_starts_with($data, 'pay:')) {
            if (!$this->database->isAdminUser($userId)) {
                $this->telegram->answerCallbackQuery($callbackId, 'شما دسترسی ادمین ندارید.');
                return;
            }
            $this->database->clearUserState($userId);
            $this->telegram->answerCallbackQuery($callbackId, '⚠️ مسیر ادمین قدیمی شده است. از پنل مدیریت جدید ادامه دهید.');
            $this->telegram->sendMessage(
                $chatId,
                $this->menus->adminRootText() . "\n\n<blockquote>ℹ️ این callback منقضی شده و flow جدید جایگزین آن است.</blockquote>",
                $this->menus->adminRootReplyKeyboard()
            );
            return;
        }

        if ($data === 'nav:main') {
            $this->telegram->editMessageText($chatId, $messageId, $this->menus->mainMenuText());
            $this->telegram->sendMessage($chatId, 'منوی اصلی:', $this->menus->mainMenuReplyKeyboard($userId));
            $this->telegram->answerCallbackQuery($callbackId);
            return;
        }

        $this->telegram->answerCallbackQuery($callbackId, 'عملیات منقضی شده است. از منوی اصلی ادامه دهید.');
    }

    private function isDeprecatedUserInlineAction(string $data): bool
    {
        if (in_array($data, self::DEPRECATED_USER_CALLBACK_EXACT, true)) {
            return true;
        }
        foreach (self::DEPRECATED_USER_CALLBACK_PREFIX as $prefix) {
            if (str_starts_with($data, $prefix)) {
                return true;
            }
        }

        return false;
    }

    private function checkChannelMembership(int $userId): bool
    {
        $channelId = trim($this->settings->get('channel_id', ''));
        if ($channelId === '') {
            return true;
        }

        $member = $this->telegram->getChatMember($channelId, $userId);
        $status = (string) ($member['status'] ?? 'left');

        return in_array($status, ['member', 'administrator', 'creator'], true);
    }

    private function channelLockText(): string
    {
        $channelId = trim($this->settings->get('channel_id', ''));
        $title = trim($this->settings->get('channel_title', ''));
        if ($title === '') {
            $title = 'کانال اطلاع‌رسانی';
        }

        $mention = $channelId;
        if ($mention !== '' && !str_starts_with($mention, '@') && preg_match('/^-?\d+$/', $mention) !== 1) {
            $mention = '@' . ltrim($mention, '@');
        }

        $line2 = $mention !== '' ? "📣 {$mention}" : '📣 کانال خصوصی';

        return "🔒 <b>عضویت اجباری</b>\n\n"
            . "برای استفاده از ربات، ابتدا عضو کانال زیر شوید:\n"
            . "• <b>{$title}</b>\n"
            . "{$line2}\n\n"
            . "پس از عضویت، روی «✅ عضو شدم» بزنید.";
    }

    private function channelLockKeyboard(): array
    {
        $channelUrl = trim($this->settings->get('channel_url', ''));
        $channelId = trim($this->settings->get('channel_id', ''));

        if ($channelUrl === '' && $channelId !== '') {
            if (str_starts_with($channelId, '@')) {
                $channelUrl = 'https://t.me/' . ltrim($channelId, '@');
            } elseif (ctype_digit(ltrim($channelId, '-')) && !str_starts_with($channelId, '-100')) {
                $channelUrl = 'https://t.me/' . $channelId;
            }
        }

        $rows = [];
        if ($channelUrl !== '') {
            $rows[] = [['text' => '📢 ورود به کانال', 'url' => $channelUrl]];
        }

        return ['inline_keyboard' => $rows];
    }

    private function channelLockReplyKeyboard(): array
    {
        return [
            'keyboard' => [
                [KeyboardBuilder::BTN_CHECK_CHANNEL],
            ],
            'resize_keyboard' => true,
            'is_persistent' => true,
        ];
    }
}
