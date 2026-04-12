<?php

declare(strict_types=1);

namespace ConfigFlow\Bot;

final class TelegramClient
{
    private string $baseUrl;

    public function __construct(string $botToken)
    {
        $this->baseUrl = 'https://api.telegram.org/bot' . $botToken . '/';
    }

    public function sendMessage(int $chatId, string $text, ?array $replyMarkup = null): void
    {
        $payload = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML',
        ];
        if ($replyMarkup !== null) {
            $payload['reply_markup'] = json_encode($replyMarkup, JSON_UNESCAPED_UNICODE);
        }

        $this->request('sendMessage', $payload);
    }

    public function editMessageText(int $chatId, int $messageId, string $text, ?array $replyMarkup = null): void
    {
        $payload = [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $text,
            'parse_mode' => 'HTML',
        ];
        if ($replyMarkup !== null) {
            $payload['reply_markup'] = json_encode($replyMarkup, JSON_UNESCAPED_UNICODE);
        }

        $this->request('editMessageText', $payload);
    }

    public function answerCallbackQuery(string $callbackQueryId, string $text = ''): void
    {
        $payload = ['callback_query_id' => $callbackQueryId];
        if ($text !== '') {
            $payload['text'] = $text;
        }
        $this->request('answerCallbackQuery', $payload);
    }

    public function copyMessage(int $chatId, int $fromChatId, int $messageId): void
    {
        $payload = [
            'chat_id' => $chatId,
            'from_chat_id' => $fromChatId,
            'message_id' => $messageId,
        ];
        $this->request('copyMessage', $payload);
    }

    public function forwardMessage(int $chatId, int $fromChatId, int $messageId): void
    {
        $payload = [
            'chat_id' => $chatId,
            'from_chat_id' => $fromChatId,
            'message_id' => $messageId,
        ];
        $this->request('forwardMessage', $payload);
    }

    private function request(string $method, array $payload): void
    {
        $ch = curl_init($this->baseUrl . $method);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
        ]);
        curl_exec($ch);
        curl_close($ch);
    }
}
