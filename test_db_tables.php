<?php
include_once 'modeloSistema/BDConexionSistema.Class.php';

$sql = "SHOW TABLES";
$resultado = BDConexionSistema::getInstancia()->query($sql);

if ($resultado) {
    while ($row = $resultado->fetch_row()) {
        echo $row[0] . "\n";
    }
} else {
    echo "Error: " . BDConexionSistema::getInstancia()->error;
}
?>
