<?php

declare(strict_types=1);

namespace ConfigFlow\Bot;

final class UiLabels
{
    public const BTN_BACK = '↩️ بازگشت';
    public const BTN_MAIN = '🏠 منوی اصلی';
    public const BTN_CANCEL = '❌ انصراف';

    public const BTN_CONFIRM_YES = '✅ تایید';
    public const BTN_CONFIRM_NO = self::BTN_CANCEL;

    private function __construct()
    {
    }
}
