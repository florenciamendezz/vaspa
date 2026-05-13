<?php
include_once 'modeloSistema/BDConexionSistema.Class.php';

$sql = "SELECT id FROM asignatura LIMIT 1";
$res = BDConexionSistema::getInstancia()->query($sql);

if ($res && $res->num_rows > 0) {
    $row = $res->fetch_assoc();
    echo "Valid ID: " . $row['id'];
} else {
    echo "No asignaturas found.";
}
?>
