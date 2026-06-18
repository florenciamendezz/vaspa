<?php
require_once 'modeloSistema/BDConexionSistema.Class.php';

// Ver las carreras vinculadas al plan_asignatura de la asignatura 1108 (CUS)
$sql = "SELECT p.id AS plan_id, c.id AS idCarrera, c.nombre AS carrera 
        FROM bdgef_vaspa.plan_asignatura pa 
        JOIN bdgef_vaspa.plan p ON pa.idPlan = p.id 
        JOIN bdgef_vaspa.carrera c ON p.idCarrera = c.id 
        WHERE pa.idAsignatura = '1108'";
$resultado = BDConexionSistema::getInstancia()->query($sql);
while($row = $resultado->fetch_assoc()){
    print_r($row);
}
