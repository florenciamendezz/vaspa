<?php
require_once 'modeloSistema/BDConexionSistema.Class.php';

// Ver los datos del profesor asignado a la materia 1108 (CUS)
$sql = "SELECT id, nombre, idProfesor, idEscuela, idDepartamento FROM bdgef_vaspa.asignatura WHERE id = '1108'";
$resultado = BDConexionSistema::getInstancia()->query($sql);
print_r($resultado->fetch_assoc());
