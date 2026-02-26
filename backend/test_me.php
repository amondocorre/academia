<?php
// 1. Login
$ch = curl_init('http://127.0.0.1/academia/backend/api/auth/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['email'=>'padre@familia.com','password'=>'123456']));
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
$result = curl_exec($ch);
$json_start = strpos($result, '{');
$json_str = substr($result, $json_start);
$login_data = json_decode($json_str, true);

if (!isset($login_data['token'])) {
    die("Login failed!\n" . $result);
}

$token = $login_data['token'];
echo "Token received: " . substr($token, 0, 20) . "...\n";

$ch2 = curl_init('http://127.0.0.1/academia/backend/api/auth/me');
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch2, CURLOPT_HTTPHEADER, array(
    'Authorization: Bearer ' . $token,
    'Content-Type: application/json'
));
$result2 = curl_exec($ch2);
$code = curl_getinfo($ch2, CURLINFO_HTTP_CODE);

echo "STATUS /me: " . $code . "\n";
echo "BODY /me: " . $result2 . "\n";
?>
