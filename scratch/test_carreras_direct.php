<?php
require_once 'modeloSistema/BDConexionSistema.Class.php';

// Ver los datos de la asignatura 1108 (CUS) en la tabla plan_asignatura y carrera
$sql = "SELECT c.id, c.nombre 
        FROM bdgef_vaspa.asignatura a 
        JOIN bdgef_vaspa.plan_asignatura pa ON a.id = pa.idAsignatura 
        JOIN bdgef_vaspa.plan p ON pa.idPlan = p.id 
        JOIN bdgef_vaspa.carrera c ON p.idCarrera = c.id 
        WHERE a.id = '1108'";
$resultado = BDConexionSistema::getInstancia()->query($sql);
while($row = $resultado->fetch_assoc()) {
    print_r($row);
}
