<?php
require_once 'modeloSistema/BDConexionSistema.Class.php';

// Ver las materias asignadas al profesor con id 5 (Sandra Casas)
$sql = "SELECT id, nombre, idEscuela, idDepartamento, es_institucional FROM bdgef_vaspa.asignatura WHERE idProfesor = '5'";
$resultado = BDConexionSistema::getInstancia()->query($sql);
while($row = $resultado->fetch_assoc()){
    print_r($row);
}
