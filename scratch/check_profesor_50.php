<?php
require_once 'modeloSistema/BDConexionSistema.Class.php';

// Ver con qué email está registrado el profesor 50 en la base de datos
$sql = "SELECT id, email, nombre, apellido FROM bdgef_vaspa.profesor WHERE id = '50'";
$resultado = BDConexionSistema::getInstancia()->query($sql);
print_r($resultado->fetch_assoc());
