<?php
// ─────────────────────────────────────────────
//  Mobile Financial Services — bKash & Nagad
// ─────────────────────────────────────────────

switch ($action) {

    // ── bKash: create payment session ──────────
    case 'bkash_create':
        Auth::requireAuth();
        $amount  = (float)($_POST['amount'] ?? 0);
        $invoice = trim($_POST['invoice']  ?? generate_ref('BKASH'));
        if ($amount <= 0) Response::error('Invalid amount');

        $cfg = _mfs_bkash_config();
        if (!$cfg) Response::error('bKash API not configured. Go to Settings → Payment → MFS API.');

        $token = _mfs_bkash_token($cfg);
        if (!$token) Response::error('bKash token grant failed. Check API credentials.');

        // Cache token for this session (valid ~3600s)
        set_setting('_bkash_token_cache',     $token);
        set_setting('_bkash_token_expiry',    time() + 3500);

        $sandbox = $cfg['sandbox'];
        $base    = $sandbox
            ? 'https://tokenized.sandbox.bka.sh/v1.2.0-beta/tokenized/checkout'
            : 'https://tokenized.pay.bka.sh/v1.2.0-beta/tokenized/checkout';

        $cbUrl = app_url('routes/api.php?module=mfs&action=bkash_callback');
        $payload = json_encode([
            'mode'                  => '0011',
            'payerReference'        => '01',
            'callbackURL'           => $cbUrl,
            'amount'                => number_format($amount, 2, '.', ''),
            'currency'              => 'BDT',
            'intent'                => 'sale',
            'merchantInvoiceNumber' => $invoice,
        ]);

        $resp = _mfs_curl('POST', $base . '/create', $payload, [
            'Content-Type: application/json',
            'Authorization: ' . $token,
            'X-APP-Key: '     . $cfg['app_key'],
        ]);

        if (($resp['statusCode'] ?? '') !== '0000') {
            Response::error($resp['statusMessage'] ?? 'bKash payment creation failed');
        }

        // Store pending payment in session/DB for verification
        DB::query(
            "INSERT INTO settings (`key`,value,`group`) VALUES (?,?,?)
             ON DUPLICATE KEY UPDATE value=?",
            ['_bkash_pending_' . $resp['paymentID'], json_encode([
                'paymentID' => $resp['paymentID'],
                'amount'    => $amount,
                'invoice'   => $invoice,
                'created'   => time(),
            ]), 'temp', json_encode([
                'paymentID' => $resp['paymentID'],
                'amount'    => $amount,
                'invoice'   => $invoice,
                'created'   => time(),
            ])]
        );

        Response::success([
            'paymentID' => $resp['paymentID'],
            'bkashURL'  => $resp['bkashURL']  ?? null,
            'sandbox'   => $sandbox,
        ]);
        break;

    // ── bKash: poll / execute payment ──────────
    case 'bkash_execute':
        Auth::requireAuth();
        $paymentID = trim($_POST['paymentID'] ?? $_GET['paymentID'] ?? '');
        if (!$paymentID) Response::error('paymentID required');

        $cfg = _mfs_bkash_config();
        if (!$cfg) Response::error('bKash not configured');

        $token = _mfs_bkash_get_or_refresh_token($cfg);
        if (!$token) Response::error('Token refresh failed');

        $base = $cfg['sandbox']
            ? 'https://tokenized.sandbox.bka.sh/v1.2.0-beta/tokenized/checkout'
            : 'https://tokenized.pay.bka.sh/v1.2.0-beta/tokenized/checkout';

        $resp = _mfs_curl('POST', $base . '/execute', json_encode(['paymentID' => $paymentID]), [
            'Content-Type: application/json',
            'Authorization: ' . $token,
            'X-APP-Key: '     . $cfg['app_key'],
        ]);

        if (($resp['statusCode'] ?? '') === '0000') {
            Response::success([
                'status'  => 'completed',
                'trxID'   => $resp['trxID']         ?? '',
                'amount'  => $resp['amount']         ?? '',
                'message' => $resp['statusMessage']  ?? 'Payment successful',
            ]);
        }
        Response::error($resp['statusMessage'] ?? 'Payment not completed yet');
        break;

    // ── bKash: query payment status ─────────────
    case 'bkash_status':
        Auth::requireAuth();
        $paymentID = trim($_GET['paymentID'] ?? '');
        if (!$paymentID) Response::error('paymentID required');

        $cfg = _mfs_bkash_config();
        if (!$cfg) Response::error('bKash not configured');

        $token = _mfs_bkash_get_or_refresh_token($cfg);
        if (!$token) Response::error('Token error');

        $base = $cfg['sandbox']
            ? 'https://tokenized.sandbox.bka.sh/v1.2.0-beta/tokenized/checkout'
            : 'https://tokenized.pay.bka.sh/v1.2.0-beta/tokenized/checkout';

        $resp = _mfs_curl('GET', $base . '/payment/status?paymentID=' . urlencode($paymentID), null, [
            'Authorization: ' . $token,
            'X-APP-Key: '     . $cfg['app_key'],
        ]);

        $status = $resp['transactionStatus'] ?? $resp['statusMessage'] ?? 'unknown';
        Response::success([
            'statusCode'  => $resp['statusCode'] ?? '',
            'status'      => $status,
            'trxID'       => $resp['trxID']       ?? '',
            'amount'      => $resp['amount']       ?? '',
            'completed'   => ($resp['statusCode'] ?? '') === '0000',
        ]);
        break;

    // ── bKash: callback (redirect from bKash app) ─
    case 'bkash_callback':
        $paymentID = $_GET['paymentID'] ?? '';
        $status    = $_GET['status']    ?? '';
        // Redirect back to POS with result
        $url = app_url('public/pos.php') . '?bkash_pid=' . urlencode($paymentID) . '&bkash_status=' . urlencode($status);
        header('Location: ' . $url);
        exit;

    // ── Nagad: create checkout ──────────────────
    case 'nagad_create':
        Auth::requireAuth();
        $amount  = (float)($_POST['amount'] ?? 0);
        $orderId = trim($_POST['invoice']   ?? generate_ref('NAGAD'));
        if ($amount <= 0) Response::error('Invalid amount');

        $cfg = _mfs_nagad_config();
        if (!$cfg) Response::error('Nagad API not configured. Go to Settings → Payment → MFS API.');

        $sandbox     = $cfg['sandbox'];
        $baseUrl     = $sandbox
            ? 'https://api.mynagad.com'
            : 'https://api.mynagad.com';
        $merchantId  = $cfg['merchant_id'];
        $merchantKey = $cfg['merchant_key'];
        $publicKey   = $cfg['public_key'];

        // Nagad requires RSA encryption — build datetime-based request
        $datetime    = date('Ymd') . 'T' . date('His') . 'Z';
        $sensitiveData = json_encode([
            'merchantId'       => $merchantId,
            'datetime'         => $datetime,
            'orderId'          => $orderId,
            'challenge'        => bin2hex(random_bytes(16)),
        ]);

        // Encrypt sensitive data with Nagad public key
        $encryptedSensitiveData = _mfs_nagad_encrypt($sensitiveData, $publicKey);
        if (!$encryptedSensitiveData) Response::error('Nagad encryption failed. Check public key.');

        // Sign with merchant private key
        $signature = _mfs_nagad_sign($sensitiveData, $merchantKey);
        if (!$signature) Response::error('Nagad signature failed. Check merchant key.');

        $initPayload = [
            'datetime'           => $datetime,
            'sensitiveData'      => $encryptedSensitiveData,
            'signature'          => $signature,
            'merchantCallbackURL'=> app_url('routes/api.php?module=mfs&action=nagad_callback'),
        ];

        $resp = _mfs_curl('POST',
            $baseUrl . '/api/dfs/check-out/initialize/' . $merchantId . '/' . $orderId,
            json_encode($initPayload),
            ['Content-Type: application/json', 'X-KM-Api-Version: v-0.2.0', 'X-KM-IP-V4: ' . ($_SERVER['SERVER_ADDR'] ?? '127.0.0.1'), 'X-KM-Client-Type: PC_WEB']
        );

        if (empty($resp['sensitiveData'])) {
            Response::error($resp['message'] ?? 'Nagad init failed');
        }

        // Decrypt response sensitiveData
        $decrypted = _mfs_nagad_decrypt($resp['sensitiveData'], $merchantKey);
        $initData  = $decrypted ? json_decode($decrypted, true) : null;
        if (!$initData || empty($initData['paymentReferenceId'])) {
            Response::error('Nagad init response invalid');
        }

        $refId      = $initData['paymentReferenceId'];
        $challenge  = $initData['challenge'];

        // Complete checkout
        $compPayload = json_encode([
            'sensitiveData' => _mfs_nagad_encrypt(json_encode([
                'merchantId'           => $merchantId,
                'orderId'              => $orderId,
                'currencyCode'         => 'BDT',
                'amount'               => number_format($amount, 2, '.', ''),
                'challenge'            => $challenge,
            ]), $publicKey),
            'signature' => _mfs_nagad_sign(json_encode([
                'merchantId'           => $merchantId,
                'orderId'              => $orderId,
                'currencyCode'         => 'BDT',
                'amount'               => number_format($amount, 2, '.', ''),
                'challenge'            => $challenge,
            ]), $merchantKey),
            'merchantCallbackURL' => app_url('routes/api.php?module=mfs&action=nagad_callback'),
            'additionalMerchantInfo' => new stdClass(),
        ]);

        $compResp = _mfs_curl('POST',
            $baseUrl . '/api/dfs/check-out/complete/' . $merchantId . '/' . $refId,
            $compPayload,
            ['Content-Type: application/json', 'X-KM-Api-Version: v-0.2.0', 'X-KM-IP-V4: ' . ($_SERVER['SERVER_ADDR'] ?? '127.0.0.1'), 'X-KM-Client-Type: PC_WEB']
        );

        if (empty($compResp['callBackUrl'])) {
            Response::error($compResp['message'] ?? 'Nagad complete failed');
        }

        Response::success([
            'redirectUrl' => $compResp['callBackUrl'],
            'refId'       => $refId,
            'orderId'     => $orderId,
        ]);
        break;

    // ── Nagad: verify transaction ───────────────
    case 'nagad_verify':
        Auth::requireAuth();
        $refId = trim($_GET['refId'] ?? '');
        if (!$refId) Response::error('refId required');

        $cfg = _mfs_nagad_config();
        if (!$cfg) Response::error('Nagad not configured');

        $sandbox = $cfg['sandbox'];
        $baseUrl = $sandbox ? 'https://api.mynagad.com' : 'https://api.mynagad.com';

        $resp = _mfs_curl('GET',
            $baseUrl . '/api/dfs/verify/payment/' . $refId, null,
            ['Content-Type: application/json', 'X-KM-Api-Version: v-0.2.0']
        );

        $completed = ($resp['status'] ?? '') === 'Success';
        Response::success([
            'completed' => $completed,
            'status'    => $resp['status']  ?? 'unknown',
            'trxID'     => $resp['trxId']   ?? '',
            'amount'    => $resp['amount']  ?? '',
        ]);
        break;

    case 'nagad_callback':
        $refId  = $_GET['payment_ref_id'] ?? '';
        $status = $_GET['status']         ?? '';
        $url    = app_url('public/pos.php') . '?nagad_ref=' . urlencode($refId) . '&nagad_status=' . urlencode($status);
        header('Location: ' . $url);
        exit;

    default:
        Response::error('Unknown MFS action', 404);
}

// ══════════════════════════════════════════════
//  Helper functions
// ══════════════════════════════════════════════

function _mfs_bkash_config(): ?array
{
    $appKey    = get_setting('bkash_app_key');
    $appSecret = get_setting('bkash_app_secret');
    $username  = get_setting('bkash_username');
    $password  = get_setting('bkash_password');
    $sandbox   = get_setting('bkash_sandbox', '1') === '1';
    if (!$appKey || !$appSecret || !$username || !$password) return null;
    return compact('appKey', 'appSecret', 'username', 'password', 'sandbox') + [
        'app_key' => $appKey, 'app_secret' => $appSecret,
    ];
}

function _mfs_nagad_config(): ?array
{
    $merchantId  = get_setting('nagad_merchant_id');
    $merchantKey = get_setting('nagad_merchant_key');
    $publicKey   = get_setting('nagad_public_key');
    $sandbox     = get_setting('nagad_sandbox', '1') === '1';
    if (!$merchantId || !$merchantKey) return null;
    return compact('merchantId', 'merchantKey', 'publicKey', 'sandbox');
}

function _mfs_bkash_token(array $cfg): ?string
{
    $sandbox = $cfg['sandbox'];
    $url = $sandbox
        ? 'https://tokenized.sandbox.bka.sh/v1.2.0-beta/tokenized/checkout/token/grant'
        : 'https://tokenized.pay.bka.sh/v1.2.0-beta/tokenized/checkout/token/grant';

    $resp = _mfs_curl('POST', $url, json_encode([
        'app_key'    => $cfg['app_key'],
        'app_secret' => $cfg['app_secret'],
    ]), [
        'Content-Type: application/json',
        'username: ' . $cfg['username'],
        'password: ' . $cfg['password'],
    ]);

    return $resp['id_token'] ?? null;
}

function _mfs_bkash_get_or_refresh_token(array $cfg): ?string
{
    $expiry = (int)get_setting('_bkash_token_expiry', 0);
    if ($expiry > time() + 60) {
        $cached = get_setting('_bkash_token_cache');
        if ($cached) return $cached;
    }
    $token = _mfs_bkash_token($cfg);
    if ($token) {
        set_setting('_bkash_token_cache',  $token);
        set_setting('_bkash_token_expiry', time() + 3500);
    }
    return $token;
}

function _mfs_curl(string $method, string $url, ?string $body, array $headers): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    if ($body !== null && in_array($method, ['POST', 'PUT', 'PATCH'])) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    }
    $raw = curl_exec($ch);
    curl_close($ch);
    return $raw ? (json_decode($raw, true) ?? []) : [];
}

function _mfs_nagad_encrypt(string $data, string $publicKey): ?string
{
    if (!$publicKey) return null;
    $key    = "-----BEGIN PUBLIC KEY-----\n" . chunk_split($publicKey, 64, "\n") . "-----END PUBLIC KEY-----";
    $pubKey = openssl_get_publickey($key);
    if (!$pubKey) return null;
    $encrypted = '';
    openssl_public_encrypt($data, $encrypted, $pubKey);
    return base64_encode($encrypted);
}

function _mfs_nagad_sign(string $data, string $privateKey): ?string
{
    if (!$privateKey) return null;
    $key    = "-----BEGIN RSA PRIVATE KEY-----\n" . chunk_split($privateKey, 64, "\n") . "-----END RSA PRIVATE KEY-----";
    $privKey = openssl_get_privatekey($key);
    if (!$privKey) return null;
    $sig = '';
    openssl_sign($data, $sig, $privKey, OPENSSL_ALGO_SHA256);
    return base64_encode($sig);
}

function _mfs_nagad_decrypt(string $encData, string $privateKey): ?string
{
    if (!$privateKey) return null;
    $key     = "-----BEGIN RSA PRIVATE KEY-----\n" . chunk_split($privateKey, 64, "\n") . "-----END RSA PRIVATE KEY-----";
    $privKey = openssl_get_privatekey($key);
    if (!$privKey) return null;
    $decoded   = base64_decode($encData);
    $decrypted = '';
    openssl_private_decrypt($decoded, $decrypted, $privKey);
    return $decrypted ?: null;
}
