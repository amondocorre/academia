<?php
$conn = new mysqli('127.0.0.1', 'root', '', 'academia');
$res = $conn->query("SELECT id, nombre, activo FROM materias");
echo "MATERIAS:\n";
while($row = $res->fetch_assoc()) {
    echo "ID: {$row['id']} | Nombre: {$row['nombre']} | Activo: {$row['activo']}\n";
}
?>
