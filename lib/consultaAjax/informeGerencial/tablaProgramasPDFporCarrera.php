<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include_once '../../ControlAcceso.Class.php';
include_once '../../../modeloSistema/Carrera.Class.php';
include_once '../../../modeloSistema/Plan.Class.php';
include_once '../../../modeloSistema/Asignatura.Class.php';
include_once '../../../modeloSistema/Profesor.Class.php';
include_once '../../../modeloSistema/ProgramaPDF.Class.php';
include_once '../../../controlSistema/ManejadorProgramaPDF.php';

$print = ''; // valor a devolver
if (isset($_POST['codCarrera']) && isset($_POST['anio'])){
    $codCarrera = $_POST['codCarrera'];
    $anio = $_POST['anio'];
    $carrera = new Carrera($codCarrera);
    $plan = $carrera->getPlan($anio);
    
    $manejadorPDF = new ManejadorProgramaPDF($codCarrera, $anio);
    
    $programas = $manejadorPDF->getColeccion();
    $cantProgDisponible = 0;
    $cantProgNoDisponible = 0;
    if (is_null($plan)){
                $print = '<div class="alert alert-warning" role="alert">
                    No se encontr&oacute; el Plan de Estudio de la Carrera.
                  </div>';
                $cantProgDisponible = -1;
                $cantProgNoDisponible = -1;
    } else {
        $asignaturas = $plan->getAsignaturas();
        $print .= '<table class="table table-hover table-sm" id="tablaAsignaturas">
                        <thead>
                            <tr class="table-info">
                                <th>C&oacute;digo</th>
                                <th>Asignatura</th>
                                <th>Profesor Responsable</th>
                                <th>Programa PDF disponible</th>
                            </tr>
                        </thead>
                        <tbody>';
        
        if (is_null($asignaturas)){
             $print = '<div class="alert alert-warning" role="alert">
                    No se encontraron asignaturas para el Plan de Estudio seleccionado.
                  </div>';
            $cantProgDisponible = -1;
            $cantProgNoDisponible = -1;
        } else {
            foreach ($asignaturas as $asignatura) {
                $prof = new Profesor($asignatura->getIdProfesor());
                $estaDisponible = false;

                // 1. Verificar PDF Legacy + Aprobación en tabla programa
                if ($manejadorPDF->tieneProgramaPDF($asignatura->getId()) != ""){
                    $sqlLegacy = "SELECT id FROM programa WHERE idAsignatura = {$asignatura->getId()} AND anio = {$anio} AND aprobadoVa = 1 AND aprobadoDepto = 1";
                    $resLegacy = BDConexionSistema::getInstancia()->query($sqlLegacy);
                    if ($resLegacy && $resLegacy->num_rows > 0) {
                        $estaDisponible = true;
                    }
                }

                // 2. Verificar PDF Nuevo (programa_pdf_detalle) + Aprobación (si no se encontró legacy)
                if (!$estaDisponible) {
                    $sqlNew = "SELECT id FROM programa_pdf_detalle WHERE id_asignatura = {$asignatura->getId()} AND anio = {$anio} AND aprobado_va = 1 AND aprobado_depto = 1";
                    $resNew = BDConexionSistema::getInstancia()->query($sqlNew);
                    if ($resNew && $resNew->num_rows > 0) {
                        $estaDisponible = true;
                    }
                }

                if ($estaDisponible){
                    $print .= '<tr><td>'.$asignatura->getId().'</td>';
                    $print .= '<td>'.$asignatura->getNombre().'</td>';
                    $print .= '<td>'.$prof->getNombreCompleto().'</td>';
                    $print .= '<td class="text-success text-center">Si <span class="oi oi-check"></span></td></tr>';
                    $cantProgDisponible++;
                } else {
                    $print .= '<tr><td>'.$asignatura->getId().'</td>';
                    $print .= '<td>'.$asignatura->getNombre().'</td>';
                    $print .= '<td>'.$prof->getNombreCompleto().'</td>';
                    $print .= '<td class="text-danger text-center">No <span class="oi oi-x"></span></td></tr>';
                    $cantProgNoDisponible++;
                }
            }
            
        }
        $print .= '</tbody>';
        $print .= '</table>';
        
        $print = '<div class="row justify-content-md-center">
                                <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#myModal1">
                                    Ver Gr&aacute;fico
                                </button>
                            </div>
                            <br>'.$print;
    }
    
    
    //var_dump($programas);
    
    
} else {
            $print = '<div class="alert alert-warning" role="alert">
                    Faltan datos.
                  </div>';
}
    // --- INICIO LOGGING ---
    // Guardar log del informe generado
    include_once '../../../modeloSistema/LogInforme.Class.php';
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    $idUsuarioLog = 0;
    $emailUsuarioLog = 'Desconocido';
    if (isset($_SESSION['usuario'])) {
        $idUsuarioLog = $_SESSION['usuario']->id;
        $emailUsuarioLog = $_SESSION['usuario']->email;
    }
    
    if (isset($codCarrera) && isset($anio)) {
        $tipoInforme = "Reporte Carreras (Carrera: {$codCarrera}, Año: {$anio})";
        LogInforme::guardarLog($idUsuarioLog, $emailUsuarioLog, $tipoInforme, $print);
    }
    // --- FIN LOGGING ---
    
    echo $print;
?>
<script type="text/javascript">
google.charts.load('current', {'packages':['corechart']});
google.charts.setOnLoadCallback(drawChart);

function drawChart() {

    var data = google.visualization.arrayToDataTable([
      ['Language', 'Rating'],
      <?php
      echo "['Programas Disponibles', ".$cantProgDisponible."],";
      echo "['Programas No Disponibles', ".$cantProgNoDisponible."],";
      ?>
    ]);
    
    var options = {
        width: '100%',
        height: '100%',
        //colors: ['#28a745', '#dc3545'],
        colors: ['#6BD382', '#FF5E6C']
        //is3D: true
    };
    
    var chart = new google.visualization.PieChart(document.getElementById('piechart'));
    
    chart.draw(data, options);
    
//    function resizeHandler () {
//        chart.draw(data, options);
//    }
//    if (window.addEventListener) {
//        window.addEventListener('resize', resizeHandler, false);
//    }
//    else if (window.attachEvent) {
//        window.attachEvent('onresize', resizeHandler);
//    }
    
}
</script>