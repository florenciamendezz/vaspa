<?php
require_once 'modeloSistema/BDConexionSistema.Class.php';

// Ver los campos de la tabla plan
$sql = "DESCRIBE bdgef_vaspa.plan";
$resultado = BDConexionSistema::getInstancia()->query($sql);
while($row = $resultado->fetch_assoc()){
    print_r($row);
}
