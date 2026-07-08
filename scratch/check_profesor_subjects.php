<?php
require_once 'modeloSistema/BDConexionSistema.Class.php';

// Ver las materias que corresponden al profesor 5 en planes vigentes
$sql = "SELECT DISTINCT a.id, a.nombre, a.idEscuela, c.id AS idCarrera, c.nombre AS carrera 
        FROM bdgef_vaspa.profesor p 
        INNER JOIN bdgef_vaspa.asignatura a ON p.id = a.idProfesor 
        INNER JOIN bdgef_vaspa.plan_asignatura pa ON a.id = pa.idAsignatura
        INNER JOIN bdgef_vaspa.plan pl ON pa.idPlan = pl.id
        INNER JOIN bdgef_vaspa.carrera c ON pl.idCarrera = c.id
        WHERE p.id = '5' AND (pl.anio_fin IS NULL OR pl.anio_fin >= 2026)";
$resultado = BDConexionSistema::getInstancia()->query($sql);
while ($row = $resultado->fetch_assoc()) {
    print_r($row);
}
