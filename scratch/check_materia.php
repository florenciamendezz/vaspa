<?php
require_once 'c:/xampp/htdocs/vaspa/modeloSistema/BDConexionSistema.Class.php';

try {
    $db = BDConexionSistema::getInstancia();
    
    echo "=== DATOS DE ASIGNATURA 0174 ===\n";
    $resAsig = $db->query("SELECT * FROM asignatura WHERE id = '0174'");
    if ($resAsig) {
        print_r($resAsig->fetch_assoc());
    }
    
    echo "\n=== PLANES ASOCIADOS A ASIGNATURA 0174 ===\n";
    $resPlanes = $db->query("SELECT pa.idPlan, p.anio_inicio, p.anio_fin 
                             FROM plan_asignatura pa 
                             JOIN plan p ON pa.idPlan = p.id 
                             WHERE pa.idAsignatura = '0174'");
    if ($resPlanes) {
        while ($row = $resPlanes->fetch_assoc()) {
            print_r($row);
        }
    }
    
    echo "\n=== PROFESOR SANDRA CASAS (ID: 5) ===\n";
    $resProf = $db->query("SELECT * FROM profesor WHERE id = 5");
    if ($resProf) {
        print_r($resProf->fetch_assoc());
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
