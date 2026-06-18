<?php
require_once 'modeloSistema/BDConexionSistema.Class.php';

// Ver las materias que tiene a cargo el profesor con ID 50 (Problemática Educativa)
$sql = "SELECT DISTINCT pa.idPlan, p.idCarrera, c.nombre AS carrera 
        FROM bdgef_vaspa.plan_asignatura pa 
        JOIN bdgef_vaspa.plan p ON pa.idPlan = p.id 
        JOIN bdgef_vaspa.carrera c ON p.idCarrera = c.id 
        WHERE pa.idAsignatura = '1122'";
$resultado = BDConexionSistema::getInstancia()->query($sql);
while($row = $resultado->fetch_assoc()){
    print_r($row);
}
