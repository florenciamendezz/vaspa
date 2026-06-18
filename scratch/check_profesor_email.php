<?php
require_once 'modeloSistema/BDConexionSistema.Class.php';

// Ver con qué email está logueado el profesor id = 5
$sql = "SELECT id, email, nombre, apellido FROM bdgef_vaspa.profesor WHERE id = '5'";
$resultado = BDConexionSistema::getInstancia()->query($sql);
print_r($resultado->fetch_assoc());
