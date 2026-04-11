<?php

declare(strict_types=1);

namespace ConfigFlow\Bot;

final class CallbackHandler
{
    public function __construct(
        private Database $database,
        private TelegramClient $telegram,
        private SettingsRepository $settings,
        private MenuService $menus,
    ) {
    }

    public function handle(array $update): void
    {
        $callback = $update['callback_query'] ?? null;
        if (!is_array($callback)) {
            return;
        }

        $data = (string) ($callback['data'] ?? '');
        $message = $callback['message'] ?? [];
        $fromUser = $callback['from'] ?? [];

        $chatId = (int) ($message['chat']['id'] ?? 0);
        $messageId = (int) ($message['message_id'] ?? 0);
        $userId = (int) ($fromUser['id'] ?? 0);
        $callbackId = (string) ($callback['id'] ?? '');

        if ($chatId === 0 || $messageId === 0 || $userId === 0 || $callbackId === '') {
            return;
        }

        $this->database->ensureUser($fromUser);

        if ($this->database->userStatus($userId) === 'restricted') {
            $this->telegram->answerCallbackQuery($callbackId, 'دسترسی شما محدود شده است.');
            return;
        }

        $isAdmin = in_array($userId, Config::adminIds(), true);

        if ($data === 'nav:main') {
            $this->telegram->editMessageText(
                $chatId,
                $messageId,
                $this->menus->mainMenuText(),
                $this->menus->mainMenuKeyboard($userId)
            );
            $this->telegram->answerCallbackQuery($callbackId);
            return;
        }

        if ($data === 'admin:panel') {
            if (!$isAdmin) {
                $this->telegram->answerCallbackQuery($callbackId, 'شما دسترسی ادمین ندارید.');
                return;
            }
            $this->telegram->editMessageText(
                $chatId,
                $messageId,
                '⚙️ <b>پنل مدیریت</b>',
                KeyboardBuilder::adminPanel()
            );
            $this->telegram->answerCallbackQuery($callbackId);
            return;
        }

        if ($data === 'admin:payments') {
            if (!$isAdmin) {
                $this->telegram->answerCallbackQuery($callbackId, 'شما دسترسی ادمین ندارید.');
                return;
            }
            $items = $this->database->listWaitingWalletChargePayments();
            if ($items === []) {
                $this->telegram->editMessageText(
                    $chatId,
                    $messageId,
                    '📭 درخواست شارژ در انتظار تایید وجود ندارد.',
                    KeyboardBuilder::adminPanel()
                );
                $this->telegram->answerCallbackQuery($callbackId);
                return;
            }

            $rows = [];
            foreach ($items as $item) {
                $rows[] = [[
                    'text' => sprintf('#%d | U:%d | %d تومان', (int) $item['id'], (int) $item['user_id'], (int) $item['amount']),
                    'callback_data' => 'admin:payment:view:' . (int) $item['id'],
                ]];
            }
            $rows[] = [['text' => '🔙 بازگشت', 'callback_data' => 'admin:panel']];
            $this->telegram->editMessageText(
                $chatId,
                $messageId,
                '💳 <b>درخواست‌های شارژ در انتظار تایید</b>',
                ['inline_keyboard' => $rows]
            );
            $this->telegram->answerCallbackQuery($callbackId);
            return;
        }

        if ($data === 'admin:deliveries') {
            if (!$isAdmin) {
                $this->telegram->answerCallbackQuery($callbackId, 'شما دسترسی ادمین ندارید.');
                return;
            }
            $orders = $this->database->listPendingDeliveries();
            if ($orders === []) {
                $this->telegram->editMessageText(
                    $chatId,
                    $messageId,
                    '📭 سفارشی برای تحویل در صف وجود ندارد.',
                    KeyboardBuilder::adminPanel()
                );
                $this->telegram->answerCallbackQuery($callbackId);
                return;
            }

            $rows = [];
            foreach ($orders as $order) {
                $rows[] = [[
                    'text' => sprintf('#%d | U:%d | P:%d | %d تومان', (int) $order['id'], (int) $order['user_id'], (int) $order['package_id'], (int) $order['amount']),
                    'callback_data' => 'admin:deliver:' . (int) $order['id'],
                ]];
            }
            $rows[] = [['text' => '🔙 بازگشت', 'callback_data' => 'admin:panel']];
            $this->telegram->editMessageText(
                $chatId,
                $messageId,
                '📦 <b>صف تحویل سفارش‌ها</b>',
                ['inline_keyboard' => $rows]
            );
            $this->telegram->answerCallbackQuery($callbackId);
            return;
        }

        if (str_starts_with($data, 'admin:deliver:')) {
            if (!$isAdmin) {
                $this->telegram->answerCallbackQuery($callbackId, 'شما دسترسی ادمین ندارید.');
                return;
            }
            $orderId = (int) substr($data, strlen('admin:deliver:'));
            $result = $this->database->deliverPendingOrder($orderId);
            if (!($result['ok'] ?? false)) {
                $msg = (($result['error'] ?? '') === 'no_stock') ? 'موجودی کافی برای این پکیج وجود ندارد.' : 'تحویل سفارش انجام نشد.';
                $this->telegram->answerCallbackQuery($callbackId, $msg);
                return;
            }

            $deliveryText = "🎉 <b>سفارش شما تحویل شد</b>\n\n";
            if (($result['service_name'] ?? '') !== '') {
                $deliveryText .= "سرویس: <b>" . htmlspecialchars((string) $result['service_name']) . "</b>\n\n";
            }
            $deliveryText .= "<code>" . htmlspecialchars((string) $result['config_text']) . "</code>";
            if (($result['inquiry_link'] ?? '') !== '') {
                $deliveryText .= "\n\n🔎 لینک استعلام:\n" . htmlspecialchars((string) $result['inquiry_link']);
            }
            $this->telegram->sendMessage((int) $result['user_id'], $deliveryText);
            $this->telegram->editMessageText(
                $chatId,
                $messageId,
                "✅ سفارش <code>{$orderId}</code> تحویل شد.",
                KeyboardBuilder::adminPanel()
            );
            $this->telegram->answerCallbackQuery($callbackId);
            return;
        }

        if (str_starts_with($data, 'admin:payment:view:')) {
            if (!$isAdmin) {
                $this->telegram->answerCallbackQuery($callbackId, 'شما دسترسی ادمین ندارید.');
                return;
            }
            $paymentId = (int) substr($data, strlen('admin:payment:view:'));
            $keyboard = [
                'inline_keyboard' => [
                    [
                        ['text' => '✅ تایید', 'callback_data' => 'pay:approve:' . $paymentId],
                        ['text' => '❌ رد', 'callback_data' => 'pay:reject:' . $paymentId],
                    ],
                    [['text' => '🔙 بازگشت', 'callback_data' => 'admin:payments']],
                ],
            ];
            $this->telegram->editMessageText(
                $chatId,
                $messageId,
                "درخواست شارژ شماره <code>{$paymentId}</code>\nیک عملیات را انتخاب کنید:",
                $keyboard
            );
            $this->telegram->answerCallbackQuery($callbackId);
            return;
        }

        if (str_starts_with($data, 'pay:approve:') || str_starts_with($data, 'pay:reject:')) {
            if (!$isAdmin) {
                $this->telegram->answerCallbackQuery($callbackId, 'شما دسترسی ادمین ندارید.');
                return;
            }
            $approve = str_starts_with($data, 'pay:approve:');
            $paymentId = (int) substr($data, $approve ? strlen('pay:approve:') : strlen('pay:reject:'));
            $result = $this->database->applyWalletChargeDecision($paymentId, $approve);
            if (!($result['ok'] ?? false)) {
                $this->telegram->answerCallbackQuery($callbackId, 'این درخواست قابل پردازش نیست.');
                return;
            }

            $statusText = $approve ? '✅ تایید شد' : '❌ رد شد';
            $this->telegram->editMessageText(
                $chatId,
                $messageId,
                "درخواست <code>{$paymentId}</code> {$statusText}.",
                KeyboardBuilder::adminPanel()
            );
            $this->telegram->answerCallbackQuery($callbackId);

            $userNotice = $approve
                ? "✅ درخواست شارژ کیف پول شما تایید شد.\nمبلغ: <b>{$result['amount']}</b> تومان"
                : "❌ درخواست شارژ کیف پول شما رد شد.";
            $this->telegram->sendMessage((int) $result['user_id'], $userNotice);
            return;
        }

        if ($data === 'profile') {
            $this->telegram->editMessageText(
                $chatId,
                $messageId,
                $this->menus->profileText($userId),
                KeyboardBuilder::backToMain()
            );
            $this->telegram->answerCallbackQuery($callbackId);
            return;
        }

        if ($data === 'support') {
            $this->telegram->editMessageText(
                $chatId,
                $messageId,
                $this->menus->supportText(),
                KeyboardBuilder::backToMain()
            );
            $this->telegram->answerCallbackQuery($callbackId);
            return;
        }

        if ($data === 'my_configs') {
            $this->telegram->editMessageText(
                $chatId,
                $messageId,
                $this->menus->myConfigsText($userId),
                KeyboardBuilder::backToMain()
            );
            $this->telegram->answerCallbackQuery($callbackId);
            return;
        }

        if ($data === 'referral:menu') {
            $this->telegram->editMessageText(
                $chatId,
                $messageId,
                $this->menus->referralText($userId),
                KeyboardBuilder::referral($this->menus->referralShareUrl($userId))
            );
            $this->telegram->answerCallbackQuery($callbackId);
            return;
        }

        if ($data === 'wallet:charge' || $data === 'buy:start' || $data === 'test:start' || $data === 'agency:request') {
            if ($data === 'wallet:charge') {
                $this->database->setUserState($userId, 'await_wallet_amount');
                $this->telegram->editMessageText(
                    $chatId,
                    $messageId,
                    "💳 <b>شارژ کیف پول</b>\n\nلطفاً مبلغ موردنظر را به تومان ارسال کنید.",
                    KeyboardBuilder::backToMain()
                );
                $this->telegram->answerCallbackQuery($callbackId);
                return;
            }

            if ($data === 'buy:start') {
                $types = $this->database->getActiveTypes();
                if ($types === []) {
                    $this->telegram->answerCallbackQuery($callbackId, 'فعلاً سرویسی برای خرید فعال نیست.');
                    return;
                }

                $rows = [];
                foreach ($types as $type) {
                    $rows[] = [[
                        'text' => (string) ($type['name'] ?? '—'),
                        'callback_data' => 'buy:type:' . (int) $type['id'],
                    ]];
                }
                $rows[] = [['text' => '🔙 بازگشت', 'callback_data' => 'nav:main']];

                $this->telegram->editMessageText(
                    $chatId,
                    $messageId,
                    "🛒 <b>خرید کانفیگ</b>\n\nنوع سرویس موردنظر را انتخاب کنید:",
                    ['inline_keyboard' => $rows]
                );
                $this->telegram->answerCallbackQuery($callbackId);
                return;
            }

            $this->telegram->answerCallbackQuery($callbackId, 'این بخش در فاز بعدی مهاجرت تکمیل می‌شود.');
            return;
        }

        if (str_starts_with($data, 'buy:type:')) {
            $typeId = (int) substr($data, strlen('buy:type:'));
            $packages = $this->database->getActivePackagesByType($typeId);
            if ($packages === []) {
                $this->telegram->answerCallbackQuery($callbackId, 'پکیجی برای این نوع سرویس یافت نشد.');
                return;
            }

            $rows = [];
            foreach ($packages as $pkg) {
                $label = sprintf('%s | %sGB | %s روز | %s تومان', (string) $pkg['name'], (string) $pkg['volume_gb'], (string) $pkg['duration_days'], (string) $pkg['price']);
                $rows[] = [[
                    'text' => $label,
                    'callback_data' => 'buy:pkg:' . (int) $pkg['id'],
                ]];
            }
            $rows[] = [['text' => '🔙 بازگشت', 'callback_data' => 'buy:start']];

            $this->telegram->editMessageText(
                $chatId,
                $messageId,
                '📦 یک پکیج را انتخاب کنید:',
                ['inline_keyboard' => $rows]
            );
            $this->telegram->answerCallbackQuery($callbackId);
            return;
        }

        if (str_starts_with($data, 'buy:pkg:')) {
            $packageId = (int) substr($data, strlen('buy:pkg:'));
            $package = $this->database->getPackage($packageId);
            if ($package === null) {
                $this->telegram->answerCallbackQuery($callbackId, 'پکیج پیدا نشد.');
                return;
            }

            $text = "💰 <b>پرداخت سفارش</b>\n\n"
                . "پکیج: <b>" . htmlspecialchars((string) $package['name']) . "</b>\n"
                . "قیمت: <b>" . (int) $package['price'] . "</b> تومان\n\n"
                . "روش پرداخت را انتخاب کنید:";
            $rows = [];
            $rows[] = [['text' => '💳 پرداخت با کیف پول', 'callback_data' => 'buy:wallet:' . $packageId]];
            if ($this->settings->get('gw_card_enabled', '0') === '1') {
                $rows[] = [['text' => '🏦 کارت به کارت', 'callback_data' => 'buy:card:' . $packageId]];
            }
            if ($this->settings->get('gw_crypto_enabled', '0') === '1') {
                $rows[] = [['text' => '💎 پرداخت کریپتو', 'callback_data' => 'buy:crypto:' . $packageId]];
            }
            if ($this->settings->get('gw_tetrapay_enabled', '0') === '1') {
                $rows[] = [['text' => '🏧 پرداخت TetraPay', 'callback_data' => 'buy:tetrapay:' . $packageId]];
            }
            $rows[] = [['text' => '🔙 بازگشت', 'callback_data' => 'buy:type:' . (int) $package['type_id']]];
            $keyboard = ['inline_keyboard' => $rows];
            $this->telegram->editMessageText($chatId, $messageId, $text, $keyboard);
            $this->telegram->answerCallbackQuery($callbackId);
            return;
        }

        if (str_starts_with($data, 'buy:card:') || str_starts_with($data, 'buy:crypto:') || str_starts_with($data, 'buy:tetrapay:')) {
            $method = str_starts_with($data, 'buy:card:') ? 'card' : (str_starts_with($data, 'buy:crypto:') ? 'crypto' : 'tetrapay');
            $packageId = (int) substr($data, strlen('buy:' . $method . ':'));
            $package = $this->database->getPackage($packageId);
            if ($package === null) {
                $this->telegram->answerCallbackQuery($callbackId, 'پکیج پیدا نشد.');
                return;
            }

            $amount = (int) $package['price'];
            $paymentId = $this->database->createPayment([
                'kind' => 'purchase',
                'user_id' => $userId,
                'package_id' => $packageId,
                'amount' => $amount,
                'payment_method' => $method,
                'status' => $method === 'tetrapay' ? 'waiting_gateway' : 'waiting_admin',
                'created_at' => gmdate('Y-m-d H:i:s'),
            ]);
            $pendingId = $this->database->createPendingOrder([
                'user_id' => $userId,
                'package_id' => $packageId,
                'payment_id' => $paymentId,
                'amount' => $amount,
                'payment_method' => $method,
                'created_at' => gmdate('Y-m-d H:i:s'),
                'status' => 'waiting_payment',
            ]);

            if ($method === 'card') {
                $card = htmlspecialchars($this->settings->get('payment_card', '---'));
                $bank = htmlspecialchars($this->settings->get('payment_bank', ''));
                $owner = htmlspecialchars($this->settings->get('payment_owner', ''));
                $text = "🏦 <b>پرداخت کارت به کارت</b>\n\n"
                    . "شماره کارت: <code>{$card}</code>\n"
                    . ($bank !== '' ? "بانک: {$bank}\n" : '')
                    . ($owner !== '' ? "به نام: {$owner}\n" : '')
                    . "\nشناسه سفارش: <code>{$pendingId}</code>\n"
                    . "مبلغ: <b>{$amount}</b> تومان\n\n"
                    . "پس از واریز، رسید را برای ادمین ارسال کنید.";
            } elseif ($method === 'crypto') {
                $text = "💎 <b>پرداخت کریپتو</b>\n\n"
                    . "شناسه سفارش: <code>{$pendingId}</code>\n"
                    . "مبلغ معادل ریالی: <b>{$amount}</b> تومان\n\n"
                    . "درگاه کریپتو در فاز بعدی تکمیل API می‌شود. فعلاً با ادمین هماهنگ کنید.";
            } else {
                $text = "🏧 <b>پرداخت TetraPay</b>\n\n"
                    . "شناسه سفارش: <code>{$pendingId}</code>\n"
                    . "مبلغ: <b>{$amount}</b> تومان\n\n"
                    . "اتصال API تتراپی در فاز بعدی تکمیل می‌شود.";
            }

            $this->telegram->editMessageText($chatId, $messageId, $text, KeyboardBuilder::backToMain());
            $this->telegram->answerCallbackQuery($callbackId);
            return;
        }

        if (str_starts_with($data, 'buy:wallet:')) {
            $packageId = (int) substr($data, strlen('buy:wallet:'));
            $result = $this->database->walletPayPackage($userId, $packageId);
            if (!($result['ok'] ?? false)) {
                if (($result['error'] ?? '') === 'insufficient_balance') {
                    $this->telegram->answerCallbackQuery($callbackId, 'موجودی کیف پول کافی نیست.');
                    return;
                }
                $this->telegram->answerCallbackQuery($callbackId, 'خطا در ثبت سفارش. دوباره تلاش کنید.');
                return;
            }

            $this->telegram->editMessageText(
                $chatId,
                $messageId,
                "✅ خرید با کیف پول ثبت شد.\n\n"
                . "شناسه پرداخت: <code>" . (int) $result['payment_id'] . "</code>\n"
                . "مبلغ: <b>" . (int) $result['price'] . "</b> تومان\n"
                . "موجودی جدید: <b>" . (int) $result['new_balance'] . "</b> تومان\n\n"
                . "سفارش شما در صف تحویل قرار گرفت.",
                KeyboardBuilder::backToMain()
            );
            $this->telegram->answerCallbackQuery($callbackId);
            return;
        }

        $this->telegram->answerCallbackQuery($callbackId, 'این بخش در فاز بعدی مهاجرت تکمیل می‌شود.');
    }
}
