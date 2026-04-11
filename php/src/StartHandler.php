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

        $this->telegram->sendMessage(
            $chatId,
            $this->menus->mainMenuText(),
            $this->menus->mainMenuKeyboard($userId)
        );
    }
}
