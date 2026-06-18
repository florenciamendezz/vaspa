<?php
require_once 'modeloSistema/BDConexionSistema.Class.php';

// Ver las escuelas que existen en la BD
$sql = "SELECT * FROM bdgef_vaspa.escuela";
$resultado = BDConexionSistema::getInstancia()->query($sql);
while($row = $resultado->fetch_assoc()){
    print_r($row);
}
