<?php
require_once 'modeloSistema/BDConexionSistema.Class.php';

// Ver los datos de la asignatura 1108 (CUS) y cómo se vincula a escuela / departamento
$sql = "SELECT * FROM bdgef_vaspa.asignatura WHERE id = '1108'";
$resultado = BDConexionSistema::getInstancia()->query($sql);
print_r($resultado->fetch_assoc());
