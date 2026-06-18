<?php
require_once 'modeloSistema/BDConexionSistema.Class.php';
$sql = "SELECT a.id, a.nombre, c.id AS idCarrera, c.nombre AS carrera 
        FROM bdgef_vaspa.asignatura a 
        LEFT JOIN bdgef_vaspa.plan_asignatura pa ON a.id = pa.idAsignatura 
        LEFT JOIN bdgef_vaspa.plan p ON pa.idPlan = p.id 
        LEFT JOIN bdgef_vaspa.carrera c ON p.idCarrera = c.id 
        WHERE a.nombre LIKE '%ciencia%' OR a.nombre LIKE '%sociedad%' OR a.nombre LIKE '%universidad%';";
$resultado = BDConexionSistema::getInstancia()->query($sql);
while ($row = $resultado->fetch_assoc()) {
    print_r($row);
}
