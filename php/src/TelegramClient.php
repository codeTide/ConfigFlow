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

    public function sendMessage(int $chatId, string $text): void
    {
        $payload = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML',
        ];

        $this->request('sendMessage', $payload);
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
