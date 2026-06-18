<?php
require_once 'modeloSistema/BDConexionSistema.Class.php';

// Ver los datos de la tabla plan para la carrera 016 (Analista de Sistemas)
$sql = "SELECT * FROM bdgef_vaspa.plan WHERE idCarrera = '016'";
$resultado = BDConexionSistema::getInstancia()->query($sql);
while($row = $resultado->fetch_assoc()){
    print_r($row);
}
