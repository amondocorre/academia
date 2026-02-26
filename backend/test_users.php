<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=academia', 'root', '');
    $stmt = $pdo->query("SELECT id, email, rol, padre_id FROM usuarios");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch(Exception $e) {
    echo $e->getMessage();
}
