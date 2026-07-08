<?php
require_once 'modeloSistema/BDConexionSistema.Class.php';

// Ver las materias institucionales de la base de datos
$sql = "SELECT id, nombre, idProfesor, idEscuela, es_institucional FROM bdgef_vaspa.asignatura WHERE es_institucional = 1";
$resultado = BDConexionSistema::getInstancia()->query($sql);
while($row = $resultado->fetch_assoc()){
    print_r($row);
}
