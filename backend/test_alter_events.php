<?php
$conn = new mysqli('127.0.0.1', 'root', '', 'academia');
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$sql = "ALTER TABLE calendario_eventos ADD COLUMN imagen VARCHAR(255) DEFAULT NULL;
        ALTER TABLE calendario_eventos ADD COLUMN fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP;";
if ($conn->multi_query($sql)) {
  echo "Column created successfully";
} else {
  echo "Error creating column: " . $conn->error;
}
?>
