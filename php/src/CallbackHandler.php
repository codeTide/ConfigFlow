<?php

declare(strict_types=1);

namespace ConfigFlow\Bot;

final class CallbackHandler
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

        if ($data === 'nav:main') {
            $this->telegram->editMessageText(
                $chatId,
                $messageId,
                $this->menus->mainMenuText(),
                $this->menus->mainMenuKeyboard($userId)
            );
            $this->telegram->answerCallbackQuery($callbackId);
            return;
        }

        if ($data === 'profile') {
            $this->telegram->editMessageText(
                $chatId,
                $messageId,
                $this->menus->profileText($userId),
                KeyboardBuilder::backToMain()
            );
            $this->telegram->answerCallbackQuery($callbackId);
            return;
        }

        if ($data === 'support') {
            $this->telegram->editMessageText(
                $chatId,
                $messageId,
                $this->menus->supportText(),
                KeyboardBuilder::backToMain()
            );
            $this->telegram->answerCallbackQuery($callbackId);
            return;
        }

        if ($data === 'my_configs') {
            $this->telegram->editMessageText(
                $chatId,
                $messageId,
                $this->menus->myConfigsText($userId),
                KeyboardBuilder::backToMain()
            );
            $this->telegram->answerCallbackQuery($callbackId);
            return;
        }

        $this->telegram->answerCallbackQuery($callbackId, 'این بخش در فاز بعدی مهاجرت تکمیل می‌شود.');
    }
}
