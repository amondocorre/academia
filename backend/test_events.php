<?php
error_reporting(0);
try {
    $pdo = new PDO('mysql:host=localhost;dbname=academia', 'root', '');
    $stmt = $pdo->query("SELECT id, usuario_id, titulo, tipo, fecha FROM calendario_eventos");
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_PRETTY_PRINT);
} catch(Exception $e) {
    echo $e->getMessage();
}
