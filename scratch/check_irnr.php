<?php
/**
 * Carga de materias de Ingeniería en Recursos Naturales Renovables (Carrera 023)
 * Departamentos: Ciencias Sociales (ID 2) / Ciencias Exactas y Naturales (ID 1)
 */

$conn = new mysqli('localhost', 'root', '1234', 'bdgef_vaspa');
if ($conn->connect_error) die('Error: ' . $conn->connect_error);
$conn->set_charset('utf8');

// Verificar planes y escuela
echo "=== PLANES DE CARRERA 023 ===\n";
$planes = $conn->query("SELECT * FROM plan WHERE idCarrera = '023' ORDER BY anio_inicio DESC");
while($p = $planes->fetch_object()) {
    echo "ID: $p->id | Inicio: $p->anio_inicio | Fin: " . ($p->anio_fin ?? 'NULL') . " | Estado: $p->estado\n";
}
$escuela = $conn->query("SELECT id, nombre FROM escuela WHERE nombre LIKE '%Natural%'")->fetch_object();
echo "\nEscuela: " . ($escuela ? "$escuela->id - $escuela->nombre" : "NO ENCONTRADA") . "\n";
