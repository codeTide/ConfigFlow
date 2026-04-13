<?php

declare(strict_types=1);

namespace ConfigFlow\Bot;

final class UiTextBlock
{
    /**
     * @param list<UiTextLine> $lines
     */
    public function __construct(
        public string $title,
        public array $lines = [],
        public ?string $tipBlockquote = null,
    ) {
    }
}
