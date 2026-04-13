<?php

declare(strict_types=1);

namespace ConfigFlow\Bot;

final class UiLabels
{
    // Backward-compatible constants for flows that are not migrated yet.
    public const BTN_BACK = '[legacy] buttons.back';
    public const BTN_MAIN = '[legacy] buttons.main_menu';
    public const BTN_CANCEL = '[legacy] buttons.cancel';

    public const BTN_CONFIRM_YES = '[legacy] buttons.confirm_yes';
    public const BTN_CONFIRM_NO = self::BTN_CANCEL;

    private function __construct()
    {
    }

    public static function back(?UiJsonCatalog $catalog = null): string
    {
        return self::resolve($catalog, 'buttons.back', self::BTN_BACK);
    }

    public static function main(?UiJsonCatalog $catalog = null): string
    {
        return self::resolve($catalog, 'buttons.main_menu', self::BTN_MAIN);
    }

    public static function cancel(?UiJsonCatalog $catalog = null): string
    {
        return self::resolve($catalog, 'buttons.cancel', self::BTN_CANCEL);
    }

    public static function confirmYes(?UiJsonCatalog $catalog = null): string
    {
        return self::resolve($catalog, 'buttons.confirm_yes', self::BTN_CONFIRM_YES);
    }

    public static function confirmNo(?UiJsonCatalog $catalog = null): string
    {
        return self::resolve($catalog, 'buttons.confirm_no', self::BTN_CONFIRM_NO);
    }

    private static function resolve(?UiJsonCatalog $catalog, string $key, string $fallback): string
    {
        $catalog ??= new UiJsonCatalog();

        try {
            return $catalog->get($key);
        } catch (\Throwable) {
            return $fallback;
        }
    }
}
