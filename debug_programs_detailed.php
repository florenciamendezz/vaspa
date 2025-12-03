<?php
include_once 'lib/Constantes.Class.php';
include_once 'modelo/BDColeccionGenerica.Class.php';

echo "Debugging Programs Detailed...\n";

// 1. List all Careers
echo "\n--- Careers ---\n";
$query = "SELECT id, nombre FROM " . Constantes::BD_USERS . ".carrera";
$result = BDConexion::getInstancia()->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "ID: " . $row['id'] . " - " . $row['nombre'] . "\n";
    }
}

// Function to debug a specific career and year
function debugCareer($careerId, $year) {
    echo "\n--- Debugging Career: $careerId, Year: $year ---\n";

    // 2. Check Active Plan
    echo "Checking Active Plan (anio_fin is NULL)...\n";
    $query = "SELECT id, anio_inicio FROM " . Constantes::BD_USERS . ".plan WHERE idCarrera = '$careerId' AND anio_fin IS NULL";
    $result = BDConexion::getInstancia()->query($query);
    $planId = null;
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo "Found Active Plan: " . $row['id'] . " (Start: " . $row['anio_inicio'] . ")\n";
        $planId = $row['id'];
    } else {
        echo "NO Active Plan found!\n";
        return;
    }

    // 3. Check Subjects in Plan
    echo "Checking Subjects in Plan $planId...\n";
    $query = "SELECT COUNT(*) as count FROM " . Constantes::BD_USERS . ".plan_asignatura WHERE idPlan = '$planId'";
    $result = BDConexion::getInstancia()->query($query);
    $row = $result->fetch_assoc();
    echo "Subjects in Plan: " . $row['count'] . "\n";

    // 4. Check Programs linked to Subjects in this Plan for the given Year
    echo "Checking Programs for Year $year...\n";
    $query = "SELECT a.id as idAsignatura, a.nombre, p.id as idPrograma, p.anio 
              FROM " . Constantes::BD_USERS . ".plan_asignatura pa
              JOIN " . Constantes::BD_USERS . ".asignatura a ON pa.idAsignatura = a.id
              LEFT JOIN " . Constantes::BD_USERS . ".programa p ON a.id = p.idAsignatura AND p.anio = $year
              WHERE pa.idPlan = '$planId'
              LIMIT 10";
    $result = BDConexion::getInstancia()->query($query);
    if ($result) {
        echo str_pad("ID Asig", 10) . str_pad("Asignatura", 30) . str_pad("ID Prog", 10) . "Anio\n";
        while ($row = $result->fetch_assoc()) {
            echo str_pad($row['idAsignatura'], 10) . 
                 str_pad(substr($row['nombre'], 0, 28), 30) . 
                 str_pad($row['idPrograma'] ? $row['idPrograma'] : "NULL", 10) . 
                 $row['anio'] . "\n";
        }
    }
}

// Debug "Analista de Sistemas" (Assuming ID 072 based on typical data, but will verify from list)
// Also debug another one that might be missing data.
// We'll wait to see the career list first, but let's try to guess or just run for a few.

// Let's just list careers first, then we can hardcode the calls in a second step or loop through all.
// For now, I'll loop through the first 3 careers found.
$query = "SELECT id FROM " . Constantes::BD_USERS . ".carrera LIMIT 3";
$result = BDConexion::getInstancia()->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        debugCareer($row['id'], 2018); // Using 2018 as a test year
    }
}

?>
