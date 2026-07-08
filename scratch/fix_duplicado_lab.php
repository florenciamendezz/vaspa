<?php
$conn = new mysqli('localhost', 'root', '1234', 'bdgef_vaspa');
if ($conn->connect_error) die('Error: ' . $conn->connect_error);
$conn->set_charset('utf8');

$idEliminar = '1667'; // Duplicado a eliminar
$idCorrecta = '2138'; // La original correcta

echo "=== ELIMINANDO DUPLICADO ID $idEliminar ===\n\n";

// 1. Verificar que la correcta ya cubre los mismos vínculos
$planesElim   = $conn->query("SELECT idPlan FROM plan_asignatura WHERE idAsignatura = '$idEliminar'")->fetch_all(MYSQLI_ASSOC);
$carrerasElim = $conn->query("SELECT idCarrera FROM carrera_asignatura WHERE idAsignatura = '$idEliminar'")->fetch_all(MYSQLI_ASSOC);

echo "Vínculos a reasignar a $idCorrecta:\n";
foreach ($planesElim as $p) echo "  - Plan: {$p['idPlan']}\n";
foreach ($carrerasElim as $c) echo "  - Carrera: {$c['idCarrera']}\n";

// 2. Asegurar que plan_asignatura de 2138 incluye los planes de 1667
foreach ($planesElim as $p) {
    $plan = $p['idPlan'];
    $existe = $conn->query("SELECT 1 FROM plan_asignatura WHERE idPlan = '$plan' AND idAsignatura = '$idCorrecta'")->num_rows;
    if (!$existe) {
        $conn->query("INSERT INTO plan_asignatura (idPlan, idAsignatura, tieneCorrelativa) VALUES ('$plan', '$idCorrecta', 0)");
        echo "  [AGREGADO] Plan $plan → $idCorrecta\n";
    } else {
        echo "  [YA EXISTE] Plan $plan → $idCorrecta\n";
    }
}

// 3. Asegurar que carrera_asignatura de 2138 incluye las carreras de 1667
foreach ($carrerasElim as $c) {
    $carrera = $c['idCarrera'];
    $existe = $conn->query("SELECT 1 FROM carrera_asignatura WHERE idCarrera = '$carrera' AND idAsignatura = '$idCorrecta'")->num_rows;
    if (!$existe) {
        $conn->query("INSERT INTO carrera_asignatura (idCarrera, idAsignatura) VALUES ('$carrera', '$idCorrecta')");
        echo "  [AGREGADO] Carrera $carrera → $idCorrecta\n";
    } else {
        echo "  [YA EXISTE] Carrera $carrera → $idCorrecta\n";
    }
}

// 4. Eliminar vínculos del duplicado
$conn->query("DELETE FROM plan_asignatura WHERE idAsignatura = '$idEliminar'");
echo "\nEliminados de plan_asignatura: " . $conn->affected_rows . " filas\n";

$conn->query("DELETE FROM carrera_asignatura WHERE idAsignatura = '$idEliminar'");
echo "Eliminados de carrera_asignatura: " . $conn->affected_rows . " filas\n";

// 5. Eliminar la asignatura duplicada
$conn->query("DELETE FROM asignatura WHERE id = '$idEliminar'");
echo "Eliminada asignatura $idEliminar: " . $conn->affected_rows . " fila\n";

// 6. Verificar resultado final
echo "\n=== VERIFICACIÓN FINAL ===\n";
$a = $conn->query("SELECT id, nombre FROM asignatura WHERE id IN ('$idEliminar','$idCorrecta')")->fetch_all(MYSQLI_ASSOC);
foreach($a as $row) echo "  Existe: {$row['id']} - {$row['nombre']}\n";
if (empty($a) || count($a) == 1) echo ($conn->query("SELECT 1 FROM asignatura WHERE id='$idEliminar'")->num_rows == 0 ? "  ✓ Duplicado $idEliminar eliminado correctamente\n" : "  ✗ Error al eliminar\n");

echo "\n✓ Proceso completado.\n";
