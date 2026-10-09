<?php
error_reporting(0);

// Actualiza la lista Carreras de acuerdo al anio seleccionado
include_once __DIR__ . '/../../modeloSistema/BDConexionSistema.Class.php';
include_once __DIR__ . '/../../modeloSistema/Plan.Class.php';
include_once __DIR__ . '/../../controlSistema/ManejadorPlan.php';
include_once __DIR__ . '/../../modeloSistema/Carrera.Class.php';

$anio = isset($_POST['anio']) ? intval($_POST['anio']) : 0;
$manejadorPlan = new ManejadorPlan();
$planes = $manejadorPlan->getColeccion();

$carreras = '';
$carrerasAgregadas = [];

if (!empty($planes)) {
    foreach ($planes as $plan){
        // Comprobamos si el plan está vigente para el año
        if (($plan->getAnio_inicio() <= $anio && $plan->getAnio_fin() >= $anio) || ($plan->getAnio_inicio() <= $anio && is_null($plan->getAnio_fin()))){
            $idCarrera = $plan->getIdCarrera();
            if (!in_array($idCarrera, $carrerasAgregadas)) {
                $carrera = new Carrera($idCarrera, NULL);
                if ($carrera && $carrera->getId()) {
                    $carreras .= '<option value="'.$carrera->getId().'">'.$carrera->getId().' - '.$carrera->getNombre().'</option>';
                    $carrerasAgregadas[] = $idCarrera;
                }
            }
        }
    }
}

echo $carreras;