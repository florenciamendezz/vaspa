<?php
include_once '../../../lib/ControlAcceso.Class.php';
ControlAcceso::verificaLogin();
include_once '../../../modeloSistema/Carrera.Class.php';

$planesEstudio = '';
if (isset($_POST['codCarrera'])) {
    $carrera = new Carrera($_POST['codCarrera']);

    try {
        $planes = $carrera->getPlanesDeEstudio();
    } catch (Exception $e) {
        echo '<option value="-1">Error al cargar planes</option>';
        exit;
    }
    
    if (is_null($planes) || empty($planes)) {
        $planesEstudio .= '<option value="-1">Sin planes de estudio</option>';
    } else {
        $planesEstudio .= '<option value="todos" selected>Todos los Planes de la Carrera</option>';
        foreach ($planes as $plan) {
            $periodo = $plan->getPeriodo();
            $planesEstudio .= '<option value="' . htmlspecialchars($plan->getId()) . '">' . htmlspecialchars($plan->getId()) . ' (' . htmlspecialchars($periodo) . ')</option>';
        }
    }
}

echo $planesEstudio;
