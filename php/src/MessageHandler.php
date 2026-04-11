<?php

declare(strict_types=1);

namespace ConfigFlow\Bot;

final class MessageHandler
{
    public function __construct(
        private Database $database,
        private TelegramClient $telegram,
    ) {
    }

    public function handle(array $update): void
    {
        $message = $update['message'] ?? null;
        if (!is_array($message)) {
            return;
        }

        $fromUser = $message['from'] ?? [];
        $chatId = (int) ($message['chat']['id'] ?? 0);
        $userId = (int) ($fromUser['id'] ?? 0);
        if ($chatId === 0 || $userId === 0) {
            return;
        }

        $state = $this->database->getUserState($userId);
        if ($state === null) {
            return;
        }

        $text = trim((string) ($message['text'] ?? ''));

        if ($state['state_name'] === 'await_wallet_amount') {
            if ($text === '' || str_starts_with($text, '/')) {
                return;
            }
            $amount = (int) preg_replace('/\D+/', '', $text);
            if ($amount <= 0) {
                $this->telegram->sendMessage($chatId, '⚠️ لطفاً مبلغ معتبر وارد کنید.');
                return;
            }

            $paymentId = $this->database->createPayment([
                'kind' => 'wallet_charge',
                'user_id' => $userId,
                'package_id' => null,
                'amount' => $amount,
                'payment_method' => 'card',
                'status' => 'waiting_admin',
                'created_at' => gmdate('Y-m-d H:i:s'),
            ]);

            $this->database->clearUserState($userId);
            $this->telegram->sendMessage(
                $chatId,
                "✅ درخواست شارژ کیف پول ثبت شد.\n\n"
                . "شماره درخواست: <code>{$paymentId}</code>\n"
                . "مبلغ: <b>{$amount}</b> تومان\n\n"
                . 'بعد از تایید ادمین موجودی شما شارژ می‌شود.'
            );

            $adminKeyboard = [
                'inline_keyboard' => [
                    [
                        ['text' => '✅ تایید', 'callback_data' => 'pay:approve:' . $paymentId],
                        ['text' => '❌ رد', 'callback_data' => 'pay:reject:' . $paymentId],
                    ],
                ],
            ];
            foreach (Config::adminIds() as $adminId) {
                $this->telegram->sendMessage(
                    (int) $adminId,
                    "💳 <b>درخواست شارژ کیف پول جدید</b>\n\n"
                    . "شماره: <code>{$paymentId}</code>\n"
                    . "کاربر: <code>{$userId}</code>\n"
                    . "مبلغ: <b>{$amount}</b> تومان",
                    $adminKeyboard
                );
            }
            return;
        }

        if ($state['state_name'] === 'await_card_receipt') {
            $payload = $state['payload'] ?? [];
            $paymentId = (int) ($payload['payment_id'] ?? 0);
            if ($paymentId <= 0) {
                $this->database->clearUserState($userId);
                return;
            }

            $fileId = null;
            if (isset($message['photo']) && is_array($message['photo']) && $message['photo'] !== []) {
                $last = end($message['photo']);
                $fileId = is_array($last) ? (string) ($last['file_id'] ?? '') : null;
            } elseif (isset($message['document']) && is_array($message['document'])) {
                $fileId = (string) ($message['document']['file_id'] ?? '');
            }
            $caption = trim((string) ($message['caption'] ?? ''));
            $receiptText = $caption !== '' ? $caption : ($text !== '' ? $text : null);

            if (($fileId === null || $fileId === '') && ($receiptText === null || $receiptText === '')) {
                $this->telegram->sendMessage($chatId, '⚠️ لطفاً رسید را به‌صورت عکس/فایل یا متن ارسال کنید.');
                return;
            }

            $this->database->attachPaymentReceipt($paymentId, $fileId ?: null, $receiptText);
            $this->database->clearUserState($userId);
            $this->telegram->sendMessage(
                $chatId,
                "✅ رسید شما ثبت شد و برای بررسی ادمین ارسال گردید.\nشماره پرداخت: <code>{$paymentId}</code>"
            );

            $adminKeyboard = [
                'inline_keyboard' => [
                    [
                        ['text' => '✅ تایید', 'callback_data' => 'pay:approve:' . $paymentId],
                        ['text' => '❌ رد', 'callback_data' => 'pay:reject:' . $paymentId],
                    ],
                ],
            ];
            foreach (Config::adminIds() as $adminId) {
                $this->telegram->sendMessage(
                    (int) $adminId,
                    "🧾 <b>رسید کارت‌به‌کارت جدید</b>\n\n"
                    . "پرداخت: <code>{$paymentId}</code>\n"
                    . "کاربر: <code>{$userId}</code>\n"
                    . ($receiptText ? "توضیح: " . htmlspecialchars($receiptText) . "\n" : ''),
                    $adminKeyboard
                );
            }
        }

        if ($state['state_name'] === 'await_crypto_tx') {
            $payload = $state['payload'] ?? [];
            $paymentId = (int) ($payload['payment_id'] ?? 0);
            if ($paymentId <= 0) {
                $this->database->clearUserState($userId);
                return;
            }

            $raw = trim((string) ($message['text'] ?? ''));
            $parts = preg_split('/\s+/', $raw) ?: [];
            $txHash = trim((string) ($parts[0] ?? ''));
            $claimedAmount = null;
            if (isset($parts[1]) && is_numeric(str_replace(',', '.', (string) $parts[1]))) {
                $claimedAmount = (float) str_replace(',', '.', (string) $parts[1]);
            }
            if ($txHash === '' || str_starts_with($txHash, '/')) {
                $this->telegram->sendMessage($chatId, '⚠️ لطفاً TX Hash معتبر ارسال کنید.');
                return;
            }
            if (strlen($txHash) < 10) {
                $this->telegram->sendMessage($chatId, '⚠️ طول TX Hash معتبر نیست.');
                return;
            }

            $ok = $this->database->submitCryptoTxHash($paymentId, $txHash, $claimedAmount);
            if (!$ok) {
                $this->telegram->sendMessage($chatId, '❌ ثبت TX Hash انجام نشد. لطفاً دوباره تلاش کنید.');
                return;
            }

            $this->database->clearUserState($userId);
            $this->telegram->sendMessage(
                $chatId,
                "✅ TX Hash ثبت شد و برای بررسی ادمین ارسال گردید.\nشماره پرداخت: <code>{$paymentId}</code>"
            );

            $adminKeyboard = [
                'inline_keyboard' => [
                    [
                        ['text' => '✅ تایید', 'callback_data' => 'pay:approve:' . $paymentId],
                        ['text' => '❌ رد', 'callback_data' => 'pay:reject:' . $paymentId],
                    ],
                ],
            ];
            foreach (Config::adminIds() as $adminId) {
                $this->telegram->sendMessage(
                    (int) $adminId,
                    "💎 <b>TX Hash جدید</b>\n\n"
                    . "پرداخت: <code>{$paymentId}</code>\n"
                    . "کاربر: <code>{$userId}</code>\n"
                    . "TX: <code>" . htmlspecialchars($txHash) . "</code>\n"
                    . ($claimedAmount !== null ? "Amount: <b>{$claimedAmount}</b>\n" : ''),
                    $adminKeyboard
                );
            }
        }

        if ($state['state_name'] === 'await_free_test_note') {
            if ($text === '' || str_starts_with($text, '/')) {
                $this->telegram->sendMessage($chatId, '⚠️ لطفاً توضیح کوتاه تست را ارسال کنید.');
                return;
            }

            $this->database->clearUserState($userId);
            $requestId = $this->database->createFreeTestRequest($userId, $text);
            $this->telegram->sendMessage(
                $chatId,
                "✅ درخواست تست رایگان ثبت شد و برای بررسی ادمین ارسال گردید.\n"
                . "شناسه درخواست: <code>{$requestId}</code>"
            );

            foreach (Config::adminIds() as $adminId) {
                $this->telegram->sendMessage(
                    (int) $adminId,
                    "🎁 <b>درخواست تست رایگان جدید</b>\n\n"
                    . "شناسه: <code>{$requestId}</code>\n"
                    . "کاربر: <code>{$userId}</code>\n"
                    . "توضیح:\n" . htmlspecialchars($text),
                    [
                        'inline_keyboard' => [
                            [['text' => '👀 مشاهده درخواست', 'callback_data' => 'admin:req:free:view:' . $requestId]],
                            [['text' => '🗂 مدیریت درخواست‌ها', 'callback_data' => 'admin:requests']],
                        ],
                    ]
                );
            }
            return;
        }

        if ($state['state_name'] === 'await_agency_request') {
            if ($text === '' || str_starts_with($text, '/')) {
                $this->telegram->sendMessage($chatId, '⚠️ لطفاً متن درخواست نمایندگی را ارسال کنید.');
                return;
            }

            $this->database->clearUserState($userId);
            $requestId = $this->database->createAgencyRequest($userId, $text);
            $this->telegram->sendMessage(
                $chatId,
                "✅ درخواست نمایندگی ثبت شد و برای بررسی ادمین ارسال گردید.\n"
                . "شناسه درخواست: <code>{$requestId}</code>"
            );

            foreach (Config::adminIds() as $adminId) {
                $this->telegram->sendMessage(
                    (int) $adminId,
                    "🤝 <b>درخواست نمایندگی جدید</b>\n\n"
                    . "شناسه: <code>{$requestId}</code>\n"
                    . "کاربر: <code>{$userId}</code>\n"
                    . "متن درخواست:\n" . htmlspecialchars($text),
                    [
                        'inline_keyboard' => [
                            [['text' => '👀 مشاهده درخواست', 'callback_data' => 'admin:req:agency:view:' . $requestId]],
                            [['text' => '🗂 مدیریت درخواست‌ها', 'callback_data' => 'admin:requests']],
                        ],
                    ]
                );
            }
            return;
        }

        if ($state['state_name'] === 'await_admin_request_note') {
            if (!in_array($userId, Config::adminIds(), true)) {
                $this->database->clearUserState($userId);
                return;
            }

            $payload = $state['payload'] ?? [];
            $requestKind = (string) ($payload['request_kind'] ?? '');
            $requestId = (int) ($payload['request_id'] ?? 0);
            $approve = ((int) ($payload['approve'] ?? 0)) === 1;
            $sourceChatId = (int) ($payload['source_chat_id'] ?? 0);
            $sourceMessageId = (int) ($payload['source_message_id'] ?? 0);
            if ($requestId <= 0 || ($requestKind !== 'free' && $requestKind !== 'agency')) {
                $this->database->clearUserState($userId);
                $this->telegram->sendMessage($chatId, '❌ اطلاعات درخواست نامعتبر است.');
                return;
            }
            if ($text === '' || str_starts_with($text, '/')) {
                $this->telegram->sendMessage($chatId, '⚠️ لطفاً نوت ادمین را ارسال کنید یا «-» بفرستید.');
                return;
            }
            $adminNote = trim($text) === '-' ? null : trim($text);

            $result = $requestKind === 'free'
                ? $this->database->reviewFreeTestRequest($requestId, $approve, $adminNote)
                : $this->database->reviewAgencyRequest($requestId, $approve, $adminNote);
            if (!($result['ok'] ?? false)) {
                $msg = (($result['error'] ?? '') === 'already_reviewed')
                    ? 'این درخواست قبلاً بررسی شده است.'
                    : 'ثبت نتیجه بررسی انجام نشد.';
                $this->telegram->sendMessage($chatId, '❌ ' . $msg);
                $this->database->clearUserState($userId);
                return;
            }

            $this->database->clearUserState($userId);
            $statusText = $approve ? '✅ تایید شد' : '❌ رد شد';
            $label = $requestKind === 'free' ? 'درخواست تست رایگان' : 'درخواست نمایندگی';
            if ($sourceChatId !== 0 && $sourceMessageId !== 0) {
                $this->telegram->editMessageText(
                    $sourceChatId,
                    $sourceMessageId,
                    "{$label} <code>{$requestId}</code> {$statusText}.",
                    ['inline_keyboard' => [[['text' => '🔙 بازگشت', 'callback_data' => 'admin:requests']]]]
                );
            }
            $this->telegram->sendMessage(
                $chatId,
                "{$label} <code>{$requestId}</code> {$statusText}."
            );

            $userNotice = $approve
                ? ($requestKind === 'free' ? "✅ درخواست تست رایگان شما تایید شد." : "✅ درخواست نمایندگی شما تایید شد.")
                : ($requestKind === 'free' ? "❌ درخواست تست رایگان شما رد شد." : "❌ درخواست نمایندگی شما رد شد.");
            if ($adminNote !== null && $adminNote !== '') {
                $userNotice .= "\n\n📝 توضیح ادمین:\n" . htmlspecialchars($adminNote);
            }
            $this->telegram->sendMessage((int) ($result['user_id'] ?? 0), $userNotice);
            return;
        }

        if ($state['state_name'] === 'await_admin_type_name') {
            if (!in_array($userId, Config::adminIds(), true)) {
                $this->database->clearUserState($userId);
                return;
            }
            if ($text === '' || str_starts_with($text, '/')) {
                $this->telegram->sendMessage($chatId, '⚠️ لطفاً نام نوع سرویس را ارسال کنید.');
                return;
            }
            $typeId = $this->database->addType($text, '');
            $this->database->clearUserState($userId);
            $this->telegram->sendMessage($chatId, "✅ نوع سرویس ثبت شد. شناسه: <code>{$typeId}</code>");
            return;
        }

        if ($state['state_name'] === 'await_admin_package') {
            if (!in_array($userId, Config::adminIds(), true)) {
                $this->database->clearUserState($userId);
                return;
            }
            if ($text === '' || str_starts_with($text, '/')) {
                $this->telegram->sendMessage($chatId, '⚠️ فرمت ورودی نامعتبر است.');
                return;
            }
            $payload = $state['payload'] ?? [];
            $typeId = (int) ($payload['type_id'] ?? 0);
            $parts = array_map('trim', explode('|', $text));
            if (count($parts) !== 4) {
                $this->telegram->sendMessage($chatId, '⚠️ فرمت باید 4 بخشی و با | جدا شود.');
                return;
            }
            [$name, $volumeRaw, $durationRaw, $priceRaw] = $parts;
            $volume = (float) str_replace(',', '.', $volumeRaw);
            $duration = (int) preg_replace('/\D+/', '', $durationRaw);
            $price = (int) preg_replace('/\D+/', '', $priceRaw);
            if ($name === '' || $volume <= 0 || $duration <= 0 || $price <= 0 || $typeId <= 0) {
                $this->telegram->sendMessage($chatId, '⚠️ مقادیر واردشده معتبر نیستند.');
                return;
            }
            $packageId = $this->database->addPackage($typeId, $name, $volume, $duration, $price);
            $this->database->clearUserState($userId);
            $this->telegram->sendMessage($chatId, "✅ پکیج ثبت شد. شناسه: <code>{$packageId}</code>");
            return;
        }

        if ($state['state_name'] === 'await_admin_user_balance') {
            if (!in_array($userId, Config::adminIds(), true)) {
                $this->database->clearUserState($userId);
                return;
            }
            $payload = $state['payload'] ?? [];
            $targetUid = (int) ($payload['target_user_id'] ?? 0);
            $mode = (string) ($payload['mode'] ?? 'add');
            $amount = (int) preg_replace('/\D+/', '', $text);
            if ($targetUid <= 0 || $amount <= 0) {
                $this->telegram->sendMessage($chatId, '⚠️ مبلغ معتبر وارد کنید.');
                return;
            }
            $delta = $mode === 'sub' ? -$amount : $amount;
            $this->database->updateUserBalance($targetUid, $delta);
            $this->database->clearUserState($userId);
            $this->telegram->sendMessage(
                $chatId,
                "✅ موجودی کاربر <code>{$targetUid}</code> بروزرسانی شد.\n"
                . "تغییر اعمال‌شده: <b>" . ($delta > 0 ? '+' : '') . "{$delta}</b> تومان"
            );
            return;
        }

        if ($state['state_name'] === 'await_admin_add_config') {
            if (!in_array($userId, Config::adminIds(), true)) {
                $this->database->clearUserState($userId);
                return;
            }
            $payload = $state['payload'] ?? [];
            $typeId = (int) ($payload['type_id'] ?? 0);
            $packageId = (int) ($payload['package_id'] ?? 0);
            $raw = trim((string) ($message['text'] ?? ''));
            if ($raw === '' || str_starts_with($raw, '/')) {
                $this->telegram->sendMessage($chatId, '⚠️ لطفاً متن کانفیگ را طبق فرمت ارسال کنید.');
                return;
            }
            $chunks = preg_split('/\n---\n/', $raw) ?: [];
            if (count($chunks) < 2) {
                $this->telegram->sendMessage($chatId, '⚠️ فرمت نامعتبر است. جداکننده --- را رعایت کنید.');
                return;
            }
            $serviceName = trim((string) ($chunks[0] ?? ''));
            $configText = trim((string) ($chunks[1] ?? ''));
            $inquiry = null;
            if (isset($chunks[2])) {
                $third = trim((string) $chunks[2]);
                if (str_starts_with(mb_strtolower($third), 'inquiry ')) {
                    $inquiry = trim(substr($third, strlen('inquiry ')));
                } elseif ($third !== '') {
                    $inquiry = $third;
                }
            }
            if ($serviceName === '' || $configText === '' || $typeId <= 0 || $packageId <= 0) {
                $this->telegram->sendMessage($chatId, '⚠️ نام سرویس یا متن کانفیگ معتبر نیست.');
                return;
            }
            $configId = $this->database->addConfig($typeId, $packageId, $serviceName, $configText, $inquiry);
            $this->database->clearUserState($userId);
            $this->telegram->sendMessage($chatId, "✅ کانفیگ جدید ثبت شد. شناسه: <code>{$configId}</code>");
            return;
        }

        if ($state['state_name'] === 'await_admin_stock_search') {
            if (!in_array($userId, Config::adminIds(), true)) {
                $this->database->clearUserState($userId);
                return;
            }
            $payload = $state['payload'] ?? [];
            $packageId = (int) ($payload['package_id'] ?? 0);
            $typeId = (int) ($payload['type_id'] ?? 0);
            $statusToken = (string) ($payload['status_token'] ?? 'all');
            $query = trim((string) ($message['text'] ?? ''));
            if ($query === '-' || $query === '—') {
                $query = '';
            }
            $token = '';
            if ($query !== '') {
                $raw = base64_encode($query);
                $token = rtrim(strtr($raw, '+/', '-_'), '=');
            }
            $this->database->clearUserState($userId);
            $this->telegram->sendMessage(
                $chatId,
                $query === ''
                    ? "✅ جستجو پاک شد. برای مشاهده نتایج روی دکمه زیر بزنید."
                    : "✅ جستجو ثبت شد: <code>" . htmlspecialchars($query) . "</code>\nبرای مشاهده نتایج روی دکمه زیر بزنید.",
                [
                    'inline_keyboard' => [[[
                        'text' => '📚 نمایش نتایج موجودی',
                        'callback_data' => 'admin:stock:pkg:' . $packageId . ':' . $typeId . ':1:' . $statusToken . ':' . $token,
                    ]]],
                ]
            );
            return;
        }
    }
}
