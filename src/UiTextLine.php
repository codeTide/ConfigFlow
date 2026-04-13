<?php

declare(strict_types=1);

namespace ConfigFlow\Bot;

final class UiTextLine
{
    public function __construct(
        public string $emoji,
        public string $label,
        public string $valueHtml,
    ) {
    }
}
