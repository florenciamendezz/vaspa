<?php
include_once 'lib/Constantes.Class.php';
include_once 'modelo/BDColeccionGenerica.Class.php';

echo "Debugging Plans for Careers...\n";

// Function to list plans for a career
function listPlans($careerId) {
    echo "\n--- Plans for Career: $careerId ---\n";
    $query = "SELECT id, anio_inicio, anio_fin FROM " . Constantes::BD_USERS . ".plan WHERE idCarrera = '$careerId'";
    $result = BDConexion::getInstancia()->query($query);
    if ($result) {
        echo str_pad("Plan ID", 10) . str_pad("Start", 6) . "End\n";
        while ($row = $result->fetch_assoc()) {
            echo str_pad($row['id'], 10) . 
                 str_pad($row['anio_inicio'], 6) . 
                 ($row['anio_fin'] ? $row['anio_fin'] : "NULL") . "\n";
        }
    }
}

// Check "Analista de Sistemas" (016) and "Licenciatura en Sistemas" (072)
listPlans('016');
listPlans('072');
// Check one that might have changed recently, e.g. 001
listPlans('001');

?>
