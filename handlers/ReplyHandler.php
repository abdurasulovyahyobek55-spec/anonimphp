<?php
// handlers/ReplyHandler.php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../database/Models.php';
require_once __DIR__ . '/../utils/Helpers.php';

class ReplyHandler {
    /**
     * Anonim xabarga javob berish.
     * Foydalanuvchi bot xabariga reply qilganda — javob asl yuboruvchiga qaytadi.
     */
    public static function handle($message) {
        $userId = $message['from']['id'];
        $repliedMsgId = $message['reply_to_message']['message_id'];

        // Asl yuboruvchini topish (bizning bazadan)
        $senderId = MessageDB::getSenderByBotMessage($userId, $repliedMsgId);

        if ($senderId === null) {
            // Bu bot xabariga reply emas yoki ma'lumot topilmadi
            return false;
        }

        // Javob yuboruvchining ma'lumotini tekshirish (agar qabul qiluvchi admin/ishonchli bo'lsa)
        $isSenderPrivileged = PrivilegedDB::isPrivileged($senderId);
        $replierData = UserDB::getUser($userId);

        $replierInfo = "";
        if ($isSenderPrivileged && $replierData) {
            $replierInfo = formatSenderInfo($replierData);
        }

        $msgType = getMessageType($message);
        $botMsg = null;

        try {
            switch ($msgType) {
                case 'text':
                    $text = "💬 <b>Anonim xabaringizga javob:</b>\n\n" . $message['text'];
                    if ($replierInfo) {
                        $text .= "\n\n" . $replierInfo;
                    }
                    $botMsg = Telegram::api('sendMessage', [
                        'chat_id' => $senderId,
                        'text' => $text,
                        'parse_mode' => 'HTML'
                    ]);
                    break;

                case 'photo':
                    $photo = end($message['photo']);
                    $caption = "💬 <b>Anonim xabaringizga javob:</b>";
                    if (!empty($message['caption'])) {
                        $caption .= "\n\n" . $message['caption'];
                    }
                    if ($replierInfo) {
                        $caption .= "\n\n" . $replierInfo;
                    }
                    $botMsg = Telegram::api('sendPhoto', [
                        'chat_id' => $senderId,
                        'photo' => $photo['file_id'],
                        'caption' => $caption,
                        'parse_mode' => 'HTML'
                    ]);
                    break;

                case 'video':
                    $caption = "💬 <b>Anonim xabaringizga javob:</b>";
                    if (!empty($message['caption'])) {
                        $caption .= "\n\n" . $message['caption'];
                    }
                    if ($replierInfo) {
                        $caption .= "\n\n" . $replierInfo;
                    }
                    $botMsg = Telegram::api('sendVideo', [
                        'chat_id' => $senderId,
                        'video' => $message['video']['file_id'],
                        'caption' => $caption,
                        'parse_mode' => 'HTML'
                    ]);
                    break;

                case 'voice':
                    $caption = "💬 <b>Anonim xabaringizga javob (ovozli)</b>";
                    if ($replierInfo) {
                        $caption .= "\n\n" . $replierInfo;
                    }
                    $botMsg = Telegram::api('sendVoice', [
                        'chat_id' => $senderId,
                        'voice' => $message['voice']['file_id'],
                        'caption' => $caption,
                        'parse_mode' => 'HTML'
                    ]);
                    break;

                case 'audio':
                    $caption = "💬 <b>Anonim xabaringizga javob (audio)</b>";
                    if (!empty($message['caption'])) {
                        $caption .= "\n\n" . $message['caption'];
                    }
                    if ($replierInfo) {
                        $caption .= "\n\n" . $replierInfo;
                    }
                    $botMsg = Telegram::api('sendAudio', [
                        'chat_id' => $senderId,
                        'audio' => $message['audio']['file_id'],
                        'caption' => $caption,
                        'parse_mode' => 'HTML'
                    ]);
                    break;

                case 'document':
                    $caption = "💬 <b>Anonim xabaringizga javob (fayl)</b>";
                    if (!empty($message['caption'])) {
                        $caption .= "\n\n" . $message['caption'];
                    }
                    if ($replierInfo) {
                        $caption .= "\n\n" . $replierInfo;
                    }
                    $botMsg = Telegram::api('sendDocument', [
                        'chat_id' => $senderId,
                        'document' => $message['document']['file_id'],
                        'caption' => $caption,
                        'parse_mode' => 'HTML'
                    ]);
                    break;

                case 'sticker':
                    $botMsg = Telegram::api('sendSticker', [
                        'chat_id' => $senderId,
                        'sticker' => $message['sticker']['file_id']
                    ]);
                    
                    $infoText = "💬 <b>Anonim xabaringizga sticker bilan javob berildi</b>";
                    if ($replierInfo) {
                        $infoText .= "\n\n" . $replierInfo;
                    }
                    if ($botMsg && isset($botMsg['result']['message_id'])) {
                        Telegram::api('sendMessage', [
                            'chat_id' => $senderId,
                            'text' => $infoText,
                            'parse_mode' => 'HTML',
                            'reply_to_message_id' => $botMsg['result']['message_id']
                        ]);
                    }
                    break;

                case 'video_note':
                    $botMsg = Telegram::api('sendVideoNote', [
                        'chat_id' => $senderId,
                        'video_note' => $message['video_note']['file_id']
                    ]);
                    
                    if ($replierInfo && $botMsg && isset($botMsg['result']['message_id'])) {
                        Telegram::api('sendMessage', [
                            'chat_id' => $senderId,
                            'text' => "💬 <b>Video xabarga javob</b>\n\n" . $replierInfo,
                            'parse_mode' => 'HTML',
                            'reply_to_message_id' => $botMsg['result']['message_id']
                        ]);
                    }
                    break;

                case 'animation':
                    $caption = "💬 <b>Anonim xabaringizga javob (GIF)</b>";
                    if (!empty($message['caption'])) {
                        $caption .= "\n\n" . $message['caption'];
                    }
                    if ($replierInfo) {
                        $caption .= "\n\n" . $replierInfo;
                    }
                    $botMsg = Telegram::api('sendAnimation', [
                        'chat_id' => $senderId,
                        'animation' => $message['animation']['file_id'],
                        'caption' => $caption,
                        'parse_mode' => 'HTML'
                    ]);
                    break;

                default:
                    $text = "💬 <b>Anonim xabaringizga javob:</b>\n\n" . ($message['text'] ?? "(bo'sh)");
                    if ($replierInfo) {
                        $text .= "\n\n" . $replierInfo;
                    }
                    $botMsg = Telegram::api('sendMessage', [
                        'chat_id' => $senderId,
                        'text' => $text,
                        'parse_mode' => 'HTML'
                    ]);
                    break;
            }

            if ($botMsg && isset($botMsg['result']['message_id'])) {
                // Javobni ham bazaga saqlash (asl yuboruvchi unga yana reply qaytara olishi uchun)
                MessageDB::saveMessage($userId, $senderId, $botMsg['result']['message_id'], $msgType);

                Telegram::api('sendMessage', [
                    'chat_id' => $userId,
                    'text' => "✅ Javobingiz anonim yuborildi! 👻"
                ]);
            } else {
                Telegram::api('sendMessage', [
                    'chat_id' => $userId,
                    'text' => "❌ Javobni yuborib bo'lmadi."
                ]);
            }

        } catch (Exception $e) {
            error_log("Javob yuborishda xatolik: " . $e->getMessage());
            Telegram::api('sendMessage', [
                'chat_id' => $userId,
                'text' => "❌ Javobni yuborib bo'lmadi.\nFoydalanuvchi botni bloklagan bo'lishi mumkin."
            ]);
        }
        return true;
    }
}
