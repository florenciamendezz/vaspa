<?php
$conn = new mysqli('localhost', 'root', '1234', 'bdgef_vaspa');
if ($conn->connect_error) die('Error: ' . $conn->connect_error);
$conn->set_charset('utf8');

// Describir todas las tablas relevantes
$tablas = ['plan', 'plan_asignatura', 'carrera_asignatura'];
foreach($tablas as $tabla) {
    echo "\n=== COLUMNAS DE $tabla ===\n";
    $res = $conn->query("DESCRIBE $tabla");
    while($row = $res->fetch_object()) {
        echo "$row->Field | $row->Type | Null: $row->Null | Default: $row->Default\n";
    }
    echo "\n--- Muestra de datos de $tabla ---\n";
    $r2 = $conn->query("SELECT * FROM $tabla LIMIT 5");
    while($row = $r2->fetch_assoc()) {
        echo implode(' | ', array_map(fn($v) => ($v ?? 'NULL'), $row)) . "\n";
    }
}
