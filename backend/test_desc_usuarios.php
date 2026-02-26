<?php
$conn = new mysqli('127.0.0.1', 'root', '', 'academia');
$res = $conn->query("DESCRIBE usuarios");
while($row = $res->fetch_assoc()) print_r($row);
?>
