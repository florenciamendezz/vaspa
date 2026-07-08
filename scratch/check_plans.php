<?php
require_once 'modeloSistema/BDConexionSistema.Class.php';

// Ver los planes activos en el año actual (2026) para la materia 1108 (CUS) en la carrera 016 (Analista de Sistemas)
$sql = "SELECT p.id, p.idCarrera, p.anio_inicio, p.anio_fin 
        FROM bdgef_vaspa.plan_asignatura pa
        JOIN bdgef_vaspa.plan p ON pa.idPlan = p.id
        WHERE pa.idAsignatura = '1108' AND p.idCarrera = '016'";
$resultado = BDConexionSistema::getInstancia()->query($sql);
while ($row = $resultado->fetch_assoc()) {
    print_r($row);
}
