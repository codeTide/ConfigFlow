<?php

declare(strict_types=1);

namespace ConfigFlow\Bot;

final class CallbackHandler
{
    private const DEPRECATED_USER_CALLBACK_EXACT = [
        'profile',
        'support',
        'my_configs',
        'wallet:charge',
        'buy:start',
    ];

    private const DEPRECATED_USER_CALLBACK_PREFIX = [
        'referral:',
        'buy:',
        'renew:',
        'rpay:',
    ];

    public function __construct(
        private Database $database,
        private TelegramClient $telegram,
        private SettingsRepository $settings,
        private MenuService $menus,
        private PaymentGatewayService $gateways,
        private ?UiJsonCatalog $catalog = null,
        private ?UiMessageRenderer $messageRenderer = null,
    ) {
        $this->catalog ??= new UiJsonCatalog();
        $this->messageRenderer ??= new UiMessageRenderer($this->catalog);
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
            $this->telegram->answerCallbackQuery($callbackId, $this->messageRenderer->render('messages.callback.restricted'));
            return;
        }

        if ($data === 'noop') {
            $this->telegram->answerCallbackQuery($callbackId);
            return;
        }

        if ($data === 'check_channel') {
            if ($this->checkChannelMembership($userId)) {
                $this->telegram->answerCallbackQuery($callbackId, $this->messageRenderer->render('messages.channel.membership_confirmed'));
                $this->telegram->editMessageText($chatId, $messageId, $this->menus->mainMenuText());
                $this->telegram->sendMessage($chatId, $this->messageRenderer->render('messages.generic.main_menu'), $this->menus->mainMenuReplyKeyboard($userId));
            } else {
                $this->telegram->answerCallbackQuery($callbackId, $this->messageRenderer->render('messages.channel.membership_missing'));
                $this->telegram->editMessageText($chatId, $messageId, $this->channelLockText(), $this->channelLockKeyboard());
                $this->telegram->sendMessage($chatId, $this->messageRenderer->render('messages.channel.after_join_prompt'), $this->channelLockReplyKeyboard());
            }
            return;
        }

        if (!$this->checkChannelMembership($userId)) {
            $this->telegram->answerCallbackQuery($callbackId);
            $this->telegram->editMessageText($chatId, $messageId, $this->channelLockText(), $this->channelLockKeyboard());
            $this->telegram->sendMessage($chatId, $this->messageRenderer->render('messages.channel.after_join_prompt'), $this->channelLockReplyKeyboard());
            return;
        }

        if ($this->isDeprecatedUserInlineAction($data)) {
            error_log('Deprecated user callback route used. uid=' . $userId . ' data=' . $data);
            $this->database->clearUserState($userId);
            $this->telegram->answerCallbackQuery($callbackId, $this->messageRenderer->render('messages.callback.deprecated_route'));
            $this->telegram->sendMessage($chatId, $this->menus->mainMenuText(), $this->menus->mainMenuReplyKeyboard($userId));
            return;
        }

        if (str_starts_with($data, 'admin:') || str_starts_with($data, 'pay:')) {
            if (!$this->database->isAdminUser($userId)) {
                $this->telegram->answerCallbackQuery($callbackId, $this->messageRenderer->render('messages.callback.admin_access_denied'));
                return;
            }
            $this->database->clearUserState($userId);
            $this->telegram->answerCallbackQuery($callbackId, $this->messageRenderer->render('messages.callback.admin_legacy'));
            $this->telegram->sendMessage(
                $chatId,
                $this->messageRenderer->render('messages.callback.admin_legacy_overview', [
                    'admin_overview' => $this->menus->adminRootText(),
                    'legacy_note' => $this->messageRenderer->render('messages.callback.admin_legacy_note'),
                ], ['admin_overview']),
                $this->menus->adminRootReplyKeyboard()
            );
            return;
        }

        if (str_starts_with($data, 'pv:')) {
            $this->handlePaymentVerifyInline($chatId, $messageId, $userId, $callbackId, $data, $message);
            return;
        }

        if ($data === 'nav:main') {
            $this->telegram->editMessageText($chatId, $messageId, $this->menus->mainMenuText());
            $this->telegram->sendMessage($chatId, $this->messageRenderer->render('messages.generic.main_menu'), $this->menus->mainMenuReplyKeyboard($userId));
            $this->telegram->answerCallbackQuery($callbackId);
            return;
        }

        $this->telegram->answerCallbackQuery($callbackId, $this->messageRenderer->render('messages.callback.expired_operation'));
    }

    /** @param array<string,mixed> $message */
    private function handlePaymentVerifyInline(int $chatId, int $messageId, int $userId, string $callbackId, string $data, array $message): void
    {
        $paymentId = (int) substr($data, 3);
        if ($paymentId <= 0) {
            $this->telegram->answerCallbackQuery($callbackId, $this->messageRenderer->render('messages.callback.expired_operation'));
            return;
        }
        $payment = $this->database->getPaymentById($paymentId);
        if (!is_array($payment) || (int) ($payment['user_id'] ?? 0) !== $userId) {
            $this->telegram->answerCallbackQuery($callbackId, $this->messageRenderer->render('messages.callback.expired_operation'));
            return;
        }

        $gateway = (string) ($payment['payment_method'] ?? '');
        if (!in_array($gateway, ['tetrapay', 'nowpayments'], true)) {
            $this->telegram->answerCallbackQuery($callbackId, $this->messageRenderer->render('messages.user.payment.not_found'));
            return;
        }

        $gatewayRef = (string) ($payment['gateway_ref'] ?? '');
        $providerPayload = $this->providerPayload($payment);
        $hashId = (string) ($providerPayload['hash_id'] ?? '');
        $verify = match ($gateway) {
            'tetrapay' => $this->gateways->verifyTetrapay($gatewayRef, $hashId),
            'nowpayments' => ['ok' => true, 'paid' => in_array((string) ($payment['status'] ?? ''), ['paid', 'completed'], true)],
            default => ['ok' => false, 'paid' => false],
        };

        if (!($verify['ok'] ?? false)) {
            if ($gateway === 'tetrapay') {
                $this->telegram->answerCallbackQuery($callbackId, $this->messageRenderer->render('messages.user.payment.not_confirmed'));
                return;
            }
            $this->telegram->answerCallbackQuery($callbackId, $this->messageRenderer->render('messages.user.payment.gateway.nowpayments_waiting_confirmation'));
            return;
        }

        if (($verify['paid'] ?? false) || in_array((string) ($payment['status'] ?? ''), ['paid', 'completed'], true)) {
            $changed = (string) ($payment['kind'] ?? '') === 'wallet_topup'
                ? $this->database->markWalletTopupPaidIfWaitingGateway($paymentId)
                : $this->database->markPaymentAndPendingPaidIfWaitingGateway($paymentId);
            $latest = $this->database->getPaymentById($paymentId) ?? $payment;
            $this->database->clearUserState($userId);
            $this->telegram->answerCallbackQuery($callbackId, $this->messageRenderer->render('messages.user.payment.ok.default'));
            $text = trim((string) ($message['text'] ?? ''));
            if ($text !== '') {
                $this->telegram->editMessageText($chatId, $messageId, $text);
            }
            if ($changed || in_array((string) ($latest['status'] ?? ''), ['paid', 'completed'], true)) {
                $this->telegram->sendMessage($chatId, $this->resolveGatewayOkText($gateway, (string) ($latest['kind'] ?? '')) . $this->bonusSuccessLine($latest));
            }
            return;
        }

        if ($gateway === 'nowpayments') {
            $providerStatus = (string) ($providerPayload['last_provider_status'] ?? '');
            if ($providerStatus === 'partially_paid') {
                $this->telegram->answerCallbackQuery($callbackId, $this->catalog->get('messages.user.payment.gateway.nowpayments_partially_paid'));
                return;
            }
            if (in_array((string) ($payment['status'] ?? ''), ['gateway_error'], true)) {
                $this->database->clearUserState($userId);
                $text = trim((string) ($message['text'] ?? ''));
                if ($text !== '') {
                    $this->telegram->editMessageText($chatId, $messageId, $text);
                }
                $this->telegram->answerCallbackQuery($callbackId, $this->catalog->get('messages.user.payment.gateway.nowpayments_failed_terminal'));
                return;
            }
            $this->telegram->answerCallbackQuery($callbackId, $this->catalog->get('messages.user.payment.gateway.nowpayments_waiting_confirmation'));
            return;
        }

        $this->telegram->answerCallbackQuery($callbackId, $this->messageRenderer->render('messages.user.payment.not_confirmed'));
    }

    /** @param array<string,mixed> $payment
     *  @return array<string,mixed>
     */
    private function providerPayload(array $payment): array
    {
        if (!is_string($payment['provider_payload'] ?? null)) {
            return [];
        }
        $decoded = json_decode((string) $payment['provider_payload'], true);
        return is_array($decoded) ? $decoded : [];
    }

    /** @param array<string,mixed> $payment */
    private function bonusSuccessLine(array $payment): string
    {
        $bonusAmount = max(0, (int) ($payment['bonus_amount'] ?? 0));
        if ($bonusAmount <= 0) {
            return '';
        }
        return "\n\n" . $this->catalog->get('messages.user.payment.bonus_applied', ['bonus_amount' => $bonusAmount]);
    }

    private function resolveGatewayOkText(string $gateway, string $kind): string
    {
        return match ($kind) {
            'purchase' => $this->catalog->get('messages.user.payment.ok.' . $gateway . '_purchase'),
            'renewal' => $this->catalog->get('messages.user.payment.ok.' . $gateway . '_renew'),
            'wallet_topup' => $this->catalog->get('messages.user.payment.ok.wallet_topup'),
            default => $this->catalog->get('messages.user.payment.ok.default'),
        };
    }

    private function isDeprecatedUserInlineAction(string $data): bool
    {
        if (in_array($data, self::DEPRECATED_USER_CALLBACK_EXACT, true)) {
            return true;
        }
        foreach (self::DEPRECATED_USER_CALLBACK_PREFIX as $prefix) {
            if (str_starts_with($data, $prefix)) {
                return true;
            }
        }

        return false;
    }

    private function checkChannelMembership(int $userId): bool
    {
        $channelId = trim($this->settings->get('channel_id', ''));
        if ($channelId === '') {
            return true;
        }

        $member = $this->telegram->getChatMember($channelId, $userId);
        $status = (string) ($member['status'] ?? 'left');

        return in_array($status, ['member', 'administrator', 'creator'], true);
    }

    private function channelLockText(): string
    {
        $channelId = trim($this->settings->get('channel_id', ''));
        $title = trim($this->settings->get('channel_title', ''));
        if ($title === '') {
            $title = $this->messageRenderer->render('messages.channel.lock_title_default');
        }

        $mention = $channelId;
        if ($mention !== '' && !str_starts_with($mention, '@') && preg_match('/^-?\d+$/', $mention) !== 1) {
            $mention = '@' . ltrim($mention, '@');
        }

        $line2 = $mention !== ''
            ? $this->messageRenderer->render('messages.channel.mention_line', ['mention' => $mention])
            : $this->messageRenderer->render('messages.channel.private_line', ['label' => $this->messageRenderer->render('messages.channel.private')]);

        return $this->messageRenderer->render('messages.channel.lock_detailed', [
            'title' => htmlspecialchars($title),
            'line2' => $line2,
        ], ['line2']);
    }

    private function channelLockKeyboard(): array
    {
        $channelUrl = trim($this->settings->get('channel_url', ''));
        $channelId = trim($this->settings->get('channel_id', ''));

        if ($channelUrl === '' && $channelId !== '') {
            if (str_starts_with($channelId, '@')) {
                $channelUrl = 'https://t.me/' . ltrim($channelId, '@');
            } elseif (ctype_digit(ltrim($channelId, '-')) && !str_starts_with($channelId, '-100')) {
                $channelUrl = 'https://t.me/' . $channelId;
            }
        }

        $rows = [];
        if ($channelUrl !== '') {
            $rows[] = [['text' => $this->catalog->get('buttons.enter_channel'), 'url' => $channelUrl]];
        }

        return ['inline_keyboard' => $rows];
    }

    private function channelLockReplyKeyboard(): array
    {
        return [
            'keyboard' => [
                [KeyboardBuilder::checkChannel()],
            ],
            'resize_keyboard' => true,
            'is_persistent' => true,
        ];
    }
}
