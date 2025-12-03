<?php
include_once 'modeloSistema/BDConexionSistema.Class.php';

$sql = "DESCRIBE plan_asignatura";
$resultado = BDConexionSistema::getInstancia()->query($sql);

if ($resultado) {
    while ($row = $resultado->fetch_assoc()) {
        echo $row['Field'] . "\n";
    }
} else {
    echo "Error: " . BDConexionSistema::getInstancia()->error;
}
?>
