<?php
$conn = new mysqli('localhost', 'root', '1234', 'bdgef_vaspa');
if ($conn->connect_error) die('Error: ' . $conn->connect_error);
$conn->set_charset('utf8');

// Ver planes de la carrera 001
echo "=== PLANES de Carrera 001 ===\n";
$res = $conn->query("SELECT * FROM plan WHERE idCarrera = '001'");
while($row = $res->fetch_object()) {
    echo "Plan ID: $row->id | Año: $row->anio | Descripción: $row->descripcion\n";
}

// Ver estructura tabla asignatura
echo "\n=== COLUMNAS de tabla asignatura ===\n";
$res = $conn->query("DESCRIBE asignatura");
while($row = $res->fetch_object()) {
    echo "$row->Field | $row->Type | Null: $row->Null | Default: $row->Default\n";
}

// Ver estructura tabla planasignatura (relación plan-asignatura)
echo "\n=== TABLA plan_asignatura (o similar) ===\n";
$res = $conn->query("SHOW TABLES LIKE '%asignatura%'");
while($row = $res->fetch_array()) {
    echo "Tabla: " . $row[0] . "\n";
}
$res2 = $conn->query("SHOW TABLES LIKE '%plan%'");
while($row = $res2->fetch_array()) {
    echo "Tabla: " . $row[0] . "\n";
}

// Ver las 3 materias existentes
echo "\n=== MATERIAS EXISTENTES (que matchearon) ===\n";
$codigos = [1107, 1108, 1122];
foreach($codigos as $c) {
    $r = $conn->query("SELECT id, nombre, idDepartamento FROM asignatura WHERE id = '$c'")->fetch_object();
    if($r) echo "ID: $r->id | $r->nombre | Depto: $r->idDepartamento\n";
}
