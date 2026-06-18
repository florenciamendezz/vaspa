<?php
require_once 'modeloSistema/BDConexionSistema.Class.php';

$db = BDConexionSistema::getInstancia();

// Primero ver qué planes de estudio existen para la carrera 016
echo "=== PLANES DE ESTUDIO CARRERA 016 ===\n";
$resPlanes = $db->query("SELECT * FROM plan WHERE idCarrera = '016'");
while ($p = $resPlanes->fetch_assoc()) {
    echo "Plan ID: {$p['id']}, Anio Inicio: {$p['anio_inicio']}, Anio Fin: {$p['anio_fin']}\n";
}

// Ahora ver las asignaturas asociadas a esos planes en plan_asignatura
echo "\n=== REGISTROS EN plan_asignatura PARA EL PLAN VIGENTE ===\n";
$res = $db->query("SELECT idPlan, idAsignatura, COUNT(*) as cant 
                   FROM plan_asignatura 
                   GROUP BY idPlan, idAsignatura 
                   HAVING cant > 1");
if ($res && $res->num_rows > 0) {
    echo "¡Se encontraron duplicados en plan_asignatura!\n";
    while ($row = $res->fetch_assoc()) {
        echo "Plan: {$row['idPlan']}, Asignatura: {$row['idAsignatura']}, Cantidad: {$row['cant']}\n";
    }
} else {
    echo "No hay duplicados a nivel de par (idPlan, idAsignatura) en plan_asignatura.\n";
}

// Ver cuántas filas hay en total en plan_asignatura para la carrera 016 plan vigente
echo "\n=== TODAS LAS FILAS DE plan_asignatura PARA PLAN DE CARRERA 016 ===\n";
$resAll = $db->query("SELECT pa.*, a.nombre 
                      FROM plan_asignatura pa 
                      JOIN plan p ON pa.idPlan = p.id
                      JOIN asignatura a ON pa.idAsignatura = a.id
                      WHERE p.idCarrera = '016'");
echo "Total filas: " . $resAll->num_rows . "\n";
$i = 0;
while ($row = $resAll->fetch_assoc()) {
    if ($i < 10) {
        echo "Plan: {$row['idPlan']}, Asignatura: {$row['idAsignatura']} ({$row['nombre']})\n";
    }
    $i++;
}
if ($i >= 10) {
    echo "... y " . ($i - 10) . " filas más.\n";
}
?>
