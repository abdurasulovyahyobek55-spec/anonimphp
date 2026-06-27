<?php
// utils/Helpers.php

require_once __DIR__ . '/../config.php';

/**
 * Tasodifiy unikal kod yaratish (havola uchun).
 */
function generateUniqueCode($length = 8) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $code = '';
    $max = strlen($chars) - 1;
    for ($i = 0; $i < $length; $i++) {
        try {
            $code .= $chars[random_int(0, $max)];
        } catch (Exception $e) {
            $code .= $chars[rand(0, $max)];
        }
    }
    return $code;
}

/**
 * Yuboruvchi haqida ma'lumot formatlash (admin/ishonchli uchun).
 */
function formatSenderInfo($user_data) {
    $name_parts = [];
    if (!empty($user_data['first_name'])) {
        $name_parts[] = $user_data['first_name'];
    }
    if (!empty($user_data['last_name'])) {
        $name_parts[] = $user_data['last_name'];
    }
    $full_name = implode(' ', $name_parts) ?: 'Nomaʼlum';

    $username = $user_data['username'] ?? null;
    $username_str = $username ? '@' . $username : "yo'q";
    $user_id = $user_data['user_id'] ?? 'nomaʼlum';

    if ($username) {
        $profile_link = "<a href='https://t.me/{$username}'>🔗 Profilni ochish</a>";
    } else {
        $profile_link = "<a href='tg://user?id={$user_id}'>🔗 Profilni ochish</a>";
    }

    return (
        "\n╔══════════════════════╗\n" .
        "║  🔍 YUBORUVCHI MA'LUMOTI\n" .
        "╠══════════════════════╣\n" .
        "║  👤 Ism: {$full_name}\n" .
        "║  📎 Username: {$username_str}\n" .
        "║  🆔 ID: <code>{$user_id}</code>\n" .
        "║  🔗 Profil: {$profile_link}\n" .
        "╚══════════════════════╝"
    );
}

/**
 * Xabar turini aniqlash.
 */
function getMessageType($message) {
    if (isset($message['photo'])) return 'photo';
    if (isset($message['video'])) return 'video';
    if (isset($message['voice'])) return 'voice';
    if (isset($message['audio'])) return 'audio';
    if (isset($message['document'])) return 'document';
    if (isset($message['sticker'])) return 'sticker';
    if (isset($message['video_note'])) return 'video_note';
    if (isset($message['animation'])) return 'animation';
    if (isset($message['contact'])) return 'contact';
    if (isset($message['location'])) return 'location';
    return 'text';
}

/**
 * Telegram API so'rovlarini amalga oshiruvchi klass.
 */
class Telegram {
    /**
     * API metodini chaqirish.
     */
    public static function api($method, $params = []) {
        $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/" . $method;

        // Massivlarni JSON formatiga o'tkazish (masalan: reply_markup)
        foreach ($params as $key => $value) {
            if (is_array($value)) {
                $params[$key] = json_encode($value);
            }
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            $error_msg = curl_error($ch);
            error_log("cURL xatolik Telegram::api($method) da: " . $error_msg);
            curl_close($ch);
            return null;
        }
        curl_close($ch);

        $decoded = json_decode($response, true);
        if (!$decoded || !isset($decoded['ok']) || !$decoded['ok']) {
            error_log("Telegram API xatosi Telegram::api($method) da: " . $response);
        }
        return $decoded;
    }
}
