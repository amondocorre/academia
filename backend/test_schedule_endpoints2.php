<?php
$ch = curl_init('http://127.0.0.1/academia/backend/api/auth/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['email'=>'amondocorre@gmail.com','password'=>'123456']));
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
$result = curl_exec($ch);

$json_start = strpos($result, '{');
$json_str = substr($result, $json_start);
$login_data = json_decode($json_str, true);
if(!isset($login_data['token'])) die("Login FAILED. JSON: " . print_r($login_data, true));
$token = $login_data['token'];

$ch2 = curl_init('http://127.0.0.1/academia/backend/api/schedule?hijo_id=4');
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch2, CURLOPT_HTTPHEADER, array(
    'Authorization: Bearer ' . $token,
    'Content-Type: application/json'
));
$result2 = curl_exec($ch2);
$code = curl_getinfo($ch2, CURLINFO_HTTP_CODE);

echo "STATUS /schedule: " . $code . "\n";
echo "BODY /schedule: " . $result2 . "\n";
?>
