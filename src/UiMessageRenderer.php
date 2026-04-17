<?php

declare(strict_types=1);

namespace ConfigFlow\Bot;

final class UiMessageRenderer
{
    public function __construct(
        private ?UiJsonCatalog $catalog = null,
    ) {
        $this->catalog ??= new UiJsonCatalog();
    }

    /**
     * @param array<string,scalar|null> $vars
     * @param list<string> $trustedHtmlVars
     */
    public function render(string $key, array $vars = [], array $trustedHtmlVars = []): string
    {
        $template = $this->catalog->getRaw($key);
        if (!is_string($template)) {
            throw new \UnexpectedValueException(sprintf('Localization key "%s" must be a string template.', $key));
        }

        $pairs = [];
        foreach ($vars as $name => $value) {
            $name = (string) $name;
            $stringValue = (string) ($value ?? '');
            if (!in_array($name, $trustedHtmlVars, true)) {
                $stringValue = htmlspecialchars($stringValue, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            }
            $pairs['{' . $name . '}'] = $stringValue;
            $pairs['{{' . $name . '}}'] = $stringValue;
        }

        $rendered = strtr($template, $pairs);

        if ($this->hasUnresolvedPlaceholders($rendered)) {
            error_log(sprintf('UiMessageRenderer unresolved placeholders for key "%s".', $key));
        }

        return $rendered;
    }

    private function hasUnresolvedPlaceholders(string $text): bool
    {
        return preg_match('/(?<!\{)\{[a-zA-Z0-9_]+\}(?!\})/', $text) === 1
            || preg_match('/\{\{[a-zA-Z0-9_]+\}\}/', $text) === 1;
    }
}
