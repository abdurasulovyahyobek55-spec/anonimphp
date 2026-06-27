<?php
// config.php

/**
 * .env faylidan muhit o'zgaruvchilarini yuklash.
 */
function loadEnv($path) {
    if (!file_exists($path)) {
        return;
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        // Izoh yoki noto'g'ri qatorlarni tashlab o'tish
        if (strpos($line, '#') === 0 || strpos($line, '=') === false) {
            continue;
        }
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        
        // Qo'shtirnoq yoki tirnoqlarni olib tashlash
        if (preg_match('/^([\'"])(.*)\1$/', $value, $matches)) {
            $value = $matches[2];
        }
        
        if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

loadEnv(__DIR__ . '/.env');

define('BOT_TOKEN', getenv('BOT_TOKEN') ?: '');
define('ADMIN_ID', (int)(getenv('ADMIN_ID') ?: 0));
define('DB_PATH', __DIR__ . '/data/anonim.db');
