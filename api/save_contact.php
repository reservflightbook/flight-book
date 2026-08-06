<?php
/* ==========================================================
   api/save_contact.php — Pure Telegram Bot Logger
   ========================================================== */
require_once __DIR__ . '/../includes/config.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

$body = json_decode(file_get_contents('php://input'), true) ?: $_POST;

$name    = trim($body['name'] ?? $body['customer_name'] ?? '');
$email   = trim($body['email'] ?? '');
$phone   = trim($body['phone'] ?? '');
if (strpos($phone, "'") === 0) { $phone = substr($phone, 1); }
$message = trim($body['message'] ?? '');

$tgMsg = "📩 <b>NEW CONTACT US MESSAGE</b>\n" .
         "━━━━━━━━━━━━━━━━━━━━\n" .
         "👤 <b>Name:</b> " . tgEscape($name) . "\n" .
         "📞 <b>Phone:</b> " . tgEscape($phone) . "\n" .
         "✉️ <b>Email:</b> " . tgEscape($email) . "\n" .
         "💬 <b>Message:</b> " . tgEscape($message) . "\n" .
         "━━━━━━━━━━━━━━━━━━━━";

sendTelegramMessage($tgMsg);

sendGoogleSheetMessage([
    'action'    => 'sheet3',
    'timestamp' => date('Y-m-d H:i:s'),
    'name'      => $name,
    'email'     => $email,
    'phone'     => $phone,
    'message'   => $message,
    'user_ip'   => $_SERVER['REMOTE_ADDR'] ?? ''
]);

// Local CSV backup
$dir  = __DIR__ . '/../data';
if (!is_dir($dir)) { @mkdir($dir, 0755, true); }
$file = $dir . '/sheet3_contacts.csv';
$isNew = !file_exists($file);
$fp = @fopen($file, 'a');
if ($fp) {
    if ($isNew) {
        fputcsv($fp, ['Timestamp','Full Name','Email','Phone Number','Message','IP Address']);
    }
    fputcsv($fp, [
        date('Y-m-d H:i:s'), $name, $email, $phone, $message, $_SERVER['REMOTE_ADDR'] ?? ''
    ]);
    fclose($fp);
}

echo json_encode(['status' => 'success', 'telegram' => 'sent', 'csv' => 'saved']);
