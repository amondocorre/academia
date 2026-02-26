<?php
$conn = new mysqli('127.0.0.1', 'root', '', 'academia');
$res = $conn->query("SELECT id, nombre, email, rol, padre_id FROM usuarios");
echo "USUARIOS:\n";
while($row = $res->fetch_assoc()) {
    echo "ID: {$row['id']} | Nombre: {$row['nombre']} | Rol: {$row['rol']} | PadreID: {$row['padre_id']}\n";
}

echo "\nMATERIAS:\n";
$res2 = $conn->query("SELECT id, nombre FROM materias");
if (!$res2) {
    echo "Query failed: " . $conn->error . "\n";
} else {
    while($row = $res2->fetch_assoc()) {
        echo "ID: {$row['id']} | Nombre: {$row['nombre']}\n";
    }
}
?>
