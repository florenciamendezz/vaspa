<?php
/**
 * Carga de materias del Profesorado en Geografía (Carrera 004, Plan 004P2)
 * Departamentos: Ciencias Sociales (ID 2) / Ciencias Exactas y Naturales (ID 1)
 */

$conn = new mysqli('localhost', 'root', '1234', 'bdgef_vaspa');
if ($conn->connect_error) die('Error: ' . $conn->connect_error);
$conn->set_charset('utf8');

// Verificar plan y escuela
$plan = $conn->query("SELECT * FROM plan WHERE idCarrera = '004'")->fetch_object();
echo "Plan encontrado: " . ($plan ? $plan->id . " (inicio: $plan->anio_inicio, estado: $plan->estado)" : "NINGUNO") . "\n";

$escuelas = $conn->query("SELECT id, nombre FROM escuela ORDER BY id");
echo "\nEscuelas disponibles:\n";
while($e = $escuelas->fetch_object()) echo "  $e->id - $e->nombre\n";
