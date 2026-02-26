<?php
$conn = new mysqli('127.0.0.1', 'root', '', 'academia');
$res = $conn->query("SELECT id, email, password, activo FROM usuarios");
while($row = $res->fetch_assoc()) {
    echo "ID: {$row['id']} | Email: {$row['email']} | Activo: {$row['activo']} | Pass: {$row['password']}\n";
    echo "Verify 123456: " . (password_verify('123456', $row['password']) ? 'OK' : 'FAIL') . "\n";
}
?>
