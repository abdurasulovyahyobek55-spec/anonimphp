<?php
// handlers/MessageHandler.php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../database/Models.php';
require_once __DIR__ . '/../utils/Helpers.php';

class MessageHandler {
    /**
     * Anonim xabarni qabul qiluvchiga yuborish.
     * Qaytaradi: bot_message_id (reply tracking uchun)
     */
    public static function sendAnonymousMessage($message, $receiver_id, $sender_id) {
        $isReceiverPrivileged = PrivilegedDB::isPrivileged($receiver_id);
        $senderData = UserDB::getUser($sender_id);

        $senderInfo = "";
        if ($isReceiverPrivileged && $senderData) {
            $senderInfo = formatSenderInfo($senderData);
        }

        $msgType = getMessageType($message);
        $botMsg = null;

        try {
            switch ($msgType) {
                case 'text':
                    $text = "📩 <b>Anonim xabar:</b>\n\n" . $message['text'];
                    if ($senderInfo) {
                        $text .= "\n\n" . $senderInfo;
                    }
                    $botMsg = Telegram::api('sendMessage', [
                        'chat_id' => $receiver_id,
                        'text' => $text,
                        'parse_mode' => 'HTML'
                    ]);
                    break;

                case 'photo':
                    $photo = end($message['photo']);
                    $caption = "📩 <b>Anonim xabar:</b>";
                    if (!empty($message['caption'])) {
                        $caption .= "\n\n" . $message['caption'];
                    }
                    if ($senderInfo) {
                        $caption .= "\n\n" . $senderInfo;
                    }
                    $botMsg = Telegram::api('sendPhoto', [
                        'chat_id' => $receiver_id,
                        'photo' => $photo['file_id'],
                        'caption' => $caption,
                        'parse_mode' => 'HTML'
                    ]);
                    break;

                case 'video':
                    $caption = "📩 <b>Anonim xabar:</b>";
                    if (!empty($message['caption'])) {
                        $caption .= "\n\n" . $message['caption'];
                    }
                    if ($senderInfo) {
                        $caption .= "\n\n" . $senderInfo;
                    }
                    $botMsg = Telegram::api('sendVideo', [
                        'chat_id' => $receiver_id,
                        'video' => $message['video']['file_id'],
                        'caption' => $caption,
                        'parse_mode' => 'HTML'
                    ]);
                    break;

                case 'voice':
                    $caption = "📩 <b>Anonim ovozli xabar</b>";
                    if ($senderInfo) {
                        $caption .= "\n\n" . $senderInfo;
                    }
                    $botMsg = Telegram::api('sendVoice', [
                        'chat_id' => $receiver_id,
                        'voice' => $message['voice']['file_id'],
                        'caption' => $caption,
                        'parse_mode' => 'HTML'
                    ]);
                    break;

                case 'audio':
                    $caption = "📩 <b>Anonim audio</b>";
                    if (!empty($message['caption'])) {
                        $caption .= "\n\n" . $message['caption'];
                    }
                    if ($senderInfo) {
                        $caption .= "\n\n" . $senderInfo;
                    }
                    $botMsg = Telegram::api('sendAudio', [
                        'chat_id' => $receiver_id,
                        'audio' => $message['audio']['file_id'],
                        'caption' => $caption,
                        'parse_mode' => 'HTML'
                    ]);
                    break;

                case 'document':
                    $caption = "📩 <b>Anonim fayl</b>";
                    if (!empty($message['caption'])) {
                        $caption .= "\n\n" . $message['caption'];
                    }
                    if ($senderInfo) {
                        $caption .= "\n\n" . $senderInfo;
                    }
                    $botMsg = Telegram::api('sendDocument', [
                        'chat_id' => $receiver_id,
                        'document' => $message['document']['file_id'],
                        'caption' => $caption,
                        'parse_mode' => 'HTML'
                    ]);
                    break;

                case 'sticker':
                    $botMsg = Telegram::api('sendSticker', [
                        'chat_id' => $receiver_id,
                        'sticker' => $message['sticker']['file_id']
                    ]);
                    
                    $infoText = "📩 <b>Anonim sticker yuborildi</b>";
                    if ($senderInfo) {
                        $infoText .= "\n\n" . $senderInfo;
                    }
                    if ($botMsg && isset($botMsg['result']['message_id'])) {
                        Telegram::api('sendMessage', [
                            'chat_id' => $receiver_id,
                            'text' => $infoText,
                            'parse_mode' => 'HTML',
                            'reply_to_message_id' => $botMsg['result']['message_id']
                        ]);
                    }
                    break;

                case 'video_note':
                    $botMsg = Telegram::api('sendVideoNote', [
                        'chat_id' => $receiver_id,
                        'video_note' => $message['video_note']['file_id']
                    ]);
                    
                    if ($senderInfo && $botMsg && isset($botMsg['result']['message_id'])) {
                        Telegram::api('sendMessage', [
                            'chat_id' => $receiver_id,
                            'text' => "📩 <b>Anonim video xabar</b>\n\n" . $senderInfo,
                            'parse_mode' => 'HTML',
                            'reply_to_message_id' => $botMsg['result']['message_id']
                        ]);
                    }
                    break;

                case 'animation':
                    $caption = "📩 <b>Anonim GIF</b>";
                    if (!empty($message['caption'])) {
                        $caption .= "\n\n" . $message['caption'];
                    }
                    if ($senderInfo) {
                        $caption .= "\n\n" . $senderInfo;
                    }
                    $botMsg = Telegram::api('sendAnimation', [
                        'chat_id' => $receiver_id,
                        'animation' => $message['animation']['file_id'],
                        'caption' => $caption,
                        'parse_mode' => 'HTML'
                    ]);
                    break;

                case 'contact':
                    $botMsg = Telegram::api('sendContact', [
                        'chat_id' => $receiver_id,
                        'phone_number' => $message['contact']['phone_number'],
                        'first_name' => "Anonim"
                    ]);
                    break;

                case 'location':
                    $botMsg = Telegram::api('sendLocation', [
                        'chat_id' => $receiver_id,
                        'latitude' => $message['location']['latitude'],
                        'longitude' => $message['location']['longitude']
                    ]);
                    break;

                default:
                    $text = "📩 <b>Anonim xabar:</b>\n\n" . ($message['text'] ?? "(bo'sh xabar)");
                    if ($senderInfo) {
                        $text .= "\n\n" . $senderInfo;
                    }
                    $botMsg = Telegram::api('sendMessage', [
                        'chat_id' => $receiver_id,
                        'text' => $text,
                        'parse_mode' => 'HTML'
                    ]);
                    break;
            }

            return $botMsg && isset($botMsg['result']['message_id']) ? $botMsg['result']['message_id'] : null;

        } catch (Exception $e) {
            error_log("Xabar yuborishda xatolik (receiver={$receiver_id}): " . $e->getMessage());
            return null;
        }
    }

    /**
     * Anonim xabarni qayta ishlash.
     * Faqat foydalanuvchi aktiv sessiyada bo'lganda ishlaydi.
     */
    public static function handle($message) {
        $senderId = $message['from']['id'];

        // Aktiv sessiyani tekshirish
        $receiverId = ActiveSessionDB::getSession($senderId);

        if ($receiverId === null) {
            Telegram::api('sendMessage', [
                'chat_id' => $senderId,
                'text' => "🤔 Anonim xabar yuborish uchun biror kishining havolasini bosing.\n\n🏠 Bosh menyu: /start\n🔗 Havolangiz: /mylink"
            ]);
            return;
        }

        // Bloklanganligini tekshirish
        if (UserDB::isBlocked($senderId)) {
            Telegram::api('sendMessage', [
                'chat_id' => $senderId,
                'text' => "🚫 Siz bloklangansiz va xabar yubora olmaysiz."
            ]);
            ActiveSessionDB::deleteSession($senderId);
            return;
        }

        // Xabarni anonim yuborish
        $botMessageId = self::sendAnonymousMessage($message, $receiverId, $senderId);

        if ($botMessageId) {
            // Xabarni bazaga saqlash (reply tracking uchun)
            $msgType = getMessageType($message);
            MessageDB::saveMessage($senderId, $receiverId, $botMessageId, $msgType);

            Telegram::api('sendMessage', [
                'chat_id' => $senderId,
                'text' => "✅ Xabaringiz anonim yuborildi! 👻\n\n📝 Yana xabar yozishingiz mumkin.\n🔙 Bekor qilish: /cancel\n🏠 Bosh menyu: /start"
            ]);
        } else {
            Telegram::api('sendMessage', [
                'chat_id' => $senderId,
                'text' => "❌ Xabarni yuborib bo'lmadi.\nFoydalanuvchi botni bloklagan bo'lishi mumkin."
            ]);
        }
    }
}
