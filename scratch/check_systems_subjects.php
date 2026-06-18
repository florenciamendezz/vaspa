<?php
require_once 'modeloSistema/BDConexionSistema.Class.php';

// Ver las materias que pertenecen a la escuela 8 (Sistemas e Informática) y tienen profesor
$sql = "SELECT id, nombre, idProfesor, idEscuela, idDepartamento, es_institucional FROM bdgef_vaspa.asignatura WHERE idEscuela = '8'";
$resultado = BDConexionSistema::getInstancia()->query($sql);
while($row = $resultado->fetch_assoc()){
    print_r($row);
}
