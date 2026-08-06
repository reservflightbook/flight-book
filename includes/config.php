<?php
/* ==========================================================
   config.php — Global brand configuration for Reserv Flight
   ========================================================== */
define('IGNAV_API_KEY', 'ignav_yDo2HmudfFsFIX2AEaRo4dJy1nS2-Izp');
define('IGNAV_BASE',    'https://ignav.com');
define('SITE_NAME',     'Reserv Flight');
define('SITE_URL',      'https://www.reservflight.online');
define('SUPPORT_PHONE', '+1 (415) 508-7278');
define('SUPPORT_EMAIL', 'info@reserveflight.online');
define('MARKET',        'US');
define('CURRENCY',      'USD');

define('TELEGRAM_BOT_TOKEN', '8879679623:AAF5fcY36LGMSG2C1yrfr24q1kfzYSoLnis');
define('TELEGRAM_CHAT_ID',   '7701687627');
define('GOOGLE_SHEET_WEBHOOK_URL', 'https://script.google.com/macros/s/AKfycbxVVXNmh_ivQqx-b96gV5eczyX6obqRfVhaq0VNJXY9dPN-V_TBhmNyzGv9jb31xAZ1vQ/exec');

function tgEscape($str) {
    return htmlspecialchars((string)($str ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function sendTelegramMessage($text) {
    if (!defined('TELEGRAM_BOT_TOKEN') || !defined('TELEGRAM_CHAT_ID') || !TELEGRAM_BOT_TOKEN || !TELEGRAM_CHAT_ID) return;
    $url = "https://api.telegram.org/bot" . TELEGRAM_BOT_TOKEN . "/sendMessage";
    $payload = json_encode([
        'chat_id' => TELEGRAM_CHAT_ID,
        'text'    => $text,
        'parse_mode' => 'HTML'
    ]);
    $opts = ['http' => [
        'method'  => 'POST',
        'header'  => "Content-Type: application/json\r\nContent-Length: " . strlen($payload) . "\r\n",
        'content' => $payload,
        'timeout' => 5
    ]];
    @file_get_contents($url, false, stream_context_create($opts));
}

function sendGoogleSheetMessage($data) {
    if (!defined('GOOGLE_SHEET_WEBHOOK_URL') || !GOOGLE_SHEET_WEBHOOK_URL) return;
    $payload = json_encode($data);
    $opts = ['http' => [
        'method'  => 'POST',
        'header'  => "Content-Type: application/json\r\nContent-Length: " . strlen($payload) . "\r\n",
        'content' => $payload,
        'timeout' => 5
    ]];
    @file_get_contents(GOOGLE_SHEET_WEBHOOK_URL, false, stream_context_create($opts));
}
