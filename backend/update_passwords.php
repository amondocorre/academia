<?php
$conn = new mysqli('127.0.0.1', 'root', '', 'academia');
$hash = password_hash('123456', PASSWORD_BCRYPT);
$conn->query("UPDATE usuarios SET password = '$hash'");
echo "All passwords updated to 123456\n";

$res = $conn->query("SELECT id, email, rol FROM usuarios");
while($row = $res->fetch_assoc()) {
    echo "ID: {$row['id']} | Email: {$row['email']} | Rol: {$row['rol']}\n";
}
?>
