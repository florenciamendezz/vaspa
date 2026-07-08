<?php
$conn = new mysqli('localhost', 'root', '1234', 'bdgef_vaspa');
if ($conn->connect_error) die('Error: ' . $conn->connect_error);
$conn->set_charset('utf8');

$buscar = ['Turismo', 'Geografía', 'Química', 'Comunicación', 'Administración', 'Gestión'];
foreach ($buscar as $term) {
    echo "\n--- Carreras con '$term' ---\n";
    $r = $conn->query("SELECT id, nombre FROM carrera WHERE nombre LIKE '%$term%' ORDER BY id");
    while($row = $r->fetch_object()) echo "  $row->id | $row->nombre\n";
}

echo "\n--- Planes vigentes de carreras 913, 914, 918 ---\n";
foreach (['913','914','918'] as $c) {
    $p = $conn->query("SELECT id, anio_inicio, estado FROM plan WHERE idCarrera='$c' ORDER BY anio_inicio DESC")->fetch_object();
    echo "  $c → " . ($p ? "$p->id ($p->anio_inicio, $p->estado)" : "NO ENCONTRADO") . "\n";
}
