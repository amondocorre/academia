<?php
$ch = curl_init('http://127.0.0.1/academia/backend/api/auth/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['email'=>'xcalixto2.0@gmail.com','password'=>'123456']));
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
$result = curl_exec($ch);

// Extraer JSON ignorando warnings de PHP
$json_start = strpos($result, '{');
if ($json_start === false) {
    die("No JSON found in login output:\n" . $result);
}
$json_str = substr($result, $json_start);
$login_data = json_decode($json_str, true);

if (!$login_data || !isset($login_data['token'])) {
    die("Login failed. JSON parsed:\n" . print_r($login_data, true) . "\nRaw JSON:\n" . $json_str);
}

$token = $login_data['token'];

$ch2 = curl_init('http://127.0.0.1/academia/backend/api/schedule/materias');
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch2, CURLOPT_HTTPHEADER, array(
    'Authorization: Bearer ' . $token,
    'Content-Type: application/json'
));
$result2 = curl_exec($ch2);
$code = curl_getinfo($ch2, CURLINFO_HTTP_CODE);

echo "STATUS /materias: " . $code . "\n";
echo "BODY /materias: " . $result2 . "\n";
?>
