<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
function respond($code, $message) {
    http_response_code($code);
    echo json_encode($code === 200 ? ['ok' => true] : ['ok' => false, 'error' => $message]);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') respond(405, 'Please submit the inquiry form.');
if ((int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 24000) respond(413, 'Your inquiry is too long.');
$raw = file_get_contents('php://input', false, null, 0, 24001);
if (strlen($raw) > 24000) respond(413, 'Your inquiry is too long.');
$data = json_decode($raw, true);
if (!is_array($data)) respond(400, 'Invalid inquiry.');
foreach (['name', 'email', 'body', 'token', 'duration'] as $field) {
    if (!isset($data[$field]) || !is_string($data[$field])) respond(400, 'Please complete all required fields.');
}
$email = trim($data['email']);
$name = trim($data['name']);
if ($name === '' || strlen($name) > 160 || preg_match('/[\r\n]/', $name . $email) || strlen($email) > 254 || !filter_var($email, FILTER_VALIDATE_EMAIL)) respond(400, 'Please enter a valid name and email.');
if (!ctype_digit($data['duration']) || (int)$data['duration'] < 30) respond(400, 'Please enter a stay of at least 30 days.');
if (strlen($data['body']) > 16000 || $data['body'] === '') respond(400, 'Please shorten your inquiry.');
if ($data['token'] === '' || strlen($data['token']) > 2048) respond(400, 'Please complete the security check.');
$configPath = dirname(__DIR__) . '/ashwood-form-config.php';
if (!is_file($configPath)) respond(503, 'The inquiry service is temporarily unavailable.');
$config = require $configPath;
if (!is_array($config) || empty($config['turnstile_secret']) || !function_exists('curl_init')) respond(503, 'The inquiry service is temporarily unavailable.');
$ch = curl_init('https://challenges.cloudflare.com/turnstile/v0/siteverify');
curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query(['secret' => $config['turnstile_secret'], 'response' => $data['token']]), CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 5, CURLOPT_TIMEOUT => 15]);
$verified = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
if ($verified === false || $code !== 200) respond(503, 'Security verification is temporarily unavailable. Please try again.');
$result = json_decode($verified, true);
if (empty($result['success']) || !in_array($result['hostname'] ?? '', ['theashwoodnashville.com', 'www.theashwoodnashville.com'], true) || ($result['action'] ?? '') !== 'inquiry') respond(400, 'Security check failed or expired. Please try again.');
$headers = ['From: The Ashwood <concierge@theashwoodnashville.com>', 'Reply-To: ' . $email, 'MIME-Version: 1.0', 'Content-Type: text/plain; charset=UTF-8'];
$sent = mail('concierge@theashwoodnashville.com', 'The Ashwood Private Stay Inquiry', $data['body'], implode("\r\n", $headers));
if (!$sent) respond(503, 'Your inquiry could not be sent. Please try again.');
respond(200, '');
