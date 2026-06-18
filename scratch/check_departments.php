<?php
require_once 'modeloSistema/BDConexionSistema.Class.php';

// Ver los departamentos que existen en la base de datos
$sql = "SELECT * FROM bdgef_vaspa.departamento";
$resultado = BDConexionSistema::getInstancia()->query($sql);
while($row = $resultado->fetch_assoc()){
    print_r($row);
}
