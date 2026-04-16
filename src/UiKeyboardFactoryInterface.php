<?php

declare(strict_types=1);

namespace ConfigFlow\Bot;

interface UiKeyboardFactoryInterface
{
    /** @param list<list<string>> $rows */
    public function replyMenu(array $rows, bool $isPersistent = true): array;

    public function main(): array;

    public function navBackMain(): array;

    public function navBack(): array;

    public function back(): array;

    public function confirm(string $yesLabel = UiLabels::BTN_CONFIRM_YES, string $noLabel = UiLabels::BTN_CONFIRM_NO): array;

    /** @param list<string> $choices */
    public function choiceList(array $choices, int $columns = 2, bool $includeBack = true, bool $includeMain = true): array;

    public function inlineUrl(string $text, string $url): array;

    /** @param list<array{text:string,url:string}> $buttons */
    public function inlineUrlRows(array $buttons): array;
}
