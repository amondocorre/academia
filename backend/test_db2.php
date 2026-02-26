<?php
$conn = new mysqli('127.0.0.1', 'root', '', 'academia');
$res = $conn->query("SELECT * FROM horarios_materias LIMIT 1");
print_r($res->fetch_assoc());
?>
