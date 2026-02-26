<?php
$conn = new mysqli('127.0.0.1', 'root', '', 'academia');
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$sql = "ALTER TABLE usuarios ADD COLUMN foto_perfil VARCHAR(255) DEFAULT NULL AFTER fecha_nacimiento";
if ($conn->query($sql) === TRUE) {
  echo "Column created successfully";
} else {
  echo "Error creating column: " . $conn->error;
}
?>
