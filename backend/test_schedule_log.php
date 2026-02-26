<?php
$conn = new mysqli('127.0.0.1', 'root', '', 'academia');
$dia = 3; // Miércoles
$fecha = "2026-02-25";

echo "--- SCHEDULE ---\n";
$res_schedule = $conn->query("SELECT h.id, h.materia_id, m.nombre FROM horarios h JOIN materias m ON h.materia_id = m.id WHERE h.usuario_id = 2 AND h.dia_semana = 3");
while($row = $res_schedule->fetch_assoc()) print_r($row);

echo "\n--- DAILY LOG ---\n";
$res_log = $conn->query("SELECT * FROM registros_diarios WHERE usuario_id = 2");
while($row = $res_log->fetch_assoc()) print_r($row);
?>
