<?php

declare(strict_types=1);

namespace ConfigFlow\Bot;

final class UiTextCatalog implements UiTextCatalogInterface
{
    /** @var list<string> */
    private array $genericTips;

    public function __construct(
        private ?UiJsonCatalog $catalog = null,
        private ?UiMessageRenderer $messageRenderer = null,
    ) {
        $this->catalog ??= new UiJsonCatalog();
        $this->messageRenderer ??= new UiMessageRenderer($this->catalog);
        $this->genericTips = $this->catalog->getList('validation.generic_tips');
    }

    public function success(string $message): string
    {
        return $this->singleLine($this->catalog->get('emojis.success'), $message);
    }

    public function error(string $message): string
    {
        return $this->singleLine($this->catalog->get('emojis.error'), $message);
    }

    public function warning(string $message): string
    {
        return $this->singleLine($this->catalog->get('emojis.warning'), $message);
    }

    public function info(string $message): string
    {
        return $this->singleLine($this->catalog->get('emojis.info'), $message);
    }

    public function paymentCreated(int $paymentId, int $amount, string $title, ?string $tip = null): string
    {
        $key = $tip !== null && trim($tip) !== '' ? 'payments.created.overview_with_tip' : 'payments.created.overview';
        return $this->messageRenderer->render($key, [
            'title' => trim($title),
            'payment_id' => $paymentId,
            'amount' => $amount,
            'tip' => $tip !== null ? $this->normalizeTip($tip) : '',
        ]);
    }

    private function singleLine(string $emoji, string $message): string
    {
        $text = trim($message);
        if ($text === '') {
            throw new \InvalidArgumentException('Message cannot be empty.');
        }
        if (str_contains($text, "\n")) {
            throw new \InvalidArgumentException('Single-line message must not contain new lines.');
        }
        if (!preg_match('/[\.؟!]$/u', $text)) {
            $text .= '.';
        }

        return $emoji . ' ' . $text;
    }

    private function normalizeTip(string $tip): string
    {
        $tip = trim((string) preg_replace('/[^\S\n]+/u', ' ', $tip));
        if ($tip === '') {
            throw new \InvalidArgumentException('Tip cannot be empty.');
        }

        $words = preg_split('/\s+/u', $tip) ?: [];
        if (count($words) < 8 || $this->stringLength($tip) < 45) {
            throw new \InvalidArgumentException('Tip must be at least about 1 to 1.5 lines and context-rich.');
        }

        foreach ($this->genericTips as $generic) {
            if ($this->stringLower($tip) === $this->stringLower($generic)) {
                throw new \InvalidArgumentException('Generic tips are not allowed.');
            }
        }

        return $tip;
    }

    private function stringLength(string $value): int
    {
        if (function_exists('mb_strlen')) {
            return mb_strlen($value, 'UTF-8');
        }

        return strlen($value);
    }

    private function stringLower(string $value): string
    {
        if (function_exists('mb_strtolower')) {
            return mb_strtolower($value, 'UTF-8');
        }

        return strtolower($value);
    }
}
