
<?php
/*
|--------------------------------------------------------------------------
| RideTrack Services Configuration
|--------------------------------------------------------------------------
| Leave credentials blank if a service is not configured.
| The website can still run without SMTP, SMS or bKash credentials.
|--------------------------------------------------------------------------
*/

const SMTP_HOST = 'smtp.gmail.com';
const SMTP_PORT = 465;
const SMTP_USER = '';
const SMTP_PASS = '';

const SMS_URL = '';

const BK_URL = 'https://tokenized.sandbox.bka.sh/v1.2.0-beta';
const BK_KEY = '';
const BK_SECRET = '';
const BK_USER = '';
const BK_PASS = '';

/*
 * Tesseract OCR is not available through a Windows local path
 * on InfinityFree hosting. Keep this blank unless configured
 * on a server that supports it.
 */
const TESSERACT = '';

const CA_BUNDLE = '';


/* =========================
   BASE URL
========================= */

function base()
{
    $https = (
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
    );

    $scheme = $https ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $path = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));

    return $scheme . '://' . $host . rtrim($path, '/');
}


/* =========================
   EXPIRED BOOKING CLEANUP
========================= */

function sweep()
{
    q("
        UPDATE bookings
        SET status = 'cancelled',
            active = NULL
        WHERE status = 'pending'
          AND created_at < NOW() - INTERVAL 10 MINUTE
    ");
}


/* =========================
   HTTP REQUEST
========================= */

function http($url, $headers = [], $body = null)
{
    if (!function_exists('curl_init')) {
        error_log('RideTrack: PHP cURL extension is not enabled.');
        return false;
    }

    $ch = curl_init($url);

    if (!$ch) {
        return false;
    }

    $options = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2
    ];

    if ($body !== null) {
        $options[CURLOPT_POST] = true;
        $options[CURLOPT_POSTFIELDS] = $body;
    }

    if (CA_BUNDLE !== '') {
        $options[CURLOPT_CAINFO] = CA_BUNDLE;
    }

    curl_setopt_array($ch, $options);

    $response = curl_exec($ch);

    if ($response === false) {
        error_log('RideTrack HTTP error: ' . curl_error($ch));
    }

    curl_close($ch);

    return $response;
}


/* =========================
   SEND EMAIL
========================= */

function send_mail($to, $sub, $body)
{
    if (
        SMTP_USER === ''
        || SMTP_PASS === ''
        || !filter_var($to, FILTER_VALIDATE_EMAIL)
        || !function_exists('stream_socket_client')
    ) {
        return false;
    }

    $socket = @stream_socket_client(
        'ssl://' . SMTP_HOST . ':' . SMTP_PORT,
        $errorNumber,
        $errorMessage,
        10
    );

    if (!$socket) {
        error_log('RideTrack SMTP connection failed: ' . $errorMessage);
        return false;
    }

    stream_set_timeout($socket, 10);

    $readResponse = function ($command = null) use ($socket) {
        if ($command !== null) {
            fwrite($socket, $command . "\r\n");
        }

        $response = '';

        while (($line = fgets($socket, 515)) !== false) {
            $response .= $line;

            if (strlen($line) < 4 || $line[3] === ' ') {
                break;
            }
        }

        return $response;
    };

    $readResponse();

    $ehlo = $readResponse('EHLO localhost');

    if (substr($ehlo, 0, 3) !== '250') {
        fclose($socket);
        return false;
    }

    $auth = $readResponse('AUTH LOGIN');

    if (substr($auth, 0, 3) !== '334') {
        fclose($socket);
        return false;
    }

    $readResponse(base64_encode(SMTP_USER));

    $passwordResponse = $readResponse(base64_encode(SMTP_PASS));

    if (substr($passwordResponse, 0, 3) !== '235') {
        fclose($socket);
        return false;
    }

    $fromResponse = $readResponse('MAIL FROM:<' . SMTP_USER . '>');

    if (substr($fromResponse, 0, 3) !== '250') {
        fclose($socket);
        return false;
    }

    $toResponse = $readResponse('RCPT TO:<' . $to . '>');

    if (!in_array(substr($toResponse, 0, 3), ['250', '251'], true)) {
        fclose($socket);
        return false;
    }

    $dataResponse = $readResponse('DATA');

    if (substr($dataResponse, 0, 3) !== '354') {
        fclose($socket);
        return false;
    }

    $safeBody = str_replace(
        ["\r\n.", "\n."],
        ["\r\n..", "\n.."],
        $body
    );

    $message =
        "From: Ride Track <" . SMTP_USER . ">\r\n" .
        "To: " . $to . "\r\n" .
        "Subject: =?UTF-8?B?" . base64_encode($sub) . "?=\r\n" .
        "MIME-Version: 1.0\r\n" .
        "Content-Type: text/plain; charset=UTF-8\r\n" .
        "\r\n" .
        $safeBody . "\r\n.\r\n";

    fwrite($socket, $message);

    $result = $readResponse();

    $success = substr($result, 0, 3) === '250';

    $readResponse('QUIT');

    fclose($socket);

    return $success;
}


/* =========================
   SEND SMS
========================= */

function send_sms($to, $msg)
{
    if (SMS_URL === '' || !$to) {
        return false;
    }

    $url = strtr(SMS_URL, [
        '{to}' => urlencode($to),
        '{msg}' => urlencode($msg)
    ]);

    $response = http($url);

    return $response !== false;
}


/* =========================
   BKASH CONFIGURATION
========================= */

function bk_on()
{
    return (
        BK_KEY !== ''
        && BK_SECRET !== ''
        && BK_USER !== ''
        && BK_PASS !== ''
        && BK_URL !== ''
    );
}


/* =========================
   BKASH API REQUEST
========================= */

function bk($path, $body, $token = null)
{
    if (!bk_on()) {
        return null;
    }

    $headers = [
        'Content-Type: application/json',
        'Accept: application/json'
    ];

    if ($token !== null) {
        $headers[] = 'Authorization: ' . $token;
        $headers[] = 'X-APP-Key: ' . BK_KEY;
    } else {
        $headers[] = 'username: ' . BK_USER;
        $headers[] = 'password: ' . BK_PASS;
    }

    $response = http(
        rtrim(BK_URL, '/') . $path,
        $headers,
        json_encode($body)
    );

    if ($response === false) {
        return null;
    }

    $decoded = json_decode($response, true);

    return is_array($decoded) ? $decoded : null;
}


/* =========================
   BKASH TOKEN
========================= */

function bk_token()
{
    if (!bk_on()) {
        return null;
    }

    $response = bk(
        '/tokenized/checkout/token/grant',
        [
            'app_key' => BK_KEY,
            'app_secret' => BK_SECRET
        ]
    );

    return $response['id_token'] ?? null;
}


/* =========================
   CREATE BKASH PAYMENT
========================= */

function bk_create($amount, $invoice, $callback)
{
    if (!bk_on()) {
        return null;
    }

    $token = bk_token();

    if (!$token) {
        return null;
    }

    $response = bk(
        '/tokenized/checkout/create',
        [
            'mode' => '0011',
            'payerReference' => $invoice,
            'callbackURL' => $callback,
            'amount' => number_format((float)$amount, 2, '.', ''),
            'currency' => 'BDT',
            'intent' => 'sale',
            'merchantInvoiceNumber' => $invoice
        ],
        $token
    );

    return (
        ($response['statusCode'] ?? '') === '0000'
    ) ? $response : null;
}


/* =========================
   EXECUTE BKASH PAYMENT
========================= */

function bk_exec($paymentId)
{
    if (!bk_on() || !$paymentId) {
        return null;
    }

    $token = bk_token();

    if (!$token) {
        return null;
    }

    return bk(
        '/tokenized/checkout/execute',
        [
            'paymentID' => $paymentId
        ],
        $token
    );
}
