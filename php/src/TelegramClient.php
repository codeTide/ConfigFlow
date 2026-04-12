<?php

declare(strict_types=1);

namespace ConfigFlow\Bot;

final class TelegramClient
{
    private string $baseUrl;
    private string $fileBaseUrl;

    public function __construct(string $botToken)
    {
        $this->baseUrl = 'https://api.telegram.org/bot' . $botToken . '/';
        $this->fileBaseUrl = 'https://api.telegram.org/file/bot' . $botToken . '/';
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

    public function getChatMember(string $chatId, int $userId): ?array
    {
        $result = $this->requestWithResult('getChatMember', [
            'chat_id' => $chatId,
            'user_id' => $userId,
        ]);

        return is_array($result) ? $result : null;
    }

    public function createForumTopic(int $chatId, string $name): ?int
    {
        $result = $this->requestWithResult('createForumTopic', [
            'chat_id' => $chatId,
            'name' => $name,
        ]);
        if (!is_array($result)) {
            return null;
        }
        $threadId = (int) ($result['message_thread_id'] ?? 0);
        return $threadId > 0 ? $threadId : null;
    }

    public function sendTopicMessage(int $chatId, int $threadId, string $text, ?array $replyMarkup = null): void
    {
        $payload = [
            'chat_id' => $chatId,
            'message_thread_id' => $threadId,
            'text' => $text,
            'parse_mode' => 'HTML',
        ];
        if ($replyMarkup !== null) {
            $payload['reply_markup'] = json_encode($replyMarkup, JSON_UNESCAPED_UNICODE);
        }
        $this->request('sendMessage', $payload);
    }

    public function sendDocumentFile(int $chatId, string $filePath, string $caption = '', ?int $threadId = null): void
    {
        if (!is_file($filePath)) {
            return;
        }
        $payload = [
            'chat_id' => $chatId,
            'document' => new \CURLFile($filePath),
        ];
        if ($caption !== '') {
            $payload['caption'] = $caption;
            $payload['parse_mode'] = 'HTML';
        }
        if ($threadId !== null && $threadId > 0) {
            $payload['message_thread_id'] = $threadId;
        }
        $this->requestMultipart('sendDocument', $payload);
    }

    public function downloadFileById(string $fileId): ?string
    {
        $res = $this->requestWithResult('getFile', ['file_id' => $fileId]);
        $filePath = is_array($res) ? (string) ($res['file_path'] ?? '') : '';
        if ($filePath === '') {
            return null;
        }
        $ch = curl_init($this->fileBaseUrl . ltrim($filePath, '/'));
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
        ]);
        $raw = curl_exec($ch);
        curl_close($ch);
        return is_string($raw) && $raw !== '' ? $raw : null;
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

    private function requestWithResult(string $method, array $payload): ?array
    {
        $ch = curl_init($this->baseUrl . $method);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
        ]);
        $raw = curl_exec($ch);
        curl_close($ch);

        if (!is_string($raw) || $raw === '') {
            return null;
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded) || !($decoded['ok'] ?? false) || !isset($decoded['result']) || !is_array($decoded['result'])) {
            return null;
        }

        return $decoded['result'];
    }

    private function requestMultipart(string $method, array $payload): void
    {
        $ch = curl_init($this->baseUrl . $method);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
        ]);
        curl_exec($ch);
        curl_close($ch);
    }
}
