<?php
// database/Db.php

require_once __DIR__ . '/../config.php';

class Db {
    private static $connection = null;

    /**
     * Ma'lumotlar bazasini ishga tushirish va jadvallarni yaratish.
     */
    public static function initDb() {
        $dbPath = DB_PATH;
        $dbDir = dirname($dbPath);
        if (!is_dir($dbDir)) {
            mkdir($dbDir, 0777, true);
        }

        $db = self::getDb();

        $query = "
            CREATE TABLE IF NOT EXISTS users (
                user_id INTEGER PRIMARY KEY,
                username TEXT,
                first_name TEXT,
                last_name TEXT,
                unique_code TEXT UNIQUE NOT NULL,
                is_blocked INTEGER DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS privileged_users (
                user_id INTEGER PRIMARY KEY,
                role TEXT DEFAULT 'trusted' CHECK(role IN ('admin', 'trusted')),
                granted_by INTEGER,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS messages (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                sender_id INTEGER NOT NULL,
                receiver_id INTEGER NOT NULL,
                bot_message_id INTEGER,
                message_type TEXT DEFAULT 'text',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (sender_id) REFERENCES users(user_id),
                FOREIGN KEY (receiver_id) REFERENCES users(user_id)
            );

            CREATE TABLE IF NOT EXISTS active_sessions (
                user_id INTEGER PRIMARY KEY,
                receiver_id INTEGER NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(user_id),
                FOREIGN KEY (receiver_id) REFERENCES users(user_id)
            );

            CREATE INDEX IF NOT EXISTS idx_messages_receiver_bot_msg 
                ON messages(receiver_id, bot_message_id);
            CREATE INDEX IF NOT EXISTS idx_users_code 
                ON users(unique_code);
        ";

        $db->exec($query);
    }

    /**
     * Database ulanishni qaytarish.
     * @return SQLite3
     */
    public static function getDb() {
        if (self::$connection === null) {
            self::$connection = new SQLite3(DB_PATH);
            self::$connection->busyTimeout(5000); // 5 soniya kutish vaqti
        }
        return self::$connection;
    }
}
