<?php
/**
 * api/send-sms.php — AfroMessage SMS Gateway Backend Proxy
 *
 * Routes SMS dispatch requests through backend server to avoid browser CORS restrictions.
 * Normalizes phone numbers to digits-only format without leading '+' (e.g. 251916403938).
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ── Auto-load .env file ──
$envPath = dirname(__DIR__) . '/.env';
if (file_exists($envPath)) {
    foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) continue;
        [$key, $val] = explode('=', $line, 2);
        $key = trim($key);
        $val = trim($val, " \t\n\r\0\x0B\"'");
        if (!getenv($key)) putenv("$key=$val");
    }
}

// ── Parse request body ──
$input   = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$phone   = trim($input['phone']   ?? '');
$message = trim($input['message'] ?? '');

if (!$phone || !$message) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'phone and message parameters are required']);
    exit;
}

// ── Normalize phone number (E.164 digits-only, strictly without leading '+') ──
$cleanPhone = preg_replace('/[^\d]/', '', $phone);
if (str_starts_with($cleanPhone, '0') && strlen($cleanPhone) === 10) {
    $cleanPhone = '251' . substr($cleanPhone, 1);
} elseif (str_starts_with($cleanPhone, '9') && strlen($cleanPhone) === 9) {
    $cleanPhone = '251' . $cleanPhone;
}

// ── AfroMessage Credentials ──
$apiKey   = getenv('AFROMESSAGE_API_KEY') ?: (getenv('AFROMESSAGE_TOKEN') ?: '');
$senderId = getenv('AFROMESSAGE_SENDER_ID') ?: '';

if (!$apiKey) {
    echo json_encode([
        'success'    => true,
        'phone'      => $cleanPhone,
        'messageSid' => 'AFRO-MOCK-' . strtoupper(substr(md5(uniqid()), 0, 10)),
        'gateway'    => 'AfroMessage Proxy (Mock Mode)',
        'note'       => 'AFROMESSAGE_API_KEY not configured in .env',
    ]);
    exit;
}

// ── Build AfroMessage GET request ──
$params = http_build_query(array_filter([
    'to'      => $cleanPhone,
    'message' => $message,
    'from'    => $senderId ?: null,
]));

$url = 'https://api.afromessage.com/api/send?' . $params;

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPGET        => true,
    CURLOPT_TIMEOUT        => 15,
    CURLOPT_HTTPHEADER     => [
        'Authorization: Bearer ' . $apiKey,
        'Accept: application/json',
    ],
    CURLOPT_SSL_VERIFYPEER => true,
]);

$res     = curl_exec($ch);
$code    = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr = curl_error($ch);
curl_close($ch);

if ($curlErr) {
    http_response_code(502);
    echo json_encode(['success' => false, 'error' => 'cURL error: ' . $curlErr, 'phone' => $cleanPhone]);
    exit;
}

$json = json_decode($res, true);

if ($code >= 200 && $code < 300 && ($json['acknowledge'] ?? '') === 'success') {
    echo json_encode([
        'success'    => true,
        'phone'      => $cleanPhone,
        'messageSid' => $json['response']['id'] ?? ('AFRO-' . time()),
        'gateway'    => 'AfroMessage (Backend Proxy)',
        'rawPayload' => $json
    ]);
    exit;
}

$apiError = $json['message'] ?? $json['error'] ?? $json['response']['message'] ?? ("HTTP $code: $res");
error_log("[AfroMessage Backend Proxy Error] $apiError | Phone: $cleanPhone");

http_response_code(502);
echo json_encode([
    'success'    => false,
    'error'      => 'AfroMessage: ' . $apiError,
    'phone'      => $cleanPhone,
    'rawPayload' => $json
]);
