<?php

declare(strict_types=1);

namespace ConfigFlow\Bot;

final class UiJsonCatalog
{
    /** @var array<string,mixed>|null */
    private ?array $catalog = null;

    public function __construct(
        private ?string $filePath = null,
    ) {
        $this->filePath ??= dirname(__DIR__) . '/resources/lang/fa.json';
    }

    public function get(string $key, array $replacements = []): string
    {
        $value = $this->getRaw($key);
        if (!is_string($value)) {
            throw new \UnexpectedValueException(sprintf('Localization key "%s" must be a string.', $key));
        }

        return $this->replacePlaceholders($value, $replacements);
    }

    /** @return list<string> */
    public function getList(string $key): array
    {
        $value = $this->getRaw($key);
        if (!is_array($value)) {
            throw new \UnexpectedValueException(sprintf('Localization key "%s" must be an array.', $key));
        }

        $list = [];
        foreach ($value as $item) {
            if (is_string($item)) {
                $list[] = $item;
            }
        }

        return $list;
    }

    /** @return mixed */
    public function getRaw(string $key): mixed
    {
        $data = $this->load();
        $cursor = $data;

        foreach (explode('.', $key) as $segment) {
            if (!is_array($cursor) || !array_key_exists($segment, $cursor)) {
                throw new \OutOfBoundsException(sprintf('Missing localization key "%s" in %s.', $key, $this->filePath));
            }
            $cursor = $cursor[$segment];
        }

        return $cursor;
    }

    /** @return array<string,mixed> */
    private function load(): array
    {
        if ($this->catalog !== null) {
            return $this->catalog;
        }

        if (!is_file($this->filePath)) {
            throw new \RuntimeException(sprintf('Localization file not found: %s', $this->filePath));
        }

        $json = file_get_contents($this->filePath);
        if ($json === false) {
            throw new \RuntimeException(sprintf('Unable to read localization file: %s', $this->filePath));
        }

        $decoded = json_decode($json, true);
        if (!is_array($decoded)) {
            throw new \RuntimeException(sprintf('Invalid localization JSON in file: %s', $this->filePath));
        }

        $this->catalog = $decoded;

        return $this->catalog;
    }

    private function replacePlaceholders(string $template, array $replacements): string
    {
        $pairs = [];
        foreach ($replacements as $key => $value) {
            $pairs['{' . (string) $key . '}'] = (string) $value;
        }

        return strtr($template, $pairs);
    }
}
