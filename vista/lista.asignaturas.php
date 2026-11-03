<?php

$data = json_decode($_POST['vectorElementos']);

require_once __DIR__ . '/../lib/ControlAcceso.Class.php';
ControlAcceso::iniciarSesionSegura();

$_SESSION['vectorElementos'] = $data;
header("location: plan.asignaturas.procesar.php");

?>