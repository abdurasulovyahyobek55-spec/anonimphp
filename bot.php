<?php
// bot.php

// Chiqish oqimini sozlash
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database/Db.php';
require_once __DIR__ . '/database/Models.php';
require_once __DIR__ . '/utils/Helpers.php';
require_once __DIR__ . '/handlers/StartHandler.php';
require_once __DIR__ . '/handlers/AdminHandler.php';
require_once __DIR__ . '/handlers/MessageHandler.php';
require_once __DIR__ . '/handlers/ReplyHandler.php';

/**
 * Konsolga log yozish.
 */
function logMsg($level, $message) {
    $date = date('Y-m-d H:i:s');
    echo "[{$date}] [{$level}] {$message}\n";
}

function main() {
    logMsg("INFO", "Bot tayyorlanmoqda...");

    if (empty(BOT_TOKEN)) {
        logMsg("ERROR", "XATO: .env faylida BOT_TOKEN topilmadi!");
        return;
    }

    // Ma'lumotlar bazasini ishga tushirish
    try {
        Db::initDb();
        logMsg("INFO", "Ma'lumotlar bazasi muvaffaqiyatli ishga tushirildi.");
    } catch (Exception $e) {
        logMsg("ERROR", "Ma'lumotlar bazasini ishga tushirishda xatolik: " . $e->getMessage());
        return;
    }

    $botInfo = Telegram::api('getMe');
    if ($botInfo && isset($botInfo['ok']) && $botInfo['ok']) {
        $botUsername = $botInfo['result']['username'];
        logMsg("INFO", "Bot muvaffaqiyatli ulandi: @{$botUsername}");
    } else {
        logMsg("ERROR", "Bot tokeni noto'g'ri yoki Telegram serveriga ulanib bo'lmadi.");
        return;
    }

    logMsg("INFO", "Bot ishga tushdi va xabarlarni kutmoqda (Long Polling)...");

    $offset = 0;
    while (true) {
        $updates = Telegram::api('getUpdates', [
            'offset' => $offset,
            'timeout' => 30
        ]);

        if ($updates && isset($updates['ok']) && $updates['ok'] && !empty($updates['result'])) {
            foreach ($updates['result'] as $update) {
                $offset = $update['update_id'] + 1;

                try {
                    processUpdate($update);
                } catch (Exception $e) {
                    logMsg("ERROR", "Update qayta ishlashda xatolik: " . $e->getMessage());
                }
            }
        }

        // CPU yuklamasini kamaytirish uchun biroz kutish
        usleep(100000); // 100ms
    }
}

/**
 * Har bir yangilanishni (update) qayta ishlash.
 */
function processUpdate($update) {
    if (!isset($update['message'])) {
        return;
    }

    $message = $update['message'];

    // Middleware: Foydalanuvchi ma'lumotlarini bazada avtomatik saqlash yoki yangilash
    if (isset($message['from'])) {
        $fromUser = $message['from'];
        $userId = $fromUser['id'];

        $existing = UserDB::getUser($userId);
        if (!$existing) {
            // Yangi foydalanuvchi — unikal kod bilan saqlash
            $code = generateUniqueCode();
            UserDB::saveUser(
                $userId,
                $fromUser['username'] ?? null,
                $fromUser['first_name'] ?? null,
                $fromUser['last_name'] ?? null,
                $code
            );
            logMsg("INFO", "Yangi foydalanuvchi qo'shildi: {$userId} (@" . ($fromUser['username'] ?? '') . ")");
        } else {
            // Mavjud foydalanuvchi — ma'lumotlarni yangilash
            UserDB::saveUser(
                $userId,
                $fromUser['username'] ?? null,
                $fromUser['first_name'] ?? null,
                $fromUser['last_name'] ?? null
            );
        }
    }

    // 1. Reply xabarlarini qayta ishlash
    if (isset($message['reply_to_message'])) {
        $handled = ReplyHandler::handle($message);
        if ($handled) {
            logMsg("INFO", "Javob xabari yuborildi: {$userId} dan");
            return;
        }
    }

    // 2. Buyruqlarni (commands) qayta ishlash
    if (isset($message['text']) && strpos(trim($message['text']), '/') === 0) {
        $text = trim($message['text']);
        $parts = explode(' ', $text, 2);
        $command = $parts[0];
        $args = isset($parts[1]) ? trim($parts[1]) : null;

        // Start/Help/Myid va boshqa umumiy buyruqlar
        if (StartHandler::handle($message, $command, $args)) {
            logMsg("INFO", "Buyruq qayta ishlandi (StartHandler): {$command} | user: {$userId}");
            return;
        }

        // Admin/Ishonchli buyruqlari
        if (AdminHandler::handle($message, $command, $args)) {
            logMsg("INFO", "Buyruq qayta ishlandi (AdminHandler): {$command} | user: {$userId}");
            return;
        }

        return; // Noma'lum buyruq
    }

    // 3. Regular xabarlarni (sessiya orqali anonim xabar) yuborish
    MessageHandler::handle($message);
    logMsg("INFO", "Anonim xabar yuborish so'rovi qayta ishlandi: user: {$userId}");
}

// Botni ishga tushirish
main();
