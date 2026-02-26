<?php
$conn = new mysqli('127.0.0.1', 'root', '', 'academia');
$res = $conn->query("SELECT * FROM horarios");
echo "HORARIOS:\n";
while($row = $res->fetch_assoc()) {
    print_r($row);
}
?>
