<?php
require_once 'modeloSistema/BDConexionSistema.Class.php';

// Ver los datos del profesor con ID 5 para saber si está en la escuela de Sistemas
$sql = "SELECT p.*, d.nombre AS departamento 
        FROM bdgef_vaspa.profesor p
        LEFT JOIN bdgef_vaspa.departamento d ON p.idDepartamento = d.id
        WHERE p.id = '5'";
$resultado = BDConexionSistema::getInstancia()->query($sql);
print_r($resultado->fetch_assoc());
