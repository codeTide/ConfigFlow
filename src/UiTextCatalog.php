<?php

declare(strict_types=1);

namespace ConfigFlow\Bot;

final class UiTextCatalog implements UiTextCatalogInterface
{
    /** @var list<string> */
    private array $genericTips;

    public function __construct(
        private ?UiJsonCatalog $catalog = null,
    ) {
        $this->catalog ??= new UiJsonCatalog();
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

    public function multi(UiTextBlock $block): string
    {
        $title = trim($block->title);
        if ($title === '') {
            throw new \InvalidArgumentException('UiTextBlock title cannot be empty.');
        }

        $parts = [$title];
        if ($block->lines !== []) {
            $parts[] = '';
            foreach ($block->lines as $line) {
                if (!$line instanceof UiTextLine) {
                    throw new \InvalidArgumentException('UiTextBlock lines must be UiTextLine instances.');
                }
                $parts[] = sprintf('%s %s: %s', trim($line->emoji), trim($line->label), trim($line->valueHtml));
            }
        }

        if ($block->tipBlockquote !== null && trim($block->tipBlockquote) !== '') {
            $tip = $this->normalizeTip($block->tipBlockquote);
            $parts[] = '';
            $parts[] = '<blockquote>' . htmlspecialchars($tip) . '</blockquote>';
        }

        return implode("\n", $parts);
    }

    public function paymentCreated(int $paymentId, int $amount, string $title, ?string $tip = null): string
    {
        $escapedTitle = htmlspecialchars(trim($title));

        $block = new UiTextBlock(
            title: $this->catalog->get('payments.created.title', ['title' => $escapedTitle]),
            lines: [
                new UiTextLine($this->catalog->get('emojis.receipt'), $this->catalog->get('payments.created.id_label'), '<code>' . $paymentId . '</code>'),
                new UiTextLine($this->catalog->get('emojis.money'), $this->catalog->get('payments.created.amount_label'), $this->catalog->get('payments.created.amount_value', ['amount' => $amount])),
            ],
            tipBlockquote: $tip,
        );

        return $this->multi($block);
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
        $tip = trim(preg_replace('/\s+/u', ' ', $tip) ?? '');
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
