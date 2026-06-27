<?php
// handlers/AdminHandler.php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../database/Models.php';
require_once __DIR__ . '/../utils/Helpers.php';

class AdminHandler {
    /**
     * Admin/Ishonchli insonlar buyruqlarini qayta ishlash.
     */
    public static function handle($message, $command, $args = null) {
        $userId = $message['from']['id'];

        switch ($command) {
            case '/grant':
                self::grantCommand($message, $args);
                return true;
            case '/revoke':
                self::revokeCommand($message, $args);
                return true;
            case '/admins':
                self::adminsCommand($message);
                return true;
            case '/block':
                self::blockCommand($message, $args);
                return true;
            case '/unblock':
                self::unblockCommand($message, $args);
                return true;
            case '/stats':
                self::statsCommand($message);
                return true;
            case '/broadcast':
                self::broadcastCommand($message, $args);
                return true;
        }
        return false;
    }

    /**
     * Asosiy admin ekanligini tekshirish.
     */
    private static function isMainAdmin($userId) {
        return $userId === ADMIN_ID;
    }

    /**
     * Foydalanuvchiga ko'rish huquqi berish.
     */
    private static function grantCommand($message, $args) {
        $userId = $message['from']['id'];
        if (!self::isMainAdmin($userId)) {
            return; // Maxfiy buyruq, hech narsa qaytarmaymiz
        }

        if (empty($args)) {
            Telegram::api('sendMessage', [
                'chat_id' => $userId,
                'text' => "📋 <b>Foydalanish:</b>\n" .
                          "<code>/grant 123456789</code> — ID bo'yicha\n" .
                          "<code>/grant @username</code> — username bo'yicha",
                'parse_mode' => 'HTML'
            ]);
            return;
        }

        $target = trim($args);
        $targetId = null;

        if (strpos($target, '@') === 0) {
            $userData = UserDB::getUserByUsername($target);
            if (!$userData) {
                Telegram::api('sendMessage', [
                    'chat_id' => $userId,
                    'text' => "❌ <code>{$target}</code> topilmadi.",
                    'parse_mode' => 'HTML'
                ]);
                return;
            }
            $targetId = $userData['user_id'];
        } else {
            if (is_numeric($target)) {
                $targetId = (int)$target;
            } else {
                Telegram::api('sendMessage', [
                    'chat_id' => $userId,
                    'text' => "❌ Noto'g'ri format. ID raqam bo'lishi kerak."
                ]);
                return;
            }
        }

        if ($targetId === ADMIN_ID) {
            Telegram::api('sendMessage', [
                'chat_id' => $userId,
                'text' => "ℹ️ Siz asosiy adminsiz, huquq berishning hojati yo'q."
            ]);
            return;
        }

        $success = PrivilegedDB::addPrivileged($targetId, $userId);
        if ($success) {
            $userData = UserDB::getUser($targetId);
            $name = "Nomaʼlum";
            if ($userData) {
                $name = !empty($userData['first_name']) ? $userData['first_name'] : "";
                if (!empty($userData['username'])) {
                    $name .= " (@" . $userData['username'] . ")";
                }
            }

            Telegram::api('sendMessage', [
                'chat_id' => $userId,
                'text' => "✅ <b>{$name}</b> ga ko'rish huquqi berildi!\n" .
                          "🆔 ID: <code>{$targetId}</code>\n\n" .
                          "Endi u o'ziga kelgan anonim xabarlarning yuboruvchisini ko'ra oladi.",
                'parse_mode' => 'HTML'
            ]);
        } else {
            Telegram::api('sendMessage', [
                'chat_id' => $userId,
                'text' => "❌ Xatolik yuz berdi."
            ]);
        }
    }

    /**
     * Ko'rish huquqini olish.
     */
    private static function revokeCommand($message, $args) {
        $userId = $message['from']['id'];
        if (!self::isMainAdmin($userId)) {
            return;
        }

        if (empty($args)) {
            Telegram::api('sendMessage', [
                'chat_id' => $userId,
                'text' => "📋 <b>Foydalanish:</b>\n" .
                          "<code>/revoke 123456789</code> — ID bo'yicha\n" .
                          "<code>/revoke @username</code> — username bo'yicha",
                'parse_mode' => 'HTML'
            ]);
            return;
        }

        $target = trim($args);
        $targetId = null;

        if (strpos($target, '@') === 0) {
            $userData = UserDB::getUserByUsername($target);
            if (!$userData) {
                Telegram::api('sendMessage', [
                    'chat_id' => $userId,
                    'text' => "❌ <code>{$target}</code> topilmadi.",
                    'parse_mode' => 'HTML'
                ]);
                return;
            }
            $targetId = $userData['user_id'];
        } else {
            if (is_numeric($target)) {
                $targetId = (int)$target;
            } else {
                Telegram::api('sendMessage', [
                    'chat_id' => $userId,
                    'text' => "❌ Noto'g'ri format. ID raqam bo'lishi kerak."
                ]);
                return;
            }
        }

        $success = PrivilegedDB::removePrivileged($targetId);
        if ($success) {
            Telegram::api('sendMessage', [
                'chat_id' => $userId,
                'text' => "✅ <code>{$targetId}</code> dan ko'rish huquqi olib tashlandi.",
                'parse_mode' => 'HTML'
            ]);
        } else {
            Telegram::api('sendMessage', [
                'chat_id' => $userId,
                'text' => "❌ Bu foydalanuvchi ishonchli insonlar ro'yxatida topilmadi."
            ]);
        }
    }

    /**
     * Ishonchli insonlar ro'yxatini ko'rish.
     */
    private static function adminsCommand($message) {
        $userId = $message['from']['id'];
        if (!self::isMainAdmin($userId)) {
            return;
        }

        $privileged = PrivilegedDB::getAllPrivileged();

        $text = "🛡️ <b>Ishonchli insonlar ro'yxati:</b>\n\n" .
                "👑 <b>Asosiy admin:</b> <code>" . ADMIN_ID . "</code>\n\n";

        if (!empty($privileged)) {
            foreach ($privileged as $i => $p) {
                $num = $i + 1;
                $name = !empty($p['first_name']) ? $p['first_name'] : "Nomaʼlum";
                $username = !empty($p['username']) ? "@" . $p['username'] : "username yo'q";
                $text .= "{$num}. {$name} ({$username})\n   🆔 <code>{$p['user_id']}</code> | 📋 {$p['role']}\n\n";
            }
        } else {
            $text .= "📭 Hozircha ishonchli inson qo'shilmagan.\n\n" .
                     "💡 Qo'shish: <code>/grant user_id</code>";
        }

        Telegram::api('sendMessage', [
            'chat_id' => $userId,
            'text' => $text,
            'parse_mode' => 'HTML'
        ]);
    }

    /**
     * Foydalanuvchini bloklash.
     */
    private static function blockCommand($message, $args) {
        $userId = $message['from']['id'];
        if (!PrivilegedDB::isPrivileged($userId)) {
            return;
        }

        if (empty($args)) {
            Telegram::api('sendMessage', [
                'chat_id' => $userId,
                'text' => "📋 <b>Foydalanish:</b> <code>/block user_id</code>",
                'parse_mode' => 'HTML'
            ]);
            return;
        }

        $targetId = trim($args);
        if (!is_numeric($targetId)) {
            Telegram::api('sendMessage', [
                'chat_id' => $userId,
                'text' => "❌ Noto'g'ri format. ID raqam bo'lishi kerak."
            ]);
            return;
        }

        $targetId = (int)$targetId;

        if ($targetId === ADMIN_ID) {
            Telegram::api('sendMessage', [
                'chat_id' => $userId,
                'text' => "❌ Asosiy adminni bloklash mumkin emas."
            ]);
            return;
        }

        $success = UserDB::blockUser($targetId);
        if ($success) {
            Telegram::api('sendMessage', [
                'chat_id' => $userId,
                'text' => "🚫 <code>{$targetId}</code> bloklandi.\nBu foydalanuvchi endi anonim xabar yubora olmaydi.",
                'parse_mode' => 'HTML'
            ]);
        } else {
            Telegram::api('sendMessage', [
                'chat_id' => $userId,
                'text' => "❌ Foydalanuvchi topilmadi."
            ]);
        }
    }

    /**
     * Blokdan chiqarish.
     */
    private static function unblockCommand($message, $args) {
        $userId = $message['from']['id'];
        if (!PrivilegedDB::isPrivileged($userId)) {
            return;
        }

        if (empty($args)) {
            Telegram::api('sendMessage', [
                'chat_id' => $userId,
                'text' => "📋 <b>Foydalanish:</b> <code>/unblock user_id</code>",
                'parse_mode' => 'HTML'
            ]);
            return;
        }

        $targetId = trim($args);
        if (!is_numeric($targetId)) {
            Telegram::api('sendMessage', [
                'chat_id' => $userId,
                'text' => "❌ Noto'g'ri format. ID raqam bo'lishi kerak."
            ]);
            return;
        }

        $targetId = (int)$targetId;

        $success = UserDB::unblockUser($targetId);
        if ($success) {
            Telegram::api('sendMessage', [
                'chat_id' => $userId,
                'text' => "✅ <code>{$targetId}</code> blokdan chiqarildi.",
                'parse_mode' => 'HTML'
            ]);
        } else {
            Telegram::api('sendMessage', [
                'chat_id' => $userId,
                'text' => "❌ Foydalanuvchi topilmadi."
            ]);
        }
    }

    /**
     * Bot statistikasi.
     */
    private static function statsCommand($message) {
        $userId = $message['from']['id'];
        if (!PrivilegedDB::isPrivileged($userId)) {
            return;
        }

        $userCount = UserDB::getUserCount();
        $msgCount = MessageDB::getMessageCount();
        $todayCount = MessageDB::getTodayMessageCount();
        $privileged = PrivilegedDB::getAllPrivileged();

        $text = "📊 <b>Bot statistikasi</b>\n\n" .
                "👥 Jami foydalanuvchilar: <b>{$userCount}</b>\n" .
                "📩 Jami xabarlar: <b>{$msgCount}</b>\n" .
                "📅 Bugungi xabarlar: <b>{$todayCount}</b>\n" .
                "🛡️ Ishonchli insonlar: <b>" . count($privileged) . "</b>";

        Telegram::api('sendMessage', [
            'chat_id' => $userId,
            'text' => $text,
            'parse_mode' => 'HTML'
        ]);
    }

    /**
     * Hamma foydalanuvchilarga xabar yuborish.
     */
    private static function broadcastCommand($message, $args) {
        $userId = $message['from']['id'];
        if (!self::isMainAdmin($userId)) {
            return;
        }

        if (empty($args)) {
            Telegram::api('sendMessage', [
                'chat_id' => $userId,
                'text' => "📋 <b>Foydalanish:</b> <code>/broadcast Xabar matni</code>",
                'parse_mode' => 'HTML'
            ]);
            return;
        }

        $broadcastText = trim($args);
        $users = UserDB::getAllUsers();

        $statusMsg = Telegram::api('sendMessage', [
            'chat_id' => $userId,
            'text' => "📤 Xabar yuborilmoqda... 0/" . count($users)
        ]);

        if (!$statusMsg || !isset($statusMsg['result']['message_id'])) {
            return;
        }

        $statusMsgId = $statusMsg['result']['message_id'];
        $sent = 0;
        $failed = 0;

        foreach ($users as $user) {
            $response = Telegram::api('sendMessage', [
                'chat_id' => $user['user_id'],
                'text' => "📢 <b>Bot xabarnomasi:</b>\n\n{$broadcastText}",
                'parse_mode' => 'HTML'
            ]);

            if ($response && isset($response['ok']) && $response['ok']) {
                $sent++;
            } else {
                $failed++;
            }
        }

        $text = "✅ <b>Broadcast yakunlandi!</b>\n\n" .
                "📤 Yuborildi: <b>{$sent}</b>\n" .
                "❌ Xatolik: <b>{$failed}</b>\n" .
                "👥 Jami: <b>" . count($users) . "</b>";

        Telegram::api('editMessageText', [
            'chat_id' => $userId,
            'message_id' => $statusMsgId,
            'text' => $text,
            'parse_mode' => 'HTML'
        ]);
    }
}
