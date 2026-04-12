<?php

declare(strict_types=1);

namespace ConfigFlow\Bot;

final class CallbackHandler
{
    private const GROUP_TOPICS = [
        'backup' => '💾 بکاپ',
        'broadcast_report' => '📢 اطلاع‌رسانی و پین',
        'error_log' => '🚨 خطاها',
    ];

    public function __construct(
        private Database $database,
        private TelegramClient $telegram,
        private SettingsRepository $settings,
        private MenuService $menus,
        private PaymentGatewayService $gateways,
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

        $isAdmin = $this->database->isAdminUser($userId);

        if ($data === 'noop') {
            $this->telegram->answerCallbackQuery($callbackId);
            return;
        }

        if ($data === 'check_channel') {
            if ($this->checkChannelMembership($userId)) {
                $this->telegram->answerCallbackQuery($callbackId, '✅ عضویت تأیید شد!');
                $this->telegram->editMessageText(
                    $chatId,
                    $messageId,
                    $this->menus->mainMenuText(),
                    $this->menus->mainMenuKeyboard($userId)
                );
            } else {
                $this->telegram->answerCallbackQuery($callbackId, '❌ هنوز عضو کانال نشده‌اید.');
                $this->telegram->editMessageText($chatId, $messageId, $this->channelLockText(), $this->channelLockKeyboard());
            }
            return;
        }

        if (!$this->checkChannelMembership($userId)) {
            $this->telegram->answerCallbackQuery($callbackId);
            $this->telegram->editMessageText($chatId, $messageId, $this->channelLockText(), $this->channelLockKeyboard());
            return;
        }

        if (str_starts_with($data, 'admin:')) {
            if (!$isAdmin) {
                $this->telegram->answerCallbackQuery($callbackId, 'شما دسترسی ادمین ندارید.');
                return;
            }
            $requiredPerm = $this->requiredAdminPermission($data);
            if ($requiredPerm !== null && !$this->adminHasPermission($userId, $requiredPerm)) {
                $this->telegram->answerCallbackQuery($callbackId, 'مجوز لازم برای این بخش را ندارید.');
                return;
            }
        }

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
            $items = $this->database->listWaitingAdminPayments();
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
                    'text' => sprintf('#%d | %s | U:%d | %d تومان', (int) $item['id'], (string) $item['kind'], (int) $item['user_id'], (int) $item['amount']),
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

        if ($data === 'admin:admins') {
            if (!$isAdmin) {
                $this->telegram->answerCallbackQuery($callbackId, 'شما دسترسی ادمین ندارید.');
                return;
            }
            $rows = [];
            $rows[] = [['text' => '➕ افزودن ادمین', 'callback_data' => 'admin:admins:add']];
            foreach ($this->database->listAdminUsers() as $adm) {
                $uid = (int) ($adm['user_id'] ?? 0);
                $rows[] = [[
                    'text' => "👮 U:{$uid}",
                    'callback_data' => 'admin:admins:view:' . $uid,
                ]];
            }
            foreach (Config::adminIds() as $ownerId) {
                $rows[] = [[
                    'text' => "👑 OWNER {$ownerId}",
                    'callback_data' => 'noop',
                ]];
            }
            $rows[] = [['text' => '🔙 بازگشت', 'callback_data' => 'admin:panel']];
            $this->telegram->editMessageText($chatId, $messageId, '👮 <b>مدیریت ادمین‌ها</b>', ['inline_keyboard' => $rows]);
            $this->telegram->answerCallbackQuery($callbackId);
            return;
        }

        if ($data === 'admin:admins:add') {
            if (!$isAdmin) {
                $this->telegram->answerCallbackQuery($callbackId, 'شما دسترسی ادمین ندارید.');
                return;
            }
            $this->database->setUserState($userId, 'await_admin_add_admin');
            $this->telegram->editMessageText(
                $chatId,
                $messageId,
                "📝 آیدی عددی ادمین جدید را ارسال کنید.",
                ['inline_keyboard' => [[['text' => '🔙 بازگشت', 'callback_data' => 'admin:admins']]]]
            );
            $this->telegram->answerCallbackQuery($callbackId);
            return;
        }

        if (str_starts_with($data, 'admin:admins:view:')) {
            if (!$isAdmin) {
                $this->telegram->answerCallbackQuery($callbackId, 'شما دسترسی ادمین ندارید.');
                return;
            }
            $targetUid = (int) substr($data, strlen('admin:admins:view:'));
            $perms = $this->database->getAdminPermissions($targetUid);
            if (in_array($targetUid, Config::adminIds(), true)) {
                $this->telegram->answerCallbackQuery($callbackId, 'این کاربر owner است و قابل ویرایش نیست.');
                return;
            }
            $keys = ['types', 'stock', 'users', 'settings', 'payments', 'requests', 'broadcast', 'agents', 'panels'];
            $rows = [];
            foreach ($keys as $k) {
                $enabled = (bool) ($perms[$k] ?? false);
                $rows[] = [[
                    'text' => ($enabled ? '✅ ' : '❌ ') . $k,
                    'callback_data' => 'admin:admins:perm:' . $targetUid . ':' . $k . ':' . ($enabled ? 0 : 1),
                ]];
            }
            $rows[] = [['text' => '🗑 حذف ادمین', 'callback_data' => 'admin:admins:remove:' . $targetUid]];
            $rows[] = [['text' => '🔙 بازگشت', 'callback_data' => 'admin:admins']];
            $this->telegram->editMessageText(
                $chatId,
                $messageId,
                "👮 <b>دسترسی‌های ادمین</b>\n\nآیدی: <code>{$targetUid}</code>",
                ['inline_keyboard' => $rows]
            );
            $this->telegram->answerCallbackQuery($callbackId);
            return;
        }

        if (str_starts_with($data, 'admin:admins:perm:')) {
            if (!$isAdmin) {
                $this->telegram->answerCallbackQuery($callbackId, 'شما دسترسی ادمین ندارید.');
                return;
            }
            $parts = explode(':', $data);
            $targetUid = (int) ($parts[3] ?? 0);
            $permKey = (string) ($parts[4] ?? '');
            $enabled = ((int) ($parts[5] ?? 0)) === 1;
            if ($targetUid > 0 && $permKey !== '') {
                $perms = $this->database->getAdminPermissions($targetUid);
                $perms[$permKey] = $enabled;
                $this->database->upsertAdminUser($targetUid, $userId, $perms);
            }
            $this->telegram->answerCallbackQuery($callbackId, '✅ ذخیره شد.');
            $this->handle(['callback_query' => ['id' => $callbackId, 'from' => $fromUser, 'message' => $message, 'data' => 'admin:admins:view:' . $targetUid]]);
            return;
        }

        if (str_starts_with($data, 'admin:admins:remove:')) {
            if (!$isAdmin) {
                $this->telegram->answerCallbackQuery($callbackId, 'شما دسترسی ادمین ندارید.');
                return;
            }
            $targetUid = (int) substr($data, strlen('admin:admins:remove:'));
            if (!in_array($targetUid, Config::adminIds(), true)) {
                $this->database->removeAdminUser($targetUid);
            }
            $this->telegram->answerCallbackQuery($callbackId, '✅ حذف شد.');
            $this->handle(['callback_query' => ['id' => $callbackId, 'from' => $fromUser, 'message' => $message, 'data' => 'admin:admins']]);
            return;
        }


        if ($data === 'admin:pins') {
            if (!$isAdmin) {
                $this->telegram->answerCallbackQuery($callbackId, 'شما دسترسی ادمین ندارید.');
                return;
            }
            $rows = [[['text' => '➕ افزودن پیام پین', 'callback_data' => 'admin:pins:add']]];
            foreach ($this->database->listPinnedMessages() as $pin) {
                $pinId = (int) ($pin['id'] ?? 0);
                $rows[] = [[
                    'text' => '📌 #' . $pinId,
                    'callback_data' => 'admin:pins:view:' . $pinId,
                ]];
            }
            $rows[] = [['text' => '🔙 بازگشت', 'callback_data' => 'admin:panel']];
            $this->telegram->editMessageText($chatId, $messageId, '📌 <b>مدیریت پیام‌های پین</b>', ['inline_keyboard' => $rows]);
            $this->telegram->answerCallbackQuery($callbackId);
            return;
        }

        if ($data === 'admin:pins:add') {
            if (!$isAdmin) {
                $this->telegram->answerCallbackQuery($callbackId, 'شما دسترسی ادمین ندارید.');
                return;
            }
            $this->database->setUserState($userId, 'await_admin_pin_add');
            $this->telegram->editMessageText(
                $chatId,
                $messageId,
                "📝 متن پیام پین را ارسال کنید.",
                ['inline_keyboard' => [[['text' => '🔙 بازگشت', 'callback_data' => 'admin:pins']]]]
            );
            $this->telegram->answerCallbackQuery($callbackId);
            return;
        }

        if (str_starts_with($data, 'admin:pins:view:')) {
            if (!$isAdmin) {
                $this->telegram->answerCallbackQuery($callbackId, 'شما دسترسی ادمین ندارید.');
                return;
            }
            $pinId = (int) substr($data, strlen('admin:pins:view:'));
            $pin = $this->database->getPinnedMessage($pinId);
            if ($pin === null) {
                $this->telegram->answerCallbackQuery($callbackId, 'پیام پین پیدا نشد.');
                return;
            }
            $sendCount = count($this->database->getPinnedSends($pinId));
            $rows = [
                [['text' => '📤 ارسال به همه کاربران', 'callback_data' => 'admin:pins:send:' . $pinId]],
                [['text' => '✏️ ویرایش', 'callback_data' => 'admin:pins:edit:' . $pinId]],
                [['text' => '🗑 حذف', 'callback_data' => 'admin:pins:delete:' . $pinId]],
                [['text' => '🔙 بازگشت', 'callback_data' => 'admin:pins']],
            ];
            $this->telegram->editMessageText(
                $chatId,
                $messageId,
                "📌 <b>پیام پین #{$pinId}</b>

" . htmlspecialchars((string) ($pin['text'] ?? '')) . "

ارسال‌شده: <b>{$sendCount}</b>",
                ['inline_keyboard' => $rows]
            );
            $this->telegram->answerCallbackQuery($callbackId);
            return;
        }

        if (str_starts_with($data, 'admin:pins:edit:')) {
            if (!$isAdmin) {
                $this->telegram->answerCallbackQuery($callbackId, 'شما دسترسی ادمین ندارید.');
                return;
            }
            $pinId = (int) substr($data, strlen('admin:pins:edit:'));
            $this->database->setUserState($userId, 'await_admin_pin_edit', ['pin_id' => $pinId]);
            $this->telegram->editMessageText(
                $chatId,
                $messageId,
                "📝 متن جدید پیام پین را ارسال کنید.",
                ['inline_keyboard' => [[['text' => '🔙 بازگشت', 'callback_data' => 'admin:pins:view:' . $pinId]]]]
            );
            $this->telegram->answerCallbackQuery($callbackId);
            return;
        }

        if (str_starts_with($data, 'admin:pins:delete:')) {
            if (!$isAdmin) {
                $this->telegram->answerCallbackQuery($callbackId, 'شما دسترسی ادمین ندارید.');
                return;
            }
            $pinId = (int) substr($data, strlen('admin:pins:delete:'));
            $this->database->deletePinnedMessage($pinId);
            $this->telegram->answerCallbackQuery($callbackId, '✅ حذف شد.');
            $this->handle(['callback_query' => ['id' => $callbackId, 'from' => $fromUser, 'message' => $message, 'data' => 'admin:pins']]);
            return;
        }

        if (str_starts_with($data, 'admin:pins:send:')) {
            if (!$isAdmin) {
                $this->telegram->answerCallbackQuery($callbackId, 'شما دسترسی ادمین ندارید.');
                return;
            }
            $pinId = (int) substr($data, strlen('admin:pins:send:'));
            $pin = $this->database->getPinnedMessage($pinId);
            if ($pin === null) {
                $this->telegram->answerCallbackQuery($callbackId, 'پیام پین پیدا نشد.');
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
            $this->telegram->answerCallbackQuery($callbackId, '✅ ارسال انجام شد.');
            $this->telegram->editMessageText(
                $chatId,
                $messageId,
                "✅ پیام پین ارسال شد.
📤 ارسال: <b>{$sent}</b>
📌 پین: <b>{$pinned}</b>",
                ['inline_keyboard' => [[['text' => '🔙 بازگشت', 'callback_data' => 'admin:pins:view:' . $pinId]]]]
            );
            return;
        }

        if ($data === 'admin:broadcast') {
            if (!$isAdmin) {
                $this->telegram->answerCallbackQuery($callbackId, 'شما دسترسی ادمین ندارید.');
                return;
            }
            $this->telegram->editMessageText(
                $chatId,
                $messageId,
                "📣 <b>فوروارد همگانی</b>\n\nگروه هدف را انتخاب کنید:",
                ['inline_keyboard' => [
                    [['text' => 'همه کاربران', 'callback_data' => 'admin:broadcast:set:all']],
                    [['text' => 'فقط مشتریان', 'callback_data' => 'admin:broadcast:set:customers']],
                    [['text' => 'فقط نمایندگان', 'callback_data' => 'admin:broadcast:set:agents']],
                    [['text' => 'فقط ادمین‌ها', 'callback_data' => 'admin:broadcast:set:admins']],
                    [['text' => '🔙 بازگشت', 'callback_data' => 'admin:panel']],
                ]]
            );
            $this->telegram->answerCallbackQuery($callbackId);
            return;
        }

        if (str_starts_with($data, 'admin:broadcast:set:')) {
            if (!$isAdmin) {
                $this->telegram->answerCallbackQuery($callbackId, 'شما دسترسی ادمین ندارید.');
                return;
            }
            $scope = (string) substr($data, strlen('admin:broadcast:set:'));
            if (!in_array($scope, ['all', 'customers', 'agents', 'admins'], true)) {
                $scope = 'all';
            }
            $this->database->setUserState($userId, 'await_admin_broadcast', ['scope' => $scope]);
            $this->telegram->editMessageText(
                $chatId,
                $messageId,
                "📤 پیام/فایل را ارسال کنید تا برای گروه هدف (<b>{$scope}</b>) ارسال شود.",
                ['inline_keyboard' => [[['text' => '🔙 بازگشت', 'callback_data' => 'admin:broadcast']]]]
            );
            $this->telegram->answerCallbackQuery($callbackId);
            return;
        }

        if ($data === 'admin:types') {
            if (!$isAdmin) {
                $this->telegram->answerCallbackQuery($callbackId, 'شما دسترسی ادمین ندارید.');
                return;
            }
            $types = $this->database->listTypes();
            $rows = [];
            $rows[] = [['text' => '➕ افزودن نوع سرویس', 'callback_data' => 'admin:type:add']];
            foreach ($types as $type) {
                $activeMark = ((int) ($type['is_active'] ?? 0)) === 1 ? '🟢' : '🔴';
                $rows[] = [[
                    'text' => "{$activeMark} " . (string) ($type['name'] ?? '-') . ' #' . (int) ($type['id'] ?? 0),
                    'callback_data' => 'admin:type:view:' . (int) ($type['id'] ?? 0),
                ]];
            }
            $rows[] = [['text' => '🔙 بازگشت', 'callback_data' => 'admin:panel']];
            $this->telegram->editMessageText(
                $chatId,
                $messageId,
                '🧩 <b>مدیریت نوع سرویس و پکیج‌ها</b>',
                ['inline_keyboard' => $rows]
            );
            $this->telegram->answerCallbackQuery($callbackId);
            return;
        }

        if ($data === 'admin:type:add') {
            if (!$isAdmin) {
                $this->telegram->answerCallbackQuery($callbackId, 'شما دسترسی ادمین ندارید.');
                return;
            }
            $this->database->setUserState($userId, 'await_admin_type_name', [
                'source_chat_id' => $chatId,
                'source_message_id' => $messageId,
            ]);
            $this->telegram->editMessageText(
                $chatId,
                $messageId,
                "📝 نام نوع سرویس جدید را ارسال کنید.\nمثال: <code>VLESS EU</code>",
                ['inline_keyboard' => [[['text' => '🔙 بازگشت', 'callback_data' => 'admin:types']]]
                ]
            );
            $this->telegram->answerCallbackQuery($callbackId);
            return;
        }

        if (str_starts_with($data, 'admin:type:view:')) {
            if (!$isAdmin) {
                $this->telegram->answerCallbackQuery($callbackId, 'شما دسترسی ادمین ندارید.');
                return;
            }
            $typeId = (int) substr($data, strlen('admin:type:view:'));
            $type = null;
            foreach ($this->database->listTypes() as $item) {
                if ((int) ($item['id'] ?? 0) === $typeId) {
                    $type = $item;
                    break;
                }
            }
            if (!is_array($type)) {
                $this->telegram->answerCallbackQuery($callbackId, 'نوع سرویس یافت نشد.');
                return;
            }

            $packages = $this->database->listPackagesByType($typeId);
            $rows = [];
            $rows[] = [['text' => '➕ افزودن پکیج', 'callback_data' => 'admin:pkg:add:' . $typeId]];
            $rows[] = [[
                'text' => ((int) ($type['is_active'] ?? 0) === 1 ? '🔴 غیرفعال کردن نوع' : '🟢 فعال کردن نوع'),
                'callback_data' => 'admin:type:toggle:' . $typeId . ':' . (((int) ($type['is_active'] ?? 0) === 1) ? 0 : 1),
            ]];
            $rows[] = [['text' => '🗑 حذف نوع سرویس', 'callback_data' => 'admin:type:delete:' . $typeId]];
            foreach ($packages as $pkg) {
                $mark = ((int) ($pkg['active'] ?? 0) === 1) ? '🟢' : '🔴';
                $rows[] = [[
                    'text' => sprintf(
                        '%s #%d | %s | %sGB | %s روز | %s تومان',
                        $mark,
                        (int) ($pkg['id'] ?? 0),
                        (string) ($pkg['name'] ?? '-'),
                        (string) ($pkg['volume_gb'] ?? '0'),
                        (string) ($pkg['duration_days'] ?? '0'),
                        (string) ($pkg['price'] ?? '0')
                    ),
                    'callback_data' => 'admin:pkg:view:' . (int) ($pkg['id'] ?? 0) . ':' . $typeId,
                ]];
            }
            $rows[] = [['text' => '🔙 بازگشت', 'callback_data' => 'admin:types']];
            $this->telegram->editMessageText(
                $chatId,
                $messageId,
                "📦 <b>نوع سرویس:</b> " . htmlspecialchars((string) ($type['name'] ?? '-')) . "\n"
                . "شناسه: <code>{$typeId}</code>",
                ['inline_keyboard' => $rows]
            );
            $this->telegram->answerCallbackQuery($callbackId);
            return;
        }

        if (str_starts_with($data, 'admin:type:toggle:')) {
            if (!$isAdmin) {
                $this->telegram->answerCallbackQuery($callbackId, 'شما دسترسی ادمین ندارید.');
                return;
            }
            $parts = explode(':', $data);
            $typeId = (int) ($parts[3] ?? 0);
            $active = ((int) ($parts[4] ?? 0)) === 1;
            if ($typeId > 0) {
                $this->database->setTypeActive($typeId, $active);
            }
            $this->telegram->answerCallbackQuery($callbackId, '✅ ذخیره شد.');
            $this->handle(['callback_query' => [
                'id' => $callbackId,
                'from' => $fromUser,
                'message' => $message,
                'data' => 'admin:type:view:' . $typeId,
            ]]);
            return;
        }

        if (str_starts_with($data, 'admin:type:delete:')) {
            if (!$isAdmin) {
                $this->telegram->answerCallbackQuery($callbackId, 'شما دسترسی ادمین ندارید.');
                return;
            }
            $typeId = (int) substr($data, strlen('admin:type:delete:'));
            if ($typeId > 0) {
                $this->database->deleteType($typeId);
            }
            $this->telegram->answerCallbackQuery($callbackId, '✅ نوع سرویس حذف شد.');
            $this->handle(['callback_query' => [
                'id' => $callbackId,
                'from' => $fromUser,
                'message' => $message,
                'data' => 'admin:types',
            ]]);
            return;
        }

        if (str_starts_with($data, 'admin:pkg:add:')) {
            if (!$isAdmin) {
                $this->telegram->answerCallbackQuery($callbackId, 'شما دسترسی ادمین ندارید.');
                return;
            }
            $typeId = (int) substr($data, strlen('admin:pkg:add:'));
            $this->database->setUserState($userId, 'await_admin_package', [
                'type_id' => $typeId,
                'source_chat_id' => $chatId,
                'source_message_id' => $messageId,
            ]);
            $this->telegram->editMessageText(
                $chatId,
                $messageId,
                "📝 اطلاعات پکیج را با فرمت زیر ارسال کنید:\n"
                . "<code>نام | حجم(GB) | مدت(روز) | قیمت(تومان)</code>\n\n"
                . "مثال:\n<code>پلن طلایی | 120 | 30 | 250000</code>",
                ['inline_keyboard' => [[['text' => '🔙 بازگشت', 'callback_data' => 'admin:type:view:' . $typeId]]]]
            );
            $this->telegram->answerCallbackQuery($callbackId);
            return;
        }

        if (str_starts_with($data, 'admin:pkg:view:')) {
            if (!$isAdmin) {
                $this->telegram->answerCallbackQuery($callbackId, 'شما دسترسی ادمین ندارید.');
                return;
            }
            $parts = explode(':', $data);
            $packageId = (int) ($parts[3] ?? 0);
            $typeId = (int) ($parts[4] ?? 0);
            $pkg = $this->database->getPackage($packageId);
            if (!is_array($pkg)) {
                $this->telegram->answerCallbackQuery($callbackId, 'پکیج یافت نشد.');
                return;
            }
            $active = 1;
            foreach ($this->database->listPackagesByType((int) $pkg['type_id']) as $p) {
                if ((int) $p['id'] === $packageId) {
                    $active = (int) $p['active'];
                    break;
                }
            }
            $this->telegram->editMessageText(
                $chatId,
                $messageId,
                "📦 <b>جزئیات پکیج</b>\n\n"
                . "شناسه: <code>{$packageId}</code>\n"
                . "نام: <b>" . htmlspecialchars((string) ($pkg['name'] ?? '-')) . "</b>\n"
                . "حجم: <b>" . htmlspecialchars((string) ($pkg['volume_gb'] ?? '0')) . " GB</b>\n"
                . "مدت: <b>" . htmlspecialchars((string) ($pkg['duration_days'] ?? '0')) . " روز</b>\n"
                . "قیمت: <b>" . (int) ($pkg['price'] ?? 0) . "</b> تومان\n"
                . "وضعیت: <b>" . ($active === 1 ? 'فعال' : 'غیرفعال') . "</b>",
                ['inline_keyboard' => [
                    [[
                        'text' => $active === 1 ? '🔴 غیرفعال کردن' : '🟢 فعال کردن',
                        'callback_data' => 'admin:pkg:toggle:' . $packageId . ':' . ($active === 1 ? 0 : 1) . ':' . ((int) $pkg['type_id']),
                    ]],
                    [[
                        'text' => '🗑 حذف پکیج',
                        'callback_data' => 'admin:pkg:delete:' . $packageId . ':' . ((int) $pkg['type_id']),
                    ]],
                    [[
                        'text' => '🔙 بازگشت',
                        'callback_data' => 'admin:type:view:' . ($typeId > 0 ? $typeId : (int) $pkg['type_id']),
                    ]],
                ]]
            );
            $this->telegram->answerCallbackQuery($callbackId);
            return;
        }

        if (str_starts_with($data, 'admin:pkg:toggle:')) {
            if (!$isAdmin) {
                $this->telegram->answerCallbackQuery($callbackId, 'شما دسترسی ادمین ندارید.');
                return;
            }
            $parts = explode(':', $data);
            $packageId = (int) ($parts[3] ?? 0);
            $active = ((int) ($parts[4] ?? 0)) === 1;
            $typeId = (int) ($parts[5] ?? 0);
            if ($packageId > 0) {
                $this->database->setPackageActive($packageId, $active);
            }
            $this->telegram->answerCallbackQuery($callbackId, '✅ ذخیره شد.');
            $this->handle(['callback_query' => [
                'id' => $callbackId,
                'from' => $fromUser,
                'message' => $message,
                'data' => 'admin:type:view:' . $typeId,
            ]]);
            return;
        }

        if (str_starts_with($data, 'admin:pkg:delete:')) {
            if (!$isAdmin) {
                $this->telegram->answerCallbackQuery($callbackId, 'شما دسترسی ادمین ندارید.');
                return;
            }
            $parts = explode(':', $data);
            $packageId = (int) ($parts[3] ?? 0);
            $typeId = (int) ($parts[4] ?? 0);
            if ($packageId > 0) {
                $this->database->deletePackage($packageId);
            }
            $this->telegram->answerCallbackQuery($callbackId, '✅ پکیج حذف شد.');
            $this->handle(['callback_query' => [
                'id' => $callbackId,
                'from' => $fromUser,
                'message' => $message,
                'data' => 'admin:type:view:' . $typeId,
            ]]);
            return;
        }

        if ($data === 'admin:users') {
            if (!$isAdmin) {
                $this->telegram->answerCallbackQuery($callbackId, 'شما دسترسی ادمین ندارید.');
                return;
            }
            $users = $this->database->listUsers(25);
            $rows = [];
            foreach ($users as $u) {
                $rows[] = [[
                    'text' => sprintf(
                        '%s U:%d | %s | %d تومان',
                        ((string) ($u['status'] ?? '') === 'restricted') ? '🚫' : '✅',
                        (int) ($u['user_id'] ?? 0),
                        (string) (($u['full_name'] ?? '') !== '' ? $u['full_name'] : ($u['username'] ?? '-')),
                        (int) ($u['balance'] ?? 0)
                    ),
                    'callback_data' => 'admin:user:view:' . (int) ($u['user_id'] ?? 0),
                ]];
            }
            $rows[] = [['text' => '🔙 بازگشت', 'callback_data' => 'admin:panel']];
            $this->telegram->editMessageText(
                $chatId,
                $messageId,
                '👥 <b>مدیریت کاربران</b>',
                ['inline_keyboard' => $rows]
            );
            $this->telegram->answerCallbackQuery($callbackId);
            return;
        }

        if ($data === 'admin:stock') {
            if (!$isAdmin) {
                $this->telegram->answerCallbackQuery($callbackId, 'شما دسترسی ادمین ندارید.');
                return;
            }
            $types = $this->database->listTypes();
            $rows = [];
            foreach ($types as $type) {
                $rows[] = [[
                    'text' => '🗂 ' . (string) ($type['name'] ?? '-') . ' #' . (int) ($type['id'] ?? 0),
                    'callback_data' => 'admin:stock:type:' . (int) ($type['id'] ?? 0),
                ]];
            }
            $rows[] = [['text' => '🔙 بازگشت', 'callback_data' => 'admin:panel']];
            $this->telegram->editMessageText(
                $chatId,
                $messageId,
                '📚 <b>مدیریت موجودی کانفیگ</b>' . "\n\n" . 'ابتدا نوع سرویس را انتخاب کنید:',
                ['inline_keyboard' => $rows]
            );
            $this->telegram->answerCallbackQuery($callbackId);
            return;
        }

        if (str_starts_with($data, 'admin:stock:type:')) {
            if (!$isAdmin) {
                $this->telegram->answerCallbackQuery($callbackId, 'شما دسترسی ادمین ندارید.');
                return;
            }
            $typeId = (int) substr($data, strlen('admin:stock:type:'));
            $packages = $this->database->listPackagesByType($typeId);
            $rows = [];
            foreach ($packages as $pkg) {
                $available = $this->database->countAvailableConfigsForPackage((int) ($pkg['id'] ?? 0));
                $rows[] = [[
                    'text' => sprintf(
                        '📦 #%d %s | موجودی: %d',
                        (int) ($pkg['id'] ?? 0),
                        (string) ($pkg['name'] ?? '-'),
                        $available
                    ),
                    'callback_data' => 'admin:stock:pkg:' . (int) ($pkg['id'] ?? 0) . ':' . $typeId,
                ]];
            }
            $rows[] = [['text' => '🔙 بازگشت', 'callback_data' => 'admin:stock']];
            $this->telegram->editMessageText(
                $chatId,
                $messageId,
                "📦 <b>پکیج‌های نوع {$typeId}</b>",
                ['inline_keyboard' => $rows]
            );
            $this->telegram->answerCallbackQuery($callbackId);
            return;
        }

        if (str_starts_with($data, 'admin:stock:pkg:')) {
            if (!$isAdmin) {
                $this->telegram->answerCallbackQuery($callbackId, 'شما دسترسی ادمین ندارید.');
                return;
            }
            $parts = explode(':', $data);
            $packageId = (int) ($parts[3] ?? 0);
            $typeId = (int) ($parts[4] ?? 0);
            $page = max(1, (int) ($parts[5] ?? 1));
            $statusToken = (string) ($parts[6] ?? 'all');
            $status = match ($statusToken) {
                'av' => 'available',
                'sl' => 'sold',
                'ex' => 'expired',
                default => 'all',
            };
            $query = $this->decodeStockQueryToken((string) ($parts[7] ?? ''));
            $perPage = 15;
            $total = $this->database->countConfigsByPackageFiltered($packageId, $status, $query);
            $totalPages = max(1, (int) ceil($total / $perPage));
            if ($page > $totalPages) {
                $page = $totalPages;
            }
            $offset = ($page - 1) * $perPage;
            $configs = $this->database->listConfigsByPackageFiltered($packageId, $status, $query, $perPage, $offset);
            $available = $this->database->countAvailableConfigsForPackage($packageId);
            $qToken = $this->encodeStockQueryToken($query);
            $statusToken = match ($status) {
                'available' => 'av',
                'sold' => 'sl',
                'expired' => 'ex',
                default => 'all',
            };
            $rows = [];
            $rows[] = [['text' => '➕ افزودن کانفیگ', 'callback_data' => 'admin:stock:add:' . $packageId . ':' . $typeId]];
            $rows[] = [[
                'text' => '🔎 جستجو (name/config/inquiry)',
                'callback_data' => 'admin:stock:search:' . $packageId . ':' . $typeId . ':' . $statusToken . ':' . $qToken,
            ]];
            if ($query !== null && trim($query) !== '') {
                $rows[] = [[
                    'text' => '🧹 پاک‌کردن جستجو',
                    'callback_data' => 'admin:stock:pkg:' . $packageId . ':' . $typeId . ':1:' . $statusToken . ':',
                ]];
            }
            $rows[] = [
                [
                    'text' => ($status === 'all' ? '✅ ' : '') . 'همه',
                    'callback_data' => 'admin:stock:pkg:' . $packageId . ':' . $typeId . ':1:all:' . $qToken,
                ],
                [
                    'text' => ($status === 'available' ? '✅ ' : '') . 'موجود',
                    'callback_data' => 'admin:stock:pkg:' . $packageId . ':' . $typeId . ':1:av:' . $qToken,
                ],
                [
                    'text' => ($status === 'sold' ? '✅ ' : '') . 'فروخته',
                    'callback_data' => 'admin:stock:pkg:' . $packageId . ':' . $typeId . ':1:sl:' . $qToken,
                ],
                [
                    'text' => ($status === 'expired' ? '✅ ' : '') . 'منقضی',
                    'callback_data' => 'admin:stock:pkg:' . $packageId . ':' . $typeId . ':1:ex:' . $qToken,
                ],
            ];
            $rows[] = [[
                'text' => '❌ منقضی‌کردن موجودهای این صفحه',
                'callback_data' => 'admin:stock:bulkexp:' . $packageId . ':' . $typeId . ':' . $page . ':' . $statusToken . ':' . $qToken,
            ]];
            $rows[] = [[
                'text' => '🗑 حذف موجودهای این صفحه',
                'callback_data' => 'admin:stock:bulkdel:' . $packageId . ':' . $typeId . ':' . $page . ':' . $statusToken . ':' . $qToken,
            ]];
            foreach ($configs as $cfg) {
                $state = ((int) ($cfg['is_expired'] ?? 0) === 1) ? '❌' : (((int) ($cfg['sold_to'] ?? 0) > 0) ? '🔴' : '🟢');
                $rows[] = [[
                    'text' => sprintf('%s #%d %s', $state, (int) ($cfg['id'] ?? 0), (string) ($cfg['service_name'] ?? '-')),
                    'callback_data' => 'admin:stock:view:' . (int) ($cfg['id'] ?? 0) . ':' . $packageId . ':' . $typeId,
                ]];
            }
            $nav = [];
            if ($page > 1) {
                $nav[] = ['text' => '⬅️ قبلی', 'callback_data' => 'admin:stock:pkg:' . $packageId . ':' . $typeId . ':' . ($page - 1) . ':' . $statusToken . ':' . $qToken];
            }
            $nav[] = ['text' => "📄 {$page}/{$totalPages}", 'callback_data' => 'noop'];
            if ($page < $totalPages) {
                $nav[] = ['text' => 'بعدی ➡️', 'callback_data' => 'admin:stock:pkg:' . $packageId . ':' . $typeId . ':' . ($page + 1) . ':' . $statusToken . ':' . $qToken];
            }
            $rows[] = $nav;
            $rows[] = [['text' => '🔙 بازگشت', 'callback_data' => 'admin:stock:type:' . $typeId]];
            $statusFa = match ($status) {
                'available' => 'موجود',
                'sold' => 'فروخته',
                'expired' => 'منقضی',
                default => 'همه',
            };
            $this->telegram->editMessageText(
                $chatId,
                $messageId,
                "📚 <b>کانفیگ‌های پکیج {$packageId}</b>\n"
                . "فیلتر وضعیت: <b>{$statusFa}</b>\n"
                . ($query !== null && trim($query) !== '' ? "عبارت جستجو: <code>" . htmlspecialchars($query) . "</code>\n" : '')
                . "موجودی آزاد: <b>{$available}</b>\n"
                . "کل آیتم‌ها: <b>{$total}</b>\n"
                . "راهنما: 🟢 موجود | 🔴 فروخته | ❌ منقضی",
                ['inline_keyboard' => $rows]
            );
            $this->telegram->answerCallbackQuery($callbackId);
            return;
        }

        if (str_starts_with($data, 'admin:stock:search:')) {
            if (!$isAdmin) {
                $this->telegram->answerCallbackQuery($callbackId, 'شما دسترسی ادمین ندارید.');
                return;
            }
            $parts = explode(':', $data);
            $packageId = (int) ($parts[3] ?? 0);
            $typeId = (int) ($parts[4] ?? 0);
            $statusToken = (string) ($parts[5] ?? 'all');
            $qToken = (string) ($parts[6] ?? '');
            $currentQuery = $this->decodeStockQueryToken($qToken);
            $this->database->setUserState($userId, 'await_admin_stock_search', [
                'package_id' => $packageId,
                'type_id' => $typeId,
                'status_token' => $statusToken,
            ]);
            $this->telegram->editMessageText(
                $chatId,
                $messageId,
                "🔎 عبارت جستجو را ارسال کنید.\n"
                . "روی `name` / `config_text` / `inquiry_link` جستجو می‌شود.\n\n"
                . ($currentQuery !== null && trim($currentQuery) !== '' ? "جستجوی فعلی: <code>" . htmlspecialchars($currentQuery) . "</code>" : "برای پاک کردن، یک پیام خالی یا `-` بفرست."),
                ['inline_keyboard' => [[['text' => '🔙 بازگشت', 'callback_data' => 'admin:stock:pkg:' . $packageId . ':' . $typeId . ':1:' . $statusToken . ':' . $qToken]]]]
            );
            $this->telegram->answerCallbackQuery($callbackId);
            return;
        }

        if (str_starts_with($data, 'admin:stock:add:')) {
            if (!$isAdmin) {
                $this->telegram->answerCallbackQuery($callbackId, 'شما دسترسی ادمین ندارید.');
                return;
            }
            $parts = explode(':', $data);
            $packageId = (int) ($parts[3] ?? 0);
            $typeId = (int) ($parts[4] ?? 0);
            $this->database->setUserState($userId, 'await_admin_add_config', [
                'package_id' => $packageId,
                'type_id' => $typeId,
            ]);
            $this->telegram->editMessageText(
                $chatId,
                $messageId,
                "📝 کانفیگ جدید را با فرمت زیر بفرستید:\n\n"
                . "<code>نام سرویس</code>\n"
                . "<code>---</code>\n"
                . "<code>متن کانفیگ</code>\n"
                . "<code>---</code>\n"
                . "<code>inquiry لینک-استعلام (اختیاری)</code>",
                ['inline_keyboard' => [[['text' => '🔙 بازگشت', 'callback_data' => 'admin:stock:pkg:' . $packageId . ':' . $typeId]]]]
            );
            $this->telegram->answerCallbackQuery($callbackId);
            return;
        }

        if (str_starts_with($data, 'admin:stock:bulkexp:')) {
            if (!$isAdmin) {
                $this->telegram->answerCallbackQuery($callbackId, 'شما دسترسی ادمین ندارید.');
                return;
            }
            $parts = explode(':', $data);
            $packageId = (int) ($parts[3] ?? 0);
            $typeId = (int) ($parts[4] ?? 0);
            $page = max(1, (int) ($parts[5] ?? 1));
            $statusToken = (string) ($parts[6] ?? 'all');
            $status = match ($statusToken) {
                'av' => 'available',
                'sl' => 'sold',
                'ex' => 'expired',
                default => 'all',
            };
            $qToken = (string) ($parts[7] ?? '');
            $query = $this->decodeStockQueryToken($qToken);
            $perPage = 15;
            $offset = ($page - 1) * $perPage;
            $configs = $this->database->listConfigsByPackageFiltered($packageId, $status, $query, $perPage, $offset);
            $n = 0;
            foreach ($configs as $cfg) {
                if ((int) ($cfg['sold_to'] ?? 0) === 0 && (int) ($cfg['is_expired'] ?? 0) === 0) {
                    $this->database->expireConfig((int) ($cfg['id'] ?? 0));
                    $n++;
                }
            }
            $this->telegram->answerCallbackQuery($callbackId, "✅ {$n} مورد منقضی شد.");
            $this->handle(['callback_query' => [
                'id' => $callbackId,
                'from' => $fromUser,
                'message' => $message,
                'data' => 'admin:stock:pkg:' . $packageId . ':' . $typeId . ':' . $page . ':' . $statusToken . ':' . $qToken,
            ]]);
            return;
        }

        if (str_starts_with($data, 'admin:stock:bulkdel:')) {
            if (!$isAdmin) {
                $this->telegram->answerCallbackQuery($callbackId, 'شما دسترسی ادمین ندارید.');
                return;
            }
            $parts = explode(':', $data);
            $packageId = (int) ($parts[3] ?? 0);
            $typeId = (int) ($parts[4] ?? 0);
            $page = max(1, (int) ($parts[5] ?? 1));
            $statusToken = (string) ($parts[6] ?? 'all');
            $status = match ($statusToken) {
                'av' => 'available',
                'sl' => 'sold',
                'ex' => 'expired',
                default => 'all',
            };
            $qToken = (string) ($parts[7] ?? '');
            $query = $this->decodeStockQueryToken($qToken);
            $perPage = 15;
            $offset = ($page - 1) * $perPage;
            $configs = $this->database->listConfigsByPackageFiltered($packageId, $status, $query, $perPage, $offset);
            $n = 0;
            foreach ($configs as $cfg) {
                if ((int) ($cfg['sold_to'] ?? 0) === 0) {
                    if ($this->database->deleteConfig((int) ($cfg['id'] ?? 0))) {
                        $n++;
                    }
                }
            }
            $this->telegram->answerCallbackQuery($callbackId, "✅ {$n} مورد حذف شد.");
            $this->handle(['callback_query' => [
                'id' => $callbackId,
                'from' => $fromUser,
                'message' => $message,
                'data' => 'admin:stock:pkg:' . $packageId . ':' . $typeId . ':' . $page . ':' . $statusToken . ':' . $qToken,
            ]]);
            return;
        }

        if (str_starts_with($data, 'admin:stock:view:')) {
            if (!$isAdmin) {
                $this->telegram->answerCallbackQuery($callbackId, 'شما دسترسی ادمین ندارید.');
                return;
            }
            $parts = explode(':', $data);
            $configId = (int) ($parts[3] ?? 0);
            $packageId = (int) ($parts[4] ?? 0);
            $typeId = (int) ($parts[5] ?? 0);
            $cfg = null;
            foreach ($this->database->listConfigsByPackage($packageId, 100, 0) as $item) {
                if ((int) ($item['id'] ?? 0) === $configId) {
                    $cfg = $item;
                    break;
                }
            }
            if (!is_array($cfg)) {
                $this->telegram->answerCallbackQuery($callbackId, 'کانفیگ یافت نشد.');
                return;
            }
            $state = ((int) ($cfg['is_expired'] ?? 0) === 1) ? 'منقضی' : (((int) ($cfg['sold_to'] ?? 0) > 0) ? 'فروخته' : 'موجود');
            $buttons = [];
            if ((int) ($cfg['is_expired'] ?? 0) === 0) {
                $buttons[] = [['text' => '❌ منقضی کن', 'callback_data' => 'admin:stock:expire:' . $configId . ':' . $packageId . ':' . $typeId]];
            }
            if ((int) ($cfg['sold_to'] ?? 0) === 0) {
                $buttons[] = [['text' => '🗑 حذف کن', 'callback_data' => 'admin:stock:delete:' . $configId . ':' . $packageId . ':' . $typeId]];
            }
            $buttons[] = [['text' => '🔙 بازگشت', 'callback_data' => 'admin:stock:pkg:' . $packageId . ':' . $typeId]];
            $this->telegram->editMessageText(
                $chatId,
                $messageId,
                "🧾 <b>جزئیات کانفیگ</b>\n\n"
                . "ID: <code>{$configId}</code>\n"
                . "نام: <b>" . htmlspecialchars((string) ($cfg['service_name'] ?? '-')) . "</b>\n"
                . "وضعیت: <b>{$state}</b>",
                ['inline_keyboard' => $buttons]
            );
            $this->telegram->answerCallbackQuery($callbackId);
            return;
        }

        if (str_starts_with($data, 'admin:stock:expire:')) {
            if (!$isAdmin) {
                $this->telegram->answerCallbackQuery($callbackId, 'شما دسترسی ادمین ندارید.');
                return;
            }
            $parts = explode(':', $data);
            $configId = (int) ($parts[3] ?? 0);
            $packageId = (int) ($parts[4] ?? 0);
            $typeId = (int) ($parts[5] ?? 0);
            if ($configId > 0) {
                $this->database->expireConfig($configId);
            }
            $this->telegram->answerCallbackQuery($callbackId, '✅ کانفیگ منقضی شد.');
            $this->handle(['callback_query' => [
                'id' => $callbackId,
                'from' => $fromUser,
                'message' => $message,
                'data' => 'admin:stock:pkg:' . $packageId . ':' . $typeId,
            ]]);
            return;
        }

        if (str_starts_with($data, 'admin:stock:delete:')) {
            if (!$isAdmin) {
                $this->telegram->answerCallbackQuery($callbackId, 'شما دسترسی ادمین ندارید.');
                return;
            }
            $parts = explode(':', $data);
            $configId = (int) ($parts[3] ?? 0);
            $packageId = (int) ($parts[4] ?? 0);
            $typeId = (int) ($parts[5] ?? 0);
            $ok = $configId > 0 ? $this->database->deleteConfig($configId) : false;
            $this->telegram->answerCallbackQuery($callbackId, $ok ? '✅ کانفیگ حذف شد.' : 'این کانفیگ قابل حذف نیست.');
            $this->handle(['callback_query' => [
                'id' => $callbackId,
                'from' => $fromUser,
                'message' => $message,
                'data' => 'admin:stock:pkg:' . $packageId . ':' . $typeId,
            ]]);
            return;
        }

        if (str_starts_with($data, 'admin:user:view:')) {
            if (!$isAdmin) {
                $this->telegram->answerCallbackQuery($callbackId, 'شما دسترسی ادمین ندارید.');
                return;
            }
            $targetUid = (int) substr($data, strlen('admin:user:view:'));
            $u = $this->database->getUser($targetUid);
            if (!is_array($u)) {
                $this->telegram->answerCallbackQuery($callbackId, 'کاربر یافت نشد.');
                return;
            }
            $status = (string) ($u['status'] ?? 'unsafe');
            $isAgent = ((int) ($u['is_agent'] ?? 0)) === 1;
            $this->telegram->editMessageText(
                $chatId,
                $messageId,
                "👤 <b>کاربر</b>\n\n"
                . "آیدی: <code>{$targetUid}</code>\n"
                . "نام: " . htmlspecialchars((string) ($u['full_name'] ?? '-')) . "\n"
                . "یوزرنیم: @" . htmlspecialchars((string) (($u['username'] ?? '') !== '' ? $u['username'] : '-')) . "\n"
                . "موجودی: <b>" . (int) ($u['balance'] ?? 0) . "</b> تومان\n"
                . "وضعیت: <b>{$status}</b>\n"
                . "نمایندگی: <b>" . ($isAgent ? 'فعال' : 'غیرفعال') . "</b>",
                ['inline_keyboard' => [
                    [[
                        'text' => $status === 'restricted' ? '✅ رفع محدودیت' : '🚫 محدودسازی',
                        'callback_data' => 'admin:user:status:' . $targetUid . ':' . ($status === 'restricted' ? 'unsafe' : 'restricted'),
                    ]],
                    [[
                        'text' => $isAgent ? '🧷 لغو نمایندگی' : '🤝 فعال‌سازی نمایندگی',
                        'callback_data' => 'admin:user:agent:' . $targetUid . ':' . ($isAgent ? 0 : 1),
                    ]],
                    [
                        ['text' => '➕ افزایش موجودی', 'callback_data' => 'admin:user:bal:add:' . $targetUid],
                        ['text' => '➖ کاهش موجودی', 'callback_data' => 'admin:user:bal:sub:' . $targetUid],
                    ],
                    [['text' => '🔙 بازگشت', 'callback_data' => 'admin:users']],
                ]]
            );
            $this->telegram->answerCallbackQuery($callbackId);
            return;
        }

        if (str_starts_with($data, 'admin:user:status:')) {
            if (!$isAdmin) {
                $this->telegram->answerCallbackQuery($callbackId, 'شما دسترسی ادمین ندارید.');
                return;
            }
            $parts = explode(':', $data);
            $targetUid = (int) ($parts[3] ?? 0);
            $status = (string) ($parts[4] ?? 'unsafe');
            if ($targetUid > 0 && in_array($status, ['unsafe', 'restricted'], true)) {
                $this->database->setUserStatus($targetUid, $status);
            }
            $this->telegram->answerCallbackQuery($callbackId, '✅ وضعیت کاربر بروزرسانی شد.');
            $this->handle(['callback_query' => [
                'id' => $callbackId,
                'from' => $fromUser,
                'message' => $message,
                'data' => 'admin:user:view:' . $targetUid,
            ]]);
            return;
        }

        if (str_starts_with($data, 'admin:user:agent:')) {
            if (!$isAdmin) {
                $this->telegram->answerCallbackQuery($callbackId, 'شما دسترسی ادمین ندارید.');
                return;
            }
            $parts = explode(':', $data);
            $targetUid = (int) ($parts[3] ?? 0);
            $isAgent = ((int) ($parts[4] ?? 0)) === 1;
            if ($targetUid > 0) {
                $this->database->setUserAgent($targetUid, $isAgent);
            }
            $this->telegram->answerCallbackQuery($callbackId, '✅ وضعیت نمایندگی ذخیره شد.');
            $this->handle(['callback_query' => [
                'id' => $callbackId,
                'from' => $fromUser,
                'message' => $message,
                'data' => 'admin:user:view:' . $targetUid,
            ]]);
            return;
        }

        if (str_starts_with($data, 'admin:user:bal:add:') || str_starts_with($data, 'admin:user:bal:sub:')) {
            if (!$isAdmin) {
                $this->telegram->answerCallbackQuery($callbackId, 'شما دسترسی ادمین ندارید.');
                return;
            }
            $isAdd = str_starts_with($data, 'admin:user:bal:add:');
            $targetUid = (int) substr($data, strlen($isAdd ? 'admin:user:bal:add:' : 'admin:user:bal:sub:'));
            $this->database->setUserState($userId, 'await_admin_user_balance', [
                'target_user_id' => $targetUid,
                'mode' => $isAdd ? 'add' : 'sub',
                'source_chat_id' => $chatId,
                'source_message_id' => $messageId,
            ]);
            $this->telegram->editMessageText(
                $chatId,
                $messageId,
                ($isAdd ? '➕' : '➖') . " مبلغ را به تومان ارسال کنید.\n"
                . "کاربر: <code>{$targetUid}</code>",
                ['inline_keyboard' => [[['text' => '🔙 بازگشت', 'callback_data' => 'admin:user:view:' . $targetUid]]]]
            );
            $this->telegram->answerCallbackQuery($callbackId);
            return;
        }

        if ($data === 'admin:settings') {
            if (!$isAdmin) {
                $this->telegram->answerCallbackQuery($callbackId, 'شما دسترسی ادمین ندارید.');
                return;
            }
            $botStatus = $this->settings->get('bot_status', 'on');
            $freeTest = $this->settings->get('free_test_enabled', '1');
            $agencyReq = $this->settings->get('agency_request_enabled', '1');
            $channelId = trim($this->settings->get('channel_id', ''));
            $rows = [
                [[
                    'text' => '🤖 وضعیت ربات: ' . ($botStatus === 'on' ? 'ON' : strtoupper($botStatus)),
                    'callback_data' => 'admin:settings:bot',
                ]],
                [[
                    'text' => '🎁 تست رایگان: ' . ($freeTest === '1' ? '✅' : '❌'),
                    'callback_data' => 'admin:settings:toggle:free_test_enabled',
                ]],
                [[
                    'text' => '🤝 درخواست نمایندگی: ' . ($agencyReq === '1' ? '✅' : '❌'),
                    'callback_data' => 'admin:settings:toggle:agency_request_enabled',
                ]],
                [[
                    'text' => '💳 کارت‌به‌کارت: ' . ($this->settings->get('gw_card_enabled', '0') === '1' ? '✅' : '❌'),
                    'callback_data' => 'admin:settings:toggle:gw_card_enabled',
                ]],
                [[
                    'text' => '💎 کریپتو: ' . ($this->settings->get('gw_crypto_enabled', '0') === '1' ? '✅' : '❌'),
                    'callback_data' => 'admin:settings:toggle:gw_crypto_enabled',
                ]],
                [[
                    'text' => '🏦 TetraPay: ' . ($this->settings->get('gw_tetrapay_enabled', '0') === '1' ? '✅' : '❌'),
                    'callback_data' => 'admin:settings:toggle:gw_tetrapay_enabled',
                ]],
                [[
                    'text' => '📢 کانال قفل: ' . ($channelId !== '' ? htmlspecialchars($channelId) : '❌ تنظیم نشده'),
                    'callback_data' => 'admin:settings:channel',
                ]],
                [['text' => '🔙 بازگشت', 'callback_data' => 'admin:panel']],
            ];
            $this->telegram->editMessageText(
                $chatId,
                $messageId,
                '⚙️ <b>تنظیمات سریع</b>',
                ['inline_keyboard' => $rows]
            );
            $this->telegram->answerCallbackQuery($callbackId);
            return;
        }

        if ($data === 'admin:agents') {
            if (!$isAdmin) {
                $this->telegram->answerCallbackQuery($callbackId, 'شما دسترسی ادمین ندارید.');
                return;
            }
            $rows = [];
            foreach ($this->database->listUserIdsForBroadcast('agents') as $aid) {
                $rows[] = [[
                    'text' => '🤝 U:' . $aid,
                    'callback_data' => 'admin:agents:view:' . $aid,
                ]];
            }
            if ($rows === []) {
                $rows[] = [['text' => 'نماینده‌ای ثبت نشده', 'callback_data' => 'noop']];
            }
            $rows[] = [['text' => '🔙 بازگشت', 'callback_data' => 'admin:panel']];
            $this->telegram->editMessageText($chatId, $messageId, '🤝 <b>مدیریت نمایندگان</b>', ['inline_keyboard' => $rows]);
            $this->telegram->answerCallbackQuery($callbackId);
            return;
        }

        if (str_starts_with($data, 'admin:agents:view:')) {
            if (!$isAdmin) {
                $this->telegram->answerCallbackQuery($callbackId, 'شما دسترسی ادمین ندارید.');
                return;
            }
            $agentId = (int) substr($data, strlen('admin:agents:view:'));
            $rows = [];
            foreach ($this->database->listAllPackages() as $pkg) {
                $pkgId = (int) ($pkg['id'] ?? 0);
                $defaultPrice = (int) ($pkg['price'] ?? 0);
                $custom = $this->database->getAgencyPrice($agentId, $pkgId);
                $label = sprintf(
                    '#%d %s | default:%d | agent:%s',
                    $pkgId,
                    (string) ($pkg['name'] ?? '-'),
                    $defaultPrice,
                    $custom === null ? '-' : (string) $custom
                );
                $rows[] = [[
                    'text' => $label,
                    'callback_data' => 'admin:agents:set:' . $agentId . ':' . $pkgId,
                ]];
                if ($custom !== null) {
                    $rows[] = [[
                        'text' => '🧹 حذف قیمت اختصاصی پکیج #' . $pkgId,
                        'callback_data' => 'admin:agents:clear:' . $agentId . ':' . $pkgId,
                    ]];
                }
            }
            $rows[] = [['text' => '🔙 بازگشت', 'callback_data' => 'admin:agents']];
            $this->telegram->editMessageText(
                $chatId,
                $messageId,
                "🤝 <b>قیمت‌گذاری نماینده</b>\n\nکاربر: <code>{$agentId}</code>",
                ['inline_keyboard' => $rows]
            );
            $this->telegram->answerCallbackQuery($callbackId);
            return;
        }

        if (str_starts_with($data, 'admin:agents:set:')) {
            if (!$isAdmin) {
                $this->telegram->answerCallbackQuery($callbackId, 'شما دسترسی ادمین ندارید.');
                return;
            }
            $parts = explode(':', $data);
            $agentId = (int) ($parts[3] ?? 0);
            $pkgId = (int) ($parts[4] ?? 0);
            $this->database->setUserState($userId, 'await_agent_price', [
                'agent_id' => $agentId,
                'package_id' => $pkgId,
            ]);
            $this->telegram->editMessageText(
                $chatId,
                $messageId,
                "💵 قیمت اختصاصی را به تومان ارسال کنید.\nکاربر: <code>{$agentId}</code> | پکیج: <code>{$pkgId}</code>",
                ['inline_keyboard' => [[['text' => '🔙 بازگشت', 'callback_data' => 'admin:agents:view:' . $agentId]]]]
            );
            $this->telegram->answerCallbackQuery($callbackId);
            return;
        }

        if (str_starts_with($data, 'admin:agents:clear:')) {
            if (!$isAdmin) {
                $this->telegram->answerCallbackQuery($callbackId, 'شما دسترسی ادمین ندارید.');
                return;
            }
            $parts = explode(':', $data);
            $agentId = (int) ($parts[3] ?? 0);
            $pkgId = (int) ($parts[4] ?? 0);
            $this->database->clearAgencyPrice($agentId, $pkgId);
            $this->telegram->answerCallbackQuery($callbackId, '✅ حذف شد.');
            $this->handle(['callback_query' => ['id' => $callbackId, 'from' => $fromUser, 'message' => $message, 'data' => 'admin:agents:view:' . $agentId]]);
            return;
        }

        if ($data === 'admin:panels') {
            if (!$isAdmin) {
                $this->telegram->answerCallbackQuery($callbackId, 'شما دسترسی ادمین ندارید.');
                return;
            }
            $rows = [];
            $rows[] = [['text' => '➕ افزودن پنل', 'callback_data' => 'admin:panels:add']];
            $rows[] = [['text' => '⚙️ تنظیمات Worker API', 'callback_data' => 'admin:panels:worker']];
            foreach ($this->database->listPanels() as $p) {
                $rows[] = [[
                    'text' => (((int) ($p['is_active'] ?? 0) === 1) ? '🟢 ' : '🔴 ') . '#' . (int) $p['id'] . ' ' . (string) ($p['name'] ?? '-'),
                    'callback_data' => 'admin:panels:view:' . (int) ($p['id'] ?? 0),
                ]];
            }
            $rows[] = [['text' => '🔙 بازگشت', 'callback_data' => 'admin:panel']];
            $this->telegram->editMessageText($chatId, $messageId, "🖥 <b>مدیریت پنل‌های 3x-ui</b>", ['inline_keyboard' => $rows]);
            $this->telegram->answerCallbackQuery($callbackId);
            return;
        }

        if ($data === 'admin:panels:add') {
            if (!$isAdmin) {
                $this->telegram->answerCallbackQuery($callbackId, 'شما دسترسی ادمین ندارید.');
                return;
            }
            $this->database->setUserState($userId, 'await_panel_add');
            $this->telegram->editMessageText(
                $chatId,
                $messageId,
                "📝 اطلاعات پنل را با فرمت زیر بفرستید:\n<code>name|ip|port|patch|username|password</code>",
                ['inline_keyboard' => [[['text' => '🔙 بازگشت', 'callback_data' => 'admin:panels']]]]
            );
            $this->telegram->answerCallbackQuery($callbackId);
            return;
        }

        if (str_starts_with($data, 'admin:panels:view:')) {
            if (!$isAdmin) {
                $this->telegram->answerCallbackQuery($callbackId, 'شما دسترسی ادمین ندارید.');
                return;
            }
            $panelId = (int) substr($data, strlen('admin:panels:view:'));
            $panel = $this->database->getPanel($panelId);
            if (!is_array($panel)) {
                $this->telegram->answerCallbackQuery($callbackId, 'پنل پیدا نشد.');
                return;
            }
            $rows = [
                [[
                    'text' => ((int) ($panel['is_active'] ?? 0) === 1 ? '🔴 غیرفعال‌کردن' : '🟢 فعال‌کردن'),
                    'callback_data' => 'admin:panels:toggle:' . $panelId . ':' . (((int) ($panel['is_active'] ?? 0) === 1) ? 0 : 1),
                ]],
                [['text' => '📦 مدیریت پکیج‌های پنل', 'callback_data' => 'admin:panels:pkgs:' . $panelId]],
                [['text' => '🗑 حذف پنل', 'callback_data' => 'admin:panels:del:' . $panelId]],
                [['text' => '🔙 بازگشت', 'callback_data' => 'admin:panels']],
            ];
            $this->telegram->editMessageText(
                $chatId,
                $messageId,
                "🖥 <b>جزئیات پنل</b>\n\n"
                . "ID: <code>{$panelId}</code>\n"
                . "Name: <b>" . htmlspecialchars((string) ($panel['name'] ?? '-')) . "</b>\n"
                . "IP: <code>" . htmlspecialchars((string) ($panel['ip'] ?? '-')) . "</code>\n"
                . "Port: <code>" . (int) ($panel['port'] ?? 0) . "</code>\n"
                . "Patch: <code>" . htmlspecialchars((string) ($panel['patch'] ?? '/')) . "</code>\n"
                . "User: <code>" . htmlspecialchars((string) ($panel['username'] ?? '-')) . "</code>\n"
                . "Status: <b>" . (((int) ($panel['is_active'] ?? 0) === 1) ? 'Active' : 'Inactive') . "</b>",
                ['inline_keyboard' => $rows]
            );
            $this->telegram->answerCallbackQuery($callbackId);
            return;
        }

        if (str_starts_with($data, 'admin:panels:toggle:')) {
            if (!$isAdmin) {
                $this->telegram->answerCallbackQuery($callbackId, 'شما دسترسی ادمین ندارید.');
                return;
            }
            $parts = explode(':', $data);
            $panelId = (int) ($parts[3] ?? 0);
            $active = ((int) ($parts[4] ?? 0)) === 1;
            $this->database->updatePanelActive($panelId, $active);
            $this->telegram->answerCallbackQuery($callbackId, '✅ ذخیره شد.');
            $this->handle(['callback_query' => ['id' => $callbackId, 'from' => $fromUser, 'message' => $message, 'data' => 'admin:panels:view:' . $panelId]]);
            return;
        }

        if (str_starts_with($data, 'admin:panels:del:')) {
            if (!$isAdmin) {
                $this->telegram->answerCallbackQuery($callbackId, 'شما دسترسی ادمین ندارید.');
                return;
            }
            $panelId = (int) substr($data, strlen('admin:panels:del:'));
            $this->database->deletePanel($panelId);
            $this->telegram->answerCallbackQuery($callbackId, '✅ پنل حذف شد.');
            $this->handle(['callback_query' => ['id' => $callbackId, 'from' => $fromUser, 'message' => $message, 'data' => 'admin:panels']]);
            return;
        }

        if (str_starts_with($data, 'admin:panels:pkgs:')) {
            if (!$isAdmin) {
                $this->telegram->answerCallbackQuery($callbackId, 'شما دسترسی ادمین ندارید.');
                return;
            }
            $panelId = (int) substr($data, strlen('admin:panels:pkgs:'));
            $rows = [];
            $rows[] = [['text' => '➕ افزودن پکیج پنل', 'callback_data' => 'admin:panels:pkgs:add:' . $panelId]];
            foreach ($this->database->listPanelPackages($panelId) as $pp) {
                $rows[] = [[
                    'text' => sprintf('#%d %s | %sGB/%sD | inb:%s', (int) $pp['id'], (string) $pp['name'], (string) $pp['volume_gb'], (string) $pp['duration_days'], (string) $pp['inbound_id']),
                    'callback_data' => 'admin:panels:pkgs:del:' . (int) $pp['id'] . ':' . $panelId,
                ]];
            }
            $rows[] = [['text' => '🔙 بازگشت', 'callback_data' => 'admin:panels:view:' . $panelId]];
            $this->telegram->editMessageText($chatId, $messageId, "📦 <b>پکیج‌های پنل {$panelId}</b>", ['inline_keyboard' => $rows]);
            $this->telegram->answerCallbackQuery($callbackId);
            return;
        }

        if (str_starts_with($data, 'admin:panels:pkgs:add:')) {
            if (!$isAdmin) {
                $this->telegram->answerCallbackQuery($callbackId, 'شما دسترسی ادمین ندارید.');
                return;
            }
            $panelId = (int) substr($data, strlen('admin:panels:pkgs:add:'));
            $this->database->setUserState($userId, 'await_panel_pkg_add', ['panel_id' => $panelId]);
            $this->telegram->editMessageText(
                $chatId,
                $messageId,
                "📝 اطلاعات پکیج پنل:\n<code>name|volume_gb|duration_days|inbound_id</code>",
                ['inline_keyboard' => [[['text' => '🔙 بازگشت', 'callback_data' => 'admin:panels:pkgs:' . $panelId]]]]
            );
            $this->telegram->answerCallbackQuery($callbackId);
            return;
        }

        if (str_starts_with($data, 'admin:panels:pkgs:del:')) {
            if (!$isAdmin) {
                $this->telegram->answerCallbackQuery($callbackId, 'شما دسترسی ادمین ندارید.');
                return;
            }
            $parts = explode(':', $data);
            $ppId = (int) ($parts[4] ?? 0);
            $panelId = (int) ($parts[5] ?? 0);
            $this->database->deletePanelPackage($ppId);
            $this->telegram->answerCallbackQuery($callbackId, '✅ حذف شد.');
            $this->handle(['callback_query' => ['id' => $callbackId, 'from' => $fromUser, 'message' => $message, 'data' => 'admin:panels:pkgs:' . $panelId]]);
            return;
        }

        if ($data === 'admin:panels:worker') {
            if (!$isAdmin) {
                $this->telegram->answerCallbackQuery($callbackId, 'شما دسترسی ادمین ندارید.');
                return;
            }
            $enabled = $this->settings->get('worker_api_enabled', '0');
            $port = $this->settings->get('worker_api_port', '8080');
            $key = $this->settings->get('worker_api_key', '');
            $phpRuntime = $this->settings->get('php_worker_runtime_enabled', '0');
            $phpPoll = $this->settings->get('php_worker_poll_interval', '10');
            $rows = [
                [[
                    'text' => 'Worker API: ' . ($enabled === '1' ? '✅ ON' : '❌ OFF'),
                    'callback_data' => 'admin:panels:worker:toggle',
                ]],
                [['text' => '🔑 تنظیم API Key', 'callback_data' => 'admin:panels:worker:key']],
                [['text' => '🔌 تنظیم Port', 'callback_data' => 'admin:panels:worker:port']],
                [[
                    'text' => 'PHP Runtime: ' . ($phpRuntime === '1' ? '✅ ON' : '❌ OFF'),
                    'callback_data' => 'admin:panels:worker:php_toggle',
                ]],
                [['text' => '⏱ تنظیم Poll Interval', 'callback_data' => 'admin:panels:worker:php_poll']],
                [['text' => '🔙 بازگشت', 'callback_data' => 'admin:panels']],
            ];
            $this->telegram->editMessageText(
                $chatId,
                $messageId,
                "⚙️ <b>Worker API Settings</b>\n\n"
                . "Enabled: <b>" . ($enabled === '1' ? 'ON' : 'OFF') . "</b>\n"
                . "Port: <code>" . htmlspecialchars($port) . "</code>\n"
                . "Key: <code>" . htmlspecialchars($key !== '' ? substr($key, 0, 8) . '...' : '-') . "</code>\n"
                . "PHP Runtime: <b>" . ($phpRuntime === '1' ? 'ON' : 'OFF') . "</b>\n"
                . "Poll: <code>" . htmlspecialchars($phpPoll) . "s</code>\n\n"
                . "<i>CLI:</i> <code>php php/scripts/php_worker_runtime.php</code>",
                ['inline_keyboard' => $rows]
            );
            $this->telegram->answerCallbackQuery($callbackId);
            return;
        }

        if ($data === 'admin:panels:worker:toggle') {
            if (!$isAdmin) {
                $this->telegram->answerCallbackQuery($callbackId, 'شما دسترسی ادمین ندارید.');
                return;
            }
            $cur = $this->settings->get('worker_api_enabled', '0');
            $this->settings->set('worker_api_enabled', $cur === '1' ? '0' : '1');
            $this->telegram->answerCallbackQuery($callbackId, '✅ ذخیره شد.');
            $this->handle(['callback_query' => ['id' => $callbackId, 'from' => $fromUser, 'message' => $message, 'data' => 'admin:panels:worker']]);
            return;
        }

        if ($data === 'admin:panels:worker:php_toggle') {
            if (!$isAdmin) {
                $this->telegram->answerCallbackQuery($callbackId, 'شما دسترسی ادمین ندارید.');
                return;
            }
            $cur = $this->settings->get('php_worker_runtime_enabled', '0');
            $this->settings->set('php_worker_runtime_enabled', $cur === '1' ? '0' : '1');
            $this->telegram->answerCallbackQuery($callbackId, '✅ ذخیره شد.');
            $this->handle(['callback_query' => ['id' => $callbackId, 'from' => $fromUser, 'message' => $message, 'data' => 'admin:panels:worker']]);
            return;
        }

        if ($data === 'admin:panels:worker:php_poll') {
            if (!$isAdmin) {
                $this->telegram->answerCallbackQuery($callbackId, 'شما دسترسی ادمین ندارید.');
                return;
            }
            $this->database->setUserState($userId, 'await_php_worker_poll_interval');
            $this->telegram->editMessageText(
                $chatId,
                $messageId,
                "⏱ بازه Poll را به ثانیه ارسال کنید (حداقل 3).",
                ['inline_keyboard' => [[['text' => '🔙 بازگشت', 'callback_data' => 'admin:panels:worker']]]]
            );
            $this->telegram->answerCallbackQuery($callbackId);
            return;
        }

        if ($data === 'admin:panels:worker:key' || $data === 'admin:panels:worker:port') {
            if (!$isAdmin) {
                $this->telegram->answerCallbackQuery($callbackId, 'شما دسترسی ادمین ندارید.');
                return;
            }
            $stateName = $data === 'admin:panels:worker:key' ? 'await_worker_api_key' : 'await_worker_api_port';
            $this->database->setUserState($userId, $stateName);
            $this->telegram->editMessageText(
                $chatId,
                $messageId,
                $data === 'admin:panels:worker:key'
                    ? "🔑 API Key جدید را ارسال کنید."
                    : "🔌 Port جدید را ارسال کنید.",
                ['inline_keyboard' => [[['text' => '🔙 بازگشت', 'callback_data' => 'admin:panels:worker']]]]
            );
            $this->telegram->answerCallbackQuery($callbackId);
            return;
        }

        if ($data === 'admin:settings:bot') {
            if (!$isAdmin) {
                $this->telegram->answerCallbackQuery($callbackId, 'شما دسترسی ادمین ندارید.');
                return;
            }
            $current = $this->settings->get('bot_status', 'on');
            $next = $current === 'on' ? 'update' : ($current === 'update' ? 'off' : 'on');
            $this->settings->set('bot_status', $next);
            $this->telegram->answerCallbackQuery($callbackId, '✅ وضعیت ربات: ' . strtoupper($next));
            $this->handle(['callback_query' => [
                'id' => $callbackId,
                'from' => $fromUser,
                'message' => $message,
                'data' => 'admin:settings',
            ]]);
            return;
        }

        if (str_starts_with($data, 'admin:settings:toggle:')) {
            if (!$isAdmin) {
                $this->telegram->answerCallbackQuery($callbackId, 'شما دسترسی ادمین ندارید.');
                return;
            }
            $key = substr($data, strlen('admin:settings:toggle:'));
            $allowed = ['free_test_enabled', 'agency_request_enabled', 'gw_card_enabled', 'gw_crypto_enabled', 'gw_tetrapay_enabled'];
            if (in_array($key, $allowed, true)) {
                $current = $this->settings->get($key, '0');
                $this->settings->set($key, $current === '1' ? '0' : '1');
                $this->telegram->answerCallbackQuery($callbackId, '✅ ذخیره شد.');
            } else {
                $this->telegram->answerCallbackQuery($callbackId, 'کلید تنظیمات مجاز نیست.');
            }
            $this->handle(['callback_query' => [
                'id' => $callbackId,
                'from' => $fromUser,
                'message' => $message,
                'data' => 'admin:settings',
            ]]);
            return;
        }

        if ($data === 'admin:settings:channel') {
            if (!$isAdmin) {
                $this->telegram->answerCallbackQuery($callbackId, 'شما دسترسی ادمین ندارید.');
                return;
            }
            $this->database->setUserState($userId, 'await_admin_set_channel', [
                'source_chat_id' => $chatId,
                'source_message_id' => $messageId,
            ]);
            $current = trim($this->settings->get('channel_id', ''));
            $this->telegram->editMessageText(
                $chatId,
                $messageId,
                "📢 آیدی کانال قفل را ارسال کنید.\n"
                . "نمونه: <code>@your_channel</code> یا <code>-1001234567890</code>\n"
                . "برای غیرفعال‌سازی: <code>-</code>\n\n"
                . "مقدار فعلی: " . ($current !== '' ? "<code>" . htmlspecialchars($current) . "</code>" : "❌ تنظیم نشده"),
                ['inline_keyboard' => [[['text' => '🔙 بازگشت', 'callback_data' => 'admin:settings']]]
                ]
            );
            $this->telegram->answerCallbackQuery($callbackId);
            return;
        }

        if ($data === 'admin:groupops') {
            if (!$isAdmin) {
                $this->telegram->answerCallbackQuery($callbackId, 'شما دسترسی ادمین ندارید.');
                return;
            }
            $groupId = trim($this->settings->get('group_id', ''));
            $backupTopic = trim($this->settings->get('group_topic_backup', ''));
            $broadcastTopic = trim($this->settings->get('group_topic_broadcast_report', ''));
            $errorTopic = trim($this->settings->get('group_topic_error_log', ''));
            $text = "🗃 <b>بکاپ / تاپیک گروه</b>\n\n"
                . "Group ID: " . ($groupId !== '' ? "<code>{$groupId}</code>" : "❌ تنظیم نشده") . "\n"
                . "Topic backup: " . ($backupTopic !== '' ? "<code>{$backupTopic}</code>" : "—") . "\n"
                . "Topic broadcast: " . ($broadcastTopic !== '' ? "<code>{$broadcastTopic}</code>" : "—") . "\n"
                . "Topic error: " . ($errorTopic !== '' ? "<code>{$errorTopic}</code>" : "—");
            $rows = [
                [['text' => '🆔 تنظیم Group ID', 'callback_data' => 'admin:groupops:set_group']],
                [['text' => '🧵 ساخت/تکمیل تاپیک‌ها', 'callback_data' => 'admin:groupops:ensure_topics']],
                [['text' => '💾 بکاپ تنظیمات (JSON)', 'callback_data' => 'admin:groupops:backup_settings']],
                [['text' => '🗄 بکاپ دیتابیس (SQL)', 'callback_data' => 'admin:groupops:backup_db']],
                [['text' => '♻️ بازیابی تنظیمات', 'callback_data' => 'admin:groupops:restore_settings']],
                [['text' => '🔙 بازگشت', 'callback_data' => 'admin:panel']],
            ];
            $this->telegram->editMessageText($chatId, $messageId, $text, ['inline_keyboard' => $rows]);
            $this->telegram->answerCallbackQuery($callbackId);
            return;
        }

        if ($data === 'admin:groupops:set_group') {
            if (!$isAdmin) {
                $this->telegram->answerCallbackQuery($callbackId, 'شما دسترسی ادمین ندارید.');
                return;
            }
            $this->database->setUserState($userId, 'await_admin_group_id');
            $current = trim($this->settings->get('group_id', ''));
            $this->telegram->editMessageText(
                $chatId,
                $messageId,
                "🆔 Group ID را ارسال کنید.\nبرای غیرفعال‌سازی «-» بفرستید.\n\n"
                . "مقدار فعلی: " . ($current !== '' ? "<code>" . htmlspecialchars($current) . "</code>" : "❌ تنظیم نشده"),
                ['inline_keyboard' => [[['text' => '🔙 بازگشت', 'callback_data' => 'admin:groupops']]]]
            );
            $this->telegram->answerCallbackQuery($callbackId);
            return;
        }

        if ($data === 'admin:groupops:ensure_topics') {
            if (!$isAdmin) {
                $this->telegram->answerCallbackQuery($callbackId, 'شما دسترسی ادمین ندارید.');
                return;
            }
            $status = $this->ensureGroupTopics();
            $this->telegram->answerCallbackQuery($callbackId, $status);
            $this->handle(['callback_query' => [
                'id' => $callbackId,
                'from' => $fromUser,
                'message' => $message,
                'data' => 'admin:groupops',
            ]]);
            return;
        }

        if ($data === 'admin:groupops:backup_settings') {
            if (!$isAdmin) {
                $this->telegram->answerCallbackQuery($callbackId, 'شما دسترسی ادمین ندارید.');
                return;
            }
            $ok = $this->sendSettingsBackup($chatId);
            $this->telegram->answerCallbackQuery($callbackId, $ok ? '✅ بکاپ ارسال شد.' : '❌ بکاپ انجام نشد.');
            return;
        }


        if ($data === 'admin:groupops:backup_db') {
            if (!$isAdmin) {
                $this->telegram->answerCallbackQuery($callbackId, 'شما دسترسی ادمین ندارید.');
                return;
            }
            $service = new DatabaseBackupService($this->database, $this->telegram, $this->settings);
            $ok = $service->sendBackup($chatId);
            $this->telegram->answerCallbackQuery($callbackId, $ok ? '✅ بکاپ دیتابیس ارسال شد.' : '❌ بکاپ دیتابیس انجام نشد.');
            return;
        }

        if ($data === 'admin:groupops:restore_settings') {
            if (!$isAdmin) {
                $this->telegram->answerCallbackQuery($callbackId, 'شما دسترسی ادمین ندارید.');
                return;
            }
            $this->database->setUserState($userId, 'await_admin_restore_settings');
            $this->telegram->editMessageText(
                $chatId,
                $messageId,
                "♻️ فایل JSON بکاپ تنظیمات را بفرستید.\n"
                . "فرمت مورد انتظار:\n<code>{\"settings\":{\"key\":\"value\"}}</code>",
                ['inline_keyboard' => [[['text' => '🔙 بازگشت', 'callback_data' => 'admin:groupops']]]]
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

        if ($data === 'admin:requests') {
            if (!$isAdmin) {
                $this->telegram->answerCallbackQuery($callbackId, 'شما دسترسی ادمین ندارید.');
                return;
            }
            $this->telegram->editMessageText(
                $chatId,
                $messageId,
                "🗂 <b>مدیریت درخواست‌ها</b>\n\nنوع درخواست را انتخاب کنید:",
                [
                    'inline_keyboard' => [
                        [['text' => '🎁 درخواست‌های تست رایگان', 'callback_data' => 'admin:req:free:list']],
                        [['text' => '🤝 درخواست‌های نمایندگی', 'callback_data' => 'admin:req:agency:list']],
                        [['text' => '🔙 بازگشت', 'callback_data' => 'admin:panel']],
                    ],
                ]
            );
            $this->telegram->answerCallbackQuery($callbackId);
            return;
        }

        if (str_starts_with($data, 'admin:req:free:list') || str_starts_with($data, 'admin:req:agency:list')) {
            if (!$isAdmin) {
                $this->telegram->answerCallbackQuery($callbackId, 'شما دسترسی ادمین ندارید.');
                return;
            }
            $isFree = str_starts_with($data, 'admin:req:free:list');
            $raw = substr($data, strlen($isFree ? 'admin:req:free:list' : 'admin:req:agency:list'));
            $parts = array_values(array_filter(explode(':', ltrim($raw, ':')), static fn ($x) => $x !== ''));
            $status = (string) ($parts[0] ?? 'pending');
            if (!in_array($status, ['pending', 'approved', 'rejected'], true)) {
                $status = 'pending';
            }
            $page = isset($parts[1]) ? max(1, (int) $parts[1]) : 1;
            $perPage = 10;
            $offset = ($page - 1) * $perPage;

            $items = $isFree
                ? $this->database->listFreeTestRequestsByStatus($status, $perPage, $offset)
                : $this->database->listAgencyRequestsByStatus($status, $perPage, $offset);
            $total = $isFree
                ? $this->database->countFreeTestRequestsByStatus($status)
                : $this->database->countAgencyRequestsByStatus($status);
            $totalPages = max(1, (int) ceil($total / $perPage));
            if ($page > $totalPages) {
                $page = $totalPages;
            }

            $statusLabel = $status === 'approved' ? 'approved' : ($status === 'rejected' ? 'rejected' : 'pending');
            if ($items === []) {
                $this->telegram->editMessageText(
                    $chatId,
                    $messageId,
                    $isFree
                        ? "📭 در وضعیت {$statusLabel} درخواستی برای تست رایگان وجود ندارد."
                        : "📭 در وضعیت {$statusLabel} درخواستی برای نمایندگی وجود ندارد.",
                    [
                        'inline_keyboard' => [
                            [
                                ['text' => '⏳ pending', 'callback_data' => ($isFree ? 'admin:req:free:list:pending:1' : 'admin:req:agency:list:pending:1')],
                                ['text' => '✅ approved', 'callback_data' => ($isFree ? 'admin:req:free:list:approved:1' : 'admin:req:agency:list:approved:1')],
                                ['text' => '❌ rejected', 'callback_data' => ($isFree ? 'admin:req:free:list:rejected:1' : 'admin:req:agency:list:rejected:1')],
                            ],
                            [['text' => '🔙 بازگشت', 'callback_data' => 'admin:requests']],
                        ],
                    ]
                );
                $this->telegram->answerCallbackQuery($callbackId);
                return;
            }

            $rows = [];
            foreach ($items as $item) {
                $prefix = $isFree ? 'admin:req:free:view:' : 'admin:req:agency:view:';
                $rows[] = [[
                    'text' => sprintf('#%d | U:%d | %s', (int) $item['id'], (int) $item['user_id'], (string) ($item['created_at'] ?? '-')),
                    'callback_data' => $prefix . (int) $item['id'],
                ]];
            }
            $prefix = $isFree ? 'admin:req:free:list:' : 'admin:req:agency:list:';
            $rows[] = [[
                'text' => (($status === 'pending') ? '⏳' : (($status === 'approved') ? '✅' : '❌')) . ' وضعیت فعلی: ' . $statusLabel,
                'callback_data' => 'noop',
            ]];
            $rows[] = [[
                'text' => '⏳ pending',
                'callback_data' => $prefix . 'pending:1',
            ], [
                'text' => '✅ approved',
                'callback_data' => $prefix . 'approved:1',
            ], [
                'text' => '❌ rejected',
                'callback_data' => $prefix . 'rejected:1',
            ]];
            $nav = [];
            if ($page > 1) {
                $nav[] = ['text' => '⬅️ قبلی', 'callback_data' => $prefix . $status . ':' . ($page - 1)];
            }
            $nav[] = ['text' => "📄 {$page}/{$totalPages}", 'callback_data' => 'noop'];
            if ($page < $totalPages) {
                $nav[] = ['text' => 'بعدی ➡️', 'callback_data' => $prefix . $status . ':' . ($page + 1)];
            }
            $rows[] = $nav;
            $rows[] = [['text' => '🔙 بازگشت', 'callback_data' => 'admin:requests']];
            $this->telegram->editMessageText(
                $chatId,
                $messageId,
                $isFree
                    ? "🎁 <b>درخواست‌های تست رایگان ({$statusLabel})</b>"
                    : "🤝 <b>درخواست‌های نمایندگی ({$statusLabel})</b>",
                ['inline_keyboard' => $rows]
            );
            $this->telegram->answerCallbackQuery($callbackId);
            return;
        }

        if (str_starts_with($data, 'admin:req:free:view:') || str_starts_with($data, 'admin:req:agency:view:')) {
            if (!$isAdmin) {
                $this->telegram->answerCallbackQuery($callbackId, 'شما دسترسی ادمین ندارید.');
                return;
            }
            $isFree = str_starts_with($data, 'admin:req:free:view:');
            $requestId = (int) substr($data, strlen($isFree ? 'admin:req:free:view:' : 'admin:req:agency:view:'));
            $request = $isFree
                ? $this->database->getFreeTestRequestById($requestId)
                : $this->database->getAgencyRequestById($requestId);
            if (!is_array($request)) {
                $this->telegram->answerCallbackQuery($callbackId, 'درخواست یافت نشد.');
                return;
            }

            $status = (string) ($request['status'] ?? 'pending');
            $statusText = $status === 'approved' ? '✅ approved' : ($status === 'rejected' ? '❌ rejected' : '⏳ pending');
            $text = ($isFree ? "🎁 <b>درخواست تست رایگان</b>\n\n" : "🤝 <b>درخواست نمایندگی</b>\n\n")
                . "شناسه: <code>{$requestId}</code>\n"
                . "کاربر: <code>" . (int) ($request['user_id'] ?? 0) . "</code>\n"
                . "وضعیت: {$statusText}\n"
                . "زمان ثبت: " . htmlspecialchars((string) ($request['created_at'] ?? '-')) . "\n\n"
                . "متن:\n" . htmlspecialchars((string) ($request['note'] ?? ''));

            $reviewPrefix = $isFree ? 'admin:req:free:' : 'admin:req:agency:';
            $backKey = $isFree ? 'admin:req:free:list' : 'admin:req:agency:list';
            $buttons = [
                [['text' => '✅ تایید', 'callback_data' => $reviewPrefix . 'approve:' . $requestId]],
                [['text' => '❌ رد', 'callback_data' => $reviewPrefix . 'reject:' . $requestId]],
                [['text' => '🔙 بازگشت', 'callback_data' => $backKey]],
            ];
            if ($status !== 'pending') {
                $buttons = [[['text' => '🔙 بازگشت', 'callback_data' => $backKey]]];
            }

            $this->telegram->editMessageText($chatId, $messageId, $text, ['inline_keyboard' => $buttons]);
            $this->telegram->answerCallbackQuery($callbackId);
            return;
        }

        if (
            str_starts_with($data, 'admin:req:free:approve:')
            || str_starts_with($data, 'admin:req:free:reject:')
            || str_starts_with($data, 'admin:req:agency:approve:')
            || str_starts_with($data, 'admin:req:agency:reject:')
        ) {
            if (!$isAdmin) {
                $this->telegram->answerCallbackQuery($callbackId, 'شما دسترسی ادمین ندارید.');
                return;
            }

            $isFree = str_starts_with($data, 'admin:req:free:');
            $approve = str_contains($data, ':approve:');
            $prefix = $isFree
                ? ($approve ? 'admin:req:free:approve:' : 'admin:req:free:reject:')
                : ($approve ? 'admin:req:agency:approve:' : 'admin:req:agency:reject:');
            $requestId = (int) substr($data, strlen($prefix));
            $exists = $isFree
                ? $this->database->getFreeTestRequestById($requestId)
                : $this->database->getAgencyRequestById($requestId);
            if (!is_array($exists)) {
                $this->telegram->answerCallbackQuery($callbackId, 'درخواست یافت نشد.');
                return;
            }
            if (($exists['status'] ?? '') !== 'pending') {
                $this->telegram->answerCallbackQuery($callbackId, 'این درخواست قبلاً بررسی شده است.');
                return;
            }

            $this->database->setUserState($userId, 'await_admin_request_note', [
                'request_kind' => $isFree ? 'free' : 'agency',
                'request_id' => $requestId,
                'approve' => $approve ? 1 : 0,
                'source_chat_id' => $chatId,
                'source_message_id' => $messageId,
            ]);
            $actionText = $approve ? 'تایید' : 'رد';
            $this->telegram->editMessageText(
                $chatId,
                $messageId,
                "📝 برای {$actionText} درخواست <code>{$requestId}</code> یک نوت ادمین بفرستید.\n"
                . "اگر نوت لازم ندارید، یک «-» ارسال کنید.",
                ['inline_keyboard' => [[['text' => '🔙 بازگشت', 'callback_data' => 'admin:requests']]]]
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

            if (($result['queued_worker'] ?? false) === true) {
                $jobId = (int) ($result['job_id'] ?? 0);
                $jobStatus = (string) ($result['job_status'] ?? 'pending');
                $this->telegram->editMessageText(
                    $chatId,
                    $messageId,
                    "🛰 سفارش <code>{$orderId}</code> در صف Worker API قرار گرفت.\n"
                    . "Job ID: <code>{$jobId}</code>\n"
                    . "Status: <b>" . htmlspecialchars($jobStatus) . "</b>\n\n"
                    . "بعد از تکمیل job توسط worker، دوباره روی تحویل سفارش بزنید.",
                    KeyboardBuilder::adminPanel()
                );
                $this->telegram->answerCallbackQuery($callbackId, 'سفارش در صف Worker قرار گرفت.');
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
            $payment = $this->database->getPaymentById($paymentId);
            $keyboard = [
                'inline_keyboard' => [
                    ...((is_array($payment) && str_starts_with((string) ($payment['payment_method'] ?? ''), 'crypto:')) ? [[
                        ['text' => '🔎 بررسی on-chain', 'callback_data' => 'pay:crypto:verify:' . $paymentId],
                    ]] : []),
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

        if (str_starts_with($data, 'pay:crypto:verify:')) {
            if (!$isAdmin) {
                $this->telegram->answerCallbackQuery($callbackId, 'شما دسترسی ادمین ندارید.');
                return;
            }
            $paymentId = (int) substr($data, strlen('pay:crypto:verify:'));
            $attempt = $this->database->registerVerifyAttempt($paymentId);
            if (!(bool) ($attempt['ok'] ?? false)) {
                $error = (string) ($attempt['error'] ?? '');
                if ($error === 'cooldown') {
                    $this->telegram->answerCallbackQuery($callbackId, '⏳ لطفاً چند ثانیه بعد دوباره بررسی کنید.');
                    return;
                }
                if ($error === 'max_attempts') {
                    $this->telegram->answerCallbackQuery($callbackId, '🚫 سقف دفعات بررسی این پرداخت تکمیل شده است.');
                    return;
                }
                $this->telegram->answerCallbackQuery($callbackId, 'بررسی این پرداخت فعلاً ممکن نیست.');
                return;
            }

            $payment = $this->database->getPaymentById($paymentId);
            if (!is_array($payment)) {
                $this->telegram->answerCallbackQuery($callbackId, 'پرداخت یافت نشد.');
                return;
            }
            $pm = (string) ($payment['payment_method'] ?? '');
            if (!str_starts_with($pm, 'crypto:')) {
                $this->telegram->answerCallbackQuery($callbackId, 'این پرداخت کریپتو نیست.');
                return;
            }

            $coin = trim(substr($pm, strlen('crypto:')));
            $txHash = trim((string) ($payment['tx_hash'] ?? ''));
            $claimedCoin = isset($payment['crypto_amount_claimed']) ? (float) $payment['crypto_amount_claimed'] : null;
            if ($txHash === '') {
                $this->telegram->answerCallbackQuery($callbackId, 'TX Hash ثبت نشده است.');
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
                    $note = $chainConfirmed ? 'on-chain' : 'amount-check';
                    $this->telegram->editMessageText(
                        $chatId,
                        $messageId,
                        "✅ پرداخت کریپتو تایید شد ({$note}) و سفارش در صف تحویل قرار گرفت.",
                        KeyboardBuilder::adminPanel()
                    );
                    $this->telegram->answerCallbackQuery($callbackId);
                    $this->telegram->sendMessage((int) $payment['user_id'], "✅ پرداخت کریپتوی شما تایید شد.");
                    return;
                }
            }

            $this->telegram->answerCallbackQuery($callbackId, 'تراکنش تایید نشد یا مقدار اعلامی معتبر نیست.');
            return;
        }

        if (str_starts_with($data, 'pay:swapwallet_crypto:verify:')) {
            $paymentId = (int) substr($data, strlen('pay:swapwallet_crypto:verify:'));
            $payment = $this->database->getPaymentById($paymentId);
            if ($payment === null) {
                $this->telegram->answerCallbackQuery($callbackId, 'پرداخت پیدا نشد.');
                return;
            }
            $verify = $this->gateways->checkSwapwalletCryptoInvoice((string) ($payment['gateway_ref'] ?? ''));
            if (($verify['ok'] ?? false) && ($verify['paid'] ?? false)) {
                $changed = $this->database->markPaymentAndPendingPaidIfWaitingGateway($paymentId);
                if ($changed) {
                    $this->telegram->editMessageText($chatId, $messageId, "✅ پرداخت SwapWallet تایید شد و سفارش در صف تحویل قرار گرفت.", KeyboardBuilder::backToMain());
                    $this->telegram->answerCallbackQuery($callbackId);
                    return;
                }
            }
            $this->telegram->answerCallbackQuery($callbackId, 'پرداخت هنوز تایید نشده است.');
            return;
        }

        if (str_starts_with($data, 'pay:swapwallet_crypto:')) {
            $packageId = (int) substr($data, strlen('pay:swapwallet_crypto:'));
            $package = $this->database->getPackage($packageId);
            if ($package === null) {
                $this->telegram->answerCallbackQuery($callbackId, 'پکیج پیدا نشد.');
                return;
            }
            if (!$this->ensurePurchaseAllowedForPackage($userId, (int) $package['id'], $callbackId)) {
                return;
            }
            $amount = $this->database->effectivePackagePrice($userId, $package);
            if (!$this->isGatewayAmountAllowed('swapwallet_crypto', $amount)) {
                $this->telegram->answerCallbackQuery($callbackId, 'مبلغ برای این درگاه مجاز نیست. محدوده: ' . $this->gatewayRangeText('swapwallet_crypto'));
                return;
            }
            $paymentId = $this->database->createPayment([
                'kind' => 'purchase',
                'user_id' => $userId,
                'package_id' => $packageId,
                'amount' => $amount,
                'payment_method' => 'swapwallet_crypto',
                'status' => 'waiting_gateway',
                'created_at' => gmdate('Y-m-d H:i:s'),
            ]);
            $pendingId = $this->database->createPendingOrder([
                'user_id' => $userId,
                'package_id' => $packageId,
                'payment_id' => $paymentId,
                'amount' => $amount,
                'payment_method' => 'swapwallet_crypto',
                'created_at' => gmdate('Y-m-d H:i:s'),
                'status' => 'waiting_payment',
            ]);
            $invoice = $this->gateways->createSwapwalletCryptoInvoice($amount, (string) $pendingId, 'TRON', 'Purchase');
            if (!($invoice['ok'] ?? false)) {
                $this->telegram->answerCallbackQuery($callbackId, 'خطا در ایجاد فاکتور SwapWallet.');
                return;
            }
            $invoiceId = (string) ($invoice['invoice_id'] ?? '');
            if ($invoiceId !== '') {
                $this->database->setPaymentGatewayRef($paymentId, $invoiceId);
            }
            $payUrl = (string) ($invoice['pay_url'] ?? '');
            $this->telegram->editMessageText(
                $chatId,
                $messageId,
                "💠 <b>پرداخت با SwapWallet</b>\n\n"
                . "سفارش: <code>{$pendingId}</code>\n"
                . "مبلغ: <b>{$amount}</b> تومان\n\n"
                . ($payUrl !== '' ? "لینک پرداخت:\n" . htmlspecialchars($payUrl) . "\n\n" : '')
                . "پس از پرداخت، دکمه بررسی را بزنید.",
                ['inline_keyboard' => [
                    ...($payUrl !== '' ? [[['text' => '💳 پرداخت', 'url' => $payUrl]]] : []),
                    [['text' => '🔄 بررسی پرداخت', 'callback_data' => 'pay:swapwallet_crypto:verify:' . $paymentId]],
                    [['text' => '🔙 بازگشت', 'callback_data' => 'nav:main']],
                ]]
            );
            $this->telegram->answerCallbackQuery($callbackId);
            return;
        }

        if (str_starts_with($data, 'pay:tronpays_rial:verify:')) {
            $paymentId = (int) substr($data, strlen('pay:tronpays_rial:verify:'));
            $payment = $this->database->getPaymentById($paymentId);
            if ($payment === null) {
                $this->telegram->answerCallbackQuery($callbackId, 'پرداخت پیدا نشد.');
                return;
            }
            $verify = $this->gateways->checkTronpaysRialInvoice((string) ($payment['gateway_ref'] ?? ''));
            if (($verify['ok'] ?? false) && ($verify['paid'] ?? false)) {
                $changed = $this->database->markPaymentAndPendingPaidIfWaitingGateway($paymentId);
                if ($changed) {
                    $this->telegram->editMessageText($chatId, $messageId, "✅ پرداخت TronPays تایید شد و سفارش در صف تحویل قرار گرفت.", KeyboardBuilder::backToMain());
                    $this->telegram->answerCallbackQuery($callbackId);
                    return;
                }
            }
            $this->telegram->answerCallbackQuery($callbackId, 'پرداخت هنوز تایید نشده است.');
            return;
        }

        if (str_starts_with($data, 'pay:tronpays_rial:')) {
            $packageId = (int) substr($data, strlen('pay:tronpays_rial:'));
            $package = $this->database->getPackage($packageId);
            if ($package === null) {
                $this->telegram->answerCallbackQuery($callbackId, 'پکیج پیدا نشد.');
                return;
            }
            if (!$this->ensurePurchaseAllowedForPackage($userId, (int) $package['id'], $callbackId)) {
                return;
            }
            $amount = $this->database->effectivePackagePrice($userId, $package);
            if (!$this->isGatewayAmountAllowed('tronpays_rial', $amount)) {
                $this->telegram->answerCallbackQuery($callbackId, 'مبلغ برای این درگاه مجاز نیست. محدوده: ' . $this->gatewayRangeText('tronpays_rial'));
                return;
            }
            $paymentId = $this->database->createPayment([
                'kind' => 'purchase',
                'user_id' => $userId,
                'package_id' => $packageId,
                'amount' => $amount,
                'payment_method' => 'tronpays_rial',
                'status' => 'waiting_gateway',
                'created_at' => gmdate('Y-m-d H:i:s'),
            ]);
            $pendingId = $this->database->createPendingOrder([
                'user_id' => $userId,
                'package_id' => $packageId,
                'payment_id' => $paymentId,
                'amount' => $amount,
                'payment_method' => 'tronpays_rial',
                'created_at' => gmdate('Y-m-d H:i:s'),
                'status' => 'waiting_payment',
            ]);
            $invoice = $this->gateways->createTronpaysRialInvoice($amount, 'buy-' . $userId . '-' . $packageId . '-' . time());
            if (!($invoice['ok'] ?? false)) {
                $this->telegram->answerCallbackQuery($callbackId, 'خطا در ایجاد فاکتور TronPays.');
                return;
            }
            $invoiceId = (string) ($invoice['invoice_id'] ?? '');
            if ($invoiceId !== '') {
                $this->database->setPaymentGatewayRef($paymentId, $invoiceId);
            }
            $payUrl = (string) ($invoice['pay_url'] ?? '');
            $this->telegram->editMessageText(
                $chatId,
                $messageId,
                "🧾 <b>پرداخت با TronPays</b>\n\n"
                . "سفارش: <code>{$pendingId}</code>\n"
                . "مبلغ: <b>{$amount}</b> تومان\n\n"
                . ($payUrl !== '' ? "لینک پرداخت:\n" . htmlspecialchars($payUrl) . "\n\n" : '')
                . "پس از پرداخت، دکمه بررسی را بزنید.",
                ['inline_keyboard' => [
                    ...($payUrl !== '' ? [[['text' => '💳 پرداخت', 'url' => $payUrl]]] : []),
                    [['text' => '🔄 بررسی پرداخت', 'callback_data' => 'pay:tronpays_rial:verify:' . $paymentId]],
                    [['text' => '🔙 بازگشت', 'callback_data' => 'nav:main']],
                ]]
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
            $result = $this->database->applyAdminPaymentDecision($paymentId, $approve);
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

            if (($result['kind'] ?? '') === 'wallet_charge') {
                $userNotice = $approve
                    ? "✅ درخواست شارژ کیف پول شما تایید شد.\nمبلغ: <b>{$result['amount']}</b> تومان"
                    : "❌ درخواست شارژ کیف پول شما رد شد.";
            } elseif (($result['kind'] ?? '') === 'renewal') {
                $userNotice = $approve
                    ? "✅ پرداخت تمدید شما تایید شد و در صف تحویل قرار گرفت."
                    : "❌ پرداخت تمدید شما رد شد.";
            } else {
                $userNotice = $approve
                    ? "✅ پرداخت سفارش شما تایید شد و در صف تحویل قرار گرفت."
                    : "❌ پرداخت سفارش شما رد شد.";
            }
            $this->telegram->sendMessage((int) $result['user_id'], $userNotice);
            return;
        }

        if (str_starts_with($data, 'renew:confirm:')) {
            if (!$isAdmin) {
                $this->telegram->answerCallbackQuery($callbackId, 'دسترسی مجاز نیست.');
                return;
            }
            $parts = explode(':', $data);
            $configId = (int) ($parts[2] ?? 0);
            $targetUid = (int) ($parts[3] ?? 0);
            if ($configId <= 0 || $targetUid <= 0) {
                $this->telegram->answerCallbackQuery($callbackId, 'داده نامعتبر.');
                return;
            }
            $this->database->unexpireConfig($configId);
            $this->telegram->answerCallbackQuery($callbackId, '✅ تمدید تأیید شد.');
            $this->telegram->sendMessage(
                $targetUid,
                "🎉 <b>تمدید سرویس انجام شد!</b>\n\n"
                . "سرویس شما با موفقیت تمدید شد."
            );
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
            $items = $this->database->listUserPurchasesSummary($userId, 8);
            $rows = [];
            if ($this->settings->get('manual_renewal_enabled', '1') === '1') {
                foreach ($items as $item) {
                    $purchaseId = (int) ($item['id'] ?? 0);
                    if ($purchaseId <= 0) {
                        continue;
                    }
                    $rows[] = [[
                        'text' => '♻️ تمدید سفارش #' . $purchaseId,
                        'callback_data' => 'renew:' . $purchaseId,
                    ]];
                }
            }
            $rows[] = [['text' => '🔙 بازگشت', 'callback_data' => 'nav:main']];
            $this->telegram->editMessageText(
                $chatId,
                $messageId,
                $this->menus->myConfigsText($userId),
                ['inline_keyboard' => $rows]
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

        if ($data === 'wallet:charge' || $data === 'buy:start') {
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
                if ($this->settings->get('shop_open', '1') !== '1') {
                    $this->telegram->answerCallbackQuery($callbackId, 'فروشگاه در حال حاضر بسته است.');
                    return;
                }

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

            $this->telegram->answerCallbackQuery($callbackId, 'این بخش فعلاً در دست توسعه است.');
            return;
        }

        if ($data === 'test:start') {
            $this->database->setUserState($userId, 'await_free_test_note');
            $this->telegram->editMessageText(
                $chatId,
                $messageId,
                "🎁 <b>درخواست تست رایگان</b>\n\n"
                . "لطفاً یک توضیح کوتاه ارسال کنید (مثلاً نوع مصرف/مدت موردنیاز).\n"
                . "درخواست شما برای ادمین ارسال می‌شود.",
                KeyboardBuilder::backToMain()
            );
            $this->telegram->answerCallbackQuery($callbackId);
            return;
        }

        if ($data === 'agency:request') {
            $this->database->setUserState($userId, 'await_agency_request');
            $this->telegram->editMessageText(
                $chatId,
                $messageId,
                "🤝 <b>درخواست نمایندگی</b>\n\n"
                . "لطفاً اطلاعات تماس و توضیح کوتاه درباره سابقه/برنامه همکاری را ارسال کنید.\n"
                . "پیام شما برای تیم ادمین ثبت می‌شود.",
                KeyboardBuilder::backToMain()
            );
            $this->telegram->answerCallbackQuery($callbackId);
            return;
        }

        if (str_starts_with($data, 'buy:type:')) {
            $typeId = (int) substr($data, strlen('buy:type:'));
            $stockOnly = $this->settings->get('preorder_mode', '0') === '1';
            $packages = $this->database->getActivePackagesByTypeWithStock($typeId, $stockOnly);
            if ($packages === []) {
                $this->telegram->answerCallbackQuery($callbackId, 'پکیجی برای این نوع سرویس یافت نشد.');
                return;
            }

            $rows = [];
            foreach ($packages as $pkg) {
                $price = $this->database->effectivePackagePrice($userId, $pkg);
                $stockText = isset($pkg['stock']) ? (' | موجودی: ' . (int) $pkg['stock']) : '';
                $label = sprintf('%s | %sGB | %s روز | %s تومان%s', (string) $pkg['name'], (string) $pkg['volume_gb'], (string) $pkg['duration_days'], (string) $price, $stockText);
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

        if (str_starts_with($data, 'renew:') && !str_starts_with($data, 'renew:p:')) {
            if ($this->settings->get('manual_renewal_enabled', '1') !== '1') {
                $this->telegram->answerCallbackQuery($callbackId, 'تمدید دستی غیرفعال است.');
                return;
            }
            $purchaseId = (int) substr($data, strlen('renew:'));
            $purchase = $this->database->getUserPurchaseForRenewal($userId, $purchaseId);
            if (!is_array($purchase)) {
                $this->telegram->answerCallbackQuery($callbackId, 'سفارش پیدا نشد.');
                return;
            }
            if ((int) ($purchase['is_test'] ?? 0) === 1) {
                $this->telegram->answerCallbackQuery($callbackId, 'تمدید برای سرویس تست ممکن نیست.');
                return;
            }

            $typeId = (int) ($purchase['type_id'] ?? 0);
            $packages = $this->database->getActivePackagesByType($typeId);
            if ($packages === []) {
                $this->telegram->answerCallbackQuery($callbackId, 'پکیج تمدید یافت نشد.');
                return;
            }
            $rows = [];
            foreach ($packages as $pkg) {
                $rows[] = [[
                    'text' => sprintf('%s | %sGB | %s روز | %s تومان', (string) $pkg['name'], (string) $pkg['volume_gb'], (string) $pkg['duration_days'], (string) $pkg['price']),
                    'callback_data' => 'renew:p:' . $purchaseId . ':' . (int) $pkg['id'],
                ]];
            }
            $rows[] = [['text' => '🔙 بازگشت', 'callback_data' => 'my_configs']];
            $this->telegram->editMessageText(
                $chatId,
                $messageId,
                "♻️ <b>انتخاب پکیج تمدید</b>\n\n"
                . "سفارش: <code>#{$purchaseId}</code>\n"
                . "سرویس فعلی: <b>" . htmlspecialchars((string) ($purchase['service_name'] ?? '-')) . "</b>\n"
                . "پکیج فعلی: <b>" . htmlspecialchars((string) ($purchase['package_name'] ?? '-')) . "</b>\n\n"
                . "پکیج تمدید را انتخاب کنید:",
                ['inline_keyboard' => $rows]
            );
            $this->telegram->answerCallbackQuery($callbackId);
            return;
        }

        if (str_starts_with($data, 'renew:p:')) {
            $payload = substr($data, strlen('renew:p:'));
            [$purchaseRaw, $packageRaw] = array_pad(explode(':', $payload, 2), 2, '');
            $purchaseId = (int) $purchaseRaw;
            $packageId = (int) $packageRaw;
            $purchase = $this->database->getUserPurchaseForRenewal($userId, $purchaseId);
            $package = $this->database->getPackage($packageId);
            if (!is_array($purchase) || $package === null) {
                $this->telegram->answerCallbackQuery($callbackId, 'داده تمدید معتبر نیست.');
                return;
            }

            $text = "💳 <b>پرداخت تمدید</b>\n\n"
                . "سفارش: <code>#{$purchaseId}</code>\n"
                . "پکیج تمدید: <b>" . htmlspecialchars((string) $package['name']) . "</b>\n"
                . "مبلغ: <b>" . (int) $package['price'] . "</b> تومان\n\n"
                . "روش پرداخت را انتخاب کنید:";

            $rows = [];
            $rows[] = [['text' => '💰 پرداخت با کیف پول', 'callback_data' => 'rpay:wallet:' . $purchaseId . ':' . $packageId]];
            if ($this->settings->get('gw_card_enabled', '0') === '1') {
                $rows[] = [['text' => '🏦 کارت به کارت', 'callback_data' => 'rpay:card:' . $purchaseId . ':' . $packageId]];
            }
            if ($this->settings->get('gw_crypto_enabled', '0') === '1') {
                $rows[] = [['text' => '💎 پرداخت کریپتو', 'callback_data' => 'rpay:crypto:' . $purchaseId . ':' . $packageId]];
            }
            if ($this->settings->get('gw_tetrapay_enabled', '0') === '1') {
                $rows[] = [['text' => '🏧 پرداخت TetraPay', 'callback_data' => 'rpay:tetrapay:' . $purchaseId . ':' . $packageId]];
            }
            if ($this->settings->get('gw_swapwallet_crypto_enabled', '0') === '1') {
                $rows[] = [['text' => '💠 SwapWallet Crypto', 'callback_data' => 'rpay:swapwallet_crypto:' . $purchaseId . ':' . $packageId]];
            }
            if ($this->settings->get('gw_tronpays_rial_enabled', '0') === '1') {
                $rows[] = [['text' => '🧾 TronPays Rial', 'callback_data' => 'rpay:tronpays_rial:' . $purchaseId . ':' . $packageId]];
            }
            $rows[] = [['text' => '🔙 بازگشت', 'callback_data' => 'renew:' . $purchaseId]];

            $this->telegram->editMessageText($chatId, $messageId, $text, ['inline_keyboard' => $rows]);
            $this->telegram->answerCallbackQuery($callbackId);
            return;
        }

        if (str_starts_with($data, 'rpay:wallet:')) {
            $payload = substr($data, strlen('rpay:wallet:'));
            [$purchaseRaw, $packageRaw] = array_pad(explode(':', $payload, 2), 2, '');
            $purchaseId = (int) $purchaseRaw;
            $packageId = (int) $packageRaw;
            $result = $this->database->walletPayRenewal($userId, $purchaseId, $packageId);
            if (!($result['ok'] ?? false)) {
                $msg = match ($result['error'] ?? '') {
                    'insufficient_balance' => '❌ موجودی کیف پول کافی نیست.',
                    'purchase_not_found' => '❌ سرویس قابل تمدید پیدا نشد.',
                    'test_not_renewable' => '❌ تمدید برای سرویس تست مجاز نیست.',
                    'type_mismatch' => '❌ پکیج انتخابی برای این سرویس معتبر نیست.',
                    default => '❌ پرداخت تمدید انجام نشد.',
                };
                $this->telegram->answerCallbackQuery($callbackId, $msg);
                return;
            }
            $this->telegram->editMessageText(
                $chatId,
                $messageId,
                "✅ <b>پرداخت تمدید با کیف پول انجام شد.</b>\n\n"
                . "سفارش شما در صف تحویل قرار گرفت.\n"
                . "شماره سفارش: <code>" . (int) ($result['pending_order_id'] ?? 0) . "</code>",
                KeyboardBuilder::backToMain()
            );
            $this->telegram->answerCallbackQuery($callbackId);
            return;
        }

        if (str_starts_with($data, 'rpay:swapwallet_crypto:verify:')) {
            $paymentId = (int) substr($data, strlen('rpay:swapwallet_crypto:verify:'));
            $payment = $this->database->getPaymentById($paymentId);
            if ($payment === null) {
                $this->telegram->answerCallbackQuery($callbackId, 'پرداخت پیدا نشد.');
                return;
            }
            $invoiceId = (string) ($payment['gateway_ref'] ?? '');
            $verify = $this->gateways->checkSwapwalletCryptoInvoice($invoiceId);
            if (($verify['ok'] ?? false) && ($verify['paid'] ?? false)) {
                $changed = $this->database->markPaymentAndPendingPaidIfWaitingGateway($paymentId);
                if ($changed) {
                    $this->telegram->editMessageText($chatId, $messageId, "✅ پرداخت SwapWallet تایید شد و درخواست تمدید در صف تحویل قرار گرفت.", KeyboardBuilder::backToMain());
                    $this->telegram->answerCallbackQuery($callbackId);
                    return;
                }
            }
            $this->telegram->answerCallbackQuery($callbackId, 'پرداخت هنوز تایید نشده است.');
            return;
        }

        if (str_starts_with($data, 'rpay:swapwallet_crypto:')) {
            $payload = substr($data, strlen('rpay:swapwallet_crypto:'));
            [$purchaseRaw, $packageRaw] = array_pad(explode(':', $payload, 2), 2, '');
            $purchaseId = (int) $purchaseRaw;
            $packageId = (int) $packageRaw;
            $purchase = $this->database->getUserPurchaseForRenewal($userId, $purchaseId);
            $package = $this->database->getPackage($packageId);
            if (!is_array($purchase) || $package === null) {
                $this->telegram->answerCallbackQuery($callbackId, 'داده تمدید معتبر نیست.');
                return;
            }
            $amount = (int) $package['price'];
            if (!$this->isGatewayAmountAllowed('swapwallet_crypto', $amount)) {
                $this->telegram->answerCallbackQuery($callbackId, 'مبلغ برای این درگاه مجاز نیست. محدوده: ' . $this->gatewayRangeText('swapwallet_crypto'));
                return;
            }
            $paymentId = $this->database->createPayment([
                'kind' => 'renewal',
                'user_id' => $userId,
                'package_id' => $packageId,
                'amount' => $amount,
                'payment_method' => 'swapwallet_crypto',
                'status' => 'waiting_gateway',
                'created_at' => gmdate('Y-m-d H:i:s'),
            ]);
            $pendingId = $this->database->createPendingOrder([
                'user_id' => $userId,
                'package_id' => $packageId,
                'payment_id' => $paymentId,
                'amount' => $amount,
                'payment_method' => 'swapwallet_crypto',
                'created_at' => gmdate('Y-m-d H:i:s'),
                'status' => 'waiting_payment',
            ]);
            $invoice = $this->gateways->createSwapwalletCryptoInvoice($amount, (string) $pendingId, 'TRON', 'Renewal');
            if (!($invoice['ok'] ?? false)) {
                $this->telegram->answerCallbackQuery($callbackId, 'خطا در ایجاد فاکتور SwapWallet.');
                return;
            }
            $invoiceId = (string) ($invoice['invoice_id'] ?? '');
            if ($invoiceId !== '') {
                $this->database->setPaymentGatewayRef($paymentId, $invoiceId);
            }
            $payUrl = (string) ($invoice['pay_url'] ?? '');
            $this->telegram->editMessageText(
                $chatId,
                $messageId,
                "💠 <b>پرداخت تمدید با SwapWallet</b>\n\n"
                . "سفارش: <code>{$pendingId}</code>\n"
                . "مبلغ: <b>{$amount}</b> تومان\n\n"
                . ($payUrl !== '' ? "لینک پرداخت:\n" . htmlspecialchars($payUrl) . "\n\n" : '')
                . "پس از پرداخت، دکمه بررسی را بزنید.",
                ['inline_keyboard' => [
                    ...($payUrl !== '' ? [[['text' => '💳 پرداخت', 'url' => $payUrl]]] : []),
                    [['text' => '🔄 بررسی پرداخت', 'callback_data' => 'rpay:swapwallet_crypto:verify:' . $paymentId]],
                    [['text' => '🔙 بازگشت', 'callback_data' => 'nav:main']],
                ]]
            );
            $this->telegram->answerCallbackQuery($callbackId);
            return;
        }

        if (str_starts_with($data, 'rpay:tronpays_rial:verify:')) {
            $paymentId = (int) substr($data, strlen('rpay:tronpays_rial:verify:'));
            $payment = $this->database->getPaymentById($paymentId);
            if ($payment === null) {
                $this->telegram->answerCallbackQuery($callbackId, 'پرداخت پیدا نشد.');
                return;
            }
            $invoiceId = (string) ($payment['gateway_ref'] ?? '');
            $verify = $this->gateways->checkTronpaysRialInvoice($invoiceId);
            if (($verify['ok'] ?? false) && ($verify['paid'] ?? false)) {
                $changed = $this->database->markPaymentAndPendingPaidIfWaitingGateway($paymentId);
                if ($changed) {
                    $this->telegram->editMessageText($chatId, $messageId, "✅ پرداخت TronPays تایید شد و درخواست تمدید در صف تحویل قرار گرفت.", KeyboardBuilder::backToMain());
                    $this->telegram->answerCallbackQuery($callbackId);
                    return;
                }
            }
            $this->telegram->answerCallbackQuery($callbackId, 'پرداخت هنوز تایید نشده است.');
            return;
        }

        if (str_starts_with($data, 'rpay:tronpays_rial:')) {
            $payload = substr($data, strlen('rpay:tronpays_rial:'));
            [$purchaseRaw, $packageRaw] = array_pad(explode(':', $payload, 2), 2, '');
            $purchaseId = (int) $purchaseRaw;
            $packageId = (int) $packageRaw;
            $purchase = $this->database->getUserPurchaseForRenewal($userId, $purchaseId);
            $package = $this->database->getPackage($packageId);
            if (!is_array($purchase) || $package === null) {
                $this->telegram->answerCallbackQuery($callbackId, 'داده تمدید معتبر نیست.');
                return;
            }
            $amount = (int) $package['price'];
            if (!$this->isGatewayAmountAllowed('tronpays_rial', $amount)) {
                $this->telegram->answerCallbackQuery($callbackId, 'مبلغ برای این درگاه مجاز نیست. محدوده: ' . $this->gatewayRangeText('tronpays_rial'));
                return;
            }
            $paymentId = $this->database->createPayment([
                'kind' => 'renewal',
                'user_id' => $userId,
                'package_id' => $packageId,
                'amount' => $amount,
                'payment_method' => 'tronpays_rial',
                'status' => 'waiting_gateway',
                'created_at' => gmdate('Y-m-d H:i:s'),
            ]);
            $pendingId = $this->database->createPendingOrder([
                'user_id' => $userId,
                'package_id' => $packageId,
                'payment_id' => $paymentId,
                'amount' => $amount,
                'payment_method' => 'tronpays_rial',
                'created_at' => gmdate('Y-m-d H:i:s'),
                'status' => 'waiting_payment',
            ]);
            $invoice = $this->gateways->createTronpaysRialInvoice($amount, 'rnw-' . $userId . '-' . $packageId . '-' . time());
            if (!($invoice['ok'] ?? false)) {
                $this->telegram->answerCallbackQuery($callbackId, 'خطا در ایجاد فاکتور TronPays.');
                return;
            }
            $invoiceId = (string) ($invoice['invoice_id'] ?? '');
            if ($invoiceId !== '') {
                $this->database->setPaymentGatewayRef($paymentId, $invoiceId);
            }
            $payUrl = (string) ($invoice['pay_url'] ?? '');
            $this->telegram->editMessageText(
                $chatId,
                $messageId,
                "🧾 <b>پرداخت تمدید با TronPays</b>\n\n"
                . "سفارش: <code>{$pendingId}</code>\n"
                . "مبلغ: <b>{$amount}</b> تومان\n\n"
                . ($payUrl !== '' ? "لینک پرداخت:\n" . htmlspecialchars($payUrl) . "\n\n" : '')
                . "پس از پرداخت، دکمه بررسی را بزنید.",
                ['inline_keyboard' => [
                    ...($payUrl !== '' ? [[['text' => '💳 پرداخت', 'url' => $payUrl]]] : []),
                    [['text' => '🔄 بررسی پرداخت', 'callback_data' => 'rpay:tronpays_rial:verify:' . $paymentId]],
                    [['text' => '🔙 بازگشت', 'callback_data' => 'nav:main']],
                ]]
            );
            $this->telegram->answerCallbackQuery($callbackId);
            return;
        }

        if (str_starts_with($data, 'rpay:card:') || str_starts_with($data, 'rpay:crypto:') || (str_starts_with($data, 'rpay:tetrapay:') && !str_starts_with($data, 'rpay:tetrapay:check:'))) {
            $method = str_starts_with($data, 'rpay:card:') ? 'card' : (str_starts_with($data, 'rpay:crypto:') ? 'crypto' : 'tetrapay');
            $payload = substr($data, strlen('rpay:' . $method . ':'));
            [$purchaseRaw, $packageRaw] = array_pad(explode(':', $payload, 2), 2, '');
            $purchaseId = (int) $purchaseRaw;
            $packageId = (int) $packageRaw;
            $purchase = $this->database->getUserPurchaseForRenewal($userId, $purchaseId);
            $package = $this->database->getPackage($packageId);
            if (!is_array($purchase) || $package === null) {
                $this->telegram->answerCallbackQuery($callbackId, 'داده تمدید معتبر نیست.');
                return;
            }

            $amount = (int) $package['price'];
            $gatewayKey = $method === 'card' ? 'card' : ($method === 'crypto' ? 'crypto' : 'tetrapay');
            if (!$this->isGatewayAmountAllowed($gatewayKey, $amount)) {
                $this->telegram->answerCallbackQuery($callbackId, 'مبلغ برای این درگاه مجاز نیست. محدوده: ' . $this->gatewayRangeText($gatewayKey));
                return;
            }
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
                $this->telegram->editMessageText($chatId, $messageId, $text, KeyboardBuilder::backToMain());
                $this->telegram->answerCallbackQuery($callbackId);
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
                $this->telegram->editMessageText($chatId, $messageId, $text, KeyboardBuilder::backToMain());
                $this->telegram->answerCallbackQuery($callbackId);
                return;
            }

            $tp = $this->gateways->createTetrapayOrder($amount, (string) $pendingId);
            if (($tp['ok'] ?? false) === true) {
                $authority = (string) ($tp['authority'] ?? '');
                if ($authority !== '') {
                    $this->database->setPaymentGatewayRef($paymentId, $authority);
                }
                $this->database->setPaymentProviderPayload($paymentId, [
                    'source' => 'tetrapay_create_renewal',
                    'response' => $tp,
                ]);
                $text = "🏧 <b>پرداخت TetraPay (تمدید)</b>\n\n"
                    . "سفارش: <code>{$pendingId}</code>\n"
                    . "برای پرداخت آنلاین روی لینک زیر بزنید:\n"
                    . htmlspecialchars((string) $tp['pay_url']) . "\n\n"
                    . "بعد از پرداخت، دکمه بررسی را بزنید.";
                $this->telegram->editMessageText(
                    $chatId,
                    $messageId,
                    $text,
                    ['inline_keyboard' => [
                        [['text' => '🔄 بررسی پرداخت', 'callback_data' => 'rpay:tetrapay:check:' . $paymentId]],
                        [['text' => '🔙 بازگشت', 'callback_data' => 'nav:main']],
                    ]]
                );
                $this->telegram->answerCallbackQuery($callbackId);
                return;
            }

            $this->telegram->editMessageText(
                $chatId,
                $messageId,
                "🏧 <b>پرداخت TetraPay (تمدید)</b>\n\n"
                . "شناسه سفارش: <code>{$pendingId}</code>\n"
                . "مبلغ: <b>{$amount}</b> تومان\n\n"
                . "ارتباط با درگاه آنلاین برقرار نشد. لطفاً از کارت‌به‌کارت یا کیف پول استفاده کنید.",
                KeyboardBuilder::backToMain()
            );
            $this->telegram->answerCallbackQuery($callbackId);
            return;
        }

        if (str_starts_with($data, 'buy:pkg:')) {
            if ($this->settings->get('purchase_rules_enabled', '0') === '1' && !$this->database->hasAcceptedPurchaseRules($userId)) {
                $rulesText = trim($this->settings->get('purchase_rules_text', ''));
                $rulesText = $rulesText !== '' ? $rulesText : 'لطفاً قوانین خرید را بپذیرید.';
                $this->telegram->editMessageText(
                    $chatId,
                    $messageId,
                    "📜 <b>قوانین خرید</b>

" . $rulesText,
                    ['inline_keyboard' => [
                        [['text' => '✅ قوانین را می‌پذیرم', 'callback_data' => 'buy:rules:accept']],
                        [['text' => '🔙 بازگشت', 'callback_data' => 'buy:start']],
                    ]]
                );
                $this->telegram->answerCallbackQuery($callbackId);
                return;
            }

            $packageId = (int) substr($data, strlen('buy:pkg:'));
            $package = $this->database->getPackage($packageId);
            if ($package === null) {
                $this->telegram->answerCallbackQuery($callbackId, 'پکیج پیدا نشد.');
                return;
            }

            $text = "💰 <b>پرداخت سفارش</b>\n\n"
                . "پکیج: <b>" . htmlspecialchars((string) $package['name']) . "</b>\n"
                . "قیمت: <b>" . (int) $this->database->effectivePackagePrice($userId, $package) . "</b> تومان\n\n"
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
            if ($this->settings->get('gw_swapwallet_crypto_enabled', '0') === '1') {
                $rows[] = [['text' => '💠 SwapWallet Crypto', 'callback_data' => 'pay:swapwallet_crypto:' . $packageId]];
            }
            if ($this->settings->get('gw_tronpays_rial_enabled', '0') === '1') {
                $rows[] = [['text' => '🧾 TronPays Rial', 'callback_data' => 'pay:tronpays_rial:' . $packageId]];
            }
            $rows[] = [['text' => '🔙 بازگشت', 'callback_data' => 'buy:type:' . (int) $package['type_id']]];
            $keyboard = ['inline_keyboard' => $rows];
            $this->telegram->editMessageText($chatId, $messageId, $text, $keyboard);
            $this->telegram->answerCallbackQuery($callbackId);
            return;
        }

        if ($data === 'buy:rules:accept') {
            $this->database->acceptPurchaseRules($userId);
            $this->telegram->answerCallbackQuery($callbackId, '✅ قوانین ثبت شد.');
            $this->handle(['callback_query' => ['id' => $callbackId, 'from' => $fromUser, 'message' => $message, 'data' => 'buy:start']]);
            return;
        }

        if (str_starts_with($data, 'buy:crypto:coins:')) {
            $packageId = (int) substr($data, strlen('buy:crypto:coins:'));
            if (!$this->ensurePurchaseAllowedForPackage($userId, $packageId, $callbackId)) {
                return;
            }
            $coins = [
                'tron' => 'TRON',
                'ton' => 'TON',
                'usdt_bep20' => 'USDT(BEP20)',
                'usdc_bep20' => 'USDC(BEP20)',
                'ltc' => 'LTC',
            ];
            $rows = [];
            foreach ($coins as $coinKey => $label) {
                $rows[] = [[
                    'text' => '💠 ' . $label,
                    'callback_data' => 'buy:crypto:pay:' . $packageId . ':' . $coinKey,
                ]];
            }
            $rows[] = [['text' => '🔙 بازگشت', 'callback_data' => 'buy:pkg:' . $packageId]];
            $this->telegram->editMessageText(
                $chatId,
                $messageId,
                '💎 ارز موردنظر را انتخاب کنید:',
                ['inline_keyboard' => $rows]
            );
            $this->telegram->answerCallbackQuery($callbackId);
            return;
        }

        if (str_starts_with($data, 'buy:card:') || str_starts_with($data, 'buy:crypto:pay:') || str_starts_with($data, 'buy:tetrapay:')) {
            $method = str_starts_with($data, 'buy:card:') ? 'card' : (str_starts_with($data, 'buy:crypto:pay:') ? 'crypto' : 'tetrapay');
            $packageId = (int) substr($data, strlen('buy:' . $method . ':'));
            $coin = null;
            if ($method === 'crypto') {
                $payload = substr($data, strlen('buy:crypto:pay:'));
                [$pkgRaw, $coinRaw] = array_pad(explode(':', $payload, 2), 2, '');
                $packageId = (int) $pkgRaw;
                $coin = trim($coinRaw);
            }
            $package = $this->database->getPackage($packageId);
            if ($package === null) {
                $this->telegram->answerCallbackQuery($callbackId, 'پکیج پیدا نشد.');
                return;
            }
            if (!$this->ensurePurchaseAllowedForPackage($userId, (int) $package['id'], $callbackId)) {
                return;
            }

            $amount = $this->database->effectivePackagePrice($userId, $package);
            $paymentId = $this->database->createPayment([
                'kind' => 'purchase',
                'user_id' => $userId,
                'package_id' => $packageId,
                'amount' => $amount,
                'payment_method' => $method === 'crypto' ? ('crypto:' . ($coin ?: 'unknown')) : $method,
                'status' => $method === 'tetrapay' ? 'waiting_gateway' : 'waiting_admin',
                'gateway_ref' => null,
                'created_at' => gmdate('Y-m-d H:i:s'),
            ]);
            $pendingId = $this->database->createPendingOrder([
                'user_id' => $userId,
                'package_id' => $packageId,
                'payment_id' => $paymentId,
                'amount' => $amount,
                'payment_method' => $method === 'crypto' ? ('crypto:' . ($coin ?: 'unknown')) : $method,
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
            } elseif ($method === 'crypto') {
                $address = htmlspecialchars($this->gateways->cryptoAddress((string) $coin));
                $text = "💎 <b>پرداخت کریپتو</b>\n\n"
                    . "شناسه سفارش: <code>{$pendingId}</code>\n"
                    . "ارز: <b>" . htmlspecialchars((string) strtoupper((string) $coin)) . "</b>\n"
                    . "مبلغ معادل ریالی: <b>{$amount}</b> تومان\n\n"
                    . ($address !== '' ? "آدرس کیف پول:\n<code>{$address}</code>\n\n" : '')
                    . "پس از پرداخت، TX Hash و مقدار کوین پرداختی را بفرستید.\n"
                    . "مثال: <code>TX_HASH 12.345</code>";
                $this->database->setUserState($userId, 'await_crypto_tx', ['payment_id' => $paymentId]);
            } else {
                $tp = $this->gateways->createTetrapayOrder($amount, (string) $pendingId);
                if (($tp['ok'] ?? false) === true) {
                    $authority = (string) ($tp['authority'] ?? '');
                    if ($authority !== '') {
                        $this->database->setPaymentGatewayRef($paymentId, $authority);
                    }
                    $this->database->setPaymentProviderPayload($paymentId, [
                        'source' => 'tetrapay_create',
                        'response' => $tp,
                    ]);
                    $text = "🏧 <b>پرداخت TetraPay</b>\n\n"
                        . "سفارش: <code>{$pendingId}</code>\n"
                        . "برای پرداخت آنلاین روی لینک زیر بزنید:\n"
                        . htmlspecialchars((string) $tp['pay_url']) . "\n\n"
                        . "بعد از پرداخت، دکمه بررسی را بزنید.";
                    $this->telegram->editMessageText(
                        $chatId,
                        $messageId,
                        $text,
                        ['inline_keyboard' => [
                            [['text' => '🔄 بررسی پرداخت', 'callback_data' => 'buy:tetrapay:check:' . $paymentId]],
                            [['text' => '🔙 بازگشت', 'callback_data' => 'nav:main']],
                        ]]
                    );
                    $this->telegram->answerCallbackQuery($callbackId);
                    return;
                }
                $text = "🏧 <b>پرداخت TetraPay</b>\n\n"
                    . "شناسه سفارش: <code>{$pendingId}</code>\n"
                    . "مبلغ: <b>{$amount}</b> تومان\n\n"
                    . "ارتباط با درگاه آنلاین برقرار نشد. لطفاً از کارت‌به‌کارت یا کیف پول استفاده کنید.";
            }

            $this->telegram->editMessageText($chatId, $messageId, $text, KeyboardBuilder::backToMain());
            $this->telegram->answerCallbackQuery($callbackId);
            return;
        }

        if (str_starts_with($data, 'buy:tetrapay:check:')) {
            $paymentId = (int) substr($data, strlen('buy:tetrapay:check:'));
            $payment = $this->database->getPaymentById($paymentId);
            if ($payment === null) {
                $this->telegram->answerCallbackQuery($callbackId, 'پرداخت پیدا نشد.');
                return;
            }
            if (($payment['status'] ?? '') === 'paid' || ($payment['status'] ?? '') === 'completed') {
                $this->telegram->answerCallbackQuery($callbackId, 'پرداخت قبلاً تایید شده.');
                return;
            }
            $gatewayRef = (string) ($payment['gateway_ref'] ?? '');
            $verify = $this->gateways->verifyTetrapay($gatewayRef);
            if (($verify['ok'] ?? false)) {
                $this->database->setPaymentProviderPayload($paymentId, [
                    'source' => 'tetrapay_verify',
                    'response' => $verify,
                ]);
            }
            if (($verify['ok'] ?? false) && ($verify['paid'] ?? false)) {
                $changed = $this->database->markPaymentAndPendingPaidIfWaitingGateway($paymentId);
                if ($changed) {
                    $this->telegram->editMessageText(
                        $chatId,
                        $messageId,
                        "✅ پرداخت تتراپی تایید شد.\nسفارش شما در صف تحویل قرار گرفت.",
                        KeyboardBuilder::backToMain()
                    );
                    $this->telegram->answerCallbackQuery($callbackId);
                    return;
                }
                $this->telegram->answerCallbackQuery($callbackId, 'این پرداخت قبلاً پردازش شده است.');
                return;
            }
            $this->telegram->answerCallbackQuery($callbackId, 'پرداخت هنوز تایید نشده است.');
            return;
        }

        if (str_starts_with($data, 'rpay:tetrapay:check:')) {
            $paymentId = (int) substr($data, strlen('rpay:tetrapay:check:'));
            $payment = $this->database->getPaymentById($paymentId);
            if ($payment === null) {
                $this->telegram->answerCallbackQuery($callbackId, 'پرداخت پیدا نشد.');
                return;
            }
            if (($payment['status'] ?? '') === 'paid' || ($payment['status'] ?? '') === 'completed') {
                $this->telegram->answerCallbackQuery($callbackId, 'پرداخت قبلاً تایید شده.');
                return;
            }
            $gatewayRef = (string) ($payment['gateway_ref'] ?? '');
            $verify = $this->gateways->verifyTetrapay($gatewayRef);
            if (($verify['ok'] ?? false)) {
                $this->database->setPaymentProviderPayload($paymentId, [
                    'source' => 'tetrapay_verify_renewal',
                    'response' => $verify,
                ]);
            }
            if (($verify['ok'] ?? false) && ($verify['paid'] ?? false)) {
                $changed = $this->database->markPaymentAndPendingPaidIfWaitingGateway($paymentId);
                if ($changed) {
                    $this->telegram->editMessageText(
                        $chatId,
                        $messageId,
                        "✅ <b>پرداخت تمدید تایید شد.</b>\n"
                        . "درخواست شما در صف تحویل قرار گرفت.",
                        KeyboardBuilder::backToMain()
                    );
                    $this->telegram->answerCallbackQuery($callbackId);
                    return;
                }
            }
            $this->telegram->answerCallbackQuery($callbackId, 'پرداخت هنوز تایید نشده است.');
            return;
        }

        if (str_starts_with($data, 'buy:wallet:')) {
            $packageId = (int) substr($data, strlen('buy:wallet:'));
            if (!$this->ensurePurchaseAllowedForPackage($userId, $packageId, $callbackId)) {
                return;
            }
            $result = $this->database->walletPayPackage($userId, $packageId);
            if (!($result['ok'] ?? false)) {
                if (($result['error'] ?? '') === 'insufficient_balance') {
                    $this->telegram->answerCallbackQuery($callbackId, 'موجودی کیف پول کافی نیست.');
                    return;
                }
                if (($result['error'] ?? '') === 'no_stock') {
                    $this->telegram->answerCallbackQuery($callbackId, 'برای این پکیج موجودی ثبت‌شده وجود ندارد.');
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

        $this->telegram->answerCallbackQuery($callbackId, 'عملیات نامعتبر یا پشتیبانی‌نشده.');
    }

    private function encodeStockQueryToken(?string $query): string
    {
        $q = trim((string) ($query ?? ''));
        if ($q === '') {
            return '';
        }
        $raw = base64_encode($q);
        return rtrim(strtr($raw, '+/', '-_'), '=');
    }

    private function decodeStockQueryToken(string $token): ?string
    {
        $t = trim($token);
        if ($t === '') {
            return null;
        }
        $raw = strtr($t, '-_', '+/');
        $pad = strlen($raw) % 4;
        if ($pad > 0) {
            $raw .= str_repeat('=', 4 - $pad);
        }
        $decoded = base64_decode($raw, true);
        if ($decoded === false) {
            return null;
        }
        return trim((string) $decoded);
    }

    private function adminHasPermission(int $userId, string $perm): bool
    {
        $perms = $this->database->getAdminPermissions($userId);
        if ((bool) ($perms['full'] ?? false)) {
            return true;
        }
        return (bool) ($perms[$perm] ?? false);
    }

    private function requiredAdminPermission(string $data): ?string
    {
        if (str_starts_with($data, 'admin:admins')) {
            return 'full';
        }
        if (str_starts_with($data, 'admin:broadcast') || str_starts_with($data, 'admin:pins') || $data === 'admin:pins') {
            return 'broadcast';
        }
        if (str_starts_with($data, 'admin:type') || str_starts_with($data, 'admin:pkg') || $data === 'admin:types') {
            return 'types';
        }
        if (str_starts_with($data, 'admin:stock') || $data === 'admin:stock') {
            return 'stock';
        }
        if (str_starts_with($data, 'admin:user') || $data === 'admin:users') {
            return 'users';
        }
        if (str_starts_with($data, 'admin:settings') || $data === 'admin:settings') {
            return 'settings';
        }
        if (str_starts_with($data, 'admin:groupops') || $data === 'admin:groupops') {
            return 'settings';
        }
        if (str_starts_with($data, 'admin:payment') || str_starts_with($data, 'admin:deliver') || $data === 'admin:payments' || $data === 'admin:deliveries') {
            return 'payments';
        }
        if (str_starts_with($data, 'admin:req') || $data === 'admin:requests') {
            return 'requests';
        }
        if (str_starts_with($data, 'admin:agents') || $data === 'admin:agents') {
            return 'agents';
        }
        if (str_starts_with($data, 'admin:panels') || $data === 'admin:panels') {
            return 'panels';
        }
        return null;
    }

    private function ensurePurchaseAllowedForPackage(int $userId, int $packageId, string $callbackId): bool
    {
        if ($this->settings->get('shop_open', '1') !== '1') {
            $this->telegram->answerCallbackQuery($callbackId, 'فروشگاه در حال حاضر بسته است.');
            return false;
        }

        if ($this->settings->get('purchase_rules_enabled', '0') === '1' && !$this->database->hasAcceptedPurchaseRules($userId)) {
            $this->telegram->answerCallbackQuery($callbackId, 'ابتدا قوانین خرید را بپذیرید.');
            return false;
        }

        if ($this->settings->get('preorder_mode', '0') === '1' && !$this->database->packageHasAvailableStock($packageId)) {
            $this->telegram->answerCallbackQuery($callbackId, 'این پکیج در حال حاضر موجودی ندارد.');
            return false;
        }

        return true;
    }

    private function isGatewayAmountAllowed(string $gateway, int $amount): bool
    {
        $min = (int) $this->settings->get('gw_' . $gateway . '_min', '0');
        $maxRaw = trim($this->settings->get('gw_' . $gateway . '_max', '0'));
        $max = (int) $maxRaw;
        if ($min > 0 && $amount < $min) {
            return false;
        }
        if ($max > 0 && $amount > $max) {
            return false;
        }
        return true;
    }

    private function gatewayRangeText(string $gateway): string
    {
        $min = (int) $this->settings->get('gw_' . $gateway . '_min', '0');
        $max = (int) $this->settings->get('gw_' . $gateway . '_max', '0');
        if ($min <= 0 && $max <= 0) {
            return 'بدون محدودیت';
        }
        if ($min > 0 && $max > 0) {
            return "{$min} تا {$max} تومان";
        }
        if ($min > 0) {
            return "حداقل {$min} تومان";
        }
        return "حداکثر {$max} تومان";
    }

    private function notifyAdminsRenewalRequest(int $userId, int $purchaseId, int $packageId, int $amount, string $method): void
    {
        $purchase = $this->database->getUserPurchaseForRenewal($userId, $purchaseId);
        $package = $this->database->getPackage($packageId);
        if (!is_array($purchase) || $package === null) {
            return;
        }
        $configId = (int) ($purchase['config_id'] ?? 0);
        $service = htmlspecialchars((string) ($purchase['service_name'] ?? '-'));
        $pkgName = htmlspecialchars((string) ($package['name'] ?? '-'));
        $text = "♻️ <b>درخواست تمدید</b> (" . htmlspecialchars($method) . ")\n\n"
            . "کاربر: <code>{$userId}</code>\n"
            . "سرویس فعلی: <b>{$service}</b>\n"
            . "پکیج تمدید: <b>{$pkgName}</b>\n"
            . "مبلغ: <b>{$amount}</b> تومان";
        $keyboard = $configId > 0
            ? ['inline_keyboard' => [[['text' => '✅ تایید تمدید', 'callback_data' => 'renew:confirm:' . $configId . ':' . $userId]]]]
            : null;
        foreach (Config::adminIds() as $adminId) {
            $this->telegram->sendMessage((int) $adminId, $text, $keyboard);
        }
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

    private function ensureGroupTopics(): string
    {
        $groupIdRaw = trim($this->settings->get('group_id', ''));
        if ($groupIdRaw === '' || !preg_match('/^-?\d+$/', $groupIdRaw)) {
            return 'Group ID تنظیم نشده است.';
        }
        $groupId = (int) $groupIdRaw;
        $created = 0;
        foreach (self::GROUP_TOPICS as $key => $title) {
            $settingKey = 'group_topic_' . $key;
            $existing = trim($this->settings->get($settingKey, ''));
            if ($existing !== '' && preg_match('/^\d+$/', $existing)) {
                continue;
            }
            $threadId = $this->telegram->createForumTopic($groupId, $title);
            if ($threadId !== null && $threadId > 0) {
                $this->settings->set($settingKey, (string) $threadId);
                $created++;
            }
        }
        return $created > 0 ? "✅ {$created} تاپیک ایجاد شد." : '✅ تاپیک‌ها از قبل تنظیم شده‌اند.';
    }

    private function sendSettingsBackup(int $requestChatId): bool
    {
        $rows = $this->database->pdo()->query('SELECT `key`, `value` FROM settings ORDER BY `key` ASC')->fetchAll(\PDO::FETCH_ASSOC);
        if (!is_array($rows)) {
            return false;
        }
        $settings = [];
        foreach ($rows as $row) {
            $k = (string) ($row['key'] ?? '');
            if ($k === '') {
                continue;
            }
            $settings[$k] = (string) ($row['value'] ?? '');
        }
        $payload = [
            'generated_at' => gmdate('c'),
            'kind' => 'settings_backup',
            'settings' => $settings,
        ];
        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if (!is_string($json) || $json === '') {
            return false;
        }
        $tmp = tempnam(sys_get_temp_dir(), 'cfg_backup_');
        if (!is_string($tmp) || $tmp === '') {
            return false;
        }
        $file = $tmp . '.json';
        @rename($tmp, $file);
        file_put_contents($file, $json);
        $caption = "💾 بکاپ تنظیمات\n" . gmdate('Y-m-d H:i:s') . " UTC";
        $this->telegram->sendDocumentFile($requestChatId, $file, $caption);

        $groupIdRaw = trim($this->settings->get('group_id', ''));
        $topicRaw = trim($this->settings->get('group_topic_backup', ''));
        if (preg_match('/^-?\d+$/', $groupIdRaw) && preg_match('/^\d+$/', $topicRaw)) {
            $this->telegram->sendDocumentFile((int) $groupIdRaw, $file, $caption, (int) $topicRaw);
        }
        @unlink($file);
        return true;
    }
}
