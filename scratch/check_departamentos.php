<?php
$conn = new mysqli('localhost', 'root', '1234', 'bdgef_vaspa');
if ($conn->connect_error) die('Error: ' . $conn->connect_error);

// Total de asignaturas
$total = $conn->query('SELECT COUNT(*) as n FROM asignatura')->fetch_object()->n;

// Con departamento asignado
$conDepto = $conn->query('SELECT COUNT(*) as n FROM asignatura WHERE idDepartamento IS NOT NULL AND idDepartamento != 0')->fetch_object()->n;

// Sin departamento
$sinDepto = $conn->query('SELECT COUNT(*) as n FROM asignatura WHERE idDepartamento IS NULL OR idDepartamento = 0')->fetch_object()->n;

echo 'Total asignaturas: ' . $total . PHP_EOL;
echo 'Con departamento asignado: ' . $conDepto . PHP_EOL;
echo 'Sin departamento: ' . $sinDepto . PHP_EOL;

// Ver departamentos existentes
echo PHP_EOL . '--- Departamentos existentes en BD ---' . PHP_EOL;
$deptos = $conn->query('SELECT id, nombre FROM DEPARTAMENTO ORDER BY nombre');
while($d = $deptos->fetch_object()) {
    echo 'ID ' . $d->id . ': ' . $d->nombre . PHP_EOL;
}
