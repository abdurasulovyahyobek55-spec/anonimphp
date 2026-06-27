<?php
// database/Models.php

require_once __DIR__ . '/Db.php';
require_once __DIR__ . '/../config.php';

class UserDB {
    /**
     * Foydalanuvchini saqlash yoki yangilash.
     */
    public static function saveUser($user_id, $username = null, $first_name = null, $last_name = null, $unique_code = null) {
        $db = Db::getDb();
        
        // Foydalanuvchi mavjudligini tekshirish
        $stmt = $db->prepare("SELECT user_id FROM users WHERE user_id = :user_id");
        $stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
        $result = $stmt->execute();
        $row = $result->fetchArray(SQLITE3_ASSOC);
        
        if ($row) {
            $stmt = $db->prepare("UPDATE users SET username = :username, first_name = :first_name, last_name = :last_name, updated_at = CURRENT_TIMESTAMP WHERE user_id = :user_id");
            $stmt->bindValue(':username', $username, SQLITE3_TEXT);
            $stmt->bindValue(':first_name', $first_name, SQLITE3_TEXT);
            $stmt->bindValue(':last_name', $last_name, SQLITE3_TEXT);
            $stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
            $stmt->execute();
        } else {
            $stmt = $db->prepare("INSERT INTO users (user_id, username, first_name, last_name, unique_code) VALUES (:user_id, :username, :first_name, :last_name, :unique_code)");
            $stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
            $stmt->bindValue(':username', $username, SQLITE3_TEXT);
            $stmt->bindValue(':first_name', $first_name, SQLITE3_TEXT);
            $stmt->bindValue(':last_name', $last_name, SQLITE3_TEXT);
            $stmt->bindValue(':unique_code', $unique_code, SQLITE3_TEXT);
            $stmt->execute();
        }
    }

    /**
     * Foydalanuvchi ma'lumotlarini olish.
     */
    public static function getUser($user_id) {
        $db = Db::getDb();
        $stmt = $db->prepare("SELECT * FROM users WHERE user_id = :user_id");
        $stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
        $result = $stmt->execute();
        $row = $result->fetchArray(SQLITE3_ASSOC);
        return $row ?: null;
    }

    /**
     * Unique code bo'yicha foydalanuvchini topish.
     */
    public static function getUserByCode($code) {
        $db = Db::getDb();
        $stmt = $db->prepare("SELECT * FROM users WHERE unique_code = :code");
        $stmt->bindValue(':code', $code, SQLITE3_TEXT);
        $result = $stmt->execute();
        $row = $result->fetchArray(SQLITE3_ASSOC);
        return $row ?: null;
    }

    /**
     * Username bo'yicha foydalanuvchini topish.
     */
    public static function getUserByUsername($username) {
        $db = Db::getDb();
        $cleanedUsername = ltrim($username, '@');
        $stmt = $db->prepare("SELECT * FROM users WHERE username = :username");
        $stmt->bindValue(':username', $cleanedUsername, SQLITE3_TEXT);
        $result = $stmt->execute();
        $row = $result->fetchArray(SQLITE3_ASSOC);
        return $row ?: null;
    }

    /**
     * Foydalanuvchi bloklangan yoki yo'qligini tekshirish.
     */
    public static function isBlocked($user_id) {
        $db = Db::getDb();
        $stmt = $db->prepare("SELECT is_blocked FROM users WHERE user_id = :user_id");
        $stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
        $result = $stmt->execute();
        $row = $result->fetchArray(SQLITE3_ASSOC);
        return (bool)($row && $row['is_blocked']);
    }

    /**
     * Foydalanuvchini bloklash.
     */
    public static function blockUser($user_id) {
        $db = Db::getDb();
        $stmt = $db->prepare("UPDATE users SET is_blocked = 1 WHERE user_id = :user_id");
        $stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
        $stmt->execute();
        return $db->changes() > 0;
    }

    /**
     * Foydalanuvchini blokdan chiqarish.
     */
    public static function unblockUser($user_id) {
        $db = Db::getDb();
        $stmt = $db->prepare("UPDATE users SET is_blocked = 0 WHERE user_id = :user_id");
        $stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
        $stmt->execute();
        return $db->changes() > 0;
    }

    /**
     * Barcha foydalanuvchilar ro'yxatini olish.
     */
    public static function getAllUsers() {
        $db = Db::getDb();
        $result = $db->query("SELECT * FROM users");
        $users = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $users[] = $row;
        }
        return $users;
    }

    /**
     * Foydalanuvchilar sonini olish.
     */
    public static function getUserCount() {
        $db = Db::getDb();
        return (int)$db->querySingle("SELECT COUNT(*) FROM users");
    }
}

class PrivilegedDB {
    /**
     * Foydalanuvchi admin yoki ishonchli ekanligini tekshirish.
     */
    public static function isPrivileged($user_id) {
        if ($user_id === ADMIN_ID) {
            return true;
        }
        $db = Db::getDb();
        $stmt = $db->prepare("SELECT user_id FROM privileged_users WHERE user_id = :user_id");
        $stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
        $result = $stmt->execute();
        return $result->fetchArray(SQLITE3_ASSOC) !== false;
    }

    /**
     * Ishonchli inson qo'shish.
     */
    public static function addPrivileged($user_id, $granted_by, $role = "trusted") {
        $db = Db::getDb();
        try {
            $stmt = $db->prepare("INSERT OR REPLACE INTO privileged_users (user_id, role, granted_by) VALUES (:user_id, :role, :granted_by)");
            $stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
            $stmt->bindValue(':role', $role, SQLITE3_TEXT);
            $stmt->bindValue(':granted_by', $granted_by, SQLITE3_INTEGER);
            $stmt->execute();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Ishonchli insonni olib tashlash.
     */
    public static function removePrivileged($user_id) {
        $db = Db::getDb();
        $stmt = $db->prepare("DELETE FROM privileged_users WHERE user_id = :user_id");
        $stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
        $stmt->execute();
        return $db->changes() > 0;
    }

    /**
     * Barcha ishonchli insonlar ro'yxatini olish.
     */
    public static function getAllPrivileged() {
        $db = Db::getDb();
        $result = $db->query("
            SELECT p.*, u.username, u.first_name, u.last_name 
            FROM privileged_users p
            LEFT JOIN users u ON p.user_id = u.user_id
        ");
        $privileged = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $privileged[] = $row;
        }
        return $privileged;
    }
}

class MessageDB {
    /**
     * Xabarni saqlash va ID qaytarish.
     */
    public static function saveMessage($sender_id, $receiver_id, $bot_message_id, $message_type = "text") {
        $db = Db::getDb();
        $stmt = $db->prepare("INSERT INTO messages (sender_id, receiver_id, bot_message_id, message_type) VALUES (:sender_id, :receiver_id, :bot_message_id, :message_type)");
        $stmt->bindValue(':sender_id', $sender_id, SQLITE3_INTEGER);
        $stmt->bindValue(':receiver_id', $receiver_id, SQLITE3_INTEGER);
        $stmt->bindValue(':bot_message_id', $bot_message_id, SQLITE3_INTEGER);
        $stmt->bindValue(':message_type', $message_type, SQLITE3_TEXT);
        $stmt->execute();
        return $db->lastInsertRowID();
    }

    /**
     * Bot xabari ID si bo'yicha yuboruvchini topish (reply uchun).
     */
    public static function getSenderByBotMessage($receiver_id, $bot_message_id) {
        $db = Db::getDb();
        $stmt = $db->prepare("SELECT sender_id FROM messages WHERE receiver_id = :receiver_id AND bot_message_id = :bot_message_id ORDER BY created_at DESC LIMIT 1");
        $stmt->bindValue(':receiver_id', $receiver_id, SQLITE3_INTEGER);
        $stmt->bindValue(':bot_message_id', $bot_message_id, SQLITE3_INTEGER);
        $result = $stmt->execute();
        $row = $result->fetchArray(SQLITE3_ASSOC);
        return $row ? (int)$row['sender_id'] : null;
    }

    /**
     * Jami xabarlar sonini olish.
     */
    public static function getMessageCount() {
        $db = Db::getDb();
        return (int)$db->querySingle("SELECT COUNT(*) FROM messages");
    }

    /**
     * Bugungi xabarlar sonini olish.
     */
    public static function getTodayMessageCount() {
        $db = Db::getDb();
        return (int)$db->querySingle("SELECT COUNT(*) FROM messages WHERE DATE(created_at) = DATE('now')");
    }
}

class ActiveSessionDB {
    /**
     * Foydalanuvchining aktiv sessiyasini o'rnatish.
     */
    public static function setSession($user_id, $receiver_id) {
        $db = Db::getDb();
        $stmt = $db->prepare("INSERT OR REPLACE INTO active_sessions (user_id, receiver_id) VALUES (:user_id, :receiver_id)");
        $stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
        $stmt->bindValue(':receiver_id', $receiver_id, SQLITE3_INTEGER);
        return $stmt->execute() !== false;
    }

    /**
     * Foydalanuvchining aktiv sessiyasini olish (kimga anonim xabar yozayotganini).
     */
    public static function getSession($user_id) {
        $db = Db::getDb();
        $stmt = $db->prepare("SELECT receiver_id FROM active_sessions WHERE user_id = :user_id");
        $stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
        $result = $stmt->execute();
        $row = $result->fetchArray(SQLITE3_ASSOC);
        return $row ? (int)$row['receiver_id'] : null;
    }

    /**
     * Foydalanuvchining aktiv sessiyasini bekor qilish.
     */
    public static function deleteSession($user_id) {
        $db = Db::getDb();
        $stmt = $db->prepare("DELETE FROM active_sessions WHERE user_id = :user_id");
        $stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
        $stmt->execute();
        return $db->changes() > 0;
    }
}
