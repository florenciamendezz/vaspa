<?php
/**
 * Carga de materias del Analista de Sistemas (Carrera 016)
 * Departamentos: Ciencias Sociales (ID 2) / Ciencias Exactas y Naturales (ID 1)
 */

$conn = new mysqli('localhost', 'root', '1234', 'bdgef_vaspa');
if ($conn->connect_error) die('Error: ' . $conn->connect_error);
$conn->set_charset('utf8');

// Ver todos los planes de la carrera 016
echo "=== PLANES DE CARRERA 016 ===\n";
$planes = $conn->query("SELECT * FROM plan WHERE idCarrera = '016' ORDER BY anio_inicio DESC");
while($p = $planes->fetch_object()) {
    echo "ID: $p->id | Inicio: $p->anio_inicio | Fin: " . ($p->anio_fin ?? 'NULL') . " | Estado: $p->estado\n";
}

// Ver escuela para sistemas
echo "\nEscuela de Sistemas e Informática: ";
$e = $conn->query("SELECT id, nombre FROM escuela WHERE nombre LIKE '%Sistem%'")->fetch_object();
echo ($e ? "$e->id - $e->nombre" : "NO ENCONTRADA") . "\n";

// Ver algunas materias existentes de sistemas
echo "\nMaterias existentes de esta lista:\n";
$codigos = ['1649','1654','1655','1657','1661','1666','1668','2138','0174','0175'];
foreach($codigos as $c) {
    $r = $conn->query("SELECT id, nombre FROM asignatura WHERE id = '$c'")->fetch_object();
    if($r) echo "  ✓ $r->id - $r->nombre\n";
}
