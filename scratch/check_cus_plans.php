<?php
require_once 'modeloSistema/BDConexionSistema.Class.php';

// Ver los planes y carreras donde figura la materia 1108 (CUS) en la base de datos
$sql = "SELECT DISTINCT pa.idPlan, p.idCarrera, c.nombre AS carrera, p.anio_inicio, p.anio_fin 
        FROM bdgef_vaspa.plan_asignatura pa 
        JOIN bdgef_vaspa.plan p ON pa.idPlan = p.id 
        JOIN bdgef_vaspa.carrera c ON p.idCarrera = c.id 
        WHERE pa.idAsignatura = '1108'";
$resultado = BDConexionSistema::getInstancia()->query($sql);
while($row = $resultado->fetch_assoc()){
    print_r($row);
}
