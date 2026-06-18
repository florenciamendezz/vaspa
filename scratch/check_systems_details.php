<?php
require_once 'modeloSistema/BDConexionSistema.Class.php';

// Ver las materias que tiene la escuela de sistemas 8
$sql = "SELECT id, nombre, idProfesor, idEscuela, idDepartamento FROM bdgef_vaspa.asignatura WHERE idEscuela = '8'";
$resultado = BDConexionSistema::getInstancia()->query($sql);
while($row = $resultado->fetch_assoc()){
    print_r($row);
}
