<?php

declare(strict_types=1);

namespace ConfigFlow\Bot;

final class MessageHandler
{
    private const PAY_WALLET = '[legacy] buttons.pay.wallet';
    private const PAY_CARD = '[legacy] buttons.pay.card';
    private const PAY_CRYPTO = '[legacy] buttons.pay.crypto';
    private const PAY_TETRAPAY = '[legacy] buttons.pay.tetrapay';
    private const PAY_SWAPWALLET = '[legacy] buttons.pay.swapwallet';
    private const PAY_TRONPAYS = '[legacy] buttons.pay.tronpays';
    private const PAY_VERIFY = '[legacy] buttons.pay.verify';
    private const ACCEPT_RULES = '[legacy] buttons.accept_rules';
    private const ADMIN_SERVICE_ADD = '[legacy] admin.types_packages.actions.add_service';
    private const ADMIN_SERVICE_EDIT = '[legacy] admin.types_packages.actions.service_edit';
    private const ADMIN_SERVICE_TARIFFS = '[legacy] admin.types_packages.actions.service_tariffs';
    private const ADMIN_SERVICE_INVENTORY = '[legacy] admin.types_packages.actions.service_inventory';
    private const ADMIN_SERVICE_PANEL_BIND = '[legacy] admin.types_packages.actions.service_panel_bind';
    private const ADMIN_SERVICE_TOGGLE = '[legacy] admin.types_packages.actions.service_toggle';
    private const ADMIN_SERVICE_DELETE = '[legacy] admin.types_packages.actions.service_delete';
    private const ADMIN_SERVICE_TARIFF_ADD = '[legacy] admin.types_packages.actions.service_tariff_add';
    private const ADMIN_SERVICE_STOCK_ADD = '[legacy] admin.types_packages.actions.service_stock_add';
    private const ADMIN_SERVICE_INVENTORY_REFRESH = '[legacy] admin.types_packages.actions.service_inventory_refresh';
    private const ADMIN_USERS_REFRESH = '[legacy] admin.users_stock.actions.users_refresh';
    private const ADMIN_USER_TOGGLE_STATUS = '[legacy] admin.users_stock.actions.user_toggle_status';
    private const ADMIN_USER_TOGGLE_AGENT = '[legacy] admin.users_stock.actions.user_toggle_agent';
    private const ADMIN_USER_BALANCE_ADD = '[legacy] admin.users_stock.actions.user_balance_add';
    private const ADMIN_USER_BALANCE_SUB = '[legacy] admin.users_stock.actions.user_balance_sub';
    private const ADMIN_STOCK_REFRESH = '[legacy] admin.users_stock.actions.stock_refresh';
    private const ADMIN_STOCK_ADD_CONFIG = '[legacy] admin.users_stock.actions.stock_add_config';
    private const ADMIN_STOCK_SEARCH = '[legacy] admin.users_stock.actions.stock_search';
    private const ADMIN_STOCK_SEARCH_CLEAR = '[legacy] admin.users_stock.actions.stock_search_clear';
    private const ADMIN_STOCK_EXPIRE_TOGGLE = '[legacy] admin.users_stock.actions.stock_expire_toggle';
    private const ADMIN_STOCK_DELETE_CONFIG = '[legacy] admin.users_stock.actions.stock_delete_config';
    private const ADMIN_PAYMENTS_REFRESH = '[legacy] admin.payments_requests.actions.payments_refresh';
    private const ADMIN_PAYMENT_APPROVE = '[legacy] admin.payments_requests.actions.payment_approve';
    private const ADMIN_PAYMENT_REJECT = '[legacy] admin.payments_requests.actions.payment_reject';
    private const ADMIN_PAYMENT_VERIFY_CHAIN = '[legacy] admin.payments_requests.actions.payment_verify_chain';
    private const ADMIN_REQUESTS_FREE = '[legacy] admin.payments_requests.actions.requests_free';
    private const ADMIN_REQUESTS_AGENCY = '[legacy] admin.payments_requests.actions.requests_agency';
    private const ADMIN_REQUESTS_PENDING = '[legacy] admin.ui.open.requests.list.filter_pending';
    private const ADMIN_REQUESTS_APPROVED = '[legacy] admin.ui.open.requests.list.filter_approved';
    private const ADMIN_REQUESTS_REJECTED = '[legacy] admin.ui.open.requests.list.filter_rejected';
    private const ADMIN_REQUEST_APPROVE = '[legacy] admin.payments_requests.actions.request_approve';
    private const ADMIN_REQUEST_REJECT = '[legacy] admin.payments_requests.actions.request_reject';
    private const ADMIN_SETTINGS_REFRESH = '[legacy] admin.settings_admins_pins.actions.settings_refresh';
    private const ADMIN_SETTINGS_EDIT = '[legacy] admin.settings_admins_pins.actions.settings_edit';
    private const ADMIN_SETTINGS_TOGGLE_BOT = '[legacy] admin.settings_admins_pins.actions.settings_toggle_bot';
    private const ADMIN_SETTINGS_TOGGLE_FREE_TEST = '[legacy] admin.settings_admins_pins.actions.settings_toggle_free_test';
    private const ADMIN_SETTINGS_TOGGLE_AGENCY = '[legacy] admin.settings_admins_pins.actions.settings_toggle_agency';
    private const ADMIN_SETTINGS_TOGGLE_GW_CARD = '[legacy] admin.settings_admins_pins.actions.settings_toggle_gw_card';
    private const ADMIN_SETTINGS_TOGGLE_GW_CRYPTO = '[legacy] admin.settings_admins_pins.actions.settings_toggle_gw_crypto';
    private const ADMIN_SETTINGS_TOGGLE_GW_TETRA = '[legacy] admin.settings_admins_pins.actions.settings_toggle_gw_tetra';
    private const ADMIN_SETTINGS_SET_CHANNEL = '[legacy] admin.settings_admins_pins.actions.settings_set_channel';
    private const ADMIN_ADMINS_ADD = '[legacy] admin.settings_admins_pins.actions.admins_add';
    private const ADMIN_ADMIN_DELETE = '[legacy] admin.settings_admins_pins.actions.admin_delete';
    private const ADMIN_PINS_ADD = '[legacy] admin.settings_admins_pins.actions.pins_add';
    private const ADMIN_PIN_SEND_ALL = '[legacy] admin.settings_admins_pins.actions.pin_send_all';
    private const ADMIN_PIN_EDIT = '[legacy] admin.settings_admins_pins.actions.pin_edit';
    private const ADMIN_PIN_DELETE = '[legacy] admin.settings_admins_pins.actions.pin_delete';
    private const ADMIN_AGENTS_REFRESH = '[legacy] admin.final_modules.actions.agents_refresh';
    private const ADMIN_AGENT_SET_PRICE = '[legacy] admin.final_modules.actions.agent_set_price';
    private const ADMIN_PANELS_REFRESH = '[legacy] admin.final_modules.actions.panels_refresh';
    private const ADMIN_PANELS_ADD = '[legacy] admin.final_modules.actions.panels_add';
    private const ADMIN_PANEL_TOGGLE = '[legacy] admin.final_modules.actions.panel_toggle';
    private const ADMIN_PANEL_DELETE = '[legacy] admin.final_modules.actions.panel_delete';
    private const ADMIN_PANEL_PKG_ADD = '[legacy] admin.final_modules.actions.panel_pkg_add';
    private const ADMIN_BROADCAST_SCOPE_ALL = '[legacy] admin.final_modules.actions.broadcast_scope_all';
    private const ADMIN_BROADCAST_SCOPE_USERS = '[legacy] admin.final_modules.actions.broadcast_scope_users';
    private const ADMIN_BROADCAST_SCOPE_AGENTS = '[legacy] admin.final_modules.actions.broadcast_scope_agents';
    private const ADMIN_BROADCAST_SCOPE_ADMINS = '[legacy] admin.final_modules.actions.broadcast_scope_admins';
    private const ADMIN_BROADCAST_SEND = '[legacy] admin.final_modules.actions.broadcast_send';
    private const ADMIN_DELIVERIES_REFRESH = '[legacy] admin.final_modules.actions.deliveries_refresh';
    private const ADMIN_DELIVERY_DO = '[legacy] admin.final_modules.actions.delivery_do';
    private const ADMIN_GROUPOPS_SET_GROUP = '[legacy] admin.final_modules.actions.groupops_set_group';
    private const ADMIN_GROUPOPS_RESTORE = '[legacy] admin.final_modules.actions.groupops_restore';
    private const ADMIN_FREETEST_RULE = '[legacy] admin.final_modules.actions.freetest_rule';
    private const ADMIN_FREETEST_RESET = '[legacy] admin.final_modules.actions.freetest_reset';

    public function __construct(
        private Database $database,
        private TelegramClient $telegram,
        private SettingsRepository $settings,
        private MenuService $menus,
        private PaymentGatewayService $gateways,
        private ?UiTextCatalogInterface $uiText = null,
        private ?UiKeyboardFactoryInterface $uiKeyboard = null,
        private ?UiJsonCatalog $catalog = null,
        private ?UiMessageRenderer $messageRenderer = null,
    ) {
        $this->uiText ??= new UiTextCatalog();
        $this->uiKeyboard ??= new UiKeyboardFactory();
        $this->catalog ??= new UiJsonCatalog();
        $this->messageRenderer ??= new UiMessageRenderer($this->catalog);
    }


    private function uiConst(string $value): string
    {
        if (str_starts_with($value, '[legacy] ')) {
            return $this->catalog->get(substr($value, 9));
        }

        return $value;
    }

    private function isMainMenuInput(string $text): bool
    {
        return $text === UiLabels::main($this->catalog)
            || $text === KeyboardBuilder::backMain();
    }

    private function isAdminExitInput(string $text): bool
    {
        return $text === $this->catalog->get('buttons.admin.exit_panel')
            || $this->isMainMenuInput($text);
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
            if ($text === KeyboardBuilder::checkChannel()) {
                if ($this->checkChannelMembership($userId)) {
                    $this->telegram->sendMessage($chatId, $this->menus->mainMenuText(), $this->menus->mainMenuReplyKeyboard($userId));
                } else {
                    $this->telegram->sendMessage($chatId, $this->channelLockText(), $this->channelLockKeyboard());
                    $this->telegram->sendMessage($chatId, $this->catalog->get('messages.channel.after_join_prompt'), $this->channelLockReplyKeyboard());
                }
                return;
            }
            $this->telegram->sendMessage($chatId, $this->channelLockText(), $this->channelLockKeyboard());
            $this->telegram->sendMessage($chatId, $this->catalog->get('messages.channel.after_join_prompt'), $this->channelLockReplyKeyboard());
            return;
        }

        $state = $this->database->getUserState($userId);
        if ($state === null) {
            if ($this->handleMainReplyKeyboardInput($chatId, $messageId, $userId, $fromUser, $text)) {
                return;
            }
            return;
        }

        if (($text === KeyboardBuilder::admin() || $text === $this->catalog->get('admin.common.back_to_panel')) && $this->database->isAdminUser($userId)) {
            $this->database->clearUserState($userId);
            $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('admin.common.legacy_state_reset')));
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

        if ($state['state_name'] === 'buy.panel.await_service') {
            $this->handlePanelServiceSelectionState($chatId, $userId, $text, $state);
            return;
        }

        if ($state['state_name'] === 'buy.service.await_service') {
            $this->handleServiceSelectionState($chatId, $userId, $text, $state);
            return;
        }

        if ($state['state_name'] === 'buy.service.await_tariff') {
            $this->handleServiceTariffSelectionState($chatId, $userId, $text, $state);
            return;
        }

        if ($state['state_name'] === 'buy.service.await_volume') {
            $this->handleServiceTariffVolumeInputState($chatId, $userId, $text, $state);
            return;
        }

        if ($state['state_name'] === 'buy.panel.await_volume') {
            $this->handlePanelVolumeInputState($chatId, $userId, $text, $state);
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

        if ($state['state_name'] === 'buy.panel.await_payment_method') {
            $this->handlePanelBuyPaymentSelectionState($chatId, $userId, $text, $state);
            return;
        }

        if ($state['state_name'] === 'buy.service.await_payment_method') {
            $this->handleServiceBuyPaymentSelectionState($chatId, $userId, $text, $state);
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
            $state['state_name'] === 'admin.service.landing'
            || $state['state_name'] === 'admin.service.type_select'
            || $state['state_name'] === 'admin.service.list'
            || $state['state_name'] === 'admin.type.create'
            || $state['state_name'] === 'admin.type.edit'
            || $state['state_name'] === 'admin.type.delete'
            || $state['state_name'] === 'admin.service.create'
            || $state['state_name'] === 'admin.service.view'
            || $state['state_name'] === 'admin.service.edit'
            || $state['state_name'] === 'admin.service.tariffs'
            || $state['state_name'] === 'admin.service.inventory_bridge'
            || $state['state_name'] === 'admin.service.tariff.create'
            || $state['state_name'] === 'admin.service.tariff.edit'
            || $state['state_name'] === 'admin.service.tariff.delete'
            || $state['state_name'] === 'admin.service.inventory'
            || $state['state_name'] === 'admin.service.inventory.add'
            || $state['state_name'] === 'admin.service.inventory.detail'
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
            if ($text === $this->catalog->get('admin.ui.stock_results_button')) {
                $route = (string) (($state['payload'] ?? [])['route'] ?? '');
                if ($route !== '') {
                    $this->database->clearUserState($userId);
                    $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('admin.ui.expired_route')));
                    $this->openAdminRoot($chatId, $userId);
                }
                return;
            }
            if ($text === KeyboardBuilder::admin()) {
                $this->database->clearUserState($userId);
                $this->openAdminRoot($chatId, $userId);
                return;
            }
        }

        if ($state['state_name'] === 'await_wallet_amount') {
            if ($text === KeyboardBuilder::backMain()) {
                $this->database->clearUserState($userId);
                $this->telegram->sendMessage($chatId, $this->menus->mainMenuText(), $this->menus->mainMenuReplyKeyboard($userId));
                return;
            }
            if ($text === KeyboardBuilder::backAccount()) {
                $this->database->clearUserState($userId);
                $this->telegram->sendMessage($chatId, $this->menus->profileText($userId), $this->menus->accountMenuReplyKeyboard());
                return;
            }
            if ($text === '' || str_starts_with($text, '/')) {
                return;
            }
            $amount = (int) preg_replace('/\D+/', '', $text);
            if ($amount <= 0) {
                $this->telegram->sendMessage($chatId, $this->uiText->error($this->catalog->get('messages.user.wallet.invalid_amount')));
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
                $this->catalog->get('messages.user.wallet.request_submitted', [
                    'payment_id' => $paymentId,
                    'amount' => $amount,
                ])
            );

            $adminKeyboard = $this->replyKeyboard([
                [
                    $this->catalog->get('admin.payments.actions.approve', ['payment_id' => $paymentId]),
                    $this->catalog->get('admin.payments.actions.reject', ['payment_id' => $paymentId]),
                ],
                [KeyboardBuilder::admin()],
            ]);
            foreach (Config::adminIds() as $adminId) {
                $this->telegram->sendMessage(
                    (int) $adminId,
                    $this->catalog->get('admin.payments.wallet_charge_new', [
                        'payment_id' => $paymentId,
                        'user_id' => $userId,
                        'amount' => $amount,
                    ]),
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
                $this->telegram->sendMessage($chatId, $this->catalog->get('messages.user.payment.receipt.missing_purchase'));
                return;
            }

            $this->database->attachPaymentReceipt($paymentId, $fileId ?: null, $receiptText);
            $this->database->clearUserState($userId);
            $this->telegram->sendMessage(
                $chatId,
                $this->catalog->get('messages.user.payment.receipt.saved_purchase', ['payment_id' => $paymentId])
            );

            $adminKeyboard = $this->replyKeyboard([
                [
                    $this->catalog->get('admin.payments.actions.approve', ['payment_id' => $paymentId]),
                    $this->catalog->get('admin.payments.actions.reject', ['payment_id' => $paymentId]),
                ],
                [KeyboardBuilder::admin()],
            ]);
            foreach (Config::adminIds() as $adminId) {
                $this->telegram->sendMessage(
                    (int) $adminId,
                    $this->catalog->get('admin.payments.card_receipt_new', [
                        'payment_id' => $paymentId,
                        'user_id' => $userId,
                        'note_line' => $receiptText ? $this->catalog->get('admin.common.note_line', ['note' => htmlspecialchars($receiptText)]) : '',
                    ]),
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
                $this->telegram->sendMessage($chatId, $this->catalog->get('messages.user.payment.receipt.missing_renew'));
                return;
            }

            $this->database->attachPaymentReceipt($paymentId, $fileId ?: null, $receiptText);
            $this->database->clearUserState($userId);
            $this->telegram->sendMessage(
                $chatId,
                $this->catalog->get('messages.user.payment.receipt.saved_renew', ['payment_id' => $paymentId])
            );

            $adminKeyboard = $this->replyKeyboard([
                [
                    $this->catalog->get('admin.payments.actions.approve', ['payment_id' => $paymentId]),
                    $this->catalog->get('admin.payments.actions.reject', ['payment_id' => $paymentId]),
                ],
                [KeyboardBuilder::admin()],
            ]);
            foreach (Config::adminIds() as $adminId) {
                $this->telegram->sendMessage(
                    (int) $adminId,
                    $this->catalog->get('admin.payments.renew_receipt_new', [
                        'payment_id' => $paymentId,
                        'user_id' => $userId,
                        'note_line' => $receiptText ? $this->catalog->get('admin.common.note_line', ['note' => htmlspecialchars($receiptText)]) : '',
                    ]),
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
                $this->telegram->sendMessage($chatId, $this->catalog->get('messages.user.payment.tx.invalid'));
                return;
            }
            if (strlen($txHash) < 10) {
                $this->telegram->sendMessage($chatId, $this->catalog->get('messages.user.payment.tx.invalid_length'));
                return;
            }

            $ok = $this->database->submitCryptoTxHash($paymentId, $txHash, $claimedAmount);
            if (!$ok) {
                $this->telegram->sendMessage($chatId, $this->catalog->get('messages.user.payment.tx.submit_failed'));
                return;
            }

            $this->database->clearUserState($userId);
            $this->telegram->sendMessage(
                $chatId,
                $this->catalog->get('messages.user.payment.tx.saved_purchase', ['payment_id' => $paymentId])
            );

            $adminKeyboard = $this->replyKeyboard([
                [
                    $this->catalog->get('admin.payments.actions.approve', ['payment_id' => $paymentId]),
                    $this->catalog->get('admin.payments.actions.reject', ['payment_id' => $paymentId]),
                ],
                [KeyboardBuilder::admin()],
            ]);
            foreach (Config::adminIds() as $adminId) {
                $this->telegram->sendMessage(
                    (int) $adminId,
                    $this->catalog->get('admin.payments.tx_new', [
                        'payment_id' => $paymentId,
                        'user_id' => $userId,
                        'tx_hash' => htmlspecialchars($txHash),
                        'amount_line' => $claimedAmount !== null ? $this->catalog->get('admin.payments.amount_line', ['amount' => $claimedAmount]) : '',
                    ]),
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
                $this->telegram->sendMessage($chatId, $this->catalog->get('messages.user.payment.tx.invalid'));
                return;
            }
            if (strlen($txHash) < 10) {
                $this->telegram->sendMessage($chatId, $this->catalog->get('messages.user.payment.tx.invalid_length'));
                return;
            }

            $ok = $this->database->submitCryptoTxHash($paymentId, $txHash, $claimedAmount);
            if (!$ok) {
                $this->telegram->sendMessage($chatId, $this->catalog->get('messages.user.payment.tx.submit_failed'));
                return;
            }

            $this->database->clearUserState($userId);
            $this->telegram->sendMessage(
                $chatId,
                $this->catalog->get('messages.user.payment.tx.saved_renew', ['payment_id' => $paymentId])
            );

            $adminKeyboard = $this->replyKeyboard([
                [
                    $this->catalog->get('admin.payments.actions.approve', ['payment_id' => $paymentId]),
                    $this->catalog->get('admin.payments.actions.reject', ['payment_id' => $paymentId]),
                ],
                [KeyboardBuilder::admin()],
            ]);
            foreach (Config::adminIds() as $adminId) {
                $this->telegram->sendMessage(
                    (int) $adminId,
                    $this->catalog->get('admin.payments.tx_renew_new', [
                        'payment_id' => $paymentId,
                        'user_id' => $userId,
                        'tx_hash' => htmlspecialchars($txHash),
                        'amount_line' => $claimedAmount !== null ? $this->catalog->get('admin.payments.amount_line', ['amount' => $claimedAmount]) : '',
                    ]),
                    $adminKeyboard
                );
            }
            return;
        }

        if ($state['state_name'] === 'await_free_test_note') {
            if ($this->isBotMenuButton($text)) {
                $this->telegram->sendMessage($chatId, $this->catalog->get('messages.user.free_test.note_as_text'));
                return;
            }
            if ($text === '' || str_starts_with($text, '/')) {
                $this->telegram->sendMessage($chatId, $this->catalog->get('messages.user.free_test.note_required'));
                return;
            }

            $this->database->clearUserState($userId);
            $requestId = $this->database->createFreeTestRequest($userId, $text);
            $this->telegram->sendMessage(
                $chatId,
                $this->catalog->get('messages.user.free_test.request_submitted', ['request_id' => $requestId])
            );

            foreach (Config::adminIds() as $adminId) {
                $this->telegram->sendMessage(
                    (int) $adminId,
                    $this->catalog->get('admin.requests.new_free_request', [
                        'request_id' => $requestId,
                        'user_id' => $userId,
                        'note' => htmlspecialchars($text),
                    ]),
                    $this->replyKeyboard([
                        [$this->catalog->get('admin.requests.actions.open_free', ['request_id' => $requestId])],
                        [$this->catalog->get('buttons.admin.requests')],
                        [KeyboardBuilder::admin()],
                    ])
                );
            }
            return;
        }

        if ($state['state_name'] === 'await_agency_request') {
            if ($this->isBotMenuButton($text)) {
                $this->telegram->sendMessage($chatId, $this->catalog->get('messages.user.agency.note_as_text'));
                return;
            }
            if ($text === '' || str_starts_with($text, '/')) {
                $this->telegram->sendMessage($chatId, $this->catalog->get('messages.user.agency.note_required'));
                return;
            }

            $this->database->clearUserState($userId);
            $requestId = $this->database->createAgencyRequest($userId, $text);
            $this->telegram->sendMessage(
                $chatId,
                $this->catalog->get('messages.user.agency.request_submitted', ['request_id' => $requestId])
            );

            foreach (Config::adminIds() as $adminId) {
                $this->telegram->sendMessage(
                    (int) $adminId,
                    $this->catalog->get('admin.requests.new_agency_request', [
                        'request_id' => $requestId,
                        'user_id' => $userId,
                        'note' => htmlspecialchars($text),
                    ]),
                    $this->replyKeyboard([
                        [$this->catalog->get('admin.requests.actions.open_agency', ['request_id' => $requestId])],
                        [$this->catalog->get('buttons.admin.requests')],
                        [KeyboardBuilder::admin()],
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
                $this->telegram->sendMessage($chatId, $this->catalog->get('admin.legacy.errors.invalid_freetest_rule_format'));
                return;
            }
            $this->database->saveFreeTestRule($packageId, $maxClaims, $cooldownDays, true);
            $this->database->clearUserState($userId);
            $this->telegram->sendMessage($chatId, $this->catalog->get('admin.legacy.success.freetest_rule_saved'));
            return;
        }

        if ($state['state_name'] === 'await_admin_free_test_reset_user') {
            if (!in_array($userId, Config::adminIds(), true)) {
                $this->database->clearUserState($userId);
                return;
            }
            $targetUserId = (int) preg_replace('/\D+/', '', $text);
            if ($targetUserId <= 0) {
                $this->telegram->sendMessage($chatId, $this->catalog->get('admin.legacy.errors.valid_numeric_user_id_required'));
                return;
            }
            $this->database->resetFreeTestQuota($targetUserId);
            $this->database->clearUserState($userId);
            $this->telegram->sendMessage($chatId, $this->catalog->get('admin.legacy.success.freetest_user_quota_reset', ['target_user_id' => $targetUserId]));
            return;
        }

        if ($state['state_name'] === 'await_admin_request_note') {
            if (!in_array($userId, Config::adminIds(), true)) {
                $this->database->clearUserState($userId);
                return;
            }
            if ($this->isMainMenuInput($text)) {
                $this->openAdminRoot($chatId, $userId);
                return;
            }

            $payload = $state['payload'] ?? [];
            $requestKind = (string) ($payload['request_kind'] ?? '');
            $requestId = (int) ($payload['request_id'] ?? 0);
            $approve = ((int) ($payload['approve'] ?? 0)) === 1;
            if ($text === UiLabels::back($this->catalog)) {
                if ($requestKind === 'free' || $requestKind === 'agency') {
                    $this->openAdminRequestView($chatId, $userId, $requestKind, $requestId, 'pending', $this->uiText->info($this->catalog->get('admin.legacy.info.request_note_legacy_redirect')));
                } else {
                    $this->openAdminRequestsList($chatId, $userId, '', 'pending', $this->uiText->info($this->catalog->get('admin.legacy.info.request_note_legacy_redirect')));
                }
                return;
            }
            if ($requestId <= 0 || ($requestKind !== 'free' && $requestKind !== 'agency')) {
                $this->database->clearUserState($userId);
                $this->telegram->sendMessage($chatId, $this->catalog->get('admin.legacy.errors.invalid_request_info'));
                return;
            }
            if ($text === '' || str_starts_with($text, '/')) {
                $this->telegram->sendMessage($chatId, $this->catalog->get('admin.legacy.errors.admin_note_required'));
                return;
            }
            $adminNote = trim($text) === '-' ? null : trim($text);

            $result = $requestKind === 'free'
                ? $this->database->reviewFreeTestRequest($requestId, $approve, $adminNote)
                : $this->database->reviewAgencyRequest($requestId, $approve, $adminNote);
            if (!($result['ok'] ?? false)) {
                $msg = (($result['error'] ?? '') === 'already_reviewed')
                    ? $this->catalog->get('admin.legacy.errors.request_already_reviewed')
                    : $this->catalog->get('admin.legacy.errors.request_review_failed');
                $this->telegram->sendMessage($chatId, $this->catalog->get('admin.ui.audit.error_prefix', ['msg' => $msg]));
                $this->openAdminRequestsList($chatId, $userId, $requestKind, 'pending');
                return;
            }

            $this->telegram->sendMessage(
                $chatId,
                $this->messageRenderer->render('admin.payments_requests.success.request_reviewed', [
                    'request_label' => $requestKind === 'free'
                        ? $this->catalog->get('admin.payments_requests.labels.request_free')
                        : $this->catalog->get('admin.payments_requests.labels.request_agency'),
                    'request_id' => $requestId,
                    'status_text' => $approve
                        ? $this->catalog->get('admin.payments_requests.labels.status_approved')
                        : $this->catalog->get('admin.payments_requests.labels.status_rejected'),
                ])
            );

            $userNotice = $approve
                ? ($requestKind === 'free' ? $this->catalog->get('admin.legacy.user_notice.free_approved') : $this->catalog->get('admin.legacy.user_notice.agency_approved'))
                : ($requestKind === 'free' ? $this->catalog->get('admin.legacy.user_notice.free_rejected') : $this->catalog->get('admin.legacy.user_notice.agency_rejected'));
            $noteLine = $adminNote !== null && $adminNote !== ''
                ? $this->catalog->get('admin.legacy.user_notice.admin_note', ['note' => htmlspecialchars($adminNote)])
                : '';
            $userNotice = $this->messageRenderer->render('admin.common.notice_with_optional_note', [
                'notice' => $userNotice,
                'note_line' => $noteLine,
            ], ['notice', 'note_line']);
            $this->telegram->sendMessage((int) ($result['user_id'] ?? 0), $userNotice);
            $this->openAdminRequestsList($chatId, $userId, $requestKind, 'pending', $this->uiText->info($this->catalog->get('admin.legacy.info.legacy_path_not_canonical')));
            return;
        }

        if ($state['state_name'] === 'await_admin_type_name') {
            if (!in_array($userId, Config::adminIds(), true)) {
                $this->database->clearUserState($userId);
                return;
            }
            if ($this->isMainMenuInput($text)) {
                $this->openAdminRoot($chatId, $userId);
                return;
            }
            if ($text === UiLabels::back($this->catalog)) {
                $this->openAdminTypesList($chatId, $userId, $this->uiText->info($this->catalog->get('admin.legacy.info.type_name_legacy_redirect')));
                return;
            }
            $this->openAdminTypesList($chatId, $userId, $this->uiText->info('ایجاد Type از مسیر legacy غیرفعال شده است.'));
            return;
        }

        if ($state['state_name'] === 'await_admin_package') {
            if (!in_array($userId, Config::adminIds(), true)) {
                $this->database->clearUserState($userId);
                return;
            }
            if ($this->isMainMenuInput($text)) {
                $this->openAdminRoot($chatId, $userId);
                return;
            }
            $payload = $state['payload'] ?? [];
            $typeId = (int) ($payload['type_id'] ?? 0);
            if ($text === UiLabels::back($this->catalog)) {
                if ($typeId > 0) {
                    $this->openAdminTypeView($chatId, $userId, $typeId, $this->uiText->info($this->catalog->get('admin.legacy.info.legacy_use_canonical')));
                } else {
                    $this->openAdminTypesList($chatId, $userId, $this->uiText->info($this->catalog->get('admin.legacy.info.legacy_use_canonical')));
                }
                return;
            }
            if ($typeId > 0) {
                $this->openAdminTypeView($chatId, $userId, $typeId, $this->uiText->info('ایجاد Package از مسیر legacy غیرفعال شده است.'));
                return;
            }
            $this->openAdminTypesList($chatId, $userId, $this->uiText->info('ایجاد Package از مسیر legacy غیرفعال شده است.'));
            return;
        }

        if ($state['state_name'] === 'await_admin_user_balance') {
            if (!in_array($userId, Config::adminIds(), true)) {
                $this->database->clearUserState($userId);
                return;
            }
            if ($this->isMainMenuInput($text)) {
                $this->openAdminRoot($chatId, $userId);
                return;
            }
            $payload = $state['payload'] ?? [];
            $targetUid = (int) ($payload['target_user_id'] ?? 0);
            $mode = (string) ($payload['mode'] ?? 'add');
            if ($text === UiLabels::back($this->catalog)) {
                $this->openAdminUserView($chatId, $userId, $targetUid, $this->uiText->info($this->catalog->get('admin.legacy.info.user_balance_legacy_redirect')));
                return;
            }
            $amount = (int) preg_replace('/\D+/', '', $text);
            if ($targetUid <= 0 || $amount <= 0) {
                $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('admin.legacy.errors.valid_amount_required')));
                return;
            }
            $delta = $mode === 'sub' ? -$amount : $amount;
            $this->database->updateUserBalance($targetUid, $delta);
            $this->openAdminUserView($chatId, $userId, $targetUid, $this->uiText->info($this->catalog->get('admin.legacy.info.legacy_path_not_canonical')));
            return;
        }

        if ($state['state_name'] === 'await_admin_add_config') {
            if (!in_array($userId, Config::adminIds(), true)) {
                $this->database->clearUserState($userId);
                return;
            }
            if ($this->isMainMenuInput($text)) {
                $this->openAdminRoot($chatId, $userId);
                return;
            }
            $payload = $state['payload'] ?? [];
            $typeId = (int) ($payload['type_id'] ?? 0);
            $packageId = (int) ($payload['package_id'] ?? 0);
            if ($text === UiLabels::back($this->catalog)) {
                $this->openAdminStockConfigsView($chatId, $userId, $typeId, $packageId, '', $this->uiText->info($this->catalog->get('admin.legacy.info.stock_add_config_legacy_redirect')));
                return;
            }
            $raw = trim((string) ($message['text'] ?? ''));
            if ($raw === '' || str_starts_with($raw, '/')) {
                $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('admin.legacy.errors.config_text_required')));
                return;
            }
            $chunks = preg_split('/\n---\n/', $raw) ?: [];
            if (count($chunks) < 2) {
                $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('admin.legacy.errors.invalid_config_separator')));
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
                $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('admin.legacy.errors.invalid_service_or_config_text')));
                return;
            }
            $configId = $this->database->addConfig($typeId, $packageId, $serviceName, $configText, $inquiry);
            $this->openAdminStockConfigsView($chatId, $userId, $typeId, $packageId, '', $this->uiText->info($this->catalog->get('admin.legacy.info.config_created_noncanonical', ['config_id' => $configId])));
            return;
        }

        if ($state['state_name'] === 'await_admin_stock_search') {
            if (!in_array($userId, Config::adminIds(), true)) {
                $this->database->clearUserState($userId);
                return;
            }
            if ($this->isMainMenuInput($text)) {
                $this->openAdminRoot($chatId, $userId);
                return;
            }
            $payload = $state['payload'] ?? [];
            $packageId = (int) ($payload['package_id'] ?? 0);
            $typeId = (int) ($payload['type_id'] ?? 0);
            if ($text === UiLabels::back($this->catalog)) {
                $this->openAdminStockConfigsView($chatId, $userId, $typeId, $packageId, '', $this->uiText->info($this->catalog->get('admin.legacy.info.settings_legacy_redirect')));
                return;
            }
            $query = trim((string) ($message['text'] ?? ''));
            if ($query === '-' || $query === '—') {
                $query = '';
            }
            $this->openAdminStockConfigsView($chatId, $userId, $typeId, $packageId, $query, $this->uiText->info($this->catalog->get('admin.legacy.info.search_legacy_redirect')));
            return;
        }

        if ($state['state_name'] === 'await_admin_add_admin') {
            if (!in_array($userId, Config::adminIds(), true)) {
                $this->database->clearUserState($userId);
                return;
            }
            if ($this->isMainMenuInput($text)) {
                $this->openAdminRoot($chatId, $userId);
                return;
            }
            if ($text === UiLabels::back($this->catalog)) {
                $this->openAdminAdminsList($chatId, $userId, $this->uiText->info($this->catalog->get('admin.legacy.info.settings_legacy_redirect')));
                return;
            }
            $targetUid = (int) preg_replace('/\D+/', '', $text);
            if ($targetUid <= 0) {
                $this->telegram->sendMessage($chatId, $this->catalog->get('admin.legacy.errors.valid_numeric_user_id_required'));
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
            $this->openAdminAdminView($chatId, $userId, $targetUid, $this->uiText->info($this->catalog->get('admin.legacy.info.add_admin_legacy_redirect')));
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
                $this->telegram->sendMessage($chatId, $this->catalog->get('admin.legacy.errors.valid_price_required'));
                return;
            }
            $this->database->setAgencyPrice($agentId, $packageId, $price);
            $this->database->clearUserState($userId);
            $this->telegram->sendMessage($chatId, $this->catalog->get('admin.legacy.success.agent_price_saved', ['agent_id' => $agentId, 'package_id' => $packageId, 'price' => $price]));
            return;
        }

        if ($state['state_name'] === 'await_panel_add' || $state['state_name'] === 'await_panel_pkg_add') {
            if (!$this->database->isAdminUser($userId)) {
                $this->database->clearUserState($userId);
                return;
            }
            $this->openAdminPanelSettings(
                $chatId,
                $userId,
                $this->uiText->info($this->catalog->get('admin.final_modules.info.panels_legacy_removed'))
            );
            return;
        }

        if ($state['state_name'] === 'await_worker_api_key') {
            if (!$this->database->isAdminUser($userId)) {
                $this->database->clearUserState($userId);
                return;
            }
            $key = trim($text);
            if ($key === '' || strlen($key) < 8) {
                $this->telegram->sendMessage($chatId, $this->catalog->get('admin.legacy.errors.invalid_worker_api_key'));
                return;
            }
            $this->database->clearUserState($userId);
            $this->database->pdo()->prepare('INSERT INTO settings (`key`,`value`) VALUES (\'worker_api_key\', :v) ON DUPLICATE KEY UPDATE `value`=VALUES(`value`)')->execute(['v' => $key]);
            $this->telegram->sendMessage($chatId, $this->catalog->get('admin.legacy.success.worker_api_key_saved'));
            return;
        }

        if ($state['state_name'] === 'await_worker_api_port') {
            if (!$this->database->isAdminUser($userId)) {
                $this->database->clearUserState($userId);
                return;
            }
            $port = (int) preg_replace('/\D+/', '', $text);
            if ($port < 1 || $port > 65535) {
                $this->telegram->sendMessage($chatId, $this->catalog->get('admin.legacy.errors.invalid_worker_api_port'));
                return;
            }
            $this->database->clearUserState($userId);
            $this->database->pdo()->prepare('INSERT INTO settings (`key`,`value`) VALUES (\'worker_api_port\', :v) ON DUPLICATE KEY UPDATE `value`=VALUES(`value`)')->execute(['v' => (string) $port]);
            $this->telegram->sendMessage($chatId, $this->catalog->get('admin.legacy.success.worker_api_port_saved'));
            return;
        }

        if ($state['state_name'] === 'await_php_worker_poll_interval') {
            if (!$this->database->isAdminUser($userId)) {
                $this->database->clearUserState($userId);
                return;
            }
            $interval = (int) preg_replace('/\\D+/', '', $text);
            if ($interval < 3 || $interval > 3600) {
                $this->telegram->sendMessage($chatId, $this->catalog->get('admin.legacy.errors.invalid_poll_interval'));
                return;
            }
            $this->settings->set('php_worker_poll_interval', (string) $interval);
            $this->database->clearUserState($userId);
            $this->telegram->sendMessage($chatId, $this->catalog->get('admin.legacy.success.poll_interval_saved', ['interval' => $interval]));
            return;
        }

        if ($state['state_name'] === 'await_admin_set_channel') {
            if (!$this->database->isAdminUser($userId)) {
                $this->database->clearUserState($userId);
                return;
            }
            if ($this->isMainMenuInput($text)) {
                $this->openAdminRoot($chatId, $userId);
                return;
            }
            if ($text === UiLabels::back($this->catalog)) {
                $this->openAdminSettingsView($chatId, $userId, $this->uiText->info($this->catalog->get('admin.legacy.info.settings_legacy_redirect')));
                return;
            }
            $value = trim($text);
            if ($value === '') {
                $this->telegram->sendMessage($chatId, $this->catalog->get('admin.legacy.errors.channel_value_empty'));
                return;
            }
            $channelId = $value === '-' ? '' : $value;
            $this->settings->set('channel_id', $channelId);
            $msg = $channelId === '' ? $this->catalog->get('admin.legacy.success.channel_lock_disabled') : $this->catalog->get('admin.legacy.success.channel_lock_saved', ['channel_id' => htmlspecialchars($channelId)]);
            $this->openAdminSettingsView($chatId, $userId, $this->uiText->info($this->catalog->get('admin.legacy.info.noncanonical_prefix', ['msg' => $msg])));
            return;
        }

        if ($state['state_name'] === 'await_admin_group_id') {
            if (!$this->database->isAdminUser($userId)) {
                $this->database->clearUserState($userId);
                return;
            }
            $value = trim($text);
            if ($value === '') {
                $this->telegram->sendMessage($chatId, $this->catalog->get('admin.legacy.errors.group_id_empty'));
                return;
            }
            if ($value !== '-' && !preg_match('/^-?\d+$/', $value)) {
                $this->telegram->sendMessage($chatId, $this->catalog->get('admin.legacy.errors.group_id_numeric_or_dash'));
                return;
            }
            $groupId = $value === '-' ? '' : $value;
            $this->settings->set('group_id', $groupId);
            $this->database->clearUserState($userId);
            $this->telegram->sendMessage(
                $chatId,
                $groupId === '' ? $this->catalog->get('admin.legacy.success.group_id_disabled') : $this->catalog->get('admin.legacy.success.group_id_saved', ['group_id' => htmlspecialchars($groupId)])
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
                $this->telegram->sendMessage($chatId, $this->catalog->get('admin.legacy.errors.valid_json_input_not_sent'));
                return;
            }
            $data = json_decode($raw, true);
            $settings = is_array($data) ? ($data['settings'] ?? null) : null;
            if (!is_array($settings)) {
                $this->telegram->sendMessage($chatId, $this->catalog->get('admin.legacy.errors.invalid_json_missing_settings'));
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
            $this->telegram->sendMessage($chatId, $this->catalog->get('admin.legacy.success.settings_restored_count', ['count' => $count]));
            $this->sendToGroupTopic('backup', $this->catalog->get('admin.legacy.info.settings_restored_group_topic', ['user_id' => $userId, 'count' => $count]));
            return;
        }



        if ($state['state_name'] === 'await_admin_pin_add') {
            if (!$this->database->isAdminUser($userId)) {
                $this->database->clearUserState($userId);
                return;
            }
            if ($this->isMainMenuInput($text)) {
                $this->openAdminRoot($chatId, $userId);
                return;
            }
            if ($text === UiLabels::back($this->catalog)) {
                $this->openAdminPinsList($chatId, $userId, $this->uiText->info($this->catalog->get('admin.legacy.info.settings_legacy_redirect')));
                return;
            }
            $body = trim((string) ($message['text'] ?? ''));
            if ($body === '') {
                $this->telegram->sendMessage($chatId, $this->catalog->get('admin.legacy.errors.pin_message_empty'));
                return;
            }
            $pinId = $this->database->addPinnedMessage($body);
            $this->openAdminPinView($chatId, $userId, $pinId, $this->uiText->info($this->catalog->get('admin.legacy.info.add_admin_legacy_redirect')));
            return;
        }

        if ($state['state_name'] === 'await_admin_pin_edit') {
            if (!$this->database->isAdminUser($userId)) {
                $this->database->clearUserState($userId);
                return;
            }
            $pinId = (int) (($state['payload'] ?? [])['pin_id'] ?? 0);
            if ($this->isMainMenuInput($text)) {
                $this->openAdminRoot($chatId, $userId);
                return;
            }
            if ($text === UiLabels::back($this->catalog)) {
                $this->openAdminPinView($chatId, $userId, $pinId, $this->uiText->info($this->catalog->get('admin.legacy.info.settings_legacy_redirect')));
                return;
            }
            $body = trim((string) ($message['text'] ?? ''));
            if ($pinId <= 0 || $body === '') {
                $this->telegram->sendMessage($chatId, $this->catalog->get('admin.legacy.errors.invalid_pin_edit_data'));
                return;
            }
            $this->database->updatePinnedMessage($pinId, $body);
            $this->openAdminPinView($chatId, $userId, $pinId, $this->uiText->info($this->catalog->get('admin.legacy.info.add_admin_legacy_redirect')));
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
                $this->telegram->sendMessage($chatId, $this->catalog->get('admin.ui.broadcast_message_not_sendable'));
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
            $this->telegram->sendMessage($chatId, $this->catalog->get('admin.ui.broadcast_done_legacy', ['sent' => $sent]));
            $this->sendToGroupTopic('broadcast_report', $this->catalog->get('admin.ui.broadcast_report_legacy', ['user_id' => $userId, 'scope' => htmlspecialchars($scope), 'sent' => $sent]));
            return;
        }
    }

    private function handleMainReplyKeyboardInput(int $chatId, int $messageId, int $userId, array $fromUser, string $text): bool
    {
        if ($text === '' || str_starts_with($text, '/')) {
            return false;
        }

        if ($text === KeyboardBuilder::profile()) {
            $this->telegram->sendMessage($chatId, $this->menus->profileText($userId), $this->menus->accountMenuReplyKeyboard());
            return true;
        }

        if ($text === KeyboardBuilder::support()) {
            $this->telegram->sendMessage($chatId, $this->menus->supportText());
            return true;
        }

        if ($text === KeyboardBuilder::myConfigs()) {
            $this->showMyConfigsWithReplyFlow($chatId, $userId);
            return true;
        }

        if ($text === KeyboardBuilder::referralButton()) {
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

        if ($text === KeyboardBuilder::wallet()) {
            $this->database->setUserState($userId, 'await_wallet_amount');
            $this->telegram->sendMessage(
                $chatId,
                $this->catalog->get('messages.user.wallet.enter_amount'),
                $this->replyKeyboard([[KeyboardBuilder::backAccount(), KeyboardBuilder::backMain()]])
            );
            return true;
        }

        if ($text === KeyboardBuilder::buy()) {
            $this->startBuyTypeReplyFlow($chatId, $userId);
            return true;
        }

        if ($text === KeyboardBuilder::freeTest()) {
            if ($this->settings->get('free_test_enabled', '1') !== '1') {
                return false;
            }
            $claim = $this->database->claimFreeTest($userId);
            if (($claim['ok'] ?? false) !== true) {
                $this->telegram->sendMessage(
                    $chatId,
                    $this->catalog->get('messages.user.free_test.not_available')
                );
                return true;
            }
            $serviceName = htmlspecialchars((string) ($claim['service_name'] ?? $this->catalog->get('messages.user.free_test.default_service')));
            $configText = htmlspecialchars((string) ($claim['config_text'] ?? ''));
            $inquiryLink = trim((string) ($claim['inquiry_link'] ?? ''));
            $msg = $this->catalog->get('messages.user.free_test.ready', [
                'service_name' => $serviceName,
                'config_text' => $configText,
            ]);
            if ($inquiryLink !== '') {
                $msg = $this->catalog->get('messages.user.free_test.ready_with_inquiry', [
                    'service_name' => $serviceName,
                    'config_text' => $configText,
                    'inquiry_link' => htmlspecialchars($inquiryLink),
                ]);
            }
            $this->telegram->sendMessage($chatId, $msg);
            return true;
        }

        if ($text === KeyboardBuilder::agency()) {
            if ($this->settings->get('agency_request_enabled', '1') !== '1') {
                return false;
            }
            $this->database->setUserState($userId, 'await_agency_request');
            $this->telegram->sendMessage(
                $chatId,
                $this->catalog->get('messages.user.agency.request_intro')
            );
            return true;
        }

        if ($text === KeyboardBuilder::admin()) {
            if (!$this->database->isAdminUser($userId)) {
                return false;
            }
            $this->openAdminRoot($chatId, $userId);
            return true;
        }

        if ($text === $this->catalog->get('admin.common.back_to_panel')) {
            if (!$this->database->isAdminUser($userId)) {
                return false;
            }
            $this->database->clearUserState($userId);
            $this->openAdminRoot($chatId, $userId);
            return true;
        }

        if ($this->database->isAdminUser($userId)) {
            if ($text === $this->catalog->get('buttons.admin.settings')) {
                $this->openAdminSettingsView($chatId, $userId);
                return true;
            }
            if ($text === $this->catalog->get('buttons.admin.admins')) {
                $this->openAdminAdminsList($chatId, $userId);
                return true;
            }
            if ($text === $this->catalog->get('buttons.admin.pins')) {
                $this->openAdminPinsList($chatId, $userId);
                return true;
            }
            if ($text === $this->catalog->get('buttons.admin.agencies')) {
                $this->openAdminAgentsList($chatId, $userId);
                return true;
            }
            if ($text === $this->catalog->get('buttons.admin.broadcast')) {
                $this->openAdminBroadcastCompose($chatId, $userId);
                return true;
            }
            if ($text === $this->catalog->get('buttons.admin.delivery')) {
                $this->openAdminDeliveriesList($chatId, $userId);
                return true;
            }
            if ($text === $this->catalog->get('buttons.admin.backup_topics')) {
                $this->openAdminGroupOpsView($chatId, $userId);
                return true;
            }
            if ($text === $this->catalog->get('buttons.admin.free_test')) {
                $this->openAdminFreeTestMenu($chatId, $userId);
                return true;
            }
            if ($text === $this->catalog->get('buttons.admin.charges')) {
                $this->openAdminPaymentsList($chatId, $userId);
                return true;
            }
            if ($text === $this->catalog->get('buttons.admin.requests')) {
                $this->openAdminRequestsList($chatId, $userId);
                return true;
            }
            if (preg_match('/^(' . preg_quote($this->catalog->get('admin.ui.payment_approve_prefix_regex'), '/') . '|' . preg_quote($this->catalog->get('admin.ui.payment_reject_prefix_regex'), '/') . ')\s*#(\d+)$/u', $text, $m) === 1) {
                $paymentId = (int) ($m[2] ?? 0);
                if ($paymentId > 0) {
                    $this->openAdminPaymentView(
                        $chatId,
                        $userId,
                        $paymentId,
                        $this->uiText->info($this->catalog->get('admin.common.legacy_button_payment'))
                    );
                    return true;
                }
            }
            if (preg_match('/^' . preg_quote($this->catalog->get('admin.common.legacy_free_request_prefix'), '/') . '\s*#(\d+)$/u', $text, $m) === 1) {
                $requestId = (int) ($m[1] ?? 0);
                if ($requestId > 0) {
                    $this->openAdminRequestView(
                        $chatId,
                        $userId,
                        'free',
                        $requestId,
                        'pending',
                        $this->uiText->info($this->catalog->get('admin.common.legacy_button_request'))
                    );
                    return true;
                }
            }
            if (preg_match('/^' . preg_quote($this->catalog->get('admin.common.legacy_agency_request_prefix'), '/') . '\s*#(\d+)$/u', $text, $m) === 1) {
                $requestId = (int) ($m[1] ?? 0);
                if ($requestId > 0) {
                    $this->openAdminRequestView(
                        $chatId,
                        $userId,
                        'agency',
                        $requestId,
                        'pending',
                        $this->uiText->info($this->catalog->get('admin.common.legacy_button_request'))
                    );
                    return true;
                }
            }
        }

        if ($text === KeyboardBuilder::backMain()) {
            $this->database->clearUserState($userId);
            $this->telegram->sendMessage($chatId, $this->menus->mainMenuText(), $this->menus->mainMenuReplyKeyboard($userId));
            return true;
        }

        return false;
    }

    private function startBuyTypeReplyFlow(int $chatId, int $userId): void
    {
        if ($this->settings->get('shop_open', '1') !== '1') {
            $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('messages.user.buy.shop_closed')));
            return;
        }

        $types = $this->database->getActiveTypes();
        if ($types === []) {
            $this->telegram->sendMessage($chatId, $this->catalog->get('emojis.cart') . ' ' . $this->catalog->get('messages.user.buy.no_active_service'));
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
            if ($this->database->countServicesWithTariffsByType($typeId) <= 0) {
                continue;
            }
            $name = trim((string) ($type['name'] ?? '—'));
            $lines[] = "{$num}) " . htmlspecialchars($name);
            $optionMap[$num] = $typeId;
            $buttons[] = [$num . ' - ' . $name];
        }

        if ($optionMap === []) {
            $this->telegram->sendMessage($chatId, $this->catalog->get('emojis.cart') . ' ' . $this->catalog->get('messages.user.buy.no_active_service'));
            return;
        }

        $buttons[] = [UiLabels::main($this->catalog)];
        $this->database->setUserState($userId, 'buy.await_type', ['options' => $optionMap, 'stack' => [], 'type_id' => null, 'package_id' => null, 'payment_method' => null]);
        $this->telegram->sendMessage(
            $chatId,
            $this->messageRenderer->render('messages.user.buy.type_selection.overview', [
                'options' => implode("\n", $lines),
            ], ['options']),
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
        if (($state['state_name'] ?? '') === 'admin.root' && $this->isAdminExitInput($text)) {
            $this->database->clearUserState($userId);
            $this->telegram->sendMessage($chatId, $this->menus->mainMenuText(), $this->menus->mainMenuReplyKeyboard($userId));
            return;
        }

        if ($this->isMainMenuInput($text)) {
            $this->openAdminRoot($chatId, $userId);
            return;
        }
        if ($text === UiLabels::back($this->catalog)) {
            $this->openAdminRoot($chatId, $userId);
            return;
        }

        if (($state['state_name'] ?? '') === 'admin.root') {
            $adminRouteMap = [
                $this->catalog->get('buttons.admin.types_packages') => 'admin:types',
                $this->catalog->get('buttons.admin.inventory') => 'admin:stock',
                $this->catalog->get('buttons.admin.users') => 'admin:users',
                $this->catalog->get('buttons.admin.settings') => 'admin:settings',
                $this->catalog->get('buttons.admin.free_test') => 'admin:free_test:menu',
                $this->catalog->get('buttons.admin.admins') => 'admin:admins',
                $this->catalog->get('buttons.admin.broadcast') => 'admin:broadcast',
                $this->catalog->get('buttons.admin.pins') => 'admin:pins',
                $this->catalog->get('buttons.admin.agencies') => 'admin:agents',
                $this->catalog->get('buttons.admin.charges') => 'admin:payments',
                $this->catalog->get('buttons.admin.delivery') => 'admin:deliveries',
                $this->catalog->get('buttons.admin.requests') => 'admin:requests',
                $this->catalog->get('buttons.admin.backup_topics') => 'admin:groupops',
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
                    $this->messageRenderer->render('admin.ui.nav.overview', [
                        'route' => $route,
                    ]),
                    $this->uiKeyboard->replyMenu([[UiLabels::back($this->catalog), UiLabels::main($this->catalog)]])
                );
                return;
            }
        }

        $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('admin.ui.invalid_admin_option')));
    }

    private function handleAdminTypesPackagesState(int $chatId, int $userId, string $text, array $state): void
    {
        if ($this->isMainMenuInput($text)) {
            $this->openAdminRoot($chatId, $userId);
            return;
        }

        $stateName = (string) ($state['state_name'] ?? '');
        $payload = is_array($state['payload'] ?? null) ? $state['payload'] : [];

        if ($stateName === 'admin.service.landing') {
            if ($text === UiLabels::back($this->catalog)) {
                $this->openAdminRoot($chatId, $userId);
                return;
            }
            if (
                $text === $this->catalog->get('admin.types_packages.actions.add_service')
                || $text === $this->uiConst(self::ADMIN_SERVICE_ADD)
            ) {
                $defaultTypeId = (int) ($payload['default_type_id'] ?? 0);
                $this->database->setUserState($userId, 'admin.service.create', ['type_id' => $defaultTypeId, 'step' => 'name', 'data' => [], 'stack' => ['admin.service.landing']]);
                $this->telegram->sendMessage(
                    $chatId,
                    $this->catalog->get('admin.types_packages.prompts.service_wizard.name'),
                    $this->uiKeyboard->replyMenu([[UiLabels::back($this->catalog), UiLabels::main($this->catalog)]])
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
            $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('admin.types_packages.errors.invalid_type_option')));
            return;
        }

        if ($stateName === 'admin.type.create') {
            if ($text === UiLabels::back($this->catalog)) {
                $this->openAdminTypesList($chatId, $userId);
                return;
            }
            $name = trim($text);
            if ($name === '') {
                $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('admin.types_packages.errors.type_name_required')));
                return;
            }
            $typeId = $this->database->createType($name);
            $this->openAdminTypeView(
                $chatId,
                $userId,
                $typeId,
                $this->uiText->success($this->catalog->get('admin.types_packages.success.type_created', ['type_id' => $typeId]))
            );
            return;
        }

        if ($stateName === 'admin.type.edit') {
            $typeId = (int) ($payload['type_id'] ?? 0);
            if ($text === UiLabels::back($this->catalog)) {
                $this->openAdminTypeView($chatId, $userId, $typeId);
                return;
            }
            $name = trim($text);
            if ($name === '') {
                $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('admin.types_packages.errors.type_name_required')));
                return;
            }
            $this->database->updateTypeName($typeId, $name);
            $this->openAdminTypeView($chatId, $userId, $typeId, $this->uiText->success($this->catalog->get('admin.types_packages.success.type_status_updated')));
            return;
        }

        if ($stateName === 'admin.type.delete') {
            $typeId = (int) ($payload['type_id'] ?? 0);
            if ($text === UiLabels::back($this->catalog)) {
                $this->openAdminTypeView($chatId, $userId, $typeId);
                return;
            }
            if ($text !== $this->catalog->get('admin.types_packages.actions.delete_confirm')) {
                $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('admin.ui.invalid_admin_option')));
                return;
            }
            $this->database->deleteType($typeId);
            $this->openAdminTypesList($chatId, $userId, $this->uiText->success($this->catalog->get('admin.types_packages.success.type_deleted')));
            return;
        }

        if ($stateName === 'admin.service.type_select') {
            if ($text === UiLabels::back($this->catalog)) {
                $this->openAdminTypesList($chatId, $userId);
                return;
            }
            $options = is_array($payload['options'] ?? null) ? $payload['options'] : [];
            $selected = $this->extractOptionKey($text);
            $typeId = isset($options[$selected]) ? (int) $options[$selected] : 0;
            if ($typeId > 0) {
                $this->database->setUserState($userId, 'admin.service.create', ['type_id' => $typeId, 'step' => 'name', 'data' => [], 'stack' => ['admin.service.type_select']]);
                $this->telegram->sendMessage(
                    $chatId,
                    $this->catalog->get('admin.types_packages.prompts.service_wizard.name'),
                    $this->uiKeyboard->replyMenu([[UiLabels::back($this->catalog), UiLabels::main($this->catalog)]])
                );
                return;
            }
            $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('admin.types_packages.errors.invalid_type_option')));
            return;
        }

        if ($stateName === 'admin.service.list') {
            if ($text === UiLabels::back($this->catalog)) {
                $this->openAdminTypesList($chatId, $userId);
                return;
            }
            $typeId = (int) ($payload['type_id'] ?? 0);
            if ($typeId <= 0) {
                $this->openAdminTypesList($chatId, $userId, $this->uiText->warning($this->catalog->get('admin.types_packages.errors.invalid_type_again')));
                return;
            }
            if ($text === $this->catalog->get('admin.types_packages.actions.add_service') || $text === $this->uiConst(self::ADMIN_SERVICE_ADD)) {
                $this->database->setUserState($userId, 'admin.service.create', ['type_id' => $typeId, 'step' => 'name', 'data' => [], 'stack' => ['admin.service.list']]);
                $this->telegram->sendMessage(
                    $chatId,
                    $this->catalog->get('admin.types_packages.prompts.service_wizard.name'),
                    $this->uiKeyboard->replyMenu([[UiLabels::back($this->catalog), UiLabels::main($this->catalog)]])
                );
                return;
            }
            if ($text === $this->catalog->get('admin.types_packages.actions.toggle_type')) {
                $type = $this->database->getTypeById($typeId);
                if (!is_array($type)) {
                    $this->openAdminTypesList($chatId, $userId, $this->uiText->warning($this->catalog->get('admin.types_packages.errors.type_not_found')));
                    return;
                }
                $active = ((int) ($type['is_active'] ?? 0)) === 1;
                $this->database->updateTypeActive($typeId, !$active);
                $this->openAdminTypeView($chatId, $userId, $typeId, $this->uiText->success($this->catalog->get('admin.types_packages.success.type_status_updated')));
                return;
            }
            if ($text === $this->catalog->get('admin.types_packages.actions.edit_type')) {
                $this->database->setUserState($userId, 'admin.type.edit', ['type_id' => $typeId, 'stack' => ['admin.service.list', 'admin.service.landing', 'admin.root']]);
                $this->telegram->sendMessage(
                    $chatId,
                    $this->uiText->info($this->catalog->get('admin.types_packages.prompts.type_edit_name')),
                    $this->uiKeyboard->replyMenu([[UiLabels::back($this->catalog), UiLabels::main($this->catalog)]])
                );
                return;
            }
            if ($text === $this->catalog->get('admin.types_packages.actions.delete_service')) {
                if ($this->database->countServicesByType($typeId) > 0) {
                    $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('admin.types_packages.errors.type_delete_blocked')));
                    return;
                }
                $this->database->setUserState($userId, 'admin.type.delete', ['type_id' => $typeId, 'stack' => ['admin.service.list', 'admin.service.landing', 'admin.root']]);
                $this->telegram->sendMessage(
                    $chatId,
                    $this->uiText->warning($this->catalog->get('admin.types_packages.prompts.type_delete_confirm', ['type_id' => $typeId])),
                    $this->uiKeyboard->replyMenu([
                        [$this->catalog->get('admin.types_packages.actions.delete_confirm')],
                        [UiLabels::back($this->catalog), UiLabels::main($this->catalog)],
                    ])
                );
                return;
            }
            $serviceOptions = is_array($payload['service_options'] ?? null) ? $payload['service_options'] : [];
            $serviceId = isset($serviceOptions[$text]) ? (int) $serviceOptions[$text] : 0;
            if ($serviceId > 0) {
                $this->openAdminServiceView($chatId, $userId, $typeId, $serviceId);
                return;
            }

            $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('admin.types_packages.errors.invalid_package_or_action')));
            return;
        }

        if ($stateName === 'admin.service.create') {
            $typeId = (int) ($payload['type_id'] ?? 0);
            $step = (string) ($payload['step'] ?? 'name');
            $data = is_array($payload['data'] ?? null) ? $payload['data'] : [];
            if ($text === UiLabels::back($this->catalog)) {
                $this->handleAdminServiceCreateBack($chatId, $userId, $typeId, $step, $data);
                return;
            }
            if (!$this->applyServiceWizardInput(
                $chatId,
                $userId,
                $typeId,
                $step,
                $text,
                $data,
                'admin.service.create',
                []
            )) {
                return;
            }
            if ($typeId <= 0) {
                $typeId = $this->ensureServiceRootTypeId();
            }
            $serviceId = $this->database->createService([
                'type_id' => $typeId,
                'name' => (string) ($data['name'] ?? ''),
                'mode' => (string) ($data['mode'] ?? 'stock'),
                'panel_provider' => isset($data['panel_provider']) ? (string) $data['panel_provider'] : null,
                'panel_base_url' => isset($data['panel_base_url']) ? (string) $data['panel_base_url'] : null,
                'panel_username' => isset($data['panel_username']) ? (string) $data['panel_username'] : null,
                'panel_password' => isset($data['panel_password']) ? (string) $data['panel_password'] : null,
                'is_active' => 1,
            ]);
            $this->openAdminServiceView($chatId, $userId, $typeId, $serviceId, $this->uiText->success($this->catalog->get('admin.types_packages.success.service_created', ['service_id' => $serviceId])));
            return;
        }

        if ($stateName === 'admin.service.view') {
            $typeId = (int) ($payload['type_id'] ?? 0);
            $serviceId = (int) ($payload['service_id'] ?? 0);
            if ($typeId <= 0 || $serviceId <= 0) {
                $this->openAdminTypeView($chatId, $userId, $typeId > 0 ? $typeId : 0, $this->uiText->warning($this->catalog->get('admin.types_packages.errors.service_not_found')));
                return;
            }
            if ($text === UiLabels::back($this->catalog)) {
                $this->openAdminTypeView($chatId, $userId, $typeId);
                return;
            }
            if ($text === $this->uiConst(self::ADMIN_SERVICE_EDIT)) {
                $service = $this->database->getService($serviceId);
                if (!is_array($service)) {
                    $this->openAdminTypeView($chatId, $userId, $typeId, $this->uiText->warning($this->catalog->get('admin.types_packages.errors.service_not_found')));
                    return;
                }
                $data = [
                    'name' => (string) ($service['name'] ?? ''),
                    'mode' => (string) ($service['mode'] ?? 'stock'),
                    'panel_provider' => (string) ($service['panel_provider'] ?? ''),
                    'panel_base_url' => (string) ($service['panel_base_url'] ?? ''),
                    'panel_username' => (string) ($service['panel_username'] ?? ''),
                    'panel_password' => (string) ($service['panel_password'] ?? ''),
                ];
                $this->database->setUserState($userId, 'admin.service.edit', ['type_id' => $typeId, 'service_id' => $serviceId, 'step' => 'name', 'data' => $data]);
                $this->telegram->sendMessage(
                    $chatId,
                    $this->catalog->get('admin.types_packages.prompts.service_wizard.name'),
                    $this->uiKeyboard->replyMenu([[UiLabels::back($this->catalog), UiLabels::main($this->catalog)]])
                );
                return;
            }
            if ($text === $this->uiConst(self::ADMIN_SERVICE_TARIFFS)) {
                $this->openAdminServiceTariffsView($chatId, $userId, $typeId, $serviceId);
                return;
            }
            if ($text === $this->uiConst(self::ADMIN_SERVICE_INVENTORY)) {
                $service = $this->database->getService($serviceId);
                if (!is_array($service)) {
                    $this->openAdminServiceView($chatId, $userId, $typeId, $serviceId, $this->uiText->warning($this->catalog->get('admin.types_packages.errors.service_not_found')));
                    return;
                }
                if ((string) ($service['mode'] ?? 'stock') !== 'stock') {
                    $this->openAdminServiceView($chatId, $userId, $typeId, $serviceId, $this->uiText->info($this->catalog->get('admin.types_packages.messages.inventory_bridge_not_stock')));
                    return;
                }
                $this->openAdminServiceInventoryView($chatId, $userId, $typeId, $serviceId);
                return;
            }
            if ($text === $this->uiConst(self::ADMIN_SERVICE_TARIFF_ADD)) {
                $this->database->setUserState($userId, 'admin.service.tariff.create', [
                    'type_id' => $typeId,
                    'service_id' => $serviceId,
                    'step' => 'title',
                    'data' => [],
                ]);
                $this->telegram->sendMessage($chatId, $this->uiText->info($this->catalog->get('admin.types_packages.prompts.tariff_wizard.title')), $this->uiKeyboard->replyMenu([[UiLabels::back($this->catalog), UiLabels::main($this->catalog)]]));
                return;
            }
            if ($text === $this->uiConst(self::ADMIN_SERVICE_STOCK_ADD)) {
                $service = $this->database->getService($serviceId);
                if (!is_array($service) || (string) ($service['mode'] ?? 'stock') !== 'stock') {
                    $this->openAdminServiceView($chatId, $userId, $typeId, $serviceId, $this->uiText->info($this->catalog->get('admin.types_packages.messages.inventory_bridge_not_stock')));
                    return;
                }
                $this->database->setUserState($userId, 'admin.service.inventory.add', [
                    'type_id' => $typeId,
                    'service_id' => $serviceId,
                    'step' => 'tariff',
                    'data' => [],
                ]);
                $this->promptServiceInventoryTariffStep($chatId, $userId, $typeId, $serviceId, [], 'admin.service.inventory.add');
                return;
            }
            if ($text === $this->uiConst(self::ADMIN_SERVICE_TOGGLE)) {
                $service = $this->database->getService($serviceId);
                if (!is_array($service)) {
                    $this->openAdminServiceView($chatId, $userId, $typeId, $serviceId, $this->uiText->warning($this->catalog->get('admin.types_packages.errors.service_not_found')));
                    return;
                }
                $active = ((int) ($service['is_active'] ?? 0)) === 1;
                $this->database->updateServiceActive($serviceId, !$active);
                $this->openAdminServiceView($chatId, $userId, $typeId, $serviceId, $this->uiText->success($this->catalog->get('admin.types_packages.success.service_status_updated')));
                return;
            }
            if ($text === $this->uiConst(self::ADMIN_SERVICE_DELETE)) {
                if ($this->database->countTariffsByService($serviceId) > 0 || $this->database->countConfigsByService($serviceId) > 0) {
                    $this->openAdminServiceView($chatId, $userId, $typeId, $serviceId, $this->uiText->warning($this->catalog->get('admin.types_packages.errors.service_delete_blocked')));
                    return;
                }
                $this->database->deleteService($serviceId);
                $this->openAdminTypeView($chatId, $userId, $typeId, $this->uiText->success($this->catalog->get('admin.types_packages.success.service_deleted')));
                return;
            }
            $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('admin.types_packages.errors.invalid_service_action')));
            return;
        }

        if ($stateName === 'admin.service.edit') {
            $typeId = (int) ($payload['type_id'] ?? 0);
            $serviceId = (int) ($payload['service_id'] ?? 0);
            $step = (string) ($payload['step'] ?? 'name');
            $data = is_array($payload['data'] ?? null) ? $payload['data'] : [];
            if ($text === UiLabels::back($this->catalog)) {
                $this->handleAdminServiceEditBack($chatId, $userId, $typeId, $serviceId, $step, $data);
                return;
            }
            if (!$this->applyServiceWizardInput(
                $chatId,
                $userId,
                $typeId,
                $step,
                $text,
                $data,
                'admin.service.edit',
                ['service_id' => $serviceId]
            )) {
                return;
            }
            $this->database->updateServiceBasic($serviceId, [
                'name' => (string) ($data['name'] ?? ''),
                'mode' => (string) ($data['mode'] ?? 'stock'),
                'panel_provider' => isset($data['panel_provider']) ? (string) $data['panel_provider'] : null,
                'panel_base_url' => isset($data['panel_base_url']) ? (string) $data['panel_base_url'] : null,
                'panel_username' => isset($data['panel_username']) ? (string) $data['panel_username'] : null,
                'panel_password' => isset($data['panel_password']) ? (string) $data['panel_password'] : null,
                'is_active' => 1,
            ]);
            $this->openAdminServiceView($chatId, $userId, $typeId, $serviceId, $this->uiText->success($this->catalog->get('admin.types_packages.success.service_updated')));
            return;
        }

        if ($stateName === 'admin.service.tariffs') {
            $typeId = (int) ($payload['type_id'] ?? 0);
            $serviceId = (int) ($payload['service_id'] ?? 0);
            $selectedTariffId = (int) ($payload['selected_tariff_id'] ?? 0);
            if ($text === UiLabels::back($this->catalog)) {
                $this->openAdminServiceView($chatId, $userId, $typeId, $serviceId);
                return;
            }
            if ($selectedTariffId > 0 && $text === $this->catalog->get('admin.types_packages.actions.service_tariff_edit')) {
                $tariff = $this->database->getServiceTariff($selectedTariffId);
                if (!is_array($tariff) || (int) ($tariff['service_id'] ?? 0) !== $serviceId) {
                    $this->openAdminServiceTariffsView($chatId, $userId, $typeId, $serviceId, $this->uiText->warning($this->catalog->get('admin.types_packages.errors.tariff_not_found')));
                    return;
                }
                $data = [
                    'title' => (string) ($tariff['title'] ?? ''),
                    'pricing_mode' => (string) ($tariff['pricing_mode'] ?? 'fixed'),
                    'volume_gb' => isset($tariff['volume_gb']) ? (float) $tariff['volume_gb'] : null,
                    'duration_days' => isset($tariff['duration_days']) ? (int) $tariff['duration_days'] : null,
                    'price' => isset($tariff['price']) ? (int) $tariff['price'] : null,
                    'min_volume_gb' => isset($tariff['min_volume_gb']) ? (float) $tariff['min_volume_gb'] : null,
                    'max_volume_gb' => isset($tariff['max_volume_gb']) ? (float) $tariff['max_volume_gb'] : null,
                    'step_volume_gb' => isset($tariff['step_volume_gb']) ? (float) $tariff['step_volume_gb'] : null,
                    'price_per_gb' => isset($tariff['price_per_gb']) ? (int) $tariff['price_per_gb'] : null,
                    'duration_policy' => (string) ($tariff['duration_policy'] ?? ''),
                ];
                $this->database->setUserState($userId, 'admin.service.tariff.edit', ['type_id' => $typeId, 'service_id' => $serviceId, 'tariff_id' => $selectedTariffId, 'step' => 'title', 'data' => $data]);
                $this->telegram->sendMessage($chatId, $this->uiText->info($this->catalog->get('admin.types_packages.prompts.tariff_wizard.title')), $this->uiKeyboard->replyMenu([[UiLabels::back($this->catalog), UiLabels::main($this->catalog)]]));
                return;
            }
            if ($selectedTariffId > 0 && $text === $this->catalog->get('admin.types_packages.actions.service_tariff_delete')) {
                $this->database->setUserState($userId, 'admin.service.tariff.delete', ['type_id' => $typeId, 'service_id' => $serviceId, 'tariff_id' => $selectedTariffId]);
                $this->telegram->sendMessage(
                    $chatId,
                    $this->uiText->warning($this->catalog->get('admin.types_packages.prompts.tariff_delete_confirm', [
                        'tariff_id' => $selectedTariffId,
                        'confirm_word' => $this->catalog->get('admin.final_modules.keywords.delete_confirm'),
                    ])),
                    $this->uiKeyboard->replyMenu([[UiLabels::back($this->catalog), UiLabels::main($this->catalog)]])
                );
                return;
            }
            if ($text === $this->uiConst(self::ADMIN_SERVICE_TARIFF_ADD)) {
                $this->database->setUserState($userId, 'admin.service.tariff.create', ['type_id' => $typeId, 'service_id' => $serviceId, 'step' => 'title', 'data' => []]);
                $this->telegram->sendMessage($chatId, $this->uiText->info($this->catalog->get('admin.types_packages.prompts.tariff_wizard.title')), $this->uiKeyboard->replyMenu([[UiLabels::back($this->catalog), UiLabels::main($this->catalog)]]));
                return;
            }
            $options = is_array($payload['options'] ?? null) ? $payload['options'] : [];
            $selected = $this->extractOptionKey($text);
            $tariffId = isset($options[$selected]) ? (int) $options[$selected] : 0;
            if ($tariffId > 0) {
                $this->openAdminServiceTariffDetailView($chatId, $userId, $typeId, $serviceId, $tariffId);
                return;
            }
            $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('admin.types_packages.errors.invalid_tariff_option')));
            return;
        }

        if ($stateName === 'admin.service.inventory') {
            $typeId = (int) ($payload['type_id'] ?? 0);
            $serviceId = (int) ($payload['service_id'] ?? 0);
            if ($text === UiLabels::back($this->catalog)) {
                $this->openAdminServiceView($chatId, $userId, $typeId, $serviceId);
                return;
            }
            if ($text === $this->uiConst(self::ADMIN_SERVICE_INVENTORY_REFRESH)) {
                $this->openAdminServiceInventoryView($chatId, $userId, $typeId, $serviceId);
                return;
            }
            if ($text === $this->uiConst(self::ADMIN_SERVICE_STOCK_ADD)) {
                $this->database->setUserState($userId, 'admin.service.inventory.add', ['type_id' => $typeId, 'service_id' => $serviceId, 'step' => 'tariff', 'data' => []]);
                $this->promptServiceInventoryTariffStep($chatId, $userId, $typeId, $serviceId, [], 'admin.service.inventory.add');
                return;
            }
            $options = is_array($payload['options'] ?? null) ? $payload['options'] : [];
            $selected = $this->extractOptionKey($text);
            $configId = isset($options[$selected]) ? (int) $options[$selected] : 0;
            if ($configId > 0) {
                $this->openAdminServiceInventoryDetailView($chatId, $userId, $typeId, $serviceId, $configId);
                return;
            }
            $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('admin.types_packages.errors.invalid_service_inventory_option')));
            return;
        }

        if ($stateName === 'admin.service.inventory_bridge') {
            $typeId = (int) ($payload['type_id'] ?? 0);
            $serviceId = (int) ($payload['service_id'] ?? 0);
            if ($text === UiLabels::back($this->catalog)) {
                $this->openAdminServiceView($chatId, $userId, $typeId, $serviceId);
                return;
            }
            $this->openAdminServiceInventoryView($chatId, $userId, $typeId, $serviceId);
            return;
        }

        if ($stateName === 'admin.service.tariff.create' || $stateName === 'admin.service.tariff.edit') {
            $typeId = (int) ($payload['type_id'] ?? 0);
            $serviceId = (int) ($payload['service_id'] ?? 0);
            $tariffId = (int) ($payload['tariff_id'] ?? 0);
            $step = (string) ($payload['step'] ?? 'title');
            $data = is_array($payload['data'] ?? null) ? $payload['data'] : [];
            if ($text === UiLabels::back($this->catalog)) {
                $this->handleTariffWizardBack($chatId, $userId, $typeId, $serviceId, $tariffId, $stateName, $step, $data);
                return;
            }
            if (!$this->applyTariffWizardInput($chatId, $userId, $typeId, $serviceId, $stateName, $step, $text, $data, $tariffId)) {
                return;
            }
            if ($stateName === 'admin.service.tariff.create') {
                $newId = $this->database->createServiceTariff(array_merge($data, ['service_id' => $serviceId, 'is_active' => 1]));
                $this->openAdminServiceTariffDetailView($chatId, $userId, $typeId, $serviceId, $newId, $this->uiText->success($this->catalog->get('admin.types_packages.success.tariff_created')));
                return;
            }
            $this->database->updateServiceTariff($tariffId, array_merge($data, ['is_active' => 1]));
            $this->openAdminServiceTariffDetailView($chatId, $userId, $typeId, $serviceId, $tariffId, $this->uiText->success($this->catalog->get('admin.types_packages.success.tariff_updated')));
            return;
        }

        if ($stateName === 'admin.service.tariff.delete') {
            $typeId = (int) ($payload['type_id'] ?? 0);
            $serviceId = (int) ($payload['service_id'] ?? 0);
            $tariffId = (int) ($payload['tariff_id'] ?? 0);
            if ($text === UiLabels::back($this->catalog)) {
                $this->openAdminServiceTariffDetailView($chatId, $userId, $typeId, $serviceId, $tariffId);
                return;
            }
            if (trim($text) !== $this->catalog->get('admin.final_modules.keywords.delete_confirm')) {
                $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('admin.types_packages.errors.tariff_delete_confirm_required')));
                return;
            }
            $this->database->deleteServiceTariff($tariffId);
            $this->openAdminServiceTariffsView($chatId, $userId, $typeId, $serviceId, $this->uiText->success($this->catalog->get('admin.types_packages.success.tariff_deleted')));
            return;
        }

        if ($stateName === 'admin.service.inventory.add') {
            $typeId = (int) ($payload['type_id'] ?? 0);
            $serviceId = (int) ($payload['service_id'] ?? 0);
            $step = (string) ($payload['step'] ?? 'tariff');
            $data = is_array($payload['data'] ?? null) ? $payload['data'] : [];
            if ($text === UiLabels::back($this->catalog)) {
                if ($step === 'tariff') {
                    $this->openAdminServiceInventoryView($chatId, $userId, $typeId, $serviceId);
                    return;
                }
                if ($step === 'payload') {
                    $this->promptServiceInventoryTariffStep($chatId, $userId, $typeId, $serviceId, $data, 'admin.service.inventory.add');
                    return;
                }
                $this->database->setUserState($userId, 'admin.service.inventory.add', ['type_id' => $typeId, 'service_id' => $serviceId, 'step' => 'payload', 'data' => $data]);
                $this->telegram->sendMessage($chatId, $this->uiText->info($this->catalog->get('admin.types_packages.prompts.service_inventory.payload')));
                return;
            }
            if (!$this->applyServiceInventoryAddInput($chatId, $userId, $typeId, $serviceId, $step, $text, $data)) {
                return;
            }
            $configId = $this->database->addConfigForService(
                $serviceId,
                isset($data['tariff_id']) ? (int) $data['tariff_id'] : null,
                (string) ($data['service_name'] ?? $this->catalog->get('messages.generic.dash')),
                (string) ($data['config_text'] ?? ''),
                isset($data['inquiry_link']) ? (string) $data['inquiry_link'] : null
            );
            $this->openAdminServiceInventoryView($chatId, $userId, $typeId, $serviceId, $this->uiText->success($this->catalog->get('admin.types_packages.success.service_stock_added', ['config_id' => $configId])));
            return;
        }

        if ($stateName === 'admin.service.inventory.detail') {
            $typeId = (int) ($payload['type_id'] ?? 0);
            $serviceId = (int) ($payload['service_id'] ?? 0);
            $configId = (int) ($payload['config_id'] ?? 0);
            if ($text === UiLabels::back($this->catalog)) {
                $this->openAdminServiceInventoryView($chatId, $userId, $typeId, $serviceId);
                return;
            }
            if ($text === $this->uiConst(self::ADMIN_STOCK_EXPIRE_TOGGLE)) {
                $cfg = $this->findConfigById($serviceId, $configId, true);
                if ($cfg === null) {
                    $this->openAdminServiceInventoryView($chatId, $userId, $typeId, $serviceId, $this->uiText->warning($this->catalog->get('admin.users_stock.errors.config_not_found')));
                    return;
                }
                $isExpired = ((int) ($cfg['is_expired'] ?? 0)) === 1;
                if ($isExpired) {
                    $this->database->unexpireConfig($configId);
                } else {
                    $this->database->expireConfig($configId);
                }
                $this->openAdminServiceInventoryDetailView($chatId, $userId, $typeId, $serviceId, $configId, $this->uiText->success($this->catalog->get('admin.users_stock.success.config_expire_status_updated')));
                return;
            }
            if ($text === $this->uiConst(self::ADMIN_STOCK_DELETE_CONFIG)) {
                $this->database->deleteConfig($configId);
                $this->openAdminServiceInventoryView($chatId, $userId, $typeId, $serviceId, $this->uiText->success($this->catalog->get('admin.users_stock.success.config_deleted')));
                return;
            }
            $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('admin.users_stock.errors.invalid_config_detail_option')));
            return;
        }

        $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('admin.types_packages.errors.invalid_service_action')));
    }

    private function openAdminTypesList(int $chatId, int $userId, ?string $notice = null): void
    {
        $types = $this->database->listTypes();
        $rows = [];
        $options = [];
        $buttons = [];
        $num = 1;
        foreach (array_values($types) as $type) {
            $typeId = (int) ($type['id'] ?? 0);
            if ($typeId <= 0) {
                continue;
            }
            $services = $this->database->listServicesByType($typeId);
            $serviceCount = count($services);
            $key = (string) $num;
            $num++;
            $isActive = ((int) ($type['is_active'] ?? 0)) === 1;
            $status = $isActive
                ? $this->catalog->get('admin.ui.open.common.status_active')
                : $this->catalog->get('admin.ui.open.common.status_inactive');
            $name = trim((string) ($type['name'] ?? $this->catalog->get('messages.generic.dash')));
            $rows[] = $this->catalog->get('admin.ui.open.types_list.row', ['num' => $key, 'status' => $status, 'name' => $name, 'type_id' => $typeId, 'service_count' => $serviceCount]);
            $options[$key] = $typeId;
            $buttons[] = [$this->catalog->get('admin.ui.open.types_list.button', ['num' => $key, 'name' => $name, 'service_count' => $serviceCount])];
        }
        $defaultTypeId = 0;
        if ($types !== []) {
            $defaultTypeId = (int) (($types[0]['id'] ?? 0));
        }
        $buttons[] = [$this->catalog->get('admin.types_packages.actions.add_service')];
        $buttons[] = [UiLabels::back($this->catalog), UiLabels::main($this->catalog)];
        $this->database->setUserState($userId, 'admin.service.landing', ['options' => $options, 'stack' => ['admin.root'], 'default_type_id' => $defaultTypeId]);
        if ($notice !== null && $notice !== '') {
            $this->telegram->sendMessage($chatId, $notice);
        }
        $landingText = $rows !== []
            ? $this->messageRenderer->render('admin.ui.open.types_list.overview', ['list' => implode("\n", $rows)], ['list'])
            : $this->messageRenderer->render('admin.ui.open.types_list.empty_overview');

        $this->telegram->sendMessage($chatId, $landingText, $this->uiKeyboard->replyMenu($buttons));
    }

    private function ensureServiceRootTypeId(): int
    {
        $types = $this->database->listTypes();
        if ($types !== []) {
            return (int) ($types[0]['id'] ?? 0);
        }

        return $this->database->createType('سرویس‌ها');
    }

    private function openAdminServiceTypeSelector(int $chatId, int $userId): void
    {
        $types = $this->database->listTypes();
        $options = [];
        $rows = [];
        $num = 1;
        foreach (array_values($types) as $type) {
            $typeId = (int) ($type['id'] ?? 0);
            if ($typeId <= 0) {
                continue;
            }
            $numKey = (string) $num;
            $num++;
            $name = trim((string) ($type['name'] ?? $this->catalog->get('messages.generic.dash')));
            $options[$numKey] = $typeId;
            $rows[] = $numKey . ' - ' . $name;
        }
        if ($options === []) {
            $this->openAdminTypesList($chatId, $userId, $this->uiText->warning($this->catalog->get('admin.ui.open.types_list.empty')));
            return;
        }
        $this->database->setUserState($userId, 'admin.service.type_select', ['options' => $options, 'stack' => ['admin.service.landing', 'admin.root']]);
        $keyboardRows = array_map(static fn (string $label): array => [$label], $rows);
        $keyboardRows[] = [UiLabels::back($this->catalog), UiLabels::main($this->catalog)];
        $this->telegram->sendMessage(
            $chatId,
            $this->uiText->info($this->catalog->get('admin.types_packages.prompts.package_mode_select')),
            $this->uiKeyboard->replyMenu($keyboardRows)
        );
    }

    private function openAdminTypeView(int $chatId, int $userId, int $typeId, ?string $notice = null): void
    {
        $type = $this->findTypeById($typeId);
        if ($type === null) {
            $this->openAdminTypesList($chatId, $userId, $this->uiText->warning($this->catalog->get('admin.ui.open.type_view.not_found')));
            return;
        }

        $services = $this->database->listServicesByType($typeId);
        $serviceOptions = [];
        $serviceRows = [];
        $buttons = [
            [$this->uiConst(self::ADMIN_SERVICE_ADD)],
            [
                $this->catalog->get('admin.types_packages.actions.edit_type'),
                $this->catalog->get('admin.types_packages.actions.toggle_type'),
            ],
            [$this->catalog->get('admin.types_packages.actions.delete_service')],
        ];
        foreach (array_values($services) as $service) {
            $serviceId = (int) ($service['id'] ?? 0);
            if ($serviceId <= 0) {
                continue;
            }
            $isActive = ((int) ($service['is_active'] ?? 0)) === 1;
            $status = $isActive
                ? $this->catalog->get('admin.ui.open.common.status_active')
                : $this->catalog->get('admin.ui.open.common.status_inactive');
            $mode = (string) ($service['mode'] ?? 'stock');
            $modeLabel = $mode === 'panel_auto'
                ? $this->catalog->get('admin.ui.open.service_view.mode_panel_auto')
                : $this->catalog->get('admin.ui.open.service_view.mode_stock');
            $serviceRows[] = $status . ' #' . $serviceId . ' - ' . (string) ($service['name'] ?? $this->catalog->get('messages.generic.dash')) . ' (' . $modeLabel . ')';
            $serviceButton = $this->catalog->get('admin.ui.open.type_view.service_button', ['service_id' => $serviceId, 'name' => (string) ($service['name'] ?? $this->catalog->get('messages.generic.dash'))]);
            $serviceOptions[$serviceButton] = $serviceId;
            $buttons[] = [$serviceButton];
        }

        $buttons[] = [UiLabels::back($this->catalog), UiLabels::main($this->catalog)];

        $this->database->setUserState($userId, 'admin.service.list', ['type_id' => $typeId, 'service_options' => $serviceOptions, 'stack' => ['admin.service.landing', 'admin.root']]);
        if ($notice !== null && $notice !== '') {
            $this->telegram->sendMessage($chatId, $notice);
        }
        $statusText = ((int) ($type['is_active'] ?? 0)) === 1
            ? $this->catalog->get('admin.ui.open.common.status_active')
            : $this->catalog->get('admin.ui.open.common.status_inactive');
        $this->telegram->sendMessage(
            $chatId,
            $this->messageRenderer->render('admin.ui.open.type_view.overview', [
                'type_name' => (string) ($type['name'] ?? '-'),
                'type_id' => $typeId,
                'status_text' => $statusText,
                'packages' => $serviceRows !== [] ? implode("\n", $serviceRows) : $this->catalog->get('admin.ui.open.type_view.packages_empty'),
            ], ['packages']),
            $this->uiKeyboard->replyMenu($buttons)
        );
    }

    private function openAdminServiceView(int $chatId, int $userId, int $typeId, int $serviceId, ?string $notice = null): void
    {
        $service = $this->database->getService($serviceId);
        $type = $this->findTypeById($typeId);
        if (!is_array($service) || !is_array($type) || (int) ($service['type_id'] ?? 0) !== $typeId) {
            $this->openAdminTypeView($chatId, $userId, $typeId, $this->uiText->warning($this->catalog->get('admin.types_packages.errors.service_not_found')));
            return;
        }

        $mode = (string) ($service['mode'] ?? 'stock');
        $modeText = $mode === 'panel_auto'
            ? $this->catalog->get('admin.ui.open.service_view.mode_panel_auto')
            : $this->catalog->get('admin.ui.open.service_view.mode_stock');
        $panelName = trim((string) ($service['panel_base_url'] ?? ''));
        if ($panelName === '') {
            $panelName = $this->catalog->get('admin.ui.open.service_view.panel_none');
        }
        $statusText = ((int) ($service['is_active'] ?? 0)) === 1
            ? $this->catalog->get('admin.ui.open.common.status_active')
            : $this->catalog->get('admin.ui.open.common.status_inactive');
        $tariffCount = $this->database->countTariffsByService($serviceId);
        $stockCount = $this->database->countAvailableConfigsByService($serviceId);

        $buttons = [
            [$this->uiConst(self::ADMIN_SERVICE_EDIT), $this->uiConst(self::ADMIN_SERVICE_TOGGLE)],
            [$this->uiConst(self::ADMIN_SERVICE_DELETE)],
            [$this->uiConst(self::ADMIN_SERVICE_TARIFFS), $this->uiConst(self::ADMIN_SERVICE_INVENTORY)],
            [$this->uiConst(self::ADMIN_SERVICE_TARIFF_ADD), $this->uiConst(self::ADMIN_SERVICE_STOCK_ADD)],
        ];
        $buttons[] = [UiLabels::back($this->catalog), UiLabels::main($this->catalog)];

        $this->database->setUserState($userId, 'admin.service.view', [
            'type_id' => $typeId,
            'service_id' => $serviceId,
            'stack' => ['admin.service.list', 'admin.service.landing', 'admin.root'],
        ]);
        if ($notice !== null && $notice !== '') {
            $this->telegram->sendMessage($chatId, $notice);
        }
        $this->telegram->sendMessage(
            $chatId,
            $this->messageRenderer->render('admin.ui.open.service_view.overview', [
                'service_name' => (string) ($service['name'] ?? $this->catalog->get('messages.generic.dash')),
                'service_id' => $serviceId,
                'type_name' => (string) ($type['name'] ?? $this->catalog->get('messages.generic.dash')),
                'type_id' => $typeId,
                'mode_text' => $modeText,
                'panel_name' => $panelName,
                'status_text' => $statusText,
                'tariff_count' => $tariffCount,
                'stock_count' => $stockCount,
            ]),
            $this->uiKeyboard->replyMenu($buttons)
        );
    }

    /** @param array<string,mixed> $data */
    private function handleAdminServiceCreateBack(int $chatId, int $userId, int $typeId, string $step, array $data): void
    {
        if ($step === 'name') {
            $this->openAdminTypeView($chatId, $userId, $typeId);
            return;
        }
        if ($step === 'mode') {
            $this->database->setUserState($userId, 'admin.service.create', ['type_id' => $typeId, 'step' => 'name', 'data' => $data]);
            $this->telegram->sendMessage($chatId, $this->catalog->get('admin.types_packages.prompts.service_wizard.name'), $this->uiKeyboard->replyMenu([[UiLabels::back($this->catalog), UiLabels::main($this->catalog)]]));
            return;
        }
        if ($step === 'panel_base_url') {
            $this->promptServiceModeSelection($chatId, $userId, $typeId, 'admin.service.create', $data);
            return;
        }
        if ($step === 'panel_username') {
            $this->database->setUserState($userId, 'admin.service.create', ['type_id' => $typeId, 'step' => 'panel_base_url', 'data' => $data]);
            $this->telegram->sendMessage($chatId, $this->messageRenderer->render('admin.panel_settings.messages.wizard_step_base_url'), $this->uiKeyboard->replyMenu([[UiLabels::back($this->catalog), UiLabels::main($this->catalog)]]));
            return;
        }
        if ($step === 'panel_password') {
            $this->database->setUserState($userId, 'admin.service.create', ['type_id' => $typeId, 'step' => 'panel_username', 'data' => $data]);
            $this->telegram->sendMessage($chatId, $this->messageRenderer->render('admin.panel_settings.messages.wizard_step_username'), $this->uiKeyboard->replyMenu([[UiLabels::back($this->catalog), UiLabels::main($this->catalog)]]));
            return;
        }
        if ($step === 'confirm') {
            if ((string) ($data['mode'] ?? 'stock') === 'panel_auto') {
                $this->database->setUserState($userId, 'admin.service.create', ['type_id' => $typeId, 'step' => 'panel_password', 'data' => $data]);
                $this->telegram->sendMessage($chatId, $this->messageRenderer->render('admin.panel_settings.messages.wizard_step_password'), $this->uiKeyboard->replyMenu([[UiLabels::back($this->catalog), UiLabels::main($this->catalog)]]));
                return;
            }
            $this->promptServiceModeSelection($chatId, $userId, $typeId, 'admin.service.create', $data);
            return;
        }
        $this->openAdminTypeView($chatId, $userId, $typeId);
    }

    /** @param array<string,mixed> $data */
    private function handleAdminServiceEditBack(int $chatId, int $userId, int $typeId, int $serviceId, string $step, array $data): void
    {
        if ($step === 'name') {
            $this->openAdminServiceView($chatId, $userId, $typeId, $serviceId);
            return;
        }
        if ($step === 'mode') {
            $this->database->setUserState($userId, 'admin.service.edit', ['type_id' => $typeId, 'service_id' => $serviceId, 'step' => 'name', 'data' => $data]);
            $this->telegram->sendMessage($chatId, $this->catalog->get('admin.types_packages.prompts.service_wizard.name'), $this->uiKeyboard->replyMenu([[UiLabels::back($this->catalog), UiLabels::main($this->catalog)]]));
            return;
        }
        if ($step === 'panel_base_url') {
            $this->promptServiceModeSelection($chatId, $userId, $typeId, 'admin.service.edit', $data, ['service_id' => $serviceId]);
            return;
        }
        if ($step === 'panel_username') {
            $this->database->setUserState($userId, 'admin.service.edit', ['type_id' => $typeId, 'service_id' => $serviceId, 'step' => 'panel_base_url', 'data' => $data]);
            $this->telegram->sendMessage($chatId, $this->messageRenderer->render('admin.panel_settings.messages.wizard_step_base_url'), $this->uiKeyboard->replyMenu([[UiLabels::back($this->catalog), UiLabels::main($this->catalog)]]));
            return;
        }
        if ($step === 'panel_password') {
            $this->database->setUserState($userId, 'admin.service.edit', ['type_id' => $typeId, 'service_id' => $serviceId, 'step' => 'panel_username', 'data' => $data]);
            $this->telegram->sendMessage($chatId, $this->messageRenderer->render('admin.panel_settings.messages.wizard_step_username'), $this->uiKeyboard->replyMenu([[UiLabels::back($this->catalog), UiLabels::main($this->catalog)]]));
            return;
        }
        if ($step === 'confirm') {
            if ((string) ($data['mode'] ?? 'stock') === 'panel_auto') {
                $this->database->setUserState($userId, 'admin.service.edit', ['type_id' => $typeId, 'service_id' => $serviceId, 'step' => 'panel_password', 'data' => $data]);
                $this->telegram->sendMessage($chatId, $this->messageRenderer->render('admin.panel_settings.messages.wizard_step_password'), $this->uiKeyboard->replyMenu([[UiLabels::back($this->catalog), UiLabels::main($this->catalog)]]));
                return;
            }
            $this->promptServiceModeSelection($chatId, $userId, $typeId, 'admin.service.edit', $data, ['service_id' => $serviceId]);
            return;
        }
        $this->openAdminServiceView($chatId, $userId, $typeId, $serviceId);
    }

    /** @param array<string,mixed> $data */
    private function applyServiceWizardInput(
        int $chatId,
        int $userId,
        int $typeId,
        string $step,
        string $text,
        array &$data,
        string $stateName,
        array $extraPayload = []
    ): bool {
        $raw = trim($text);
        if ($raw === '' || str_starts_with($raw, '/')) {
            $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('admin.types_packages.errors.service_name_required')));
            return false;
        }

        if ($step === 'name') {
            $excludeServiceId = isset($extraPayload['service_id']) ? (int) $extraPayload['service_id'] : null;
            if ($this->database->serviceNameExists($raw, $excludeServiceId)) {
                $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('admin.types_packages.errors.service_name_duplicate')));
                return false;
            }
            $data['name'] = $raw;
            $this->promptServiceModeSelection($chatId, $userId, $typeId, $stateName, $data, $extraPayload);
            return false;
        }
        if ($step === 'mode') {
            $modeStock = $this->catalog->get('admin.types_packages.prompts.service_wizard.mode_stock');
            $modePanel = $this->catalog->get('admin.types_packages.prompts.service_wizard.mode_panel_auto');
            $mode = '';
            if ($raw === $modeStock) {
                $mode = 'stock';
            } elseif ($raw === $modePanel) {
                $mode = 'panel_auto';
            }
            if ($mode === '') {
                $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('admin.types_packages.errors.service_mode_invalid')));
                return false;
            }
            $data['mode'] = $mode;
            if ($mode === 'stock') {
                $data['panel_provider'] = null;
                $data['panel_base_url'] = null;
                $data['panel_username'] = null;
                $data['panel_password'] = null;
                $this->database->setUserState($userId, $stateName, array_merge($extraPayload, ['type_id' => $typeId, 'step' => 'confirm', 'data' => $data]));
                $this->telegram->sendMessage(
                    $chatId,
                    $this->messageRenderer->render('admin.types_packages.messages.service_wizard_preview_stock', [
                        'name' => (string) ($data['name'] ?? ''),
                        'mode' => $this->catalog->get('admin.types_packages.labels.mode_stock_fa'),
                    ]),
                    $this->uiKeyboard->replyMenu([
                        [$this->catalog->get('buttons.confirm_yes')],
                        [UiLabels::back($this->catalog), UiLabels::main($this->catalog)],
                    ])
                );
                return false;
            }
            $this->database->setUserState($userId, $stateName, array_merge($extraPayload, ['type_id' => $typeId, 'step' => 'panel_base_url', 'data' => $data]));
            $this->telegram->sendMessage($chatId, $this->messageRenderer->render('admin.panel_settings.messages.wizard_step_base_url'), $this->uiKeyboard->replyMenu([[UiLabels::back($this->catalog), UiLabels::main($this->catalog)]]));
            return false;
        }
        if ($step === 'panel_base_url') {
            if ($raw === '' || (!str_starts_with($raw, 'https://') && !str_starts_with($raw, 'http://'))) {
                $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('admin.panel_settings.errors.invalid_input')));
                return false;
            }
            $data['panel_provider'] = 'pasarguard';
            $data['panel_base_url'] = $raw;
            $this->database->setUserState($userId, $stateName, array_merge($extraPayload, ['type_id' => $typeId, 'step' => 'panel_username', 'data' => $data]));
            $this->telegram->sendMessage($chatId, $this->messageRenderer->render('admin.panel_settings.messages.wizard_step_username'), $this->uiKeyboard->replyMenu([[UiLabels::back($this->catalog), UiLabels::main($this->catalog)]]));
            return false;
        }
        if ($step === 'panel_username') {
            $data['panel_username'] = $raw;
            $this->database->setUserState($userId, $stateName, array_merge($extraPayload, ['type_id' => $typeId, 'step' => 'panel_password', 'data' => $data]));
            $this->telegram->sendMessage($chatId, $this->messageRenderer->render('admin.panel_settings.messages.wizard_step_password'), $this->uiKeyboard->replyMenu([[UiLabels::back($this->catalog), UiLabels::main($this->catalog)]]));
            return false;
        }
        if ($step === 'panel_password') {
            $data['panel_password'] = $raw;
            $panelName = (string) ($data['panel_base_url'] ?? $this->catalog->get('admin.ui.open.service_view.panel_none'));
            $this->database->setUserState($userId, $stateName, array_merge($extraPayload, ['type_id' => $typeId, 'step' => 'confirm', 'data' => $data]));
            $this->telegram->sendMessage(
                $chatId,
                $this->messageRenderer->render('admin.types_packages.messages.service_wizard_preview_panel', [
                    'name' => (string) ($data['name'] ?? ''),
                    'mode' => $this->catalog->get('admin.types_packages.labels.mode_panel_auto_fa'),
                    'panel' => $panelName,
                ]),
                $this->uiKeyboard->replyMenu([
                    [$this->catalog->get('buttons.confirm_yes')],
                    [UiLabels::back($this->catalog), UiLabels::main($this->catalog)],
                ])
            );
            return false;
        }
        if ($step === 'confirm') {
            if ($raw !== $this->catalog->get('buttons.confirm_yes')) {
                $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('admin.types_packages.errors.confirm_required')));
                return false;
            }
            if ((string) ($data['mode'] ?? '') === 'panel_auto') {
                $baseUrl = trim((string) ($data['panel_base_url'] ?? ''));
                $panelUsername = trim((string) ($data['panel_username'] ?? ''));
                $panelPassword = trim((string) ($data['panel_password'] ?? ''));
                if ($baseUrl === '' || $panelUsername === '' || $panelPassword === '') {
                    $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('admin.panel_settings.errors.invalid_input')));
                    return false;
                }
            }
            return true;
        }
        return false;
    }

    /** @param array<string,mixed> $data */
    private function promptServiceModeSelection(
        int $chatId,
        int $userId,
        int $typeId,
        string $stateName,
        array $data,
        array $extraPayload = []
    ): void {
        $this->database->setUserState($userId, $stateName, array_merge($extraPayload, ['type_id' => $typeId, 'step' => 'mode', 'data' => $data]));
        $this->telegram->sendMessage(
            $chatId,
            $this->catalog->get('admin.types_packages.prompts.service_wizard.mode'),
            $this->uiKeyboard->replyMenu([
                [$this->catalog->get('admin.types_packages.prompts.service_wizard.mode_stock'), $this->catalog->get('admin.types_packages.prompts.service_wizard.mode_panel_auto')],
                [UiLabels::back($this->catalog), UiLabels::main($this->catalog)],
            ])
        );
    }

    private function openAdminServiceTariffsView(int $chatId, int $userId, int $typeId, int $serviceId, ?string $notice = null): void
    {
        $service = $this->database->getService($serviceId);
        if (!is_array($service) || (int) ($service['type_id'] ?? 0) !== $typeId) {
            $this->openAdminTypeView($chatId, $userId, $typeId, $this->uiText->warning($this->catalog->get('admin.types_packages.errors.service_not_found')));
            return;
        }
        $tariffs = $this->database->listTariffsByService($serviceId);
        $options = [];
        $lines = [];
        $buttons = [[$this->uiConst(self::ADMIN_SERVICE_TARIFF_ADD)]];
        foreach (array_values($tariffs) as $idx => $tariff) {
            $num = (string) ($idx + 1);
            $tariffId = (int) ($tariff['id'] ?? 0);
            if ($tariffId <= 0) {
                continue;
            }
            $mode = (string) ($tariff['pricing_mode'] ?? 'fixed');
            $summary = $mode === 'fixed'
                ? $this->catalog->get('admin.types_packages.labels.tariff_fixed_row', [
                    'volume_gb' => (string) ($tariff['volume_gb'] ?? '0'),
                    'duration_days' => (string) ($tariff['duration_days'] ?? '0'),
                    'price' => (string) ($tariff['price'] ?? '0'),
                ])
                : $this->catalog->get('admin.types_packages.labels.tariff_per_gb_row', [
                    'min_volume_gb' => (string) ($tariff['min_volume_gb'] ?? '0'),
                    'max_volume_gb' => (string) ($tariff['max_volume_gb'] ?? '0'),
                    'step_volume_gb' => (string) ($tariff['step_volume_gb'] ?? '1'),
                    'price_per_gb' => (string) ($tariff['price_per_gb'] ?? '0'),
                ]);
            $lines[] = $this->catalog->get('admin.types_packages.labels.tariff_list_row', [
                'num' => $num,
                'title' => (string) ($tariff['title'] ?? $this->catalog->get('messages.generic.dash')),
                'tariff_id' => $tariffId,
                'summary' => $summary,
            ]);
            $options[$num] = $tariffId;
            $buttons[] = [$this->catalog->get('admin.types_packages.labels.tariff_option_button', ['num' => $num, 'title' => (string) ($tariff['title'] ?? $this->catalog->get('messages.generic.dash'))])];
        }
        $buttons[] = [UiLabels::back($this->catalog), UiLabels::main($this->catalog)];
        $this->database->setUserState($userId, 'admin.service.tariffs', [
            'type_id' => $typeId,
            'service_id' => $serviceId,
            'options' => $options,
        ]);
        if ($notice !== null && $notice !== '') {
            $this->telegram->sendMessage($chatId, $notice);
        }
        $this->telegram->sendMessage(
            $chatId,
            $this->messageRenderer->render('admin.types_packages.messages.tariffs_overview', [
                'service_name' => (string) ($service['name'] ?? $this->catalog->get('messages.generic.dash')),
                'list' => $lines !== [] ? implode("\n", $lines) : $this->catalog->get('admin.types_packages.messages.tariffs_empty'),
            ], ['list']),
            $this->uiKeyboard->replyMenu($buttons)
        );
    }

    private function openAdminServiceTariffDetailView(int $chatId, int $userId, int $typeId, int $serviceId, int $tariffId, ?string $notice = null): void
    {
        $tariff = $this->database->getServiceTariff($tariffId);
        if (!is_array($tariff) || (int) ($tariff['service_id'] ?? 0) !== $serviceId) {
            $this->openAdminServiceTariffsView($chatId, $userId, $typeId, $serviceId, $this->uiText->warning($this->catalog->get('admin.types_packages.errors.tariff_not_found')));
            return;
        }
        $mode = (string) ($tariff['pricing_mode'] ?? 'fixed');
        $modeText = $mode === 'fixed'
            ? $this->catalog->get('admin.types_packages.labels.pricing_mode_fixed')
            : $this->catalog->get('admin.types_packages.labels.pricing_mode_per_gb');
        $summary = $mode === 'fixed'
            ? $this->catalog->get('admin.types_packages.labels.tariff_fixed_detail', [
                'volume_gb' => (string) ($tariff['volume_gb'] ?? '0'),
                'duration_days' => (string) ($tariff['duration_days'] ?? '0'),
                'price' => (string) ($tariff['price'] ?? '0'),
            ])
            : $this->catalog->get('admin.types_packages.labels.tariff_per_gb_detail', [
                'min_volume_gb' => (string) ($tariff['min_volume_gb'] ?? '0'),
                'max_volume_gb' => (string) ($tariff['max_volume_gb'] ?? '0'),
                'step_volume_gb' => (string) ($tariff['step_volume_gb'] ?? '1'),
                'price_per_gb' => (string) ($tariff['price_per_gb'] ?? '0'),
                'duration_policy' => (string) (($tariff['duration_policy'] ?? '') !== '' ? $tariff['duration_policy'] : $this->catalog->get('messages.generic.dash')),
                'duration_days' => (string) (($tariff['duration_days'] ?? null) !== null ? $tariff['duration_days'] : $this->catalog->get('messages.generic.dash')),
            ]);
        $this->database->setUserState($userId, 'admin.service.tariffs', [
            'type_id' => $typeId,
            'service_id' => $serviceId,
            'options' => [],
        ]);
        if ($notice !== null && $notice !== '') {
            $this->telegram->sendMessage($chatId, $notice);
        }
        $this->telegram->sendMessage(
            $chatId,
            $this->messageRenderer->render('admin.types_packages.messages.tariff_detail_overview', [
                'tariff_id' => $tariffId,
                'title' => (string) ($tariff['title'] ?? $this->catalog->get('messages.generic.dash')),
                'mode_text' => $modeText,
                'summary' => $summary,
            ]),
            $this->uiKeyboard->replyMenu([
                [$this->catalog->get('admin.types_packages.actions.service_tariff_edit'), $this->catalog->get('admin.types_packages.actions.service_tariff_delete')],
                [UiLabels::back($this->catalog), UiLabels::main($this->catalog)],
            ])
        );
        $this->database->setUserState($userId, 'admin.service.tariffs', [
            'type_id' => $typeId,
            'service_id' => $serviceId,
            'options' => [],
            'selected_tariff_id' => $tariffId,
        ]);
    }

    /** @param array<string,mixed> $data */
    private function handleTariffWizardBack(int $chatId, int $userId, int $typeId, int $serviceId, int $tariffId, string $stateName, string $step, array $data): void
    {
        if ($step === 'title') {
            $this->openAdminServiceTariffsView($chatId, $userId, $typeId, $serviceId);
            return;
        }
        $prev = [
            'pricing_mode' => 'title',
            'volume_gb' => 'pricing_mode',
            'duration_days' => 'volume_gb',
            'price' => 'duration_days',
            'min_volume_gb' => 'pricing_mode',
            'max_volume_gb' => 'min_volume_gb',
            'step_volume_gb' => 'max_volume_gb',
            'price_per_gb' => 'step_volume_gb',
            'duration_policy' => 'price_per_gb',
            'confirm' => 'duration_policy',
        ];
        $backStep = $prev[$step] ?? 'title';
        $payload = ['type_id' => $typeId, 'service_id' => $serviceId, 'step' => $backStep, 'data' => $data];
        if ($stateName === 'admin.service.tariff.edit') {
            $payload['tariff_id'] = $tariffId;
        }
        $this->database->setUserState($userId, $stateName, $payload);
        $this->promptTariffWizardStep($chatId, $userId, $serviceId, $stateName, $backStep, $data, $tariffId);
    }

    /** @param array<string,mixed> $data */
    private function promptTariffWizardStep(int $chatId, int $userId, int $serviceId, string $stateName, string $step, array $data, int $tariffId = 0): void
    {
        if ($step === 'pricing_mode') {
            $payload = ['service_id' => $serviceId, 'step' => 'pricing_mode', 'data' => $data];
            if ($stateName === 'admin.service.tariff.edit' && $tariffId > 0) {
                $payload['tariff_id'] = $tariffId;
            }
            $this->database->setUserState($userId, $stateName, $payload);
            $this->telegram->sendMessage(
                $chatId,
                $this->uiText->info($this->catalog->get('admin.types_packages.prompts.tariff_wizard.pricing_mode')),
                $this->uiKeyboard->replyMenu([
                    [$this->catalog->get('admin.types_packages.labels.pricing_mode_fixed'), $this->catalog->get('admin.types_packages.labels.pricing_mode_per_gb')],
                    [UiLabels::back($this->catalog), UiLabels::main($this->catalog)],
                ])
            );
            return;
        }
        $text = $this->catalog->get('admin.types_packages.prompts.tariff_wizard.' . $step);
        $payload = ['service_id' => $serviceId, 'step' => $step, 'data' => $data];
        if ($stateName === 'admin.service.tariff.edit' && $tariffId > 0) {
            $payload['tariff_id'] = $tariffId;
        }
        $this->database->setUserState($userId, $stateName, $payload);
        $this->telegram->sendMessage($chatId, $this->uiText->info($text), $this->uiKeyboard->replyMenu([[UiLabels::back($this->catalog), UiLabels::main($this->catalog)]]));
    }

    /** @param array<string,mixed> $data */
    private function applyTariffWizardInput(int $chatId, int $userId, int $typeId, int $serviceId, string $stateName, string $step, string $text, array &$data, int $tariffId = 0): bool
    {
        $raw = trim($text);
        if ($raw === '' || str_starts_with($raw, '/')) {
            $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('admin.types_packages.errors.tariff_invalid_input')));
            return false;
        }

        if ($step === 'title') {
            $data['title'] = $raw;
            $this->promptTariffWizardStep($chatId, $userId, $serviceId, $stateName, 'pricing_mode', $data, $tariffId);
            return false;
        }
        if ($step === 'pricing_mode') {
            if ($raw === $this->catalog->get('admin.types_packages.labels.pricing_mode_fixed')) {
                $data['pricing_mode'] = 'fixed';
                $this->promptTariffWizardStep($chatId, $userId, $serviceId, $stateName, 'volume_gb', $data, $tariffId);
                return false;
            }
            if ($raw === $this->catalog->get('admin.types_packages.labels.pricing_mode_per_gb')) {
                $data['pricing_mode'] = 'per_gb';
                $this->promptTariffWizardStep($chatId, $userId, $serviceId, $stateName, 'min_volume_gb', $data, $tariffId);
                return false;
            }
            $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('admin.types_packages.errors.tariff_invalid_pricing_mode')));
            return false;
        }
        if ($step === 'volume_gb') {
            $val = (float) str_replace(',', '.', $raw);
            if ($val <= 0) {
                $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('admin.types_packages.errors.tariff_fixed_required')));
                return false;
            }
            $data['volume_gb'] = $val;
            $this->promptTariffWizardStep($chatId, $userId, $serviceId, $stateName, 'duration_days', $data, $tariffId);
            return false;
        }
        if ($step === 'duration_days' && (string) ($data['pricing_mode'] ?? '') === 'fixed') {
            $days = (int) preg_replace('/\D+/', '', $raw);
            if ($days <= 0) {
                $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('admin.types_packages.errors.tariff_fixed_required')));
                return false;
            }
            $data['duration_days'] = $days;
            $this->promptTariffWizardStep($chatId, $userId, $serviceId, $stateName, 'price', $data, $tariffId);
            return false;
        }
        if ($step === 'price') {
            $price = (int) preg_replace('/\D+/', '', $raw);
            if ($price <= 0) {
                $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('admin.types_packages.errors.tariff_fixed_required')));
                return false;
            }
            $data['price'] = $price;
            $this->promptTariffConfirm($chatId, $userId, $typeId, $serviceId, $stateName, $data, $tariffId);
            return false;
        }
        if ($step === 'min_volume_gb') {
            $val = (float) str_replace(',', '.', $raw);
            if ($val <= 0) {
                $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('admin.types_packages.errors.tariff_per_gb_required')));
                return false;
            }
            $data['min_volume_gb'] = $val;
            $this->promptTariffWizardStep($chatId, $userId, $serviceId, $stateName, 'max_volume_gb', $data, $tariffId);
            return false;
        }
        if ($step === 'max_volume_gb') {
            $val = (float) str_replace(',', '.', $raw);
            if ($val < (float) ($data['min_volume_gb'] ?? 0)) {
                $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('admin.types_packages.errors.tariff_per_gb_required')));
                return false;
            }
            $data['max_volume_gb'] = $val;
            $this->promptTariffWizardStep($chatId, $userId, $serviceId, $stateName, 'step_volume_gb', $data, $tariffId);
            return false;
        }
        if ($step === 'step_volume_gb') {
            $val = (float) str_replace(',', '.', $raw);
            if ($val <= 0) {
                $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('admin.types_packages.errors.tariff_per_gb_required')));
                return false;
            }
            $data['step_volume_gb'] = $val;
            $this->promptTariffWizardStep($chatId, $userId, $serviceId, $stateName, 'price_per_gb', $data, $tariffId);
            return false;
        }
        if ($step === 'price_per_gb') {
            $val = (int) preg_replace('/\D+/', '', $raw);
            if ($val <= 0) {
                $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('admin.types_packages.errors.tariff_per_gb_required')));
                return false;
            }
            $data['price_per_gb'] = $val;
            $this->promptTariffWizardStep($chatId, $userId, $serviceId, $stateName, 'duration_policy', $data, $tariffId);
            return false;
        }
        if ($step === 'duration_policy') {
            $allowed = ['fixed_days', 'unlimited'];
            if (!in_array($raw, $allowed, true)) {
                $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('admin.types_packages.errors.tariff_invalid_duration_policy')));
                return false;
            }
            $data['duration_policy'] = $raw;
            if ($raw === 'fixed_days') {
                $this->promptTariffWizardStep($chatId, $userId, $serviceId, $stateName, 'duration_days', $data, $tariffId);
                return false;
            }
            $this->promptTariffConfirm($chatId, $userId, $typeId, $serviceId, $stateName, $data, $tariffId);
            return false;
        }
        if ($step === 'duration_days' && (string) ($data['pricing_mode'] ?? '') === 'per_gb') {
            $days = (int) preg_replace('/\D+/', '', $raw);
            if ($days <= 0) {
                $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('admin.types_packages.errors.tariff_duration_days_required')));
                return false;
            }
            $data['duration_days'] = $days;
            $this->promptTariffConfirm($chatId, $userId, $typeId, $serviceId, $stateName, $data, $tariffId);
            return false;
        }
        if ($step === 'confirm') {
            if ($raw !== $this->catalog->get('buttons.confirm_yes')) {
                $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('admin.types_packages.errors.confirm_required')));
                return false;
            }
            if (!$this->validateTariffData($data)) {
                $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('admin.types_packages.errors.tariff_validation_failed')));
                return false;
            }
            return true;
        }
        return false;
    }

    /** @param array<string,mixed> $data */
    private function promptTariffConfirm(int $chatId, int $userId, int $typeId, int $serviceId, string $stateName, array $data, int $tariffId = 0): void
    {
        $summary = $this->catalog->get('admin.types_packages.prompts.tariff_wizard.summary_template', [
            'title' => (string) ($data['title'] ?? ''),
            'pricing_mode' => (string) ($data['pricing_mode'] ?? ''),
            'volume_gb' => (string) (($data['volume_gb'] ?? null) !== null ? $data['volume_gb'] : $this->catalog->get('messages.generic.dash')),
            'duration_days' => (string) (($data['duration_days'] ?? null) !== null ? $data['duration_days'] : $this->catalog->get('messages.generic.dash')),
            'price' => (string) (($data['price'] ?? null) !== null ? $data['price'] : $this->catalog->get('messages.generic.dash')),
            'min_volume_gb' => (string) (($data['min_volume_gb'] ?? null) !== null ? $data['min_volume_gb'] : $this->catalog->get('messages.generic.dash')),
            'max_volume_gb' => (string) (($data['max_volume_gb'] ?? null) !== null ? $data['max_volume_gb'] : $this->catalog->get('messages.generic.dash')),
            'step_volume_gb' => (string) (($data['step_volume_gb'] ?? null) !== null ? $data['step_volume_gb'] : $this->catalog->get('messages.generic.dash')),
            'price_per_gb' => (string) (($data['price_per_gb'] ?? null) !== null ? $data['price_per_gb'] : $this->catalog->get('messages.generic.dash')),
            'duration_policy' => (string) (($data['duration_policy'] ?? null) !== null ? $data['duration_policy'] : $this->catalog->get('messages.generic.dash')),
        ]);
        $payload = ['type_id' => $typeId, 'service_id' => $serviceId, 'step' => 'confirm', 'data' => $data];
        if ($stateName === 'admin.service.tariff.edit' && $tariffId > 0) {
            $payload['tariff_id'] = $tariffId;
        }
        $this->database->setUserState($userId, $stateName, $payload);
        $this->telegram->sendMessage(
            $chatId,
            $this->messageRenderer->render('admin.types_packages.messages.tariff_wizard_summary', ['summary' => $summary]),
            $this->uiKeyboard->replyMenu([
                [$this->catalog->get('buttons.confirm_yes')],
                [UiLabels::back($this->catalog), UiLabels::main($this->catalog)],
            ])
        );
    }

    /** @param array<string,mixed> $data */
    private function validateTariffData(array $data): bool
    {
        $mode = (string) ($data['pricing_mode'] ?? '');
        if ($mode === 'fixed') {
            return (float) ($data['volume_gb'] ?? 0) > 0
                && (int) ($data['duration_days'] ?? 0) > 0
                && (int) ($data['price'] ?? 0) > 0;
        }
        if ($mode === 'per_gb') {
            $required = (float) ($data['min_volume_gb'] ?? 0) > 0
                && (float) ($data['max_volume_gb'] ?? 0) >= (float) ($data['min_volume_gb'] ?? 0)
                && (float) ($data['step_volume_gb'] ?? 0) > 0
                && (int) ($data['price_per_gb'] ?? 0) > 0;
            if (!$required) {
                return false;
            }
            if ((string) ($data['duration_policy'] ?? '') === 'fixed_days') {
                return (int) ($data['duration_days'] ?? 0) > 0;
            }
            return true;
        }
        return false;
    }

    private function openAdminServiceInventoryView(int $chatId, int $userId, int $typeId, int $serviceId, ?string $notice = null): void
    {
        $service = $this->database->getService($serviceId);
        if (!is_array($service) || (int) ($service['type_id'] ?? 0) !== $typeId) {
            $this->openAdminServiceView($chatId, $userId, $typeId, $serviceId, $this->uiText->warning($this->catalog->get('admin.types_packages.errors.service_not_found')));
            return;
        }
        $configs = $this->database->listConfigsByService($serviceId, null, 20, 0);
        $options = [];
        $lines = [];
        foreach (array_values($configs) as $idx => $cfg) {
            $num = (string) ($idx + 1);
            $configId = (int) ($cfg['id'] ?? 0);
            if ($configId <= 0) {
                continue;
            }
            $status = ((int) ($cfg['is_expired'] ?? 0)) === 1
                ? $this->catalog->get('admin.ui.open.stock.common.status_symbol_expired')
                : (((int) ($cfg['sold_to'] ?? 0)) > 0 ? $this->catalog->get('admin.ui.open.stock.common.status_symbol_sold') : $this->catalog->get('admin.ui.open.stock.common.status_symbol_ready'));
            $lines[] = $this->catalog->get('admin.types_packages.labels.service_inventory_row', [
                'num' => $num,
                'status' => $status,
                'service_name' => (string) ($cfg['service_name'] ?? $this->catalog->get('messages.generic.dash')),
                'config_id' => $configId,
            ]);
            $options[$num] = $configId;
        }
        $this->database->setUserState($userId, 'admin.service.inventory', ['type_id' => $typeId, 'service_id' => $serviceId, 'options' => $options]);
        if ($notice !== null && $notice !== '') {
            $this->telegram->sendMessage($chatId, $notice);
        }
        $this->telegram->sendMessage(
            $chatId,
            $this->messageRenderer->render('admin.types_packages.messages.service_inventory_overview', [
                'service_name' => (string) ($service['name'] ?? $this->catalog->get('messages.generic.dash')),
                'list' => $lines !== [] ? implode("\n", $lines) : $this->catalog->get('admin.types_packages.messages.service_inventory_empty'),
            ], ['list']),
            $this->uiKeyboard->replyMenu([
                [$this->uiConst(self::ADMIN_SERVICE_INVENTORY_REFRESH), $this->uiConst(self::ADMIN_SERVICE_STOCK_ADD)],
                ...array_map(fn ($num) => [$this->catalog->get('admin.types_packages.labels.service_inventory_option_button', ['num' => $num])], array_keys($options)),
                [UiLabels::back($this->catalog), UiLabels::main($this->catalog)],
            ])
        );
    }

    private function openAdminServiceInventoryDetailView(int $chatId, int $userId, int $typeId, int $serviceId, int $configId, ?string $notice = null): void
    {
        $cfg = $this->findConfigById($serviceId, $configId, true);
        if ($cfg === null) {
            $this->openAdminServiceInventoryView($chatId, $userId, $typeId, $serviceId, $this->uiText->warning($this->catalog->get('admin.users_stock.errors.config_not_found')));
            return;
        }
        $status = ((int) ($cfg['is_expired'] ?? 0)) === 1
            ? $this->catalog->get('admin.ui.open.stock.config_detail.status_expired')
            : (((int) ($cfg['sold_to'] ?? 0)) > 0 ? $this->catalog->get('admin.ui.open.stock.config_detail.status_sold') : $this->catalog->get('admin.ui.open.stock.config_detail.status_ready'));
        $this->database->setUserState($userId, 'admin.service.inventory.detail', ['type_id' => $typeId, 'service_id' => $serviceId, 'config_id' => $configId]);
        if ($notice !== null && $notice !== '') {
            $this->telegram->sendMessage($chatId, $notice);
        }
        $this->telegram->sendMessage(
            $chatId,
            $this->messageRenderer->render('admin.ui.open.stock.config_detail.overview', [
                'config_id' => $configId,
                'service_name' => (string) ($cfg['service_name'] ?? $this->catalog->get('messages.generic.dash')),
                'status' => $status,
            ]),
            $this->uiKeyboard->replyMenu([
                [$this->uiConst(self::ADMIN_STOCK_EXPIRE_TOGGLE), $this->uiConst(self::ADMIN_STOCK_DELETE_CONFIG)],
                [UiLabels::back($this->catalog), UiLabels::main($this->catalog)],
            ])
        );
    }

    /** @param array<string,mixed> $data */
    private function promptServiceInventoryTariffStep(int $chatId, int $userId, int $typeId, int $serviceId, array $data, string $stateName): void
    {
        $tariffs = $this->database->listTariffsByService($serviceId);
        $options = [$this->catalog->get('admin.types_packages.labels.service_inventory_no_tariff') => 0];
        $rows = [[$this->catalog->get('admin.types_packages.labels.service_inventory_no_tariff')]];
        foreach ($tariffs as $tariff) {
            $tariffId = (int) ($tariff['id'] ?? 0);
            if ($tariffId <= 0) {
                continue;
            }
            $label = $this->catalog->get('admin.types_packages.labels.service_inventory_tariff_button', [
                'tariff_id' => $tariffId,
                'title' => (string) ($tariff['title'] ?? $this->catalog->get('messages.generic.dash')),
            ]);
            $options[$label] = $tariffId;
            $rows[] = [$label];
        }
        $rows[] = [UiLabels::back($this->catalog), UiLabels::main($this->catalog)];
        $this->database->setUserState($userId, $stateName, [
            'type_id' => $typeId,
            'service_id' => $serviceId,
            'step' => 'tariff',
            'data' => $data,
            'tariff_options' => $options,
        ]);
        $this->telegram->sendMessage($chatId, $this->uiText->info($this->catalog->get('admin.types_packages.prompts.service_inventory.tariff')), $this->uiKeyboard->replyMenu($rows));
    }

    /** @param array<string,mixed> $data */
    private function applyServiceInventoryAddInput(int $chatId, int $userId, int $typeId, int $serviceId, string $step, string $text, array &$data): bool
    {
        $raw = trim($text);
        if ($raw === '' || str_starts_with($raw, '/')) {
            $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('admin.types_packages.errors.service_inventory_invalid_input')));
            return false;
        }
        $state = $this->database->getUserState($userId);
        $payload = is_array($state['payload'] ?? null) ? $state['payload'] : [];
        if ($step === 'tariff') {
            $options = is_array($payload['tariff_options'] ?? null) ? $payload['tariff_options'] : [];
            if (!array_key_exists($raw, $options)) {
                $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('admin.types_packages.errors.service_inventory_tariff_invalid')));
                return false;
            }
            $selectedTariffId = (int) $options[$raw];
            if ($selectedTariffId > 0) {
                $tariff = $this->database->getServiceTariff($selectedTariffId);
                if (!is_array($tariff) || (int) ($tariff['service_id'] ?? 0) !== $serviceId) {
                    $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('admin.types_packages.errors.service_inventory_tariff_invalid')));
                    return false;
                }
                $data['tariff_id'] = $selectedTariffId;
            } else {
                $data['tariff_id'] = null;
            }
            $this->database->setUserState($userId, 'admin.service.inventory.add', ['type_id' => $typeId, 'service_id' => $serviceId, 'step' => 'payload', 'data' => $data]);
            $this->telegram->sendMessage($chatId, $this->uiText->info($this->catalog->get('admin.types_packages.prompts.service_inventory.payload')), $this->uiKeyboard->replyMenu([[UiLabels::back($this->catalog), UiLabels::main($this->catalog)]]));
            return false;
        }
        if ($step === 'payload') {
            $chunks = preg_split('/\n---\n/', $raw) ?: [];
            if (count($chunks) < 2) {
                $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('admin.types_packages.errors.service_inventory_payload_format')));
                return false;
            }
            $serviceName = trim((string) ($chunks[0] ?? ''));
            $configText = trim((string) ($chunks[1] ?? ''));
            $inquiry = null;
            if (isset($chunks[2])) {
                $third = trim((string) $chunks[2]);
                $inquiry = $third !== '' ? $third : null;
            }
            if ($serviceName === '' || $configText === '') {
                $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('admin.types_packages.errors.service_inventory_invalid_input')));
                return false;
            }
            $data['service_name'] = $serviceName;
            $data['config_text'] = $configText;
            $data['inquiry_link'] = $inquiry;
            return true;
        }
        return false;
    }

    private function findTypeById(int $typeId): ?array
    {
        return $this->database->getTypeById($typeId);
    }

    private function handleAdminUsersStockState(int $chatId, int $userId, string $text, array $state, array $message): void
    {
        if ($this->isMainMenuInput($text)) {
            $this->openAdminRoot($chatId, $userId);
            return;
        }

        $stateName = (string) ($state['state_name'] ?? '');
        $payload = is_array($state['payload'] ?? null) ? $state['payload'] : [];

        if ($stateName === 'admin.users.list') {
            if ($text === UiLabels::back($this->catalog)) {
                $this->openAdminRoot($chatId, $userId);
                return;
            }
            if ($text === $this->uiConst(self::ADMIN_USERS_REFRESH)) {
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
            $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('admin.users_stock.errors.invalid_user_list_option')));
            return;
        }

        if ($stateName === 'admin.user.view') {
            $targetUid = (int) ($payload['target_user_id'] ?? 0);
            if ($targetUid <= 0) {
                $this->openAdminUsersList($chatId, $userId, $this->uiText->warning($this->catalog->get('admin.users_stock.errors.invalid_selected_user')));
                return;
            }
            if ($text === UiLabels::back($this->catalog)) {
                $this->openAdminUsersList($chatId, $userId);
                return;
            }
            if ($text === $this->uiConst(self::ADMIN_USER_TOGGLE_STATUS)) {
                $target = $this->database->getUser($targetUid);
                if ($target === null) {
                    $this->openAdminUsersList($chatId, $userId, $this->uiText->warning($this->catalog->get('admin.users_stock.errors.user_not_found')));
                    return;
                }
                $status = (string) ($target['status'] ?? 'unsafe');
                $nextStatus = $status === 'restricted' ? 'unsafe' : 'restricted';
                $this->database->setUserStatus($targetUid, $nextStatus);
                $this->openAdminUserView($chatId, $userId, $targetUid, $this->uiText->success($this->catalog->get('admin.users_stock.success.user_status_updated')));
                return;
            }
            if ($text === $this->uiConst(self::ADMIN_USER_TOGGLE_AGENT)) {
                $target = $this->database->getUser($targetUid);
                if ($target === null) {
                    $this->openAdminUsersList($chatId, $userId, $this->uiText->warning($this->catalog->get('admin.users_stock.errors.user_not_found')));
                    return;
                }
                $isAgent = ((int) ($target['is_agent'] ?? 0)) === 1;
                $this->database->setUserAgent($targetUid, !$isAgent);
                $this->openAdminUserView($chatId, $userId, $targetUid, $this->uiText->success($this->catalog->get('admin.users_stock.success.agent_status_updated')));
                return;
            }
            if ($text === $this->uiConst(self::ADMIN_USER_BALANCE_ADD) || $text === $this->uiConst(self::ADMIN_USER_BALANCE_SUB)) {
                $mode = $text === $this->uiConst(self::ADMIN_USER_BALANCE_SUB) ? 'sub' : 'add';
                $this->database->setUserState($userId, 'admin.user.action', [
                    'target_user_id' => $targetUid,
                    'mode' => $mode,
                    'stack' => ['admin.user.view', 'admin.users.list', 'admin.root'],
                ]);
                $modeText = $this->catalog->get($mode === 'sub' ? 'admin.users_stock.labels.balance_mode_sub' : 'admin.users_stock.labels.balance_mode_add');
                $this->telegram->sendMessage(
                    $chatId,
                    $this->messageRenderer->render('admin.users_stock.prompts.balance_action_overview', [
                        'mode_text' => $modeText,
                        'target_uid' => $targetUid,
                    ]),
                    $this->uiKeyboard->replyMenu([[UiLabels::back($this->catalog), UiLabels::main($this->catalog)]])
                );
                return;
            }
            $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('admin.users_stock.errors.invalid_user_action_option')));
            return;
        }

        if ($stateName === 'admin.user.action') {
            $targetUid = (int) ($payload['target_user_id'] ?? 0);
            if ($targetUid <= 0) {
                $this->openAdminUsersList($chatId, $userId, $this->uiText->warning($this->catalog->get('admin.users_stock.errors.invalid_user')));
                return;
            }
            if ($text === UiLabels::back($this->catalog)) {
                $this->openAdminUserView($chatId, $userId, $targetUid);
                return;
            }
            $amount = (int) preg_replace('/\D+/', '', $text);
            if ($amount <= 0) {
                $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('admin.users_stock.errors.invalid_amount')));
                return;
            }
            $mode = (string) ($payload['mode'] ?? 'add');
            $delta = $mode === 'sub' ? -$amount : $amount;
            $this->database->updateUserBalance($targetUid, $delta);
            $this->openAdminUserView($chatId, $userId, $targetUid, $this->uiText->success($this->catalog->get('admin.users_stock.success.user_balance_updated')));
            return;
        }

        if ($stateName === 'admin.stock.view') {
            $level = (string) ($payload['level'] ?? 'types');
            if ($text === UiLabels::back($this->catalog)) {
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
                if ($text === $this->uiConst(self::ADMIN_STOCK_REFRESH)) {
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
                $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('admin.users_stock.errors.invalid_stock_type_option')));
                return;
            }

            if ($level === 'packages') {
                if ($text === $this->uiConst(self::ADMIN_STOCK_REFRESH)) {
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
                $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('admin.users_stock.errors.invalid_stock_package_option')));
                return;
            }

            if ($level === 'configs') {
                $typeId = (int) ($payload['type_id'] ?? 0);
                $packageId = (int) ($payload['package_id'] ?? 0);
                $query = (string) ($payload['query'] ?? '');
                if ($text === $this->uiConst(self::ADMIN_STOCK_REFRESH)) {
                    $this->openAdminStockConfigsView($chatId, $userId, $typeId, $packageId, $query);
                    return;
                }
                if ($text === $this->uiConst(self::ADMIN_STOCK_ADD_CONFIG)) {
                    $this->database->setUserState($userId, 'admin.stock.update', [
                        'mode' => 'add_config',
                        'type_id' => $typeId,
                        'package_id' => $packageId,
                        'stack' => ['admin.stock.view', 'admin.root'],
                    ]);
                    $this->telegram->sendMessage(
                        $chatId,
                        $this->messageRenderer->render('admin.users_stock.prompts.add_config_overview'),
                        $this->uiKeyboard->replyMenu([[UiLabels::back($this->catalog), UiLabels::main($this->catalog)]])
                    );
                    return;
                }
                if ($text === $this->uiConst(self::ADMIN_STOCK_SEARCH)) {
                    $this->database->setUserState($userId, 'admin.stock.update', [
                        'mode' => 'search',
                        'type_id' => $typeId,
                        'package_id' => $packageId,
                        'query' => $query,
                        'stack' => ['admin.stock.view', 'admin.root'],
                    ]);
                    $this->telegram->sendMessage(
                        $chatId,
                        $this->messageRenderer->render('admin.users_stock.prompts.search_config_overview'),
                        $this->uiKeyboard->replyMenu([[UiLabels::back($this->catalog), UiLabels::main($this->catalog)]])
                    );
                    return;
                }
                if ($text === $this->uiConst(self::ADMIN_STOCK_SEARCH_CLEAR)) {
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
                $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('admin.users_stock.errors.invalid_configs_option')));
                return;
            }

            if ($level === 'config_detail') {
                $typeId = (int) ($payload['type_id'] ?? 0);
                $packageId = (int) ($payload['package_id'] ?? 0);
                $configId = (int) ($payload['config_id'] ?? 0);
                $query = (string) ($payload['query'] ?? '');
                if ($text === $this->uiConst(self::ADMIN_STOCK_EXPIRE_TOGGLE)) {
                    $cfg = $this->findConfigById($packageId, $configId);
                    if ($cfg === null) {
                        $this->openAdminStockConfigsView($chatId, $userId, $typeId, $packageId, $query, $this->uiText->warning($this->catalog->get('admin.users_stock.errors.config_not_found')));
                        return;
                    }
                    $isExpired = ((int) ($cfg['is_expired'] ?? 0)) === 1;
                    if ($isExpired) {
                        $this->database->unexpireConfig($configId);
                    } else {
                        $this->database->expireConfig($configId);
                    }
                    $this->openAdminStockConfigDetailView($chatId, $userId, $typeId, $packageId, $configId, $query, $this->uiText->success($this->catalog->get('admin.users_stock.success.config_expire_status_updated')));
                    return;
                }
                if ($text === $this->uiConst(self::ADMIN_STOCK_DELETE_CONFIG)) {
                    $this->database->deleteConfig($configId);
                    $this->openAdminStockConfigsView($chatId, $userId, $typeId, $packageId, $query, $this->uiText->success($this->catalog->get('admin.users_stock.success.config_deleted')));
                    return;
                }
                $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('admin.users_stock.errors.invalid_config_detail_option')));
                return;
            }
        }

        if ($stateName === 'admin.stock.update') {
            $mode = (string) ($payload['mode'] ?? '');
            $typeId = (int) ($payload['type_id'] ?? 0);
            $packageId = (int) ($payload['package_id'] ?? 0);
            if ($text === UiLabels::back($this->catalog)) {
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
                    $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('admin.users_stock.errors.config_text_required')));
                    return;
                }
                $chunks = preg_split('/\n---\n/', $raw) ?: [];
                if (count($chunks) < 2) {
                    $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('admin.users_stock.errors.invalid_config_format')));
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
                    $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('admin.users_stock.errors.invalid_service_or_config_text')));
                    return;
                }
                $configId = $this->database->addConfig($typeId, $packageId, $serviceName, $configText, $inquiry);
                $this->openAdminStockConfigsView($chatId, $userId, $typeId, $packageId, '', $this->uiText->success($this->catalog->get('admin.users_stock.success.config_created', ['config_id' => $configId])));
                return;
            }
        }
    }

    private function openAdminUsersList(int $chatId, int $userId, ?string $notice = null): void
    {
        $users = $this->database->listUsers(30);
        $options = [];
        $lines = [];
        $buttons = [[$this->uiConst(self::ADMIN_USERS_REFRESH)]];
        foreach (array_values($users) as $idx => $u) {
            $num = (string) ($idx + 1);
            $uid = (int) ($u['user_id'] ?? 0);
            if ($uid <= 0) {
                continue;
            }
            $status = (string) ($u['status'] ?? 'unsafe');
            $statusEmoji = $status === 'restricted'
                ? $this->catalog->get('admin.ui.open.users_list.status_restricted_emoji')
                : $this->catalog->get('admin.ui.open.users_list.status_active_emoji');
            $name = trim((string) ($u['full_name'] ?? $this->catalog->get('messages.generic.dash')));
            $balance = (int) ($u['balance'] ?? 0);
            $lines[] = $this->catalog->get('admin.ui.open.users_list.row', ['num' => $num, 'status_emoji' => $statusEmoji, 'name' => $name, 'uid' => $uid, 'balance' => $balance]);
            $options[$num] = $uid;
            $buttons[] = [$this->catalog->get('admin.ui.open.users_list.button', ['num' => $num, 'name' => $name])];
        }
        $buttons[] = [UiLabels::back($this->catalog), UiLabels::main($this->catalog)];
        $this->database->setUserState($userId, 'admin.users.list', ['options' => $options, 'stack' => ['admin.root']]);
        if ($notice !== null && $notice !== '') {
            $this->telegram->sendMessage($chatId, $notice);
        }
        $this->telegram->sendMessage(
            $chatId,
            $this->messageRenderer->render('admin.ui.open.users_list.overview', [
                'list' => $lines !== [] ? implode("\n", $lines) : $this->catalog->get('admin.ui.open.users_list.empty'),
            ], ['list']),
            $this->uiKeyboard->replyMenu($buttons)
        );
    }

    private function openAdminUserView(int $chatId, int $userId, int $targetUid, ?string $notice = null): void
    {
        $target = $this->database->getUser($targetUid);
        if ($target === null) {
            $this->openAdminUsersList($chatId, $userId, $this->uiText->warning($this->catalog->get('admin.ui.open.user_view.not_found')));
            return;
        }

        $status = (string) ($target['status'] ?? 'unsafe');
        $statusText = $status === 'restricted'
            ? $this->catalog->get('admin.ui.open.user_view.status_restricted')
            : $this->catalog->get('admin.ui.open.user_view.status_active');
        $isAgent = ((int) ($target['is_agent'] ?? 0)) === 1;
        $agentText = $isAgent
            ? $this->catalog->get('admin.ui.open.user_view.agent_yes')
            : $this->catalog->get('admin.ui.open.user_view.agent_no');
        if ($notice !== null && $notice !== '') {
            $this->telegram->sendMessage($chatId, $notice);
        }
        $this->database->setUserState($userId, 'admin.user.view', ['target_user_id' => $targetUid, 'stack' => ['admin.users.list', 'admin.root']]);
        $this->telegram->sendMessage(
            $chatId,
            $this->messageRenderer->render('admin.ui.open.user_view.overview', [
                'user_id' => $targetUid,
                'name' => (string) ($target['full_name'] ?? $this->catalog->get('messages.generic.dash')),
                'balance' => (int) ($target['balance'] ?? 0),
                'status_text' => $statusText,
                'agent_text' => $agentText,
            ]),
            $this->uiKeyboard->replyMenu([
                [$this->uiConst(self::ADMIN_USER_TOGGLE_STATUS), $this->uiConst(self::ADMIN_USER_TOGGLE_AGENT)],
                [$this->uiConst(self::ADMIN_USER_BALANCE_ADD), $this->uiConst(self::ADMIN_USER_BALANCE_SUB)],
                [UiLabels::back($this->catalog), UiLabels::main($this->catalog)],
            ])
        );
    }

    private function openAdminStockTypesView(int $chatId, int $userId, ?string $notice = null): void
    {
        $types = $this->database->listTypes();
        $options = [];
        $lines = [];
        $buttons = [[$this->uiConst(self::ADMIN_STOCK_REFRESH)]];
        foreach (array_values($types) as $idx => $type) {
            $num = (string) ($idx + 1);
            $typeId = (int) ($type['id'] ?? 0);
            if ($typeId <= 0) {
                continue;
            }
            $name = trim((string) ($type['name'] ?? $this->catalog->get('messages.generic.dash')));
            $lines[] = $this->catalog->get('admin.ui.open.stock.types.row', ['num' => $num, 'name' => $name, 'type_id' => $typeId]);
            $options[$num] = $typeId;
            $buttons[] = [$this->catalog->get('admin.ui.open.stock.types.button', ['num' => $num, 'name' => $name])];
        }
        $buttons[] = [UiLabels::back($this->catalog), UiLabels::main($this->catalog)];
        $this->database->setUserState($userId, 'admin.stock.view', ['level' => 'types', 'options' => $options, 'stack' => ['admin.root']]);
        if ($notice !== null && $notice !== '') {
            $this->telegram->sendMessage($chatId, $notice);
        }
        $this->telegram->sendMessage(
            $chatId,
            $this->messageRenderer->render('admin.ui.open.stock.types.overview', [
                'list' => $lines !== [] ? implode("\n", $lines) : $this->catalog->get('admin.ui.open.stock.types.empty'),
            ], ['list']),
            $this->uiKeyboard->replyMenu($buttons)
        );
    }

    private function openAdminStockPackagesView(int $chatId, int $userId, int $typeId, ?string $notice = null): void
    {
        if ($typeId <= 0) {
            $this->openAdminStockTypesView($chatId, $userId, $this->uiText->warning($this->catalog->get('admin.ui.open.stock.packages.invalid_type')));
            return;
        }
        $packages = $this->database->listPackagesByType($typeId);
        $options = [];
        $lines = [];
        $buttons = [[$this->uiConst(self::ADMIN_STOCK_REFRESH)]];
        foreach (array_values($packages) as $idx => $pkg) {
            $num = (string) ($idx + 1);
            $packageId = (int) ($pkg['id'] ?? 0);
            if ($packageId <= 0) {
                continue;
            }
            $available = $this->database->countAvailableConfigsForPackage($packageId);
            $name = trim((string) ($pkg['name'] ?? $this->catalog->get('admin.ui.open.stock.packages.default_name')));
            $lines[] = $this->catalog->get('admin.ui.open.stock.packages.row', ['num' => $num, 'name' => $name, 'package_id' => $packageId, 'available' => $available]);
            $options[$num] = $packageId;
            $buttons[] = [$this->catalog->get('admin.ui.open.stock.packages.button', ['num' => $num, 'name' => $name])];
        }
        $buttons[] = [UiLabels::back($this->catalog), UiLabels::main($this->catalog)];
        $this->database->setUserState($userId, 'admin.stock.view', ['level' => 'packages', 'type_id' => $typeId, 'options' => $options, 'stack' => ['admin.stock.view', 'admin.root']]);
        if ($notice !== null && $notice !== '') {
            $this->telegram->sendMessage($chatId, $notice);
        }
        $this->telegram->sendMessage(
            $chatId,
            $this->messageRenderer->render('admin.ui.open.stock.packages.overview', [
                'list' => $lines !== [] ? implode("\n", $lines) : $this->catalog->get('admin.ui.open.stock.packages.empty'),
            ], ['list']),
            $this->uiKeyboard->replyMenu($buttons)
        );
    }

    private function openAdminStockConfigsView(int $chatId, int $userId, int $typeId, int $packageId, string $query = '', ?string $notice = null): void
    {
        if ($typeId <= 0 || $packageId <= 0) {
            $this->openAdminStockTypesView($chatId, $userId, $this->uiText->warning($this->catalog->get('admin.ui.open.stock.configs.invalid_type_package')));
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
            $status = $expired
                ? $this->catalog->get('admin.ui.open.stock.common.status_symbol_expired')
                : ($soldTo > 0 ? $this->catalog->get('admin.ui.open.stock.common.status_symbol_sold') : $this->catalog->get('admin.ui.open.stock.common.status_symbol_ready'));
            $service = trim((string) ($cfg['service_name'] ?? $this->catalog->get('messages.generic.dash')));
            $lines[] = $this->catalog->get('admin.ui.open.stock.configs.row', ['num' => $num, 'status' => $status, 'service' => $service, 'config_id' => $configId]);
            $options[$num] = $configId;
        }
        $buttons = [
            [$this->uiConst(self::ADMIN_STOCK_REFRESH), $this->uiConst(self::ADMIN_STOCK_ADD_CONFIG)],
            [$this->uiConst(self::ADMIN_STOCK_SEARCH), $this->uiConst(self::ADMIN_STOCK_SEARCH_CLEAR)],
        ];
        foreach (array_keys($options) as $num) {
            $buttons[] = [$this->catalog->get('admin.ui.open.stock.configs.option_button', ['num' => $num])];
        }
        $buttons[] = [UiLabels::back($this->catalog), UiLabels::main($this->catalog)];
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
        $queryView = $query === '' ? $this->catalog->get('admin.ui.open.stock.configs.query_none') : '<code>' . htmlspecialchars($query) . '</code>';
        $this->telegram->sendMessage(
            $chatId,
            $this->messageRenderer->render('admin.ui.open.stock.configs.overview', [
                'package_id' => $packageId,
                'query_view' => $queryView,
                'list' => $lines !== [] ? implode("\n", $lines) : $this->catalog->get('admin.ui.open.stock.configs.empty'),
            ], ['query_view', 'list']),
            $this->uiKeyboard->replyMenu($buttons)
        );
    }

    private function openAdminStockConfigDetailView(int $chatId, int $userId, int $typeId, int $packageId, int $configId, string $query = '', ?string $notice = null): void
    {
        $cfg = $this->findConfigById($packageId, $configId);
        if ($cfg === null) {
            $this->openAdminStockConfigsView($chatId, $userId, $typeId, $packageId, $query, $this->uiText->warning($this->catalog->get('admin.ui.open.stock.config_detail.not_found')));
            return;
        }
        $soldTo = (int) ($cfg['sold_to'] ?? 0);
        $expired = ((int) ($cfg['is_expired'] ?? 0)) === 1;
        $status = $expired
            ? $this->catalog->get('admin.ui.open.stock.config_detail.status_expired')
            : ($soldTo > 0 ? $this->catalog->get('admin.ui.open.stock.config_detail.status_sold') : $this->catalog->get('admin.ui.open.stock.config_detail.status_ready'));
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
            $this->messageRenderer->render('admin.ui.open.stock.config_detail.overview', [
                'config_id' => $configId,
                'service_name' => (string) ($cfg['service_name'] ?? $this->catalog->get('messages.generic.dash')),
                'status' => $status,
            ]),
            $this->uiKeyboard->replyMenu([
                [$this->uiConst(self::ADMIN_STOCK_EXPIRE_TOGGLE), $this->uiConst(self::ADMIN_STOCK_DELETE_CONFIG)],
                [UiLabels::back($this->catalog), UiLabels::main($this->catalog)],
            ])
        );
    }

    private function findConfigById(int $ownerId, int $configId, bool $serviceBased = false): ?array
    {
        $rows = $serviceBased
            ? $this->database->listConfigsByService($ownerId, null, 100, 0)
            : $this->database->listConfigsByPackage($ownerId, 100, 0);
        foreach ($rows as $cfg) {
            if ((int) ($cfg['id'] ?? 0) === $configId) {
                return $cfg;
            }
        }

        return null;
    }

    private function handleAdminPaymentsRequestsState(int $chatId, int $userId, string $text, array $state): void
    {
        if ($this->isMainMenuInput($text)) {
            $this->openAdminRoot($chatId, $userId);
            return;
        }

        $stateName = (string) ($state['state_name'] ?? '');
        $payload = is_array($state['payload'] ?? null) ? $state['payload'] : [];
        $paymentsRefreshLabel = $this->catalog->get('admin.payments_requests.actions.payments_refresh');
        $paymentVerifyChainLabel = $this->catalog->get('admin.payments_requests.actions.payment_verify_chain');
        $paymentApproveLabel = $this->catalog->get('admin.payments_requests.actions.payment_approve');
        $paymentRejectLabel = $this->catalog->get('admin.payments_requests.actions.payment_reject');
        $requestsFreeLabel = $this->catalog->get('admin.payments_requests.actions.requests_free');
        $requestsAgencyLabel = $this->catalog->get('admin.payments_requests.actions.requests_agency');
        $requestsPendingLabel = $this->catalog->get('admin.payments_requests.actions.requests_pending');
        $requestsApprovedLabel = $this->catalog->get('admin.payments_requests.actions.requests_approved');
        $requestsRejectedLabel = $this->catalog->get('admin.payments_requests.actions.requests_rejected');
        $requestApproveLabel = $this->catalog->get('admin.payments_requests.actions.request_approve');
        $requestRejectLabel = $this->catalog->get('admin.payments_requests.actions.request_reject');

        if ($stateName === 'admin.payments.list') {
            if ($text === UiLabels::back($this->catalog)) {
                $this->openAdminRoot($chatId, $userId);
                return;
            }
            if ($text === $paymentsRefreshLabel || $text === $this->uiConst(self::ADMIN_PAYMENTS_REFRESH)) {
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
            $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('admin.payments_requests.errors.invalid_payment_option')));
            return;
        }

        if ($stateName === 'admin.payment.view') {
            $paymentId = (int) ($payload['payment_id'] ?? 0);
            if ($paymentId <= 0) {
                $this->openAdminPaymentsList($chatId, $userId, $this->uiText->warning($this->catalog->get('admin.payments_requests.errors.invalid_payment_id')));
                return;
            }
            if ($text === UiLabels::back($this->catalog)) {
                $this->openAdminPaymentsList($chatId, $userId);
                return;
            }
            if ($text === $paymentVerifyChainLabel || $text === $this->uiConst(self::ADMIN_PAYMENT_VERIFY_CHAIN)) {
                $this->database->setUserState($userId, 'admin.payment.review', [
                    'payment_id' => $paymentId,
                    'action' => 'verify',
                    'stack' => ['admin.payment.view', 'admin.payments.list', 'admin.root'],
                ]);
                $this->processAdminPaymentReview($chatId, $userId, $paymentId, 'verify');
                return;
            }
            if (
                $text === $paymentApproveLabel
                || $text === $this->uiConst(self::ADMIN_PAYMENT_APPROVE)
                || $text === $paymentRejectLabel
                || $text === $this->uiConst(self::ADMIN_PAYMENT_REJECT)
            ) {
                $action = ($text === $paymentApproveLabel || $text === $this->uiConst(self::ADMIN_PAYMENT_APPROVE)) ? 'approve' : 'reject';
                $this->database->setUserState($userId, 'admin.payment.review', [
                    'payment_id' => $paymentId,
                    'action' => $action,
                    'stack' => ['admin.payment.view', 'admin.payments.list', 'admin.root'],
                ]);
                $this->processAdminPaymentReview($chatId, $userId, $paymentId, $action);
                return;
            }
            $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('admin.payments_requests.errors.invalid_payment_review_action')));
            return;
        }

        if ($stateName === 'admin.payment.review') {
            $paymentId = (int) ($payload['payment_id'] ?? 0);
            if ($paymentId <= 0) {
                $this->openAdminPaymentsList($chatId, $userId, $this->uiText->warning($this->catalog->get('admin.payments_requests.errors.invalid_payment')));
                return;
            }
            if ($text === UiLabels::back($this->catalog)) {
                $this->openAdminPaymentView($chatId, $userId, $paymentId);
                return;
            }
            $this->openAdminPaymentView($chatId, $userId, $paymentId);
            return;
        }

        if ($stateName === 'admin.requests.list') {
            if ($text === UiLabels::back($this->catalog)) {
                $this->openAdminRoot($chatId, $userId);
                return;
            }

            $kind = (string) ($payload['kind'] ?? '');
            $status = (string) ($payload['status'] ?? 'pending');
            if ($kind === '') {
                if ($text === $requestsFreeLabel || $text === $this->uiConst(self::ADMIN_REQUESTS_FREE)) {
                    $this->openAdminRequestsList($chatId, $userId, 'free', 'pending');
                    return;
                }
                if ($text === $requestsAgencyLabel || $text === $this->uiConst(self::ADMIN_REQUESTS_AGENCY)) {
                    $this->openAdminRequestsList($chatId, $userId, 'agency', 'pending');
                    return;
                }
                $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('admin.payments_requests.errors.invalid_request_kind_option')));
                return;
            }

            if ($text === $requestsPendingLabel || $text === $this->uiConst(self::ADMIN_REQUESTS_PENDING)) {
                $this->openAdminRequestsList($chatId, $userId, $kind, 'pending');
                return;
            }
            if ($text === $requestsApprovedLabel || $text === $this->uiConst(self::ADMIN_REQUESTS_APPROVED)) {
                $this->openAdminRequestsList($chatId, $userId, $kind, 'approved');
                return;
            }
            if ($text === $requestsRejectedLabel || $text === $this->uiConst(self::ADMIN_REQUESTS_REJECTED)) {
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
            $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('admin.payments_requests.errors.invalid_request_option')));
            return;
        }

        if ($stateName === 'admin.request.view') {
            $kind = (string) ($payload['kind'] ?? '');
            $requestId = (int) ($payload['request_id'] ?? 0);
            $status = (string) ($payload['status'] ?? 'pending');
            if ($kind === '' || $requestId <= 0) {
                $this->openAdminRequestsList($chatId, $userId, '', 'pending', $this->uiText->warning($this->catalog->get('admin.payments_requests.errors.invalid_request_info')));
                return;
            }
            if ($text === UiLabels::back($this->catalog)) {
                $this->openAdminRequestsList($chatId, $userId, $kind, $status);
                return;
            }
            if (
                $text === $requestApproveLabel
                || $text === $this->uiConst(self::ADMIN_REQUEST_APPROVE)
                || $text === $requestRejectLabel
                || $text === $this->uiConst(self::ADMIN_REQUEST_REJECT)
            ) {
                $action = ($text === $requestApproveLabel || $text === $this->uiConst(self::ADMIN_REQUEST_APPROVE)) ? 'approve' : 'reject';
                $this->database->setUserState($userId, 'admin.request.review', [
                    'kind' => $kind,
                    'request_id' => $requestId,
                    'status' => $status,
                    'action' => $action,
                    'stack' => ['admin.request.view', 'admin.requests.list', 'admin.root'],
                ]);
                $actionText = $this->catalog->get($action === 'approve' ? 'admin.payments_requests.labels.action_approve' : 'admin.payments_requests.labels.action_reject');
                $this->telegram->sendMessage(
                    $chatId,
                    $this->messageRenderer->render('admin.payments_requests.prompts.request_review_note_overview', [
                        'action_text' => $actionText,
                        'request_id' => $requestId,
                    ]),
                    $this->uiKeyboard->replyMenu([[UiLabels::back($this->catalog), UiLabels::main($this->catalog)]])
                );
                return;
            }
            $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('admin.payments_requests.errors.invalid_request_review_action')));
            return;
        }

        if ($stateName === 'admin.request.review') {
            $kind = (string) ($payload['kind'] ?? '');
            $requestId = (int) ($payload['request_id'] ?? 0);
            $status = (string) ($payload['status'] ?? 'pending');
            $action = (string) ($payload['action'] ?? '');
            if ($kind === '' || $requestId <= 0 || ($action !== 'approve' && $action !== 'reject')) {
                $this->openAdminRequestsList($chatId, $userId, '', 'pending', $this->uiText->warning($this->catalog->get('admin.payments_requests.errors.invalid_request_review_info')));
                return;
            }
            if ($text === UiLabels::back($this->catalog)) {
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
                    ? $this->catalog->get('admin.payments_requests.errors.request_already_reviewed')
                    : $this->catalog->get('admin.payments_requests.errors.request_review_failed');
                $this->telegram->sendMessage($chatId, $this->uiText->error($msg));
                $this->openAdminRequestsList($chatId, $userId, $kind, 'pending');
                return;
            }

            $label = $kind === 'free'
                ? $this->catalog->get('admin.payments_requests.labels.request_free')
                : $this->catalog->get('admin.payments_requests.labels.request_agency');
            $statusText = $approve
                ? $this->catalog->get('admin.payments_requests.labels.status_approved')
                : $this->catalog->get('admin.payments_requests.labels.status_rejected');
            $this->telegram->sendMessage($chatId, $this->uiText->success($this->catalog->get('admin.payments_requests.success.request_reviewed', [
                'request_label' => $label,
                'request_id' => $requestId,
                'status_text' => $statusText,
            ])));
            $userNotice = $approve
                ? ($kind === 'free'
                    ? $this->catalog->get('admin.payments_requests.user_notice.free_approved')
                    : $this->catalog->get('admin.payments_requests.user_notice.agency_approved'))
                : ($kind === 'free'
                    ? $this->catalog->get('admin.payments_requests.user_notice.free_rejected')
                    : $this->catalog->get('admin.payments_requests.user_notice.agency_rejected'));
            $noteLine = $adminNote !== null && $adminNote !== ''
                ? $this->catalog->get('admin.payments_requests.user_notice.admin_note', [
                    'note' => htmlspecialchars($adminNote),
                ])
                : '';
            $userNotice = $this->messageRenderer->render('admin.common.notice_with_optional_note', [
                'notice' => $userNotice,
                'note_line' => $noteLine,
            ], ['notice', 'note_line']);
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
        $buttons = [[$this->uiConst(self::ADMIN_PAYMENTS_REFRESH)]];
        foreach (array_values($items) as $idx => $item) {
            $num = (string) ($idx + 1);
            $paymentId = (int) ($item['id'] ?? 0);
            if ($paymentId <= 0) {
                continue;
            }
            $kind = (string) ($item['kind'] ?? $this->catalog->get('messages.generic.dash'));
            $uid = (int) ($item['user_id'] ?? 0);
            $amount = (int) ($item['amount'] ?? 0);
            $method = (string) ($item['payment_method'] ?? $this->catalog->get('messages.generic.dash'));
            $lines[] = $this->catalog->get('admin.ui.open.payments.list.row', ['num' => $num, 'payment_id' => $paymentId, 'kind' => $kind, 'uid' => $uid, 'amount' => $amount, 'method' => $method]);
            $options[$num] = $paymentId;
            $buttons[] = [$this->catalog->get('admin.ui.open.payments.list.button', ['num' => $num, 'payment_id' => $paymentId])];
        }
        $buttons[] = [UiLabels::back($this->catalog), UiLabels::main($this->catalog)];
        $this->database->setUserState($userId, 'admin.payments.list', ['options' => $options, 'stack' => ['admin.root']]);
        if ($notice !== null && $notice !== '') {
            $this->telegram->sendMessage($chatId, $notice);
        }
        $this->telegram->sendMessage(
            $chatId,
            $this->messageRenderer->render('admin.ui.open.payments.list.overview', [
                'list' => $lines !== [] ? implode("\n", $lines) : $this->catalog->get('admin.ui.open.payments.list.empty'),
            ], ['list']),
            $this->uiKeyboard->replyMenu($buttons)
        );
    }

    private function openAdminPaymentView(int $chatId, int $userId, int $paymentId, ?string $notice = null): void
    {
        $payment = $this->database->getPaymentById($paymentId);
        if ($payment === null) {
            $this->openAdminPaymentsList($chatId, $userId, $this->uiText->warning($this->catalog->get('admin.ui.open.payments.view.not_found')));
            return;
        }
        if ($notice !== null && $notice !== '') {
            $this->telegram->sendMessage($chatId, $notice);
        }

        $status = (string) ($payment['status'] ?? $this->catalog->get('messages.generic.dash'));
        $method = (string) ($payment['payment_method'] ?? $this->catalog->get('messages.generic.dash'));
        $buttons = [];
        if (str_starts_with($method, 'crypto:')) {
            $buttons[] = [$this->uiConst(self::ADMIN_PAYMENT_VERIFY_CHAIN)];
        }
        if ($status === 'waiting_admin') {
            $buttons[] = [$this->uiConst(self::ADMIN_PAYMENT_APPROVE), $this->uiConst(self::ADMIN_PAYMENT_REJECT)];
        }
        $buttons[] = [UiLabels::back($this->catalog), UiLabels::main($this->catalog)];

        $this->database->setUserState($userId, 'admin.payment.view', ['payment_id' => $paymentId, 'stack' => ['admin.payments.list', 'admin.root']]);
        $this->telegram->sendMessage(
            $chatId,
            $this->messageRenderer->render('admin.ui.open.payments.view.overview', [
                'payment_id' => $paymentId,
                'user_id' => (int) ($payment['user_id'] ?? 0),
                'amount' => (int) ($payment['amount'] ?? 0),
                'method' => $method,
                'status' => $status,
            ]),
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
                    'cooldown' => $this->catalog->get('admin.ui.audit.payment_review.cooldown'),
                    'max_attempts' => $this->catalog->get('admin.ui.audit.payment_review.max_attempts'),
                    default => $this->catalog->get('admin.ui.audit.payment_review.unavailable'),
                };
                $this->openAdminPaymentView($chatId, $userId, $paymentId, $this->uiText->warning($message));
                return;
            }

            $payment = $this->database->getPaymentById($paymentId);
            if ($payment === null) {
                $this->openAdminPaymentsList($chatId, $userId, $this->uiText->warning($this->catalog->get('admin.ui.audit.payment_review.payment_not_found')));
                return;
            }
            $pm = (string) ($payment['payment_method'] ?? '');
            if (!str_starts_with($pm, 'crypto:')) {
                $this->openAdminPaymentView($chatId, $userId, $paymentId, $this->uiText->warning($this->catalog->get('admin.ui.audit.payment_review.not_crypto')));
                return;
            }
            $coin = trim(substr($pm, strlen('crypto:')));
            $txHash = trim((string) ($payment['tx_hash'] ?? ''));
            $claimedCoin = isset($payment['crypto_amount_claimed']) ? (float) $payment['crypto_amount_claimed'] : null;
            if ($txHash === '') {
                $this->openAdminPaymentView($chatId, $userId, $paymentId, $this->uiText->warning($this->catalog->get('admin.ui.audit.payment_review.tx_hash_missing')));
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
                    $this->openAdminPaymentView($chatId, $userId, $paymentId, $this->uiText->success($this->catalog->get('admin.ui.audit.payment_review.crypto_confirmed')));
                    return;
                }
            }
            $this->openAdminPaymentView($chatId, $userId, $paymentId, $this->uiText->warning($this->catalog->get('admin.ui.audit.payment_review.tx_not_confirmed_or_invalid')));
            return;
        }

        $approve = $action === 'approve';
        $result = $this->database->applyAdminPaymentDecision($paymentId, $approve);
        if (!($result['ok'] ?? false)) {
            $this->openAdminPaymentView($chatId, $userId, $paymentId, $this->uiText->warning($this->catalog->get('admin.ui.audit.payment_review.request_not_processable')));
            return;
        }
        $this->notifyPaymentDecision((int) ($result['user_id'] ?? 0), (string) ($result['kind'] ?? ''), (int) ($result['amount'] ?? 0), $approve);
        $statusText = $approve ? $this->catalog->get('admin.legacy.labels.status_approved') : $this->catalog->get('admin.legacy.labels.status_rejected');
        $this->openAdminPaymentsList($chatId, $userId, $this->uiText->success($this->catalog->get('admin.ui.audit.payment_review.request_status', ['payment_id' => $paymentId, 'status_text' => $statusText])));
    }

    private function notifyPaymentDecision(int $targetUserId, string $kind, int $amount, bool $approve): void
    {
        if ($targetUserId <= 0) {
            return;
        }
        if ($kind === 'wallet_charge') {
            $userNotice = $approve
                ? $this->catalog->get('admin.ui.audit.user_notice.wallet_approved', ['amount' => $amount])
                : $this->catalog->get('admin.ui.audit.user_notice.wallet_rejected');
        } elseif ($kind === 'renewal') {
            $userNotice = $approve
                ? $this->catalog->get('admin.ui.audit.user_notice.renewal_approved')
                : $this->catalog->get('admin.ui.audit.user_notice.renewal_rejected');
        } else {
            $userNotice = $approve
                ? $this->catalog->get('admin.ui.audit.user_notice.order_approved')
                : $this->catalog->get('admin.ui.audit.user_notice.order_rejected');
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
                $this->messageRenderer->render('admin.ui.open.requests.root.overview'),
                $this->uiKeyboard->replyMenu([
                    [$this->uiConst(self::ADMIN_REQUESTS_FREE)],
                    [$this->uiConst(self::ADMIN_REQUESTS_AGENCY)],
                    [UiLabels::back($this->catalog), UiLabels::main($this->catalog)],
                ])
            );
            return;
        }

        $items = $kind === 'free'
            ? $this->database->listFreeTestRequestsByStatus($status, 20, 0)
            : $this->database->listAgencyRequestsByStatus($status, 20, 0);
        $options = [];
        $lines = [];
        $buttons = [[
            $this->catalog->get('admin.ui.open.requests.list.filter_pending'),
            $this->catalog->get('admin.ui.open.requests.list.filter_approved'),
            $this->catalog->get('admin.ui.open.requests.list.filter_rejected'),
        ]];
        foreach (array_values($items) as $idx => $item) {
            $num = (string) ($idx + 1);
            $requestId = (int) ($item['id'] ?? 0);
            if ($requestId <= 0) {
                continue;
            }
            $uid = (int) ($item['user_id'] ?? 0);
            $created = (string) ($item['created_at'] ?? $this->catalog->get('messages.generic.dash'));
            $lines[] = $this->catalog->get('admin.ui.open.requests.list.row', ['num' => $num, 'request_id' => $requestId, 'uid' => $uid, 'created_at' => $created]);
            $options[$num] = $requestId;
            $buttons[] = [$this->catalog->get('admin.ui.open.requests.list.button', ['num' => $num, 'request_id' => $requestId])];
        }
        $buttons[] = [UiLabels::back($this->catalog), UiLabels::main($this->catalog)];
        $this->database->setUserState($userId, 'admin.requests.list', [
            'kind' => $kind,
            'status' => $status,
            'options' => $options,
            'stack' => ['admin.root'],
        ]);
        $kindTitle = $kind === 'free'
            ? $this->catalog->get('admin.ui.open.requests.list.kind_free')
            : $this->catalog->get('admin.ui.open.requests.list.kind_agency');
        $this->telegram->sendMessage(
            $chatId,
            $this->messageRenderer->render('admin.ui.open.requests.list.overview', [
                'kind_title' => $kindTitle,
                'status' => $status,
                'list' => $lines !== [] ? implode("\n", $lines) : $this->catalog->get('admin.ui.open.requests.list.empty'),
            ], ['list']),
            $this->uiKeyboard->replyMenu($buttons)
        );
    }

    private function openAdminRequestView(int $chatId, int $userId, string $kind, int $requestId, string $backStatus = 'pending', ?string $notice = null): void
    {
        $request = $kind === 'free'
            ? $this->database->getFreeTestRequestById($requestId)
            : $this->database->getAgencyRequestById($requestId);
        if ($request === null) {
            $this->openAdminRequestsList($chatId, $userId, $kind, $backStatus, $this->uiText->warning($this->catalog->get('admin.ui.open.requests.view.not_found')));
            return;
        }
        if ($notice !== null && $notice !== '') {
            $this->telegram->sendMessage($chatId, $notice);
        }
        $status = (string) ($request['status'] ?? 'pending');
        $statusText = $status === 'approved'
            ? $this->catalog->get('admin.ui.open.requests.view.status_approved')
            : ($status === 'rejected' ? $this->catalog->get('admin.ui.open.requests.view.status_rejected') : $this->catalog->get('admin.ui.open.requests.view.status_pending'));
        $kindTitle = $kind === 'free'
            ? $this->catalog->get('admin.ui.open.requests.view.kind_free')
            : $this->catalog->get('admin.ui.open.requests.view.kind_agency');
        $buttons = [];
        if ($status === 'pending') {
            $buttons[] = [$this->uiConst(self::ADMIN_REQUEST_APPROVE), $this->uiConst(self::ADMIN_REQUEST_REJECT)];
        }
        $buttons[] = [UiLabels::back($this->catalog), UiLabels::main($this->catalog)];
        $this->database->setUserState($userId, 'admin.request.view', [
            'kind' => $kind,
            'request_id' => $requestId,
            'status' => $backStatus,
            'stack' => ['admin.requests.list', 'admin.root'],
        ]);
        $this->telegram->sendMessage(
            $chatId,
            $this->messageRenderer->render('admin.ui.open.requests.view.overview', [
                'kind_title' => $kindTitle,
                'request_id' => $requestId,
                'user_id' => (int) ($request['user_id'] ?? 0),
                'status_text' => $statusText,
                'created_at' => (string) ($request['created_at'] ?? $this->catalog->get('messages.generic.dash')),
                'note' => (string) ($request['note'] ?? ''),
            ]),
            $this->uiKeyboard->replyMenu($buttons)
        );
    }

    private function handleAdminSettingsAdminsPinsState(int $chatId, int $userId, string $text, array $state): void
    {
        if ($this->isMainMenuInput($text)) {
            $this->openAdminRoot($chatId, $userId);
            return;
        }
        $stateName = (string) ($state['state_name'] ?? '');
        $payload = is_array($state['payload'] ?? null) ? $state['payload'] : [];
        $settingsRefreshLabel = $this->catalog->get('admin.settings_admins_pins.actions.settings_refresh');
        $settingsToggleBotLabel = $this->catalog->get('admin.settings_admins_pins.actions.settings_toggle_bot');
        $settingsToggleFreeTestLabel = $this->catalog->get('admin.settings_admins_pins.actions.settings_toggle_free_test');
        $settingsToggleAgencyLabel = $this->catalog->get('admin.settings_admins_pins.actions.settings_toggle_agency');
        $settingsToggleGwCardLabel = $this->catalog->get('admin.settings_admins_pins.actions.settings_toggle_gw_card');
        $settingsToggleGwCryptoLabel = $this->catalog->get('admin.settings_admins_pins.actions.settings_toggle_gw_crypto');
        $settingsToggleGwTetraLabel = $this->catalog->get('admin.settings_admins_pins.actions.settings_toggle_gw_tetra');
        $settingsSetChannelLabel = $this->catalog->get('admin.settings_admins_pins.actions.settings_set_channel');
        $settingsSetDeliveryModeLabel = $this->catalog->get('admin.settings_admins_pins.actions.settings_set_delivery_mode');
        $settingsEditLabel = $this->catalog->get('admin.settings_admins_pins.actions.settings_edit');
        $adminsAddLabel = $this->catalog->get('admin.settings_admins_pins.actions.admins_add');
        $adminDeleteLabel = $this->catalog->get('admin.settings_admins_pins.actions.admin_delete');
        $pinsAddLabel = $this->catalog->get('admin.settings_admins_pins.actions.pins_add');
        $pinEditLabel = $this->catalog->get('admin.settings_admins_pins.actions.pin_edit');
        $pinDeleteLabel = $this->catalog->get('admin.settings_admins_pins.actions.pin_delete');
        $pinSendAllLabel = $this->catalog->get('admin.settings_admins_pins.actions.pin_send_all');
        $confirmDeleteWord = $this->catalog->get('admin.settings_admins_pins.keywords.delete_confirm');
        $confirmSendWord = $this->catalog->get('admin.settings_admins_pins.keywords.send_confirm');

        if ($stateName === 'admin.settings.view') {
            if ($text === UiLabels::back($this->catalog)) {
                $this->openAdminRoot($chatId, $userId);
                return;
            }
            $toggleMap = [
                $this->uiConst(self::ADMIN_SETTINGS_TOGGLE_FREE_TEST) => 'free_test_enabled',
                $this->uiConst(self::ADMIN_SETTINGS_TOGGLE_AGENCY) => 'agency_request_enabled',
                $this->uiConst(self::ADMIN_SETTINGS_TOGGLE_GW_CARD) => 'gw_card_enabled',
                $this->uiConst(self::ADMIN_SETTINGS_TOGGLE_GW_CRYPTO) => 'gw_crypto_enabled',
                $this->uiConst(self::ADMIN_SETTINGS_TOGGLE_GW_TETRA) => 'gw_tetrapay_enabled',
                $settingsToggleFreeTestLabel => 'free_test_enabled',
                $settingsToggleAgencyLabel => 'agency_request_enabled',
                $settingsToggleGwCardLabel => 'gw_card_enabled',
                $settingsToggleGwCryptoLabel => 'gw_crypto_enabled',
                $settingsToggleGwTetraLabel => 'gw_tetrapay_enabled',
            ];
            if ($text === $settingsRefreshLabel || $text === $this->uiConst(self::ADMIN_SETTINGS_REFRESH)) {
                $this->openAdminSettingsView($chatId, $userId);
                return;
            }
            if ($text === $settingsToggleBotLabel || $text === $this->uiConst(self::ADMIN_SETTINGS_TOGGLE_BOT)) {
                $cur = $this->settings->get('bot_status', 'on');
                $next = $cur === 'on' ? 'update' : ($cur === 'update' ? 'off' : 'on');
                $this->settings->set('bot_status', $next);
                $this->openAdminSettingsView($chatId, $userId, $this->uiText->success($this->catalog->get('admin.settings_admins_pins.success.bot_status_updated')));
                return;
            }
            if (isset($toggleMap[$text])) {
                $key = $toggleMap[$text];
                $current = $this->settings->get($key, '0');
                $this->settings->set($key, $current === '1' ? '0' : '1');
                $this->openAdminSettingsView($chatId, $userId, $this->uiText->success($this->catalog->get('admin.settings_admins_pins.success.setting_updated')));
                return;
            }
            if ($text === $settingsSetChannelLabel || $text === $this->uiConst(self::ADMIN_SETTINGS_SET_CHANNEL)) {
                $this->database->setUserState($userId, 'admin.settings.edit', ['mode' => 'channel', 'stack' => ['admin.settings.view', 'admin.root']]);
                $this->telegram->sendMessage(
                    $chatId,
                    $this->messageRenderer->render('admin.settings_admins_pins.prompts.set_channel_overview'),
                    $this->uiKeyboard->replyMenu([[UiLabels::back($this->catalog), UiLabels::main($this->catalog)]])
                );
                return;
            }
            if ($text === $settingsSetDeliveryModeLabel) {
                $this->database->setUserState($userId, 'admin.settings.edit', ['mode' => 'delivery_mode', 'stack' => ['admin.settings.view', 'admin.root']]);
                $this->telegram->sendMessage(
                    $chatId,
                    $this->uiText->info($this->catalog->get('admin.settings_admins_pins.prompts.delivery_mode_input')),
                    $this->uiKeyboard->replyMenu([[UiLabels::back($this->catalog), UiLabels::main($this->catalog)]])
                );
                return;
            }
            if ($text === $settingsEditLabel || $text === $this->uiConst(self::ADMIN_SETTINGS_EDIT)) {
                $this->database->setUserState($userId, 'admin.settings.edit', ['mode' => 'kv', 'stack' => ['admin.settings.view', 'admin.root']]);
                $this->telegram->sendMessage(
                    $chatId,
                    $this->messageRenderer->render('admin.settings_admins_pins.prompts.edit_setting_overview'),
                    $this->uiKeyboard->replyMenu([[UiLabels::back($this->catalog), UiLabels::main($this->catalog)]])
                );
                return;
            }
        }

        if ($stateName === 'admin.settings.edit') {
            $mode = (string) ($payload['mode'] ?? 'kv');
            if ($text === UiLabels::back($this->catalog)) {
                $this->openAdminSettingsView($chatId, $userId);
                return;
            }
            if ($text === '' || str_starts_with($text, '/')) {
                $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('admin.settings_admins_pins.errors.invalid_input')));
                return;
            }
            if ($mode === 'channel') {
                $value = trim($text);
                if ($value === '-' || $value === '—') {
                    $value = '';
                }
                $this->settings->set('channel_id', $value);
                $this->openAdminSettingsView($chatId, $userId, $this->uiText->success($this->catalog->get('admin.settings_admins_pins.success.lock_channel_updated')));
                return;
            }
            if ($mode === 'delivery_mode') {
                $value = trim($text);
                if (!in_array($value, ['stock_only', 'panel_only'], true)) {
                    $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('admin.settings_admins_pins.errors.invalid_delivery_mode')));
                    return;
                }
                $this->settings->set('delivery_mode', $value);
                $this->openAdminSettingsView($chatId, $userId, $this->uiText->success($this->catalog->get('admin.settings_admins_pins.success.delivery_mode_saved')));
                return;
            }
            $parts = array_map('trim', explode('|', $text, 2));
            $key = (string) ($parts[0] ?? '');
            $value = (string) ($parts[1] ?? '');
            if ($key === '' || count($parts) < 2) {
                $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('admin.settings_admins_pins.errors.invalid_kv_format')));
                return;
            }
            $this->settings->set($key, $value);
            $this->openAdminSettingsView($chatId, $userId, $this->uiText->success($this->catalog->get('admin.settings_admins_pins.success.setting_saved')));
            return;
        }

        if ($stateName === 'admin.admins.list') {
            if ($text === UiLabels::back($this->catalog)) {
                $this->openAdminRoot($chatId, $userId);
                return;
            }
            if ($text === $adminsAddLabel || $text === $this->uiConst(self::ADMIN_ADMINS_ADD)) {
                $this->database->setUserState($userId, 'admin.admin.create', ['stack' => ['admin.admins.list', 'admin.root']]);
                $this->telegram->sendMessage($chatId, $this->messageRenderer->render('admin.settings_admins_pins.prompts.add_admin_overview'), $this->uiKeyboard->replyMenu([[UiLabels::back($this->catalog), UiLabels::main($this->catalog)]]));
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
            if ($text === UiLabels::back($this->catalog)) {
                $this->openAdminAdminsList($chatId, $userId);
                return;
            }
            $targetUid = (int) preg_replace('/\D+/', '', $text);
            if ($targetUid <= 0) {
                $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('admin.settings_admins_pins.errors.invalid_admin_id')));
                return;
            }
            $this->database->upsertAdminUser($targetUid, $userId, [
                'types' => true, 'stock' => true, 'users' => true, 'settings' => true, 'payments' => true, 'requests' => true, 'broadcast' => false, 'agents' => false, 'panels' => false,
            ]);
            $this->openAdminAdminView($chatId, $userId, $targetUid, $this->uiText->success($this->catalog->get('admin.settings_admins_pins.success.admin_created')));
            return;
        }

        if ($stateName === 'admin.admin.view') {
            $targetUid = (int) ($payload['target_user_id'] ?? 0);
            if ($targetUid <= 0) {
                $this->openAdminAdminsList($chatId, $userId, $this->uiText->warning($this->catalog->get('admin.settings_admins_pins.errors.invalid_admin')));
                return;
            }
            if ($text === UiLabels::back($this->catalog)) {
                $this->openAdminAdminsList($chatId, $userId);
                return;
            }
            if ($text === $adminDeleteLabel || $text === $this->uiConst(self::ADMIN_ADMIN_DELETE)) {
                $this->database->setUserState($userId, 'admin.admin.delete', ['target_user_id' => $targetUid, 'stack' => ['admin.admin.view', 'admin.admins.list', 'admin.root']]);
                $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('admin.settings_admins_pins.prompts.admin_delete_confirm', ['target_uid' => $targetUid, 'confirm_word' => $confirmDeleteWord])), $this->uiKeyboard->replyMenu([[UiLabels::back($this->catalog), UiLabels::main($this->catalog)]]));
                return;
            }
            $permMap = is_array($payload['perm_labels'] ?? null) ? $payload['perm_labels'] : [];
            $permKey = $permMap[$text] ?? '';
            if ($permKey !== '') {
                $perms = $this->database->getAdminPermissions($targetUid);
                $perms[$permKey] = !((bool) ($perms[$permKey] ?? false));
                $this->database->upsertAdminUser($targetUid, $userId, $perms);
                $this->openAdminAdminView($chatId, $userId, $targetUid, $this->uiText->success($this->catalog->get('admin.settings_admins_pins.success.permission_updated')));
                return;
            }
        }

        if ($stateName === 'admin.admin.delete') {
            $targetUid = (int) ($payload['target_user_id'] ?? 0);
            if ($text === UiLabels::back($this->catalog)) {
                $this->openAdminAdminView($chatId, $userId, $targetUid);
                return;
            }
            if ($targetUid > 0 && trim($text) === $confirmDeleteWord && !in_array($targetUid, Config::adminIds(), true)) {
                $this->database->removeAdminUser($targetUid);
                $this->openAdminAdminsList($chatId, $userId, $this->uiText->success($this->catalog->get('admin.settings_admins_pins.success.admin_deleted')));
                return;
            }
            $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('admin.settings_admins_pins.errors.admin_delete_confirm_required', ['confirm_word' => $confirmDeleteWord])));
            return;
        }

        if ($stateName === 'admin.pins.list') {
            if ($text === UiLabels::back($this->catalog)) {
                $this->openAdminRoot($chatId, $userId);
                return;
            }
            if ($text === $pinsAddLabel || $text === $this->uiConst(self::ADMIN_PINS_ADD)) {
                $this->database->setUserState($userId, 'admin.pin.create', ['stack' => ['admin.pins.list', 'admin.root']]);
                $this->telegram->sendMessage($chatId, $this->uiText->info($this->catalog->get('admin.settings_admins_pins.prompts.pin_text_send')), $this->uiKeyboard->replyMenu([[UiLabels::back($this->catalog), UiLabels::main($this->catalog)]]));
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
            if ($text === UiLabels::back($this->catalog)) {
                $this->openAdminPinsList($chatId, $userId);
                return;
            }
            if (trim($text) === '' || str_starts_with($text, '/')) {
                $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('admin.settings_admins_pins.errors.pin_text_empty')));
                return;
            }
            $pinId = $this->database->addPinnedMessage($text);
            $this->openAdminPinView($chatId, $userId, $pinId, $this->uiText->success($this->catalog->get('admin.settings_admins_pins.success.pin_created')));
            return;
        }

        if ($stateName === 'admin.pin.view') {
            $pinId = (int) ($payload['pin_id'] ?? 0);
            if ($pinId <= 0) {
                $this->openAdminPinsList($chatId, $userId, $this->uiText->warning($this->catalog->get('admin.settings_admins_pins.errors.invalid_pin')));
                return;
            }
            if ($text === UiLabels::back($this->catalog)) {
                $this->openAdminPinsList($chatId, $userId);
                return;
            }
            if ($text === $pinEditLabel || $text === $this->uiConst(self::ADMIN_PIN_EDIT)) {
                $this->database->setUserState($userId, 'admin.pin.edit', ['pin_id' => $pinId, 'stack' => ['admin.pin.view', 'admin.pins.list', 'admin.root']]);
                $this->telegram->sendMessage($chatId, $this->uiText->info($this->catalog->get('admin.settings_admins_pins.prompts.pin_new_text_send')), $this->uiKeyboard->replyMenu([[UiLabels::back($this->catalog), UiLabels::main($this->catalog)]]));
                return;
            }
            if ($text === $pinDeleteLabel || $text === $this->uiConst(self::ADMIN_PIN_DELETE)) {
                $this->database->setUserState($userId, 'admin.pin.delete', ['pin_id' => $pinId, 'stack' => ['admin.pin.view', 'admin.pins.list', 'admin.root']]);
                $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('admin.settings_admins_pins.prompts.pin_delete_confirm', ['pin_id' => $pinId, 'confirm_word' => $confirmDeleteWord])), $this->uiKeyboard->replyMenu([[UiLabels::back($this->catalog), UiLabels::main($this->catalog)]]));
                return;
            }
            if ($text === $pinSendAllLabel || $text === $this->uiConst(self::ADMIN_PIN_SEND_ALL)) {
                $this->database->setUserState($userId, 'admin.pin.send', ['pin_id' => $pinId, 'stack' => ['admin.pin.view', 'admin.pins.list', 'admin.root']]);
                $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('admin.settings_admins_pins.prompts.pin_send_all_confirm', ['pin_id' => $pinId, 'confirm_word' => $confirmSendWord])), $this->uiKeyboard->replyMenu([[UiLabels::back($this->catalog), UiLabels::main($this->catalog)]]));
                return;
            }
        }

        if ($stateName === 'admin.pin.edit') {
            $pinId = (int) ($payload['pin_id'] ?? 0);
            if ($text === UiLabels::back($this->catalog)) {
                $this->openAdminPinView($chatId, $userId, $pinId);
                return;
            }
            if (trim($text) === '' || str_starts_with($text, '/')) {
                $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('admin.settings_admins_pins.errors.valid_text_required')));
                return;
            }
            $this->database->updatePinnedMessage($pinId, $text);
            $this->openAdminPinView($chatId, $userId, $pinId, $this->uiText->success($this->catalog->get('admin.settings_admins_pins.success.pin_updated')));
            return;
        }

        if ($stateName === 'admin.pin.delete') {
            $pinId = (int) ($payload['pin_id'] ?? 0);
            if ($text === UiLabels::back($this->catalog)) {
                $this->openAdminPinView($chatId, $userId, $pinId);
                return;
            }
            if (trim($text) === $confirmDeleteWord) {
                $this->database->deletePinnedMessage($pinId);
                $this->openAdminPinsList($chatId, $userId, $this->uiText->success($this->catalog->get('admin.settings_admins_pins.success.pin_deleted')));
                return;
            }
            $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('admin.settings_admins_pins.errors.pin_delete_confirm_required', ['confirm_word' => $confirmDeleteWord])));
            return;
        }

        if ($stateName === 'admin.pin.send') {
            $pinId = (int) ($payload['pin_id'] ?? 0);
            if ($text === UiLabels::back($this->catalog)) {
                $this->openAdminPinView($chatId, $userId, $pinId);
                return;
            }
            if (trim($text) !== $confirmSendWord) {
                $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('admin.settings_admins_pins.errors.pin_send_confirm_required', ['confirm_word' => $confirmSendWord])));
                return;
            }
            $pin = $this->database->getPinnedMessage($pinId);
            if ($pin === null) {
                $this->openAdminPinsList($chatId, $userId, $this->uiText->warning($this->catalog->get('admin.settings_admins_pins.errors.pin_not_found')));
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
            $this->openAdminPinView($chatId, $userId, $pinId, $this->uiText->success($this->catalog->get('admin.settings_admins_pins.success.pin_send_done', ['sent' => $sent, 'pinned' => $pinned])));
            return;
        }

        $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('admin.settings_admins_pins.errors.invalid_section_option')));
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
            'delivery_mode' => $this->settings->get('delivery_mode', 'stock_only'),
        ];
        if ($notice !== null && $notice !== '') {
            $this->telegram->sendMessage($chatId, $notice);
        }
        $this->database->setUserState($userId, 'admin.settings.view', ['stack' => ['admin.root']]);
        $this->telegram->sendMessage($chatId, $this->messageRenderer->render('admin.ui.open.settings_admins_pins.settings.overview', [
            'bot_status' => $vals['bot_status'],
            'free_test_enabled' => $vals['free_test_enabled'] === '1' ? $this->catalog->get('emojis.success') : $this->catalog->get('emojis.error'),
            'agency_request_enabled' => $vals['agency_request_enabled'] === '1' ? $this->catalog->get('emojis.success') : $this->catalog->get('emojis.error'),
            'gw_card_enabled' => $vals['gw_card_enabled'] === '1' ? $this->catalog->get('emojis.success') : $this->catalog->get('emojis.error'),
            'gw_crypto_enabled' => $vals['gw_crypto_enabled'] === '1' ? $this->catalog->get('emojis.success') : $this->catalog->get('emojis.error'),
            'gw_tetrapay_enabled' => $vals['gw_tetrapay_enabled'] === '1' ? $this->catalog->get('emojis.success') : $this->catalog->get('emojis.error'),
            'channel_id' => $vals['channel_id'] !== '' ? $vals['channel_id'] : $this->catalog->get('admin.ui.open.settings_admins_pins.settings.channel_unset'),
            'delivery_mode_label' => $this->catalog->get('admin.settings_admins_pins.labels.delivery_mode'),
            'delivery_mode' => (string) $vals['delivery_mode'],
        ]), $this->uiKeyboard->replyMenu([
            [$this->uiConst(self::ADMIN_SETTINGS_REFRESH), $this->uiConst(self::ADMIN_SETTINGS_EDIT)],
            [$this->uiConst(self::ADMIN_SETTINGS_TOGGLE_BOT), $this->uiConst(self::ADMIN_SETTINGS_SET_CHANNEL)],
            [$this->catalog->get('admin.settings_admins_pins.actions.settings_set_delivery_mode')],
            [$this->uiConst(self::ADMIN_SETTINGS_TOGGLE_FREE_TEST), $this->uiConst(self::ADMIN_SETTINGS_TOGGLE_AGENCY)],
            [$this->uiConst(self::ADMIN_SETTINGS_TOGGLE_GW_CARD), $this->uiConst(self::ADMIN_SETTINGS_TOGGLE_GW_CRYPTO), $this->uiConst(self::ADMIN_SETTINGS_TOGGLE_GW_TETRA)],
            [UiLabels::back($this->catalog), UiLabels::main($this->catalog)],
        ]));
    }

    private function openAdminAdminsList(int $chatId, int $userId, ?string $notice = null): void
    {
        $options = [];
        $lines = [];
        $buttons = [[$this->uiConst(self::ADMIN_ADMINS_ADD)]];
        foreach (array_values($this->database->listAdminUsers()) as $idx => $adm) {
            $num = (string) ($idx + 1);
            $uid = (int) ($adm['user_id'] ?? 0);
            if ($uid <= 0) {
                continue;
            }
            $lines[] = $this->catalog->get('admin.ui.open.settings_admins_pins.admins.row', ['num' => $num, 'uid' => $uid]);
            $options[$num] = $uid;
            $buttons[] = [$this->catalog->get('admin.ui.open.settings_admins_pins.admins.button', ['num' => $num, 'uid' => $uid])];
        }
        foreach (Config::adminIds() as $ownerId) {
            $lines[] = $this->catalog->get('admin.ui.open.settings_admins_pins.admins.owner_row', ['emoji' => $this->catalog->get('admin.ui.open.settings_admins_pins.admins.owner_emoji'), 'owner_id' => $ownerId]);
        }
        $buttons[] = [UiLabels::back($this->catalog), UiLabels::main($this->catalog)];
        $this->database->setUserState($userId, 'admin.admins.list', ['options' => $options, 'stack' => ['admin.root']]);
        if ($notice !== null && $notice !== '') {
            $this->telegram->sendMessage($chatId, $notice);
        }
        $this->telegram->sendMessage(
            $chatId,
            $this->messageRenderer->render('admin.ui.open.settings_admins_pins.admins.overview', [
                'list' => $lines !== [] ? implode("\n", $lines) : $this->catalog->get('admin.ui.open.settings_admins_pins.admins.empty'),
            ], ['list']),
            $this->uiKeyboard->replyMenu($buttons)
        );
    }

    private function openAdminAdminView(int $chatId, int $userId, int $targetUid, ?string $notice = null): void
    {
        if (in_array($targetUid, Config::adminIds(), true)) {
            $this->openAdminAdminsList($chatId, $userId, $this->uiText->warning($this->catalog->get('admin.ui.open.settings_admins_pins.admin_view.owner_locked')));
            return;
        }
        $perms = $this->database->getAdminPermissions($targetUid);
        $permKeys = ['types', 'stock', 'users', 'settings', 'payments', 'requests', 'broadcast', 'agents', 'panels'];
        $rows = [];
        $permLabels = [];
        $lines = [];
        foreach ($permKeys as $k) {
            $enabled = (bool) ($perms[$k] ?? false);
            $label = $this->catalog->get('admin.ui.open.settings_admins_pins.admin_view.perm_row', [
                'status' => $enabled ? $this->catalog->get('emojis.success') : $this->catalog->get('emojis.error'),
                'perm' => $k,
            ]);
            $rows[] = [$label];
            $permLabels[$label] = $k;
            $lines[] = $label;
        }
        $rows[] = [$this->uiConst(self::ADMIN_ADMIN_DELETE)];
        $rows[] = [UiLabels::back($this->catalog), UiLabels::main($this->catalog)];
        $this->database->setUserState($userId, 'admin.admin.view', ['target_user_id' => $targetUid, 'perm_labels' => $permLabels, 'stack' => ['admin.admins.list', 'admin.root']]);
        if ($notice !== null && $notice !== '') {
            $this->telegram->sendMessage($chatId, $notice);
        }
        $this->telegram->sendMessage($chatId, $this->messageRenderer->render('admin.ui.open.settings_admins_pins.admin_view.overview', [
            'target_uid' => $targetUid,
            'permissions' => implode("\n", $lines),
        ], ['permissions']), $this->uiKeyboard->replyMenu($rows));
    }

    private function openAdminPinsList(int $chatId, int $userId, ?string $notice = null): void
    {
        $options = [];
        $lines = [];
        $buttons = [[$this->uiConst(self::ADMIN_PINS_ADD)]];
        foreach (array_values($this->database->listPinnedMessages()) as $idx => $pin) {
            $num = (string) ($idx + 1);
            $pinId = (int) ($pin['id'] ?? 0);
            if ($pinId <= 0) {
                continue;
            }
            $preview = mb_substr(trim((string) ($pin['text'] ?? '')), 0, 24);
            $lines[] = $this->catalog->get('admin.ui.open.settings_admins_pins.pins.row', ['num' => $num, 'pin_id' => $pinId, 'preview' => htmlspecialchars($preview)]);
            $options[$num] = $pinId;
            $buttons[] = [$this->catalog->get('admin.ui.open.settings_admins_pins.pins.button', ['num' => $num, 'pin_id' => $pinId])];
        }
        $buttons[] = [UiLabels::back($this->catalog), UiLabels::main($this->catalog)];
        $this->database->setUserState($userId, 'admin.pins.list', ['options' => $options, 'stack' => ['admin.root']]);
        if ($notice !== null && $notice !== '') {
            $this->telegram->sendMessage($chatId, $notice);
        }
        $this->telegram->sendMessage(
            $chatId,
            $this->messageRenderer->render('admin.ui.open.settings_admins_pins.pins.overview', [
                'list' => $lines !== [] ? implode("\n", $lines) : $this->catalog->get('admin.ui.open.settings_admins_pins.pins.empty'),
            ], ['list']),
            $this->uiKeyboard->replyMenu($buttons)
        );
    }

    private function openAdminPinView(int $chatId, int $userId, int $pinId, ?string $notice = null): void
    {
        $pin = $this->database->getPinnedMessage($pinId);
        if ($pin === null) {
            $this->openAdminPinsList($chatId, $userId, $this->uiText->warning($this->catalog->get('admin.ui.open.settings_admins_pins.pin_view.not_found')));
            return;
        }
        $sendCount = count($this->database->getPinnedSends($pinId));
        $this->database->setUserState($userId, 'admin.pin.view', ['pin_id' => $pinId, 'stack' => ['admin.pins.list', 'admin.root']]);
        if ($notice !== null && $notice !== '') {
            $this->telegram->sendMessage($chatId, $notice);
        }
        $this->telegram->sendMessage($chatId, $this->messageRenderer->render('admin.ui.open.settings_admins_pins.pin_view.overview', [
            'pin_id' => $pinId,
            'text' => (string) ($pin['text'] ?? ''),
            'sent_count' => (string) $sendCount,
        ]), $this->uiKeyboard->replyMenu([
            [$this->uiConst(self::ADMIN_PIN_SEND_ALL)],
            [$this->uiConst(self::ADMIN_PIN_EDIT), $this->uiConst(self::ADMIN_PIN_DELETE)],
            [UiLabels::back($this->catalog), UiLabels::main($this->catalog)],
        ]));
    }

    private function handleAdminFinalModulesState(int $chatId, int $userId, string $text, array $state, array $message): void
    {
        if ($this->isMainMenuInput($text)) {
            $this->openAdminRoot($chatId, $userId);
            return;
        }
        $stateName = (string) ($state['state_name'] ?? '');
        $payload = is_array($state['payload'] ?? null) ? $state['payload'] : [];
        $agentsRefreshLabel = $this->catalog->get('admin.final_modules.actions.agents_refresh');
        $panelsSettingsLabel = $this->catalog->get('admin.final_modules.actions.panels_refresh');
        $panelToggleLabel = $this->catalog->get('admin.final_modules.actions.panel_toggle');
        $panelDeleteLabel = $this->catalog->get('admin.final_modules.actions.panel_delete');
        $panelPkgAddLabel = $this->catalog->get('admin.final_modules.actions.panel_pkg_add');
        $broadcastScopeAllLabel = $this->catalog->get('admin.final_modules.actions.broadcast_scope_all');
        $broadcastScopeUsersLabel = $this->catalog->get('admin.final_modules.actions.broadcast_scope_users');
        $broadcastScopeAgentsLabel = $this->catalog->get('admin.final_modules.actions.broadcast_scope_agents');
        $broadcastScopeAdminsLabel = $this->catalog->get('admin.final_modules.actions.broadcast_scope_admins');
        $broadcastSendLabel = $this->catalog->get('admin.final_modules.actions.broadcast_send');
        $deliveriesRefreshLabel = $this->catalog->get('admin.final_modules.actions.deliveries_refresh');
        $deliveryDoLabel = $this->catalog->get('admin.final_modules.actions.delivery_do');
        $groupopsSetGroupLabel = $this->catalog->get('admin.final_modules.actions.groupops_set_group');
        $groupopsRestoreLabel = $this->catalog->get('admin.final_modules.actions.groupops_restore');
        $freetestRuleLabel = $this->catalog->get('admin.final_modules.actions.freetest_rule');
        $freetestResetLabel = $this->catalog->get('admin.final_modules.actions.freetest_reset');
        $deleteConfirmWord = $this->catalog->get('admin.final_modules.keywords.delete_confirm');
        $deliverConfirmWord = $this->catalog->get('admin.final_modules.keywords.deliver_confirm');

        if ($stateName === 'admin.agents.list') {
            if ($text === UiLabels::back($this->catalog)) {
                $this->openAdminRoot($chatId, $userId);
                return;
            }
            if ($text === $agentsRefreshLabel || $text === $this->uiConst(self::ADMIN_AGENTS_REFRESH)) {
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
            if ($text === UiLabels::back($this->catalog)) {
                $this->openAdminAgentsList($chatId, $userId);
                return;
            }
            $options = is_array($payload['options'] ?? null) ? $payload['options'] : [];
            $selected = $this->extractOptionKey($text);
                $pkgId = isset($options[$selected]) ? (int) $options[$selected] : 0;
                if ($pkgId > 0) {
                    $this->database->setUserState($userId, 'admin.agent.edit', ['agent_id' => $agentId, 'package_id' => $pkgId]);
                    $this->telegram->sendMessage($chatId, $this->uiText->info($this->catalog->get('admin.final_modules.prompts.agent_price_input')), $this->uiKeyboard->replyMenu([[UiLabels::back($this->catalog), UiLabels::main($this->catalog)]]));
                    return;
                }
            }
        if ($stateName === 'admin.agent.edit') {
            $agentId = (int) ($payload['agent_id'] ?? 0);
            $pkgId = (int) ($payload['package_id'] ?? 0);
            if ($text === UiLabels::back($this->catalog)) {
                $this->openAdminAgentView($chatId, $userId, $agentId);
                return;
            }
            $raw = trim($text);
            if ($raw === '-' || $raw === '—') {
                $this->database->clearAgencyPrice($agentId, $pkgId);
                $this->openAdminAgentView($chatId, $userId, $agentId, $this->uiText->success($this->catalog->get('admin.final_modules.success.agent_price_deleted')));
                return;
            }
            $price = (int) preg_replace('/\D+/', '', $raw);
            if ($price <= 0) {
                $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('admin.final_modules.errors.valid_price_required')));
                return;
            }
            $this->database->setAgencyPrice($agentId, $pkgId, $price);
            $this->openAdminAgentView($chatId, $userId, $agentId, $this->uiText->success($this->catalog->get('admin.final_modules.success.agent_price_saved')));
            return;
        }

        if ($stateName === 'admin.panels.list') {
            if ($text === UiLabels::back($this->catalog)) {
                $this->openAdminRoot($chatId, $userId);
                return;
            }
            if ($text === $panelsSettingsLabel || $text === $this->uiConst(self::ADMIN_PANELS_REFRESH)) {
                $this->openAdminPanelSettings($chatId, $userId);
                return;
            }
            $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('admin.final_modules.errors.panels_route_hint')));
        }
        if ($stateName === 'admin.panel.create') {
            $returnTypeId = (int) ($payload['return_type_id'] ?? 0);
            if ($text === UiLabels::back($this->catalog)) {
                $step = (string) ($payload['step'] ?? 'title');
                $data = is_array($payload['data'] ?? null) ? $payload['data'] : [];
                if ($step === 'title') {
                    if ($returnTypeId > 0) {
                        $this->openAdminTypeView($chatId, $userId, $returnTypeId);
                    } else {
                        $this->openAdminPanelsList($chatId, $userId);
                    }
                    return;
                }
                $prev = [
                    'min_gb' => 'title',
                    'max_gb' => 'min_gb',
                    'step_gb' => 'max_gb',
                    'price_per_gb' => 'step_gb',
                    'duration_policy' => 'price_per_gb',
                    'duration_days' => 'duration_policy',
                    'provider' => 'duration_days',
                    'group_ids' => 'provider',
                    'description' => 'group_ids',
                    'confirm' => 'description',
                ];
                $backStep = $prev[$step] ?? 'title';
                $extra = $returnTypeId > 0 ? ['return_type_id' => $returnTypeId] : [];
                $this->database->setUserState($userId, 'admin.panel.create', array_merge($extra, ['step' => $backStep, 'data' => $data]));
                $this->promptPanelWizardStep($chatId, $userId, 'admin.panel.create', $backStep, $data, $extra);
                return;
            }
            $step = (string) ($payload['step'] ?? 'title');
            $data = is_array($payload['data'] ?? null) ? $payload['data'] : [];
            $extra = $returnTypeId > 0 ? ['return_type_id' => $returnTypeId] : [];
            if (!$this->applyPanelWizardInput($chatId, $userId, 'admin.panel.create', $step, $text, $data, $extra)) {
                return;
            }
            $serviceId = $this->database->createProvisioningService([
                'title' => (string) ($data['title'] ?? ''),
                'description' => (string) ($data['description'] ?? ''),
                'min_gb' => (float) ($data['min_gb'] ?? 0),
                'max_gb' => (float) ($data['max_gb'] ?? 0),
                'step_gb' => (float) ($data['step_gb'] ?? 1),
                'price_per_gb' => (int) ($data['price_per_gb'] ?? 0),
                'duration_policy' => (string) ($data['duration_policy'] ?? 'fixed_days'),
                'duration_days' => (($data['duration_policy'] ?? 'fixed_days') === 'fixed_days') ? (int) ($data['duration_days'] ?? 30) : null,
                'provider' => (string) ($data['provider'] ?? 'pasarguard'),
                'provider_group_ids' => (string) ($data['group_ids'] ?? ''),
                'is_active' => 1,
            ]);
            if ($returnTypeId > 0) {
                $this->openAdminTypeView($chatId, $userId, $returnTypeId, $this->uiText->success($this->catalog->get('admin.final_modules.success.panel_created')));
            } else {
                $this->openAdminPanelView($chatId, $userId, $serviceId, $this->uiText->success($this->catalog->get('admin.final_modules.success.panel_created')));
            }
            return;
        }
        if ($stateName === 'admin.panel.view') {
            $serviceId = (int) ($payload['panel_id'] ?? 0);
            if ($text === UiLabels::back($this->catalog)) {
                $this->openAdminPanelsList($chatId, $userId);
                return;
            }
            if ($text === $panelToggleLabel || $text === $this->uiConst(self::ADMIN_PANEL_TOGGLE)) {
                $service = $this->database->getProvisioningService($serviceId);
                if (is_array($service)) {
                    $active = ((int) ($service['is_active'] ?? 0)) === 1;
                    $this->database->updateProvisioningServiceActive($serviceId, !$active);
                }
                $this->openAdminPanelView($chatId, $userId, $serviceId, $this->uiText->success($this->catalog->get('admin.final_modules.success.panel_status_updated')));
                return;
            }
            if ($text === $panelDeleteLabel || $text === $this->uiConst(self::ADMIN_PANEL_DELETE)) {
                $this->database->setUserState($userId, 'admin.panel.delete', ['panel_id' => $serviceId]);
                $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('admin.final_modules.prompts.panel_delete_confirm', ['panel_id' => $serviceId, 'confirm_word' => $deleteConfirmWord])), $this->uiKeyboard->replyMenu([[UiLabels::back($this->catalog), UiLabels::main($this->catalog)]]));
                return;
            }
            if ($text === $panelPkgAddLabel || $text === $this->uiConst(self::ADMIN_PANEL_PKG_ADD)) {
                $panel = $this->database->getProvisioningService($serviceId);
                $data = is_array($panel) ? [
                    'title' => (string) ($panel['title'] ?? ''),
                    'min_gb' => (float) ($panel['min_gb'] ?? 0),
                    'max_gb' => (float) ($panel['max_gb'] ?? 0),
                    'step_gb' => (float) ($panel['step_gb'] ?? 1),
                    'price_per_gb' => (int) ($panel['price_per_gb'] ?? 0),
                    'duration_policy' => (string) ($panel['duration_policy'] ?? 'fixed_days'),
                    'duration_days' => (int) ($panel['duration_days'] ?? 30),
                    'provider' => (string) ($panel['provider'] ?? 'pasarguard'),
                    'group_ids' => (string) ($panel['provider_group_ids'] ?? ''),
                    'description' => (string) ($panel['description'] ?? ''),
                ] : [];
                $this->database->setUserState($userId, 'admin.panel.edit', ['panel_id' => $serviceId, 'step' => 'title', 'data' => $data]);
                $this->promptPanelWizardStep($chatId, $userId, 'admin.panel.edit', 'title', $data);
                return;
            }
        }
        if ($stateName === 'admin.panel.edit') {
            $panelId = (int) ($payload['panel_id'] ?? 0);
            if ($text === UiLabels::back($this->catalog)) {
                $step = (string) ($payload['step'] ?? 'title');
                $data = is_array($payload['data'] ?? null) ? $payload['data'] : [];
                if ($step === 'title') {
                    $this->openAdminPanelView($chatId, $userId, $panelId);
                    return;
                }
                $prev = [
                    'min_gb' => 'title',
                    'max_gb' => 'min_gb',
                    'step_gb' => 'max_gb',
                    'price_per_gb' => 'step_gb',
                    'duration_policy' => 'price_per_gb',
                    'duration_days' => 'duration_policy',
                    'provider' => 'duration_days',
                    'group_ids' => 'provider',
                    'description' => 'group_ids',
                    'confirm' => 'description',
                ];
                $backStep = $prev[$step] ?? 'title';
                $this->database->setUserState($userId, 'admin.panel.edit', ['panel_id' => $panelId, 'step' => $backStep, 'data' => $data]);
                $this->promptPanelWizardStep($chatId, $userId, 'admin.panel.edit', $backStep, $data);
                return;
            }
            $step = (string) ($payload['step'] ?? 'title');
            $data = is_array($payload['data'] ?? null) ? $payload['data'] : [];
            if ($panelId <= 0 || !$this->applyPanelWizardInput($chatId, $userId, 'admin.panel.edit', $step, $text, $data, ['panel_id' => $panelId])) {
                return;
            }
            $this->database->updateProvisioningService($panelId, [
                'title' => (string) ($data['title'] ?? ''),
                'description' => (string) ($data['description'] ?? ''),
                'min_gb' => (float) ($data['min_gb'] ?? 0),
                'max_gb' => (float) ($data['max_gb'] ?? 0),
                'step_gb' => (float) ($data['step_gb'] ?? 1),
                'price_per_gb' => (int) ($data['price_per_gb'] ?? 0),
                'duration_policy' => (string) ($data['duration_policy'] ?? 'fixed_days'),
                'duration_days' => (($data['duration_policy'] ?? 'fixed_days') === 'fixed_days') ? (int) ($data['duration_days'] ?? 30) : null,
                'provider' => (string) ($data['provider'] ?? 'pasarguard'),
                'provider_group_ids' => (string) ($data['group_ids'] ?? ''),
                'is_active' => 1,
            ]);
            $this->openAdminPanelView($chatId, $userId, $panelId, $this->uiText->success($this->catalog->get('admin.final_modules.success.panel_package_created')));
            return;
        }
        if ($stateName === 'admin.panel.settings') {
            $mode = (string) ($payload['mode'] ?? 'menu');
            if ($mode === 'menu') {
                if ($text === UiLabels::back($this->catalog)) {
                    $this->openAdminRoot($chatId, $userId);
                    return;
                }
                if ($text === $this->catalog->get('admin.final_modules.actions.panel_conn_add')) {
                    $this->database->setUserState($userId, 'admin.panel.settings', ['mode' => 'wizard', 'step' => 'base_url', 'data' => []]);
                    $this->promptPanelConnectionStep($chatId, $userId, 'base_url', []);
                    return;
                }
                if ($text === $this->catalog->get('admin.final_modules.actions.panel_conn_edit')) {
                    $this->database->setUserState($userId, 'admin.panel.settings', [
                        'mode' => 'wizard',
                        'step' => 'base_url',
                        'data' => [
                            'base_url' => trim($this->settings->get('pg_base_url', '')),
                            'username' => trim($this->settings->get('pg_username', '')),
                        ],
                    ]);
                    $this->promptPanelConnectionStep($chatId, $userId, 'base_url', [
                        'base_url' => trim($this->settings->get('pg_base_url', '')),
                        'username' => trim($this->settings->get('pg_username', '')),
                    ]);
                    return;
                }
                if ($text === $this->catalog->get('admin.final_modules.actions.panel_conn_delete')) {
                    $this->database->setUserState($userId, 'admin.panel.settings', ['mode' => 'delete_confirm']);
                    $this->openAdminPanelDeleteConfirm($chatId);
                    return;
                }
                if ($text === $this->catalog->get('admin.final_modules.actions.panel_conn_status')) {
                    $this->sendAdminPanelStatusOverview($chatId);
                    return;
                }
                $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('admin.panel_settings.errors.invalid_menu_option')));
                return;
            }
            if ($mode === 'delete_confirm') {
                if ($text === $this->catalog->get('admin.final_modules.actions.panel_conn_delete_confirm')) {
                    $this->settings->set('pg_base_url', '');
                    $this->settings->set('pg_username', '');
                    $this->settings->set('pg_password', '');
                    $this->openAdminPanelSettings($chatId, $userId, $this->uiText->success($this->catalog->get('admin.panel_settings.success.connection_deleted')));
                    return;
                }
                if ($text === $this->catalog->get('admin.final_modules.actions.panel_conn_delete_cancel') || $text === UiLabels::back($this->catalog)) {
                    $this->openAdminPanelSettings($chatId, $userId);
                    return;
                }
                $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('admin.panel_settings.errors.delete_choice_required')));
                return;
            }
            if ($text === UiLabels::back($this->catalog)) {
                $step = (string) ($payload['step'] ?? 'base_url');
                $data = is_array($payload['data'] ?? null) ? $payload['data'] : [];
                if ($step === 'base_url') {
                    $this->openAdminPanelSettings($chatId, $userId);
                    return;
                }
                $prev = ['username' => 'base_url', 'password' => 'username', 'confirm' => 'password'];
                $backStep = $prev[$step] ?? 'base_url';
                $this->database->setUserState($userId, 'admin.panel.settings', ['mode' => 'wizard', 'step' => $backStep, 'data' => $data]);
                $this->promptPanelConnectionStep($chatId, $userId, $backStep, $data);
                return;
            }
            $step = (string) ($payload['step'] ?? 'base_url');
            $data = is_array($payload['data'] ?? null) ? $payload['data'] : [];
            $raw = trim($text);
            if ($raw === '' || str_starts_with($raw, '/')) {
                $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('admin.panel_settings.errors.invalid_input')));
                return;
            }
            if ($step === 'base_url') {
                $data['base_url'] = $raw;
                $this->database->setUserState($userId, 'admin.panel.settings', ['mode' => 'wizard', 'step' => 'username', 'data' => $data]);
                $this->promptPanelConnectionStep($chatId, $userId, 'username', $data);
                return;
            }
            if ($step === 'username') {
                $data['username'] = $raw;
                $this->database->setUserState($userId, 'admin.panel.settings', ['mode' => 'wizard', 'step' => 'password', 'data' => $data]);
                $this->promptPanelConnectionStep($chatId, $userId, 'password', $data);
                return;
            }
            if ($step === 'password') {
                $data['password'] = $raw;
                $this->database->setUserState($userId, 'admin.panel.settings', ['mode' => 'wizard', 'step' => 'confirm', 'data' => $data]);
                $preview = $this->messageRenderer->render('admin.panel_settings.messages.wizard_preview', [
                    'base_url' => (string) ($data['base_url'] ?? ''),
                    'username' => (string) ($data['username'] ?? ''),
                    'password_masked' => str_repeat('*', min(strlen((string) ($data['password'] ?? '')), 10)),
                ]);
                $this->telegram->sendMessage(
                    $chatId,
                    $preview,
                    $this->uiKeyboard->replyMenu([[$this->catalog->get('buttons.confirm_yes')], [UiLabels::back($this->catalog)]])
                );
                return;
            }
            if ($raw !== $this->catalog->get('buttons.confirm_yes')) {
                $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('admin.panel_settings.errors.confirm_required')));
                return;
            }
            $this->settings->set('pg_base_url', (string) ($data['base_url'] ?? ''));
            $this->settings->set('pg_username', (string) ($data['username'] ?? ''));
            $this->settings->set('pg_password', (string) ($data['password'] ?? ''));
            $this->database->setUserState($userId, 'admin.panel.settings', ['mode' => 'menu']);
            $this->telegram->sendMessage($chatId, $this->uiText->success($this->catalog->get('admin.panel_settings.success.connection_saved')));
            return;
        }
        if ($stateName === 'admin.panel.delete') {
            $panelId = (int) ($payload['panel_id'] ?? 0);
            if ($text === UiLabels::back($this->catalog)) {
                $this->openAdminPanelView($chatId, $userId, $panelId);
                return;
            }
            if (trim($text) === $deleteConfirmWord) {
                $this->database->deleteProvisioningService($panelId);
                $this->openAdminPanelsList($chatId, $userId, $this->uiText->success($this->catalog->get('admin.final_modules.success.panel_deleted')));
                return;
            }
            $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('admin.final_modules.errors.panel_delete_confirm_required', ['confirm_word' => $deleteConfirmWord])));
            return;
        }

        if ($stateName === 'admin.broadcast.compose') {
            if ($text === UiLabels::back($this->catalog)) {
                $this->openAdminRoot($chatId, $userId);
                return;
            }
            if (trim($text) === '' || str_starts_with($text, '/')) {
                $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('admin.final_modules.errors.broadcast_message_required')));
                return;
            }
            $this->database->setUserState($userId, 'admin.broadcast.confirm', ['message' => $text]);
            $this->telegram->sendMessage($chatId, $this->messageRenderer->render('admin.final_modules.prompts.broadcast_confirm_overview', [
                'message_preview' => $text,
            ]), $this->uiKeyboard->replyMenu([
                [$this->uiConst(self::ADMIN_BROADCAST_SCOPE_ALL), $this->uiConst(self::ADMIN_BROADCAST_SCOPE_USERS)],
                [$this->uiConst(self::ADMIN_BROADCAST_SCOPE_AGENTS), $this->uiConst(self::ADMIN_BROADCAST_SCOPE_ADMINS)],
                [$this->uiConst(self::ADMIN_BROADCAST_SEND)],
                [UiLabels::back($this->catalog), UiLabels::main($this->catalog)],
            ]));
            return;
        }
        if ($stateName === 'admin.broadcast.confirm') {
            if ($text === UiLabels::back($this->catalog)) {
                $this->openAdminBroadcastCompose($chatId, $userId);
                return;
            }
            $scopeMap = [
                $this->uiConst(self::ADMIN_BROADCAST_SCOPE_ALL) => 'all',
                $this->uiConst(self::ADMIN_BROADCAST_SCOPE_USERS) => 'users',
                $this->uiConst(self::ADMIN_BROADCAST_SCOPE_AGENTS) => 'agents',
                $this->uiConst(self::ADMIN_BROADCAST_SCOPE_ADMINS) => 'admins',
                $broadcastScopeAllLabel => 'all',
                $broadcastScopeUsersLabel => 'users',
                $broadcastScopeAgentsLabel => 'agents',
                $broadcastScopeAdminsLabel => 'admins',
            ];
            $scope = (string) ($payload['scope'] ?? 'all');
            if (isset($scopeMap[$text])) {
                $scope = $scopeMap[$text];
                $this->database->setUserState($userId, 'admin.broadcast.confirm', ['message' => (string) ($payload['message'] ?? ''), 'scope' => $scope]);
                $this->telegram->sendMessage($chatId, $this->uiText->info($this->catalog->get('admin.final_modules.info.scope_selected', ['scope' => $scope])));
                return;
            }
            if ($text === $broadcastSendLabel || $text === $this->uiConst(self::ADMIN_BROADCAST_SEND)) {
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
                $this->telegram->sendMessage($chatId, $this->uiText->success($this->catalog->get('admin.final_modules.success.broadcast_done', ['sent' => $sent])));
                return;
            }
        }

        if ($stateName === 'admin.deliveries.list') {
            if ($text === UiLabels::back($this->catalog)) {
                $this->openAdminRoot($chatId, $userId);
                return;
            }
            if ($text === $deliveriesRefreshLabel || $text === $this->uiConst(self::ADMIN_DELIVERIES_REFRESH)) {
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
            if ($text === UiLabels::back($this->catalog)) {
                $this->openAdminDeliveriesList($chatId, $userId);
                return;
            }
            if ($text === $deliveryDoLabel || $text === $this->uiConst(self::ADMIN_DELIVERY_DO)) {
                $this->database->setUserState($userId, 'admin.delivery.review', ['order_id' => $orderId]);
                $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('admin.final_modules.prompts.delivery_confirm', ['order_id' => $orderId, 'confirm_word' => $deliverConfirmWord])), $this->uiKeyboard->replyMenu([[UiLabels::back($this->catalog), UiLabels::main($this->catalog)]]));
                return;
            }
        }
        if ($stateName === 'admin.delivery.review') {
            $orderId = (int) ($payload['order_id'] ?? 0);
            if ($text === UiLabels::back($this->catalog)) {
                $this->openAdminDeliveryView($chatId, $userId, $orderId);
                return;
            }
            if (trim($text) === $deliverConfirmWord) {
                $res = $this->database->deliverPendingOrder($orderId);
                if ($res['ok'] ?? false) {
                    $this->openAdminDeliveriesList($chatId, $userId, $this->uiText->success($this->catalog->get('admin.final_modules.success.delivery_done')));
                } else {
                    $this->openAdminDeliveryView($chatId, $userId, $orderId, $this->uiText->warning($this->catalog->get('admin.final_modules.errors.delivery_failed')));
                }
                return;
            }
        }

        if ($stateName === 'admin.groupops.view') {
            if ($text === UiLabels::back($this->catalog)) {
                $this->openAdminRoot($chatId, $userId);
                return;
            }
            if ($text === $groupopsSetGroupLabel || $text === $this->uiConst(self::ADMIN_GROUPOPS_SET_GROUP)) {
                $this->database->setUserState($userId, 'admin.groupops.action', ['mode' => 'group_id']);
                $this->telegram->sendMessage($chatId, $this->uiText->info($this->catalog->get('admin.final_modules.prompts.group_id_input')), $this->uiKeyboard->replyMenu([[UiLabels::back($this->catalog), UiLabels::main($this->catalog)]]));
                return;
            }
            if ($text === $groupopsRestoreLabel || $text === $this->uiConst(self::ADMIN_GROUPOPS_RESTORE)) {
                $this->database->setUserState($userId, 'admin.groupops.action', ['mode' => 'restore']);
                $this->telegram->sendMessage($chatId, $this->uiText->info($this->catalog->get('admin.final_modules.prompts.restore_json_input')), $this->uiKeyboard->replyMenu([[UiLabels::back($this->catalog), UiLabels::main($this->catalog)]]));
                return;
            }
        }
        if ($stateName === 'admin.groupops.action') {
            $mode = (string) ($payload['mode'] ?? '');
            if ($text === UiLabels::back($this->catalog)) {
                $this->openAdminGroupOpsView($chatId, $userId);
                return;
            }
            if ($mode === 'group_id') {
                $val = trim($text);
                if ($val !== '-' && !preg_match('/^-?\\d+$/', $val)) {
                    $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('admin.final_modules.errors.group_id_numeric')));
                    return;
                }
                $this->settings->set('group_id', $val === '-' ? '' : $val);
                $this->openAdminGroupOpsView($chatId, $userId, $this->uiText->success($this->catalog->get('admin.final_modules.success.group_id_saved')));
                return;
            }
            if ($mode === 'restore') {
                $raw = trim((string) ($message['text'] ?? ''));
                $data = json_decode($raw, true);
                $settings = is_array($data) ? ($data['settings'] ?? null) : null;
                if (!is_array($settings)) {
                    $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('admin.final_modules.errors.invalid_json_structure')));
                    return;
                }
                foreach ($settings as $k => $v) {
                    $key = trim((string) $k);
                    if ($key !== '') {
                        $this->settings->set($key, (string) $v);
                    }
                }
                $this->openAdminGroupOpsView($chatId, $userId, $this->uiText->success($this->catalog->get('admin.final_modules.success.settings_restored')));
                return;
            }
        }

        if ($stateName === 'admin.freetest.menu') {
            if ($text === UiLabels::back($this->catalog)) {
                $this->openAdminRoot($chatId, $userId);
                return;
            }
            if ($text === $freetestRuleLabel || $text === $this->uiConst(self::ADMIN_FREETEST_RULE)) {
                $this->database->setUserState($userId, 'admin.freetest.rule', []);
                $this->telegram->sendMessage($chatId, $this->uiText->info($this->catalog->get('admin.final_modules.prompts.freetest_rule_format')), $this->uiKeyboard->replyMenu([[UiLabels::back($this->catalog), UiLabels::main($this->catalog)]]));
                return;
            }
            if ($text === $freetestResetLabel || $text === $this->uiConst(self::ADMIN_FREETEST_RESET)) {
                $this->database->setUserState($userId, 'admin.freetest.reset', []);
                $this->telegram->sendMessage($chatId, $this->uiText->info($this->catalog->get('admin.final_modules.prompts.freetest_reset_user_id_input')), $this->uiKeyboard->replyMenu([[UiLabels::back($this->catalog), UiLabels::main($this->catalog)]]));
                return;
            }
        }
        if ($stateName === 'admin.freetest.rule') {
            if ($text === UiLabels::back($this->catalog)) {
                $this->openAdminFreeTestMenu($chatId, $userId);
                return;
            }
            $parts = array_map('trim', explode('|', $text));
            $packageId = (int) ($parts[0] ?? 0);
            $maxClaims = (int) ($parts[1] ?? 1);
            $cooldownDays = (int) ($parts[2] ?? 0);
            if ($packageId <= 0) {
                $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('admin.final_modules.errors.invalid_format')));
                return;
            }
            $this->database->saveFreeTestRule($packageId, $maxClaims, $cooldownDays, true);
            $this->openAdminFreeTestMenu($chatId, $userId, $this->uiText->success($this->catalog->get('admin.final_modules.success.freetest_rule_saved')));
            return;
        }
        if ($stateName === 'admin.freetest.reset') {
            if ($text === UiLabels::back($this->catalog)) {
                $this->openAdminFreeTestMenu($chatId, $userId);
                return;
            }
            $targetUserId = (int) preg_replace('/\D+/', '', $text);
            if ($targetUserId <= 0) {
                $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('admin.final_modules.errors.valid_user_id_required')));
                return;
            }
            $this->database->resetFreeTestQuota($targetUserId);
            $this->openAdminFreeTestMenu($chatId, $userId, $this->uiText->success($this->catalog->get('admin.final_modules.success.freetest_quota_reset')));
            return;
        }
    }

    private function openAdminAgentsList(int $chatId, int $userId, ?string $notice = null): void
    {
        $agents = $this->database->listUserIdsForBroadcast('agents');
        $options = [];
        $lines = [];
        $buttons = [[$this->uiConst(self::ADMIN_AGENTS_REFRESH)]];
        foreach (array_values($agents) as $idx => $aid) {
            $num = (string) ($idx + 1);
            $id = (int) $aid;
            if ($id <= 0) {
                continue;
            }
            $lines[] = $this->catalog->get('admin.ui.agents.row', ['num' => $num, 'id' => $id]);
            $options[$num] = $id;
            $buttons[] = [$this->catalog->get('admin.ui.agents.button', ['num' => $num, 'id' => $id])];
        }
        $buttons[] = [UiLabels::back($this->catalog), UiLabels::main($this->catalog)];
        $this->database->setUserState($userId, 'admin.agents.list', ['options' => $options]);
        if ($notice) {
            $this->telegram->sendMessage($chatId, $notice);
        }
        $this->telegram->sendMessage(
            $chatId,
            $this->messageRenderer->render('admin.ui.agents.overview', [
                'list' => $lines !== [] ? implode("\n", $lines) : $this->catalog->get('admin.ui.agents.empty'),
            ], ['list']),
            $this->uiKeyboard->replyMenu($buttons)
        );
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
            $lines[] = $this->catalog->get('admin.ui.agent_view.row', [
                'num' => $num,
                'pkg_id' => $pkgId,
                'name' => (string) ($pkg['name'] ?? $this->catalog->get('messages.generic.dash')),
                'custom' => $custom === null ? $this->catalog->get('messages.generic.dash') : (string) $custom,
            ]);
            $options[$num] = $pkgId;
            $buttons[] = [$this->catalog->get('admin.ui.agent_view.package_button', ['num' => $num, 'id' => $pkgId])];
        }
        $buttons[] = [UiLabels::back($this->catalog), UiLabels::main($this->catalog)];
        $this->database->setUserState($userId, 'admin.agent.view', ['agent_id' => $agentId, 'options' => $options]);
        if ($notice) {
            $this->telegram->sendMessage($chatId, $notice);
        }
        $this->telegram->sendMessage(
            $chatId,
            $this->messageRenderer->render('admin.ui.agent_view.overview', [
                'agent_id' => $agentId,
                'packages' => implode("\n", $lines),
            ], ['packages']),
            $this->uiKeyboard->replyMenu($buttons)
        );
    }

    private function openAdminPanelsList(int $chatId, int $userId, ?string $notice = null): void
    {
        $baseUrl = trim($this->settings->get('pg_base_url', ''));
        $username = trim($this->settings->get('pg_username', ''));
        $hasConnection = $baseUrl !== '' || $username !== '';
        $panelsListText = $this->messageRenderer->render('admin.panel_settings.messages.panels_list', [
            'connection_status' => $hasConnection ? $this->catalog->get('admin.panel_settings.info.connection_exists') : $this->catalog->get('admin.panel_settings.info.connection_missing'),
        ]);
        $buttons = [[$this->uiConst(self::ADMIN_PANELS_REFRESH)]];
        $buttons[] = [UiLabels::back($this->catalog), UiLabels::main($this->catalog)];
        $this->database->setUserState($userId, 'admin.panels.list', []);
        if ($notice) {
            $this->telegram->sendMessage($chatId, $notice);
        }
        $this->telegram->sendMessage($chatId, $panelsListText, $this->uiKeyboard->replyMenu($buttons));
    }

    private function openAdminPanelSettings(int $chatId, int $userId, ?string $notice = null): void
    {
        [$baseUrl, $username, $passwordMasked, $hasConnection] = $this->getPanelConnectionSummary();
        $this->database->setUserState($userId, 'admin.panel.settings', ['mode' => 'menu']);
        if ($notice) {
            $this->telegram->sendMessage($chatId, $notice);
        }
        $rows = $this->buildPanelSettingsKeyboard($hasConnection);
        $overviewText = $this->messageRenderer->render('admin.panel_settings.messages.menu_overview', [
            'base_url' => $baseUrl !== '' ? $baseUrl : '-',
            'username' => $username !== '' ? $username : '-',
            'password_masked' => $passwordMasked,
            'status_line' => $hasConnection
                ? ''
                : $this->catalog->get('admin.panel_settings.messages.status_line', [
                    'status_text' => $this->catalog->get('admin.ui.open.panel_settings.status_empty'),
                ]),
            'status_text' => $hasConnection ? $this->catalog->get('admin.ui.open.panel_settings.status_ready') : $this->catalog->get('admin.ui.open.panel_settings.status_empty'),
            'guide_text' => $hasConnection ? $this->catalog->get('admin.ui.open.panel_settings.guide_ready') : $this->catalog->get('admin.ui.open.panel_settings.guide_empty'),
        ]);

        $this->telegram->sendMessage(
            $chatId,
            $overviewText,
            $this->uiKeyboard->replyMenu($rows)
        );
    }

    /** @return array{0:string,1:string,2:string,3:bool} */
    private function getPanelConnectionSummary(): array
    {
        $baseUrl = trim($this->settings->get('pg_base_url', ''));
        $username = trim($this->settings->get('pg_username', ''));
        $password = trim($this->settings->get('pg_password', ''));
        $passwordMasked = $password === '' ? '-' : str_repeat('*', min(strlen($password), 10));
        $hasConnection = $baseUrl !== '' || $username !== '' || $password !== '';

        return [$baseUrl, $username, $passwordMasked, $hasConnection];
    }

    /** @return list<list<string>> */
    private function buildPanelSettingsKeyboard(bool $hasConnection): array
    {
        $rows = [[
            $hasConnection ? $this->catalog->get('admin.final_modules.actions.panel_conn_edit') : $this->catalog->get('admin.final_modules.actions.panel_conn_add'),
            $this->catalog->get('admin.final_modules.actions.panel_conn_status'),
        ]];
        if ($hasConnection) {
            $rows[] = [$this->catalog->get('admin.final_modules.actions.panel_conn_delete')];
            $rows[] = [UiLabels::back($this->catalog)];
            return $rows;
        }
        $rows[] = [UiLabels::back($this->catalog)];

        return $rows;
    }

    private function sendAdminPanelStatusOverview(int $chatId): void
    {
        [$baseUrl, $username, $passwordMasked, $hasConnection] = $this->getPanelConnectionSummary();
        $snapshotText = $this->messageRenderer->render('admin.panel_settings.messages.menu_overview', [
            'base_url' => $baseUrl !== '' ? $baseUrl : '-',
            'username' => $username !== '' ? $username : '-',
            'password_masked' => $passwordMasked,
            'status_line' => $hasConnection
                ? ''
                : $this->catalog->get('admin.panel_settings.messages.status_line', [
                    'status_text' => $this->catalog->get('admin.ui.open.panel_settings.status_empty'),
                ]),
            'status_text' => $hasConnection ? $this->catalog->get('admin.ui.open.panel_settings.status_ready') : $this->catalog->get('admin.ui.open.panel_settings.status_empty'),
            'guide_text' => $hasConnection ? $this->catalog->get('admin.ui.open.panel_settings.guide_ready') : $this->catalog->get('admin.ui.open.panel_settings.guide_empty'),
        ]);
        $this->telegram->sendMessage($chatId, $snapshotText);
    }

    /** @param array<string,mixed> $data */
    private function promptPanelConnectionStep(int $chatId, int $userId, string $step, array $data): void
    {
        $text = match ($step) {
            'base_url' => $this->messageRenderer->render('admin.panel_settings.messages.wizard_step_base_url'),
            'username' => $this->messageRenderer->render('admin.panel_settings.messages.wizard_step_username'),
            'password' => $this->messageRenderer->render('admin.panel_settings.messages.wizard_step_password'),
            default => '',
        };
        if ($text !== '') {
            $this->telegram->sendMessage($chatId, $text, $this->uiKeyboard->replyMenu([[UiLabels::back($this->catalog)]]));
        }
    }

    private function openAdminPanelDeleteConfirm(int $chatId): void
    {
        $message = $this->messageRenderer->render('admin.panel_settings.messages.delete_confirm', [
            'delete_text' => $this->catalog->get('admin.ui.open.panel_settings.delete_confirm_text'),
            'delete_tip' => $this->catalog->get('admin.ui.open.panel_settings.delete_confirm_tip'),
        ]);
        $this->telegram->sendMessage(
            $chatId,
            $message,
            $this->uiKeyboard->replyMenu([[$this->catalog->get('admin.final_modules.actions.panel_conn_delete_confirm'), $this->catalog->get('admin.final_modules.actions.panel_conn_delete_cancel')]])
        );
    }

    private function openAdminPanelView(int $chatId, int $userId, int $panelId, ?string $notice = null): void
    {
        $panel = $this->database->getProvisioningService($panelId);
        if (!is_array($panel)) {
            $this->openAdminPanelsList($chatId, $userId, $this->uiText->warning($this->catalog->get('admin.ui.panels.not_found')));
            return;
        }
        $durationPolicy = (string) ($panel['duration_policy'] ?? 'fixed_days');
        $durationDays = (int) ($panel['duration_days'] ?? 0);
        $durationText = $durationPolicy === 'unlimited'
            ? $this->catalog->get('admin.panels.labels.duration_unlimited')
            : $this->catalog->get('admin.panels.labels.duration_days', ['days' => $durationDays]);
        $summary = $this->messageRenderer->render('admin.panels.messages.panel_summary', [
            'min_gb' => (string) ($panel['min_gb'] ?? '0'),
            'max_gb' => (string) ($panel['max_gb'] ?? '0'),
            'step_gb' => (string) ($panel['step_gb'] ?? '1'),
            'price_per_gb' => (string) ((int) ($panel['price_per_gb'] ?? 0)),
            'provider' => (string) ($panel['provider'] ?? 'pasarguard'),
            'provider_group_ids' => (string) ($panel['provider_group_ids'] ?? '-'),
            'duration' => $durationText,
        ]);
        if ($notice) {
            $this->telegram->sendMessage($chatId, $notice);
        }
        $this->database->setUserState($userId, 'admin.panel.view', ['panel_id' => $panelId]);
        $panelViewText = $this->messageRenderer->render('admin.panels.messages.panel_view', [
            'panel_id' => $panelId,
            'panel_title' => (string) ($panel['title'] ?? '-'),
            'summary' => $summary,
        ]);
        $this->telegram->sendMessage($chatId, $panelViewText, $this->uiKeyboard->replyMenu([[$this->uiConst(self::ADMIN_PANEL_TOGGLE), $this->uiConst(self::ADMIN_PANEL_DELETE)], [$this->uiConst(self::ADMIN_PANEL_PKG_ADD)], [UiLabels::back($this->catalog), UiLabels::main($this->catalog)]]));
    }

    /** @param array<string,mixed> $data */
    private function promptPanelWizardStep(int $chatId, int $userId, string $stateName, string $step, array $data, array $extraPayload = []): void
    {
        $baseByStep = [
            'title' => 'admin.final_modules.prompts.panel_wizard.title',
            'min_gb' => 'admin.final_modules.prompts.panel_wizard.min_gb',
            'max_gb' => 'admin.final_modules.prompts.panel_wizard.max_gb',
            'step_gb' => 'admin.final_modules.prompts.panel_wizard.step_gb',
            'price_per_gb' => 'admin.final_modules.prompts.panel_wizard.price_per_gb',
            'duration_policy' => 'admin.final_modules.prompts.panel_wizard.duration_policy',
            'duration_days' => 'admin.final_modules.prompts.panel_wizard.duration_days',
            'provider' => 'admin.final_modules.prompts.panel_wizard.provider',
            'group_ids' => 'admin.final_modules.prompts.panel_wizard.group_ids',
            'description' => 'admin.final_modules.prompts.panel_wizard.description',
        ];
        $baseKey = (string) ($baseByStep[$step] ?? '');
        $baseText = $baseKey !== '' ? $this->catalog->get($baseKey) : '';
        $currentText = isset($data[$step])
            ? $this->catalog->get('admin.final_modules.prompts.panel_wizard.current_value', ['value' => htmlspecialchars((string) $data[$step])])
            : '';
        $text = $this->messageRenderer->render('admin.common.notice_with_optional_note', [
            'notice' => $baseText,
            'note_line' => $currentText,
        ], ['notice', 'note_line']);
        $this->database->setUserState($userId, $stateName, array_merge($extraPayload, ['step' => $step, 'data' => $data]));
        $this->telegram->sendMessage($chatId, $this->uiText->info($text), $this->uiKeyboard->replyMenu([[UiLabels::back($this->catalog), UiLabels::main($this->catalog)]]));
    }

    /** @param array<string,mixed> $data */
    private function applyPanelWizardInput(int $chatId, int $userId, string $stateName, string $step, string $text, array &$data, array $extraPayload = []): bool
    {
        $raw = trim($text);
        if ($raw === '' || str_starts_with($raw, '/')) {
            $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('admin.final_modules.errors.valid_input_required')));
            return false;
        }
        $next = '';
        switch ($step) {
            case 'title':
                $data['title'] = $raw;
                $next = 'min_gb';
                break;
            case 'min_gb':
                $val = (float) str_replace(',', '.', $raw);
                if ($val <= 0) { $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('admin.final_modules.errors.invalid_min_gb'))); return false; }
                $data['min_gb'] = $val;
                $next = 'max_gb';
                break;
            case 'max_gb':
                $val = (float) str_replace(',', '.', $raw);
                if ($val < (float) ($data['min_gb'] ?? 0)) { $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('admin.final_modules.errors.invalid_max_gb'))); return false; }
                $data['max_gb'] = $val;
                $next = 'step_gb';
                break;
            case 'step_gb':
                $val = (float) str_replace(',', '.', $raw);
                if ($val <= 0) { $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('admin.final_modules.errors.invalid_step_gb'))); return false; }
                $data['step_gb'] = $val;
                $next = 'price_per_gb';
                break;
            case 'price_per_gb':
                $val = (int) preg_replace('/\D+/', '', $raw);
                if ($val <= 0) { $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('admin.final_modules.errors.valid_price_required'))); return false; }
                $data['price_per_gb'] = $val;
                $next = 'duration_policy';
                break;
            case 'duration_policy':
                $policy = in_array($raw, ['fixed_days', 'unlimited'], true) ? $raw : '';
                if ($policy === '') { $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('admin.final_modules.errors.invalid_duration_policy'))); return false; }
                $data['duration_policy'] = $policy;
                $next = $policy === 'fixed_days' ? 'duration_days' : 'provider';
                break;
            case 'duration_days':
                $val = (int) preg_replace('/\D+/', '', $raw);
                if ($val <= 0) { $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('admin.final_modules.errors.invalid_duration_days'))); return false; }
                $data['duration_days'] = $val;
                $next = 'provider';
                break;
            case 'provider':
                $data['provider'] = $raw;
                $next = 'group_ids';
                break;
            case 'group_ids':
                if ($raw === '') { $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('admin.final_modules.errors.group_ids_required'))); return false; }
                $data['group_ids'] = $raw;
                $next = 'description';
                break;
            case 'description':
                $data['description'] = ($raw === '-' || $raw === '—') ? '' : $raw;
                $durationValue = (string) ($data['duration_policy'] ?? '');
                if (($data['duration_policy'] ?? '') === 'fixed_days') {
                    $durationValue = $this->catalog->get('admin.final_modules.prompts.panel_wizard.duration_with_days', ['policy' => (string) ($data['duration_policy'] ?? ''), 'days' => (int) ($data['duration_days'] ?? 0)]);
                }
                $summary = $this->catalog->get('admin.final_modules.prompts.panel_wizard.summary_template', [
                    'title' => (string) ($data['title'] ?? ''),
                    'min_gb' => (string) ($data['min_gb'] ?? ''),
                    'max_gb' => (string) ($data['max_gb'] ?? ''),
                    'step_gb' => (string) ($data['step_gb'] ?? ''),
                    'price_per_gb' => (string) ($data['price_per_gb'] ?? ''),
                    'duration_value' => $durationValue,
                    'provider' => (string) ($data['provider'] ?? ''),
                    'group_ids' => (string) ($data['group_ids'] ?? ''),
                    'description' => (string) (($data['description'] ?? '') !== '' ? $data['description'] : '-'),
                ]);
                $this->database->setUserState($userId, $stateName, array_merge($extraPayload, ['step' => 'confirm', 'data' => $data]));
                $summaryText = $this->messageRenderer->render('admin.final_modules.messages.panel_wizard_summary', [
                    'summary' => $summary,
                ]);
                $this->telegram->sendMessage(
                    $chatId,
                    $summaryText,
                    $this->uiKeyboard->replyMenu([
                        [$this->catalog->get('buttons.confirm_yes')],
                        [UiLabels::back($this->catalog), UiLabels::main($this->catalog)],
                    ])
                );
                return false;
            case 'confirm':
                if ($raw !== $this->catalog->get('buttons.confirm_yes')) {
                    $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('admin.final_modules.errors.panel_wizard_confirm_required')));
                    return false;
                }
                return true;
            default:
                $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('admin.final_modules.errors.invalid_state')));
                return false;
        }

        $this->promptPanelWizardStep($chatId, $userId, $stateName, $next, $data, $extraPayload);
        return false;
    }

    private function openAdminBroadcastCompose(int $chatId, int $userId, ?string $notice = null): void
    {
        $this->database->setUserState($userId, 'admin.broadcast.compose', []);
        if ($notice) {
            $this->telegram->sendMessage($chatId, $notice);
        }
        $this->telegram->sendMessage(
            $chatId,
            $this->messageRenderer->render('admin.ui.broadcast.overview'),
            $this->uiKeyboard->replyMenu([[UiLabels::back($this->catalog), UiLabels::main($this->catalog)]])
        );
    }

    private function openAdminDeliveriesList(int $chatId, int $userId, ?string $notice = null): void
    {
        $orders = $this->database->listPendingDeliveries(30);
        $options = [];
        $lines = [];
        $buttons = [[$this->uiConst(self::ADMIN_DELIVERIES_REFRESH)]];
        foreach (array_values($orders) as $idx => $ord) {
            $num = (string) ($idx + 1);
            $id = (int) ($ord['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            $lines[] = $this->catalog->get('admin.ui.open.deliveries.list.row', ['num' => $num, 'order_id' => $id, 'user_id' => (int) ($ord['user_id'] ?? 0), 'package_id' => (int) ($ord['package_id'] ?? 0)]);
            $options[$num] = $id;
            $buttons[] = [$this->catalog->get('admin.ui.open.deliveries.list.button', ['num' => $num, 'order_id' => $id])];
        }
        $buttons[] = [UiLabels::back($this->catalog), UiLabels::main($this->catalog)];
        $this->database->setUserState($userId, 'admin.deliveries.list', ['options' => $options]);
        if ($notice) {
            $this->telegram->sendMessage($chatId, $notice);
        }
        $this->telegram->sendMessage(
            $chatId,
            $this->messageRenderer->render('admin.ui.open.deliveries.list.overview', [
                'list' => $lines !== [] ? implode("\n", $lines) : $this->catalog->get('admin.ui.open.deliveries.list.empty'),
            ], ['list']),
            $this->uiKeyboard->replyMenu($buttons)
        );
    }

    private function openAdminDeliveryView(int $chatId, int $userId, int $orderId, ?string $notice = null): void
    {
        $this->database->setUserState($userId, 'admin.delivery.view', ['order_id' => $orderId]);
        if ($notice) {
            $this->telegram->sendMessage($chatId, $notice);
        }
        $this->telegram->sendMessage(
            $chatId,
            $this->messageRenderer->render('admin.ui.open.deliveries.view.overview', ['order_id' => $orderId]),
            $this->uiKeyboard->replyMenu([[$this->uiConst(self::ADMIN_DELIVERY_DO)], [UiLabels::back($this->catalog), UiLabels::main($this->catalog)]])
        );
    }

    private function openAdminGroupOpsView(int $chatId, int $userId, ?string $notice = null): void
    {
        $this->database->setUserState($userId, 'admin.groupops.view', []);
        if ($notice) {
            $this->telegram->sendMessage($chatId, $notice);
        }
        $groupId = trim($this->settings->get('group_id', ''));
        $this->telegram->sendMessage(
            $chatId,
            $this->messageRenderer->render('admin.ui.open.groupops.overview', [
                'group_id' => $groupId !== '' ? "<code>{$groupId}</code>" : $this->catalog->get('admin.ui.open.groupops.group_id_unset'),
            ], ['group_id']),
            $this->uiKeyboard->replyMenu([[$this->uiConst(self::ADMIN_GROUPOPS_SET_GROUP), $this->uiConst(self::ADMIN_GROUPOPS_RESTORE)], [UiLabels::back($this->catalog), UiLabels::main($this->catalog)]])
        );
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
        $this->telegram->sendMessage(
            $chatId,
            $this->messageRenderer->render('admin.ui.freetest.overview', [
                'rules' => $lines !== [] ? implode("\n", $lines) : $this->catalog->get('admin.ui.freetest.rules_empty'),
            ], ['rules']),
            $this->uiKeyboard->replyMenu([[$this->uiConst(self::ADMIN_FREETEST_RULE), $this->uiConst(self::ADMIN_FREETEST_RESET)], [UiLabels::back($this->catalog), UiLabels::main($this->catalog)]])
        );
    }

    private function handleBuyTypeSelectionState(int $chatId, int $userId, string $text, array $state): void
    {
        if ($this->isMainMenuInput($text)) {
            $this->database->clearUserState($userId);
            $this->telegram->sendMessage($chatId, $this->menus->mainMenuText(), $this->menus->mainMenuReplyKeyboard($userId));
            return;
        }

        $options = is_array($state['payload']['options'] ?? null) ? $state['payload']['options'] : [];
        $selected = $this->extractOptionKey($text);
        $typeId = isset($options[$selected]) ? (int) $options[$selected] : 0;
        if ($typeId <= 0) {
            $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('messages.user.common.invalid_option')));
            return;
        }

        if ($this->database->countServicesWithTariffsByType($typeId) <= 0) {
            $this->telegram->sendMessage($chatId, $this->uiText->info($this->catalog->get('messages.user.buy.no_active_service')));
            return;
        }
        $this->showBuyServiceSelection($chatId, $userId, $typeId);
    }

    private function showBuyServiceSelection(int $chatId, int $userId, int $typeId): void
    {
        $services = $this->database->listActiveServicesByType($typeId);
        $lines = [];
        $optionMap = [];
        $buttons = [];
        foreach (array_values($services) as $idx => $service) {
            $serviceId = (int) ($service['id'] ?? 0);
            if ($serviceId <= 0 || $this->database->countTariffsByService($serviceId) <= 0) {
                continue;
            }
            $num = (string) ($idx + 1);
            $name = trim((string) ($service['name'] ?? $this->catalog->get('messages.user.buy.panel.default_service_title')));
            $lines[] = $this->catalog->get('messages.user.buy.service.service_row', [
                'num' => $num,
                'name' => htmlspecialchars($name),
                'mode' => (string) ($service['mode'] ?? 'stock'),
            ]);
            $optionMap[$num] = $serviceId;
            $buttons[] = [$this->catalog->get('messages.user.buy.service.service_button', ['num' => $num, 'name' => $name])];
        }
        if ($optionMap === []) {
            $this->telegram->sendMessage($chatId, $this->uiText->info($this->catalog->get('messages.user.buy.no_active_service')));
            return;
        }
        $buttons[] = [UiLabels::back($this->catalog), UiLabels::main($this->catalog)];
        $this->database->setUserState($userId, 'buy.service.await_service', [
            'type_id' => $typeId,
            'options' => $optionMap,
        ]);
        $this->telegram->sendMessage(
            $chatId,
            $this->messageRenderer->render('messages.user.buy.service.service_selection.overview', [
                'services' => implode("\n", $lines),
            ], ['services']),
            $this->uiKeyboard->replyMenu($buttons)
        );
    }

    private function showBuyPackageSelection(int $chatId, int $userId, int $typeId): void
    {
        $stockOnly = $this->settings->get('delivery_mode', 'stock_only') === 'stock_only';
        $packages = $this->database->getActivePackagesByTypeWithStock($typeId, $stockOnly);
        if ($packages === []) {
            $this->telegram->sendMessage($chatId, $this->uiText->info($this->catalog->get('messages.user.buy.no_package_for_type')));
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
            $stockText = isset($pkg['stock']) ? $this->catalog->get('messages.user.buy.stock_suffix', ['stock' => (int) $pkg['stock']]) : '';
            $label = $this->catalog->get('messages.user.buy.package_row', [
                'name' => (string) $pkg['name'],
                'volume_gb' => (string) $pkg['volume_gb'],
                'duration_days' => (string) $pkg['duration_days'],
                'price' => (string) $price,
                'stock_suffix' => $stockText,
            ]);
            $lines[] = "{$num}) " . htmlspecialchars($label);
            $optionMap[$num] = $pkgId;
            $buttons[] = [$num . ' - ' . (string) ($pkg['name'] ?? $this->catalog->get('messages.user.buy.default_package_name'))];
        }

        $buttons[] = [UiLabels::back($this->catalog), UiLabels::main($this->catalog)];
        $this->database->setUserState($userId, 'buy.await_package', ['options' => $optionMap, 'type_id' => $typeId, 'stack' => ['buy.await_type'], 'package_id' => null, 'payment_method' => null]);
        $this->telegram->sendMessage(
            $chatId,
            $this->messageRenderer->render('messages.user.buy.package_selection.overview', [
                'options' => implode("\n", $lines),
            ], ['options']),
            $this->uiKeyboard->replyMenu($buttons)
        );
    }

    private function openPanelServiceSelection(int $chatId, int $userId): void
    {
        $services = $this->database->listActiveProvisioningServices('pasarguard');
        if ($services === []) {
            $this->telegram->sendMessage($chatId, $this->uiText->info($this->catalog->get('messages.user.buy.no_active_service')));
            return;
        }

        $lines = [];
        $options = [];
        $buttons = [];
        foreach (array_values($services) as $idx => $service) {
            $num = (string) ($idx + 1);
            $serviceId = (int) ($service['id'] ?? 0);
            if ($serviceId <= 0) {
                continue;
            }
            $title = trim((string) ($service['title'] ?? $this->catalog->get('messages.user.buy.panel.default_service_title')));
            $lines[] = $this->catalog->get('messages.user.buy.panel.service_row', [
                'num' => $num,
                'title' => htmlspecialchars($title),
                'min_gb' => (string) ($service['min_gb'] ?? '0'),
                'max_gb' => (string) ($service['max_gb'] ?? '0'),
                'step_gb' => (string) ($service['step_gb'] ?? '1'),
                'price_per_gb' => (string) ((int) ($service['price_per_gb'] ?? 0)),
            ]);
            $options[$num] = $serviceId;
            $buttons[] = [$this->catalog->get('messages.user.buy.panel.service_button', ['num' => $num, 'title' => $title])];
        }
        if ($options === []) {
            $this->telegram->sendMessage($chatId, $this->uiText->info($this->catalog->get('messages.user.buy.no_active_service')));
            return;
        }

        $buttons[] = [UiLabels::main($this->catalog)];
        $this->database->setUserState($userId, 'buy.panel.await_service', ['options' => $options]);
        $this->telegram->sendMessage(
            $chatId,
            $this->messageRenderer->render('messages.user.buy.panel.service_selection.overview', [
                'services' => implode("\n", $lines),
            ], ['services']),
            $this->uiKeyboard->replyMenu($buttons)
        );
    }

    private function handlePanelServiceSelectionState(int $chatId, int $userId, string $text, array $state): void
    {
        if ($this->isMainMenuInput($text)) {
            $this->database->clearUserState($userId);
            $this->telegram->sendMessage($chatId, $this->menus->mainMenuText(), $this->menus->mainMenuReplyKeyboard($userId));
            return;
        }

        $options = is_array($state['payload']['options'] ?? null) ? $state['payload']['options'] : [];
        $selected = $this->extractOptionKey($text);
        $serviceId = isset($options[$selected]) ? (int) $options[$selected] : 0;
        if ($serviceId <= 0) {
            $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('messages.user.common.invalid_option')));
            return;
        }

        $service = $this->database->getProvisioningService($serviceId);
        if (!is_array($service)) {
            $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('messages.user.common.invalid_option')));
            return;
        }

        $this->database->setUserState($userId, 'buy.panel.await_volume', ['service_id' => $serviceId]);
        $this->telegram->sendMessage(
            $chatId,
            $this->messageRenderer->render('messages.user.buy.panel.volume_selection.overview', [
                'title' => (string) ($service['title'] ?? $this->catalog->get('messages.user.buy.panel.default_service_title')),
                'min_gb' => (string) ($service['min_gb'] ?? '0'),
                'max_gb' => (string) ($service['max_gb'] ?? '0'),
                'step_gb' => (string) ($service['step_gb'] ?? '1'),
                'price_per_gb' => (int) ($service['price_per_gb'] ?? 0),
            ]),
            $this->uiKeyboard->replyMenu([[UiLabels::back($this->catalog), UiLabels::main($this->catalog)]])
        );
    }

    private function handlePanelVolumeInputState(int $chatId, int $userId, string $text, array $state): void
    {
        if ($text === UiLabels::back($this->catalog)) {
            $this->openPanelServiceSelection($chatId, $userId);
            return;
        }
        if ($this->isMainMenuInput($text)) {
            $this->database->clearUserState($userId);
            $this->telegram->sendMessage($chatId, $this->menus->mainMenuText(), $this->menus->mainMenuReplyKeyboard($userId));
            return;
        }

        $serviceId = (int) ($state['payload']['service_id'] ?? 0);
        $service = $this->database->getProvisioningService($serviceId);
        if (!is_array($service)) {
            $this->database->clearUserState($userId);
            $this->openPanelServiceSelection($chatId, $userId);
            return;
        }

        $volume = (float) str_replace(',', '.', trim($text));
        if (!$this->database->validatePanelServiceVolume($service, $volume)) {
            $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('messages.user.buy.panel.errors.invalid_volume')));
            return;
        }

        $amount = $this->database->calculatePanelServiceAmount($service, $volume);
        if ($this->settings->get('purchase_rules_enabled', '0') === '1' && !$this->database->hasAcceptedPurchaseRules($userId)) {
            $rulesText = trim($this->settings->get('purchase_rules_text', ''));
            $rulesText = $rulesText !== '' ? $rulesText : $this->catalog->get('messages.user.buy.rules.default_text');
            $this->database->setUserState($userId, 'buy.await_rules_accept', [
                'order_mode' => 'panel_only',
                'service_id' => $serviceId,
                'selected_volume_gb' => $volume,
                'computed_amount' => $amount,
            ]);
            $this->telegram->sendMessage(
                $chatId,
                $this->messageRenderer->render('messages.user.buy.rules.overview', [
                    'rules_text' => $rulesText,
                ]),
                $this->uiKeyboard->replyMenu([[$this->catalog->get('buttons.accept_rules')], [UiLabels::back($this->catalog), UiLabels::main($this->catalog)]])
            );
            return;
        }

        $this->openPanelPaymentSelection($chatId, $userId, $service, $volume, $amount);
    }

    private function handleServiceSelectionState(int $chatId, int $userId, string $text, array $state): void
    {
        if ($text === UiLabels::back($this->catalog)) {
            $this->startBuyTypeReplyFlow($chatId, $userId);
            return;
        }
        if ($this->isMainMenuInput($text)) {
            $this->database->clearUserState($userId);
            $this->telegram->sendMessage($chatId, $this->menus->mainMenuText(), $this->menus->mainMenuReplyKeyboard($userId));
            return;
        }
        $options = is_array($state['payload']['options'] ?? null) ? $state['payload']['options'] : [];
        $selected = $this->extractOptionKey($text);
        $serviceId = isset($options[$selected]) ? (int) $options[$selected] : 0;
        $typeId = (int) ($state['payload']['type_id'] ?? 0);
        if ($serviceId <= 0 || $typeId <= 0) {
            $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('messages.user.common.invalid_option')));
            return;
        }
        $service = $this->database->getService($serviceId);
        if (!is_array($service) || (int) ($service['type_id'] ?? 0) !== $typeId || (int) ($service['is_active'] ?? 0) !== 1) {
            $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('messages.user.common.invalid_option')));
            return;
        }
        $this->showBuyServiceTariffSelection($chatId, $userId, $typeId, $serviceId);
    }

    private function showBuyServiceTariffSelection(int $chatId, int $userId, int $typeId, int $serviceId): void
    {
        $service = $this->database->getService($serviceId);
        if (!is_array($service) || (int) ($service['type_id'] ?? 0) !== $typeId) {
            $this->showBuyServiceSelection($chatId, $userId, $typeId);
            return;
        }
        $tariffs = $this->database->listActiveTariffsByService($serviceId);
        $lines = [];
        $optionMap = [];
        $buttons = [];
        foreach (array_values($tariffs) as $idx => $tariff) {
            $tariffId = (int) ($tariff['id'] ?? 0);
            if ($tariffId <= 0) {
                continue;
            }
            $num = (string) ($idx + 1);
            $summary = (string) ($tariff['pricing_mode'] ?? 'fixed') === 'fixed'
                ? $this->catalog->get('messages.user.buy.service.tariff_fixed_summary', [
                    'volume_gb' => (string) ($tariff['volume_gb'] ?? '0'),
                    'duration_days' => (string) ($tariff['duration_days'] ?? '0'),
                    'price' => (string) ($tariff['price'] ?? '0'),
                ])
                : $this->catalog->get('messages.user.buy.service.tariff_per_gb_summary', [
                    'min_volume_gb' => (string) ($tariff['min_volume_gb'] ?? '0'),
                    'max_volume_gb' => (string) ($tariff['max_volume_gb'] ?? '0'),
                    'step_volume_gb' => (string) ($tariff['step_volume_gb'] ?? '0'),
                    'price_per_gb' => (string) ($tariff['price_per_gb'] ?? '0'),
                ]);
            $lines[] = $this->catalog->get('messages.user.buy.service.tariff_row', [
                'num' => $num,
                'title' => htmlspecialchars((string) ($tariff['title'] ?? $this->catalog->get('messages.generic.dash'))),
                'summary' => $summary,
            ]);
            $optionMap[$num] = $tariffId;
            $buttons[] = [$this->catalog->get('messages.user.buy.service.tariff_button', ['num' => $num, 'title' => (string) ($tariff['title'] ?? $this->catalog->get('messages.generic.dash'))])];
        }
        if ($optionMap === []) {
            $this->showBuyServiceSelection($chatId, $userId, $typeId);
            return;
        }
        $buttons[] = [UiLabels::back($this->catalog), UiLabels::main($this->catalog)];
        $this->database->setUserState($userId, 'buy.service.await_tariff', [
            'type_id' => $typeId,
            'service_id' => $serviceId,
            'options' => $optionMap,
        ]);
        $this->telegram->sendMessage(
            $chatId,
            $this->messageRenderer->render('messages.user.buy.service.tariff_selection.overview', [
                'service_name' => (string) ($service['name'] ?? $this->catalog->get('messages.user.buy.panel.default_service_title')),
                'tariffs' => implode("\n", $lines),
            ], ['tariffs']),
            $this->uiKeyboard->replyMenu($buttons)
        );
    }

    private function handleServiceTariffSelectionState(int $chatId, int $userId, string $text, array $state): void
    {
        $typeId = (int) ($state['payload']['type_id'] ?? 0);
        $serviceId = (int) ($state['payload']['service_id'] ?? 0);
        if ($text === UiLabels::back($this->catalog)) {
            $this->showBuyServiceSelection($chatId, $userId, $typeId);
            return;
        }
        if ($this->isMainMenuInput($text)) {
            $this->database->clearUserState($userId);
            $this->telegram->sendMessage($chatId, $this->menus->mainMenuText(), $this->menus->mainMenuReplyKeyboard($userId));
            return;
        }
        $options = is_array($state['payload']['options'] ?? null) ? $state['payload']['options'] : [];
        $selected = $this->extractOptionKey($text);
        $tariffId = isset($options[$selected]) ? (int) $options[$selected] : 0;
        if ($tariffId <= 0) {
            $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('messages.user.common.invalid_option')));
            return;
        }
        $tariff = $this->database->getServiceTariffForService($serviceId, $tariffId);
        if (!is_array($tariff)) {
            $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('messages.user.common.invalid_option')));
            return;
        }
        if ((string) ($tariff['pricing_mode'] ?? 'fixed') === 'per_gb') {
            $this->database->setUserState($userId, 'buy.service.await_volume', [
                'type_id' => $typeId,
                'service_id' => $serviceId,
                'tariff_id' => $tariffId,
            ]);
            $this->telegram->sendMessage(
                $chatId,
                $this->messageRenderer->render('messages.user.buy.service.volume_selection.overview', [
                    'min_volume_gb' => (string) ($tariff['min_volume_gb'] ?? '0'),
                    'max_volume_gb' => (string) ($tariff['max_volume_gb'] ?? '0'),
                    'step_volume_gb' => (string) ($tariff['step_volume_gb'] ?? '0'),
                    'price_per_gb' => (string) ($tariff['price_per_gb'] ?? '0'),
                ]),
                $this->uiKeyboard->replyMenu([[UiLabels::back($this->catalog), UiLabels::main($this->catalog)]])
            );
            return;
        }
        $this->openServicePaymentSelection($chatId, $userId, $typeId, $serviceId, $tariffId, null);
    }

    private function handleServiceTariffVolumeInputState(int $chatId, int $userId, string $text, array $state): void
    {
        $typeId = (int) ($state['payload']['type_id'] ?? 0);
        $serviceId = (int) ($state['payload']['service_id'] ?? 0);
        $tariffId = (int) ($state['payload']['tariff_id'] ?? 0);
        if ($text === UiLabels::back($this->catalog)) {
            $this->showBuyServiceTariffSelection($chatId, $userId, $typeId, $serviceId);
            return;
        }
        if ($this->isMainMenuInput($text)) {
            $this->database->clearUserState($userId);
            $this->telegram->sendMessage($chatId, $this->menus->mainMenuText(), $this->menus->mainMenuReplyKeyboard($userId));
            return;
        }
        $volume = (float) str_replace(',', '.', trim($text));
        if ($volume <= 0) {
            $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('messages.user.buy.panel.errors.invalid_volume')));
            return;
        }
        $this->openServicePaymentSelection($chatId, $userId, $typeId, $serviceId, $tariffId, $volume);
    }

    private function handleBuyPackageSelectionState(int $chatId, int $userId, string $text, array $state): void
    {
        if ($text === UiLabels::back($this->catalog)) {
            $this->startBuyTypeReplyFlow($chatId, $userId);
            return;
        }
        if ($this->isMainMenuInput($text)) {
            $this->database->clearUserState($userId);
            $this->telegram->sendMessage($chatId, $this->menus->mainMenuText(), $this->menus->mainMenuReplyKeyboard($userId));
            return;
        }

        $options = is_array($state['payload']['options'] ?? null) ? $state['payload']['options'] : [];
        $selected = $this->extractOptionKey($text);
        $packageId = isset($options[$selected]) ? (int) $options[$selected] : 0;
        if ($packageId <= 0) {
            $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('messages.user.common.invalid_option')));
            return;
        }

        if ($this->settings->get('purchase_rules_enabled', '0') === '1' && !$this->database->hasAcceptedPurchaseRules($userId)) {
            $rulesText = trim($this->settings->get('purchase_rules_text', ''));
            $rulesText = $rulesText !== '' ? $rulesText : $this->catalog->get('messages.user.buy.rules.default_text');
            $this->database->setUserState($userId, 'buy.await_rules_accept', ['package_id' => $packageId, 'type_id' => (int) ($state['payload']['type_id'] ?? 0), 'stack' => ['buy.await_type', 'buy.await_package'], 'payment_method' => null]);
            $this->telegram->sendMessage(
                $chatId,
                $this->messageRenderer->render('messages.user.buy.rules.overview', [
                    'rules_text' => $rulesText,
                ]),
                $this->uiKeyboard->replyMenu([[$this->catalog->get('buttons.accept_rules')], [UiLabels::back($this->catalog), UiLabels::main($this->catalog)]])
            );
            return;
        }

        $this->database->clearUserState($userId);
        $package = $this->database->getPackage($packageId);
        if ($package === null) {
            $this->telegram->sendMessage($chatId, $this->uiText->error($this->catalog->get('messages.user.buy.package_not_found')));
            return;
        }
        $textOut = $this->messageRenderer->render('messages.user.buy.payment.overview', [
            'package_name' => (string) $package['name'],
            'amount' => (int) $this->database->effectivePackagePrice($userId, $package),
        ]);
        $buttons = [[$this->catalog->get('buttons.pay.wallet')]];
        if ($this->settings->get('gw_card_enabled', '0') === '1') {
            $buttons[] = [$this->catalog->get('buttons.pay.card')];
        }
        if ($this->settings->get('gw_crypto_enabled', '0') === '1') {
            $buttons[] = [$this->catalog->get('buttons.pay.crypto')];
        }
        if ($this->settings->get('gw_tetrapay_enabled', '0') === '1') {
            $buttons[] = [$this->catalog->get('buttons.pay.tetrapay')];
        }
        if ($this->settings->get('gw_swapwallet_crypto_enabled', '0') === '1') {
            $buttons[] = [$this->catalog->get('buttons.pay.swapwallet')];
        }
        if ($this->settings->get('gw_tronpays_rial_enabled', '0') === '1') {
            $buttons[] = [$this->catalog->get('buttons.pay.tronpays')];
        }
        $buttons[] = [UiLabels::back($this->catalog), UiLabels::main($this->catalog)];
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
            $service = trim((string) ($item['service_name'] ?? $this->catalog->get('messages.generic.dash')));
            $lines[] = $this->catalog->get('messages.user.renew.order_option', ['num' => $num, 'purchase_id' => $purchaseId, 'service_name' => htmlspecialchars($service)]);
            $optionMap[$num] = $purchaseId;
            $buttons[] = [$this->catalog->get('messages.user.renew.option_button', ['num' => $num])];
        }

        if ($optionMap === []) {
            return;
        }

        $buttons[] = [UiLabels::main($this->catalog)];
        $this->database->setUserState($userId, 'renew.await_purchase', ['options' => $optionMap, 'stack' => [], 'purchase_id' => null, 'package_id' => null, 'payment_method' => null]);
        $this->telegram->sendMessage(
            $chatId,
            $this->messageRenderer->render('messages.user.renew.select_order.overview', [
                'orders' => implode("\n", $lines),
            ], ['orders']),
            $this->uiKeyboard->replyMenu($buttons)
        );
    }

    private function handleRenewPurchaseSelectionState(int $chatId, int $userId, string $text, array $state): void
    {
        if ($this->isMainMenuInput($text)) {
            $this->database->clearUserState($userId);
            $this->telegram->sendMessage($chatId, $this->menus->mainMenuText(), $this->menus->mainMenuReplyKeyboard($userId));
            return;
        }

        $options = is_array($state['payload']['options'] ?? null) ? $state['payload']['options'] : [];
        $selected = $this->extractOptionKey($text);
        $purchaseId = isset($options[$selected]) ? (int) $options[$selected] : 0;
        if ($purchaseId <= 0) {
            $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('messages.user.common.invalid_option')));
            return;
        }

        $purchase = $this->database->getUserPurchaseForRenewal($userId, $purchaseId);
        if (!is_array($purchase)) {
            $this->telegram->sendMessage($chatId, $this->uiText->error($this->catalog->get('messages.user.renew.order_not_found')));
            return;
        }
        if ((int) ($purchase['is_test'] ?? 0) === 1) {
            $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('messages.user.renew.test_not_renewable')));
            return;
        }

        $typeId = (int) ($purchase['type_id'] ?? 0);
        $packages = $this->database->getActivePackagesByType($typeId);
        if ($packages === []) {
            $this->telegram->sendMessage($chatId, $this->uiText->info($this->catalog->get('messages.user.renew.no_package')));
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
            $label = $this->catalog->get('messages.user.renew.package_row', [
                'name' => (string) $pkg['name'],
                'volume_gb' => (string) $pkg['volume_gb'],
                'duration_days' => (string) $pkg['duration_days'],
                'price' => (string) $pkg['price'],
            ]);
            $lines[] = "{$num}) " . htmlspecialchars($label);
            $optionMap[$num] = $pkgId;
            $buttons[] = [$this->catalog->get('messages.user.renew.package_button', ['num' => $num])];
        }

        $buttons[] = [UiLabels::back($this->catalog), UiLabels::main($this->catalog)];
        $this->database->setUserState($userId, 'renew.await_package', ['options' => $optionMap, 'purchase_id' => $purchaseId, 'stack' => ['renew.await_purchase'], 'package_id' => null, 'payment_method' => null]);
        $this->telegram->sendMessage(
            $chatId,
            $this->messageRenderer->render('messages.user.renew.select_package.overview', [
                'purchase_id' => $purchaseId,
                'service_name' => (string) ($purchase['service_name'] ?? $this->catalog->get('messages.generic.dash')),
                'package_name' => (string) ($purchase['package_name'] ?? $this->catalog->get('messages.generic.dash')),
                'options' => implode("\n", $lines),
            ], ['options']),
            $this->uiKeyboard->replyMenu($buttons)
        );
    }

    private function handleRenewPackageSelectionState(int $chatId, int $userId, string $text, array $state): void
    {
        if ($text === UiLabels::back($this->catalog)) {
            $this->showMyConfigsWithReplyFlow($chatId, $userId);
            return;
        }
        if ($this->isMainMenuInput($text)) {
            $this->database->clearUserState($userId);
            $this->telegram->sendMessage($chatId, $this->menus->mainMenuText(), $this->menus->mainMenuReplyKeyboard($userId));
            return;
        }

        $options = is_array($state['payload']['options'] ?? null) ? $state['payload']['options'] : [];
        $selected = $this->extractOptionKey($text);
        $packageId = isset($options[$selected]) ? (int) $options[$selected] : 0;
        $purchaseId = (int) ($state['payload']['purchase_id'] ?? 0);
        if ($packageId <= 0 || $purchaseId <= 0) {
            $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('messages.user.common.invalid_option')));
            return;
        }

        $this->database->clearUserState($userId);
        $package = $this->database->getPackage($packageId);
        $purchase = $this->database->getUserPurchaseForRenewal($userId, $purchaseId);
        if ($package === null || !is_array($purchase)) {
            $this->telegram->sendMessage($chatId, $this->uiText->error($this->catalog->get('messages.user.renew.invalid_data')));
            return;
        }

        $textOut = $this->messageRenderer->render('messages.user.renew.payment.overview', [
            'purchase_id' => $purchaseId,
            'package_name' => (string) $package['name'],
            'amount' => (int) $package['price'],
        ]);
        $buttons = [[$this->catalog->get('buttons.pay.wallet')]];
        if ($this->settings->get('gw_card_enabled', '0') === '1') {
            $buttons[] = [$this->catalog->get('buttons.pay.card')];
        }
        if ($this->settings->get('gw_crypto_enabled', '0') === '1') {
            $buttons[] = [$this->catalog->get('buttons.pay.crypto')];
        }
        if ($this->settings->get('gw_tetrapay_enabled', '0') === '1') {
            $buttons[] = [$this->catalog->get('buttons.pay.tetrapay')];
        }
        if ($this->settings->get('gw_swapwallet_crypto_enabled', '0') === '1') {
            $buttons[] = [$this->catalog->get('buttons.pay.swapwallet')];
        }
        if ($this->settings->get('gw_tronpays_rial_enabled', '0') === '1') {
            $buttons[] = [$this->catalog->get('buttons.pay.tronpays')];
        }
        $buttons[] = [UiLabels::back($this->catalog), UiLabels::main($this->catalog)];
        $this->database->setUserState($userId, 'renew.await_payment_method', ['purchase_id' => $purchaseId, 'package_id' => $packageId, 'stack' => ['renew.await_purchase', 'renew.await_package'], 'payment_method' => null, 'gateway' => null]);
        $this->telegram->sendMessage($chatId, $textOut, $this->uiKeyboard->replyMenu($buttons));
    }

    private function handleBuyPaymentSelectionState(int $chatId, int $userId, string $text, array $state): void
    {
        if ($text === UiLabels::back($this->catalog)) {
            $typeId = (int) ($state['payload']['type_id'] ?? 0);
            if ($typeId > 0) {
                $this->showBuyPackageSelection($chatId, $userId, $typeId);
                return;
            }
            $this->database->clearUserState($userId);
            $this->telegram->sendMessage($chatId, $this->menus->mainMenuText(), $this->menus->mainMenuReplyKeyboard($userId));
            return;
        }
        if ($this->isMainMenuInput($text)) {
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

        if ($text === $this->catalog->get('buttons.pay.wallet') || $text === $this->uiConst(self::PAY_WALLET)) {
            $this->database->clearUserState($userId);
            $result = $this->database->walletPayPackage($userId, $packageId);
            if (!($result['ok'] ?? false)) {
                $msg = match ($result['error'] ?? '') {
                    'insufficient_balance' => $this->catalog->get('messages.user.payment.errors.insufficient_balance'),
                    'no_stock' => $this->catalog->get('messages.user.payment.errors.no_stock'),
                    default => $this->catalog->get('messages.user.payment.errors.create_order_failed'),
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
                $this->catalog->get('messages.user.payment.wallet_purchase_success', [
                    'payment_id' => (int) $result['payment_id'],
                    'amount' => (int) $result['price'],
                    'new_balance' => (int) $result['new_balance'],
                ])
            );
            return;
        }

        if ($text === $this->catalog->get('buttons.pay.card') || $text === $this->uiConst(self::PAY_CARD) || $text === $this->catalog->get('buttons.pay.crypto') || $text === $this->uiConst(self::PAY_CRYPTO) || $text === $this->catalog->get('buttons.pay.tetrapay') || $text === $this->uiConst(self::PAY_TETRAPAY)) {
            $this->database->clearUserState($userId);
            $this->createPurchasePaymentByMethod($chatId, $userId, $packageId, $text);
            return;
        }

        if ($text === $this->catalog->get('buttons.pay.swapwallet') || $text === $this->uiConst(self::PAY_SWAPWALLET) || $text === $this->catalog->get('buttons.pay.tronpays') || $text === $this->uiConst(self::PAY_TRONPAYS)) {
            $this->database->clearUserState($userId);
            $this->createPurchaseGatewayInvoice($chatId, $userId, $packageId, $text);
            return;
        }

        if ($text !== $this->catalog->get('buttons.pay.wallet') && $text !== $this->uiConst(self::PAY_WALLET) && $text !== $this->catalog->get('buttons.pay.card') && $text !== $this->uiConst(self::PAY_CARD) && $text !== $this->catalog->get('buttons.pay.crypto') && $text !== $this->uiConst(self::PAY_CRYPTO) && $text !== $this->catalog->get('buttons.pay.tetrapay') && $text !== $this->uiConst(self::PAY_TETRAPAY) && $text !== $this->catalog->get('buttons.pay.swapwallet') && $text !== $this->uiConst(self::PAY_SWAPWALLET) && $text !== $this->catalog->get('buttons.pay.tronpays') && $text !== $this->uiConst(self::PAY_TRONPAYS)) {
            $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('messages.user.payment.errors.select_method')));
            return;
        }
    }

    private function openServicePaymentSelection(int $chatId, int $userId, int $typeId, int $serviceId, int $tariffId, ?float $selectedVolumeGb): void
    {
        $service = $this->database->getService($serviceId);
        $tariff = $this->database->getServiceTariffForService($serviceId, $tariffId);
        if (!is_array($service) || !is_array($tariff) || (int) ($service['type_id'] ?? 0) !== $typeId) {
            $this->showBuyServiceSelection($chatId, $userId, $typeId);
            return;
        }
        $serviceMode = (string) ($service['mode'] ?? 'stock');
        if (!in_array($serviceMode, ['stock', 'panel_auto'], true)) {
            $this->telegram->sendMessage($chatId, $this->uiText->error($this->catalog->get('messages.user.payment.errors.create_order_failed')));
            return;
        }
        if (
            $serviceMode === 'panel_auto'
            && (
                trim((string) ($service['panel_base_url'] ?? '')) === ''
                || trim((string) ($service['panel_username'] ?? '')) === ''
                || trim((string) ($service['panel_password'] ?? '')) === ''
            )
        ) {
            $this->telegram->sendMessage($chatId, $this->uiText->error($this->catalog->get('messages.user.payment.errors.create_order_failed')));
            return;
        }
        if ($serviceMode === 'stock' && $this->database->countAvailableConfigsByService($serviceId, $tariffId) <= 0) {
            $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('messages.user.payment.errors.no_stock')));
            return;
        }
        $amount = $this->database->calculateServiceTariffAmount($tariff, $selectedVolumeGb);
        if ($amount <= 0) {
            $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('messages.user.buy.panel.errors.invalid_volume')));
            return;
        }
        if ($this->settings->get('purchase_rules_enabled', '0') === '1' && !$this->database->hasAcceptedPurchaseRules($userId)) {
            $rulesText = trim($this->settings->get('purchase_rules_text', ''));
            $rulesText = $rulesText !== '' ? $rulesText : $this->catalog->get('messages.user.buy.rules.default_text');
            $this->database->setUserState($userId, 'buy.await_rules_accept', [
                'type_id' => $typeId,
                'service_id' => $serviceId,
                'tariff_id' => $tariffId,
                'selected_volume_gb' => $selectedVolumeGb,
                'computed_amount' => $amount,
                'service_flow' => 1,
            ]);
            $this->telegram->sendMessage(
                $chatId,
                $this->messageRenderer->render('messages.user.buy.rules.overview', [
                    'rules_text' => $rulesText,
                ]),
                $this->uiKeyboard->replyMenu([[$this->catalog->get('buttons.accept_rules')], [UiLabels::back($this->catalog), UiLabels::main($this->catalog)]])
            );
            return;
        }

        $tariffTitle = (string) ($tariff['title'] ?? $this->catalog->get('messages.generic.dash'));
        $label = (string) ($service['name'] ?? $this->catalog->get('messages.user.buy.panel.default_service_title')) . ' / ' . $tariffTitle;
        $textOut = $this->messageRenderer->render('messages.user.buy.payment.overview', [
            'package_name' => $label,
            'amount' => $amount,
        ]);
        $buttons = [[$this->catalog->get('buttons.pay.wallet')]];
        if ($this->settings->get('gw_card_enabled', '0') === '1') {
            $buttons[] = [$this->catalog->get('buttons.pay.card')];
        }
        if ($this->settings->get('gw_crypto_enabled', '0') === '1') {
            $buttons[] = [$this->catalog->get('buttons.pay.crypto')];
        }
        if ($this->settings->get('gw_tetrapay_enabled', '0') === '1') {
            $buttons[] = [$this->catalog->get('buttons.pay.tetrapay')];
        }
        if ($this->settings->get('gw_swapwallet_crypto_enabled', '0') === '1') {
            $buttons[] = [$this->catalog->get('buttons.pay.swapwallet')];
        }
        if ($this->settings->get('gw_tronpays_rial_enabled', '0') === '1') {
            $buttons[] = [$this->catalog->get('buttons.pay.tronpays')];
        }
        $buttons[] = [UiLabels::back($this->catalog), UiLabels::main($this->catalog)];
        $this->database->setUserState($userId, 'buy.service.await_payment_method', [
            'type_id' => $typeId,
            'service_id' => $serviceId,
            'tariff_id' => $tariffId,
            'selected_volume_gb' => $selectedVolumeGb,
            'computed_amount' => $amount,
        ]);
        $this->telegram->sendMessage($chatId, $textOut, $this->uiKeyboard->replyMenu($buttons));
    }

    private function handleServiceBuyPaymentSelectionState(int $chatId, int $userId, string $text, array $state): void
    {
        $typeId = (int) ($state['payload']['type_id'] ?? 0);
        $serviceId = (int) ($state['payload']['service_id'] ?? 0);
        $tariffId = (int) ($state['payload']['tariff_id'] ?? 0);
        $selectedVolumeGb = isset($state['payload']['selected_volume_gb']) ? (float) $state['payload']['selected_volume_gb'] : null;
        if ($text === UiLabels::back($this->catalog)) {
            $this->showBuyServiceTariffSelection($chatId, $userId, $typeId, $serviceId);
            return;
        }
        if ($this->isMainMenuInput($text)) {
            $this->database->clearUserState($userId);
            $this->telegram->sendMessage($chatId, $this->menus->mainMenuText(), $this->menus->mainMenuReplyKeyboard($userId));
            return;
        }
        if ($serviceId <= 0 || $tariffId <= 0) {
            $this->database->clearUserState($userId);
            return;
        }
        if ($text === $this->catalog->get('buttons.pay.wallet') || $text === $this->uiConst(self::PAY_WALLET)) {
            $this->database->clearUserState($userId);
            $result = $this->database->walletPayServiceTariff($userId, $serviceId, $tariffId, $selectedVolumeGb);
            if (!($result['ok'] ?? false)) {
                $msg = match ($result['error'] ?? '') {
                    'insufficient_balance' => $this->catalog->get('messages.user.payment.errors.insufficient_balance'),
                    'no_stock' => $this->catalog->get('messages.user.payment.errors.no_stock'),
                    'invalid_volume' => $this->catalog->get('messages.user.buy.panel.errors.invalid_volume'),
                    default => $this->catalog->get('messages.user.payment.errors.create_order_failed'),
                };
                $this->telegram->sendMessage($chatId, $this->uiText->error($msg));
                return;
            }
            $this->database->setUserState($userId, 'buy.done', [
                'type_id' => $typeId,
                'service_id' => $serviceId,
                'tariff_id' => $tariffId,
                'payment_method' => 'wallet',
                'gateway' => null,
            ]);
            $this->telegram->sendMessage(
                $chatId,
                $this->catalog->get('messages.user.payment.wallet_purchase_success', [
                    'payment_id' => (int) $result['payment_id'],
                    'amount' => (int) ($result['amount'] ?? 0),
                    'new_balance' => (int) ($result['new_balance'] ?? 0),
                ])
            );
            return;
        }
        if ($text === $this->catalog->get('buttons.pay.card') || $text === $this->uiConst(self::PAY_CARD) || $text === $this->catalog->get('buttons.pay.crypto') || $text === $this->uiConst(self::PAY_CRYPTO) || $text === $this->catalog->get('buttons.pay.tetrapay') || $text === $this->uiConst(self::PAY_TETRAPAY)) {
            $this->database->clearUserState($userId);
            $this->createServicePurchasePaymentByMethod($chatId, $userId, $serviceId, $tariffId, $selectedVolumeGb, $text);
            return;
        }
        if ($text === $this->catalog->get('buttons.pay.swapwallet') || $text === $this->uiConst(self::PAY_SWAPWALLET) || $text === $this->catalog->get('buttons.pay.tronpays') || $text === $this->uiConst(self::PAY_TRONPAYS)) {
            $this->database->clearUserState($userId);
            $this->createServicePurchaseGatewayInvoice($chatId, $userId, $serviceId, $tariffId, $selectedVolumeGb, $text);
            return;
        }
        $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('messages.user.payment.errors.select_method')));
    }

    private function openPanelPaymentSelection(int $chatId, int $userId, array $service, float $volume, int $amount): void
    {
        $this->database->clearUserState($userId);
        $textOut = $this->messageRenderer->render('messages.user.buy.panel.payment.overview', [
            'service_title' => (string) ($service['title'] ?? $this->catalog->get('messages.user.buy.panel.default_service_title')),
            'volume' => (string) $volume,
            'amount' => $amount,
        ]);
        $buttons = [[$this->catalog->get('buttons.pay.wallet')]];
        if ($this->settings->get('gw_card_enabled', '0') === '1') {
            $buttons[] = [$this->catalog->get('buttons.pay.card')];
        }
        if ($this->settings->get('gw_crypto_enabled', '0') === '1') {
            $buttons[] = [$this->catalog->get('buttons.pay.crypto')];
        }
        if ($this->settings->get('gw_tetrapay_enabled', '0') === '1') {
            $buttons[] = [$this->catalog->get('buttons.pay.tetrapay')];
        }
        if ($this->settings->get('gw_swapwallet_crypto_enabled', '0') === '1') {
            $buttons[] = [$this->catalog->get('buttons.pay.swapwallet')];
        }
        if ($this->settings->get('gw_tronpays_rial_enabled', '0') === '1') {
            $buttons[] = [$this->catalog->get('buttons.pay.tronpays')];
        }
        $buttons[] = [UiLabels::back($this->catalog), UiLabels::main($this->catalog)];
        $this->database->setUserState($userId, 'buy.panel.await_payment_method', [
            'service_id' => (int) ($service['id'] ?? 0),
            'selected_volume_gb' => $volume,
            'computed_amount' => $amount,
        ]);
        $this->telegram->sendMessage($chatId, $textOut, $this->uiKeyboard->replyMenu($buttons));
    }

    private function handlePanelBuyPaymentSelectionState(int $chatId, int $userId, string $text, array $state): void
    {
        if ($text === UiLabels::back($this->catalog)) {
            $serviceId = (int) ($state['payload']['service_id'] ?? 0);
            $service = $this->database->getProvisioningService($serviceId);
            if (!is_array($service)) {
                $this->openPanelServiceSelection($chatId, $userId);
                return;
            }
            $this->database->setUserState($userId, 'buy.panel.await_volume', ['service_id' => $serviceId]);
            $this->telegram->sendMessage(
                $chatId,
                $this->messageRenderer->render('messages.user.buy.panel.volume_selection.overview', [
                    'title' => (string) ($service['title'] ?? $this->catalog->get('messages.user.buy.panel.default_service_title')),
                    'min_gb' => (string) ($service['min_gb'] ?? '0'),
                    'max_gb' => (string) ($service['max_gb'] ?? '0'),
                    'step_gb' => (string) ($service['step_gb'] ?? '1'),
                    'price_per_gb' => (int) ($service['price_per_gb'] ?? 0),
                ]),
                $this->uiKeyboard->replyMenu([[UiLabels::back($this->catalog), UiLabels::main($this->catalog)]])
            );
            return;
        }
        if ($this->isMainMenuInput($text)) {
            $this->database->clearUserState($userId);
            $this->telegram->sendMessage($chatId, $this->menus->mainMenuText(), $this->menus->mainMenuReplyKeyboard($userId));
            return;
        }

        $serviceId = (int) ($state['payload']['service_id'] ?? 0);
        $selectedVolumeGb = (float) ($state['payload']['selected_volume_gb'] ?? 0);
        $computedAmount = (int) ($state['payload']['computed_amount'] ?? 0);
        if ($serviceId <= 0 || $selectedVolumeGb <= 0 || $computedAmount <= 0) {
            $this->database->clearUserState($userId);
            $this->openPanelServiceSelection($chatId, $userId);
            return;
        }

        if ($text === $this->catalog->get('buttons.pay.wallet') || $text === $this->uiConst(self::PAY_WALLET)) {
            $this->database->clearUserState($userId);
            $result = $this->database->walletPayPanelService($userId, $serviceId, $selectedVolumeGb);
            if (!($result['ok'] ?? false)) {
                $msg = match ($result['error'] ?? '') {
                    'insufficient_balance' => $this->catalog->get('messages.user.payment.errors.insufficient_balance'),
                    'invalid_volume' => $this->catalog->get('messages.user.buy.panel.errors.invalid_volume'),
                    default => $this->catalog->get('messages.user.payment.errors.create_order_failed'),
                };
                $this->telegram->sendMessage($chatId, $this->uiText->error($msg));
                return;
            }
            $this->database->setUserState($userId, 'buy.done', [
                'service_id' => $serviceId,
                'selected_volume_gb' => $selectedVolumeGb,
                'payment_method' => 'wallet',
                'gateway' => null,
            ]);
            $this->telegram->sendMessage(
                $chatId,
                $this->catalog->get('messages.user.payment.wallet_purchase_success', [
                    'payment_id' => (int) $result['payment_id'],
                    'amount' => (int) ($result['amount'] ?? $computedAmount),
                    'new_balance' => (int) $result['new_balance'],
                ])
            );
            return;
        }

        if ($text === $this->catalog->get('buttons.pay.card') || $text === $this->uiConst(self::PAY_CARD) || $text === $this->catalog->get('buttons.pay.crypto') || $text === $this->uiConst(self::PAY_CRYPTO) || $text === $this->catalog->get('buttons.pay.tetrapay') || $text === $this->uiConst(self::PAY_TETRAPAY)) {
            $this->database->clearUserState($userId);
            $this->createPanelPurchasePaymentByMethod($chatId, $userId, $serviceId, $selectedVolumeGb, $computedAmount, $text);
            return;
        }

        if ($text === $this->catalog->get('buttons.pay.swapwallet') || $text === $this->uiConst(self::PAY_SWAPWALLET) || $text === $this->catalog->get('buttons.pay.tronpays') || $text === $this->uiConst(self::PAY_TRONPAYS)) {
            $this->database->clearUserState($userId);
            $this->createPanelPurchaseGatewayInvoice($chatId, $userId, $serviceId, $selectedVolumeGb, $computedAmount, $text);
            return;
        }

        $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('messages.user.payment.errors.select_method')));
    }

    private function handleRenewPaymentSelectionState(int $chatId, int $userId, string $text, array $state): void
    {
        if ($text === UiLabels::back($this->catalog)) {
            $purchaseId = (int) ($state['payload']['purchase_id'] ?? 0);
            if ($purchaseId > 0) {
                $this->showMyConfigsWithReplyFlow($chatId, $userId);
                return;
            }
            $this->database->clearUserState($userId);
            $this->telegram->sendMessage($chatId, $this->menus->mainMenuText(), $this->menus->mainMenuReplyKeyboard($userId));
            return;
        }
        if ($this->isMainMenuInput($text)) {
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
        if ($text === $this->catalog->get('buttons.pay.wallet') || $text === $this->uiConst(self::PAY_WALLET)) {
            $this->database->clearUserState($userId);
            $result = $this->database->walletPayRenewal($userId, $purchaseId, $packageId);
            if (!($result['ok'] ?? false)) {
                $msg = match ($result['error'] ?? '') {
                    'insufficient_balance' => $this->catalog->get('messages.user.payment.errors.insufficient_balance'),
                    'purchase_not_found' => $this->catalog->get('messages.user.payment.errors.purchase_not_found'),
                    'test_not_renewable' => $this->catalog->get('messages.user.payment.errors.test_not_renewable'),
                    'type_mismatch' => $this->catalog->get('messages.user.payment.errors.type_mismatch'),
                    default => $this->catalog->get('messages.user.payment.errors.renewal_failed'),
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
                $this->catalog->get('messages.user.payment.wallet_renew_success', [
                    'pending_order_id' => (int) ($result['pending_order_id'] ?? 0),
                ])
            );
            return;
        }

        if ($text === $this->catalog->get('buttons.pay.card') || $text === $this->uiConst(self::PAY_CARD) || $text === $this->catalog->get('buttons.pay.crypto') || $text === $this->uiConst(self::PAY_CRYPTO) || $text === $this->catalog->get('buttons.pay.tetrapay') || $text === $this->uiConst(self::PAY_TETRAPAY)) {
            $this->database->clearUserState($userId);
            $this->createRenewalPaymentByMethod($chatId, $userId, $purchaseId, $packageId, $text);
            return;
        }

        if ($text === $this->catalog->get('buttons.pay.swapwallet') || $text === $this->uiConst(self::PAY_SWAPWALLET) || $text === $this->catalog->get('buttons.pay.tronpays') || $text === $this->uiConst(self::PAY_TRONPAYS)) {
            $this->database->clearUserState($userId);
            $this->createRenewalGatewayInvoice($chatId, $userId, $purchaseId, $packageId, $text);
            return;
        }

        if ($text !== $this->catalog->get('buttons.pay.wallet') && $text !== $this->uiConst(self::PAY_WALLET) && $text !== $this->catalog->get('buttons.pay.card') && $text !== $this->uiConst(self::PAY_CARD) && $text !== $this->catalog->get('buttons.pay.crypto') && $text !== $this->uiConst(self::PAY_CRYPTO) && $text !== $this->catalog->get('buttons.pay.tetrapay') && $text !== $this->uiConst(self::PAY_TETRAPAY) && $text !== $this->catalog->get('buttons.pay.swapwallet') && $text !== $this->uiConst(self::PAY_SWAPWALLET) && $text !== $this->catalog->get('buttons.pay.tronpays') && $text !== $this->uiConst(self::PAY_TRONPAYS)) {
            $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('messages.user.payment.errors.select_method')));
            return;
        }
    }

    private function createPurchasePaymentByMethod(int $chatId, int $userId, int $packageId, string $methodLabel): void
    {
        $method = ($methodLabel === $this->catalog->get('buttons.pay.card') || $methodLabel === $this->uiConst(self::PAY_CARD)) ? 'card' : (($methodLabel === $this->catalog->get('buttons.pay.crypto') || $methodLabel === $this->uiConst(self::PAY_CRYPTO)) ? 'crypto' : 'tetrapay');
        $package = $this->database->getPackage($packageId);
        if ($package === null) {
            $this->telegram->sendMessage($chatId, $this->uiText->error($this->catalog->get('messages.user.buy.package_not_found')));
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
            $text = $this->catalog->get('messages.user.payment.card_purchase_intro', [
                'card' => $card,
                'bank_line' => $bank !== '' ? $this->catalog->get('messages.user.payment.bank_line', ['bank' => $bank]) : '',
                'owner_line' => $owner !== '' ? $this->catalog->get('messages.user.payment.owner_line', ['owner' => $owner]) : '',
                'pending_id' => $pendingId,
                'amount' => $amount,
            ]);
            $this->database->setUserState($userId, 'await_card_receipt', ['payment_id' => $paymentId]);
            $this->telegram->sendMessage($chatId, $text);
            return;
        }

        if ($method === 'crypto') {
            $address = htmlspecialchars($this->gateways->cryptoAddress('tron'));
            $text = $this->catalog->get('messages.user.payment.crypto_purchase_intro', [
                'pending_id' => $pendingId,
                'amount' => $amount,
                'address_block' => $address !== '' ? $this->catalog->get('messages.user.payment.address_block', ['address' => $address]) : '',
            ]);
            $this->database->setUserState($userId, 'await_crypto_tx', ['payment_id' => $paymentId]);
            $this->telegram->sendMessage($chatId, $text);
            return;
        }

        $tp = $this->gateways->createTetrapayOrder($amount, (string) $pendingId);
        if (!($tp['ok'] ?? false)) {
            $this->telegram->sendMessage($chatId, $this->catalog->get('messages.user.payment.tetrapay_unavailable', ['pending_id' => $pendingId, 'amount' => $amount]));
            return;
        }
        $authority = (string) ($tp['authority'] ?? '');
        if ($authority !== '') {
            $this->database->setPaymentGatewayRef($paymentId, $authority);
        }
        $payUrl = (string) ($tp['pay_url'] ?? '');
        $this->database->setUserState($userId, 'buy.await_payment_verify', ['payment_id' => $paymentId, 'gateway' => 'tetrapay', 'ok_text' => $this->catalog->get('messages.user.payment.ok.tetrapay_purchase'), 'type_id' => 0, 'package_id' => $packageId, 'payment_method' => 'tetrapay']);
        $this->sendGatewayPaymentIntro($chatId, $this->catalog->get('messages.user.payment.titles.tetrapay_purchase'), $pendingId, $amount, $payUrl);
    }

    private function createPanelPurchasePaymentByMethod(int $chatId, int $userId, int $serviceId, float $selectedVolumeGb, int $amount, string $methodLabel): void
    {
        $service = $this->database->getProvisioningService($serviceId);
        if (!is_array($service) || !$this->database->validatePanelServiceVolume($service, $selectedVolumeGb)) {
            $this->telegram->sendMessage($chatId, $this->uiText->error($this->catalog->get('messages.user.buy.panel.errors.invalid_volume')));
            return;
        }

        $method = ($methodLabel === $this->catalog->get('buttons.pay.card') || $methodLabel === $this->uiConst(self::PAY_CARD)) ? 'card' : (($methodLabel === $this->catalog->get('buttons.pay.crypto') || $methodLabel === $this->uiConst(self::PAY_CRYPTO)) ? 'crypto' : 'tetrapay');
        $paymentMethod = $method === 'crypto' ? 'crypto:tron' : $method;
        $paymentId = $this->database->createPayment([
            'kind' => 'purchase',
            'user_id' => $userId,
            'package_id' => null,
            'amount' => $amount,
            'payment_method' => $paymentMethod,
            'status' => $method === 'tetrapay' ? 'waiting_gateway' : 'waiting_admin',
            'gateway_ref' => null,
            'created_at' => gmdate('Y-m-d H:i:s'),
        ]);
        $pendingId = $this->database->createPendingOrder([
            'user_id' => $userId,
            'package_id' => null,
            'order_mode' => 'panel_only',
            'service_id' => $serviceId,
            'selected_volume_gb' => $selectedVolumeGb,
            'computed_amount' => $amount,
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
            $text = $this->catalog->get('messages.user.payment.card_purchase_intro', [
                'card' => $card,
                'bank_line' => $bank !== '' ? $this->catalog->get('messages.user.payment.bank_line', ['bank' => $bank]) : '',
                'owner_line' => $owner !== '' ? $this->catalog->get('messages.user.payment.owner_line', ['owner' => $owner]) : '',
                'pending_id' => $pendingId,
                'amount' => $amount,
            ]);
            $this->database->setUserState($userId, 'await_card_receipt', ['payment_id' => $paymentId]);
            $this->telegram->sendMessage($chatId, $text);
            return;
        }

        if ($method === 'crypto') {
            $address = htmlspecialchars($this->gateways->cryptoAddress('tron'));
            $text = $this->catalog->get('messages.user.payment.crypto_purchase_intro', [
                'pending_id' => $pendingId,
                'amount' => $amount,
                'address_block' => $address !== '' ? $this->catalog->get('messages.user.payment.address_block', ['address' => $address]) : '',
            ]);
            $this->database->setUserState($userId, 'await_crypto_tx', ['payment_id' => $paymentId]);
            $this->telegram->sendMessage($chatId, $text);
            return;
        }

        $tp = $this->gateways->createTetrapayOrder($amount, (string) $pendingId);
        if (!($tp['ok'] ?? false)) {
            $this->telegram->sendMessage($chatId, $this->catalog->get('messages.user.payment.tetrapay_unavailable', ['pending_id' => $pendingId, 'amount' => $amount]));
            return;
        }
        $authority = (string) ($tp['authority'] ?? '');
        if ($authority !== '') {
            $this->database->setPaymentGatewayRef($paymentId, $authority);
        }
        $payUrl = (string) ($tp['pay_url'] ?? '');
        $this->database->setUserState($userId, 'buy.await_payment_verify', ['payment_id' => $paymentId, 'gateway' => 'tetrapay', 'ok_text' => $this->catalog->get('messages.user.payment.ok.tetrapay_purchase'), 'service_id' => $serviceId, 'selected_volume_gb' => $selectedVolumeGb, 'payment_method' => 'tetrapay']);
        $this->sendGatewayPaymentIntro($chatId, $this->catalog->get('messages.user.payment.titles.tetrapay_purchase'), $pendingId, $amount, $payUrl);
    }

    private function createServicePurchasePaymentByMethod(int $chatId, int $userId, int $serviceId, int $tariffId, ?float $selectedVolumeGb, string $methodLabel): void
    {
        $service = $this->database->getService($serviceId);
        $tariff = $this->database->getServiceTariffForService($serviceId, $tariffId);
        if (!is_array($service) || !is_array($tariff)) {
            $this->telegram->sendMessage($chatId, $this->uiText->error($this->catalog->get('messages.user.common.invalid_option')));
            return;
        }
        if ((string) ($service['mode'] ?? 'stock') === 'stock' && $this->database->countAvailableConfigsByService($serviceId, $tariffId) <= 0) {
            $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('messages.user.payment.errors.no_stock')));
            return;
        }
        $amount = $this->database->calculateServiceTariffAmount($tariff, $selectedVolumeGb);
        if ($amount <= 0) {
            $this->telegram->sendMessage($chatId, $this->uiText->error($this->catalog->get('messages.user.buy.panel.errors.invalid_volume')));
            return;
        }
        $method = ($methodLabel === $this->catalog->get('buttons.pay.card') || $methodLabel === $this->uiConst(self::PAY_CARD)) ? 'card' : (($methodLabel === $this->catalog->get('buttons.pay.crypto') || $methodLabel === $this->uiConst(self::PAY_CRYPTO)) ? 'crypto' : 'tetrapay');
        $paymentMethod = $method === 'crypto' ? 'crypto:tron' : $method;
        $paymentId = $this->database->createPayment([
            'kind' => 'purchase',
            'user_id' => $userId,
            'package_id' => null,
            'service_id' => $serviceId,
            'tariff_id' => $tariffId,
            'amount' => $amount,
            'payment_method' => $paymentMethod,
            'status' => $method === 'tetrapay' ? 'waiting_gateway' : 'waiting_admin',
            'gateway_ref' => null,
            'created_at' => gmdate('Y-m-d H:i:s'),
        ]);
        $pendingId = $this->database->createPendingOrder([
            'user_id' => $userId,
            'package_id' => null,
            'service_id' => $serviceId,
            'tariff_id' => $tariffId,
            'selected_volume_gb' => $selectedVolumeGb,
            'computed_amount' => $amount,
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
            $text = $this->catalog->get('messages.user.payment.card_purchase_intro', [
                'card' => $card,
                'bank_line' => $bank !== '' ? $this->catalog->get('messages.user.payment.bank_line', ['bank' => $bank]) : '',
                'owner_line' => $owner !== '' ? $this->catalog->get('messages.user.payment.owner_line', ['owner' => $owner]) : '',
                'pending_id' => $pendingId,
                'amount' => $amount,
            ]);
            $this->database->setUserState($userId, 'await_card_receipt', ['payment_id' => $paymentId]);
            $this->telegram->sendMessage($chatId, $text);
            return;
        }

        if ($method === 'crypto') {
            $address = htmlspecialchars($this->gateways->cryptoAddress('tron'));
            $text = $this->catalog->get('messages.user.payment.crypto_purchase_intro', [
                'pending_id' => $pendingId,
                'amount' => $amount,
                'address_block' => $address !== '' ? $this->catalog->get('messages.user.payment.address_block', ['address' => $address]) : '',
            ]);
            $this->database->setUserState($userId, 'await_crypto_tx', ['payment_id' => $paymentId]);
            $this->telegram->sendMessage($chatId, $text);
            return;
        }

        $tp = $this->gateways->createTetrapayOrder($amount, (string) $pendingId);
        if (!($tp['ok'] ?? false)) {
            $this->telegram->sendMessage($chatId, $this->catalog->get('messages.user.payment.tetrapay_unavailable', ['pending_id' => $pendingId, 'amount' => $amount]));
            return;
        }
        $authority = (string) ($tp['authority'] ?? '');
        if ($authority !== '') {
            $this->database->setPaymentGatewayRef($paymentId, $authority);
        }
        $payUrl = (string) ($tp['pay_url'] ?? '');
        $this->database->setUserState($userId, 'buy.await_payment_verify', ['payment_id' => $paymentId, 'gateway' => 'tetrapay', 'ok_text' => $this->catalog->get('messages.user.payment.ok.tetrapay_purchase'), 'service_id' => $serviceId, 'tariff_id' => $tariffId, 'selected_volume_gb' => $selectedVolumeGb, 'payment_method' => 'tetrapay']);
        $this->sendGatewayPaymentIntro($chatId, $this->catalog->get('messages.user.payment.titles.tetrapay_purchase'), $pendingId, $amount, $payUrl);
    }

    private function createRenewalPaymentByMethod(int $chatId, int $userId, int $purchaseId, int $packageId, string $methodLabel): void
    {
        $method = ($methodLabel === $this->catalog->get('buttons.pay.card') || $methodLabel === $this->uiConst(self::PAY_CARD)) ? 'card' : (($methodLabel === $this->catalog->get('buttons.pay.crypto') || $methodLabel === $this->uiConst(self::PAY_CRYPTO)) ? 'crypto' : 'tetrapay');
        $purchase = $this->database->getUserPurchaseForRenewal($userId, $purchaseId);
        $package = $this->database->getPackage($packageId);
        if (!is_array($purchase) || $package === null) {
            $this->telegram->sendMessage($chatId, $this->catalog->get('messages.user.renew.invalid_data'));
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
            $text = $this->catalog->get('messages.user.payment.card_renew_intro', [
                'card' => $card,
                'bank_line' => $bank !== '' ? $this->catalog->get('messages.user.payment.bank_line', ['bank' => $bank]) : '',
                'owner_line' => $owner !== '' ? $this->catalog->get('messages.user.payment.owner_line', ['owner' => $owner]) : '',
                'pending_id' => $pendingId,
                'amount' => $amount,
            ]);
            $this->database->setUserState($userId, 'await_renewal_receipt', ['payment_id' => $paymentId]);
            $this->telegram->sendMessage($chatId, $text);
            return;
        }

        if ($method === 'crypto') {
            $address = htmlspecialchars($this->gateways->cryptoAddress('tron'));
            $text = $this->catalog->get('messages.user.payment.crypto_renew_intro', [
                'pending_id' => $pendingId,
                'amount' => $amount,
                'address_block' => $address !== '' ? $this->catalog->get('messages.user.payment.address_block', ['address' => $address]) : '',
            ]);
            $this->database->setUserState($userId, 'await_renewal_crypto_tx', ['payment_id' => $paymentId]);
            $this->telegram->sendMessage($chatId, $text);
            return;
        }

        $tp = $this->gateways->createTetrapayOrder($amount, (string) $pendingId);
        if (!($tp['ok'] ?? false)) {
            $this->telegram->sendMessage($chatId, $this->catalog->get('messages.user.payment.tetrapay_renew_unavailable', ['pending_id' => $pendingId, 'amount' => $amount]));
            return;
        }
        $authority = (string) ($tp['authority'] ?? '');
        if ($authority !== '') {
            $this->database->setPaymentGatewayRef($paymentId, $authority);
        }
        $payUrl = (string) ($tp['pay_url'] ?? '');
        $this->database->setUserState($userId, 'renew.await_payment_verify', ['payment_id' => $paymentId, 'gateway' => 'tetrapay', 'ok_text' => $this->catalog->get('messages.user.payment.ok.tetrapay_renew'), 'purchase_id' => $purchaseId, 'package_id' => $packageId, 'payment_method' => 'tetrapay']);
        $this->sendGatewayPaymentIntro($chatId, $this->catalog->get('messages.user.payment.titles.tetrapay_renew'), $pendingId, $amount, $payUrl);
    }

    private function createPurchaseGatewayInvoice(int $chatId, int $userId, int $packageId, string $methodLabel): void
    {
        $gateway = $methodLabel === $this->uiConst(self::PAY_SWAPWALLET) ? 'swapwallet_crypto' : 'tronpays_rial';
        $package = $this->database->getPackage($packageId);
        if ($package === null) {
            $this->telegram->sendMessage($chatId, $this->catalog->get('messages.user.buy.package_not_found'));
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
                $this->telegram->sendMessage($chatId, $this->catalog->get('messages.user.payment.gateway.swapwallet_invoice_error'));
                return;
            }
            $invoiceId = (string) ($invoice['invoice_id'] ?? '');
            if ($invoiceId !== '') {
                $this->database->setPaymentGatewayRef($paymentId, $invoiceId);
            }
            $payUrl = (string) ($invoice['pay_url'] ?? '');
            $this->database->setUserState($userId, 'buy.await_payment_verify', ['payment_id' => $paymentId, 'gateway' => $gateway, 'ok_text' => $this->catalog->get('messages.user.payment.ok.swapwallet_purchase'), 'type_id' => 0, 'package_id' => $packageId, 'payment_method' => $gateway]);
            $this->sendGatewayPaymentIntro($chatId, $this->catalog->get('messages.user.payment.titles.swapwallet_purchase'), $pendingId, $amount, $payUrl);
            return;
        }
        $invoice = $this->gateways->createTronpaysRialInvoice($amount, 'buy-' . $userId . '-' . $packageId . '-' . time());
        if (!($invoice['ok'] ?? false)) {
            $this->telegram->sendMessage($chatId, $this->catalog->get('messages.user.payment.gateway.tronpays_invoice_error'));
            return;
        }
        $invoiceId = (string) ($invoice['invoice_id'] ?? '');
        if ($invoiceId !== '') {
            $this->database->setPaymentGatewayRef($paymentId, $invoiceId);
        }
        $payUrl = (string) ($invoice['pay_url'] ?? '');
        $this->database->setUserState($userId, 'buy.await_payment_verify', ['payment_id' => $paymentId, 'gateway' => $gateway, 'ok_text' => $this->catalog->get('messages.user.payment.ok.tronpays_purchase'), 'type_id' => 0, 'package_id' => $packageId, 'payment_method' => $gateway]);
        $this->sendGatewayPaymentIntro($chatId, $this->catalog->get('messages.user.payment.titles.tronpays_purchase'), $pendingId, $amount, $payUrl);
    }

    private function createPanelPurchaseGatewayInvoice(int $chatId, int $userId, int $serviceId, float $selectedVolumeGb, int $amount, string $methodLabel): void
    {
        $service = $this->database->getProvisioningService($serviceId);
        if (!is_array($service) || !$this->database->validatePanelServiceVolume($service, $selectedVolumeGb)) {
            $this->telegram->sendMessage($chatId, $this->uiText->error($this->catalog->get('messages.user.buy.panel.errors.invalid_volume')));
            return;
        }

        $gateway = $methodLabel === $this->uiConst(self::PAY_SWAPWALLET) ? 'swapwallet_crypto' : 'tronpays_rial';
        $paymentId = $this->database->createPayment([
            'kind' => 'purchase',
            'user_id' => $userId,
            'package_id' => null,
            'amount' => $amount,
            'payment_method' => $gateway,
            'status' => 'waiting_gateway',
            'created_at' => gmdate('Y-m-d H:i:s'),
        ]);
        $pendingId = $this->database->createPendingOrder([
            'user_id' => $userId,
            'package_id' => null,
            'order_mode' => 'panel_only',
            'service_id' => $serviceId,
            'selected_volume_gb' => $selectedVolumeGb,
            'computed_amount' => $amount,
            'payment_id' => $paymentId,
            'amount' => $amount,
            'payment_method' => $gateway,
            'created_at' => gmdate('Y-m-d H:i:s'),
            'status' => 'waiting_payment',
        ]);

        if ($gateway === 'swapwallet_crypto') {
            $invoice = $this->gateways->createSwapwalletCryptoInvoice($amount, (string) $pendingId, 'TRON', 'Purchase');
            if (!($invoice['ok'] ?? false)) {
                $this->telegram->sendMessage($chatId, $this->catalog->get('messages.user.payment.gateway.swapwallet_invoice_error'));
                return;
            }
            $invoiceId = (string) ($invoice['invoice_id'] ?? '');
            if ($invoiceId !== '') {
                $this->database->setPaymentGatewayRef($paymentId, $invoiceId);
            }
            $payUrl = (string) ($invoice['pay_url'] ?? '');
            $this->database->setUserState($userId, 'buy.await_payment_verify', ['payment_id' => $paymentId, 'gateway' => $gateway, 'ok_text' => $this->catalog->get('messages.user.payment.ok.swapwallet_purchase'), 'service_id' => $serviceId, 'selected_volume_gb' => $selectedVolumeGb, 'payment_method' => $gateway]);
            $this->sendGatewayPaymentIntro($chatId, $this->catalog->get('messages.user.payment.titles.swapwallet_purchase'), $pendingId, $amount, $payUrl);
            return;
        }

        $invoice = $this->gateways->createTronpaysRialInvoice($amount, 'buy-panel-' . $userId . '-' . $serviceId . '-' . time());
        if (!($invoice['ok'] ?? false)) {
            $this->telegram->sendMessage($chatId, $this->catalog->get('messages.user.payment.gateway.tronpays_invoice_error'));
            return;
        }
        $invoiceId = (string) ($invoice['invoice_id'] ?? '');
        if ($invoiceId !== '') {
            $this->database->setPaymentGatewayRef($paymentId, $invoiceId);
        }
        $payUrl = (string) ($invoice['pay_url'] ?? '');
        $this->database->setUserState($userId, 'buy.await_payment_verify', ['payment_id' => $paymentId, 'gateway' => $gateway, 'ok_text' => $this->catalog->get('messages.user.payment.ok.tronpays_purchase'), 'service_id' => $serviceId, 'selected_volume_gb' => $selectedVolumeGb, 'payment_method' => $gateway]);
        $this->sendGatewayPaymentIntro($chatId, $this->catalog->get('messages.user.payment.titles.tronpays_purchase'), $pendingId, $amount, $payUrl);
    }

    private function createServicePurchaseGatewayInvoice(int $chatId, int $userId, int $serviceId, int $tariffId, ?float $selectedVolumeGb, string $methodLabel): void
    {
        $service = $this->database->getService($serviceId);
        $tariff = $this->database->getServiceTariffForService($serviceId, $tariffId);
        if (!is_array($service) || !is_array($tariff)) {
            $this->telegram->sendMessage($chatId, $this->uiText->error($this->catalog->get('messages.user.common.invalid_option')));
            return;
        }
        if ((string) ($service['mode'] ?? 'stock') === 'stock' && $this->database->countAvailableConfigsByService($serviceId, $tariffId) <= 0) {
            $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('messages.user.payment.errors.no_stock')));
            return;
        }
        $amount = $this->database->calculateServiceTariffAmount($tariff, $selectedVolumeGb);
        if ($amount <= 0) {
            $this->telegram->sendMessage($chatId, $this->uiText->error($this->catalog->get('messages.user.buy.panel.errors.invalid_volume')));
            return;
        }

        $gateway = $methodLabel === $this->uiConst(self::PAY_SWAPWALLET) ? 'swapwallet_crypto' : 'tronpays_rial';
        $paymentId = $this->database->createPayment([
            'kind' => 'purchase',
            'user_id' => $userId,
            'package_id' => null,
            'service_id' => $serviceId,
            'tariff_id' => $tariffId,
            'amount' => $amount,
            'payment_method' => $gateway,
            'status' => 'waiting_gateway',
            'created_at' => gmdate('Y-m-d H:i:s'),
        ]);
        $pendingId = $this->database->createPendingOrder([
            'user_id' => $userId,
            'package_id' => null,
            'service_id' => $serviceId,
            'tariff_id' => $tariffId,
            'selected_volume_gb' => $selectedVolumeGb,
            'computed_amount' => $amount,
            'payment_id' => $paymentId,
            'amount' => $amount,
            'payment_method' => $gateway,
            'created_at' => gmdate('Y-m-d H:i:s'),
            'status' => 'waiting_payment',
        ]);

        if ($gateway === 'swapwallet_crypto') {
            $invoice = $this->gateways->createSwapwalletCryptoInvoice($amount, (string) $pendingId, 'TRON', 'Purchase');
            if (!($invoice['ok'] ?? false)) {
                $this->telegram->sendMessage($chatId, $this->catalog->get('messages.user.payment.gateway.swapwallet_invoice_error'));
                return;
            }
            $invoiceId = (string) ($invoice['invoice_id'] ?? '');
            if ($invoiceId !== '') {
                $this->database->setPaymentGatewayRef($paymentId, $invoiceId);
            }
            $payUrl = (string) ($invoice['pay_url'] ?? '');
            $this->database->setUserState($userId, 'buy.await_payment_verify', ['payment_id' => $paymentId, 'gateway' => $gateway, 'ok_text' => $this->catalog->get('messages.user.payment.ok.swapwallet_purchase'), 'service_id' => $serviceId, 'tariff_id' => $tariffId, 'selected_volume_gb' => $selectedVolumeGb, 'payment_method' => $gateway]);
            $this->sendGatewayPaymentIntro($chatId, $this->catalog->get('messages.user.payment.titles.swapwallet_purchase'), $pendingId, $amount, $payUrl);
            return;
        }

        $invoice = $this->gateways->createTronpaysRialInvoice($amount, 'buy-service-' . $userId . '-' . $serviceId . '-' . $tariffId . '-' . time());
        if (!($invoice['ok'] ?? false)) {
            $this->telegram->sendMessage($chatId, $this->catalog->get('messages.user.payment.gateway.tronpays_invoice_error'));
            return;
        }
        $invoiceId = (string) ($invoice['invoice_id'] ?? '');
        if ($invoiceId !== '') {
            $this->database->setPaymentGatewayRef($paymentId, $invoiceId);
        }
        $payUrl = (string) ($invoice['pay_url'] ?? '');
        $this->database->setUserState($userId, 'buy.await_payment_verify', ['payment_id' => $paymentId, 'gateway' => $gateway, 'ok_text' => $this->catalog->get('messages.user.payment.ok.tronpays_purchase'), 'service_id' => $serviceId, 'tariff_id' => $tariffId, 'selected_volume_gb' => $selectedVolumeGb, 'payment_method' => $gateway]);
        $this->sendGatewayPaymentIntro($chatId, $this->catalog->get('messages.user.payment.titles.tronpays_purchase'), $pendingId, $amount, $payUrl);
    }

    private function createRenewalGatewayInvoice(int $chatId, int $userId, int $purchaseId, int $packageId, string $methodLabel): void
    {
        $gateway = $methodLabel === $this->uiConst(self::PAY_SWAPWALLET) ? 'swapwallet_crypto' : 'tronpays_rial';
        $package = $this->database->getPackage($packageId);
        $purchase = $this->database->getUserPurchaseForRenewal($userId, $purchaseId);
        if ($package === null || !is_array($purchase)) {
            $this->telegram->sendMessage($chatId, $this->catalog->get('messages.user.renew.invalid_data'));
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
                $this->telegram->sendMessage($chatId, $this->catalog->get('messages.user.payment.gateway.swapwallet_invoice_error'));
                return;
            }
            $invoiceId = (string) ($invoice['invoice_id'] ?? '');
            if ($invoiceId !== '') {
                $this->database->setPaymentGatewayRef($paymentId, $invoiceId);
            }
            $payUrl = (string) ($invoice['pay_url'] ?? '');
            $this->database->setUserState($userId, 'renew.await_payment_verify', ['payment_id' => $paymentId, 'gateway' => $gateway, 'ok_text' => $this->catalog->get('messages.user.payment.ok.swapwallet_renew'), 'purchase_id' => $purchaseId, 'package_id' => $packageId, 'payment_method' => $gateway]);
            $this->sendGatewayPaymentIntro($chatId, $this->catalog->get('messages.user.payment.titles.swapwallet_renew'), $pendingId, $amount, $payUrl);
            return;
        }
        $invoice = $this->gateways->createTronpaysRialInvoice($amount, 'rnw-' . $userId . '-' . $packageId . '-' . time());
        if (!($invoice['ok'] ?? false)) {
            $this->telegram->sendMessage($chatId, $this->catalog->get('messages.user.payment.gateway.tronpays_invoice_error'));
            return;
        }
        $invoiceId = (string) ($invoice['invoice_id'] ?? '');
        if ($invoiceId !== '') {
            $this->database->setPaymentGatewayRef($paymentId, $invoiceId);
        }
        $payUrl = (string) ($invoice['pay_url'] ?? '');
        $this->database->setUserState($userId, 'renew.await_payment_verify', ['payment_id' => $paymentId, 'gateway' => $gateway, 'ok_text' => $this->catalog->get('messages.user.payment.ok.tronpays_renew'), 'purchase_id' => $purchaseId, 'package_id' => $packageId, 'payment_method' => $gateway]);
        $this->sendGatewayPaymentIntro($chatId, $this->catalog->get('messages.user.payment.titles.tronpays_renew'), $pendingId, $amount, $payUrl);
    }

    private function handleGatewayVerifyState(int $chatId, int $userId, string $text, array $state): void
    {
        if ($this->isMainMenuInput($text)) {
            $this->database->clearUserState($userId);
            $this->telegram->sendMessage($chatId, $this->menus->mainMenuText(), $this->menus->mainMenuReplyKeyboard($userId));
            return;
        }
        if ($text !== $this->catalog->get('buttons.pay.verify') && $text !== $this->uiConst(self::PAY_VERIFY)) {
            $this->telegram->sendMessage($chatId, $this->uiText->info($this->catalog->get('messages.user.payment.verify_hint')));
            return;
        }
        $paymentId = (int) ($state['payload']['payment_id'] ?? 0);
        $gateway = (string) ($state['payload']['gateway'] ?? '');
        $okText = (string) ($state['payload']['ok_text'] ?? $this->catalog->get('messages.user.payment.ok.default'));
        if ($paymentId <= 0 || $gateway === '') {
            $this->database->clearUserState($userId);
            return;
        }
        $payment = $this->database->getPaymentById($paymentId);
        if ($payment === null) {
            $this->telegram->sendMessage($chatId, $this->uiText->error($this->catalog->get('messages.user.payment.not_found')));
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
        $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('messages.user.payment.not_confirmed')));
    }

    private function handlePurchaseRulesAcceptState(int $chatId, int $userId, string $text, array $state): void
    {
        if ($this->isMainMenuInput($text)) {
            $this->database->clearUserState($userId);
            $this->telegram->sendMessage($chatId, $this->menus->mainMenuText(), $this->menus->mainMenuReplyKeyboard($userId));
            return;
        }
        if ($text === UiLabels::back($this->catalog)) {
            if ((int) ($state['payload']['service_flow'] ?? 0) === 1) {
                $typeId = (int) ($state['payload']['type_id'] ?? 0);
                $serviceId = (int) ($state['payload']['service_id'] ?? 0);
                if ($typeId > 0 && $serviceId > 0) {
                    $this->showBuyServiceTariffSelection($chatId, $userId, $typeId, $serviceId);
                    return;
                }
            }
            if ((string) ($state['payload']['order_mode'] ?? '') === 'panel_only') {
                $this->openPanelServiceSelection($chatId, $userId);
                return;
            }
            $typeId = (int) ($state['payload']['type_id'] ?? 0);
            if ($typeId > 0) {
                $this->showBuyPackageSelection($chatId, $userId, $typeId);
                return;
            }
            $this->startBuyTypeReplyFlow($chatId, $userId);
            return;
        }
        if ($text !== $this->catalog->get('buttons.accept_rules') && $text !== $this->uiConst(self::ACCEPT_RULES)) {
            $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('messages.user.buy.rules.must_accept')));
            return;
        }
        if ((string) ($state['payload']['order_mode'] ?? '') === 'panel_only') {
            $serviceId = (int) ($state['payload']['service_id'] ?? 0);
            $selectedVolumeGb = (float) ($state['payload']['selected_volume_gb'] ?? 0);
            $computedAmount = (int) ($state['payload']['computed_amount'] ?? 0);
            $service = $this->database->getProvisioningService($serviceId);
            if (!is_array($service) || !$this->database->validatePanelServiceVolume($service, $selectedVolumeGb) || $computedAmount <= 0) {
                $this->database->clearUserState($userId);
                $this->openPanelServiceSelection($chatId, $userId);
                return;
            }
            $this->database->acceptPurchaseRules($userId);
            $this->openPanelPaymentSelection($chatId, $userId, $service, $selectedVolumeGb, $computedAmount);
            return;
        }
        if ((int) ($state['payload']['service_flow'] ?? 0) === 1) {
            $typeId = (int) ($state['payload']['type_id'] ?? 0);
            $serviceId = (int) ($state['payload']['service_id'] ?? 0);
            $tariffId = (int) ($state['payload']['tariff_id'] ?? 0);
            $selectedVolumeGb = isset($state['payload']['selected_volume_gb']) ? (float) $state['payload']['selected_volume_gb'] : null;
            if ($typeId <= 0 || $serviceId <= 0 || $tariffId <= 0) {
                $this->database->clearUserState($userId);
                $this->startBuyTypeReplyFlow($chatId, $userId);
                return;
            }
            $this->database->acceptPurchaseRules($userId);
            $this->openServicePaymentSelection($chatId, $userId, $typeId, $serviceId, $tariffId, $selectedVolumeGb);
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
            $this->telegram->sendMessage($chatId, $this->catalog->get('messages.user.buy.package_not_found'));
            return;
        }
        $textOut = $this->messageRenderer->render('messages.user.buy.payment.overview', [
            'package_name' => (string) $package['name'],
            'amount' => (int) $this->database->effectivePackagePrice($userId, $package),
        ]);
        $buttons = [[$this->catalog->get('buttons.pay.wallet')]];
        if ($this->settings->get('gw_card_enabled', '0') === '1') {
            $buttons[] = [$this->catalog->get('buttons.pay.card')];
        }
        if ($this->settings->get('gw_crypto_enabled', '0') === '1') {
            $buttons[] = [$this->catalog->get('buttons.pay.crypto')];
        }
        if ($this->settings->get('gw_tetrapay_enabled', '0') === '1') {
            $buttons[] = [$this->catalog->get('buttons.pay.tetrapay')];
        }
        if ($this->settings->get('gw_swapwallet_crypto_enabled', '0') === '1') {
            $buttons[] = [$this->catalog->get('buttons.pay.swapwallet')];
        }
        if ($this->settings->get('gw_tronpays_rial_enabled', '0') === '1') {
            $buttons[] = [$this->catalog->get('buttons.pay.tronpays')];
        }
        $buttons[] = [UiLabels::back($this->catalog), UiLabels::main($this->catalog)];
        $this->database->setUserState($userId, 'buy.await_payment_method', ['package_id' => $packageId, 'type_id' => (int) ($state['payload']['type_id'] ?? 0), 'stack' => ['buy.await_type', 'buy.await_package'], 'payment_method' => null, 'gateway' => null]);
        $this->telegram->sendMessage($chatId, $textOut, $this->uiKeyboard->replyMenu($buttons));
    }

    private function sendGatewayPaymentIntro(int $chatId, string $title, int $pendingId, int $amount, string $payUrl): void
    {
        $text = $this->uiText->paymentCreated(
            paymentId: $pendingId,
            amount: $amount,
            title: $title,
            tip: $this->catalog->get('messages.user.payment.gateway_intro_tip'),
        );
        if ($payUrl !== '') {
            $this->telegram->sendMessage($chatId, $text, $this->uiKeyboard->inlineUrl($this->catalog->get('buttons.pay.gateway_pay'), $payUrl));
        } else {
            $this->telegram->sendMessage($chatId, $text);
        }
        $this->telegram->sendMessage(
            $chatId,
            $this->uiText->info($this->catalog->get('messages.user.payment.verify_prompt')),
            $this->uiKeyboard->replyMenu([[$this->catalog->get('buttons.pay.verify')], [UiLabels::back($this->catalog), UiLabels::main($this->catalog)]])
        );
    }

    private function ensurePurchaseAllowedForPackageMessage(int $chatId, int $userId, int $packageId): bool
    {
        if ($this->settings->get('shop_open', '1') !== '1') {
            $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('messages.user.buy.shop_closed')));
            return false;
        }
        if ($this->settings->get('purchase_rules_enabled', '0') === '1' && !$this->database->hasAcceptedPurchaseRules($userId)) {
            $this->telegram->sendMessage($chatId, $this->uiText->warning($this->catalog->get('messages.user.buy.rules.need_accept_first')));
            return false;
        }
        if ($this->settings->get('delivery_mode', 'stock_only') === 'stock_only' && !$this->database->packageHasAvailableStock($packageId)) {
            $this->telegram->sendMessage($chatId, $this->uiText->info($this->catalog->get('messages.user.buy.out_of_stock')));
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
        return $this->catalog->get('messages.channel.lock_simple');
    }

    private function channelLockKeyboard(): array
    {
        $channelId = trim($this->settings->get('channel_id', ''));
        $channelUrl = $this->channelJoinUrl($channelId);
        return ['inline_keyboard' => [[['text' => $this->catalog->get('buttons.join_channel'), 'url' => $channelUrl]]]];
    }

    private function channelLockReplyKeyboard(): array
    {
        return $this->replyKeyboard([[KeyboardBuilder::checkChannel()]]);
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
            KeyboardBuilder::buy(),
            KeyboardBuilder::myConfigs(),
            KeyboardBuilder::freeTest(),
            KeyboardBuilder::profile(),
            KeyboardBuilder::wallet(),
            KeyboardBuilder::support(),
            KeyboardBuilder::referralButton(),
            KeyboardBuilder::agency(),
            KeyboardBuilder::admin(),
            KeyboardBuilder::backMain(),
            KeyboardBuilder::backAccount(),
            KeyboardBuilder::backTypes(),
            KeyboardBuilder::backPurchases(),
            KeyboardBuilder::checkChannel(),
            KeyboardBuilder::buy(),
            KeyboardBuilder::myConfigs(),
            KeyboardBuilder::freeTest(),
            KeyboardBuilder::profile(),
            KeyboardBuilder::wallet(),
            KeyboardBuilder::support(),
            KeyboardBuilder::referralButton(),
            KeyboardBuilder::agency(),
            KeyboardBuilder::admin(),
            KeyboardBuilder::backMain(),
            KeyboardBuilder::backAccount(),
            KeyboardBuilder::backTypes(),
            KeyboardBuilder::backPurchases(),
            KeyboardBuilder::checkChannel(),
        ], true);
    }
}
