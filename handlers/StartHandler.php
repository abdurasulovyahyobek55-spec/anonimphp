<?php
// handlers/StartHandler.php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../database/Models.php';
require_once __DIR__ . '/../utils/Helpers.php';

class StartHandler {
    /**
     * Start, help, myid, mylink va cancel buyruqlarini qayta ishlash.
     */
    public static function handle($message, $command, $args = null) {
        switch ($command) {
            case '/start':
                if (!empty($args)) {
                    self::startWithLink($message, $args);
                } else {
                    self::startCommand($message);
                }
                return true;
            case '/help':
                self::helpCommand($message);
                return true;
            case '/myid':
                self::myidCommand($message);
                return true;
            case '/mylink':
                self::mylinkCommand($message);
                return true;
            case '/cancel':
                self::cancelCommand($message);
                return true;
        }
        return false;
    }

    /**
     * Foydalanuvchi deep-link orqali kirganida (birovning havolasini bosganda).
     */
    private static function startWithLink($message, $code) {
        $fromUser = $message['from'];
        $userId = $fromUser['id'];

        // Havola egasini topish
        $targetUser = UserDB::getUserByCode($code);

        if (!$targetUser) {
            Telegram::api('sendMessage', [
                'chat_id' => $userId,
                'text' => "❌ Bu havola noto'g'ri yoki eskirgan.\nBotdan foydalanish uchun /start buyrug'ini bosing."
            ]);
            return;
        }

        // O'zining havolasini bosgan bo'lsa
        if ($targetUser['user_id'] === $userId) {
            self::showMainMenu($message);
            return;
        }

        // Sessiyani boshlash
        ActiveSessionDB::setSession($userId, $targetUser['user_id']);

        $targetName = !empty($targetUser['first_name']) ? $targetUser['first_name'] : "Foydalanuvchi";

        $text = "👻 <b>Anonim rejim faollashtirildi!</b>\n\n" .
                "Siz hozir <b>{$targetName}</b> ga anonim xabar yozmoqdasiz.\n\n" .
                "📝 Xabaringizni yozing — u anonim tarzda yetkaziladi.\n" .
                "📎 Matn, rasm, video, audio — barchasi qo'llab-quvvatlanadi.\n\n" .
                "🔙 Bekor qilish uchun: /cancel\n" .
                "🏠 Bosh menyu: /start";

        Telegram::api('sendMessage', [
            'chat_id' => $userId,
            'text' => $text,
            'parse_mode' => 'HTML'
        ]);
    }

    /**
     * Oddiy /start buyrug'i.
     */
    private static function startCommand($message) {
        $userId = $message['from']['id'];
        // Sessiyani o'chirish
        ActiveSessionDB::deleteSession($userId);
        self::showMainMenu($message);
    }

    /**
     * Asosiy menyuni ko'rsatish.
     */
    public static function showMainMenu($message) {
        $fromUser = $message['from'];
        $userId = $fromUser['id'];

        $userData = UserDB::getUser($userId);
        if (!$userData) {
            $code = generateUniqueCode();
            UserDB::saveUser(
                $userId,
                $fromUser['username'] ?? null,
                $fromUser['first_name'] ?? null,
                $fromUser['last_name'] ?? null,
                $code
            );
            $userData = UserDB::getUser($userId);
        }

        $uniqueCode = $userData['unique_code'];
        $botUsername = self::getBotUsername();
        $link = "https://t.me/{$botUsername}?start={$uniqueCode}";

        $isPriv = PrivilegedDB::isPrivileged($userId);

        $welcomeText = "👋 <b>Assalomu alaykum, " . htmlspecialchars($fromUser['first_name']) . "!</b>\n\n" .
                       "🤖 Bu bot orqali siz <b>anonim xabarlar</b> qabul qilishingiz mumkin.\n\n" .
                       "🔗 <b>Sizning shaxsiy havolangiz:</b>\n" .
                       "<code>{$link}</code>\n\n" .
                       "☝️ Bu havolani do'stlaringiz, ijtimoiy tarmoqlarda yoki bio'ngizda ulashing.\n" .
                       "Havolani bosgan har bir inson sizga <b>anonim xabar</b> yuborishi mumkin!\n\n";

        if ($isPriv) {
            $welcomeText .= "🔐 <b>Sizda maxsus huquq mavjud!</b>\n" .
                            "Sizga kelgan anonim xabarlarning yuboruvchisini ko'rishingiz mumkin.\n\n";
        }

        $welcomeText .= "📋 <b>Buyruqlar:</b>\n" .
                        "├ /start — Bosh menyu\n" .
                        "├ /help — Yordam\n" .
                        "├ /myid — Sizning Telegram ID\n" .
                        "├ /mylink — Sizning havola\n" .
                        "└ /cancel — Anonim yozishni bekor qilish";

        if ($isPriv) {
            $welcomeText .= "\n\n🛡️ <b>Admin buyruqlari:</b>\n" .
                            "├ /grant &lt;user_id&gt; — Huquq berish\n" .
                            "├ /revoke &lt;user_id&gt; — Huquqni olish\n" .
                            "├ /admins — Ishonchli insonlar\n" .
                            "├ /block &lt;user_id&gt; — Bloklash\n" .
                            "├ /unblock &lt;user_id&gt; — Blokdan chiqarish\n" .
                            "└ /stats — Statistika";
        }

        $keyboard = [
            'inline_keyboard' => [
                [
                    [
                        'text' => '🔗 Havolani ulashish',
                        'url' => "https://t.me/share/url?url=" . urlencode($link) . "&text=" . urlencode("Menga anonim xabar yuboring 👻")
                    ]
                ]
            ]
        ];

        Telegram::api('sendMessage', [
            'chat_id' => $userId,
            'text' => $welcomeText,
            'parse_mode' => 'HTML',
            'reply_markup' => $keyboard
        ]);
    }

    /**
     * Yordam buyrug'i.
     */
    private static function helpCommand($message) {
        $userId = $message['from']['id'];
        $isPriv = PrivilegedDB::isPrivileged($userId);

        $text = "📖 <b>Botdan foydalanish yo'riqnomasi</b>\n\n" .
                "1️⃣ /start buyrug'ini bosing va shaxsiy havolangizni oling\n" .
                "2️⃣ Havolani do'stlaringizga yuboring\n" .
                "3️⃣ Ular havola orqali sizga anonim xabar yozadi\n" .
                "4️⃣ Siz xabarga reply qiling — javob yuboruvchiga boradi\n\n" .
                "💡 <b>Muhim:</b>\n" .
                "• Rasm, video, audio, sticker — barchasi qo'llab-quvvatlanadi\n" .
                "• Anonim xabarga reply qiling — javob yuboruvchiga qaytadi\n" .
                "• /cancel — anonim yozish rejimini bekor qiladi";

        if ($isPriv) {
            $text .= "\n\n🛡️ <b>Admin buyruqlari:</b>\n" .
                     "• /grant <user_id> — foydalanuvchiga ko'rish huquqi berish\n" .
                     "• /revoke <user_id> — huquqni olib tashlash\n" .
                     "• /admins — ishonchli insonlar ro'yxati\n" .
                     "• /block <user_id> — foydalanuvchini bloklash\n" .
                     "• /unblock <user_id> — blokdan chiqarish\n" .
                     "• /stats — bot statistikasi\n" .
                     "• /broadcast <xabar> — barchaga xabar yuborish";
        }

        Telegram::api('sendMessage', [
            'chat_id' => $userId,
            'text' => $text,
            'parse_mode' => 'HTML'
        ]);
    }

    /**
     * Telegram ID ni qaytarish.
     */
    private static function myidCommand($message) {
        $userId = $message['from']['id'];
        Telegram::api('sendMessage', [
            'chat_id' => $userId,
            'text' => "🆔 <b>Sizning Telegram ID:</b>\n<code>{$userId}</code>",
            'parse_mode' => 'HTML'
        ]);
    }

    /**
     * Shaxsiy havolani qaytarish.
     */
    private static function mylinkCommand($message) {
        $userId = $message['from']['id'];
        $userData = UserDB::getUser($userId);

        if (!$userData) {
            Telegram::api('sendMessage', [
                'chat_id' => $userId,
                'text' => "❌ Avval /start buyrug'ini bosing."
            ]);
            return;
        }

        $botUsername = self::getBotUsername();
        $link = "https://t.me/{$botUsername}?start={$userData['unique_code']}";

        $keyboard = [
            'inline_keyboard' => [
                [
                    [
                        'text' => '🔗 Havolani ulashish',
                        'url' => "https://t.me/share/url?url=" . urlencode($link) . "&text=" . urlencode("Menga anonim xabar yuboring 👻")
                    ]
                ]
            ]
        ];

        $text = "🔗 <b>Sizning shaxsiy havolangiz:</b>\n\n" .
                "<code>{$link}</code>\n\n" .
                "Bu havolani ulashib, anonim xabarlar qabul qiling! 👻";

        Telegram::api('sendMessage', [
            'chat_id' => $userId,
            'text' => $text,
            'parse_mode' => 'HTML',
            'reply_markup' => $keyboard
        ]);
    }

    /**
     * Anonim rejimni bekor qilish.
     */
    private static function cancelCommand($message) {
        $userId = $message['from']['id'];
        $hasSession = ActiveSessionDB::getSession($userId) !== null;

        if ($hasSession) {
            ActiveSessionDB::deleteSession($userId);
            Telegram::api('sendMessage', [
                'chat_id' => $userId,
                'text' => "✅ Anonim yozish rejimi bekor qilindi.\n🏠 Bosh menyu uchun: /start"
            ]);
        } else {
            Telegram::api('sendMessage', [
                'chat_id' => $userId,
                'text' => "ℹ️ Siz hozir hech kimga anonim yozmayapsiz.\n🏠 Bosh menyu uchun: /start"
            ]);
        }
    }

    /**
     * Bot foydalanuvchi nomini olish.
     */
    private static function getBotUsername() {
        static $username = null;
        if ($username === null) {
            $botInfo = Telegram::api('getMe');
            if ($botInfo && isset($botInfo['result']['username'])) {
                $username = $botInfo['result']['username'];
            } else {
                $username = 'bot';
            }
        }
        return $username;
    }
}
