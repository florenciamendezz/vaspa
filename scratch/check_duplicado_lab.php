<?php
$conn = new mysqli('localhost', 'root', '1234', 'bdgef_vaspa');
if ($conn->connect_error) die('Error: ' . $conn->connect_error);
$conn->set_charset('utf8');

echo "=== DUPLICADOS: Laboratorio de Desarrollo de Software ===\n\n";

$ids = ['1667', '2138'];
foreach ($ids as $id) {
    $a = $conn->query("SELECT * FROM asignatura WHERE id = '$id'")->fetch_object();
    if (!$a) { echo "ID $id: NO EXISTE\n\n"; continue; }
    echo "ID: $a->id | $a->nombre | Depto: $a->idDepartamento | Escuela: $a->idEscuela\n";

    $planes = $conn->query("SELECT idPlan FROM plan_asignatura WHERE idAsignatura = '$id'");
    echo "  Planes: ";
    $ps = [];
    while($p = $planes->fetch_object()) $ps[] = $p->idPlan;
    echo (count($ps) ? implode(', ', $ps) : 'ninguno') . "\n";

    $carreras = $conn->query("SELECT idCarrera FROM carrera_asignatura WHERE idAsignatura = '$id'");
    echo "  Carreras: ";
    $cs = [];
    while($c = $carreras->fetch_object()) $cs[] = $c->idCarrera;
    echo (count($cs) ? implode(', ', $cs) : 'ninguna') . "\n\n";
}
