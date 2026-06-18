<?php
require_once 'modeloSistema/BDConexionSistema.Class.php';

// Ver la estructura de la tabla carrera para saber qué campos tiene (idEscuela, idDepartamento, etc.)
$sql = "DESCRIBE bdgef_vaspa.carrera";
$resultado = BDConexionSistema::getInstancia()->query($sql);
while($row = $resultado->fetch_assoc()){
    print_r($row);
}
