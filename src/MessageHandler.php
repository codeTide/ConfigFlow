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
    private const ADMIN_TYPES_ADD = '➕ افزودن نوع سرویس';
    private const ADMIN_TYPE_ADD_PACKAGE = '➕ افزودن پکیج';
    private const ADMIN_TYPE_TOGGLE = '🔁 تغییر وضعیت نوع';
    private const ADMIN_TYPE_DELETE = '🗑 حذف نوع سرویس';
    private const ADMIN_PACKAGE_TOGGLE = '🔁 تغییر وضعیت پکیج';
    private const ADMIN_PACKAGE_DELETE = '🗑 حذف پکیج';
    private const ADMIN_USERS_REFRESH = '🔄 بروزرسانی لیست کاربران';
    private const ADMIN_USER_TOGGLE_STATUS = '⛔️/✅ تغییر وضعیت کاربر';
    private const ADMIN_USER_TOGGLE_AGENT = '🤝 تغییر نمایندگی';
    private const ADMIN_USER_BALANCE_ADD = '➕ افزایش موجودی';
    private const ADMIN_USER_BALANCE_SUB = '➖ کاهش موجودی';
    private const ADMIN_STOCK_REFRESH = '🔄 بروزرسانی موجودی';
    private const ADMIN_STOCK_ADD_CONFIG = '➕ افزودن کانفیگ';
    private const ADMIN_STOCK_SEARCH = '🔎 جستجوی کانفیگ';
    private const ADMIN_STOCK_SEARCH_CLEAR = '🧹 پاکسازی جستجو';
    private const ADMIN_STOCK_EXPIRE_TOGGLE = '⏳ تغییر وضعیت انقضا';
    private const ADMIN_STOCK_DELETE_CONFIG = '🗑 حذف کانفیگ';
    private const ADMIN_PAYMENTS_REFRESH = '🔄 بروزرسانی پرداخت‌ها';
    private const ADMIN_PAYMENT_APPROVE = '✅ تایید پرداخت';
    private const ADMIN_PAYMENT_REJECT = '❌ رد پرداخت';
    private const ADMIN_PAYMENT_VERIFY_CHAIN = '🔎 بررسی on-chain';
    private const ADMIN_REQUESTS_FREE = '🎁 درخواست‌های تست رایگان';
    private const ADMIN_REQUESTS_AGENCY = '🤝 درخواست‌های نمایندگی';
    private const ADMIN_REQUESTS_PENDING = '⏳ pending';
    private const ADMIN_REQUESTS_APPROVED = '✅ approved';
    private const ADMIN_REQUESTS_REJECTED = '❌ rejected';
    private const ADMIN_REQUEST_APPROVE = '✅ تایید درخواست';
    private const ADMIN_REQUEST_REJECT = '❌ رد درخواست';
    private const ADMIN_SETTINGS_REFRESH = '🔄 بروزرسانی تنظیمات';
    private const ADMIN_SETTINGS_EDIT = '✏️ ویرایش دستی تنظیم';
    private const ADMIN_SETTINGS_TOGGLE_BOT = '🤖 تغییر وضعیت ربات';
    private const ADMIN_SETTINGS_TOGGLE_FREE_TEST = '🎁 تغییر تست رایگان';
    private const ADMIN_SETTINGS_TOGGLE_AGENCY = '🤝 تغییر درخواست نمایندگی';
    private const ADMIN_SETTINGS_TOGGLE_GW_CARD = '💳 تغییر کارت‌به‌کارت';
    private const ADMIN_SETTINGS_TOGGLE_GW_CRYPTO = '💎 تغییر کریپتو';
    private const ADMIN_SETTINGS_TOGGLE_GW_TETRA = '🏦 تغییر TetraPay';
    private const ADMIN_SETTINGS_SET_CHANNEL = '📢 تنظیم کانال قفل';
    private const ADMIN_ADMINS_ADD = '➕ افزودن ادمین';
    private const ADMIN_ADMIN_DELETE = '🗑 حذف ادمین';
    private const ADMIN_PINS_ADD = '➕ افزودن پیام پین';
    private const ADMIN_PIN_SEND_ALL = '📤 ارسال به همه کاربران';
    private const ADMIN_PIN_EDIT = '✏️ ویرایش پیام پین';
    private const ADMIN_PIN_DELETE = '🗑 حذف پیام پین';
    private const ADMIN_AGENTS_REFRESH = '🔄 بروزرسانی نماینده‌ها';
    private const ADMIN_AGENT_SET_PRICE = '💵 ثبت قیمت';
    private const ADMIN_PANELS_REFRESH = '🔄 بروزرسانی پنل‌ها';
    private const ADMIN_PANELS_ADD = '➕ افزودن پنل';
    private const ADMIN_PANEL_TOGGLE = '🔁 تغییر وضعیت پنل';
    private const ADMIN_PANEL_DELETE = '🗑 حذف پنل';
    private const ADMIN_PANEL_PKG_ADD = '➕ افزودن پکیج پنل';
    private const ADMIN_BROADCAST_SCOPE_ALL = '🌐 همگانی';
    private const ADMIN_BROADCAST_SCOPE_USERS = '👥 کاربران';
    private const ADMIN_BROADCAST_SCOPE_AGENTS = '🤝 نماینده‌ها';
    private const ADMIN_BROADCAST_SCOPE_ADMINS = '👮 ادمین‌ها';
    private const ADMIN_BROADCAST_SEND = '📣 ارسال';
    private const ADMIN_DELIVERIES_REFRESH = '🔄 بروزرسانی تحویل‌ها';
    private const ADMIN_DELIVERY_DO = '✅ تحویل سفارش';
    private const ADMIN_GROUPOPS_SET_GROUP = '🧩 تنظیم Group ID';
    private const ADMIN_GROUPOPS_RESTORE = '♻️ بازیابی تنظیمات';
    private const ADMIN_FREETEST_RULE = '➕ افزودن/ویرایش قانون';
    private const ADMIN_FREETEST_RESET = '♻️ ریست سهمیه کاربر';

    public function __construct(
        private Database $database,
        private TelegramClient $telegram,
        private SettingsRepository $settings,
        private MenuService $menus,
        private PaymentGatewayService $gateways,
        private ?UiTextCatalogInterface $uiText = null,
        private ?UiKeyboardFactoryInterface $uiKeyboard = null,
    ) {
        $this->uiText ??= new UiTextCatalog();
        $this->uiKeyboard ??= new UiKeyboardFactory();
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
            if ($text === KeyboardBuilder::BTN_CHECK_CHANNEL) {
                if ($this->checkChannelMembership($userId)) {
                    $this->telegram->sendMessage($chatId, $this->menus->mainMenuText(), $this->menus->mainMenuReplyKeyboard($userId));
                } else {
                    $this->telegram->sendMessage($chatId, $this->channelLockText(), $this->channelLockKeyboard());
                    $this->telegram->sendMessage($chatId, 'بعد از عضویت، از دکمه معمولی زیر استفاده کنید:', $this->channelLockReplyKeyboard());
                }
                return;
            }
            $this->telegram->sendMessage($chatId, $this->channelLockText(), $this->channelLockKeyboard());
            $this->telegram->sendMessage($chatId, 'بعد از عضویت، از دکمه معمولی زیر استفاده کنید:', $this->channelLockReplyKeyboard());
            return;
        }

        $state = $this->database->getUserState($userId);
        if ($state === null) {
            if ($this->handleMainReplyKeyboardInput($chatId, $messageId, $userId, $fromUser, $text)) {
                return;
            }
            return;
        }

        if (($text === KeyboardBuilder::BTN_ADMIN || $text === '↩️ پنل مدیریت') && $this->database->isAdminUser($userId)) {
            $this->database->clearUserState($userId);
            $this->telegram->sendMessage($chatId, $this->uiText->warning('وضعیت قبلی منقضی شده بود و ریست شد. اکنون از پنل مدیریت ادامه دهید.'));
            $this->openAdminRoot($chatId, $userId);
            return;
        }

        if ($state['state_name'] === 'buy.done') {
            $this->database->clearUserState($userId);
            $this->telegram->sendMessage($chatId, $this->menus->mainMenuText(), $this->menus->mainMenuReplyKeyboard($userId));
            return;
        }

        if ($text !== '' && str_starts_with($text, '/start')) {
            $this->database->clearUserState($userId);
            return;
        }

        if ($state['state_name'] === 'await_buy_type_selection' || $state['state_name'] === 'buy.await_type') {
            $this->handleBuyTypeSelectionState($chatId, $userId, $text, $state);
            return;
        }

        if ($state['state_name'] === 'await_buy_package_selection' || $state['state_name'] === 'buy.await_package') {
            $this->handleBuyPackageSelectionState($chatId, $userId, $text, $state);
            return;
        }

        if ($state['state_name'] === 'await_renew_purchase_selection' || $state['state_name'] === 'renew.await_purchase') {
            $this->handleRenewPurchaseSelectionState($chatId, $userId, $text, $state);
            return;
        }

        if ($state['state_name'] === 'await_renew_package_selection' || $state['state_name'] === 'renew.await_package') {
            $this->handleRenewPackageSelectionState($chatId, $userId, $text, $state);
            return;
        }

        if ($state['state_name'] === 'await_buy_payment_selection' || $state['state_name'] === 'buy.await_payment_method') {
            $this->handleBuyPaymentSelectionState($chatId, $userId, $text, $state);
            return;
        }

        if ($state['state_name'] === 'await_renew_payment_selection' || $state['state_name'] === 'renew.await_payment_method') {
            $this->handleRenewPaymentSelectionState($chatId, $userId, $text, $state);
            return;
        }

        if ($state['state_name'] === 'await_gateway_verify' || $state['state_name'] === 'buy.await_payment_verify' || $state['state_name'] === 'renew.await_payment_verify') {
            $this->handleGatewayVerifyState($chatId, $userId, $text, $state);
            return;
        }

        if ($state['state_name'] === 'renew.done') {
            $this->database->clearUserState($userId);
            $this->telegram->sendMessage($chatId, $this->menus->mainMenuText(), $this->menus->mainMenuReplyKeyboard($userId));
            return;
        }

        if ($state['state_name'] === 'admin.root' || $state['state_name'] === 'admin.nav') {
            $this->handleAdminNavigationState($chatId, $userId, $text, $state);
            return;
        }

        if (
            $state['state_name'] === 'admin.types.list'
            || $state['state_name'] === 'admin.type.view'
            || $state['state_name'] === 'admin.type.create'
            || $state['state_name'] === 'admin.package.create'
            || $state['state_name'] === 'admin.package.view'
        ) {
            $this->handleAdminTypesPackagesState($chatId, $userId, $text, $state);
            return;
        }

        if (
            $state['state_name'] === 'admin.users.list'
            || $state['state_name'] === 'admin.user.view'
            || $state['state_name'] === 'admin.user.action'
            || $state['state_name'] === 'admin.stock.view'
            || $state['state_name'] === 'admin.stock.update'
        ) {
            $this->handleAdminUsersStockState($chatId, $userId, $text, $state, $message);
            return;
        }

        if (
            $state['state_name'] === 'admin.payments.list'
            || $state['state_name'] === 'admin.payment.view'
            || $state['state_name'] === 'admin.payment.review'
            || $state['state_name'] === 'admin.requests.list'
            || $state['state_name'] === 'admin.request.view'
            || $state['state_name'] === 'admin.request.review'
        ) {
            $this->handleAdminPaymentsRequestsState($chatId, $userId, $text, $state);
            return;
        }

        if (
            $state['state_name'] === 'admin.settings.view'
            || $state['state_name'] === 'admin.settings.edit'
            || $state['state_name'] === 'admin.admins.list'
            || $state['state_name'] === 'admin.admin.view'
            || $state['state_name'] === 'admin.admin.create'
            || $state['state_name'] === 'admin.admin.delete'
            || $state['state_name'] === 'admin.pins.list'
            || $state['state_name'] === 'admin.pin.view'
            || $state['state_name'] === 'admin.pin.create'
            || $state['state_name'] === 'admin.pin.edit'
            || $state['state_name'] === 'admin.pin.delete'
            || $state['state_name'] === 'admin.pin.send'
        ) {
            $this->handleAdminSettingsAdminsPinsState($chatId, $userId, $text, $state);
            return;
        }

        if (
            $state['state_name'] === 'admin.agents.list'
            || $state['state_name'] === 'admin.agent.view'
            || $state['state_name'] === 'admin.agent.edit'
            || $state['state_name'] === 'admin.panels.list'
            || $state['state_name'] === 'admin.panel.view'
            || $state['state_name'] === 'admin.panel.create'
            || $state['state_name'] === 'admin.panel.pkg.create'
            || $state['state_name'] === 'admin.panel.delete'
            || $state['state_name'] === 'admin.broadcast.compose'
            || $state['state_name'] === 'admin.broadcast.confirm'
            || $state['state_name'] === 'admin.deliveries.list'
            || $state['state_name'] === 'admin.delivery.view'
            || $state['state_name'] === 'admin.delivery.review'
            || $state['state_name'] === 'admin.groupops.view'
            || $state['state_name'] === 'admin.groupops.action'
            || $state['state_name'] === 'admin.freetest.menu'
            || $state['state_name'] === 'admin.freetest.rule'
            || $state['state_name'] === 'admin.freetest.reset'
        ) {
            $this->handleAdminFinalModulesState($chatId, $userId, $text, $state, $message);
            return;
        }

        if ($state['state_name'] === 'await_purchase_rules_accept' || $state['state_name'] === 'buy.await_rules_accept') {
            $this->handlePurchaseRulesAcceptState($chatId, $userId, $text, $state);
            return;
        }

        if ($state['state_name'] === 'await_admin_stock_results_open') {
            if ($text === '📚 نمایش نتایج موجودی') {
                $route = (string) (($state['payload'] ?? [])['route'] ?? '');
                if ($route !== '') {
                    $this->database->clearUserState($userId);
                    $this->telegram->sendMessage($chatId, $this->uiText->warning('مسیر قبلی منقضی شده است. لطفاً از پنل مدیریت دوباره شروع کنید.'));
                    $this->openAdminRoot($chatId, $userId);
                }
                return;
            }
            if ($text === KeyboardBuilder::BTN_ADMIN) {
                $this->database->clearUserState($userId);
                $this->openAdminRoot($chatId, $userId);
                return;
            }
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
                $this->telegram->sendMessage($chatId, $this->uiText->error('مبلغ وارد شده معتبر نیست. لطفاً فقط عدد صحیح وارد کنید.'));
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

            $adminKeyboard = $this->replyKeyboard([
                ["✅ تایید #{$paymentId}", "❌ رد #{$paymentId}"],
                [KeyboardBuilder::BTN_ADMIN],
            ]);
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

            $adminKeyboard = $this->replyKeyboard([
                ["✅ تایید #{$paymentId}", "❌ رد #{$paymentId}"],
                [KeyboardBuilder::BTN_ADMIN],
            ]);
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

            $adminKeyboard = $this->replyKeyboard([
                ["✅ تایید #{$paymentId}", "❌ رد #{$paymentId}"],
                [KeyboardBuilder::BTN_ADMIN],
            ]);
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

            $adminKeyboard = $this->replyKeyboard([
                ["✅ تایید #{$paymentId}", "❌ رد #{$paymentId}"],
                [KeyboardBuilder::BTN_ADMIN],
            ]);
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

            $adminKeyboard = $this->replyKeyboard([
                ["✅ تایید #{$paymentId}", "❌ رد #{$paymentId}"],
                [KeyboardBuilder::BTN_ADMIN],
            ]);
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
                    $this->replyKeyboard([
                        ["👀 درخواست تست #{$requestId}"],
                        ['🗂 درخواست‌ها'],
                        [KeyboardBuilder::BTN_ADMIN],
                    ])
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
                    $this->replyKeyboard([
                        ["👀 درخواست نمایندگی #{$requestId}"],
                        ['🗂 درخواست‌ها'],
                        [KeyboardBuilder::BTN_ADMIN],
                    ])
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
            if ($text === UiLabels::BTN_CANCEL || $text === UiLabels::BTN_MAIN || $text === KeyboardBuilder::BTN_BACK_MAIN) {
                $this->openAdminRoot($chatId, $userId);
                return;
            }

            $payload = $state['payload'] ?? [];
            $requestKind = (string) ($payload['request_kind'] ?? '');
            $requestId = (int) ($payload['request_id'] ?? 0);
            $approve = ((int) ($payload['approve'] ?? 0)) === 1;
            if ($text === UiLabels::BTN_BACK) {
                if ($requestKind === 'free' || $requestKind === 'agency') {
                    $this->openAdminRequestView($chatId, $userId, $requestKind, $requestId, 'pending', $this->uiText->info('این مسیر legacy است؛ ادامه بررسی از مسیر canonical انجام می‌شود.'));
                } else {
                    $this->openAdminRequestsList($chatId, $userId, '', 'pending', $this->uiText->info('این مسیر legacy است؛ ادامه بررسی از مسیر canonical انجام می‌شود.'));
                }
                return;
            }
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
                $this->openAdminRequestsList($chatId, $userId, $requestKind, 'pending');
                return;
            }

            $statusText = $approve ? '✅ تایید شد' : '❌ رد شد';
            $label = $requestKind === 'free' ? 'درخواست تست رایگان' : 'درخواست نمایندگی';
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
            $this->openAdminRequestsList($chatId, $userId, $requestKind, 'pending', $this->uiText->info('مسیر قبلی برای سازگاری نگه داشته شده اما canonical نیست.'));
            return;
        }

        if ($state['state_name'] === 'await_admin_type_name') {
            if (!in_array($userId, Config::adminIds(), true)) {
                $this->database->clearUserState($userId);
                return;
            }
            if ($text === UiLabels::BTN_CANCEL || $text === UiLabels::BTN_MAIN || $text === KeyboardBuilder::BTN_BACK_MAIN) {
                $this->openAdminRoot($chatId, $userId);
                return;
            }
            if ($text === UiLabels::BTN_BACK) {
                $this->openAdminTypesList($chatId, $userId, $this->uiText->info('این مسیر legacy است؛ از این به بعد مسیر canonical در منوی reply انجام می‌شود.'));
                return;
            }
            if ($text === '' || str_starts_with($text, '/')) {
                $this->telegram->sendMessage($chatId, $this->uiText->warning('لطفاً نام نوع سرویس را ارسال کنید.'));
                return;
            }
            $typeId = $this->database->addType($text, '');
            $this->telegram->sendMessage($chatId, $this->uiText->success("نوع سرویس ثبت شد. شناسه: <code>{$typeId}</code>"));
            $this->openAdminTypesList($chatId, $userId, $this->uiText->info('مسیر قبلی برای سازگاری نگه داشته شده اما canonical نیست.'));
            return;
        }

        if ($state['state_name'] === 'await_admin_package') {
            if (!in_array($userId, Config::adminIds(), true)) {
                $this->database->clearUserState($userId);
                return;
            }
            if ($text === UiLabels::BTN_CANCEL || $text === UiLabels::BTN_MAIN || $text === KeyboardBuilder::BTN_BACK_MAIN) {
                $this->openAdminRoot($chatId, $userId);
                return;
            }
            if ($text === '' || str_starts_with($text, '/')) {
                $this->telegram->sendMessage($chatId, $this->uiText->warning('فرمت ورودی نامعتبر است.'));
                return;
            }
            $payload = $state['payload'] ?? [];
            $typeId = (int) ($payload['type_id'] ?? 0);
            if ($text === UiLabels::BTN_BACK) {
                if ($typeId > 0) {
                    $this->openAdminTypeView($chatId, $userId, $typeId, $this->uiText->info('این مسیر legacy است؛ برای ادامه از مسیر canonical استفاده کنید.'));
                } else {
                    $this->openAdminTypesList($chatId, $userId, $this->uiText->info('این مسیر legacy است؛ برای ادامه از مسیر canonical استفاده کنید.'));
                }
                return;
            }
            $parts = array_map('trim', explode('|', $text));
            if (count($parts) !== 4) {
                $this->telegram->sendMessage($chatId, $this->uiText->warning('فرمت باید 4 بخشی و با | جدا شود.'));
                return;
            }
            [$name, $volumeRaw, $durationRaw, $priceRaw] = $parts;
            $volume = (float) str_replace(',', '.', $volumeRaw);
            $duration = (int) preg_replace('/\D+/', '', $durationRaw);
            $price = (int) preg_replace('/\D+/', '', $priceRaw);
            if ($name === '' || $volume <= 0 || $duration <= 0 || $price <= 0 || $typeId <= 0) {
                $this->telegram->sendMessage($chatId, $this->uiText->warning('مقادیر واردشده معتبر نیستند.'));
                return;
            }
            $packageId = $this->database->addPackage($typeId, $name, $volume, $duration, $price);
            $this->telegram->sendMessage($chatId, $this->uiText->success("پکیج ثبت شد. شناسه: <code>{$packageId}</code>"));
            $this->openAdminTypeView($chatId, $userId, $typeId, $this->uiText->info('مسیر قبلی برای سازگاری نگه داشته شده اما canonical نیست.'));
            return;
        }

        if ($state['state_name'] === 'await_admin_user_balance') {
            if (!in_array($userId, Config::adminIds(), true)) {
                $this->database->clearUserState($userId);
                return;
            }
            if ($text === UiLabels::BTN_CANCEL || $text === UiLabels::BTN_MAIN || $text === KeyboardBuilder::BTN_BACK_MAIN) {
                $this->openAdminRoot($chatId, $userId);
                return;
            }
            $payload = $state['payload'] ?? [];
            $targetUid = (int) ($payload['target_user_id'] ?? 0);
            $mode = (string) ($payload['mode'] ?? 'add');
            if ($text === UiLabels::BTN_BACK) {
                $this->openAdminUserView($chatId, $userId, $targetUid, $this->uiText->info('این مسیر legacy است؛ ادامه کار از flow canonical انجام می‌شود.'));
                return;
            }
            $amount = (int) preg_replace('/\D+/', '', $text);
            if ($targetUid <= 0 || $amount <= 0) {
                $this->telegram->sendMessage($chatId, $this->uiText->warning('مبلغ معتبر وارد کنید.'));
                return;
            }
            $delta = $mode === 'sub' ? -$amount : $amount;
            $this->database->updateUserBalance($targetUid, $delta);
            $this->openAdminUserView($chatId, $userId, $targetUid, $this->uiText->info('مسیر قبلی برای سازگاری نگه داشته شده اما canonical نیست.'));
            return;
        }

        if ($state['state_name'] === 'await_admin_add_config') {
            if (!in_array($userId, Config::adminIds(), true)) {
                $this->database->clearUserState($userId);
                return;
            }
            if ($text === UiLabels::BTN_CANCEL || $text === UiLabels::BTN_MAIN || $text === KeyboardBuilder::BTN_BACK_MAIN) {
                $this->openAdminRoot($chatId, $userId);
                return;
            }
            $payload = $state['payload'] ?? [];
            $typeId = (int) ($payload['type_id'] ?? 0);
            $packageId = (int) ($payload['package_id'] ?? 0);
            if ($text === UiLabels::BTN_BACK) {
                $this->openAdminStockConfigsView($chatId, $userId, $typeId, $packageId, '', $this->uiText->info('این مسیر legacy است؛ برای ادامه از flow canonical استفاده کنید.'));
                return;
            }
            $raw = trim((string) ($message['text'] ?? ''));
            if ($raw === '' || str_starts_with($raw, '/')) {
                $this->telegram->sendMessage($chatId, $this->uiText->warning('لطفاً متن کانفیگ را طبق فرمت ارسال کنید.'));
                return;
            }
            $chunks = preg_split('/\n---\n/', $raw) ?: [];
            if (count($chunks) < 2) {
                $this->telegram->sendMessage($chatId, $this->uiText->warning('فرمت نامعتبر است. جداکننده --- را رعایت کنید.'));
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
                $this->telegram->sendMessage($chatId, $this->uiText->warning('نام سرویس یا متن کانفیگ معتبر نیست.'));
                return;
            }
            $configId = $this->database->addConfig($typeId, $packageId, $serviceName, $configText, $inquiry);
            $this->openAdminStockConfigsView($chatId, $userId, $typeId, $packageId, '', $this->uiText->info("مسیر قبلی canonical نیست؛ کانفیگ با شناسه <code>{$configId}</code> ثبت شد."));
            return;
        }

        if ($state['state_name'] === 'await_admin_stock_search') {
            if (!in_array($userId, Config::adminIds(), true)) {
                $this->database->clearUserState($userId);
                return;
            }
            if ($text === UiLabels::BTN_CANCEL || $text === UiLabels::BTN_MAIN || $text === KeyboardBuilder::BTN_BACK_MAIN) {
                $this->openAdminRoot($chatId, $userId);
                return;
            }
            $payload = $state['payload'] ?? [];
            $packageId = (int) ($payload['package_id'] ?? 0);
            $typeId = (int) ($payload['type_id'] ?? 0);
            if ($text === UiLabels::BTN_BACK) {
                $this->openAdminStockConfigsView($chatId, $userId, $typeId, $packageId, '', $this->uiText->info('این مسیر legacy است؛ ادامه از مسیر canonical انجام می‌شود.'));
                return;
            }
            $query = trim((string) ($message['text'] ?? ''));
            if ($query === '-' || $query === '—') {
                $query = '';
            }
            $this->openAdminStockConfigsView($chatId, $userId, $typeId, $packageId, $query, $this->uiText->info('مسیر جستجوی قبلی canonical نیست و به flow جدید منتقل شد.'));
            return;
        }

        if ($state['state_name'] === 'await_admin_add_admin') {
            if (!in_array($userId, Config::adminIds(), true)) {
                $this->database->clearUserState($userId);
                return;
            }
            if ($text === UiLabels::BTN_CANCEL || $text === UiLabels::BTN_MAIN || $text === KeyboardBuilder::BTN_BACK_MAIN) {
                $this->openAdminRoot($chatId, $userId);
                return;
            }
            if ($text === UiLabels::BTN_BACK) {
                $this->openAdminAdminsList($chatId, $userId, $this->uiText->info('این مسیر legacy است؛ ادامه از مسیر canonical انجام می‌شود.'));
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
            $this->openAdminAdminView($chatId, $userId, $targetUid, $this->uiText->info('مسیر قبلی canonical نیست و به flow جدید منتقل شد.'));
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
            if ($text === UiLabels::BTN_CANCEL || $text === UiLabels::BTN_MAIN || $text === KeyboardBuilder::BTN_BACK_MAIN) {
                $this->openAdminRoot($chatId, $userId);
                return;
            }
            if ($text === UiLabels::BTN_BACK) {
                $this->openAdminSettingsView($chatId, $userId, $this->uiText->info('این مسیر legacy است؛ ادامه از مسیر canonical انجام می‌شود.'));
                return;
            }
            $value = trim($text);
            if ($value === '') {
                $this->telegram->sendMessage($chatId, '⚠️ مقدار کانال نمی‌تواند خالی باشد. برای غیرفعال‌سازی «-» ارسال کنید.');
                return;
            }
            $channelId = $value === '-' ? '' : $value;
            $this->settings->set('channel_id', $channelId);
            $msg = $channelId === '' ? '✅ قفل کانال غیرفعال شد.' : "✅ کانال قفل ذخیره شد: <code>" . htmlspecialchars($channelId) . "</code>";
            $this->openAdminSettingsView($chatId, $userId, $this->uiText->info("مسیر قبلی canonical نیست. {$msg}"));
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
            if ($text === UiLabels::BTN_CANCEL || $text === UiLabels::BTN_MAIN || $text === KeyboardBuilder::BTN_BACK_MAIN) {
                $this->openAdminRoot($chatId, $userId);
                return;
            }
            if ($text === UiLabels::BTN_BACK) {
                $this->openAdminPinsList($chatId, $userId, $this->uiText->info('این مسیر legacy است؛ ادامه از مسیر canonical انجام می‌شود.'));
                return;
            }
            $body = trim((string) ($message['text'] ?? ''));
            if ($body === '') {
                $this->telegram->sendMessage($chatId, '⚠️ متن پیام پین نمی‌تواند خالی باشد.');
                return;
            }
            $pinId = $this->database->addPinnedMessage($body);
            $this->openAdminPinView($chatId, $userId, $pinId, $this->uiText->info('مسیر قبلی canonical نیست و به flow جدید منتقل شد.'));
            return;
        }

        if ($state['state_name'] === 'await_admin_pin_edit') {
            if (!$this->database->isAdminUser($userId)) {
                $this->database->clearUserState($userId);
                return;
            }
            $pinId = (int) (($state['payload'] ?? [])['pin_id'] ?? 0);
            if ($text === UiLabels::BTN_CANCEL || $text === UiLabels::BTN_MAIN || $text === KeyboardBuilder::BTN_BACK_MAIN) {
                $this->openAdminRoot($chatId, $userId);
                return;
            }
            if ($text === UiLabels::BTN_BACK) {
                $this->openAdminPinView($chatId, $userId, $pinId, $this->uiText->info('این مسیر legacy است؛ ادامه از مسیر canonical انجام می‌شود.'));
                return;
            }
            $body = trim((string) ($message['text'] ?? ''));
            if ($pinId <= 0 || $body === '') {
                $this->telegram->sendMessage($chatId, '⚠️ داده ویرایش پیام پین معتبر نیست.');
                return;
            }
            $this->database->updatePinnedMessage($pinId, $body);
            $this->openAdminPinView($chatId, $userId, $pinId, $this->uiText->info('مسیر قبلی canonical نیست و به flow جدید منتقل شد.'));
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
                $this->menus->referralKeyboard($userId)
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
            $this->openAdminRoot($chatId, $userId);
            return true;
        }

        if ($text === '↩️ پنل مدیریت') {
            if (!$this->database->isAdminUser($userId)) {
                return false;
            }
            $this->database->clearUserState($userId);
            $this->openAdminRoot($chatId, $userId);
            return true;
        }

        if ($this->database->isAdminUser($userId)) {
            if ($text === '⚙️ تنظیمات') {
                $this->openAdminSettingsView($chatId, $userId);
                return true;
            }
            if ($text === '👮 ادمین‌ها') {
                $this->openAdminAdminsList($chatId, $userId);
                return true;
            }
            if ($text === '📌 پین‌ها') {
                $this->openAdminPinsList($chatId, $userId);
                return true;
            }
            if ($text === '🤝 نماینده‌ها') {
                $this->openAdminAgentsList($chatId, $userId);
                return true;
            }
            if ($text === '🖥 پنل‌های 3x-ui') {
                $this->openAdminPanelsList($chatId, $userId);
                return true;
            }
            if ($text === '📣 همگانی') {
                $this->openAdminBroadcastCompose($chatId, $userId);
                return true;
            }
            if ($text === '📦 تحویل سفارش') {
                $this->openAdminDeliveriesList($chatId, $userId);
                return true;
            }
            if ($text === '🗃 بکاپ/تاپیک') {
                $this->openAdminGroupOpsView($chatId, $userId);
                return true;
            }
            if ($text === '🧪 تست رایگان') {
                $this->openAdminFreeTestMenu($chatId, $userId);
                return true;
            }
            if ($text === '💳 شارژها') {
                $this->openAdminPaymentsList($chatId, $userId);
                return true;
            }
            if ($text === '🗂 درخواست‌ها') {
                $this->openAdminRequestsList($chatId, $userId);
                return true;
            }
            if (preg_match('/^(✅ تایید|❌ رد)\s*#(\d+)$/u', $text, $m) === 1) {
                $paymentId = (int) ($m[2] ?? 0);
                if ($paymentId > 0) {
                    $this->openAdminPaymentView(
                        $chatId,
                        $userId,
                        $paymentId,
                        $this->uiText->info('دکمه قدیمی است؛ مسیر canonical برای بررسی پرداخت باز شد.')
                    );
                    return true;
                }
            }
            if (preg_match('/^👀\s*درخواست تست\s*#(\d+)$/u', $text, $m) === 1) {
                $requestId = (int) ($m[1] ?? 0);
                if ($requestId > 0) {
                    $this->openAdminRequestView(
                        $chatId,
                        $userId,
                        'free',
                        $requestId,
                        'pending',
                        $this->uiText->info('دکمه قدیمی است؛ مسیر canonical برای بررسی درخواست باز شد.')
                    );
                    return true;
                }
            }
            if (preg_match('/^👀\s*درخواست نمایندگی\s*#(\d+)$/u', $text, $m) === 1) {
                $requestId = (int) ($m[1] ?? 0);
                if ($requestId > 0) {
                    $this->openAdminRequestView(
                        $chatId,
                        $userId,
                        'agency',
                        $requestId,
                        'pending',
                        $this->uiText->info('دکمه قدیمی است؛ مسیر canonical برای بررسی درخواست باز شد.')
                    );
                    return true;
                }
            }
        }

        if ($text === KeyboardBuilder::BTN_BACK_MAIN) {
            $this->database->clearUserState($userId);
            $this->telegram->sendMessage($chatId, $this->menus->mainMenuText(), $this->menus->mainMenuReplyKeyboard($userId));
            return true;
        }

        return false;
    }

    private function startBuyTypeReplyFlow(int $chatId, int $userId): void
    {
        if ($this->settings->get('shop_open', '1') !== '1') {
            $this->telegram->sendMessage($chatId, $this->uiText->warning('فروشگاه در حال حاضر بسته است.'));
            return;
        }
        $types = $this->database->getActiveTypes();
        if ($types === []) {
            $this->telegram->sendMessage($chatId, $this->uiText->info('فعلاً سرویسی برای خرید فعال نیست.'));
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
            $this->telegram->sendMessage($chatId, $this->uiText->info('فعلاً سرویسی برای خرید فعال نیست.'));
            return;
        }

        $buttons[] = [UiLabels::BTN_MAIN, UiLabels::BTN_CANCEL];
        $this->database->setUserState($userId, 'buy.await_type', ['options' => $optionMap, 'stack' => [], 'type_id' => null, 'package_id' => null, 'payment_method' => null]);
        $this->telegram->sendMessage(
            $chatId,
            $this->uiText->multi(new UiTextBlock(
                title: '🛒 <b>خرید کانفیگ</b>',
                lines: [
                    new UiTextLine('🧩', 'انتخاب نوع سرویس', implode("\n", $lines)),
                ],
                tipBlockquote: '💡 ابتدا نوع سرویس را انتخاب کنید؛ در مرحله بعد لیست پکیج‌های همان نوع نمایش داده می‌شود و می‌توانید با دکمه‌های بازگشت یا منوی اصلی مسیر را مدیریت کنید.',
            )),
            $this->uiKeyboard->replyMenu($buttons)
        );
    }

    private function openAdminRoot(int $chatId, int $userId): void
    {
        $this->database->setUserState($userId, 'admin.root', ['stack' => []]);
        $this->telegram->sendMessage($chatId, $this->menus->adminRootText(), $this->menus->adminRootReplyKeyboard());
    }

    private function handleAdminNavigationState(int $chatId, int $userId, string $text, array $state): void
    {
        if ($text === UiLabels::BTN_CANCEL) {
            $this->openAdminRoot($chatId, $userId);
            return;
        }
        if ($text === UiLabels::BTN_MAIN || $text === KeyboardBuilder::BTN_BACK_MAIN) {
            $this->database->clearUserState($userId);
            $this->telegram->sendMessage($chatId, $this->menus->mainMenuText(), $this->menus->mainMenuReplyKeyboard($userId));
            return;
        }
        if ($text === UiLabels::BTN_BACK) {
            $this->openAdminRoot($chatId, $userId);
            return;
        }

        if (($state['state_name'] ?? '') === 'admin.root') {
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
            ];
            $route = $adminRouteMap[$text] ?? '';
            if ($route !== '') {
                if ($route === 'admin:types') {
                    $this->openAdminTypesList($chatId, $userId);
                    return;
                }
                if ($route === 'admin:users') {
                    $this->openAdminUsersList($chatId, $userId);
                    return;
                }
                if ($route === 'admin:stock') {
                    $this->openAdminStockTypesView($chatId, $userId);
                    return;
                }
                if ($route === 'admin:payments') {
                    $this->openAdminPaymentsList($chatId, $userId);
                    return;
                }
                if ($route === 'admin:requests') {
                    $this->openAdminRequestsList($chatId, $userId);
                    return;
                }
                if ($route === 'admin:settings') {
                    $this->openAdminSettingsView($chatId, $userId);
                    return;
                }
                if ($route === 'admin:admins') {
                    $this->openAdminAdminsList($chatId, $userId);
                    return;
                }
                if ($route === 'admin:pins') {
                    $this->openAdminPinsList($chatId, $userId);
                    return;
                }
                if ($route === 'admin:agents') {
                    $this->openAdminAgentsList($chatId, $userId);
                    return;
                }
                if ($route === 'admin:panels') {
                    $this->openAdminPanelsList($chatId, $userId);
                    return;
                }
                if ($route === 'admin:broadcast') {
                    $this->openAdminBroadcastCompose($chatId, $userId);
                    return;
                }
                if ($route === 'admin:deliveries') {
                    $this->openAdminDeliveriesList($chatId, $userId);
                    return;
                }
                if ($route === 'admin:groupops') {
                    $this->openAdminGroupOpsView($chatId, $userId);
                    return;
                }
                if ($route === 'admin:free_test:menu') {
                    $this->openAdminFreeTestMenu($chatId, $userId);
                    return;
                }
                $this->database->setUserState($userId, 'admin.nav', [
                    'module' => $route,
                    'stack' => ['admin.root'],
                ]);
                $this->telegram->sendMessage(
                    $chatId,
                    $this->uiText->multi(new UiTextBlock(
                        title: '⚙️ <b>ناوبری پنل مدیریت</b>',
                        lines: [
                            new UiTextLine('📍', 'ماژول انتخاب‌شده', '<code>' . htmlspecialchars($route) . '</code>'),
                        ],
                        tipBlockquote: '💡 این ماژول در chunkهای بعدی به‌صورت کامل به reply/state مهاجرت می‌شود. برای ادامه از بازگشت یا منوی اصلی استفاده کنید.',
                    )),
                    $this->uiKeyboard->replyMenu([[UiLabels::BTN_BACK, UiLabels::BTN_MAIN, UiLabels::BTN_CANCEL]])
                );
                return;
            }
        }

        $this->telegram->sendMessage($chatId, $this->uiText->warning('گزینه نامعتبر است. لطفاً از دکمه‌های پنل مدیریت استفاده کنید.'));
    }

    private function handleAdminTypesPackagesState(int $chatId, int $userId, string $text, array $state): void
    {
        if ($text === UiLabels::BTN_CANCEL || $text === UiLabels::BTN_MAIN || $text === KeyboardBuilder::BTN_BACK_MAIN) {
            $this->openAdminRoot($chatId, $userId);
            return;
        }

        $stateName = (string) ($state['state_name'] ?? '');
        $payload = is_array($state['payload'] ?? null) ? $state['payload'] : [];

        if ($stateName === 'admin.types.list') {
            if ($text === UiLabels::BTN_BACK) {
                $this->openAdminRoot($chatId, $userId);
                return;
            }
            if ($text === self::ADMIN_TYPES_ADD) {
                $this->database->setUserState($userId, 'admin.type.create', ['stack' => ['admin.types.list']]);
                $this->telegram->sendMessage(
                    $chatId,
                    $this->uiText->multi(new UiTextBlock(
                        title: '🆕 <b>افزودن نوع سرویس</b>',
                        lines: [
                            new UiTextLine('✍️', 'راهنما', 'نام نوع سرویس جدید را ارسال کنید.'),
                        ],
                        tipBlockquote: '💡 بعد از ثبت، به لیست نوع‌ها برمی‌گردید و می‌توانید پکیج‌های مرتبط را مدیریت کنید.',
                    )),
                    $this->uiKeyboard->replyMenu([[UiLabels::BTN_BACK, UiLabels::BTN_MAIN, UiLabels::BTN_CANCEL]])
                );
                return;
            }

            $options = is_array($payload['options'] ?? null) ? $payload['options'] : [];
            $selected = $this->extractOptionKey($text);
            $typeId = isset($options[$selected]) ? (int) $options[$selected] : 0;
            if ($typeId > 0) {
                $this->openAdminTypeView($chatId, $userId, $typeId);
                return;
            }
            $this->telegram->sendMessage($chatId, $this->uiText->warning('گزینه نامعتبر است. لطفاً یکی از دکمه‌های لیست نوع‌ها را انتخاب کنید.'));
            return;
        }

        if ($stateName === 'admin.type.create') {
            if ($text === UiLabels::BTN_BACK) {
                $this->openAdminTypesList($chatId, $userId);
                return;
            }
            if ($text === '' || str_starts_with($text, '/')) {
                $this->telegram->sendMessage($chatId, $this->uiText->warning('لطفاً نام نوع سرویس را به‌صورت متن معمولی ارسال کنید.'));
                return;
            }
            $typeId = $this->database->addType($text, '');
            $this->telegram->sendMessage($chatId, $this->uiText->success("نوع سرویس با شناسه <code>{$typeId}</code> ثبت شد."));
            $this->openAdminTypesList($chatId, $userId);
            return;
        }

        if ($stateName === 'admin.type.view') {
            $typeId = (int) ($payload['type_id'] ?? 0);
            if ($typeId <= 0) {
                $this->openAdminTypesList($chatId, $userId, $this->uiText->warning('نوع سرویس نامعتبر بود. دوباره از لیست انتخاب کنید.'));
                return;
            }
            if ($text === UiLabels::BTN_BACK) {
                $this->openAdminTypesList($chatId, $userId);
                return;
            }
            if ($text === self::ADMIN_TYPE_ADD_PACKAGE) {
                $this->database->setUserState($userId, 'admin.package.create', ['type_id' => $typeId, 'stack' => ['admin.type.view']]);
                $this->telegram->sendMessage(
                    $chatId,
                    $this->uiText->multi(new UiTextBlock(
                        title: '📦 <b>افزودن پکیج</b>',
                        lines: [
                            new UiTextLine('🧾', 'فرمت', '<code>نام|حجم(GB)|مدت(روز)|قیمت</code>'),
                        ],
                        tipBlockquote: '💡 مثال: <code>اقتصادی|50|30|120000</code>',
                    )),
                    $this->uiKeyboard->replyMenu([[UiLabels::BTN_BACK, UiLabels::BTN_MAIN, UiLabels::BTN_CANCEL]])
                );
                return;
            }
            if ($text === self::ADMIN_TYPE_TOGGLE) {
                $type = $this->findTypeById($typeId);
                if ($type === null) {
                    $this->openAdminTypesList($chatId, $userId, $this->uiText->warning('نوع سرویس پیدا نشد.'));
                    return;
                }
                $isActive = ((int) ($type['is_active'] ?? 0)) === 1;
                $this->database->setTypeActive($typeId, !$isActive);
                $this->openAdminTypeView($chatId, $userId, $typeId, $this->uiText->success('وضعیت نوع سرویس بروزرسانی شد.'));
                return;
            }
            if ($text === self::ADMIN_TYPE_DELETE) {
                $this->database->deleteType($typeId);
                $this->openAdminTypesList($chatId, $userId, $this->uiText->success('نوع سرویس حذف شد.'));
                return;
            }

            $options = is_array($payload['options'] ?? null) ? $payload['options'] : [];
            $selected = $this->extractOptionKey($text);
            $packageId = isset($options[$selected]) ? (int) $options[$selected] : 0;
            if ($packageId > 0) {
                $this->openAdminPackageView($chatId, $userId, $typeId, $packageId);
                return;
            }

            $this->telegram->sendMessage($chatId, $this->uiText->warning('گزینه نامعتبر است. یکی از پکیج‌ها یا عملیات مدیریتی همین صفحه را انتخاب کنید.'));
            return;
        }

        if ($stateName === 'admin.package.create') {
            $typeId = (int) ($payload['type_id'] ?? 0);
            if ($typeId <= 0) {
                $this->openAdminTypesList($chatId, $userId, $this->uiText->warning('نوع سرویس معتبر نبود. لطفاً دوباره انتخاب کنید.'));
                return;
            }
            if ($text === UiLabels::BTN_BACK) {
                $this->openAdminTypeView($chatId, $userId, $typeId);
                return;
            }
            if ($text === '' || str_starts_with($text, '/')) {
                $this->telegram->sendMessage($chatId, $this->uiText->warning('فرمت ورودی معتبر نیست. لطفاً مطابق راهنما ارسال کنید.'));
                return;
            }
            $parts = array_map('trim', explode('|', $text));
            if (count($parts) !== 4) {
                $this->telegram->sendMessage($chatId, $this->uiText->warning('فرمت باید ۴ بخش داشته باشد: نام|حجم|مدت|قیمت'));
                return;
            }
            [$name, $volumeRaw, $durationRaw, $priceRaw] = $parts;
            $volume = (float) str_replace(',', '.', $volumeRaw);
            $duration = (int) preg_replace('/\D+/', '', $durationRaw);
            $price = (int) preg_replace('/\D+/', '', $priceRaw);
            if ($name === '' || $volume <= 0 || $duration <= 0 || $price <= 0) {
                $this->telegram->sendMessage($chatId, $this->uiText->warning('مقادیر واردشده معتبر نیستند. مثال: <code>اقتصادی|50|30|120000</code>'));
                return;
            }
            $packageId = $this->database->addPackage($typeId, $name, $volume, $duration, $price);
            $this->telegram->sendMessage($chatId, $this->uiText->success("پکیج با شناسه <code>{$packageId}</code> ثبت شد."));
            $this->openAdminTypeView($chatId, $userId, $typeId);
            return;
        }

        if ($stateName === 'admin.package.view') {
            $typeId = (int) ($payload['type_id'] ?? 0);
            $packageId = (int) ($payload['package_id'] ?? 0);
            if ($typeId <= 0 || $packageId <= 0) {
                $this->openAdminTypesList($chatId, $userId, $this->uiText->warning('اطلاعات پکیج معتبر نبود. لطفاً دوباره انتخاب کنید.'));
                return;
            }
            if ($text === UiLabels::BTN_BACK) {
                $this->openAdminTypeView($chatId, $userId, $typeId);
                return;
            }
            if ($text === self::ADMIN_PACKAGE_TOGGLE) {
                $package = $this->findPackageInType($typeId, $packageId);
                if ($package === null) {
                    $this->openAdminTypeView($chatId, $userId, $typeId, $this->uiText->warning('پکیج پیدا نشد.'));
                    return;
                }
                $isActive = ((int) ($package['is_active'] ?? 0)) === 1;
                $this->database->setPackageActive($packageId, !$isActive);
                $this->openAdminPackageView($chatId, $userId, $typeId, $packageId, $this->uiText->success('وضعیت پکیج بروزرسانی شد.'));
                return;
            }
            if ($text === self::ADMIN_PACKAGE_DELETE) {
                $this->database->deletePackage($packageId);
                $this->openAdminTypeView($chatId, $userId, $typeId, $this->uiText->success('پکیج حذف شد.'));
                return;
            }

            $this->telegram->sendMessage($chatId, $this->uiText->warning('گزینه نامعتبر است. لطفاً از دکمه‌های مدیریت پکیج استفاده کنید.'));
            return;
        }
    }

    private function openAdminTypesList(int $chatId, int $userId, ?string $notice = null): void
    {
        $types = $this->database->listTypes();
        $lines = [];
        $options = [];
        $buttons = [[self::ADMIN_TYPES_ADD]];
        foreach (array_values($types) as $idx => $type) {
            $num = (string) ($idx + 1);
            $typeId = (int) ($type['id'] ?? 0);
            if ($typeId <= 0) {
                continue;
            }
            $isActive = ((int) ($type['is_active'] ?? 0)) === 1;
            $status = $isActive ? '🟢' : '🔴';
            $name = trim((string) ($type['name'] ?? '—'));
            $lines[] = "{$num}) {$status} {$name} | ID: {$typeId}";
            $options[$num] = $typeId;
            $buttons[] = ["{$num} - {$name}"];
        }
        $buttons[] = [UiLabels::BTN_BACK, UiLabels::BTN_MAIN, UiLabels::BTN_CANCEL];
        $this->database->setUserState($userId, 'admin.types.list', ['options' => $options, 'stack' => ['admin.root']]);
        if ($notice !== null && $notice !== '') {
            $this->telegram->sendMessage($chatId, $notice);
        }
        $this->telegram->sendMessage(
            $chatId,
            $this->uiText->multi(new UiTextBlock(
                title: '🧩 <b>مدیریت نوع/پکیج</b>',
                lines: [
                    new UiTextLine('📋', 'لیست نوع‌ها', $lines !== [] ? implode("\n", $lines) : 'هنوز نوع سرویسی ثبت نشده است.'),
                ],
                tipBlockquote: '💡 یک نوع را انتخاب کنید تا پکیج‌ها و عملیات همان نوع باز شود.',
            )),
            $this->uiKeyboard->replyMenu($buttons)
        );
    }

    private function openAdminTypeView(int $chatId, int $userId, int $typeId, ?string $notice = null): void
    {
        $type = $this->findTypeById($typeId);
        if ($type === null) {
            $this->openAdminTypesList($chatId, $userId, $this->uiText->warning('نوع سرویس پیدا نشد.'));
            return;
        }

        $packages = $this->database->listPackagesByType($typeId);
        $lines = [];
        $options = [];
        $buttons = [[self::ADMIN_TYPE_ADD_PACKAGE], [self::ADMIN_TYPE_TOGGLE, self::ADMIN_TYPE_DELETE]];
        foreach (array_values($packages) as $idx => $pkg) {
            $num = (string) ($idx + 1);
            $packageId = (int) ($pkg['id'] ?? 0);
            if ($packageId <= 0) {
                continue;
            }
            $isActive = ((int) ($pkg['is_active'] ?? 0)) === 1;
            $status = $isActive ? '🟢' : '🔴';
            $name = trim((string) ($pkg['name'] ?? 'پکیج'));
            $price = (int) ($pkg['price'] ?? 0);
            $days = (int) ($pkg['duration_days'] ?? 0);
            $volume = (float) ($pkg['volume_gb'] ?? 0);
            $lines[] = "{$num}) {$status} {$name} | ID: {$packageId} | {$volume}GB | {$days} روز | {$price} تومان";
            $options[$num] = $packageId;
            $buttons[] = ["{$num} - {$name}"];
        }
        $buttons[] = [UiLabels::BTN_BACK, UiLabels::BTN_MAIN, UiLabels::BTN_CANCEL];

        $this->database->setUserState($userId, 'admin.type.view', ['type_id' => $typeId, 'options' => $options, 'stack' => ['admin.types.list', 'admin.root']]);
        if ($notice !== null && $notice !== '') {
            $this->telegram->sendMessage($chatId, $notice);
        }
        $statusText = ((int) ($type['is_active'] ?? 0)) === 1 ? '🟢 فعال' : '🔴 غیرفعال';
        $this->telegram->sendMessage(
            $chatId,
            $this->uiText->multi(new UiTextBlock(
                title: '🧩 <b>جزئیات نوع سرویس</b>',
                lines: [
                    new UiTextLine('🏷', 'نوع', htmlspecialchars((string) ($type['name'] ?? '-')) . " | <code>{$typeId}</code>"),
                    new UiTextLine('📶', 'وضعیت', $statusText),
                    new UiTextLine('📦', 'پکیج‌ها', $lines !== [] ? implode("\n", $lines) : 'برای این نوع هنوز پکیجی ثبت نشده است.'),
                ],
                tipBlockquote: '💡 برای مدیریت هر پکیج، شماره همان پکیج را انتخاب کنید.',
            )),
            $this->uiKeyboard->replyMenu($buttons)
        );
    }

    private function openAdminPackageView(int $chatId, int $userId, int $typeId, int $packageId, ?string $notice = null): void
    {
        $pkg = $this->database->getPackage($packageId);
        $type = $this->findTypeById($typeId);
        $pkgType = $pkg !== null ? (int) ($pkg['type_id'] ?? 0) : 0;
        if ($pkg === null || $type === null || $pkgType !== $typeId) {
            $this->openAdminTypeView($chatId, $userId, $typeId, $this->uiText->warning('پکیج پیدا نشد یا با نوع انتخابی سازگار نیست.'));
            return;
        }

        $pkgInType = $this->findPackageInType($typeId, $packageId);
        $isActive = ((int) (($pkgInType['is_active'] ?? $pkg['is_active'] ?? 0))) === 1;
        $statusText = $isActive ? '🟢 فعال' : '🔴 غیرفعال';
        $this->database->setUserState($userId, 'admin.package.view', ['type_id' => $typeId, 'package_id' => $packageId, 'stack' => ['admin.type.view', 'admin.types.list', 'admin.root']]);
        if ($notice !== null && $notice !== '') {
            $this->telegram->sendMessage($chatId, $notice);
        }
        $this->telegram->sendMessage(
            $chatId,
            $this->uiText->multi(new UiTextBlock(
                title: '📦 <b>جزئیات پکیج</b>',
                lines: [
                    new UiTextLine('🏷', 'نام', '<b>' . htmlspecialchars((string) ($pkg['name'] ?? '-')) . '</b> | <code>' . $packageId . '</code>'),
                    new UiTextLine('🧩', 'نوع', htmlspecialchars((string) ($type['name'] ?? '-')) . ' | <code>' . $typeId . '</code>'),
                    new UiTextLine('💾', 'حجم', (string) ((float) ($pkg['volume_gb'] ?? 0)) . ' GB'),
                    new UiTextLine('⏳', 'مدت', (string) ((int) ($pkg['duration_days'] ?? 0)) . ' روز'),
                    new UiTextLine('💵', 'قیمت', (string) ((int) ($pkg['price'] ?? 0)) . ' تومان'),
                    new UiTextLine('📶', 'وضعیت', $statusText),
                ],
                tipBlockquote: '💡 در این صفحه می‌توانید وضعیت پکیج را تغییر دهید یا آن را حذف کنید.',
            )),
            $this->uiKeyboard->replyMenu([
                [self::ADMIN_PACKAGE_TOGGLE, self::ADMIN_PACKAGE_DELETE],
                [UiLabels::BTN_BACK, UiLabels::BTN_MAIN, UiLabels::BTN_CANCEL],
            ])
        );
    }

    private function findTypeById(int $typeId): ?array
    {
        foreach ($this->database->listTypes() as $type) {
            if ((int) ($type['id'] ?? 0) === $typeId) {
                return $type;
            }
        }

        return null;
    }

    private function findPackageInType(int $typeId, int $packageId): ?array
    {
        foreach ($this->database->listPackagesByType($typeId) as $pkg) {
            if ((int) ($pkg['id'] ?? 0) === $packageId) {
                return $pkg;
            }
        }

        return null;
    }

    private function handleAdminUsersStockState(int $chatId, int $userId, string $text, array $state, array $message): void
    {
        if ($text === UiLabels::BTN_CANCEL || $text === UiLabels::BTN_MAIN || $text === KeyboardBuilder::BTN_BACK_MAIN) {
            $this->openAdminRoot($chatId, $userId);
            return;
        }

        $stateName = (string) ($state['state_name'] ?? '');
        $payload = is_array($state['payload'] ?? null) ? $state['payload'] : [];

        if ($stateName === 'admin.users.list') {
            if ($text === UiLabels::BTN_BACK) {
                $this->openAdminRoot($chatId, $userId);
                return;
            }
            if ($text === self::ADMIN_USERS_REFRESH) {
                $this->openAdminUsersList($chatId, $userId);
                return;
            }
            $options = is_array($payload['options'] ?? null) ? $payload['options'] : [];
            $selected = $this->extractOptionKey($text);
            $targetUid = isset($options[$selected]) ? (int) $options[$selected] : 0;
            if ($targetUid > 0) {
                $this->openAdminUserView($chatId, $userId, $targetUid);
                return;
            }
            $this->telegram->sendMessage($chatId, $this->uiText->warning('گزینه نامعتبر است. لطفاً از لیست کاربران انتخاب کنید.'));
            return;
        }

        if ($stateName === 'admin.user.view') {
            $targetUid = (int) ($payload['target_user_id'] ?? 0);
            if ($targetUid <= 0) {
                $this->openAdminUsersList($chatId, $userId, $this->uiText->warning('کاربر انتخاب‌شده معتبر نبود.'));
                return;
            }
            if ($text === UiLabels::BTN_BACK) {
                $this->openAdminUsersList($chatId, $userId);
                return;
            }
            if ($text === self::ADMIN_USER_TOGGLE_STATUS) {
                $target = $this->database->getUser($targetUid);
                if ($target === null) {
                    $this->openAdminUsersList($chatId, $userId, $this->uiText->warning('کاربر پیدا نشد.'));
                    return;
                }
                $status = (string) ($target['status'] ?? 'unsafe');
                $nextStatus = $status === 'restricted' ? 'unsafe' : 'restricted';
                $this->database->setUserStatus($targetUid, $nextStatus);
                $this->openAdminUserView($chatId, $userId, $targetUid, $this->uiText->success('وضعیت کاربر بروزرسانی شد.'));
                return;
            }
            if ($text === self::ADMIN_USER_TOGGLE_AGENT) {
                $target = $this->database->getUser($targetUid);
                if ($target === null) {
                    $this->openAdminUsersList($chatId, $userId, $this->uiText->warning('کاربر پیدا نشد.'));
                    return;
                }
                $isAgent = ((int) ($target['is_agent'] ?? 0)) === 1;
                $this->database->setUserAgent($targetUid, !$isAgent);
                $this->openAdminUserView($chatId, $userId, $targetUid, $this->uiText->success('وضعیت نمایندگی بروزرسانی شد.'));
                return;
            }
            if ($text === self::ADMIN_USER_BALANCE_ADD || $text === self::ADMIN_USER_BALANCE_SUB) {
                $mode = $text === self::ADMIN_USER_BALANCE_SUB ? 'sub' : 'add';
                $this->database->setUserState($userId, 'admin.user.action', [
                    'target_user_id' => $targetUid,
                    'mode' => $mode,
                    'stack' => ['admin.user.view', 'admin.users.list', 'admin.root'],
                ]);
                $modeText = $mode === 'sub' ? 'کاهش' : 'افزایش';
                $this->telegram->sendMessage(
                    $chatId,
                    $this->uiText->multi(new UiTextBlock(
                        title: "💵 <b>{$modeText} موجودی کاربر</b>",
                        lines: [
                            new UiTextLine('👤', 'کاربر', "<code>{$targetUid}</code>"),
                            new UiTextLine('🔢', 'ورودی', 'مبلغ را به تومان ارسال کنید (فقط عدد).'),
                        ],
                        tipBlockquote: '💡 با دکمه بازگشت می‌توانید به پروفایل کاربر برگردید.',
                    )),
                    $this->uiKeyboard->replyMenu([[UiLabels::BTN_BACK, UiLabels::BTN_MAIN, UiLabels::BTN_CANCEL]])
                );
                return;
            }
            $this->telegram->sendMessage($chatId, $this->uiText->warning('گزینه نامعتبر است. لطفاً از دکمه‌های مدیریت کاربر استفاده کنید.'));
            return;
        }

        if ($stateName === 'admin.user.action') {
            $targetUid = (int) ($payload['target_user_id'] ?? 0);
            if ($targetUid <= 0) {
                $this->openAdminUsersList($chatId, $userId, $this->uiText->warning('کاربر معتبر نبود.'));
                return;
            }
            if ($text === UiLabels::BTN_BACK) {
                $this->openAdminUserView($chatId, $userId, $targetUid);
                return;
            }
            $amount = (int) preg_replace('/\D+/', '', $text);
            if ($amount <= 0) {
                $this->telegram->sendMessage($chatId, $this->uiText->warning('مبلغ معتبر وارد کنید.'));
                return;
            }
            $mode = (string) ($payload['mode'] ?? 'add');
            $delta = $mode === 'sub' ? -$amount : $amount;
            $this->database->updateUserBalance($targetUid, $delta);
            $this->openAdminUserView($chatId, $userId, $targetUid, $this->uiText->success('موجودی کاربر بروزرسانی شد.'));
            return;
        }

        if ($stateName === 'admin.stock.view') {
            $level = (string) ($payload['level'] ?? 'types');
            if ($text === UiLabels::BTN_BACK) {
                if ($level === 'packages') {
                    $this->openAdminStockTypesView($chatId, $userId);
                    return;
                }
                if ($level === 'configs') {
                    $typeId = (int) ($payload['type_id'] ?? 0);
                    $this->openAdminStockPackagesView($chatId, $userId, $typeId);
                    return;
                }
                if ($level === 'config_detail') {
                    $typeId = (int) ($payload['type_id'] ?? 0);
                    $packageId = (int) ($payload['package_id'] ?? 0);
                    $query = (string) ($payload['query'] ?? '');
                    $this->openAdminStockConfigsView($chatId, $userId, $typeId, $packageId, $query);
                    return;
                }
                $this->openAdminRoot($chatId, $userId);
                return;
            }

            if ($level === 'types') {
                if ($text === self::ADMIN_STOCK_REFRESH) {
                    $this->openAdminStockTypesView($chatId, $userId);
                    return;
                }
                $options = is_array($payload['options'] ?? null) ? $payload['options'] : [];
                $selected = $this->extractOptionKey($text);
                $typeId = isset($options[$selected]) ? (int) $options[$selected] : 0;
                if ($typeId > 0) {
                    $this->openAdminStockPackagesView($chatId, $userId, $typeId);
                    return;
                }
                $this->telegram->sendMessage($chatId, $this->uiText->warning('گزینه نامعتبر است. یکی از نوع‌ها را انتخاب کنید.'));
                return;
            }

            if ($level === 'packages') {
                if ($text === self::ADMIN_STOCK_REFRESH) {
                    $typeId = (int) ($payload['type_id'] ?? 0);
                    $this->openAdminStockPackagesView($chatId, $userId, $typeId);
                    return;
                }
                $options = is_array($payload['options'] ?? null) ? $payload['options'] : [];
                $selected = $this->extractOptionKey($text);
                $packageId = isset($options[$selected]) ? (int) $options[$selected] : 0;
                if ($packageId > 0) {
                    $typeId = (int) ($payload['type_id'] ?? 0);
                    $this->openAdminStockConfigsView($chatId, $userId, $typeId, $packageId, '');
                    return;
                }
                $this->telegram->sendMessage($chatId, $this->uiText->warning('گزینه نامعتبر است. یکی از پکیج‌ها را انتخاب کنید.'));
                return;
            }

            if ($level === 'configs') {
                $typeId = (int) ($payload['type_id'] ?? 0);
                $packageId = (int) ($payload['package_id'] ?? 0);
                $query = (string) ($payload['query'] ?? '');
                if ($text === self::ADMIN_STOCK_REFRESH) {
                    $this->openAdminStockConfigsView($chatId, $userId, $typeId, $packageId, $query);
                    return;
                }
                if ($text === self::ADMIN_STOCK_ADD_CONFIG) {
                    $this->database->setUserState($userId, 'admin.stock.update', [
                        'mode' => 'add_config',
                        'type_id' => $typeId,
                        'package_id' => $packageId,
                        'stack' => ['admin.stock.view', 'admin.root'],
                    ]);
                    $this->telegram->sendMessage(
                        $chatId,
                        $this->uiText->multi(new UiTextBlock(
                            title: '➕ <b>افزودن کانفیگ</b>',
                            lines: [
                                new UiTextLine('🧾', 'فرمت', "خط اول: نام سرویس\n---\nخط دوم: متن کانفیگ\n---\n(اختیاری) لینک استعلام"),
                            ],
                            tipBlockquote: '💡 جداکننده بین بخش‌ها باید دقیقاً --- باشد.',
                        )),
                        $this->uiKeyboard->replyMenu([[UiLabels::BTN_BACK, UiLabels::BTN_MAIN, UiLabels::BTN_CANCEL]])
                    );
                    return;
                }
                if ($text === self::ADMIN_STOCK_SEARCH) {
                    $this->database->setUserState($userId, 'admin.stock.update', [
                        'mode' => 'search',
                        'type_id' => $typeId,
                        'package_id' => $packageId,
                        'query' => $query,
                        'stack' => ['admin.stock.view', 'admin.root'],
                    ]);
                    $this->telegram->sendMessage(
                        $chatId,
                        $this->uiText->multi(new UiTextBlock(
                            title: '🔎 <b>جستجو در کانفیگ‌ها</b>',
                            lines: [
                                new UiTextLine('📝', 'ورودی', 'عبارت جستجو را ارسال کنید. برای پاک‌کردن از "-" استفاده کنید.'),
                            ],
                            tipBlockquote: '💡 جستجو روی نام سرویس، متن کانفیگ و لینک استعلام انجام می‌شود.',
                        )),
                        $this->uiKeyboard->replyMenu([[UiLabels::BTN_BACK, UiLabels::BTN_MAIN, UiLabels::BTN_CANCEL]])
                    );
                    return;
                }
                if ($text === self::ADMIN_STOCK_SEARCH_CLEAR) {
                    $this->openAdminStockConfigsView($chatId, $userId, $typeId, $packageId, '');
                    return;
                }
                $options = is_array($payload['options'] ?? null) ? $payload['options'] : [];
                $selected = $this->extractOptionKey($text);
                $configId = isset($options[$selected]) ? (int) $options[$selected] : 0;
                if ($configId > 0) {
                    $this->openAdminStockConfigDetailView($chatId, $userId, $typeId, $packageId, $configId, $query);
                    return;
                }
                $this->telegram->sendMessage($chatId, $this->uiText->warning('گزینه نامعتبر است. از دکمه‌های همین صفحه استفاده کنید.'));
                return;
            }

            if ($level === 'config_detail') {
                $typeId = (int) ($payload['type_id'] ?? 0);
                $packageId = (int) ($payload['package_id'] ?? 0);
                $configId = (int) ($payload['config_id'] ?? 0);
                $query = (string) ($payload['query'] ?? '');
                if ($text === self::ADMIN_STOCK_EXPIRE_TOGGLE) {
                    $cfg = $this->findConfigById($packageId, $configId);
                    if ($cfg === null) {
                        $this->openAdminStockConfigsView($chatId, $userId, $typeId, $packageId, $query, $this->uiText->warning('کانفیگ پیدا نشد.'));
                        return;
                    }
                    $isExpired = ((int) ($cfg['is_expired'] ?? 0)) === 1;
                    if ($isExpired) {
                        $this->database->unexpireConfig($configId);
                    } else {
                        $this->database->expireConfig($configId);
                    }
                    $this->openAdminStockConfigDetailView($chatId, $userId, $typeId, $packageId, $configId, $query, $this->uiText->success('وضعیت انقضا بروزرسانی شد.'));
                    return;
                }
                if ($text === self::ADMIN_STOCK_DELETE_CONFIG) {
                    $this->database->deleteConfig($configId);
                    $this->openAdminStockConfigsView($chatId, $userId, $typeId, $packageId, $query, $this->uiText->success('کانفیگ حذف شد.'));
                    return;
                }
                $this->telegram->sendMessage($chatId, $this->uiText->warning('گزینه نامعتبر است. از دکمه‌های مدیریت کانفیگ استفاده کنید.'));
                return;
            }
        }

        if ($stateName === 'admin.stock.update') {
            $mode = (string) ($payload['mode'] ?? '');
            $typeId = (int) ($payload['type_id'] ?? 0);
            $packageId = (int) ($payload['package_id'] ?? 0);
            if ($text === UiLabels::BTN_BACK) {
                $query = (string) ($payload['query'] ?? '');
                $this->openAdminStockConfigsView($chatId, $userId, $typeId, $packageId, $query);
                return;
            }
            if ($mode === 'search') {
                $query = trim($text);
                if ($query === '-' || $query === '—') {
                    $query = '';
                }
                $this->openAdminStockConfigsView($chatId, $userId, $typeId, $packageId, $query);
                return;
            }
            if ($mode === 'add_config') {
                $raw = trim((string) ($message['text'] ?? ''));
                if ($raw === '' || str_starts_with($raw, '/')) {
                    $this->telegram->sendMessage($chatId, $this->uiText->warning('لطفاً متن کانفیگ را طبق فرمت ارسال کنید.'));
                    return;
                }
                $chunks = preg_split('/\n---\n/', $raw) ?: [];
                if (count($chunks) < 2) {
                    $this->telegram->sendMessage($chatId, $this->uiText->warning('فرمت نامعتبر است. جداکننده --- را رعایت کنید.'));
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
                    $this->telegram->sendMessage($chatId, $this->uiText->warning('نام سرویس یا متن کانفیگ معتبر نیست.'));
                    return;
                }
                $configId = $this->database->addConfig($typeId, $packageId, $serviceName, $configText, $inquiry);
                $this->openAdminStockConfigsView($chatId, $userId, $typeId, $packageId, '', $this->uiText->success("کانفیگ با شناسه <code>{$configId}</code> ثبت شد."));
                return;
            }
        }
    }

    private function openAdminUsersList(int $chatId, int $userId, ?string $notice = null): void
    {
        $users = $this->database->listUsers(30);
        $options = [];
        $lines = [];
        $buttons = [[self::ADMIN_USERS_REFRESH]];
        foreach (array_values($users) as $idx => $u) {
            $num = (string) ($idx + 1);
            $uid = (int) ($u['user_id'] ?? 0);
            if ($uid <= 0) {
                continue;
            }
            $status = (string) ($u['status'] ?? 'unsafe');
            $statusEmoji = $status === 'restricted' ? '⛔️' : '✅';
            $name = trim((string) ($u['full_name'] ?? '—'));
            $balance = (int) ($u['balance'] ?? 0);
            $lines[] = "{$num}) {$statusEmoji} {$name} | U:{$uid} | Bal:{$balance}";
            $options[$num] = $uid;
            $buttons[] = ["{$num} - {$name}"];
        }
        $buttons[] = [UiLabels::BTN_BACK, UiLabels::BTN_MAIN, UiLabels::BTN_CANCEL];
        $this->database->setUserState($userId, 'admin.users.list', ['options' => $options, 'stack' => ['admin.root']]);
        if ($notice !== null && $notice !== '') {
            $this->telegram->sendMessage($chatId, $notice);
        }
        $this->telegram->sendMessage(
            $chatId,
            $this->uiText->multi(new UiTextBlock(
                title: '👥 <b>مدیریت کاربران</b>',
                lines: [
                    new UiTextLine('📋', 'آخرین کاربران', $lines !== [] ? implode("\n", $lines) : 'کاربری یافت نشد.'),
                ],
                tipBlockquote: '💡 یکی از کاربران را انتخاب کنید تا جزئیات و عملیات مدیریتی نمایش داده شود.',
            )),
            $this->uiKeyboard->replyMenu($buttons)
        );
    }

    private function openAdminUserView(int $chatId, int $userId, int $targetUid, ?string $notice = null): void
    {
        $target = $this->database->getUser($targetUid);
        if ($target === null) {
            $this->openAdminUsersList($chatId, $userId, $this->uiText->warning('کاربر پیدا نشد.'));
            return;
        }

        $status = (string) ($target['status'] ?? 'unsafe');
        $statusText = $status === 'restricted' ? '⛔️ محدود' : '✅ فعال';
        $isAgent = ((int) ($target['is_agent'] ?? 0)) === 1;
        $agentText = $isAgent ? '✅ بله' : '❌ خیر';
        if ($notice !== null && $notice !== '') {
            $this->telegram->sendMessage($chatId, $notice);
        }
        $this->database->setUserState($userId, 'admin.user.view', ['target_user_id' => $targetUid, 'stack' => ['admin.users.list', 'admin.root']]);
        $this->telegram->sendMessage(
            $chatId,
            $this->uiText->multi(new UiTextBlock(
                title: '👤 <b>جزئیات کاربر</b>',
                lines: [
                    new UiTextLine('🆔', 'کاربر', "<code>{$targetUid}</code>"),
                    new UiTextLine('📛', 'نام', htmlspecialchars((string) ($target['full_name'] ?? '-'))),
                    new UiTextLine('💰', 'موجودی', (string) ((int) ($target['balance'] ?? 0)) . ' تومان'),
                    new UiTextLine('📶', 'وضعیت', $statusText),
                    new UiTextLine('🤝', 'نماینده', $agentText),
                ],
                tipBlockquote: '💡 تغییرات وضعیت/موجودی فوری اعمال می‌شود.',
            )),
            $this->uiKeyboard->replyMenu([
                [self::ADMIN_USER_TOGGLE_STATUS, self::ADMIN_USER_TOGGLE_AGENT],
                [self::ADMIN_USER_BALANCE_ADD, self::ADMIN_USER_BALANCE_SUB],
                [UiLabels::BTN_BACK, UiLabels::BTN_MAIN, UiLabels::BTN_CANCEL],
            ])
        );
    }

    private function openAdminStockTypesView(int $chatId, int $userId, ?string $notice = null): void
    {
        $types = $this->database->listTypes();
        $options = [];
        $lines = [];
        $buttons = [[self::ADMIN_STOCK_REFRESH]];
        foreach (array_values($types) as $idx => $type) {
            $num = (string) ($idx + 1);
            $typeId = (int) ($type['id'] ?? 0);
            if ($typeId <= 0) {
                continue;
            }
            $name = trim((string) ($type['name'] ?? '—'));
            $lines[] = "{$num}) {$name} | ID: {$typeId}";
            $options[$num] = $typeId;
            $buttons[] = ["{$num} - {$name}"];
        }
        $buttons[] = [UiLabels::BTN_BACK, UiLabels::BTN_MAIN, UiLabels::BTN_CANCEL];
        $this->database->setUserState($userId, 'admin.stock.view', ['level' => 'types', 'options' => $options, 'stack' => ['admin.root']]);
        if ($notice !== null && $notice !== '') {
            $this->telegram->sendMessage($chatId, $notice);
        }
        $this->telegram->sendMessage(
            $chatId,
            $this->uiText->multi(new UiTextBlock(
                title: '📚 <b>موجودی کانفیگ‌ها</b>',
                lines: [
                    new UiTextLine('🧩', 'انتخاب نوع سرویس', $lines !== [] ? implode("\n", $lines) : 'نوع سرویسی برای موجودی یافت نشد.'),
                ],
                tipBlockquote: '💡 ابتدا نوع سرویس را انتخاب کنید تا لیست پکیج‌ها نمایش داده شود.',
            )),
            $this->uiKeyboard->replyMenu($buttons)
        );
    }

    private function openAdminStockPackagesView(int $chatId, int $userId, int $typeId, ?string $notice = null): void
    {
        if ($typeId <= 0) {
            $this->openAdminStockTypesView($chatId, $userId, $this->uiText->warning('نوع سرویس معتبر نبود.'));
            return;
        }
        $packages = $this->database->listPackagesByType($typeId);
        $options = [];
        $lines = [];
        $buttons = [[self::ADMIN_STOCK_REFRESH]];
        foreach (array_values($packages) as $idx => $pkg) {
            $num = (string) ($idx + 1);
            $packageId = (int) ($pkg['id'] ?? 0);
            if ($packageId <= 0) {
                continue;
            }
            $available = $this->database->countAvailableConfigsForPackage($packageId);
            $name = trim((string) ($pkg['name'] ?? 'پکیج'));
            $lines[] = "{$num}) {$name} | P:{$packageId} | موجود: {$available}";
            $options[$num] = $packageId;
            $buttons[] = ["{$num} - {$name}"];
        }
        $buttons[] = [UiLabels::BTN_BACK, UiLabels::BTN_MAIN, UiLabels::BTN_CANCEL];
        $this->database->setUserState($userId, 'admin.stock.view', ['level' => 'packages', 'type_id' => $typeId, 'options' => $options, 'stack' => ['admin.stock.view', 'admin.root']]);
        if ($notice !== null && $notice !== '') {
            $this->telegram->sendMessage($chatId, $notice);
        }
        $this->telegram->sendMessage(
            $chatId,
            $this->uiText->multi(new UiTextBlock(
                title: '📦 <b>پکیج‌های موجودی</b>',
                lines: [
                    new UiTextLine('🧾', 'لیست پکیج‌ها', $lines !== [] ? implode("\n", $lines) : 'برای این نوع پکیجی ثبت نشده است.'),
                ],
                tipBlockquote: '💡 با انتخاب پکیج، لیست کانفیگ‌های موجودی همان پکیج باز می‌شود.',
            )),
            $this->uiKeyboard->replyMenu($buttons)
        );
    }

    private function openAdminStockConfigsView(int $chatId, int $userId, int $typeId, int $packageId, string $query = '', ?string $notice = null): void
    {
        if ($typeId <= 0 || $packageId <= 0) {
            $this->openAdminStockTypesView($chatId, $userId, $this->uiText->warning('نوع/پکیج معتبر نبود.'));
            return;
        }
        $configs = $this->database->listConfigsByPackageFiltered($packageId, 'all', $query !== '' ? $query : null, 20, 0);
        $options = [];
        $lines = [];
        foreach (array_values($configs) as $idx => $cfg) {
            $num = (string) ($idx + 1);
            $configId = (int) ($cfg['id'] ?? 0);
            if ($configId <= 0) {
                continue;
            }
            $soldTo = (int) ($cfg['sold_to'] ?? 0);
            $expired = ((int) ($cfg['is_expired'] ?? 0)) === 1;
            $status = $expired ? '⏳' : ($soldTo > 0 ? '🔴' : '🟢');
            $service = trim((string) ($cfg['service_name'] ?? '-'));
            $lines[] = "{$num}) {$status} {$service} | C:{$configId}";
            $options[$num] = $configId;
        }
        $buttons = [
            [self::ADMIN_STOCK_REFRESH, self::ADMIN_STOCK_ADD_CONFIG],
            [self::ADMIN_STOCK_SEARCH, self::ADMIN_STOCK_SEARCH_CLEAR],
        ];
        foreach (array_keys($options) as $num) {
            $buttons[] = ["{$num} - کانفیگ"];
        }
        $buttons[] = [UiLabels::BTN_BACK, UiLabels::BTN_MAIN, UiLabels::BTN_CANCEL];
        $this->database->setUserState($userId, 'admin.stock.view', [
            'level' => 'configs',
            'type_id' => $typeId,
            'package_id' => $packageId,
            'query' => $query,
            'options' => $options,
            'stack' => ['admin.stock.view', 'admin.root'],
        ]);
        if ($notice !== null && $notice !== '') {
            $this->telegram->sendMessage($chatId, $notice);
        }
        $queryView = $query === '' ? 'ندارد' : '<code>' . htmlspecialchars($query) . '</code>';
        $this->telegram->sendMessage(
            $chatId,
            $this->uiText->multi(new UiTextBlock(
                title: '🗂 <b>لیست کانفیگ‌های پکیج</b>',
                lines: [
                    new UiTextLine('📦', 'پکیج', "<code>{$packageId}</code>"),
                    new UiTextLine('🔎', 'فیلتر جستجو', $queryView),
                    new UiTextLine('📋', 'کانفیگ‌ها', $lines !== [] ? implode("\n", $lines) : 'کانفیگی مطابق فیلتر پیدا نشد.'),
                ],
                tipBlockquote: '💡 برای جزئیات هر کانفیگ، شماره همان مورد را انتخاب کنید.',
            )),
            $this->uiKeyboard->replyMenu($buttons)
        );
    }

    private function openAdminStockConfigDetailView(int $chatId, int $userId, int $typeId, int $packageId, int $configId, string $query = '', ?string $notice = null): void
    {
        $cfg = $this->findConfigById($packageId, $configId);
        if ($cfg === null) {
            $this->openAdminStockConfigsView($chatId, $userId, $typeId, $packageId, $query, $this->uiText->warning('کانفیگ پیدا نشد.'));
            return;
        }
        $soldTo = (int) ($cfg['sold_to'] ?? 0);
        $expired = ((int) ($cfg['is_expired'] ?? 0)) === 1;
        $status = $expired ? '⏳ منقضی' : ($soldTo > 0 ? '🔴 فروخته‌شده' : '🟢 آماده');
        if ($notice !== null && $notice !== '') {
            $this->telegram->sendMessage($chatId, $notice);
        }
        $this->database->setUserState($userId, 'admin.stock.view', [
            'level' => 'config_detail',
            'type_id' => $typeId,
            'package_id' => $packageId,
            'config_id' => $configId,
            'query' => $query,
            'stack' => ['admin.stock.view', 'admin.root'],
        ]);
        $this->telegram->sendMessage(
            $chatId,
            $this->uiText->multi(new UiTextBlock(
                title: '🔍 <b>جزئیات کانفیگ</b>',
                lines: [
                    new UiTextLine('🆔', 'شناسه', "<code>{$configId}</code>"),
                    new UiTextLine('📛', 'سرویس', htmlspecialchars((string) ($cfg['service_name'] ?? '-'))),
                    new UiTextLine('📶', 'وضعیت', $status),
                ],
                tipBlockquote: '💡 حذف کانفیگ غیرقابل بازگشت است.',
            )),
            $this->uiKeyboard->replyMenu([
                [self::ADMIN_STOCK_EXPIRE_TOGGLE, self::ADMIN_STOCK_DELETE_CONFIG],
                [UiLabels::BTN_BACK, UiLabels::BTN_MAIN, UiLabels::BTN_CANCEL],
            ])
        );
    }

    private function findConfigById(int $packageId, int $configId): ?array
    {
        foreach ($this->database->listConfigsByPackage($packageId, 100, 0) as $cfg) {
            if ((int) ($cfg['id'] ?? 0) === $configId) {
                return $cfg;
            }
        }

        return null;
    }

    private function handleAdminPaymentsRequestsState(int $chatId, int $userId, string $text, array $state): void
    {
        if ($text === UiLabels::BTN_CANCEL || $text === UiLabels::BTN_MAIN || $text === KeyboardBuilder::BTN_BACK_MAIN) {
            $this->openAdminRoot($chatId, $userId);
            return;
        }

        $stateName = (string) ($state['state_name'] ?? '');
        $payload = is_array($state['payload'] ?? null) ? $state['payload'] : [];

        if ($stateName === 'admin.payments.list') {
            if ($text === UiLabels::BTN_BACK) {
                $this->openAdminRoot($chatId, $userId);
                return;
            }
            if ($text === self::ADMIN_PAYMENTS_REFRESH) {
                $this->openAdminPaymentsList($chatId, $userId);
                return;
            }
            $options = is_array($payload['options'] ?? null) ? $payload['options'] : [];
            $selected = $this->extractOptionKey($text);
            $paymentId = isset($options[$selected]) ? (int) $options[$selected] : 0;
            if ($paymentId > 0) {
                $this->openAdminPaymentView($chatId, $userId, $paymentId);
                return;
            }
            $this->telegram->sendMessage($chatId, $this->uiText->warning('گزینه نامعتبر است. یکی از پرداخت‌ها را انتخاب کنید.'));
            return;
        }

        if ($stateName === 'admin.payment.view') {
            $paymentId = (int) ($payload['payment_id'] ?? 0);
            if ($paymentId <= 0) {
                $this->openAdminPaymentsList($chatId, $userId, $this->uiText->warning('شناسه پرداخت معتبر نبود.'));
                return;
            }
            if ($text === UiLabels::BTN_BACK) {
                $this->openAdminPaymentsList($chatId, $userId);
                return;
            }
            if ($text === self::ADMIN_PAYMENT_VERIFY_CHAIN) {
                $this->database->setUserState($userId, 'admin.payment.review', [
                    'payment_id' => $paymentId,
                    'action' => 'verify',
                    'stack' => ['admin.payment.view', 'admin.payments.list', 'admin.root'],
                ]);
                $this->processAdminPaymentReview($chatId, $userId, $paymentId, 'verify');
                return;
            }
            if ($text === self::ADMIN_PAYMENT_APPROVE || $text === self::ADMIN_PAYMENT_REJECT) {
                $action = $text === self::ADMIN_PAYMENT_APPROVE ? 'approve' : 'reject';
                $this->database->setUserState($userId, 'admin.payment.review', [
                    'payment_id' => $paymentId,
                    'action' => $action,
                    'stack' => ['admin.payment.view', 'admin.payments.list', 'admin.root'],
                ]);
                $this->processAdminPaymentReview($chatId, $userId, $paymentId, $action);
                return;
            }
            $this->telegram->sendMessage($chatId, $this->uiText->warning('گزینه نامعتبر است. از دکمه‌های بررسی پرداخت استفاده کنید.'));
            return;
        }

        if ($stateName === 'admin.payment.review') {
            $paymentId = (int) ($payload['payment_id'] ?? 0);
            if ($paymentId <= 0) {
                $this->openAdminPaymentsList($chatId, $userId, $this->uiText->warning('پرداخت نامعتبر بود.'));
                return;
            }
            if ($text === UiLabels::BTN_BACK) {
                $this->openAdminPaymentView($chatId, $userId, $paymentId);
                return;
            }
            $this->openAdminPaymentView($chatId, $userId, $paymentId);
            return;
        }

        if ($stateName === 'admin.requests.list') {
            if ($text === UiLabels::BTN_BACK) {
                $this->openAdminRoot($chatId, $userId);
                return;
            }

            $kind = (string) ($payload['kind'] ?? '');
            $status = (string) ($payload['status'] ?? 'pending');
            if ($kind === '') {
                if ($text === self::ADMIN_REQUESTS_FREE) {
                    $this->openAdminRequestsList($chatId, $userId, 'free', 'pending');
                    return;
                }
                if ($text === self::ADMIN_REQUESTS_AGENCY) {
                    $this->openAdminRequestsList($chatId, $userId, 'agency', 'pending');
                    return;
                }
                $this->telegram->sendMessage($chatId, $this->uiText->warning('گزینه نامعتبر است. نوع درخواست را انتخاب کنید.'));
                return;
            }

            if ($text === self::ADMIN_REQUESTS_PENDING) {
                $this->openAdminRequestsList($chatId, $userId, $kind, 'pending');
                return;
            }
            if ($text === self::ADMIN_REQUESTS_APPROVED) {
                $this->openAdminRequestsList($chatId, $userId, $kind, 'approved');
                return;
            }
            if ($text === self::ADMIN_REQUESTS_REJECTED) {
                $this->openAdminRequestsList($chatId, $userId, $kind, 'rejected');
                return;
            }

            $options = is_array($payload['options'] ?? null) ? $payload['options'] : [];
            $selected = $this->extractOptionKey($text);
            $requestId = isset($options[$selected]) ? (int) $options[$selected] : 0;
            if ($requestId > 0) {
                $this->openAdminRequestView($chatId, $userId, $kind, $requestId, $status);
                return;
            }
            $this->telegram->sendMessage($chatId, $this->uiText->warning('گزینه نامعتبر است. یکی از درخواست‌ها را انتخاب کنید.'));
            return;
        }

        if ($stateName === 'admin.request.view') {
            $kind = (string) ($payload['kind'] ?? '');
            $requestId = (int) ($payload['request_id'] ?? 0);
            $status = (string) ($payload['status'] ?? 'pending');
            if ($kind === '' || $requestId <= 0) {
                $this->openAdminRequestsList($chatId, $userId, '', 'pending', $this->uiText->warning('اطلاعات درخواست معتبر نبود.'));
                return;
            }
            if ($text === UiLabels::BTN_BACK) {
                $this->openAdminRequestsList($chatId, $userId, $kind, $status);
                return;
            }
            if ($text === self::ADMIN_REQUEST_APPROVE || $text === self::ADMIN_REQUEST_REJECT) {
                $action = $text === self::ADMIN_REQUEST_APPROVE ? 'approve' : 'reject';
                $this->database->setUserState($userId, 'admin.request.review', [
                    'kind' => $kind,
                    'request_id' => $requestId,
                    'status' => $status,
                    'action' => $action,
                    'stack' => ['admin.request.view', 'admin.requests.list', 'admin.root'],
                ]);
                $actionText = $action === 'approve' ? 'تایید' : 'رد';
                $this->telegram->sendMessage(
                    $chatId,
                    $this->uiText->multi(new UiTextBlock(
                        title: "📝 <b>ثبت یادداشت {$actionText}</b>",
                        lines: [
                            new UiTextLine('🆔', 'شناسه درخواست', "<code>{$requestId}</code>"),
                            new UiTextLine('✍️', 'یادداشت', 'یادداشت ادمین را ارسال کنید. اگر یادداشت ندارید، «-» بفرستید.'),
                        ],
                        tipBlockquote: '💡 یادداشت به کاربر ارسال می‌شود؛ کوتاه و واضح بنویسید.',
                    )),
                    $this->uiKeyboard->replyMenu([[UiLabels::BTN_BACK, UiLabels::BTN_MAIN, UiLabels::BTN_CANCEL]])
                );
                return;
            }
            $this->telegram->sendMessage($chatId, $this->uiText->warning('گزینه نامعتبر است. از دکمه‌های بررسی درخواست استفاده کنید.'));
            return;
        }

        if ($stateName === 'admin.request.review') {
            $kind = (string) ($payload['kind'] ?? '');
            $requestId = (int) ($payload['request_id'] ?? 0);
            $status = (string) ($payload['status'] ?? 'pending');
            $action = (string) ($payload['action'] ?? '');
            if ($kind === '' || $requestId <= 0 || ($action !== 'approve' && $action !== 'reject')) {
                $this->openAdminRequestsList($chatId, $userId, '', 'pending', $this->uiText->warning('اطلاعات بررسی درخواست معتبر نبود.'));
                return;
            }
            if ($text === UiLabels::BTN_BACK) {
                $this->openAdminRequestView($chatId, $userId, $kind, $requestId, $status);
                return;
            }

            $adminNote = trim($text) === '-' ? null : trim($text);
            $approve = $action === 'approve';
            $result = $kind === 'free'
                ? $this->database->reviewFreeTestRequest($requestId, $approve, $adminNote)
                : $this->database->reviewAgencyRequest($requestId, $approve, $adminNote);
            if (!($result['ok'] ?? false)) {
                $msg = (($result['error'] ?? '') === 'already_reviewed')
                    ? 'این درخواست قبلاً بررسی شده است.'
                    : 'ثبت نتیجه بررسی انجام نشد.';
                $this->telegram->sendMessage($chatId, $this->uiText->error($msg));
                $this->openAdminRequestsList($chatId, $userId, $kind, 'pending');
                return;
            }

            $label = $kind === 'free' ? 'درخواست تست رایگان' : 'درخواست نمایندگی';
            $statusText = $approve ? '✅ تایید شد' : '❌ رد شد';
            $this->telegram->sendMessage($chatId, $this->uiText->success("{$label} <code>{$requestId}</code> {$statusText}."));
            $userNotice = $approve
                ? ($kind === 'free' ? "✅ درخواست تست رایگان شما تایید شد." : "✅ درخواست نمایندگی شما تایید شد.")
                : ($kind === 'free' ? "❌ درخواست تست رایگان شما رد شد." : "❌ درخواست نمایندگی شما رد شد.");
            if ($adminNote !== null && $adminNote !== '') {
                $userNotice .= "\n\n📝 توضیح ادمین:\n" . htmlspecialchars($adminNote);
            }
            $this->telegram->sendMessage((int) ($result['user_id'] ?? 0), $userNotice);
            $this->openAdminRequestsList($chatId, $userId, $kind, 'pending');
            return;
        }
    }

    private function openAdminPaymentsList(int $chatId, int $userId, ?string $notice = null): void
    {
        $items = $this->database->listWaitingAdminPayments(30);
        $options = [];
        $lines = [];
        $buttons = [[self::ADMIN_PAYMENTS_REFRESH]];
        foreach (array_values($items) as $idx => $item) {
            $num = (string) ($idx + 1);
            $paymentId = (int) ($item['id'] ?? 0);
            if ($paymentId <= 0) {
                continue;
            }
            $kind = (string) ($item['kind'] ?? '-');
            $uid = (int) ($item['user_id'] ?? 0);
            $amount = (int) ($item['amount'] ?? 0);
            $method = (string) ($item['payment_method'] ?? '-');
            $lines[] = "{$num}) #{$paymentId} | {$kind} | U:{$uid} | {$amount} | {$method}";
            $options[$num] = $paymentId;
            $buttons[] = ["{$num} - پرداخت #{$paymentId}"];
        }
        $buttons[] = [UiLabels::BTN_BACK, UiLabels::BTN_MAIN, UiLabels::BTN_CANCEL];
        $this->database->setUserState($userId, 'admin.payments.list', ['options' => $options, 'stack' => ['admin.root']]);
        if ($notice !== null && $notice !== '') {
            $this->telegram->sendMessage($chatId, $notice);
        }
        $this->telegram->sendMessage(
            $chatId,
            $this->uiText->multi(new UiTextBlock(
                title: '💳 <b>مدیریت پرداخت‌ها</b>',
                lines: [
                    new UiTextLine('📋', 'پرداخت‌های در انتظار', $lines !== [] ? implode("\n", $lines) : 'پرداختی در انتظار بررسی نیست.'),
                ],
                tipBlockquote: '💡 یک پرداخت را انتخاب کنید تا عملیات بررسی/تایید/رد انجام شود.',
            )),
            $this->uiKeyboard->replyMenu($buttons)
        );
    }

    private function openAdminPaymentView(int $chatId, int $userId, int $paymentId, ?string $notice = null): void
    {
        $payment = $this->database->getPaymentById($paymentId);
        if ($payment === null) {
            $this->openAdminPaymentsList($chatId, $userId, $this->uiText->warning('پرداخت پیدا نشد.'));
            return;
        }
        if ($notice !== null && $notice !== '') {
            $this->telegram->sendMessage($chatId, $notice);
        }

        $status = (string) ($payment['status'] ?? '-');
        $method = (string) ($payment['payment_method'] ?? '-');
        $buttons = [];
        if (str_starts_with($method, 'crypto:')) {
            $buttons[] = [self::ADMIN_PAYMENT_VERIFY_CHAIN];
        }
        if ($status === 'waiting_admin') {
            $buttons[] = [self::ADMIN_PAYMENT_APPROVE, self::ADMIN_PAYMENT_REJECT];
        }
        $buttons[] = [UiLabels::BTN_BACK, UiLabels::BTN_MAIN, UiLabels::BTN_CANCEL];

        $this->database->setUserState($userId, 'admin.payment.view', ['payment_id' => $paymentId, 'stack' => ['admin.payments.list', 'admin.root']]);
        $this->telegram->sendMessage(
            $chatId,
            $this->uiText->multi(new UiTextBlock(
                title: '💳 <b>جزئیات پرداخت</b>',
                lines: [
                    new UiTextLine('🆔', 'شناسه', "<code>{$paymentId}</code>"),
                    new UiTextLine('👤', 'کاربر', '<code>' . (int) ($payment['user_id'] ?? 0) . '</code>'),
                    new UiTextLine('💵', 'مبلغ', (string) ((int) ($payment['amount'] ?? 0)) . ' تومان'),
                    new UiTextLine('🧾', 'روش', htmlspecialchars($method)),
                    new UiTextLine('📶', 'وضعیت', htmlspecialchars($status)),
                ],
                tipBlockquote: '💡 ابتدا وضعیت و روش پرداخت را بررسی کنید، سپس تایید یا رد انجام دهید.',
            )),
            $this->uiKeyboard->replyMenu($buttons)
        );
    }

    private function processAdminPaymentReview(int $chatId, int $userId, int $paymentId, string $action): void
    {
        if ($action === 'verify') {
            $attempt = $this->database->registerVerifyAttempt($paymentId);
            if (!(bool) ($attempt['ok'] ?? false)) {
                $error = (string) ($attempt['error'] ?? '');
                $message = match ($error) {
                    'cooldown' => '⏳ لطفاً چند ثانیه بعد دوباره بررسی کنید.',
                    'max_attempts' => '🚫 سقف دفعات بررسی این پرداخت تکمیل شده است.',
                    default => 'بررسی این پرداخت فعلاً ممکن نیست.',
                };
                $this->openAdminPaymentView($chatId, $userId, $paymentId, $this->uiText->warning($message));
                return;
            }

            $payment = $this->database->getPaymentById($paymentId);
            if ($payment === null) {
                $this->openAdminPaymentsList($chatId, $userId, $this->uiText->warning('پرداخت یافت نشد.'));
                return;
            }
            $pm = (string) ($payment['payment_method'] ?? '');
            if (!str_starts_with($pm, 'crypto:')) {
                $this->openAdminPaymentView($chatId, $userId, $paymentId, $this->uiText->warning('این پرداخت کریپتو نیست.'));
                return;
            }
            $coin = trim(substr($pm, strlen('crypto:')));
            $txHash = trim((string) ($payment['tx_hash'] ?? ''));
            $claimedCoin = isset($payment['crypto_amount_claimed']) ? (float) $payment['crypto_amount_claimed'] : null;
            if ($txHash === '') {
                $this->openAdminPaymentView($chatId, $userId, $paymentId, $this->uiText->warning('TX Hash ثبت نشده است.'));
                return;
            }

            $verify = $this->gateways->verifyCryptoTransaction($coin, $txHash);
            $effectivePaidCoin = $this->gateways->resolveEffectivePaidAmount($verify, $claimedCoin);
            $amountCheck = $this->gateways->validateClaimedAmount($coin, (int) ($payment['amount'] ?? 0), $effectivePaidCoin);
            $this->database->setPaymentProviderPayload($paymentId, [
                'source' => 'crypto_verify',
                'response' => $verify,
                'effective_paid_coin' => $effectivePaidCoin,
                'amount_check' => $amountCheck,
            ]);

            $chainConfirmed = (($verify['ok'] ?? false) && ($verify['confirmed'] ?? false));
            $amountMatched = (($amountCheck['ok'] ?? false) && ($amountCheck['amount_match'] ?? false));
            $canApprove = $chainConfirmed || (($verify['error'] ?? '') === 'coin_not_supported_yet' && $amountMatched);
            if ($canApprove) {
                $result = $this->database->applyAdminPaymentDecision($paymentId, true);
                if ($result['ok'] ?? false) {
                    $this->notifyPaymentDecision((int) ($result['user_id'] ?? 0), (string) ($result['kind'] ?? ''), (int) ($result['amount'] ?? 0), true);
                    $this->openAdminPaymentView($chatId, $userId, $paymentId, $this->uiText->success('پرداخت کریپتو تایید شد و سفارش در صف تحویل قرار گرفت.'));
                    return;
                }
            }
            $this->openAdminPaymentView($chatId, $userId, $paymentId, $this->uiText->warning('تراکنش تایید نشد یا مقدار اعلامی معتبر نیست.'));
            return;
        }

        $approve = $action === 'approve';
        $result = $this->database->applyAdminPaymentDecision($paymentId, $approve);
        if (!($result['ok'] ?? false)) {
            $this->openAdminPaymentView($chatId, $userId, $paymentId, $this->uiText->warning('این درخواست قابل پردازش نیست.'));
            return;
        }
        $this->notifyPaymentDecision((int) ($result['user_id'] ?? 0), (string) ($result['kind'] ?? ''), (int) ($result['amount'] ?? 0), $approve);
        $statusText = $approve ? '✅ تایید شد' : '❌ رد شد';
        $this->openAdminPaymentsList($chatId, $userId, $this->uiText->success("درخواست <code>{$paymentId}</code> {$statusText}."));
    }

    private function notifyPaymentDecision(int $targetUserId, string $kind, int $amount, bool $approve): void
    {
        if ($targetUserId <= 0) {
            return;
        }
        if ($kind === 'wallet_charge') {
            $userNotice = $approve
                ? "✅ درخواست شارژ کیف پول شما تایید شد.\nمبلغ: <b>{$amount}</b> تومان"
                : "❌ درخواست شارژ کیف پول شما رد شد.";
        } elseif ($kind === 'renewal') {
            $userNotice = $approve
                ? "✅ پرداخت تمدید شما تایید شد و در صف تحویل قرار گرفت."
                : "❌ پرداخت تمدید شما رد شد.";
        } else {
            $userNotice = $approve
                ? "✅ پرداخت سفارش شما تایید شد و در صف تحویل قرار گرفت."
                : "❌ پرداخت سفارش شما رد شد.";
        }
        $this->telegram->sendMessage($targetUserId, $userNotice);
    }

    private function openAdminRequestsList(int $chatId, int $userId, string $kind = '', string $status = 'pending', ?string $notice = null): void
    {
        if ($notice !== null && $notice !== '') {
            $this->telegram->sendMessage($chatId, $notice);
        }
        if ($kind === '') {
            $this->database->setUserState($userId, 'admin.requests.list', ['kind' => '', 'stack' => ['admin.root']]);
            $this->telegram->sendMessage(
                $chatId,
                $this->uiText->multi(new UiTextBlock(
                    title: '🗂 <b>مدیریت درخواست‌ها</b>',
                    lines: [
                        new UiTextLine('📌', 'مرحله اول', 'نوع درخواست را انتخاب کنید.'),
                    ],
                    tipBlockquote: '💡 بعد از انتخاب نوع، می‌توانید وضعیت pending/approved/rejected را فیلتر کنید.',
                )),
                $this->uiKeyboard->replyMenu([
                    [self::ADMIN_REQUESTS_FREE],
                    [self::ADMIN_REQUESTS_AGENCY],
                    [UiLabels::BTN_BACK, UiLabels::BTN_MAIN, UiLabels::BTN_CANCEL],
                ])
            );
            return;
        }

        $items = $kind === 'free'
            ? $this->database->listFreeTestRequestsByStatus($status, 20, 0)
            : $this->database->listAgencyRequestsByStatus($status, 20, 0);
        $options = [];
        $lines = [];
        $buttons = [[self::ADMIN_REQUESTS_PENDING, self::ADMIN_REQUESTS_APPROVED, self::ADMIN_REQUESTS_REJECTED]];
        foreach (array_values($items) as $idx => $item) {
            $num = (string) ($idx + 1);
            $requestId = (int) ($item['id'] ?? 0);
            if ($requestId <= 0) {
                continue;
            }
            $uid = (int) ($item['user_id'] ?? 0);
            $created = (string) ($item['created_at'] ?? '-');
            $lines[] = "{$num}) #{$requestId} | U:{$uid} | {$created}";
            $options[$num] = $requestId;
            $buttons[] = ["{$num} - درخواست #{$requestId}"];
        }
        $buttons[] = [UiLabels::BTN_BACK, UiLabels::BTN_MAIN, UiLabels::BTN_CANCEL];
        $this->database->setUserState($userId, 'admin.requests.list', [
            'kind' => $kind,
            'status' => $status,
            'options' => $options,
            'stack' => ['admin.root'],
        ]);
        $kindTitle = $kind === 'free' ? 'تست رایگان' : 'نمایندگی';
        $this->telegram->sendMessage(
            $chatId,
            $this->uiText->multi(new UiTextBlock(
                title: "🗂 <b>درخواست‌های {$kindTitle}</b>",
                lines: [
                    new UiTextLine('📶', 'فیلتر وضعیت', htmlspecialchars($status)),
                    new UiTextLine('📋', 'لیست درخواست‌ها', $lines !== [] ? implode("\n", $lines) : 'درخواستی برای این فیلتر یافت نشد.'),
                ],
                tipBlockquote: '💡 با تغییر فیلتر وضعیت می‌توانید سوابق تایید/رد را هم بررسی کنید.',
            )),
            $this->uiKeyboard->replyMenu($buttons)
        );
    }

    private function openAdminRequestView(int $chatId, int $userId, string $kind, int $requestId, string $backStatus = 'pending', ?string $notice = null): void
    {
        $request = $kind === 'free'
            ? $this->database->getFreeTestRequestById($requestId)
            : $this->database->getAgencyRequestById($requestId);
        if ($request === null) {
            $this->openAdminRequestsList($chatId, $userId, $kind, $backStatus, $this->uiText->warning('درخواست پیدا نشد.'));
            return;
        }
        if ($notice !== null && $notice !== '') {
            $this->telegram->sendMessage($chatId, $notice);
        }
        $status = (string) ($request['status'] ?? 'pending');
        $statusText = $status === 'approved' ? '✅ approved' : ($status === 'rejected' ? '❌ rejected' : '⏳ pending');
        $kindTitle = $kind === 'free' ? 'درخواست تست رایگان' : 'درخواست نمایندگی';
        $buttons = [];
        if ($status === 'pending') {
            $buttons[] = [self::ADMIN_REQUEST_APPROVE, self::ADMIN_REQUEST_REJECT];
        }
        $buttons[] = [UiLabels::BTN_BACK, UiLabels::BTN_MAIN, UiLabels::BTN_CANCEL];
        $this->database->setUserState($userId, 'admin.request.view', [
            'kind' => $kind,
            'request_id' => $requestId,
            'status' => $backStatus,
            'stack' => ['admin.requests.list', 'admin.root'],
        ]);
        $this->telegram->sendMessage(
            $chatId,
            $this->uiText->multi(new UiTextBlock(
                title: "📝 <b>{$kindTitle}</b>",
                lines: [
                    new UiTextLine('🆔', 'شناسه', "<code>{$requestId}</code>"),
                    new UiTextLine('👤', 'کاربر', '<code>' . (int) ($request['user_id'] ?? 0) . '</code>'),
                    new UiTextLine('📶', 'وضعیت', $statusText),
                    new UiTextLine('🕒', 'زمان ثبت', htmlspecialchars((string) ($request['created_at'] ?? '-'))),
                    new UiTextLine('🧾', 'متن درخواست', htmlspecialchars((string) ($request['note'] ?? ''))),
                ],
                tipBlockquote: '💡 قبل از تایید یا رد، متن درخواست را کامل بررسی کنید.',
            )),
            $this->uiKeyboard->replyMenu($buttons)
        );
    }

    private function handleAdminSettingsAdminsPinsState(int $chatId, int $userId, string $text, array $state): void
    {
        if ($text === UiLabels::BTN_CANCEL || $text === UiLabels::BTN_MAIN || $text === KeyboardBuilder::BTN_BACK_MAIN) {
            $this->openAdminRoot($chatId, $userId);
            return;
        }
        $stateName = (string) ($state['state_name'] ?? '');
        $payload = is_array($state['payload'] ?? null) ? $state['payload'] : [];

        if ($stateName === 'admin.settings.view') {
            if ($text === UiLabels::BTN_BACK) {
                $this->openAdminRoot($chatId, $userId);
                return;
            }
            $toggleMap = [
                self::ADMIN_SETTINGS_TOGGLE_FREE_TEST => 'free_test_enabled',
                self::ADMIN_SETTINGS_TOGGLE_AGENCY => 'agency_request_enabled',
                self::ADMIN_SETTINGS_TOGGLE_GW_CARD => 'gw_card_enabled',
                self::ADMIN_SETTINGS_TOGGLE_GW_CRYPTO => 'gw_crypto_enabled',
                self::ADMIN_SETTINGS_TOGGLE_GW_TETRA => 'gw_tetrapay_enabled',
            ];
            if ($text === self::ADMIN_SETTINGS_REFRESH) {
                $this->openAdminSettingsView($chatId, $userId);
                return;
            }
            if ($text === self::ADMIN_SETTINGS_TOGGLE_BOT) {
                $cur = $this->settings->get('bot_status', 'on');
                $next = $cur === 'on' ? 'update' : ($cur === 'update' ? 'off' : 'on');
                $this->settings->set('bot_status', $next);
                $this->openAdminSettingsView($chatId, $userId, $this->uiText->success('وضعیت ربات بروزرسانی شد.'));
                return;
            }
            if (isset($toggleMap[$text])) {
                $key = $toggleMap[$text];
                $current = $this->settings->get($key, '0');
                $this->settings->set($key, $current === '1' ? '0' : '1');
                $this->openAdminSettingsView($chatId, $userId, $this->uiText->success('تنظیم بروزرسانی شد.'));
                return;
            }
            if ($text === self::ADMIN_SETTINGS_SET_CHANNEL) {
                $this->database->setUserState($userId, 'admin.settings.edit', ['mode' => 'channel', 'stack' => ['admin.settings.view', 'admin.root']]);
                $this->telegram->sendMessage(
                    $chatId,
                    $this->uiText->multi(new UiTextBlock(
                        title: '📢 <b>تنظیم کانال قفل</b>',
                        lines: [new UiTextLine('🧾', 'ورودی', 'آیدی کانال را بفرستید (@channel یا -100...). برای غیرفعال‌سازی «-» ارسال کنید.')],
                        tipBlockquote: '💡 کانال قفل روی شروع کاربرها اثر مستقیم دارد؛ مقدار را دقیق ثبت کنید.',
                    )),
                    $this->uiKeyboard->replyMenu([[UiLabels::BTN_BACK, UiLabels::BTN_MAIN, UiLabels::BTN_CANCEL]])
                );
                return;
            }
            if ($text === self::ADMIN_SETTINGS_EDIT) {
                $this->database->setUserState($userId, 'admin.settings.edit', ['mode' => 'kv', 'stack' => ['admin.settings.view', 'admin.root']]);
                $this->telegram->sendMessage(
                    $chatId,
                    $this->uiText->multi(new UiTextBlock(
                        title: '✏️ <b>ویرایش دستی تنظیم</b>',
                        lines: [new UiTextLine('🧾', 'فرمت', '<code>key|value</code>')],
                        tipBlockquote: '💡 برای کلیدهای حساس مقدار معتبر وارد کنید.',
                    )),
                    $this->uiKeyboard->replyMenu([[UiLabels::BTN_BACK, UiLabels::BTN_MAIN, UiLabels::BTN_CANCEL]])
                );
                return;
            }
        }

        if ($stateName === 'admin.settings.edit') {
            $mode = (string) ($payload['mode'] ?? 'kv');
            if ($text === UiLabels::BTN_BACK) {
                $this->openAdminSettingsView($chatId, $userId);
                return;
            }
            if ($text === '' || str_starts_with($text, '/')) {
                $this->telegram->sendMessage($chatId, $this->uiText->warning('ورودی معتبر ارسال کنید.'));
                return;
            }
            if ($mode === 'channel') {
                $value = trim($text);
                if ($value === '-' || $value === '—') {
                    $value = '';
                }
                $this->settings->set('channel_id', $value);
                $this->openAdminSettingsView($chatId, $userId, $this->uiText->success('کانال قفل بروزرسانی شد.'));
                return;
            }
            $parts = array_map('trim', explode('|', $text, 2));
            $key = (string) ($parts[0] ?? '');
            $value = (string) ($parts[1] ?? '');
            if ($key === '' || count($parts) < 2) {
                $this->telegram->sendMessage($chatId, $this->uiText->warning('فرمت باید key|value باشد.'));
                return;
            }
            $this->settings->set($key, $value);
            $this->openAdminSettingsView($chatId, $userId, $this->uiText->success('تنظیم ذخیره شد.'));
            return;
        }

        if ($stateName === 'admin.admins.list') {
            if ($text === UiLabels::BTN_BACK) {
                $this->openAdminRoot($chatId, $userId);
                return;
            }
            if ($text === self::ADMIN_ADMINS_ADD) {
                $this->database->setUserState($userId, 'admin.admin.create', ['stack' => ['admin.admins.list', 'admin.root']]);
                $this->telegram->sendMessage($chatId, $this->uiText->multi(new UiTextBlock(
                    title: '➕ <b>افزودن ادمین</b>',
                    lines: [new UiTextLine('🆔', 'راهنما', 'آیدی عددی ادمین جدید را ارسال کنید.')],
                    tipBlockquote: '💡 سطح دسترسی اولیه با سطح‌های پایه ایجاد می‌شود و قابل ویرایش است.',
                )), $this->uiKeyboard->replyMenu([[UiLabels::BTN_BACK, UiLabels::BTN_MAIN, UiLabels::BTN_CANCEL]]));
                return;
            }
            $options = is_array($payload['options'] ?? null) ? $payload['options'] : [];
            $selected = $this->extractOptionKey($text);
            $targetUid = isset($options[$selected]) ? (int) $options[$selected] : 0;
            if ($targetUid > 0) {
                $this->openAdminAdminView($chatId, $userId, $targetUid);
                return;
            }
        }

        if ($stateName === 'admin.admin.create') {
            if ($text === UiLabels::BTN_BACK) {
                $this->openAdminAdminsList($chatId, $userId);
                return;
            }
            $targetUid = (int) preg_replace('/\D+/', '', $text);
            if ($targetUid <= 0) {
                $this->telegram->sendMessage($chatId, $this->uiText->warning('آیدی معتبر ارسال کنید.'));
                return;
            }
            $this->database->upsertAdminUser($targetUid, $userId, [
                'types' => true, 'stock' => true, 'users' => true, 'settings' => true, 'payments' => true, 'requests' => true, 'broadcast' => false, 'agents' => false, 'panels' => false,
            ]);
            $this->openAdminAdminView($chatId, $userId, $targetUid, $this->uiText->success('ادمین جدید ایجاد شد.'));
            return;
        }

        if ($stateName === 'admin.admin.view') {
            $targetUid = (int) ($payload['target_user_id'] ?? 0);
            if ($targetUid <= 0) {
                $this->openAdminAdminsList($chatId, $userId, $this->uiText->warning('ادمین معتبر نبود.'));
                return;
            }
            if ($text === UiLabels::BTN_BACK) {
                $this->openAdminAdminsList($chatId, $userId);
                return;
            }
            if ($text === self::ADMIN_ADMIN_DELETE) {
                $this->database->setUserState($userId, 'admin.admin.delete', ['target_user_id' => $targetUid, 'stack' => ['admin.admin.view', 'admin.admins.list', 'admin.root']]);
                $this->telegram->sendMessage($chatId, $this->uiText->warning("برای حذف ادمین <code>{$targetUid}</code>، عبارت «حذف» را ارسال کنید."), $this->uiKeyboard->replyMenu([[UiLabels::BTN_BACK, UiLabels::BTN_MAIN, UiLabels::BTN_CANCEL]]));
                return;
            }
            $permMap = is_array($payload['perm_labels'] ?? null) ? $payload['perm_labels'] : [];
            $permKey = $permMap[$text] ?? '';
            if ($permKey !== '') {
                $perms = $this->database->getAdminPermissions($targetUid);
                $perms[$permKey] = !((bool) ($perms[$permKey] ?? false));
                $this->database->upsertAdminUser($targetUid, $userId, $perms);
                $this->openAdminAdminView($chatId, $userId, $targetUid, $this->uiText->success('دسترسی بروزرسانی شد.'));
                return;
            }
        }

        if ($stateName === 'admin.admin.delete') {
            $targetUid = (int) ($payload['target_user_id'] ?? 0);
            if ($text === UiLabels::BTN_BACK) {
                $this->openAdminAdminView($chatId, $userId, $targetUid);
                return;
            }
            if ($targetUid > 0 && trim($text) === 'حذف' && !in_array($targetUid, Config::adminIds(), true)) {
                $this->database->removeAdminUser($targetUid);
                $this->openAdminAdminsList($chatId, $userId, $this->uiText->success('ادمین حذف شد.'));
                return;
            }
            $this->telegram->sendMessage($chatId, $this->uiText->warning('برای تایید حذف، عبارت «حذف» را ارسال کنید.'));
            return;
        }

        if ($stateName === 'admin.pins.list') {
            if ($text === UiLabels::BTN_BACK) {
                $this->openAdminRoot($chatId, $userId);
                return;
            }
            if ($text === self::ADMIN_PINS_ADD) {
                $this->database->setUserState($userId, 'admin.pin.create', ['stack' => ['admin.pins.list', 'admin.root']]);
                $this->telegram->sendMessage($chatId, $this->uiText->info('متن پیام پین را ارسال کنید.'), $this->uiKeyboard->replyMenu([[UiLabels::BTN_BACK, UiLabels::BTN_MAIN, UiLabels::BTN_CANCEL]]));
                return;
            }
            $options = is_array($payload['options'] ?? null) ? $payload['options'] : [];
            $selected = $this->extractOptionKey($text);
            $pinId = isset($options[$selected]) ? (int) $options[$selected] : 0;
            if ($pinId > 0) {
                $this->openAdminPinView($chatId, $userId, $pinId);
                return;
            }
        }

        if ($stateName === 'admin.pin.create') {
            if ($text === UiLabels::BTN_BACK) {
                $this->openAdminPinsList($chatId, $userId);
                return;
            }
            if (trim($text) === '' || str_starts_with($text, '/')) {
                $this->telegram->sendMessage($chatId, $this->uiText->warning('متن پیام پین نمی‌تواند خالی باشد.'));
                return;
            }
            $pinId = $this->database->addPinnedMessage($text);
            $this->openAdminPinView($chatId, $userId, $pinId, $this->uiText->success('پیام پین ثبت شد.'));
            return;
        }

        if ($stateName === 'admin.pin.view') {
            $pinId = (int) ($payload['pin_id'] ?? 0);
            if ($pinId <= 0) {
                $this->openAdminPinsList($chatId, $userId, $this->uiText->warning('پیام پین معتبر نبود.'));
                return;
            }
            if ($text === UiLabels::BTN_BACK) {
                $this->openAdminPinsList($chatId, $userId);
                return;
            }
            if ($text === self::ADMIN_PIN_EDIT) {
                $this->database->setUserState($userId, 'admin.pin.edit', ['pin_id' => $pinId, 'stack' => ['admin.pin.view', 'admin.pins.list', 'admin.root']]);
                $this->telegram->sendMessage($chatId, $this->uiText->info('متن جدید پیام پین را ارسال کنید.'), $this->uiKeyboard->replyMenu([[UiLabels::BTN_BACK, UiLabels::BTN_MAIN, UiLabels::BTN_CANCEL]]));
                return;
            }
            if ($text === self::ADMIN_PIN_DELETE) {
                $this->database->setUserState($userId, 'admin.pin.delete', ['pin_id' => $pinId, 'stack' => ['admin.pin.view', 'admin.pins.list', 'admin.root']]);
                $this->telegram->sendMessage($chatId, $this->uiText->warning("برای حذف پیام پین #{$pinId} عبارت «حذف» را ارسال کنید."), $this->uiKeyboard->replyMenu([[UiLabels::BTN_BACK, UiLabels::BTN_MAIN, UiLabels::BTN_CANCEL]]));
                return;
            }
            if ($text === self::ADMIN_PIN_SEND_ALL) {
                $this->database->setUserState($userId, 'admin.pin.send', ['pin_id' => $pinId, 'stack' => ['admin.pin.view', 'admin.pins.list', 'admin.root']]);
                $this->telegram->sendMessage($chatId, $this->uiText->warning("برای ارسال و پین همگانی #{$pinId} عبارت «ارسال» را ارسال کنید."), $this->uiKeyboard->replyMenu([[UiLabels::BTN_BACK, UiLabels::BTN_MAIN, UiLabels::BTN_CANCEL]]));
                return;
            }
        }

        if ($stateName === 'admin.pin.edit') {
            $pinId = (int) ($payload['pin_id'] ?? 0);
            if ($text === UiLabels::BTN_BACK) {
                $this->openAdminPinView($chatId, $userId, $pinId);
                return;
            }
            if (trim($text) === '' || str_starts_with($text, '/')) {
                $this->telegram->sendMessage($chatId, $this->uiText->warning('متن معتبر ارسال کنید.'));
                return;
            }
            $this->database->updatePinnedMessage($pinId, $text);
            $this->openAdminPinView($chatId, $userId, $pinId, $this->uiText->success('پیام پین ویرایش شد.'));
            return;
        }

        if ($stateName === 'admin.pin.delete') {
            $pinId = (int) ($payload['pin_id'] ?? 0);
            if ($text === UiLabels::BTN_BACK) {
                $this->openAdminPinView($chatId, $userId, $pinId);
                return;
            }
            if (trim($text) === 'حذف') {
                $this->database->deletePinnedMessage($pinId);
                $this->openAdminPinsList($chatId, $userId, $this->uiText->success('پیام پین حذف شد.'));
                return;
            }
            $this->telegram->sendMessage($chatId, $this->uiText->warning('برای تایید حذف عبارت «حذف» را ارسال کنید.'));
            return;
        }

        if ($stateName === 'admin.pin.send') {
            $pinId = (int) ($payload['pin_id'] ?? 0);
            if ($text === UiLabels::BTN_BACK) {
                $this->openAdminPinView($chatId, $userId, $pinId);
                return;
            }
            if (trim($text) !== 'ارسال') {
                $this->telegram->sendMessage($chatId, $this->uiText->warning('برای تایید ارسال همگانی عبارت «ارسال» را بفرستید.'));
                return;
            }
            $pin = $this->database->getPinnedMessage($pinId);
            if ($pin === null) {
                $this->openAdminPinsList($chatId, $userId, $this->uiText->warning('پیام پین پیدا نشد.'));
                return;
            }
            $sent = 0;
            $pinned = 0;
            foreach ($this->database->listUserIdsForBroadcast('all') as $targetId) {
                if ($targetId <= 0) {
                    continue;
                }
                try {
                    $result = $this->telegram->sendMessageWithResult($targetId, (string) ($pin['text'] ?? ''));
                    if (is_array($result)) {
                        $sent++;
                        $msgId = (int) ($result['message_id'] ?? 0);
                        if ($msgId > 0) {
                            $this->database->savePinnedSend($pinId, $targetId, $msgId);
                            $this->telegram->pinChatMessage($targetId, $msgId, true);
                            $pinned++;
                        }
                    }
                } catch (\Throwable $e) {
                }
            }
            $this->openAdminPinView($chatId, $userId, $pinId, $this->uiText->success("ارسال انجام شد. ارسال: <b>{$sent}</b> | پین: <b>{$pinned}</b>"));
            return;
        }

        $this->telegram->sendMessage($chatId, $this->uiText->warning('گزینه نامعتبر است. لطفاً از دکمه‌های همین بخش استفاده کنید.'));
    }

    private function openAdminSettingsView(int $chatId, int $userId, ?string $notice = null): void
    {
        $vals = [
            'bot_status' => $this->settings->get('bot_status', 'on'),
            'free_test_enabled' => $this->settings->get('free_test_enabled', '1'),
            'agency_request_enabled' => $this->settings->get('agency_request_enabled', '1'),
            'gw_card_enabled' => $this->settings->get('gw_card_enabled', '0'),
            'gw_crypto_enabled' => $this->settings->get('gw_crypto_enabled', '0'),
            'gw_tetrapay_enabled' => $this->settings->get('gw_tetrapay_enabled', '0'),
            'channel_id' => trim($this->settings->get('channel_id', '')),
        ];
        if ($notice !== null && $notice !== '') {
            $this->telegram->sendMessage($chatId, $notice);
        }
        $this->database->setUserState($userId, 'admin.settings.view', ['stack' => ['admin.root']]);
        $this->telegram->sendMessage($chatId, $this->uiText->multi(new UiTextBlock(
            title: '⚙️ <b>تنظیمات سریع</b>',
            lines: [
                new UiTextLine('🤖', 'bot_status', htmlspecialchars($vals['bot_status'])),
                new UiTextLine('🎁', 'free_test_enabled', $vals['free_test_enabled'] === '1' ? '✅' : '❌'),
                new UiTextLine('🤝', 'agency_request_enabled', $vals['agency_request_enabled'] === '1' ? '✅' : '❌'),
                new UiTextLine('💳', 'gw_card_enabled', $vals['gw_card_enabled'] === '1' ? '✅' : '❌'),
                new UiTextLine('💎', 'gw_crypto_enabled', $vals['gw_crypto_enabled'] === '1' ? '✅' : '❌'),
                new UiTextLine('🏦', 'gw_tetrapay_enabled', $vals['gw_tetrapay_enabled'] === '1' ? '✅' : '❌'),
                new UiTextLine('📢', 'channel_id', $vals['channel_id'] !== '' ? htmlspecialchars($vals['channel_id']) : '❌ تنظیم نشده'),
            ],
            tipBlockquote: '💡 تغییرات این صفحه فوری اعمال می‌شوند.',
        )), $this->uiKeyboard->replyMenu([
            [self::ADMIN_SETTINGS_REFRESH, self::ADMIN_SETTINGS_EDIT],
            [self::ADMIN_SETTINGS_TOGGLE_BOT, self::ADMIN_SETTINGS_SET_CHANNEL],
            [self::ADMIN_SETTINGS_TOGGLE_FREE_TEST, self::ADMIN_SETTINGS_TOGGLE_AGENCY],
            [self::ADMIN_SETTINGS_TOGGLE_GW_CARD, self::ADMIN_SETTINGS_TOGGLE_GW_CRYPTO, self::ADMIN_SETTINGS_TOGGLE_GW_TETRA],
            [UiLabels::BTN_BACK, UiLabels::BTN_MAIN, UiLabels::BTN_CANCEL],
        ]));
    }

    private function openAdminAdminsList(int $chatId, int $userId, ?string $notice = null): void
    {
        $options = [];
        $lines = [];
        $buttons = [[self::ADMIN_ADMINS_ADD]];
        foreach (array_values($this->database->listAdminUsers()) as $idx => $adm) {
            $num = (string) ($idx + 1);
            $uid = (int) ($adm['user_id'] ?? 0);
            if ($uid <= 0) {
                continue;
            }
            $lines[] = "{$num}) U:{$uid}";
            $options[$num] = $uid;
            $buttons[] = ["{$num} - ادمین {$uid}"];
        }
        foreach (Config::adminIds() as $ownerId) {
            $lines[] = "👑 OWNER {$ownerId}";
        }
        $buttons[] = [UiLabels::BTN_BACK, UiLabels::BTN_MAIN, UiLabels::BTN_CANCEL];
        $this->database->setUserState($userId, 'admin.admins.list', ['options' => $options, 'stack' => ['admin.root']]);
        if ($notice !== null && $notice !== '') {
            $this->telegram->sendMessage($chatId, $notice);
        }
        $this->telegram->sendMessage($chatId, $this->uiText->multi(new UiTextBlock(
            title: '👮 <b>مدیریت ادمین‌ها</b>',
            lines: [new UiTextLine('📋', 'لیست ادمین‌ها', $lines !== [] ? implode("\n", $lines) : 'ادمینی ثبت نشده است.')],
            tipBlockquote: '💡 OWNERها فقط نمایش داده می‌شوند و قابل حذف نیستند.',
        )), $this->uiKeyboard->replyMenu($buttons));
    }

    private function openAdminAdminView(int $chatId, int $userId, int $targetUid, ?string $notice = null): void
    {
        if (in_array($targetUid, Config::adminIds(), true)) {
            $this->openAdminAdminsList($chatId, $userId, $this->uiText->warning('این کاربر owner است و قابل ویرایش نیست.'));
            return;
        }
        $perms = $this->database->getAdminPermissions($targetUid);
        $permKeys = ['types', 'stock', 'users', 'settings', 'payments', 'requests', 'broadcast', 'agents', 'panels'];
        $rows = [];
        $permLabels = [];
        $lines = [];
        foreach ($permKeys as $k) {
            $enabled = (bool) ($perms[$k] ?? false);
            $label = ($enabled ? '✅ ' : '❌ ') . $k;
            $rows[] = [$label];
            $permLabels[$label] = $k;
            $lines[] = $label;
        }
        $rows[] = [self::ADMIN_ADMIN_DELETE];
        $rows[] = [UiLabels::BTN_BACK, UiLabels::BTN_MAIN, UiLabels::BTN_CANCEL];
        $this->database->setUserState($userId, 'admin.admin.view', ['target_user_id' => $targetUid, 'perm_labels' => $permLabels, 'stack' => ['admin.admins.list', 'admin.root']]);
        if ($notice !== null && $notice !== '') {
            $this->telegram->sendMessage($chatId, $notice);
        }
        $this->telegram->sendMessage($chatId, $this->uiText->multi(new UiTextBlock(
            title: '👮 <b>دسترسی‌های ادمین</b>',
            lines: [
                new UiTextLine('🆔', 'ادمین', "<code>{$targetUid}</code>"),
                new UiTextLine('🔐', 'دسترسی‌ها', implode("\n", $lines)),
            ],
            tipBlockquote: '💡 با لمس هر دسترسی، وضعیت آن روشن/خاموش می‌شود.',
        )), $this->uiKeyboard->replyMenu($rows));
    }

    private function openAdminPinsList(int $chatId, int $userId, ?string $notice = null): void
    {
        $options = [];
        $lines = [];
        $buttons = [[self::ADMIN_PINS_ADD]];
        foreach (array_values($this->database->listPinnedMessages()) as $idx => $pin) {
            $num = (string) ($idx + 1);
            $pinId = (int) ($pin['id'] ?? 0);
            if ($pinId <= 0) {
                continue;
            }
            $preview = mb_substr(trim((string) ($pin['text'] ?? '')), 0, 24);
            $lines[] = "{$num}) #{$pinId} | " . htmlspecialchars($preview);
            $options[$num] = $pinId;
            $buttons[] = ["{$num} - پین #{$pinId}"];
        }
        $buttons[] = [UiLabels::BTN_BACK, UiLabels::BTN_MAIN, UiLabels::BTN_CANCEL];
        $this->database->setUserState($userId, 'admin.pins.list', ['options' => $options, 'stack' => ['admin.root']]);
        if ($notice !== null && $notice !== '') {
            $this->telegram->sendMessage($chatId, $notice);
        }
        $this->telegram->sendMessage($chatId, $this->uiText->multi(new UiTextBlock(
            title: '📌 <b>مدیریت پیام‌های پین</b>',
            lines: [new UiTextLine('📋', 'پیام‌ها', $lines !== [] ? implode("\n", $lines) : 'پیامی ثبت نشده است.')],
            tipBlockquote: '💡 قبل از ارسال همگانی، متن پین را مرور کنید.',
        )), $this->uiKeyboard->replyMenu($buttons));
    }

    private function openAdminPinView(int $chatId, int $userId, int $pinId, ?string $notice = null): void
    {
        $pin = $this->database->getPinnedMessage($pinId);
        if ($pin === null) {
            $this->openAdminPinsList($chatId, $userId, $this->uiText->warning('پیام پین پیدا نشد.'));
            return;
        }
        $sendCount = count($this->database->getPinnedSends($pinId));
        $this->database->setUserState($userId, 'admin.pin.view', ['pin_id' => $pinId, 'stack' => ['admin.pins.list', 'admin.root']]);
        if ($notice !== null && $notice !== '') {
            $this->telegram->sendMessage($chatId, $notice);
        }
        $this->telegram->sendMessage($chatId, $this->uiText->multi(new UiTextBlock(
            title: "📌 <b>پیام پین #{$pinId}</b>",
            lines: [
                new UiTextLine('🧾', 'متن', htmlspecialchars((string) ($pin['text'] ?? ''))),
                new UiTextLine('📤', 'ارسال‌شده', (string) $sendCount),
            ],
            tipBlockquote: '💡 ارسال همگانی ممکن است زمان‌بر باشد.',
        )), $this->uiKeyboard->replyMenu([
            [self::ADMIN_PIN_SEND_ALL],
            [self::ADMIN_PIN_EDIT, self::ADMIN_PIN_DELETE],
            [UiLabels::BTN_BACK, UiLabels::BTN_MAIN, UiLabels::BTN_CANCEL],
        ]));
    }

    private function handleAdminFinalModulesState(int $chatId, int $userId, string $text, array $state, array $message): void
    {
        if ($text === UiLabels::BTN_CANCEL || $text === UiLabels::BTN_MAIN || $text === KeyboardBuilder::BTN_BACK_MAIN) {
            $this->openAdminRoot($chatId, $userId);
            return;
        }
        $stateName = (string) ($state['state_name'] ?? '');
        $payload = is_array($state['payload'] ?? null) ? $state['payload'] : [];

        if ($stateName === 'admin.agents.list') {
            if ($text === UiLabels::BTN_BACK) {
                $this->openAdminRoot($chatId, $userId);
                return;
            }
            if ($text === self::ADMIN_AGENTS_REFRESH) {
                $this->openAdminAgentsList($chatId, $userId);
                return;
            }
            $options = is_array($payload['options'] ?? null) ? $payload['options'] : [];
            $selected = $this->extractOptionKey($text);
            $agentId = isset($options[$selected]) ? (int) $options[$selected] : 0;
            if ($agentId > 0) {
                $this->openAdminAgentView($chatId, $userId, $agentId);
                return;
            }
        }
        if ($stateName === 'admin.agent.view') {
            $agentId = (int) ($payload['agent_id'] ?? 0);
            if ($text === UiLabels::BTN_BACK) {
                $this->openAdminAgentsList($chatId, $userId);
                return;
            }
            $options = is_array($payload['options'] ?? null) ? $payload['options'] : [];
            $selected = $this->extractOptionKey($text);
            $pkgId = isset($options[$selected]) ? (int) $options[$selected] : 0;
            if ($pkgId > 0) {
                $this->database->setUserState($userId, 'admin.agent.edit', ['agent_id' => $agentId, 'package_id' => $pkgId]);
                $this->telegram->sendMessage($chatId, $this->uiText->info('قیمت را به تومان بفرستید. برای حذف قیمت اختصاصی «-» بفرستید.'), $this->uiKeyboard->replyMenu([[UiLabels::BTN_BACK, UiLabels::BTN_MAIN, UiLabels::BTN_CANCEL]]));
                return;
            }
        }
        if ($stateName === 'admin.agent.edit') {
            $agentId = (int) ($payload['agent_id'] ?? 0);
            $pkgId = (int) ($payload['package_id'] ?? 0);
            if ($text === UiLabels::BTN_BACK) {
                $this->openAdminAgentView($chatId, $userId, $agentId);
                return;
            }
            $raw = trim($text);
            if ($raw === '-' || $raw === '—') {
                $this->database->clearAgencyPrice($agentId, $pkgId);
                $this->openAdminAgentView($chatId, $userId, $agentId, $this->uiText->success('قیمت اختصاصی حذف شد.'));
                return;
            }
            $price = (int) preg_replace('/\D+/', '', $raw);
            if ($price <= 0) {
                $this->telegram->sendMessage($chatId, $this->uiText->warning('قیمت معتبر وارد کنید.'));
                return;
            }
            $this->database->setAgencyPrice($agentId, $pkgId, $price);
            $this->openAdminAgentView($chatId, $userId, $agentId, $this->uiText->success('قیمت اختصاصی ثبت شد.'));
            return;
        }

        if ($stateName === 'admin.panels.list') {
            if ($text === UiLabels::BTN_BACK) {
                $this->openAdminRoot($chatId, $userId);
                return;
            }
            if ($text === self::ADMIN_PANELS_ADD) {
                $this->database->setUserState($userId, 'admin.panel.create', []);
                $this->telegram->sendMessage($chatId, $this->uiText->info('فرمت پنل: name|ip|port|patch|username|password'), $this->uiKeyboard->replyMenu([[UiLabels::BTN_BACK, UiLabels::BTN_MAIN, UiLabels::BTN_CANCEL]]));
                return;
            }
            $options = is_array($payload['options'] ?? null) ? $payload['options'] : [];
            $selected = $this->extractOptionKey($text);
            $panelId = isset($options[$selected]) ? (int) $options[$selected] : 0;
            if ($panelId > 0) {
                $this->openAdminPanelView($chatId, $userId, $panelId);
                return;
            }
        }
        if ($stateName === 'admin.panel.create') {
            if ($text === UiLabels::BTN_BACK) {
                $this->openAdminPanelsList($chatId, $userId);
                return;
            }
            $parts = array_map('trim', explode('|', $text));
            if (count($parts) !== 6) {
                $this->telegram->sendMessage($chatId, $this->uiText->warning('فرمت باید ۶ بخشی باشد.'));
                return;
            }
            [$name, $ip, $portRaw, $patch, $username, $password] = $parts;
            $port = (int) preg_replace('/\D+/', '', $portRaw);
            if ($name === '' || $ip === '' || $port <= 0 || $username === '' || $password === '') {
                $this->telegram->sendMessage($chatId, $this->uiText->warning('مقادیر معتبر نیستند.'));
                return;
            }
            $panelId = $this->database->addPanel($name, $ip, $port, $patch, $username, $password);
            $this->openAdminPanelView($chatId, $userId, $panelId, $this->uiText->success('پنل ثبت شد.'));
            return;
        }
        if ($stateName === 'admin.panel.view') {
            $panelId = (int) ($payload['panel_id'] ?? 0);
            if ($text === UiLabels::BTN_BACK) {
                $this->openAdminPanelsList($chatId, $userId);
                return;
            }
            if ($text === self::ADMIN_PANEL_TOGGLE) {
                $panel = $this->database->getPanel($panelId);
                if (is_array($panel)) {
                    $active = ((int) ($panel['is_active'] ?? 0)) === 1;
                    $this->database->updatePanelActive($panelId, !$active);
                }
                $this->openAdminPanelView($chatId, $userId, $panelId, $this->uiText->success('وضعیت پنل بروزرسانی شد.'));
                return;
            }
            if ($text === self::ADMIN_PANEL_DELETE) {
                $this->database->setUserState($userId, 'admin.panel.delete', ['panel_id' => $panelId]);
                $this->telegram->sendMessage($chatId, $this->uiText->warning("برای حذف پنل #{$panelId} عبارت «حذف» را ارسال کنید."), $this->uiKeyboard->replyMenu([[UiLabels::BTN_BACK, UiLabels::BTN_MAIN, UiLabels::BTN_CANCEL]]));
                return;
            }
            if ($text === self::ADMIN_PANEL_PKG_ADD) {
                $this->database->setUserState($userId, 'admin.panel.pkg.create', ['panel_id' => $panelId]);
                $this->telegram->sendMessage($chatId, $this->uiText->info('فرمت پکیج پنل: name|volume_gb|duration_days|inbound_id'), $this->uiKeyboard->replyMenu([[UiLabels::BTN_BACK, UiLabels::BTN_MAIN, UiLabels::BTN_CANCEL]]));
                return;
            }
        }
        if ($stateName === 'admin.panel.pkg.create') {
            $panelId = (int) ($payload['panel_id'] ?? 0);
            if ($text === UiLabels::BTN_BACK) {
                $this->openAdminPanelView($chatId, $userId, $panelId);
                return;
            }
            $parts = array_map('trim', explode('|', $text));
            if (count($parts) !== 4) {
                $this->telegram->sendMessage($chatId, $this->uiText->warning('فرمت معتبر نیست.'));
                return;
            }
            [$name, $volRaw, $durRaw, $inbRaw] = $parts;
            $vol = (float) str_replace(',', '.', $volRaw);
            $dur = (int) preg_replace('/\D+/', '', $durRaw);
            $inb = (int) preg_replace('/\D+/', '', $inbRaw);
            if ($panelId <= 0 || $name === '' || $vol <= 0 || $dur <= 0 || $inb <= 0) {
                $this->telegram->sendMessage($chatId, $this->uiText->warning('مقادیر معتبر نیستند.'));
                return;
            }
            $this->database->addPanelPackage($panelId, $name, $vol, $dur, $inb);
            $this->openAdminPanelView($chatId, $userId, $panelId, $this->uiText->success('پکیج پنل ثبت شد.'));
            return;
        }
        if ($stateName === 'admin.panel.delete') {
            $panelId = (int) ($payload['panel_id'] ?? 0);
            if ($text === UiLabels::BTN_BACK) {
                $this->openAdminPanelView($chatId, $userId, $panelId);
                return;
            }
            if (trim($text) === 'حذف') {
                $this->database->deletePanel($panelId);
                $this->openAdminPanelsList($chatId, $userId, $this->uiText->success('پنل حذف شد.'));
                return;
            }
            $this->telegram->sendMessage($chatId, $this->uiText->warning('برای تایید حذف عبارت «حذف» را بفرستید.'));
            return;
        }

        if ($stateName === 'admin.broadcast.compose') {
            if ($text === UiLabels::BTN_BACK) {
                $this->openAdminRoot($chatId, $userId);
                return;
            }
            if (trim($text) === '' || str_starts_with($text, '/')) {
                $this->telegram->sendMessage($chatId, $this->uiText->warning('متن پیام را ارسال کنید.'));
                return;
            }
            $this->database->setUserState($userId, 'admin.broadcast.confirm', ['message' => $text]);
            $this->telegram->sendMessage($chatId, $this->uiText->multi(new UiTextBlock(
                title: '📣 <b>تایید ارسال همگانی</b>',
                lines: [new UiTextLine('📝', 'پیش‌نمایش', htmlspecialchars($text))],
                tipBlockquote: '💡 scope را انتخاب کنید و سپس دکمه ارسال را بزنید.',
            )), $this->uiKeyboard->replyMenu([
                [self::ADMIN_BROADCAST_SCOPE_ALL, self::ADMIN_BROADCAST_SCOPE_USERS],
                [self::ADMIN_BROADCAST_SCOPE_AGENTS, self::ADMIN_BROADCAST_SCOPE_ADMINS],
                [self::ADMIN_BROADCAST_SEND],
                [UiLabels::BTN_BACK, UiLabels::BTN_MAIN, UiLabels::BTN_CANCEL],
            ]));
            return;
        }
        if ($stateName === 'admin.broadcast.confirm') {
            if ($text === UiLabels::BTN_BACK) {
                $this->openAdminBroadcastCompose($chatId, $userId);
                return;
            }
            $scopeMap = [self::ADMIN_BROADCAST_SCOPE_ALL => 'all', self::ADMIN_BROADCAST_SCOPE_USERS => 'users', self::ADMIN_BROADCAST_SCOPE_AGENTS => 'agents', self::ADMIN_BROADCAST_SCOPE_ADMINS => 'admins'];
            $scope = (string) ($payload['scope'] ?? 'all');
            if (isset($scopeMap[$text])) {
                $scope = $scopeMap[$text];
                $this->database->setUserState($userId, 'admin.broadcast.confirm', ['message' => (string) ($payload['message'] ?? ''), 'scope' => $scope]);
                $this->telegram->sendMessage($chatId, $this->uiText->info("scope انتخاب شد: <b>{$scope}</b>"));
                return;
            }
            if ($text === self::ADMIN_BROADCAST_SEND) {
                $msg = (string) ($payload['message'] ?? '');
                $targets = $this->database->listUserIdsForBroadcast($scope);
                $sent = 0;
                foreach ($targets as $targetId) {
                    if ($targetId <= 0) {
                        continue;
                    }
                    try {
                        $this->telegram->sendMessage($targetId, $msg);
                        $sent++;
                    } catch (\Throwable $e) {
                    }
                }
                $this->openAdminRoot($chatId, $userId);
                $this->telegram->sendMessage($chatId, $this->uiText->success("ارسال همگانی انجام شد. موفق: <b>{$sent}</b>"));
                return;
            }
        }

        if ($stateName === 'admin.deliveries.list') {
            if ($text === UiLabels::BTN_BACK) {
                $this->openAdminRoot($chatId, $userId);
                return;
            }
            if ($text === self::ADMIN_DELIVERIES_REFRESH) {
                $this->openAdminDeliveriesList($chatId, $userId);
                return;
            }
            $options = is_array($payload['options'] ?? null) ? $payload['options'] : [];
            $selected = $this->extractOptionKey($text);
            $orderId = isset($options[$selected]) ? (int) $options[$selected] : 0;
            if ($orderId > 0) {
                $this->openAdminDeliveryView($chatId, $userId, $orderId);
                return;
            }
        }
        if ($stateName === 'admin.delivery.view') {
            $orderId = (int) ($payload['order_id'] ?? 0);
            if ($text === UiLabels::BTN_BACK) {
                $this->openAdminDeliveriesList($chatId, $userId);
                return;
            }
            if ($text === self::ADMIN_DELIVERY_DO) {
                $this->database->setUserState($userId, 'admin.delivery.review', ['order_id' => $orderId]);
                $this->telegram->sendMessage($chatId, $this->uiText->warning("برای تحویل سفارش #{$orderId} عبارت «تحویل» را ارسال کنید."), $this->uiKeyboard->replyMenu([[UiLabels::BTN_BACK, UiLabels::BTN_MAIN, UiLabels::BTN_CANCEL]]));
                return;
            }
        }
        if ($stateName === 'admin.delivery.review') {
            $orderId = (int) ($payload['order_id'] ?? 0);
            if ($text === UiLabels::BTN_BACK) {
                $this->openAdminDeliveryView($chatId, $userId, $orderId);
                return;
            }
            if (trim($text) === 'تحویل') {
                $res = $this->database->deliverPendingOrder($orderId);
                if ($res['ok'] ?? false) {
                    $this->openAdminDeliveriesList($chatId, $userId, $this->uiText->success('تحویل انجام شد.'));
                } else {
                    $this->openAdminDeliveryView($chatId, $userId, $orderId, $this->uiText->warning('تحویل انجام نشد.'));
                }
                return;
            }
        }

        if ($stateName === 'admin.groupops.view') {
            if ($text === UiLabels::BTN_BACK) {
                $this->openAdminRoot($chatId, $userId);
                return;
            }
            if ($text === self::ADMIN_GROUPOPS_SET_GROUP) {
                $this->database->setUserState($userId, 'admin.groupops.action', ['mode' => 'group_id']);
                $this->telegram->sendMessage($chatId, $this->uiText->info('Group ID را ارسال کنید (یا - برای غیرفعال‌سازی).'), $this->uiKeyboard->replyMenu([[UiLabels::BTN_BACK, UiLabels::BTN_MAIN, UiLabels::BTN_CANCEL]]));
                return;
            }
            if ($text === self::ADMIN_GROUPOPS_RESTORE) {
                $this->database->setUserState($userId, 'admin.groupops.action', ['mode' => 'restore']);
                $this->telegram->sendMessage($chatId, $this->uiText->info('JSON تنظیمات را ارسال کنید.'), $this->uiKeyboard->replyMenu([[UiLabels::BTN_BACK, UiLabels::BTN_MAIN, UiLabels::BTN_CANCEL]]));
                return;
            }
        }
        if ($stateName === 'admin.groupops.action') {
            $mode = (string) ($payload['mode'] ?? '');
            if ($text === UiLabels::BTN_BACK) {
                $this->openAdminGroupOpsView($chatId, $userId);
                return;
            }
            if ($mode === 'group_id') {
                $val = trim($text);
                if ($val !== '-' && !preg_match('/^-?\\d+$/', $val)) {
                    $this->telegram->sendMessage($chatId, $this->uiText->warning('Group ID باید عددی باشد.'));
                    return;
                }
                $this->settings->set('group_id', $val === '-' ? '' : $val);
                $this->openAdminGroupOpsView($chatId, $userId, $this->uiText->success('Group ID ذخیره شد.'));
                return;
            }
            if ($mode === 'restore') {
                $raw = trim((string) ($message['text'] ?? ''));
                $data = json_decode($raw, true);
                $settings = is_array($data) ? ($data['settings'] ?? null) : null;
                if (!is_array($settings)) {
                    $this->telegram->sendMessage($chatId, $this->uiText->warning('ساختار JSON نامعتبر است.'));
                    return;
                }
                foreach ($settings as $k => $v) {
                    $key = trim((string) $k);
                    if ($key !== '') {
                        $this->settings->set($key, (string) $v);
                    }
                }
                $this->openAdminGroupOpsView($chatId, $userId, $this->uiText->success('بازیابی تنظیمات انجام شد.'));
                return;
            }
        }

        if ($stateName === 'admin.freetest.menu') {
            if ($text === UiLabels::BTN_BACK) {
                $this->openAdminRoot($chatId, $userId);
                return;
            }
            if ($text === self::ADMIN_FREETEST_RULE) {
                $this->database->setUserState($userId, 'admin.freetest.rule', []);
                $this->telegram->sendMessage($chatId, $this->uiText->info('فرمت قانون: package_id|max_claims|cooldown_days'), $this->uiKeyboard->replyMenu([[UiLabels::BTN_BACK, UiLabels::BTN_MAIN, UiLabels::BTN_CANCEL]]));
                return;
            }
            if ($text === self::ADMIN_FREETEST_RESET) {
                $this->database->setUserState($userId, 'admin.freetest.reset', []);
                $this->telegram->sendMessage($chatId, $this->uiText->info('آیدی عددی کاربر را ارسال کنید.'), $this->uiKeyboard->replyMenu([[UiLabels::BTN_BACK, UiLabels::BTN_MAIN, UiLabels::BTN_CANCEL]]));
                return;
            }
        }
        if ($stateName === 'admin.freetest.rule') {
            if ($text === UiLabels::BTN_BACK) {
                $this->openAdminFreeTestMenu($chatId, $userId);
                return;
            }
            $parts = array_map('trim', explode('|', $text));
            $packageId = (int) ($parts[0] ?? 0);
            $maxClaims = (int) ($parts[1] ?? 1);
            $cooldownDays = (int) ($parts[2] ?? 0);
            if ($packageId <= 0) {
                $this->telegram->sendMessage($chatId, $this->uiText->warning('فرمت نامعتبر است.'));
                return;
            }
            $this->database->saveFreeTestRule($packageId, $maxClaims, $cooldownDays, true);
            $this->openAdminFreeTestMenu($chatId, $userId, $this->uiText->success('قانون تست رایگان ذخیره شد.'));
            return;
        }
        if ($stateName === 'admin.freetest.reset') {
            if ($text === UiLabels::BTN_BACK) {
                $this->openAdminFreeTestMenu($chatId, $userId);
                return;
            }
            $targetUserId = (int) preg_replace('/\D+/', '', $text);
            if ($targetUserId <= 0) {
                $this->telegram->sendMessage($chatId, $this->uiText->warning('آیدی معتبر نیست.'));
                return;
            }
            $this->database->resetFreeTestQuota($targetUserId);
            $this->openAdminFreeTestMenu($chatId, $userId, $this->uiText->success('سهمیه کاربر ریست شد.'));
            return;
        }
    }

    private function openAdminAgentsList(int $chatId, int $userId, ?string $notice = null): void
    {
        $agents = $this->database->listUserIdsForBroadcast('agents');
        $options = [];
        $lines = [];
        $buttons = [[self::ADMIN_AGENTS_REFRESH]];
        foreach (array_values($agents) as $idx => $aid) {
            $num = (string) ($idx + 1);
            $id = (int) $aid;
            if ($id <= 0) {
                continue;
            }
            $lines[] = "{$num}) U:{$id}";
            $options[$num] = $id;
            $buttons[] = ["{$num} - نماینده {$id}"];
        }
        $buttons[] = [UiLabels::BTN_BACK, UiLabels::BTN_MAIN, UiLabels::BTN_CANCEL];
        $this->database->setUserState($userId, 'admin.agents.list', ['options' => $options]);
        if ($notice) {
            $this->telegram->sendMessage($chatId, $notice);
        }
        $this->telegram->sendMessage($chatId, $this->uiText->multi(new UiTextBlock(title: '🤝 <b>مدیریت نماینده‌ها</b>', lines: [new UiTextLine('📋', 'لیست', $lines !== [] ? implode("\n", $lines) : 'نماینده‌ای ثبت نشده است.')], tipBlockquote: '💡 نماینده را انتخاب کنید تا قیمت اختصاصی پکیج‌ها را مدیریت کنید.')), $this->uiKeyboard->replyMenu($buttons));
    }

    private function openAdminAgentView(int $chatId, int $userId, int $agentId, ?string $notice = null): void
    {
        $lines = [];
        $options = [];
        $buttons = [];
        foreach (array_values($this->database->listAllPackages()) as $idx => $pkg) {
            $num = (string) ($idx + 1);
            $pkgId = (int) ($pkg['id'] ?? 0);
            if ($pkgId <= 0) {
                continue;
            }
            $custom = $this->database->getAgencyPrice($agentId, $pkgId);
            $lines[] = "{$num}) #{$pkgId} " . (string) ($pkg['name'] ?? '-') . ' | ' . ($custom === null ? '-' : (string) $custom);
            $options[$num] = $pkgId;
            $buttons[] = ["{$num} - پکیج {$pkgId}"];
        }
        $buttons[] = [UiLabels::BTN_BACK, UiLabels::BTN_MAIN, UiLabels::BTN_CANCEL];
        $this->database->setUserState($userId, 'admin.agent.view', ['agent_id' => $agentId, 'options' => $options]);
        if ($notice) {
            $this->telegram->sendMessage($chatId, $notice);
        }
        $this->telegram->sendMessage($chatId, $this->uiText->multi(new UiTextBlock(title: "🤝 <b>قیمت‌گذاری نماینده {$agentId}</b>", lines: [new UiTextLine('📦', 'پکیج‌ها', implode("\n", $lines))], tipBlockquote: '💡 پکیج را انتخاب کنید و قیمت اختصاصی را ثبت یا حذف کنید.')), $this->uiKeyboard->replyMenu($buttons));
    }

    private function openAdminPanelsList(int $chatId, int $userId, ?string $notice = null): void
    {
        $options = [];
        $lines = [];
        $buttons = [[self::ADMIN_PANELS_ADD]];
        foreach (array_values($this->database->listPanels()) as $idx => $panel) {
            $num = (string) ($idx + 1);
            $panelId = (int) ($panel['id'] ?? 0);
            if ($panelId <= 0) {
                continue;
            }
            $active = ((int) ($panel['is_active'] ?? 0)) === 1 ? '🟢' : '🔴';
            $lines[] = "{$num}) {$active} #{$panelId} " . (string) ($panel['name'] ?? '-');
            $options[$num] = $panelId;
            $buttons[] = ["{$num} - پنل {$panelId}"];
        }
        $buttons[] = [UiLabels::BTN_BACK, UiLabels::BTN_MAIN, UiLabels::BTN_CANCEL];
        $this->database->setUserState($userId, 'admin.panels.list', ['options' => $options]);
        if ($notice) {
            $this->telegram->sendMessage($chatId, $notice);
        }
        $this->telegram->sendMessage($chatId, $this->uiText->multi(new UiTextBlock(title: '🖥 <b>مدیریت پنل‌ها</b>', lines: [new UiTextLine('📋', 'پنل‌ها', $lines !== [] ? implode("\n", $lines) : 'پنلی ثبت نشده است.')], tipBlockquote: '💡 پنل را انتخاب کنید تا تغییرات مدیریت شود.')), $this->uiKeyboard->replyMenu($buttons));
    }

    private function openAdminPanelView(int $chatId, int $userId, int $panelId, ?string $notice = null): void
    {
        $panel = $this->database->getPanel($panelId);
        if (!is_array($panel)) {
            $this->openAdminPanelsList($chatId, $userId, $this->uiText->warning('پنل پیدا نشد.'));
            return;
        }
        $pkgs = $this->database->listPanelPackages($panelId);
        $pkgLines = [];
        foreach ($pkgs as $pp) {
            $pkgLines[] = '#' . (int) ($pp['id'] ?? 0) . ' ' . (string) ($pp['name'] ?? '-');
        }
        if ($notice) {
            $this->telegram->sendMessage($chatId, $notice);
        }
        $this->database->setUserState($userId, 'admin.panel.view', ['panel_id' => $panelId]);
        $this->telegram->sendMessage($chatId, $this->uiText->multi(new UiTextBlock(title: "🖥 <b>پنل #{$panelId}</b>", lines: [new UiTextLine('📛', 'نام', htmlspecialchars((string) ($panel['name'] ?? '-'))), new UiTextLine('📦', 'پکیج‌ها', $pkgLines !== [] ? implode("\n", $pkgLines) : 'پکیجی ثبت نشده است.')], tipBlockquote: '💡 عملیات حساس مانند حذف پنل نیازمند تایید هستند.')), $this->uiKeyboard->replyMenu([[self::ADMIN_PANEL_TOGGLE, self::ADMIN_PANEL_DELETE], [self::ADMIN_PANEL_PKG_ADD], [UiLabels::BTN_BACK, UiLabels::BTN_MAIN, UiLabels::BTN_CANCEL]]));
    }

    private function openAdminBroadcastCompose(int $chatId, int $userId, ?string $notice = null): void
    {
        $this->database->setUserState($userId, 'admin.broadcast.compose', []);
        if ($notice) {
            $this->telegram->sendMessage($chatId, $notice);
        }
        $this->telegram->sendMessage($chatId, $this->uiText->multi(new UiTextBlock(title: '📣 <b>ارسال همگانی</b>', lines: [new UiTextLine('📝', 'مرحله ۱', 'متن پیام همگانی را ارسال کنید.')], tipBlockquote: '💡 بعد از متن، scope انتخاب می‌شود و سپس ارسال نهایی تایید می‌گردد.')), $this->uiKeyboard->replyMenu([[UiLabels::BTN_BACK, UiLabels::BTN_MAIN, UiLabels::BTN_CANCEL]]));
    }

    private function openAdminDeliveriesList(int $chatId, int $userId, ?string $notice = null): void
    {
        $orders = $this->database->listPendingDeliveries(30);
        $options = [];
        $lines = [];
        $buttons = [[self::ADMIN_DELIVERIES_REFRESH]];
        foreach (array_values($orders) as $idx => $ord) {
            $num = (string) ($idx + 1);
            $id = (int) ($ord['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            $lines[] = "{$num}) #{$id} | U:" . (int) ($ord['user_id'] ?? 0) . " | P:" . (int) ($ord['package_id'] ?? 0);
            $options[$num] = $id;
            $buttons[] = ["{$num} - سفارش {$id}"];
        }
        $buttons[] = [UiLabels::BTN_BACK, UiLabels::BTN_MAIN, UiLabels::BTN_CANCEL];
        $this->database->setUserState($userId, 'admin.deliveries.list', ['options' => $options]);
        if ($notice) {
            $this->telegram->sendMessage($chatId, $notice);
        }
        $this->telegram->sendMessage($chatId, $this->uiText->multi(new UiTextBlock(title: '📦 <b>تحویل سفارش‌ها</b>', lines: [new UiTextLine('📋', 'سفارش‌های معلق', $lines !== [] ? implode("\n", $lines) : 'سفارشی برای تحویل وجود ندارد.')], tipBlockquote: '💡 سفارش را انتخاب کنید و سپس تحویل را تایید کنید.')), $this->uiKeyboard->replyMenu($buttons));
    }

    private function openAdminDeliveryView(int $chatId, int $userId, int $orderId, ?string $notice = null): void
    {
        $this->database->setUserState($userId, 'admin.delivery.view', ['order_id' => $orderId]);
        if ($notice) {
            $this->telegram->sendMessage($chatId, $notice);
        }
        $this->telegram->sendMessage($chatId, $this->uiText->multi(new UiTextBlock(title: "📦 <b>سفارش #{$orderId}</b>", lines: [new UiTextLine('⚙️', 'عملیات', 'برای تحویل نهایی از دکمه زیر استفاده کنید.')], tipBlockquote: '💡 تحویل، عملیات حساس و غیرقابل بازگشت است.')), $this->uiKeyboard->replyMenu([[self::ADMIN_DELIVERY_DO], [UiLabels::BTN_BACK, UiLabels::BTN_MAIN, UiLabels::BTN_CANCEL]]));
    }

    private function openAdminGroupOpsView(int $chatId, int $userId, ?string $notice = null): void
    {
        $this->database->setUserState($userId, 'admin.groupops.view', []);
        if ($notice) {
            $this->telegram->sendMessage($chatId, $notice);
        }
        $groupId = trim($this->settings->get('group_id', ''));
        $this->telegram->sendMessage($chatId, $this->uiText->multi(new UiTextBlock(title: '🗃 <b>گروه/بکاپ</b>', lines: [new UiTextLine('🧩', 'Group ID فعلی', $groupId !== '' ? "<code>{$groupId}</code>" : '❌ تنظیم نشده')], tipBlockquote: '💡 بازیابی تنظیمات فقط با JSON معتبر انجام می‌شود.')), $this->uiKeyboard->replyMenu([[self::ADMIN_GROUPOPS_SET_GROUP, self::ADMIN_GROUPOPS_RESTORE], [UiLabels::BTN_BACK, UiLabels::BTN_MAIN, UiLabels::BTN_CANCEL]]));
    }

    private function openAdminFreeTestMenu(int $chatId, int $userId, ?string $notice = null): void
    {
        $lines = [];
        foreach ($this->database->listFreeTestRules() as $rule) {
            $lines[] = '#'.(int)($rule['package_id'] ?? 0).' | max='.(int)($rule['max_claims'] ?? 1).' | cd='.(int)($rule['cooldown_days'] ?? 0);
        }
        $this->database->setUserState($userId, 'admin.freetest.menu', []);
        if ($notice) {
            $this->telegram->sendMessage($chatId, $notice);
        }
        $this->telegram->sendMessage($chatId, $this->uiText->multi(new UiTextBlock(title: '🧪 <b>مدیریت تست رایگان</b>', lines: [new UiTextLine('📋', 'قوانین', $lines !== [] ? implode("\n", $lines) : 'قانونی ثبت نشده است.')], tipBlockquote: '💡 در صورت نیاز می‌توانید سهمیه کاربر را ریست کنید.')), $this->uiKeyboard->replyMenu([[self::ADMIN_FREETEST_RULE, self::ADMIN_FREETEST_RESET], [UiLabels::BTN_BACK, UiLabels::BTN_MAIN, UiLabels::BTN_CANCEL]]));
    }

    private function handleBuyTypeSelectionState(int $chatId, int $userId, string $text, array $state): void
    {
        if ($text === UiLabels::BTN_MAIN || $text === KeyboardBuilder::BTN_BACK_MAIN || $text === UiLabels::BTN_CANCEL) {
            $this->database->clearUserState($userId);
            $this->telegram->sendMessage($chatId, $this->menus->mainMenuText(), $this->menus->mainMenuReplyKeyboard($userId));
            return;
        }

        $options = is_array($state['payload']['options'] ?? null) ? $state['payload']['options'] : [];
        $selected = $this->extractOptionKey($text);
        $typeId = isset($options[$selected]) ? (int) $options[$selected] : 0;
        if ($typeId <= 0) {
            $this->telegram->sendMessage($chatId, $this->uiText->warning('گزینه نامعتبر است. لطفاً یکی از گزینه‌های لیست را انتخاب کنید.'));
            return;
        }

        $this->showBuyPackageSelection($chatId, $userId, $typeId);
    }

    private function showBuyPackageSelection(int $chatId, int $userId, int $typeId): void
    {
        $stockOnly = $this->settings->get('preorder_mode', '0') === '1';
        $packages = $this->database->getActivePackagesByTypeWithStock($typeId, $stockOnly);
        if ($packages === []) {
            $this->telegram->sendMessage($chatId, $this->uiText->info('پکیجی برای این نوع سرویس یافت نشد.'));
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

        $buttons[] = [UiLabels::BTN_BACK, UiLabels::BTN_MAIN, UiLabels::BTN_CANCEL];
        $this->database->setUserState($userId, 'buy.await_package', ['options' => $optionMap, 'type_id' => $typeId, 'stack' => ['buy.await_type'], 'package_id' => null, 'payment_method' => null]);
        $this->telegram->sendMessage(
            $chatId,
            $this->uiText->multi(new UiTextBlock(
                title: '📦 <b>انتخاب پکیج</b>',
                lines: [
                    new UiTextLine('📋', 'لیست پکیج‌ها', implode("\n", $lines)),
                ],
                tipBlockquote: '💡 شماره هر پکیج را از روی دکمه‌های همین صفحه انتخاب کنید؛ در صورت نیاز با بازگشت می‌توانید نوع سرویس را دوباره تغییر دهید.',
            )),
            $this->uiKeyboard->replyMenu($buttons)
        );
    }

    private function handleBuyPackageSelectionState(int $chatId, int $userId, string $text, array $state): void
    {
        if ($text === UiLabels::BTN_BACK || $text === KeyboardBuilder::BTN_BACK_TYPES) {
            $this->startBuyTypeReplyFlow($chatId, $userId);
            return;
        }
        if ($text === UiLabels::BTN_MAIN || $text === KeyboardBuilder::BTN_BACK_MAIN || $text === UiLabels::BTN_CANCEL) {
            $this->database->clearUserState($userId);
            $this->telegram->sendMessage($chatId, $this->menus->mainMenuText(), $this->menus->mainMenuReplyKeyboard($userId));
            return;
        }

        $options = is_array($state['payload']['options'] ?? null) ? $state['payload']['options'] : [];
        $selected = $this->extractOptionKey($text);
        $packageId = isset($options[$selected]) ? (int) $options[$selected] : 0;
        if ($packageId <= 0) {
            $this->telegram->sendMessage($chatId, $this->uiText->warning('گزینه نامعتبر است. لطفاً یکی از گزینه‌های لیست را انتخاب کنید.'));
            return;
        }

        if ($this->settings->get('purchase_rules_enabled', '0') === '1' && !$this->database->hasAcceptedPurchaseRules($userId)) {
            $rulesText = trim($this->settings->get('purchase_rules_text', ''));
            $rulesText = $rulesText !== '' ? $rulesText : 'لطفاً قوانین خرید را بپذیرید.';
            $this->database->setUserState($userId, 'buy.await_rules_accept', ['package_id' => $packageId, 'type_id' => (int) ($state['payload']['type_id'] ?? 0), 'stack' => ['buy.await_type', 'buy.await_package'], 'payment_method' => null]);
            $this->telegram->sendMessage(
                $chatId,
                $this->uiText->multi(new UiTextBlock(
                    title: '📜 <b>قوانین خرید</b>',
                    lines: [
                        new UiTextLine('📝', 'متن قوانین', htmlspecialchars($rulesText)),
                    ],
                    tipBlockquote: '⚠️ با تایید قوانین، خرید شما وارد مرحله انتخاب روش پرداخت می‌شود؛ اگر نیاز به تغییر پکیج دارید از دکمه بازگشت استفاده کنید تا انتخاب قبلی را اصلاح کنید.',
                )),
                $this->uiKeyboard->replyMenu([[self::ACCEPT_RULES], [UiLabels::BTN_BACK, UiLabels::BTN_MAIN, UiLabels::BTN_CANCEL]])
            );
            return;
        }

        $this->database->clearUserState($userId);
        $package = $this->database->getPackage($packageId);
        if ($package === null) {
            $this->telegram->sendMessage($chatId, $this->uiText->error('پکیج پیدا نشد.'));
            return;
        }
        $textOut = $this->uiText->multi(new UiTextBlock(
            title: '💰 <b>پرداخت سفارش</b>',
            lines: [
                new UiTextLine('📦', 'پکیج', '<b>' . htmlspecialchars((string) $package['name']) . '</b>'),
                new UiTextLine('💵', 'قیمت', '<b>' . (int) $this->database->effectivePackagePrice($userId, $package) . '</b> تومان'),
            ],
            tipBlockquote: '💡 روش پرداخت را از روی دکمه‌ها انتخاب کنید؛ بعد از پرداخت، وضعیت تراکنش را بررسی کنید تا سفارش به صف تحویل وارد شود.',
        ));
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
        $buttons[] = [UiLabels::BTN_BACK, UiLabels::BTN_MAIN, UiLabels::BTN_CANCEL];
        $this->database->setUserState($userId, 'buy.await_payment_method', ['package_id' => $packageId, 'type_id' => (int) ($state['payload']['type_id'] ?? 0), 'stack' => ['buy.await_type', 'buy.await_package'], 'payment_method' => null, 'gateway' => null]);
        $this->telegram->sendMessage($chatId, $textOut, $this->uiKeyboard->replyMenu($buttons));
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

        $buttons[] = [UiLabels::BTN_MAIN, UiLabels::BTN_CANCEL];
        $this->database->setUserState($userId, 'renew.await_purchase', ['options' => $optionMap, 'stack' => [], 'purchase_id' => null, 'package_id' => null, 'payment_method' => null]);
        $this->telegram->sendMessage(
            $chatId,
            $this->uiText->multi(new UiTextBlock(
                title: '♻️ <b>تمدید سرویس</b>',
                lines: [
                    new UiTextLine('📋', 'لیست سفارش‌ها', implode("\n", $lines)),
                ],
                tipBlockquote: '💡 برای تمدید، شماره سفارش موردنظر را از دکمه‌ها انتخاب کنید؛ در ادامه پکیج‌های تمدید همان سرویس نمایش داده می‌شود و می‌توانید مسیر را با بازگشت مدیریت کنید.',
            )),
            $this->uiKeyboard->replyMenu($buttons)
        );
    }

    private function handleRenewPurchaseSelectionState(int $chatId, int $userId, string $text, array $state): void
    {
        if ($text === UiLabels::BTN_MAIN || $text === UiLabels::BTN_CANCEL || $text === KeyboardBuilder::BTN_BACK_MAIN) {
            $this->database->clearUserState($userId);
            $this->telegram->sendMessage($chatId, $this->menus->mainMenuText(), $this->menus->mainMenuReplyKeyboard($userId));
            return;
        }

        $options = is_array($state['payload']['options'] ?? null) ? $state['payload']['options'] : [];
        $selected = $this->extractOptionKey($text);
        $purchaseId = isset($options[$selected]) ? (int) $options[$selected] : 0;
        if ($purchaseId <= 0) {
            $this->telegram->sendMessage($chatId, $this->uiText->warning('گزینه نامعتبر است. لطفاً یکی از گزینه‌های لیست را انتخاب کنید.'));
            return;
        }

        $purchase = $this->database->getUserPurchaseForRenewal($userId, $purchaseId);
        if (!is_array($purchase)) {
            $this->telegram->sendMessage($chatId, $this->uiText->error('سفارش پیدا نشد.'));
            return;
        }
        if ((int) ($purchase['is_test'] ?? 0) === 1) {
            $this->telegram->sendMessage($chatId, $this->uiText->warning('تمدید برای سرویس تست ممکن نیست.'));
            return;
        }

        $typeId = (int) ($purchase['type_id'] ?? 0);
        $packages = $this->database->getActivePackagesByType($typeId);
        if ($packages === []) {
            $this->telegram->sendMessage($chatId, $this->uiText->info('پکیج تمدید یافت نشد.'));
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

        $buttons[] = [UiLabels::BTN_BACK, UiLabels::BTN_MAIN, UiLabels::BTN_CANCEL];
        $this->database->setUserState($userId, 'renew.await_package', ['options' => $optionMap, 'purchase_id' => $purchaseId, 'stack' => ['renew.await_purchase'], 'package_id' => null, 'payment_method' => null]);
        $this->telegram->sendMessage(
            $chatId,
            $this->uiText->multi(new UiTextBlock(
                title: '♻️ <b>انتخاب پکیج تمدید</b>',
                lines: [
                    new UiTextLine('🧾', 'سفارش', "<code>#{$purchaseId}</code>"),
                    new UiTextLine('📡', 'سرویس فعلی', '<b>' . htmlspecialchars((string) ($purchase['service_name'] ?? '-')) . '</b>'),
                    new UiTextLine('📦', 'پکیج فعلی', '<b>' . htmlspecialchars((string) ($purchase['package_name'] ?? '-')) . '</b>'),
                    new UiTextLine('📋', 'گزینه‌های تمدید', implode("\n", $lines)),
                ],
                tipBlockquote: '💡 پکیج تمدید باید با سرویس فعلی شما سازگار باشد؛ اگر انتخاب را اشتباه انجام دادید با بازگشت به لیست سفارش‌ها برگردید و گزینه درست را انتخاب کنید.',
            )),
            $this->uiKeyboard->replyMenu($buttons)
        );
    }

    private function handleRenewPackageSelectionState(int $chatId, int $userId, string $text, array $state): void
    {
        if ($text === UiLabels::BTN_BACK || $text === KeyboardBuilder::BTN_BACK_PURCHASES) {
            $this->showMyConfigsWithReplyFlow($chatId, $userId);
            return;
        }
        if ($text === UiLabels::BTN_MAIN || $text === UiLabels::BTN_CANCEL || $text === KeyboardBuilder::BTN_BACK_MAIN) {
            $this->database->clearUserState($userId);
            $this->telegram->sendMessage($chatId, $this->menus->mainMenuText(), $this->menus->mainMenuReplyKeyboard($userId));
            return;
        }

        $options = is_array($state['payload']['options'] ?? null) ? $state['payload']['options'] : [];
        $selected = $this->extractOptionKey($text);
        $packageId = isset($options[$selected]) ? (int) $options[$selected] : 0;
        $purchaseId = (int) ($state['payload']['purchase_id'] ?? 0);
        if ($packageId <= 0 || $purchaseId <= 0) {
            $this->telegram->sendMessage($chatId, $this->uiText->warning('گزینه نامعتبر است. لطفاً یکی از گزینه‌های لیست را انتخاب کنید.'));
            return;
        }

        $this->database->clearUserState($userId);
        $package = $this->database->getPackage($packageId);
        $purchase = $this->database->getUserPurchaseForRenewal($userId, $purchaseId);
        if ($package === null || !is_array($purchase)) {
            $this->telegram->sendMessage($chatId, $this->uiText->error('داده تمدید معتبر نیست.'));
            return;
        }

        $textOut = $this->uiText->multi(new UiTextBlock(
            title: '💳 <b>پرداخت تمدید</b>',
            lines: [
                new UiTextLine('🧾', 'سفارش', "<code>#{$purchaseId}</code>"),
                new UiTextLine('📦', 'پکیج تمدید', '<b>' . htmlspecialchars((string) $package['name']) . '</b>'),
                new UiTextLine('💵', 'مبلغ', '<b>' . (int) $package['price'] . '</b> تومان'),
            ],
            tipBlockquote: '💡 روش پرداخت مناسب را انتخاب کنید؛ بعد از تکمیل پرداخت، وضعیت تراکنش را بررسی کنید تا درخواست تمدید در صف تحویل قرار بگیرد.',
        ));
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
        $buttons[] = [UiLabels::BTN_BACK, UiLabels::BTN_MAIN, UiLabels::BTN_CANCEL];
        $this->database->setUserState($userId, 'renew.await_payment_method', ['purchase_id' => $purchaseId, 'package_id' => $packageId, 'stack' => ['renew.await_purchase', 'renew.await_package'], 'payment_method' => null, 'gateway' => null]);
        $this->telegram->sendMessage($chatId, $textOut, $this->uiKeyboard->replyMenu($buttons));
    }

    private function handleBuyPaymentSelectionState(int $chatId, int $userId, string $text, array $state): void
    {
        if ($text === UiLabels::BTN_BACK) {
            $typeId = (int) ($state['payload']['type_id'] ?? 0);
            if ($typeId > 0) {
                $this->showBuyPackageSelection($chatId, $userId, $typeId);
                return;
            }
            $this->database->clearUserState($userId);
            $this->telegram->sendMessage($chatId, $this->menus->mainMenuText(), $this->menus->mainMenuReplyKeyboard($userId));
            return;
        }
        if ($text === UiLabels::BTN_MAIN || $text === UiLabels::BTN_CANCEL || $text === KeyboardBuilder::BTN_BACK_MAIN) {
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
                $this->telegram->sendMessage($chatId, $this->uiText->error($msg));
                return;
            }
            $this->database->setUserState($userId, 'buy.done', [
                'type_id' => (int) ($state['payload']['type_id'] ?? 0),
                'package_id' => $packageId,
                'payment_method' => 'wallet',
                'gateway' => null,
            ]);
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
            $this->telegram->sendMessage($chatId, $this->uiText->warning('لطفاً یکی از روش‌های پرداخت را انتخاب کنید.'));
            return;
        }
    }

    private function handleRenewPaymentSelectionState(int $chatId, int $userId, string $text, array $state): void
    {
        if ($text === UiLabels::BTN_BACK) {
            $purchaseId = (int) ($state['payload']['purchase_id'] ?? 0);
            if ($purchaseId > 0) {
                $this->showMyConfigsWithReplyFlow($chatId, $userId);
                return;
            }
            $this->database->clearUserState($userId);
            $this->telegram->sendMessage($chatId, $this->menus->mainMenuText(), $this->menus->mainMenuReplyKeyboard($userId));
            return;
        }
        if ($text === UiLabels::BTN_MAIN || $text === UiLabels::BTN_CANCEL || $text === KeyboardBuilder::BTN_BACK_MAIN) {
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
                    'insufficient_balance' => 'موجودی کیف پول کافی نیست.',
                    'purchase_not_found' => 'سرویس قابل تمدید پیدا نشد.',
                    'test_not_renewable' => 'تمدید برای سرویس تست مجاز نیست.',
                    'type_mismatch' => 'پکیج انتخابی برای این سرویس معتبر نیست.',
                    default => 'پرداخت تمدید انجام نشد.',
                };
                $this->telegram->sendMessage($chatId, $this->uiText->error($msg));
                return;
            }
            $this->database->setUserState($userId, 'renew.done', [
                'purchase_id' => $purchaseId,
                'package_id' => $packageId,
                'payment_method' => 'wallet',
                'gateway' => null,
            ]);
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
            $this->telegram->sendMessage($chatId, $this->uiText->warning('لطفاً یکی از روش‌های پرداخت را انتخاب کنید.'));
            return;
        }
    }

    private function createPurchasePaymentByMethod(int $chatId, int $userId, int $packageId, string $methodLabel): void
    {
        $method = $methodLabel === self::PAY_CARD ? 'card' : ($methodLabel === self::PAY_CRYPTO ? 'crypto' : 'tetrapay');
        $package = $this->database->getPackage($packageId);
        if ($package === null) {
            $this->telegram->sendMessage($chatId, $this->uiText->error('پکیج پیدا نشد.'));
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
        $this->database->setUserState($userId, 'buy.await_payment_verify', ['payment_id' => $paymentId, 'gateway' => 'tetrapay', 'ok_text' => '✅ پرداخت تتراپی تایید شد. سفارش شما در صف تحویل قرار گرفت.', 'type_id' => 0, 'package_id' => $packageId, 'payment_method' => 'tetrapay']);
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
        $this->database->setUserState($userId, 'renew.await_payment_verify', ['payment_id' => $paymentId, 'gateway' => 'tetrapay', 'ok_text' => '✅ پرداخت تمدید تایید شد. درخواست شما در صف تحویل قرار گرفت.', 'purchase_id' => $purchaseId, 'package_id' => $packageId, 'payment_method' => 'tetrapay']);
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
            $this->database->setUserState($userId, 'buy.await_payment_verify', ['payment_id' => $paymentId, 'gateway' => $gateway, 'ok_text' => '✅ پرداخت SwapWallet تایید شد و سفارش در صف تحویل قرار گرفت.', 'type_id' => 0, 'package_id' => $packageId, 'payment_method' => $gateway]);
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
        $this->database->setUserState($userId, 'buy.await_payment_verify', ['payment_id' => $paymentId, 'gateway' => $gateway, 'ok_text' => '✅ پرداخت TronPays تایید شد و سفارش در صف تحویل قرار گرفت.', 'type_id' => 0, 'package_id' => $packageId, 'payment_method' => $gateway]);
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
            $this->database->setUserState($userId, 'renew.await_payment_verify', ['payment_id' => $paymentId, 'gateway' => $gateway, 'ok_text' => '✅ پرداخت SwapWallet تایید شد و درخواست تمدید در صف تحویل قرار گرفت.', 'purchase_id' => $purchaseId, 'package_id' => $packageId, 'payment_method' => $gateway]);
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
        $this->database->setUserState($userId, 'renew.await_payment_verify', ['payment_id' => $paymentId, 'gateway' => $gateway, 'ok_text' => '✅ پرداخت TronPays تایید شد و درخواست تمدید در صف تحویل قرار گرفت.', 'purchase_id' => $purchaseId, 'package_id' => $packageId, 'payment_method' => $gateway]);
        $this->sendGatewayPaymentIntro($chatId, "🧾 <b>پرداخت تمدید با TronPays</b>", $pendingId, $amount, $payUrl);
    }

    private function handleGatewayVerifyState(int $chatId, int $userId, string $text, array $state): void
    {
        if ($text === UiLabels::BTN_MAIN || $text === UiLabels::BTN_CANCEL || $text === KeyboardBuilder::BTN_BACK_MAIN) {
            $this->database->clearUserState($userId);
            $this->telegram->sendMessage($chatId, $this->menus->mainMenuText(), $this->menus->mainMenuReplyKeyboard($userId));
            return;
        }
        if ($text !== self::PAY_VERIFY) {
            $this->telegram->sendMessage($chatId, $this->uiText->info('برای بررسی وضعیت، دکمه بررسی پرداخت را بزنید.'));
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
            $this->telegram->sendMessage($chatId, $this->uiText->error('پرداخت پیدا نشد.'));
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
                if ((string) ($payment['kind'] ?? '') === 'purchase') {
                    $this->database->setUserState($userId, 'buy.done', [
                        'type_id' => (int) ($state['payload']['type_id'] ?? 0),
                        'package_id' => (int) ($state['payload']['package_id'] ?? 0),
                        'payment_method' => (string) ($state['payload']['payment_method'] ?? ''),
                        'gateway' => $gateway,
                    ]);
                } elseif ((string) ($payment['kind'] ?? '') === 'renewal') {
                    $this->database->setUserState($userId, 'renew.done', [
                        'purchase_id' => (int) ($state['payload']['purchase_id'] ?? 0),
                        'package_id' => (int) ($state['payload']['package_id'] ?? 0),
                        'payment_method' => (string) ($state['payload']['payment_method'] ?? ''),
                        'gateway' => $gateway,
                    ]);
                } else {
                    $this->database->clearUserState($userId);
                }
                $this->telegram->sendMessage($chatId, $okText);
                return;
            }
        }
        $this->telegram->sendMessage($chatId, $this->uiText->warning('پرداخت هنوز تایید نشده است.'));
    }

    private function handlePurchaseRulesAcceptState(int $chatId, int $userId, string $text, array $state): void
    {
        if ($text === UiLabels::BTN_MAIN || $text === UiLabels::BTN_CANCEL || $text === KeyboardBuilder::BTN_BACK_MAIN) {
            $this->database->clearUserState($userId);
            $this->telegram->sendMessage($chatId, $this->menus->mainMenuText(), $this->menus->mainMenuReplyKeyboard($userId));
            return;
        }
        if ($text === UiLabels::BTN_BACK || $text === KeyboardBuilder::BTN_BACK_TYPES) {
            $typeId = (int) ($state['payload']['type_id'] ?? 0);
            if ($typeId > 0) {
                $this->showBuyPackageSelection($chatId, $userId, $typeId);
                return;
            }
            $this->startBuyTypeReplyFlow($chatId, $userId);
            return;
        }
        if ($text !== self::ACCEPT_RULES) {
            $this->telegram->sendMessage($chatId, $this->uiText->warning('لطفاً گزینه تایید قوانین را انتخاب کنید.'));
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
        $textOut = $this->uiText->multi(new UiTextBlock(
            title: '💰 <b>پرداخت سفارش</b>',
            lines: [
                new UiTextLine('📦', 'پکیج', '<b>' . htmlspecialchars((string) $package['name']) . '</b>'),
                new UiTextLine('💵', 'قیمت', '<b>' . (int) $this->database->effectivePackagePrice($userId, $package) . '</b> تومان'),
            ],
            tipBlockquote: '💡 روش پرداخت را از روی دکمه‌ها انتخاب کنید؛ بعد از پرداخت، وضعیت تراکنش را بررسی کنید تا سفارش به صف تحویل وارد شود.',
        ));
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
        $buttons[] = [UiLabels::BTN_BACK, UiLabels::BTN_MAIN, UiLabels::BTN_CANCEL];
        $this->database->setUserState($userId, 'buy.await_payment_method', ['package_id' => $packageId, 'type_id' => (int) ($state['payload']['type_id'] ?? 0), 'stack' => ['buy.await_type', 'buy.await_package'], 'payment_method' => null, 'gateway' => null]);
        $this->telegram->sendMessage($chatId, $textOut, $this->uiKeyboard->replyMenu($buttons));
    }

    private function sendGatewayPaymentIntro(int $chatId, string $title, int $pendingId, int $amount, string $payUrl): void
    {
        $text = $this->uiText->paymentCreated(
            paymentId: $pendingId,
            amount: $amount,
            title: $title,
            tip: '💡 بعد از تکمیل پرداخت، وضعیت تراکنش را از طریق دکمه بررسی پرداخت پیگیری کنید تا سرویس شما سریع‌تر نهایی شود.',
        );
        if ($payUrl !== '') {
            $this->telegram->sendMessage($chatId, $text, $this->uiKeyboard->inlineUrl('💳 پرداخت', $payUrl));
        } else {
            $this->telegram->sendMessage($chatId, $text);
        }
        $this->telegram->sendMessage(
            $chatId,
            $this->uiText->info('برای بررسی پرداخت از دکمه زیر استفاده کنید.'),
            $this->uiKeyboard->replyMenu([[self::PAY_VERIFY], [UiLabels::BTN_BACK, UiLabels::BTN_MAIN]])
        );
    }

    private function ensurePurchaseAllowedForPackageMessage(int $chatId, int $userId, int $packageId): bool
    {
        if ($this->settings->get('shop_open', '1') !== '1') {
            $this->telegram->sendMessage($chatId, $this->uiText->warning('فروشگاه در حال حاضر بسته است.'));
            return false;
        }
        if ($this->settings->get('purchase_rules_enabled', '0') === '1' && !$this->database->hasAcceptedPurchaseRules($userId)) {
            $this->telegram->sendMessage($chatId, $this->uiText->warning('ابتدا قوانین خرید را بپذیرید.'));
            return false;
        }
        if ($this->settings->get('preorder_mode', '0') === '1' && !$this->database->packageHasAvailableStock($packageId)) {
            $this->telegram->sendMessage($chatId, $this->uiText->info('این پکیج در حال حاضر موجودی ندارد.'));
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
        return ['inline_keyboard' => [[['text' => '📢 عضویت در کانال', 'url' => $channelUrl]]]];
    }

    private function channelLockReplyKeyboard(): array
    {
        return $this->replyKeyboard([[KeyboardBuilder::BTN_CHECK_CHANNEL]]);
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
            KeyboardBuilder::BTN_CHECK_CHANNEL,
        ], true);
    }
}
