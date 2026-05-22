<?php
include_once '../lib/ControlAcceso.Class.php';
require_once '../modeloSistema/BDConexionSistema.Class.php';
require_once '../modeloSistema/ProgramaPDFDetalle.Class.php';
require_once '../modeloSistema/Asignatura.Class.php';

// Validar permisos
ControlAcceso::requierePermiso(PermisosSistema::PERMISO_REVISAR_PROGRAMA);

if ($_SERVER["REQUEST_METHOD"] !== "POST"){
    header("location: ../vista/revisar.programas.php");
    exit;
}

$idProgramaPDF = $_POST["idPrograma"];
$programaPDF = new ProgramaPDFDetalle($idProgramaPDF);

if (!$programaPDF->getId()) {
    die("Error: Programa PDF no encontrado.");
}

$asignatura = new Asignatura($programaPDF->getIdAsignatura());
$datosAsig = "<b>{$asignatura->getNombre()} - {$asignatura->getId()}</b>";

$Usuario = $_SESSION['usuario'];
$rol = $Usuario->roles[0]->nombre;

$mailLib = '../lib/notificacionesMail/notificacionCircuitoVaspa.php';

if (isset($_POST["aprobarPrograma"])) {
    // Para VA en el segundo paso (firma final), o cualquier otra aprobación directa
    // aprobado_va_firma se pondrá en 1 si ya fue aprobado por Depto
    $esFirmaFinal = ($rol == 'Vinculación Académica' || $rol == 'Administrador') && ($programaPDF->getAprobadoDepto() == 1);
    
    if ($programaPDF->aprobar($rol)) {
        $_SESSION['mensajeRevisarPrograma'] = '<div class="alert alert-success alert-dismissible fade show text-center" role="alert">
            El programa de '.$datosAsig.' <b>fue Aprobado</b>.
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            </div>';
            
        // Si fue la firma final, se notifica la aprobación final al Profesor
        if ($esFirmaFinal) {
            if (file_exists($mailLib)) {
                include_once $mailLib;
                if (function_exists('notificarAprobacionFinal')) {
                    notificarAprobacionFinal($idProgramaPDF);
                }
            }
        }
    } else {
        $_SESSION['mensajeRevisarPrograma'] = '<div class="alert alert-danger alert-dismissible fade show text-center" role="alert">
            Ocurrio un error al intentar aprobar el programa de '.$datosAsig.'.
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            </div>';
    }
} elseif (isset($_POST["desaprobarPrograma"])) {
    $comentario = $_POST["comentario"];
    
    if ($programaPDF->desaprobar($rol, $comentario)) {
        $_SESSION['mensajeRevisarPrograma'] = '<div class="alert alert-success alert-dismissible fade show text-center" role="alert">
            El programa de '.$datosAsig.' <b>fue Desaprobado (Devuelto)</b>.
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            </div>';
            
        // Notificaciones según quién desaprobó
        if (file_exists($mailLib)) {
            include_once $mailLib;
            if ($rol == 'Director de Departamento') {
                if (function_exists('notificarDesaprobacionDepto')) {
                    notificarDesaprobacionDepto($idProgramaPDF);
                }
            } else {
                // Escuela, VA 1° paso o Admin desaprueban
                if (function_exists('notificarDesaprobacion')) {
                    notificarDesaprobacion($idProgramaPDF);
                }
            }
        }
    } else {
        $_SESSION['mensajeRevisarPrograma'] = '<div class="alert alert-danger alert-dismissible fade show text-center" role="alert">
            Ocurrio un error al intentar desaprobar el programa de '.$datosAsig.'.
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>';
    }
}

header("location: ../vista/revisar.programas.php");
?>
