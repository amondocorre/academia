<?php
error_reporting(0);
try {
    $pdo = new PDO('mysql:host=localhost;dbname=academia', 'root', '');
    $hoy = date('Y-m-d');
    
    // Get all children
    $stmt = $pdo->query("SELECT id, nombre FROM usuarios WHERE rol='hijo'");
    $hijos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach($hijos as $hijo) {
        $usuario_id = $hijo['id'];
        $stmt = $pdo->query("SELECT id, titulo, tipo, fecha FROM calendario_eventos WHERE usuario_id = $usuario_id AND fecha >= '$hoy' ORDER BY fecha ASC LIMIT 3");
        $eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "Eventos para hijo {$hijo['nombre']} (ID: $usuario_id):\n";
        print_r($eventos);
    }
} catch(Exception $e) {
    echo $e->getMessage();
}
