<?php
require_once 'modeloSistema/BDConexionSistema.Class.php';

// Ver los datos del profesor asignado a la carrera 016 (Analista de Sistemas) en CUS (1108)
// ¿Hay alguna tabla o asignación especial para profesores y carreras además de asignatura.idProfesor?
$sql = "SELECT * FROM bdgef_vaspa.asignatura WHERE id = '1108'";
$resultado = BDConexionSistema::getInstancia()->query($sql);
print_r($resultado->fetch_assoc());
