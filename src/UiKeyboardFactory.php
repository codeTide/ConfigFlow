<?php

declare(strict_types=1);

namespace ConfigFlow\Bot;

final class UiKeyboardFactory implements UiKeyboardFactoryInterface
{
    public function replyMenu(array $rows, bool $isPersistent = true): array
    {
        return [
            'keyboard' => $rows,
            'resize_keyboard' => true,
            'is_persistent' => $isPersistent,
            'one_time_keyboard' => false,
        ];
    }

    public function main(): array
    {
        return $this->replyMenu([[UiLabels::BTN_MAIN]]);
    }

    public function navBackMain(): array
    {
        return $this->replyMenu([[UiLabels::BTN_BACK, UiLabels::BTN_MAIN]]);
    }

    public function navBack(): array
    {
        return $this->back();
    }

    public function navCancel(): array
    {
        return $this->cancel();
    }

    public function back(): array
    {
        return $this->replyMenu([[UiLabels::BTN_BACK]]);
    }

    public function cancel(): array
    {
        return $this->replyMenu([[UiLabels::BTN_CANCEL]]);
    }

    public function confirm(string $yesLabel = UiLabels::BTN_CONFIRM_YES, string $noLabel = UiLabels::BTN_CONFIRM_NO): array
    {
        return $this->replyMenu([[trim($yesLabel), trim($noLabel)]]);
    }

    public function choiceList(array $choices, int $columns = 2, bool $includeBack = true, bool $includeMain = true): array
    {
        $columns = max(1, $columns);
        $clean = [];
        foreach ($choices as $choice) {
            $text = trim((string) $choice);
            if ($text !== '') {
                $clean[] = $text;
            }
        }
        $rows = [];
        while ($clean !== []) {
            $rows[] = array_splice($clean, 0, $columns);
        }
        if ($includeBack || $includeMain) {
            $nav = [];
            if ($includeBack) {
                $nav[] = UiLabels::BTN_BACK;
            }
            if ($includeMain) {
                $nav[] = UiLabels::BTN_MAIN;
            }
            $rows[] = $nav;
        }

        return $this->replyMenu($rows);
    }

    public function inlineUrl(string $text, string $url): array
    {
        return $this->inlineUrlRows([['text' => $text, 'url' => $url]]);
    }

    public function inlineUrlRows(array $buttons): array
    {
        $rows = [];
        foreach ($buttons as $button) {
            $text = trim((string) ($button['text'] ?? ''));
            $url = trim((string) ($button['url'] ?? ''));

            if ($text === '' || $url === '' || !preg_match('#^(https?://|tg://)#i', $url)) {
                $message = 'Inline keyboard is URL-only. Invalid or missing URL detected.';
                error_log($message . ' text=' . $text . ' url=' . $url);
                throw new InvalidInlineKeyboardException($message);
            }

            $rows[] = [[
                'text' => $text,
                'url' => $url,
            ]];
        }

        return ['inline_keyboard' => $rows];
    }
}
