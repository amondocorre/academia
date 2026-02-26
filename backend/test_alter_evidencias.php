<?php
$conn = new mysqli('127.0.0.1', 'root', '', 'academia');
$conn->query("ALTER TABLE evidencias ADD COLUMN tipo VARCHAR(20) DEFAULT 'tarea';");
$conn->query("UPDATE evidencias SET tipo = 'avance';"); // Existing ones act as classwork or homework, doesn't matter much. Let's make them 'tarea' as default, except the ones we already had.
echo "Column added.";
?>
