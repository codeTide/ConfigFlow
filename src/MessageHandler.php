<?php

declare(strict_types=1);

namespace ConfigFlow\Bot;

final class MessageHandler
{
    private const PAY_WALLET = '💰 پرداخت با کیف پول';
    private const PAY_CARD = '🏦 کارت به کارت';
    private const PAY_CRYPTO = '💎 پرداخت کریپتو';
    private const PAY_TETRAPAY = '🏧 پرداخت TetraPay';
    private const PAY_SWAPWALLET = '💠 SwapWallet Crypto';
    private const PAY_TRONPAYS = '🧾 TronPays Rial';
    private const PAY_VERIFY = '🔄 بررسی پرداخت';
    private const ACCEPT_RULES = '✅ قوانین را می‌پذیرم';

    public function __construct(
        private Database $database,
        private TelegramClient $telegram,
        private SettingsRepository $settings,
        private MenuService $menus,
        private PaymentGatewayService $gateways,
        private CallbackHandler $callbackHandler,
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
        $messageId = (int) ($message['message_id'] ?? 0);
        $userId = (int) ($fromUser['id'] ?? 0);
        if ($chatId === 0 || $userId === 0 || $messageId === 0) {
            return;
        }

        $text = trim((string) ($message['text'] ?? ''));
        if (!str_starts_with($text, '/start') && !$this->checkChannelMembership($userId)) {
            $this->telegram->sendMessage($chatId, $this->channelLockText(), $this->channelLockKeyboard());
            return;
        }

        $state = $this->database->getUserState($userId);
        if ($state === null) {
            if ($this->handleMainReplyKeyboardInput($chatId, $messageId, $userId, $fromUser, $text)) {
                return;
            }
            return;
        }

        if ($text !== '' && str_starts_with($text, '/start')) {
            $this->database->clearUserState($userId);
            return;
        }

        if ($state['state_name'] === 'await_buy_type_selection') {
            $this->handleBuyTypeSelectionState($chatId, $userId, $text, $state);
            return;
        }

        if ($state['state_name'] === 'await_buy_package_selection') {
            $this->handleBuyPackageSelectionState($chatId, $userId, $text, $state);
            return;
        }

        if ($state['state_name'] === 'await_renew_purchase_selection') {
            $this->handleRenewPurchaseSelectionState($chatId, $userId, $text, $state);
            return;
        }

        if ($state['state_name'] === 'await_renew_package_selection') {
            $this->handleRenewPackageSelectionState($chatId, $userId, $text, $state);
            return;
        }

        if ($state['state_name'] === 'await_buy_payment_selection') {
            $this->handleBuyPaymentSelectionState($chatId, $userId, $text, $state);
            return;
        }

        if ($state['state_name'] === 'await_renew_payment_selection') {
            $this->handleRenewPaymentSelectionState($chatId, $userId, $text, $state);
            return;
        }

        if ($state['state_name'] === 'await_gateway_verify') {
            $this->handleGatewayVerifyState($chatId, $userId, $text, $state);
            return;
        }

        if ($state['state_name'] === 'await_purchase_rules_accept') {
            $this->handlePurchaseRulesAcceptState($chatId, $userId, $text, $state);
            return;
        }

        if ($state['state_name'] === 'await_wallet_amount') {
            if ($text === KeyboardBuilder::BTN_BACK_MAIN) {
                $this->database->clearUserState($userId);
                $this->telegram->sendMessage($chatId, $this->menus->mainMenuText(), $this->menus->mainMenuReplyKeyboard($userId));
                return;
            }
            if ($text === KeyboardBuilder::BTN_BACK_ACCOUNT) {
                $this->database->clearUserState($userId);
                $this->telegram->sendMessage($chatId, $this->menus->profileText($userId), $this->menus->accountMenuReplyKeyboard());
                return;
            }
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

        if ($state['state_name'] === 'await_renewal_receipt') {
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
                $this->telegram->sendMessage($chatId, '⚠️ لطفاً رسید تمدید را به‌صورت عکس/فایل یا متن ارسال کنید.');
                return;
            }

            $this->database->attachPaymentReceipt($paymentId, $fileId ?: null, $receiptText);
            $this->database->clearUserState($userId);
            $this->telegram->sendMessage(
                $chatId,
                "✅ رسید تمدید شما ثبت شد و برای بررسی ادمین ارسال گردید.\nشماره پرداخت: <code>{$paymentId}</code>"
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
                    "♻️ <b>رسید تمدید جدید</b>\n\n"
                    . "پرداخت: <code>{$paymentId}</code>\n"
                    . "کاربر: <code>{$userId}</code>\n"
                    . ($receiptText ? "توضیح: " . htmlspecialchars($receiptText) . "\n" : ''),
                    $adminKeyboard
                );
            }
            return;
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

        if ($state['state_name'] === 'await_renewal_crypto_tx') {
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
                "✅ TX Hash تمدید ثبت شد و برای بررسی ادمین ارسال گردید.\nشماره پرداخت: <code>{$paymentId}</code>"
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
                    "♻️ <b>TX Hash تمدید جدید</b>\n\n"
                    . "پرداخت: <code>{$paymentId}</code>\n"
                    . "کاربر: <code>{$userId}</code>\n"
                    . "TX: <code>" . htmlspecialchars($txHash) . "</code>\n"
                    . ($claimedAmount !== null ? "Amount: <b>{$claimedAmount}</b>\n" : ''),
                    $adminKeyboard
                );
            }
            return;
        }

        if ($state['state_name'] === 'await_free_test_note') {
            if ($this->isBotMenuButton($text)) {
                $this->telegram->sendMessage($chatId, '⚠️ لطفاً متن توضیح ارسال کنید، نه دکمه‌های منو.');
                return;
            }
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
            if ($this->isBotMenuButton($text)) {
                $this->telegram->sendMessage($chatId, '⚠️ لطفاً متن درخواست نمایندگی را تایپ کنید، نه دکمه‌های منو.');
                return;
            }
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

        if ($state['state_name'] === 'await_admin_free_test_rule') {
            if (!in_array($userId, Config::adminIds(), true)) {
                $this->database->clearUserState($userId);
                return;
            }
            $parts = array_map('trim', explode('|', $text));
            $packageId = (int) ($parts[0] ?? 0);
            $maxClaims = (int) ($parts[1] ?? 1);
            $cooldownDays = (int) ($parts[2] ?? 0);
            if ($packageId <= 0) {
                $this->telegram->sendMessage($chatId, '⚠️ فرمت نامعتبر است. نمونه: <code>12|1|0</code>');
                return;
            }
            $this->database->saveFreeTestRule($packageId, $maxClaims, $cooldownDays, true);
            $this->database->clearUserState($userId);
            $this->telegram->sendMessage($chatId, '✅ قانون تست رایگان ذخیره شد.');
            return;
        }

        if ($state['state_name'] === 'await_admin_free_test_reset_user') {
            if (!in_array($userId, Config::adminIds(), true)) {
                $this->database->clearUserState($userId);
                return;
            }
            $targetUserId = (int) preg_replace('/\D+/', '', $text);
            if ($targetUserId <= 0) {
                $this->telegram->sendMessage($chatId, '⚠️ آیدی عددی معتبر ارسال کنید.');
                return;
            }
            $this->database->resetFreeTestQuota($targetUserId);
            $this->database->clearUserState($userId);
            $this->telegram->sendMessage($chatId, "✅ سهمیه تست کاربر <code>{$targetUserId}</code> ریست شد.");
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

        if ($state['state_name'] === 'await_admin_add_admin') {
            if (!in_array($userId, Config::adminIds(), true)) {
                $this->database->clearUserState($userId);
                return;
            }
            $targetUid = (int) preg_replace('/\D+/', '', $text);
            if ($targetUid <= 0) {
                $this->telegram->sendMessage($chatId, '⚠️ آیدی عددی معتبر ارسال کنید.');
                return;
            }
            $this->database->upsertAdminUser($targetUid, $userId, [
                'types' => true,
                'stock' => true,
                'users' => true,
                'settings' => true,
                'payments' => true,
                'requests' => true,
            ]);
            $this->database->clearUserState($userId);
            $this->telegram->sendMessage($chatId, "✅ ادمین <code>{$targetUid}</code> اضافه شد.");
            return;
        }

        if ($state['state_name'] === 'await_agent_price') {
            if (!$this->database->isAdminUser($userId)) {
                $this->database->clearUserState($userId);
                return;
            }
            $payload = $state['payload'] ?? [];
            $agentId = (int) ($payload['agent_id'] ?? 0);
            $packageId = (int) ($payload['package_id'] ?? 0);
            $price = (int) preg_replace('/\D+/', '', $text);
            if ($agentId <= 0 || $packageId <= 0 || $price <= 0) {
                $this->telegram->sendMessage($chatId, '⚠️ قیمت معتبر وارد کنید.');
                return;
            }
            $this->database->setAgencyPrice($agentId, $packageId, $price);
            $this->database->clearUserState($userId);
            $this->telegram->sendMessage($chatId, "✅ قیمت اختصاصی ثبت شد.\nU:<code>{$agentId}</code> | P:<code>{$packageId}</code> | <b>{$price}</b>");
            return;
        }

        if ($state['state_name'] === 'await_panel_add') {
            if (!$this->database->isAdminUser($userId)) {
                $this->database->clearUserState($userId);
                return;
            }
            $parts = array_map('trim', explode('|', $text));
            if (count($parts) !== 6) {
                $this->telegram->sendMessage($chatId, '⚠️ فرمت باید 6 بخشی باشد: name|ip|port|patch|username|password');
                return;
            }
            [$name, $ip, $portRaw, $patch, $username, $password] = $parts;
            $port = (int) preg_replace('/\D+/', '', $portRaw);
            if ($name === '' || $ip === '' || $port <= 0 || $username === '' || $password === '') {
                $this->telegram->sendMessage($chatId, '⚠️ مقادیر معتبر نیستند.');
                return;
            }
            $panelId = $this->database->addPanel($name, $ip, $port, $patch, $username, $password);
            $this->database->clearUserState($userId);
            $this->telegram->sendMessage($chatId, "✅ پنل ثبت شد. ID: <code>{$panelId}</code>");
            return;
        }

        if ($state['state_name'] === 'await_panel_pkg_add') {
            if (!$this->database->isAdminUser($userId)) {
                $this->database->clearUserState($userId);
                return;
            }
            $panelId = (int) (($state['payload'] ?? [])['panel_id'] ?? 0);
            $parts = array_map('trim', explode('|', $text));
            if (count($parts) !== 4) {
                $this->telegram->sendMessage($chatId, '⚠️ فرمت: name|volume_gb|duration_days|inbound_id');
                return;
            }
            [$name, $volRaw, $durRaw, $inbRaw] = $parts;
            $vol = (float) str_replace(',', '.', $volRaw);
            $dur = (int) preg_replace('/\D+/', '', $durRaw);
            $inb = (int) preg_replace('/\D+/', '', $inbRaw);
            if ($panelId <= 0 || $name === '' || $vol <= 0 || $dur <= 0 || $inb <= 0) {
                $this->telegram->sendMessage($chatId, '⚠️ مقادیر معتبر نیستند.');
                return;
            }
            $id = $this->database->addPanelPackage($panelId, $name, $vol, $dur, $inb);
            $this->database->clearUserState($userId);
            $this->telegram->sendMessage($chatId, "✅ پکیج پنل ثبت شد. ID: <code>{$id}</code>");
            return;
        }

        if ($state['state_name'] === 'await_worker_api_key') {
            if (!$this->database->isAdminUser($userId)) {
                $this->database->clearUserState($userId);
                return;
            }
            $key = trim($text);
            if ($key === '' || strlen($key) < 8) {
                $this->telegram->sendMessage($chatId, '⚠️ کلید معتبر نیست.');
                return;
            }
            $this->database->clearUserState($userId);
            $this->database->pdo()->prepare('INSERT INTO settings (`key`,`value`) VALUES (\'worker_api_key\', :v) ON DUPLICATE KEY UPDATE `value`=VALUES(`value`)')->execute(['v' => $key]);
            $this->telegram->sendMessage($chatId, '✅ Worker API key ذخیره شد.');
            return;
        }

        if ($state['state_name'] === 'await_worker_api_port') {
            if (!$this->database->isAdminUser($userId)) {
                $this->database->clearUserState($userId);
                return;
            }
            $port = (int) preg_replace('/\D+/', '', $text);
            if ($port < 1 || $port > 65535) {
                $this->telegram->sendMessage($chatId, '⚠️ پورت معتبر نیست.');
                return;
            }
            $this->database->clearUserState($userId);
            $this->database->pdo()->prepare('INSERT INTO settings (`key`,`value`) VALUES (\'worker_api_port\', :v) ON DUPLICATE KEY UPDATE `value`=VALUES(`value`)')->execute(['v' => (string) $port]);
            $this->telegram->sendMessage($chatId, '✅ Worker API port ذخیره شد.');
            return;
        }

        if ($state['state_name'] === 'await_php_worker_poll_interval') {
            if (!$this->database->isAdminUser($userId)) {
                $this->database->clearUserState($userId);
                return;
            }
            $interval = (int) preg_replace('/\\D+/', '', $text);
            if ($interval < 3 || $interval > 3600) {
                $this->telegram->sendMessage($chatId, '⚠️ بازه معتبر نیست (3 تا 3600 ثانیه).');
                return;
            }
            $this->settings->set('php_worker_poll_interval', (string) $interval);
            $this->database->clearUserState($userId);
            $this->telegram->sendMessage($chatId, "✅ بازه Poll ذخیره شد: <b>{$interval}</b> ثانیه");
            return;
        }

        if ($state['state_name'] === 'await_admin_set_channel') {
            if (!$this->database->isAdminUser($userId)) {
                $this->database->clearUserState($userId);
                return;
            }
            $value = trim($text);
            if ($value === '') {
                $this->telegram->sendMessage($chatId, '⚠️ مقدار کانال نمی‌تواند خالی باشد. برای غیرفعال‌سازی «-» ارسال کنید.');
                return;
            }
            $channelId = $value === '-' ? '' : $value;
            $this->settings->set('channel_id', $channelId);
            $this->database->clearUserState($userId);
            $this->telegram->sendMessage(
                $chatId,
                $channelId === '' ? '✅ قفل کانال غیرفعال شد.' : "✅ کانال قفل ذخیره شد: <code>" . htmlspecialchars($channelId) . "</code>"
            );
            return;
        }

        if ($state['state_name'] === 'await_admin_group_id') {
            if (!$this->database->isAdminUser($userId)) {
                $this->database->clearUserState($userId);
                return;
            }
            $value = trim($text);
            if ($value === '') {
                $this->telegram->sendMessage($chatId, '⚠️ مقدار group_id نمی‌تواند خالی باشد.');
                return;
            }
            if ($value !== '-' && !preg_match('/^-?\d+$/', $value)) {
                $this->telegram->sendMessage($chatId, '⚠️ Group ID باید عددی باشد یا «-».');
                return;
            }
            $groupId = $value === '-' ? '' : $value;
            $this->settings->set('group_id', $groupId);
            $this->database->clearUserState($userId);
            $this->telegram->sendMessage(
                $chatId,
                $groupId === '' ? '✅ Group ID غیرفعال شد.' : "✅ Group ID ذخیره شد: <code>" . htmlspecialchars($groupId) . "</code>"
            );
            return;
        }

        if ($state['state_name'] === 'await_admin_restore_settings') {
            if (!$this->database->isAdminUser($userId)) {
                $this->database->clearUserState($userId);
                return;
            }
            $raw = '';
            if ($text !== '') {
                $raw = $text;
            } elseif (isset($message['document']) && is_array($message['document'])) {
                $fileId = trim((string) ($message['document']['file_id'] ?? ''));
                if ($fileId !== '') {
                    $dl = $this->telegram->downloadFileById($fileId);
                    if (is_string($dl)) {
                        $raw = $dl;
                    }
                }
            }
            if ($raw === '') {
                $this->telegram->sendMessage($chatId, '⚠️ فایل/متن JSON معتبر ارسال نشد.');
                return;
            }
            $data = json_decode($raw, true);
            $settings = is_array($data) ? ($data['settings'] ?? null) : null;
            if (!is_array($settings)) {
                $this->telegram->sendMessage($chatId, '⚠️ ساختار JSON نامعتبر است. کلید settings پیدا نشد.');
                return;
            }
            $count = 0;
            foreach ($settings as $k => $v) {
                $key = trim((string) $k);
                if ($key === '') {
                    continue;
                }
                $this->settings->set($key, (string) $v);
                $count++;
            }
            $this->database->clearUserState($userId);
            $this->telegram->sendMessage($chatId, "✅ بازیابی تنظیمات انجام شد.\nتعداد کلیدهای اعمال‌شده: <b>{$count}</b>");
            $this->sendToGroupTopic('backup', "♻️ بازیابی تنظیمات انجام شد.\nادمین: <code>{$userId}</code>\nتعداد کلید: <b>{$count}</b>");
            return;
        }



        if ($state['state_name'] === 'await_admin_pin_add') {
            if (!$this->database->isAdminUser($userId)) {
                $this->database->clearUserState($userId);
                return;
            }
            $body = trim((string) ($message['text'] ?? ''));
            if ($body === '') {
                $this->telegram->sendMessage($chatId, '⚠️ متن پیام پین نمی‌تواند خالی باشد.');
                return;
            }
            $pinId = $this->database->addPinnedMessage($body);
            $this->database->clearUserState($userId);
            $this->telegram->sendMessage($chatId, "✅ پیام پین ثبت شد.
ID: <code>{$pinId}</code>");
            return;
        }

        if ($state['state_name'] === 'await_admin_pin_edit') {
            if (!$this->database->isAdminUser($userId)) {
                $this->database->clearUserState($userId);
                return;
            }
            $pinId = (int) (($state['payload'] ?? [])['pin_id'] ?? 0);
            $body = trim((string) ($message['text'] ?? ''));
            if ($pinId <= 0 || $body === '') {
                $this->telegram->sendMessage($chatId, '⚠️ داده ویرایش پیام پین معتبر نیست.');
                return;
            }
            $this->database->updatePinnedMessage($pinId, $body);
            $this->database->clearUserState($userId);
            $this->telegram->sendMessage($chatId, "✅ پیام پین ویرایش شد.
ID: <code>{$pinId}</code>");
            return;
        }
        if ($state['state_name'] === 'await_admin_broadcast') {
            if (!$this->database->isAdminUser($userId)) {
                $this->database->clearUserState($userId);
                return;
            }
            $scope = (string) (($state['payload'] ?? [])['scope'] ?? 'all');
            $targets = $this->database->listUserIdsForBroadcast($scope);
            $sourceChatId = (int) ($message['chat']['id'] ?? 0);
            $sourceMessageId = (int) ($message['message_id'] ?? 0);
            if ($sourceChatId === 0 || $sourceMessageId === 0) {
                $this->telegram->sendMessage($chatId, '❌ پیام قابل ارسال نیست.');
                return;
            }
            $sent = 0;
            $isForwarded = isset($message['forward_date']);
            foreach ($targets as $targetId) {
                if ($targetId <= 0) {
                    continue;
                }
                try {
                    if ($isForwarded) {
                        $this->telegram->forwardMessage($targetId, $sourceChatId, $sourceMessageId);
                    } else {
                        $this->telegram->copyMessage($targetId, $sourceChatId, $sourceMessageId);
                    }
                    $sent++;
                } catch (\Throwable $e) {
                }
            }
            $this->database->clearUserState($userId);
            $this->telegram->sendMessage($chatId, "✅ ارسال همگانی انجام شد.\nتعداد موفق: <b>{$sent}</b>");
            $this->sendToGroupTopic('broadcast_report', "📣 گزارش ارسال همگانی\nادمین: <code>{$userId}</code>\nscope: <b>" . htmlspecialchars($scope) . "</b>\nموفق: <b>{$sent}</b>");
            return;
        }
    }

    private function handleMainReplyKeyboardInput(int $chatId, int $messageId, int $userId, array $fromUser, string $text): bool
    {
        if ($text === '' || str_starts_with($text, '/')) {
            return false;
        }

        if ($text === KeyboardBuilder::BTN_PROFILE) {
            $this->telegram->sendMessage($chatId, $this->menus->profileText($userId), $this->menus->accountMenuReplyKeyboard());
            return true;
        }

        if ($text === KeyboardBuilder::BTN_SUPPORT) {
            $this->telegram->sendMessage($chatId, $this->menus->supportText());
            return true;
        }

        if ($text === KeyboardBuilder::BTN_MY_CONFIGS) {
            $this->showMyConfigsWithReplyFlow($chatId, $userId);
            return true;
        }

        if ($text === KeyboardBuilder::BTN_REFERRAL) {
            if ($this->settings->get('referral_enabled', '1') !== '1') {
                return false;
            }
            $this->telegram->sendMessage(
                $chatId,
                $this->menus->referralText($userId),
                KeyboardBuilder::referral($this->menus->referralShareUrl($userId))
            );
            return true;
        }

        if ($text === KeyboardBuilder::BTN_WALLET) {
            $this->database->setUserState($userId, 'await_wallet_amount');
            $this->telegram->sendMessage(
                $chatId,
                "💵 لطفاً مبلغ موردنظر را به تومان ارسال کنید:",
                $this->replyKeyboard([[KeyboardBuilder::BTN_BACK_ACCOUNT, KeyboardBuilder::BTN_BACK_MAIN]])
            );
            return true;
        }

        if ($text === KeyboardBuilder::BTN_BUY) {
            $this->startBuyTypeReplyFlow($chatId, $userId);
            return true;
        }

        if ($text === KeyboardBuilder::BTN_FREE_TEST) {
            if ($this->settings->get('free_test_enabled', '1') !== '1') {
                return false;
            }
            $claim = $this->database->claimFreeTest($userId);
            if (($claim['ok'] ?? false) !== true) {
                $this->telegram->sendMessage(
                    $chatId,
                    "⚠️ در حال حاضر سرویس تست آماده نداریم یا سهمیه شما کامل شده است."
                );
                return true;
            }
            $serviceName = htmlspecialchars((string) ($claim['service_name'] ?? 'سرویس تست'));
            $configText = htmlspecialchars((string) ($claim['config_text'] ?? ''));
            $inquiryLink = trim((string) ($claim['inquiry_link'] ?? ''));
            $msg = "🎁 <b>تست رایگان شما آماده است</b>\n\n"
                . "📦 سرویس: <b>{$serviceName}</b>\n"
                . "🧪 نوع سفارش: <b>تست رایگان</b>\n\n"
                . "🔗 کانفیگ شما:\n<code>{$configText}</code>";
            if ($inquiryLink !== '') {
                $msg .= "\n\n🌐 لینک استعلام:\n" . htmlspecialchars($inquiryLink);
            }
            $this->telegram->sendMessage($chatId, $msg);
            return true;
        }

        if ($text === KeyboardBuilder::BTN_AGENCY) {
            if ($this->settings->get('agency_request_enabled', '1') !== '1') {
                return false;
            }
            $this->database->setUserState($userId, 'await_agency_request');
            $this->telegram->sendMessage(
                $chatId,
                "🤝 <b>درخواست نمایندگی</b>\n\n"
                . "لطفاً اطلاعات تماس و توضیح کوتاه درباره سابقه/برنامه همکاری را ارسال کنید:\n"
                . "پیام شما برای تیم ادمین ثبت می‌شود."
            );
            return true;
        }

        if ($text === KeyboardBuilder::BTN_ADMIN) {
            if (!$this->database->isAdminUser($userId)) {
                return false;
            }
            $this->telegram->sendMessage($chatId, '⚙️ <b>پنل مدیریت</b>', KeyboardBuilder::adminPanelReply());
            return true;
        }

        if ($text === '↩️ پنل مدیریت') {
            if (!$this->database->isAdminUser($userId)) {
                return false;
            }
            $this->database->clearUserState($userId);
            $this->telegram->sendMessage($chatId, '⚙️ <b>پنل مدیریت</b>', KeyboardBuilder::adminPanelReply());
            return true;
        }

        if ($text === KeyboardBuilder::BTN_BACK_MAIN) {
            $this->database->clearUserState($userId);
            $this->telegram->sendMessage($chatId, $this->menus->mainMenuText(), $this->menus->mainMenuReplyKeyboard($userId));
            return true;
        }

        if ($this->database->isAdminUser($userId)) {
            $adminRouteMap = [
                '🧩 نوع/پکیج' => 'admin:types',
                '📚 موجودی' => 'admin:stock',
                '👥 کاربران' => 'admin:users',
                '⚙️ تنظیمات' => 'admin:settings',
                '🧪 تست رایگان' => 'admin:free_test:menu',
                '👮 ادمین‌ها' => 'admin:admins',
                '📣 همگانی' => 'admin:broadcast',
                '📌 پین‌ها' => 'admin:pins',
                '🤝 نماینده‌ها' => 'admin:agents',
                '🖥 پنل‌های 3x-ui' => 'admin:panels',
                '💳 شارژها' => 'admin:payments',
                '📦 تحویل سفارش' => 'admin:deliveries',
                '🗂 درخواست‌ها' => 'admin:requests',
                '🗃 بکاپ/تاپیک' => 'admin:groupops',
                '➕ افزودن/ویرایش قانون' => 'admin:free_test:rule:add',
                '♻️ ریست سهمیه کاربر' => 'admin:free_test:quota:reset',
            ];
            $route = $adminRouteMap[$text] ?? '';
            if ($route !== '') {
                $this->dispatchReplyAsCallback($chatId, $messageId, $fromUser, $route);
                return true;
            }
        }

        return false;
    }

    private function startBuyTypeReplyFlow(int $chatId, int $userId): void
    {
        if ($this->settings->get('shop_open', '1') !== '1') {
            $this->telegram->sendMessage($chatId, 'فروشگاه در حال حاضر بسته است.');
            return;
        }
        $types = $this->database->getActiveTypes();
        if ($types === []) {
            $this->telegram->sendMessage($chatId, 'فعلاً سرویسی برای خرید فعال نیست.');
            return;
        }

        $lines = [];
        $optionMap = [];
        $buttons = [];
        foreach (array_values($types) as $idx => $type) {
            $num = (string) ($idx + 1);
            $typeId = (int) ($type['id'] ?? 0);
            if ($typeId <= 0) {
                continue;
            }
            $name = trim((string) ($type['name'] ?? '—'));
            $lines[] = "{$num}) " . htmlspecialchars($name);
            $optionMap[$num] = $typeId;
            $buttons[] = [$num . ' - ' . $name];
        }

        if ($optionMap === []) {
            $this->telegram->sendMessage($chatId, 'فعلاً سرویسی برای خرید فعال نیست.');
            return;
        }

        $buttons[] = [KeyboardBuilder::BTN_BACK_MAIN];
        $this->database->setUserState($userId, 'await_buy_type_selection', ['options' => $optionMap]);
        $this->telegram->sendMessage(
            $chatId,
            "🛒 <b>خرید کانفیگ</b>\n\nنوع سرویس موردنظر را انتخاب کنید:\n" . implode("\n", $lines),
            $this->replyKeyboard($buttons)
        );
    }

    private function handleBuyTypeSelectionState(int $chatId, int $userId, string $text, array $state): void
    {
        if ($text === KeyboardBuilder::BTN_BACK_MAIN) {
            $this->database->clearUserState($userId);
            $this->telegram->sendMessage($chatId, $this->menus->mainMenuText(), $this->menus->mainMenuReplyKeyboard($userId));
            return;
        }

        $options = is_array($state['payload']['options'] ?? null) ? $state['payload']['options'] : [];
        $selected = $this->extractOptionKey($text);
        $typeId = isset($options[$selected]) ? (int) $options[$selected] : 0;
        if ($typeId <= 0) {
            $this->telegram->sendMessage($chatId, '⚠️ گزینه نامعتبر است. لطفاً یکی از گزینه‌های لیست را انتخاب کنید.');
            return;
        }

        $stockOnly = $this->settings->get('preorder_mode', '0') === '1';
        $packages = $this->database->getActivePackagesByTypeWithStock($typeId, $stockOnly);
        if ($packages === []) {
            $this->telegram->sendMessage($chatId, 'پکیجی برای این نوع سرویس یافت نشد.');
            return;
        }

        $lines = [];
        $optionMap = [];
        $buttons = [];
        foreach (array_values($packages) as $idx => $pkg) {
            $num = (string) ($idx + 1);
            $pkgId = (int) ($pkg['id'] ?? 0);
            if ($pkgId <= 0) {
                continue;
            }
            $price = $this->database->effectivePackagePrice($userId, $pkg);
            $stockText = isset($pkg['stock']) ? (' | موجودی: ' . (int) $pkg['stock']) : '';
            $label = sprintf('%s | %sGB | %s روز | %s تومان%s', (string) $pkg['name'], (string) $pkg['volume_gb'], (string) $pkg['duration_days'], (string) $price, $stockText);
            $lines[] = "{$num}) " . htmlspecialchars($label);
            $optionMap[$num] = $pkgId;
            $buttons[] = [$num . ' - ' . (string) ($pkg['name'] ?? 'پکیج')];
        }

        $buttons[] = [KeyboardBuilder::BTN_BACK_TYPES];
        $this->database->setUserState($userId, 'await_buy_package_selection', ['options' => $optionMap, 'type_id' => $typeId]);
        $this->telegram->sendMessage(
            $chatId,
            "📦 <b>انتخاب پکیج</b>\n\nیک پکیج را انتخاب کنید:\n" . implode("\n", $lines),
            $this->replyKeyboard($buttons)
        );
    }

    private function handleBuyPackageSelectionState(int $chatId, int $userId, string $text, array $state): void
    {
        if ($text === KeyboardBuilder::BTN_BACK_TYPES) {
            $this->startBuyTypeReplyFlow($chatId, $userId);
            return;
        }
        if ($text === KeyboardBuilder::BTN_BACK_MAIN) {
            $this->database->clearUserState($userId);
            $this->telegram->sendMessage($chatId, $this->menus->mainMenuText(), $this->menus->mainMenuReplyKeyboard($userId));
            return;
        }

        $options = is_array($state['payload']['options'] ?? null) ? $state['payload']['options'] : [];
        $selected = $this->extractOptionKey($text);
        $packageId = isset($options[$selected]) ? (int) $options[$selected] : 0;
        if ($packageId <= 0) {
            $this->telegram->sendMessage($chatId, '⚠️ گزینه نامعتبر است. لطفاً یکی از گزینه‌های لیست را انتخاب کنید.');
            return;
        }

        if ($this->settings->get('purchase_rules_enabled', '0') === '1' && !$this->database->hasAcceptedPurchaseRules($userId)) {
            $rulesText = trim($this->settings->get('purchase_rules_text', ''));
            $rulesText = $rulesText !== '' ? $rulesText : 'لطفاً قوانین خرید را بپذیرید.';
            $this->database->setUserState($userId, 'await_purchase_rules_accept', ['package_id' => $packageId]);
            $this->telegram->sendMessage(
                $chatId,
                "📜 <b>قوانین خرید</b>\n\n" . $rulesText,
                $this->replyKeyboard([[self::ACCEPT_RULES], [KeyboardBuilder::BTN_BACK_TYPES], [KeyboardBuilder::BTN_BACK_MAIN]])
            );
            return;
        }

        $this->database->clearUserState($userId);
        $package = $this->database->getPackage($packageId);
        if ($package === null) {
            $this->telegram->sendMessage($chatId, 'پکیج پیدا نشد.');
            return;
        }
        $textOut = "💰 <b>پرداخت سفارش</b>\n\n"
            . "پکیج: <b>" . htmlspecialchars((string) $package['name']) . "</b>\n"
            . "قیمت: <b>" . (int) $this->database->effectivePackagePrice($userId, $package) . "</b> تومان\n\n"
            . "روش پرداخت را از دکمه‌های عادی انتخاب کنید:";
        $buttons = [[self::PAY_WALLET]];
        if ($this->settings->get('gw_card_enabled', '0') === '1') {
            $buttons[] = [self::PAY_CARD];
        }
        if ($this->settings->get('gw_crypto_enabled', '0') === '1') {
            $buttons[] = [self::PAY_CRYPTO];
        }
        if ($this->settings->get('gw_tetrapay_enabled', '0') === '1') {
            $buttons[] = [self::PAY_TETRAPAY];
        }
        if ($this->settings->get('gw_swapwallet_crypto_enabled', '0') === '1') {
            $buttons[] = [self::PAY_SWAPWALLET];
        }
        if ($this->settings->get('gw_tronpays_rial_enabled', '0') === '1') {
            $buttons[] = [self::PAY_TRONPAYS];
        }
        $buttons[] = [KeyboardBuilder::BTN_BACK_MAIN];
        $this->database->setUserState($userId, 'await_buy_payment_selection', ['package_id' => $packageId]);
        $this->telegram->sendMessage($chatId, $textOut, $this->replyKeyboard($buttons));
    }

    private function showMyConfigsWithReplyFlow(int $chatId, int $userId): void
    {
        $items = $this->database->listUserPurchasesSummary($userId, 8);
        $this->telegram->sendMessage($chatId, $this->menus->myConfigsText($userId));

        if ($this->settings->get('manual_renewal_enabled', '1') !== '1' || $items === []) {
            return;
        }

        $lines = [];
        $optionMap = [];
        $buttons = [];
        foreach (array_values($items) as $idx => $item) {
            $num = (string) ($idx + 1);
            $purchaseId = (int) ($item['id'] ?? 0);
            if ($purchaseId <= 0) {
                continue;
            }
            $service = trim((string) ($item['service_name'] ?? '-'));
            $lines[] = "{$num}) سفارش #{$purchaseId} - " . htmlspecialchars($service);
            $optionMap[$num] = $purchaseId;
            $buttons[] = ['♻️ تمدید ' . $num];
        }

        if ($optionMap === []) {
            return;
        }

        $buttons[] = [KeyboardBuilder::BTN_BACK_MAIN];
        $this->database->setUserState($userId, 'await_renew_purchase_selection', ['options' => $optionMap]);
        $this->telegram->sendMessage(
            $chatId,
            "♻️ <b>تمدید سرویس</b>\n\nبرای تمدید، یکی از سفارش‌های زیر را انتخاب کنید:\n" . implode("\n", $lines),
            $this->replyKeyboard($buttons)
        );
    }

    private function handleRenewPurchaseSelectionState(int $chatId, int $userId, string $text, array $state): void
    {
        if ($text === KeyboardBuilder::BTN_BACK_MAIN) {
            $this->database->clearUserState($userId);
            $this->telegram->sendMessage($chatId, $this->menus->mainMenuText(), $this->menus->mainMenuReplyKeyboard($userId));
            return;
        }

        $options = is_array($state['payload']['options'] ?? null) ? $state['payload']['options'] : [];
        $selected = $this->extractOptionKey($text);
        $purchaseId = isset($options[$selected]) ? (int) $options[$selected] : 0;
        if ($purchaseId <= 0) {
            $this->telegram->sendMessage($chatId, '⚠️ گزینه نامعتبر است. لطفاً یکی از گزینه‌های لیست را انتخاب کنید.');
            return;
        }

        $purchase = $this->database->getUserPurchaseForRenewal($userId, $purchaseId);
        if (!is_array($purchase)) {
            $this->telegram->sendMessage($chatId, 'سفارش پیدا نشد.');
            return;
        }
        if ((int) ($purchase['is_test'] ?? 0) === 1) {
            $this->telegram->sendMessage($chatId, 'تمدید برای سرویس تست ممکن نیست.');
            return;
        }

        $typeId = (int) ($purchase['type_id'] ?? 0);
        $packages = $this->database->getActivePackagesByType($typeId);
        if ($packages === []) {
            $this->telegram->sendMessage($chatId, 'پکیج تمدید یافت نشد.');
            return;
        }

        $lines = [];
        $optionMap = [];
        $buttons = [];
        foreach (array_values($packages) as $idx => $pkg) {
            $num = (string) ($idx + 1);
            $pkgId = (int) ($pkg['id'] ?? 0);
            if ($pkgId <= 0) {
                continue;
            }
            $label = sprintf('%s | %sGB | %s روز | %s تومان', (string) $pkg['name'], (string) $pkg['volume_gb'], (string) $pkg['duration_days'], (string) $pkg['price']);
            $lines[] = "{$num}) " . htmlspecialchars($label);
            $optionMap[$num] = $pkgId;
            $buttons[] = ['📦 پکیج ' . $num];
        }

        $buttons[] = [KeyboardBuilder::BTN_BACK_PURCHASES];
        $this->database->setUserState($userId, 'await_renew_package_selection', ['options' => $optionMap, 'purchase_id' => $purchaseId]);
        $this->telegram->sendMessage(
            $chatId,
            "♻️ <b>انتخاب پکیج تمدید</b>\n\n"
            . "سفارش: <code>#{$purchaseId}</code>\n"
            . "سرویس فعلی: <b>" . htmlspecialchars((string) ($purchase['service_name'] ?? '-')) . "</b>\n"
            . "پکیج فعلی: <b>" . htmlspecialchars((string) ($purchase['package_name'] ?? '-')) . "</b>\n\n"
            . "پکیج تمدید را انتخاب کنید:\n" . implode("\n", $lines),
            $this->replyKeyboard($buttons)
        );
    }

    private function handleRenewPackageSelectionState(int $chatId, int $userId, string $text, array $state): void
    {
        if ($text === KeyboardBuilder::BTN_BACK_PURCHASES) {
            $this->showMyConfigsWithReplyFlow($chatId, $userId);
            return;
        }
        if ($text === KeyboardBuilder::BTN_BACK_MAIN) {
            $this->database->clearUserState($userId);
            $this->telegram->sendMessage($chatId, $this->menus->mainMenuText(), $this->menus->mainMenuReplyKeyboard($userId));
            return;
        }

        $options = is_array($state['payload']['options'] ?? null) ? $state['payload']['options'] : [];
        $selected = $this->extractOptionKey($text);
        $packageId = isset($options[$selected]) ? (int) $options[$selected] : 0;
        $purchaseId = (int) ($state['payload']['purchase_id'] ?? 0);
        if ($packageId <= 0 || $purchaseId <= 0) {
            $this->telegram->sendMessage($chatId, '⚠️ گزینه نامعتبر است. لطفاً یکی از گزینه‌های لیست را انتخاب کنید.');
            return;
        }

        $this->database->clearUserState($userId);
        $package = $this->database->getPackage($packageId);
        $purchase = $this->database->getUserPurchaseForRenewal($userId, $purchaseId);
        if ($package === null || !is_array($purchase)) {
            $this->telegram->sendMessage($chatId, 'داده تمدید معتبر نیست.');
            return;
        }

        $textOut = "💳 <b>پرداخت تمدید</b>\n\n"
            . "سفارش: <code>#{$purchaseId}</code>\n"
            . "پکیج تمدید: <b>" . htmlspecialchars((string) $package['name']) . "</b>\n"
            . "مبلغ: <b>" . (int) $package['price'] . "</b> تومان\n\n"
            . "روش پرداخت را از دکمه‌های عادی انتخاب کنید:";
        $buttons = [[self::PAY_WALLET]];
        if ($this->settings->get('gw_card_enabled', '0') === '1') {
            $buttons[] = [self::PAY_CARD];
        }
        if ($this->settings->get('gw_crypto_enabled', '0') === '1') {
            $buttons[] = [self::PAY_CRYPTO];
        }
        if ($this->settings->get('gw_tetrapay_enabled', '0') === '1') {
            $buttons[] = [self::PAY_TETRAPAY];
        }
        if ($this->settings->get('gw_swapwallet_crypto_enabled', '0') === '1') {
            $buttons[] = [self::PAY_SWAPWALLET];
        }
        if ($this->settings->get('gw_tronpays_rial_enabled', '0') === '1') {
            $buttons[] = [self::PAY_TRONPAYS];
        }
        $buttons[] = [KeyboardBuilder::BTN_BACK_MAIN];
        $this->database->setUserState($userId, 'await_renew_payment_selection', ['purchase_id' => $purchaseId, 'package_id' => $packageId]);
        $this->telegram->sendMessage($chatId, $textOut, $this->replyKeyboard($buttons));
    }

    private function handleBuyPaymentSelectionState(int $chatId, int $userId, string $text, array $state): void
    {
        if ($text === KeyboardBuilder::BTN_BACK_MAIN) {
            $this->database->clearUserState($userId);
            $this->telegram->sendMessage($chatId, $this->menus->mainMenuText(), $this->menus->mainMenuReplyKeyboard($userId));
            return;
        }
        $packageId = (int) ($state['payload']['package_id'] ?? 0);
        if ($packageId <= 0) {
            $this->database->clearUserState($userId);
            return;
        }
        if (!$this->ensurePurchaseAllowedForPackageMessage($chatId, $userId, $packageId)) {
            return;
        }

        if ($text === self::PAY_WALLET) {
            $this->database->clearUserState($userId);
            $result = $this->database->walletPayPackage($userId, $packageId);
            if (!($result['ok'] ?? false)) {
                $msg = match ($result['error'] ?? '') {
                    'insufficient_balance' => 'موجودی کیف پول کافی نیست.',
                    'no_stock' => 'برای این پکیج موجودی ثبت‌شده وجود ندارد.',
                    default => 'خطا در ثبت سفارش. دوباره تلاش کنید.',
                };
                $this->telegram->sendMessage($chatId, '❌ ' . $msg);
                return;
            }
            $this->telegram->sendMessage(
                $chatId,
                "✅ خرید با کیف پول ثبت شد.\n\n"
                . "شناسه پرداخت: <code>" . (int) $result['payment_id'] . "</code>\n"
                . "مبلغ: <b>" . (int) $result['price'] . "</b> تومان\n"
                . "موجودی جدید: <b>" . (int) $result['new_balance'] . "</b> تومان\n\n"
                . "سفارش شما در صف تحویل قرار گرفت."
            );
            return;
        }

        if ($text === self::PAY_CARD || $text === self::PAY_CRYPTO || $text === self::PAY_TETRAPAY) {
            $this->database->clearUserState($userId);
            $this->createPurchasePaymentByMethod($chatId, $userId, $packageId, $text);
            return;
        }

        if ($text === self::PAY_SWAPWALLET || $text === self::PAY_TRONPAYS) {
            $this->database->clearUserState($userId);
            $this->createPurchaseGatewayInvoice($chatId, $userId, $packageId, $text);
            return;
        }

        if ($text !== self::PAY_WALLET && $text !== self::PAY_CARD && $text !== self::PAY_CRYPTO && $text !== self::PAY_TETRAPAY && $text !== self::PAY_SWAPWALLET && $text !== self::PAY_TRONPAYS) {
            $this->telegram->sendMessage($chatId, '⚠️ لطفاً یکی از روش‌های پرداخت را انتخاب کنید.');
            return;
        }
    }

    private function handleRenewPaymentSelectionState(int $chatId, int $userId, string $text, array $state): void
    {
        if ($text === KeyboardBuilder::BTN_BACK_MAIN) {
            $this->database->clearUserState($userId);
            $this->telegram->sendMessage($chatId, $this->menus->mainMenuText(), $this->menus->mainMenuReplyKeyboard($userId));
            return;
        }
        $purchaseId = (int) ($state['payload']['purchase_id'] ?? 0);
        $packageId = (int) ($state['payload']['package_id'] ?? 0);
        if ($purchaseId <= 0 || $packageId <= 0) {
            $this->database->clearUserState($userId);
            return;
        }
        if ($text === self::PAY_WALLET) {
            $this->database->clearUserState($userId);
            $result = $this->database->walletPayRenewal($userId, $purchaseId, $packageId);
            if (!($result['ok'] ?? false)) {
                $msg = match ($result['error'] ?? '') {
                    'insufficient_balance' => '❌ موجودی کیف پول کافی نیست.',
                    'purchase_not_found' => '❌ سرویس قابل تمدید پیدا نشد.',
                    'test_not_renewable' => '❌ تمدید برای سرویس تست مجاز نیست.',
                    'type_mismatch' => '❌ پکیج انتخابی برای این سرویس معتبر نیست.',
                    default => '❌ پرداخت تمدید انجام نشد.',
                };
                $this->telegram->sendMessage($chatId, $msg);
                return;
            }
            $this->telegram->sendMessage(
                $chatId,
                "✅ <b>پرداخت تمدید با کیف پول انجام شد.</b>\n\n"
                . "سفارش شما در صف تحویل قرار گرفت.\n"
                . "شماره سفارش: <code>" . (int) ($result['pending_order_id'] ?? 0) . "</code>"
            );
            return;
        }

        if ($text === self::PAY_CARD || $text === self::PAY_CRYPTO || $text === self::PAY_TETRAPAY) {
            $this->database->clearUserState($userId);
            $this->createRenewalPaymentByMethod($chatId, $userId, $purchaseId, $packageId, $text);
            return;
        }

        if ($text === self::PAY_SWAPWALLET || $text === self::PAY_TRONPAYS) {
            $this->database->clearUserState($userId);
            $this->createRenewalGatewayInvoice($chatId, $userId, $purchaseId, $packageId, $text);
            return;
        }

        if ($text !== self::PAY_WALLET && $text !== self::PAY_CARD && $text !== self::PAY_CRYPTO && $text !== self::PAY_TETRAPAY && $text !== self::PAY_SWAPWALLET && $text !== self::PAY_TRONPAYS) {
            $this->telegram->sendMessage($chatId, '⚠️ لطفاً یکی از روش‌های پرداخت را انتخاب کنید.');
            return;
        }
    }

    private function createPurchasePaymentByMethod(int $chatId, int $userId, int $packageId, string $methodLabel): void
    {
        $method = $methodLabel === self::PAY_CARD ? 'card' : ($methodLabel === self::PAY_CRYPTO ? 'crypto' : 'tetrapay');
        $package = $this->database->getPackage($packageId);
        if ($package === null) {
            $this->telegram->sendMessage($chatId, 'پکیج پیدا نشد.');
            return;
        }
        $amount = (int) $this->database->effectivePackagePrice($userId, $package);
        $paymentMethod = $method === 'crypto' ? 'crypto:tron' : $method;
        $paymentId = $this->database->createPayment([
            'kind' => 'purchase',
            'user_id' => $userId,
            'package_id' => $packageId,
            'amount' => $amount,
            'payment_method' => $paymentMethod,
            'status' => $method === 'tetrapay' ? 'waiting_gateway' : 'waiting_admin',
            'gateway_ref' => null,
            'created_at' => gmdate('Y-m-d H:i:s'),
        ]);
        $pendingId = $this->database->createPendingOrder([
            'user_id' => $userId,
            'package_id' => $packageId,
            'payment_id' => $paymentId,
            'amount' => $amount,
            'payment_method' => $paymentMethod,
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
                . "پس از واریز، رسید را همینجا (عکس/فایل/متن) ارسال کنید.";
            $this->database->setUserState($userId, 'await_card_receipt', ['payment_id' => $paymentId]);
            $this->telegram->sendMessage($chatId, $text);
            return;
        }

        if ($method === 'crypto') {
            $address = htmlspecialchars($this->gateways->cryptoAddress('tron'));
            $text = "💎 <b>پرداخت کریپتو</b>\n\n"
                . "شناسه سفارش: <code>{$pendingId}</code>\n"
                . "ارز: <b>TRON</b>\n"
                . "مبلغ معادل ریالی: <b>{$amount}</b> تومان\n\n"
                . ($address !== '' ? "آدرس کیف پول:\n<code>{$address}</code>\n\n" : '')
                . "پس از پرداخت، TX Hash و مقدار کوین پرداختی را بفرستید.\n"
                . "مثال: <code>TX_HASH 12.345</code>";
            $this->database->setUserState($userId, 'await_crypto_tx', ['payment_id' => $paymentId]);
            $this->telegram->sendMessage($chatId, $text);
            return;
        }

        $tp = $this->gateways->createTetrapayOrder($amount, (string) $pendingId);
        if (!($tp['ok'] ?? false)) {
            $this->telegram->sendMessage($chatId, "🏧 <b>پرداخت TetraPay</b>\n\nشناسه سفارش: <code>{$pendingId}</code>\nمبلغ: <b>{$amount}</b> تومان\n\nارتباط با درگاه برقرار نشد.");
            return;
        }
        $authority = (string) ($tp['authority'] ?? '');
        if ($authority !== '') {
            $this->database->setPaymentGatewayRef($paymentId, $authority);
        }
        $payUrl = (string) ($tp['pay_url'] ?? '');
        $this->database->setUserState($userId, 'await_gateway_verify', ['payment_id' => $paymentId, 'gateway' => 'tetrapay', 'ok_text' => '✅ پرداخت تتراپی تایید شد. سفارش شما در صف تحویل قرار گرفت.']);
        $this->sendGatewayPaymentIntro($chatId, "🏧 <b>پرداخت TetraPay</b>", $pendingId, $amount, $payUrl);
    }

    private function createRenewalPaymentByMethod(int $chatId, int $userId, int $purchaseId, int $packageId, string $methodLabel): void
    {
        $method = $methodLabel === self::PAY_CARD ? 'card' : ($methodLabel === self::PAY_CRYPTO ? 'crypto' : 'tetrapay');
        $purchase = $this->database->getUserPurchaseForRenewal($userId, $purchaseId);
        $package = $this->database->getPackage($packageId);
        if (!is_array($purchase) || $package === null) {
            $this->telegram->sendMessage($chatId, 'داده تمدید معتبر نیست.');
            return;
        }
        $amount = (int) $package['price'];
        $paymentMethod = $method === 'crypto' ? 'crypto:tron' : $method;
        $paymentId = $this->database->createPayment([
            'kind' => 'renewal',
            'user_id' => $userId,
            'package_id' => $packageId,
            'amount' => $amount,
            'payment_method' => $paymentMethod,
            'status' => $method === 'tetrapay' ? 'waiting_gateway' : 'waiting_admin',
            'gateway_ref' => null,
            'created_at' => gmdate('Y-m-d H:i:s'),
        ]);
        $pendingId = $this->database->createPendingOrder([
            'user_id' => $userId,
            'package_id' => $packageId,
            'payment_id' => $paymentId,
            'amount' => $amount,
            'payment_method' => $paymentMethod,
            'created_at' => gmdate('Y-m-d H:i:s'),
            'status' => 'waiting_payment',
        ]);

        if ($method === 'card') {
            $card = htmlspecialchars($this->settings->get('payment_card', '---'));
            $bank = htmlspecialchars($this->settings->get('payment_bank', ''));
            $owner = htmlspecialchars($this->settings->get('payment_owner', ''));
            $text = "🏦 <b>کارت به کارت (تمدید)</b>\n\n"
                . "شماره کارت: <code>{$card}</code>\n"
                . ($bank !== '' ? "بانک: {$bank}\n" : '')
                . ($owner !== '' ? "به نام: {$owner}\n" : '')
                . "\nشناسه سفارش: <code>{$pendingId}</code>\n"
                . "مبلغ: <b>{$amount}</b> تومان\n\n"
                . "پس از واریز، رسید را همینجا ارسال کنید.";
            $this->database->setUserState($userId, 'await_renewal_receipt', ['payment_id' => $paymentId]);
            $this->telegram->sendMessage($chatId, $text);
            return;
        }

        if ($method === 'crypto') {
            $address = htmlspecialchars($this->gateways->cryptoAddress('tron'));
            $text = "💎 <b>پرداخت کریپتو (تمدید)</b>\n\n"
                . "شناسه سفارش: <code>{$pendingId}</code>\n"
                . "ارز: <b>TRON</b>\n"
                . "مبلغ معادل ریالی: <b>{$amount}</b> تومان\n\n"
                . ($address !== '' ? "آدرس کیف پول:\n<code>{$address}</code>\n\n" : '')
                . "پس از پرداخت، TX Hash و مقدار کوین پرداختی را بفرستید.\n"
                . "مثال: <code>TX_HASH 12.345</code>";
            $this->database->setUserState($userId, 'await_renewal_crypto_tx', ['payment_id' => $paymentId]);
            $this->telegram->sendMessage($chatId, $text);
            return;
        }

        $tp = $this->gateways->createTetrapayOrder($amount, (string) $pendingId);
        if (!($tp['ok'] ?? false)) {
            $this->telegram->sendMessage($chatId, "🏧 <b>پرداخت TetraPay (تمدید)</b>\n\nشناسه سفارش: <code>{$pendingId}</code>\nمبلغ: <b>{$amount}</b> تومان\n\nارتباط با درگاه برقرار نشد.");
            return;
        }
        $authority = (string) ($tp['authority'] ?? '');
        if ($authority !== '') {
            $this->database->setPaymentGatewayRef($paymentId, $authority);
        }
        $payUrl = (string) ($tp['pay_url'] ?? '');
        $this->database->setUserState($userId, 'await_gateway_verify', ['payment_id' => $paymentId, 'gateway' => 'tetrapay', 'ok_text' => '✅ پرداخت تمدید تایید شد. درخواست شما در صف تحویل قرار گرفت.']);
        $this->sendGatewayPaymentIntro($chatId, "🏧 <b>پرداخت TetraPay (تمدید)</b>", $pendingId, $amount, $payUrl);
    }

    private function createPurchaseGatewayInvoice(int $chatId, int $userId, int $packageId, string $methodLabel): void
    {
        $gateway = $methodLabel === self::PAY_SWAPWALLET ? 'swapwallet_crypto' : 'tronpays_rial';
        $package = $this->database->getPackage($packageId);
        if ($package === null) {
            $this->telegram->sendMessage($chatId, 'پکیج پیدا نشد.');
            return;
        }
        $amount = (int) $this->database->effectivePackagePrice($userId, $package);
        $paymentId = $this->database->createPayment([
            'kind' => 'purchase',
            'user_id' => $userId,
            'package_id' => $packageId,
            'amount' => $amount,
            'payment_method' => $gateway,
            'status' => 'waiting_gateway',
            'created_at' => gmdate('Y-m-d H:i:s'),
        ]);
        $pendingId = $this->database->createPendingOrder([
            'user_id' => $userId,
            'package_id' => $packageId,
            'payment_id' => $paymentId,
            'amount' => $amount,
            'payment_method' => $gateway,
            'created_at' => gmdate('Y-m-d H:i:s'),
            'status' => 'waiting_payment',
        ]);
        if ($gateway === 'swapwallet_crypto') {
            $invoice = $this->gateways->createSwapwalletCryptoInvoice($amount, (string) $pendingId, 'TRON', 'Purchase');
            if (!($invoice['ok'] ?? false)) {
                $this->telegram->sendMessage($chatId, 'خطا در ایجاد فاکتور SwapWallet.');
                return;
            }
            $invoiceId = (string) ($invoice['invoice_id'] ?? '');
            if ($invoiceId !== '') {
                $this->database->setPaymentGatewayRef($paymentId, $invoiceId);
            }
            $payUrl = (string) ($invoice['pay_url'] ?? '');
            $this->database->setUserState($userId, 'await_gateway_verify', ['payment_id' => $paymentId, 'gateway' => $gateway, 'ok_text' => '✅ پرداخت SwapWallet تایید شد و سفارش در صف تحویل قرار گرفت.']);
            $this->sendGatewayPaymentIntro($chatId, "💠 <b>پرداخت با SwapWallet</b>", $pendingId, $amount, $payUrl);
            return;
        }
        $invoice = $this->gateways->createTronpaysRialInvoice($amount, 'buy-' . $userId . '-' . $packageId . '-' . time());
        if (!($invoice['ok'] ?? false)) {
            $this->telegram->sendMessage($chatId, 'خطا در ایجاد فاکتور TronPays.');
            return;
        }
        $invoiceId = (string) ($invoice['invoice_id'] ?? '');
        if ($invoiceId !== '') {
            $this->database->setPaymentGatewayRef($paymentId, $invoiceId);
        }
        $payUrl = (string) ($invoice['pay_url'] ?? '');
        $this->database->setUserState($userId, 'await_gateway_verify', ['payment_id' => $paymentId, 'gateway' => $gateway, 'ok_text' => '✅ پرداخت TronPays تایید شد و سفارش در صف تحویل قرار گرفت.']);
        $this->sendGatewayPaymentIntro($chatId, "🧾 <b>پرداخت با TronPays</b>", $pendingId, $amount, $payUrl);
    }

    private function createRenewalGatewayInvoice(int $chatId, int $userId, int $purchaseId, int $packageId, string $methodLabel): void
    {
        $gateway = $methodLabel === self::PAY_SWAPWALLET ? 'swapwallet_crypto' : 'tronpays_rial';
        $package = $this->database->getPackage($packageId);
        $purchase = $this->database->getUserPurchaseForRenewal($userId, $purchaseId);
        if ($package === null || !is_array($purchase)) {
            $this->telegram->sendMessage($chatId, 'داده تمدید معتبر نیست.');
            return;
        }
        $amount = (int) $package['price'];
        $paymentId = $this->database->createPayment([
            'kind' => 'renewal',
            'user_id' => $userId,
            'package_id' => $packageId,
            'amount' => $amount,
            'payment_method' => $gateway,
            'status' => 'waiting_gateway',
            'created_at' => gmdate('Y-m-d H:i:s'),
        ]);
        $pendingId = $this->database->createPendingOrder([
            'user_id' => $userId,
            'package_id' => $packageId,
            'payment_id' => $paymentId,
            'amount' => $amount,
            'payment_method' => $gateway,
            'created_at' => gmdate('Y-m-d H:i:s'),
            'status' => 'waiting_payment',
        ]);
        if ($gateway === 'swapwallet_crypto') {
            $invoice = $this->gateways->createSwapwalletCryptoInvoice($amount, (string) $pendingId, 'TRON', 'Renewal');
            if (!($invoice['ok'] ?? false)) {
                $this->telegram->sendMessage($chatId, 'خطا در ایجاد فاکتور SwapWallet.');
                return;
            }
            $invoiceId = (string) ($invoice['invoice_id'] ?? '');
            if ($invoiceId !== '') {
                $this->database->setPaymentGatewayRef($paymentId, $invoiceId);
            }
            $payUrl = (string) ($invoice['pay_url'] ?? '');
            $this->database->setUserState($userId, 'await_gateway_verify', ['payment_id' => $paymentId, 'gateway' => $gateway, 'ok_text' => '✅ پرداخت SwapWallet تایید شد و درخواست تمدید در صف تحویل قرار گرفت.']);
            $this->sendGatewayPaymentIntro($chatId, "💠 <b>پرداخت تمدید با SwapWallet</b>", $pendingId, $amount, $payUrl);
            return;
        }
        $invoice = $this->gateways->createTronpaysRialInvoice($amount, 'rnw-' . $userId . '-' . $packageId . '-' . time());
        if (!($invoice['ok'] ?? false)) {
            $this->telegram->sendMessage($chatId, 'خطا در ایجاد فاکتور TronPays.');
            return;
        }
        $invoiceId = (string) ($invoice['invoice_id'] ?? '');
        if ($invoiceId !== '') {
            $this->database->setPaymentGatewayRef($paymentId, $invoiceId);
        }
        $payUrl = (string) ($invoice['pay_url'] ?? '');
        $this->database->setUserState($userId, 'await_gateway_verify', ['payment_id' => $paymentId, 'gateway' => $gateway, 'ok_text' => '✅ پرداخت TronPays تایید شد و درخواست تمدید در صف تحویل قرار گرفت.']);
        $this->sendGatewayPaymentIntro($chatId, "🧾 <b>پرداخت تمدید با TronPays</b>", $pendingId, $amount, $payUrl);
    }

    private function handleGatewayVerifyState(int $chatId, int $userId, string $text, array $state): void
    {
        if ($text === KeyboardBuilder::BTN_BACK_MAIN) {
            $this->database->clearUserState($userId);
            $this->telegram->sendMessage($chatId, $this->menus->mainMenuText(), $this->menus->mainMenuReplyKeyboard($userId));
            return;
        }
        if ($text !== self::PAY_VERIFY) {
            $this->telegram->sendMessage($chatId, 'برای بررسی وضعیت، دکمه «🔄 بررسی پرداخت» را بزنید.');
            return;
        }
        $paymentId = (int) ($state['payload']['payment_id'] ?? 0);
        $gateway = (string) ($state['payload']['gateway'] ?? '');
        $okText = (string) ($state['payload']['ok_text'] ?? '✅ پرداخت تایید شد.');
        if ($paymentId <= 0 || $gateway === '') {
            $this->database->clearUserState($userId);
            return;
        }
        $payment = $this->database->getPaymentById($paymentId);
        if ($payment === null) {
            $this->telegram->sendMessage($chatId, 'پرداخت پیدا نشد.');
            return;
        }
        $gatewayRef = (string) ($payment['gateway_ref'] ?? '');
        $verify = match ($gateway) {
            'tetrapay' => $this->gateways->verifyTetrapay($gatewayRef),
            'swapwallet_crypto' => $this->gateways->checkSwapwalletCryptoInvoice($gatewayRef),
            'tronpays_rial' => $this->gateways->checkTronpaysRialInvoice($gatewayRef),
            default => ['ok' => false, 'paid' => false],
        };
        if (($verify['ok'] ?? false) && ($verify['paid'] ?? false)) {
            $changed = $this->database->markPaymentAndPendingPaidIfWaitingGateway($paymentId);
            if ($changed) {
                $this->database->clearUserState($userId);
                $this->telegram->sendMessage($chatId, $okText);
                return;
            }
        }
        $this->telegram->sendMessage($chatId, 'پرداخت هنوز تایید نشده است.');
    }

    private function handlePurchaseRulesAcceptState(int $chatId, int $userId, string $text, array $state): void
    {
        if ($text === KeyboardBuilder::BTN_BACK_MAIN) {
            $this->database->clearUserState($userId);
            $this->telegram->sendMessage($chatId, $this->menus->mainMenuText(), $this->menus->mainMenuReplyKeyboard($userId));
            return;
        }
        if ($text === KeyboardBuilder::BTN_BACK_TYPES) {
            $this->startBuyTypeReplyFlow($chatId, $userId);
            return;
        }
        if ($text !== self::ACCEPT_RULES) {
            $this->telegram->sendMessage($chatId, 'لطفاً گزینه تایید قوانین را انتخاب کنید.');
            return;
        }
        $packageId = (int) ($state['payload']['package_id'] ?? 0);
        if ($packageId <= 0) {
            $this->database->clearUserState($userId);
            return;
        }
        $this->database->acceptPurchaseRules($userId);
        $this->database->clearUserState($userId);
        $package = $this->database->getPackage($packageId);
        if ($package === null) {
            $this->telegram->sendMessage($chatId, 'پکیج پیدا نشد.');
            return;
        }
        $textOut = "💰 <b>پرداخت سفارش</b>\n\n"
            . "پکیج: <b>" . htmlspecialchars((string) $package['name']) . "</b>\n"
            . "قیمت: <b>" . (int) $this->database->effectivePackagePrice($userId, $package) . "</b> تومان\n\n"
            . "روش پرداخت را از دکمه‌های عادی انتخاب کنید:";
        $buttons = [[self::PAY_WALLET]];
        if ($this->settings->get('gw_card_enabled', '0') === '1') {
            $buttons[] = [self::PAY_CARD];
        }
        if ($this->settings->get('gw_crypto_enabled', '0') === '1') {
            $buttons[] = [self::PAY_CRYPTO];
        }
        if ($this->settings->get('gw_tetrapay_enabled', '0') === '1') {
            $buttons[] = [self::PAY_TETRAPAY];
        }
        if ($this->settings->get('gw_swapwallet_crypto_enabled', '0') === '1') {
            $buttons[] = [self::PAY_SWAPWALLET];
        }
        if ($this->settings->get('gw_tronpays_rial_enabled', '0') === '1') {
            $buttons[] = [self::PAY_TRONPAYS];
        }
        $buttons[] = [KeyboardBuilder::BTN_BACK_MAIN];
        $this->database->setUserState($userId, 'await_buy_payment_selection', ['package_id' => $packageId]);
        $this->telegram->sendMessage($chatId, $textOut, $this->replyKeyboard($buttons));
    }

    private function sendGatewayPaymentIntro(int $chatId, string $title, int $pendingId, int $amount, string $payUrl): void
    {
        $text = $title . "\n\n"
            . "سفارش: <code>{$pendingId}</code>\n"
            . "مبلغ: <b>{$amount}</b> تومان\n\n"
            . "بعد از پرداخت دکمه «" . self::PAY_VERIFY . "» را بزنید.";
        if ($payUrl !== '') {
            $this->telegram->sendMessage($chatId, $text, ['inline_keyboard' => [[['text' => '💳 پرداخت', 'url' => $payUrl]]]]);
        } else {
            $this->telegram->sendMessage($chatId, $text);
        }
        $this->telegram->sendMessage($chatId, 'برای بررسی پرداخت از دکمه زیر استفاده کن:', $this->replyKeyboard([[self::PAY_VERIFY], [KeyboardBuilder::BTN_BACK_MAIN]]));
    }

    private function ensurePurchaseAllowedForPackageMessage(int $chatId, int $userId, int $packageId): bool
    {
        if ($this->settings->get('shop_open', '1') !== '1') {
            $this->telegram->sendMessage($chatId, 'فروشگاه در حال حاضر بسته است.');
            return false;
        }
        if ($this->settings->get('purchase_rules_enabled', '0') === '1' && !$this->database->hasAcceptedPurchaseRules($userId)) {
            $this->telegram->sendMessage($chatId, 'ابتدا قوانین خرید را بپذیرید.');
            return false;
        }
        if ($this->settings->get('preorder_mode', '0') === '1' && !$this->database->packageHasAvailableStock($packageId)) {
            $this->telegram->sendMessage($chatId, 'این پکیج در حال حاضر موجودی ندارد.');
            return false;
        }
        return true;
    }

    private function extractOptionKey(string $text): string
    {
        if (preg_match('/^\D*(\d+)/u', trim($text), $m) === 1) {
            return (string) $m[1];
        }
        return trim($text);
    }

    private function replyKeyboard(array $rows): array
    {
        return [
            'keyboard' => $rows,
            'resize_keyboard' => true,
            'one_time_keyboard' => false,
        ];
    }

    private function dispatchReplyAsCallback(int $chatId, int $messageId, array $fromUser, string $data): void
    {
        $fakeId = 'msg-' . substr(md5($chatId . '-' . $messageId . '-' . $data . '-' . microtime(true)), 0, 24);
        $this->callbackHandler->handle([
            'callback_query' => [
                'id' => $fakeId,
                'from' => $fromUser,
                'message' => [
                    'chat' => ['id' => $chatId],
                    'message_id' => $messageId,
                ],
                'data' => $data,
            ],
        ]);
    }

    private function checkChannelMembership(int $userId): bool
    {
        $channelId = trim($this->settings->get('channel_id', ''));
        if ($channelId === '') {
            return true;
        }

        $member = $this->telegram->getChatMember($channelId, $userId);
        if (!is_array($member)) {
            return true;
        }

        $status = (string) ($member['status'] ?? '');
        return in_array($status, ['member', 'administrator', 'creator'], true);
    }

    private function channelLockText(): string
    {
        return "🔒 برای استفاده از ربات، ابتدا باید در کانال ما عضو شوید.\n\nپس از عضویت، روی «عضو شدم» بزنید.";
    }

    private function channelLockKeyboard(): array
    {
        $channelId = trim($this->settings->get('channel_id', ''));
        $channelUrl = $this->channelJoinUrl($channelId);
        return ['inline_keyboard' => [
            [['text' => '📢 عضویت در کانال', 'url' => $channelUrl]],
            [['text' => '✅ عضو شدم', 'callback_data' => 'check_channel']],
        ]];
    }

    private function channelJoinUrl(string $channelId): string
    {
        if (str_starts_with($channelId, '@')) {
            return 'https://t.me/' . ltrim($channelId, '@');
        }
        if (str_starts_with($channelId, '-100')) {
            return 'https://t.me/c/' . substr($channelId, 4);
        }
        return 'https://t.me/' . ltrim($channelId, '@');
    }

    private function sendToGroupTopic(string $topicKey, string $text): void
    {
        $groupIdRaw = trim($this->settings->get('group_id', ''));
        $topicIdRaw = trim($this->settings->get('group_topic_' . $topicKey, ''));
        if (!preg_match('/^-?\d+$/', $groupIdRaw) || !preg_match('/^\d+$/', $topicIdRaw)) {
            return;
        }
        $this->telegram->sendTopicMessage((int) $groupIdRaw, (int) $topicIdRaw, $text);
    }

    private function isBotMenuButton(string $text): bool
    {
        if ($text === '') {
            return false;
        }
        return in_array($text, [
            KeyboardBuilder::BTN_BUY,
            KeyboardBuilder::BTN_MY_CONFIGS,
            KeyboardBuilder::BTN_FREE_TEST,
            KeyboardBuilder::BTN_PROFILE,
            KeyboardBuilder::BTN_WALLET,
            KeyboardBuilder::BTN_SUPPORT,
            KeyboardBuilder::BTN_REFERRAL,
            KeyboardBuilder::BTN_AGENCY,
            KeyboardBuilder::BTN_ADMIN,
            KeyboardBuilder::BTN_BACK_MAIN,
            KeyboardBuilder::BTN_BACK_ACCOUNT,
            KeyboardBuilder::BTN_BACK_TYPES,
            KeyboardBuilder::BTN_BACK_PURCHASES,
        ], true);
    }
}
