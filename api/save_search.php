<?php
/* ==========================================================
   api/save_search.php — Pure Telegram Bot Logger
   ========================================================== */
require_once __DIR__ . '/../includes/config.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

$body = json_decode(file_get_contents('php://input'), true) ?: $_POST;

$name  = trim($body['customer_name'] ?? $body['name'] ?? '');
$phone = trim($body['customer_phone'] ?? $body['phone'] ?? '');
if (strpos($phone, "'") === 0) { $phone = substr($phone, 1); }

$origin      = strtoupper(trim($body['origin'] ?? $body['from'] ?? ''));
$destination = strtoupper(trim($body['destination'] ?? $body['to'] ?? ''));
$dep_date    = trim($body['departure_date'] ?? $body['dep_date'] ?? $body['dep'] ?? '');
$return_date = trim($body['return_date'] ?? $body['ret'] ?? '');
$trip_type   = trim($body['trip_type'] ?? $body['trip'] ?? 'one-way');
$adults      = trim($body['adults'] ?? $body['passengers'] ?? 1);
$cabin       = trim($body['cabin_class'] ?? $body['cabin'] ?? 'economy');

$tgMsg = "✈️ <b>NEW FLIGHT SEARCH LEAD</b>\n" .
         "━━━━━━━━━━━━━━━━━━━━\n" .
         "👤 <b>Name:</b> " . tgEscape($name ?: 'N/A') . "\n" .
         "📞 <b>Phone:</b> " . tgEscape($phone ?: 'N/A') . "\n" .
         "🛫 <b>Route:</b> " . tgEscape($origin) . " ➔ " . tgEscape($destination) . "\n" .
         "📅 <b>Departure:</b> " . tgEscape($dep_date) . ($return_date ? " | <b>Return:</b> " . tgEscape($return_date) : "") . "\n" .
         "👥 <b>Passengers:</b> " . tgEscape($adults) . " (" . tgEscape($cabin) . ")\n" .
         "🌐 <b>Trip Type:</b> " . tgEscape($trip_type) . "\n" .
         "━━━━━━━━━━━━━━━━━━━━";

sendTelegramMessage($tgMsg);

sendGoogleSheetMessage([
    'action'         => 'sheet1',
    'timestamp'      => date('Y-m-d H:i:s'),
    'customer_name'  => $name,
    'customer_phone' => $phone,
    'origin'         => $origin,
    'destination'    => $destination,
    'departure_date' => $dep_date,
    'return_date'    => $return_date,
    'trip_type'      => $trip_type,
    'adults'         => $adults,
    'cabin_class'    => $cabin,
    'user_ip'        => $_SERVER['REMOTE_ADDR'] ?? ''
]);

// Local CSV backup
$dir  = __DIR__ . '/../data';
if (!is_dir($dir)) { @mkdir($dir, 0755, true); }
$file = $dir . '/sheet1_searches.csv';
$isNew = !file_exists($file);
$fp = @fopen($file, 'a');
if ($fp) {
    if ($isNew) {
        fputcsv($fp, ['Timestamp','Customer Name','Phone Number','Origin','Destination',
                      'Departure Date','Return Date','Trip Type','Adults','Cabin Class','IP Address']);
    }
    fputcsv($fp, [
        date('Y-m-d H:i:s'), $name, $phone, $origin, $destination,
        $dep_date, $return_date, $trip_type, $adults, $cabin, $_SERVER['REMOTE_ADDR'] ?? ''
    ]);
    fclose($fp);
}

echo json_encode(['status' => 'success', 'telegram' => 'sent', 'csv' => 'saved']);
