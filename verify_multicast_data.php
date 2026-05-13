<?php
include_once 'modeloSistema/BDConexionSistema.Class.php';

$conexion = BDConexionSistema::getInstancia();

$sql = "SELECT id, nombre, idProfesor FROM asignatura WHERE nombre LIKE '%Educativa%' OR nombre LIKE '%Proble%' LIMIT 50";

$result = $conexion->query($sql);

echo "Listing matching Subjects:\n\n";

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "ID: " . $row['id'] . " | Asignatura: " . $row['nombre'] . " | ProfID: " . $row['idProfesor'] . "\n";
    }
} else {
    echo "No subjects found. Error: " . $conexion->error;
}
?>
