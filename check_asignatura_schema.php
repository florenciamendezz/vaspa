<?php
include_once 'modeloSistema/BDConexionSistema.Class.php';
$conexion = BDConexionSistema::getInstancia();
$sql = "DESCRIBE asignatura";
$result = $conexion->query($sql);
if ($result) {
    while($row = $result->fetch_assoc()) {
        echo $row['Field'] . "\n";
    }
} else {
    echo "Error: " . $conexion->error;
}
?>
