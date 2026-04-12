<?php

declare(strict_types=1);

namespace ConfigFlow\Bot;

final class StartHandler
{
    public function __construct(
        private Database $database,
        private TelegramClient $telegram,
        private SettingsRepository $settings,
        private MenuService $menus,
    ) {
    }

    public function handle(array $update): void
    {
        $message = $update['message'] ?? null;
        if (!is_array($message)) {
            return;
        }

        $text = trim((string) ($message['text'] ?? ''));
        if (!str_starts_with($text, '/start')) {
            return;
        }

        $fromUser = $message['from'] ?? [];
        $userId = (int) ($fromUser['id'] ?? 0);
        $chatId = (int) ($message['chat']['id'] ?? 0);

        if ($userId <= 0 || $chatId === 0) {
            return;
        }

        $this->database->ensureUser($fromUser);

        if (preg_match('/^\/start\s+ref_(\d+)/', $text, $m) === 1) {
            $referrerId = (int) ($m[1] ?? 0);
            if ($this->settings->get('referral_enabled', '1') === '1') {
                $this->database->addReferral($referrerId, $userId);
            }
        }

        $botStatus = $this->settings->get('bot_status', 'on');

        if ($botStatus === 'off') {
            return;
        }

        if ($botStatus === 'update') {
            $this->telegram->sendMessage(
                $chatId,
                "🔄 <b>ربات در حال بروزرسانی است</b>\n\nفعلاً ربات در حال بروزرسانی می‌باشد، لطفاً بعداً اقدام نمایید."
            );

            return;
        }

        if ($this->database->userStatus($userId) === 'restricted') {
            $this->telegram->sendMessage(
                $chatId,
                "🚫 <b>دسترسی محدود شده</b>\n\nشما از ربات محدود شده‌اید و نمی‌توانید از آن استفاده کنید."
            );

            return;
        }

        if (!$this->checkChannelMembership($userId)) {
            $this->telegram->sendMessage($chatId, $this->channelLockText(), $this->channelLockKeyboard());
            return;
        }


        $this->telegram->sendMessage(
            $chatId,
            $this->menus->mainMenuText(),
            $this->menus->mainMenuKeyboard($userId)
        );
    }

    private function checkChannelMembership(int $userId): bool
    {
        $channelId = trim($this->settings->get('channel_id', ''));
        if ($channelId === '') {
            return true;
        }

        $member = $this->telegram->getChatMember($channelId, $userId);
        if (!is_array($member)) {
            return true;
        }

        $status = (string) ($member['status'] ?? '');
        return in_array($status, ['member', 'administrator', 'creator'], true);
    }

    private function channelLockText(): string
    {
        return "🔒 برای استفاده از ربات، ابتدا باید در کانال ما عضو شوید.\n\nپس از عضویت، روی «عضو شدم» بزنید.";
    }

    private function channelLockKeyboard(): array
    {
        $channelId = trim($this->settings->get('channel_id', ''));
        $channelUrl = $this->channelJoinUrl($channelId);
        return ['inline_keyboard' => [
            [['text' => '📢 عضویت در کانال', 'url' => $channelUrl]],
            [['text' => '✅ عضو شدم', 'callback_data' => 'check_channel']],
        ]];
    }

    private function channelJoinUrl(string $channelId): string
    {
        if (str_starts_with($channelId, '@')) {
            return 'https://t.me/' . ltrim($channelId, '@');
        }
        if (str_starts_with($channelId, '-100')) {
            return 'https://t.me/c/' . substr($channelId, 4);
        }
        return 'https://t.me/' . ltrim($channelId, '@');
    }
}
