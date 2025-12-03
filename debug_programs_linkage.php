<?php
include_once 'lib/Constantes.Class.php';
include_once 'modelo/BDColeccionGenerica.Class.php';

echo "Debugging Program Linkages for 2018...\n";

// 1. Get all programs for 2018
$query = "SELECT p.id as idPrograma, p.idAsignatura, a.nombre as asignatura 
          FROM " . Constantes::BD_USERS . ".programa p
          JOIN " . Constantes::BD_USERS . ".asignatura a ON p.idAsignatura = a.id
          WHERE p.anio = 2018";
$result = BDConexion::getInstancia()->query($query);

if ($result) {
    echo str_pad("Prog ID", 10) . str_pad("Subj ID", 10) . str_pad("Asignatura", 30) . "Linked to Active Plans?\n";
    while ($row = $result->fetch_assoc()) {
        $progId = $row['idPrograma'];
        $subjId = $row['idAsignatura'];
        $subjName = substr($row['asignatura'], 0, 28);
        
        // Check which ACTIVE plans contain this subject
        $queryPlans = "SELECT pl.id, pl.idCarrera, c.nombre as carrera
                       FROM " . Constantes::BD_USERS . ".plan_asignatura pa
                       JOIN " . Constantes::BD_USERS . ".plan pl ON pa.idPlan = pl.id
                       JOIN " . Constantes::BD_USERS . ".carrera c ON pl.idCarrera = c.id
                       WHERE pa.idAsignatura = '$subjId' AND pl.anio_fin IS NULL";
        
        $resultPlans = BDConexion::getInstancia()->query($queryPlans);
        $linkedPlans = [];
        if ($resultPlans && $resultPlans->num_rows > 0) {
            while ($rowPlan = $resultPlans->fetch_assoc()) {
                $linkedPlans[] = $rowPlan['carrera'] . " (" . $rowPlan['id'] . ")";
            }
            $status = "YES: " . implode(", ", $linkedPlans);
        } else {
            $status = "NO ACTIVE PLAN FOUND";
        }
        
        echo str_pad($progId, 10) . str_pad($subjId, 10) . str_pad($subjName, 30) . $status . "\n";
    }
}
?>
