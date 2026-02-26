<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=academia', 'root', '');

    echo "\n--- Altering calendario_eventos ---\n";
    $pdo->query("ALTER TABLE calendario_eventos ADD COLUMN estado ENUM('pendiente', 'concluido') DEFAULT 'pendiente' AFTER tipo");
    echo "Column added.\n";
    
    $stmt = $pdo->query("DESCRIBE calendario_eventos");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch(Exception $e) {
    echo $e->getMessage();
}


