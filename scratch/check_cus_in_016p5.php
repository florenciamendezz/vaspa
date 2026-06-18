<?php
require_once 'modeloSistema/BDConexionSistema.Class.php';

// Ver los registros en plan_asignatura para la carrera 016 (Analista de Sistemas) y plan 016P5 (Vigente)
$sql = "SELECT pa.idAsignatura, a.nombre, a.idEscuela, a.es_institucional 
        FROM bdgef_vaspa.plan_asignatura pa 
        JOIN bdgef_vaspa.asignatura a ON pa.idAsignatura = a.id
        WHERE pa.idPlan = '016P5' AND pa.idAsignatura = '1108'";
$resultado = BDConexionSistema::getInstancia()->query($sql);
print_r($resultado->fetch_assoc());
