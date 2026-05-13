<?php
include_once 'modeloSistema/BDConexionSistema.Class.php';

$sql = "DESCRIBE profesor";
$res = BDConexionSistema::getInstancia()->query($sql);

echo "<h2>Estructura de Tabla Profesor</h2>";
if ($res) {
    while ($row = $res->fetch_assoc()) {
        echo $row['Field'] . " - " . $row['Type'] . "<br>";
    }
} else {
    echo "Error al describir tabla: " . BDConexionSistema::getInstancia()->error;
}
?>
