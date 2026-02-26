<?php
$ch = curl_init('http://localhost/academia/backend/api/auth/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['email'=>'test@test.com','password'=>'123']));
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
$result = curl_exec($ch);
echo "STATUS: " . curl_getinfo($ch, CURLINFO_HTTP_CODE) . "\n";
echo "BODY: " . $result . "\n";
?>
