<?php
require_once 'modeloSistema/BDConexionSistema.Class.php';

// Ver los datos de los planes y carreras para la materia 1108 (Ciencia, Universidad y Sociedad) en la base de datos
$sql = "SELECT p.id AS plan_id, p.idCarrera, c.nombre AS carrera_nombre, p.anio_inicio, p.anio_fin 
        FROM bdgef_vaspa.plan_asignatura pa
        JOIN bdgef_vaspa.plan p ON pa.idPlan = p.id
        JOIN bdgef_vaspa.carrera c ON p.idCarrera = c.id
        WHERE pa.idAsignatura = '1108'";
$resultado = BDConexionSistema::getInstancia()->query($sql);
while($row = $resultado->fetch_assoc()) {
    print_r($row);
}
