<?php
include_once 'modeloSistema/BDConexionSistema.Class.php';

$sql = "DESCRIBE programa";
$res = BDConexionSistema::getInstancia()->query($sql);

echo "<h2>Estructura de Tabla Programa (Con Null)</h2>";
if ($res) {
    while ($row = $res->fetch_assoc()) {
        echo $row['Field'] . " - " . $row['Type'] . " - Null: " . $row['Null'] . "<br>";
    }
} else {
    echo "Error al describir tabla: " . BDConexionSistema::getInstancia()->error;
}
?>
