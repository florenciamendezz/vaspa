<?php
include_once '../lib/ControlAcceso.Class.php';
require_once '../modeloSistema/BDConexionSistema.Class.php';
require_once '../modeloSistema/ProgramaPDFDetalle.Class.php';
require_once '../modeloSistema/Asignatura.Class.php';

// Validar permisos (mismos que para revisar programa legacy)
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

// Helper function to check if both authorities have reviewed
function fueRevisadoPorSAyDpto($programaPDF) {
    // comprobamos que los campos aprobados tanto en SA como en Dpto no sean nulos
    // Nota: getAprobadoVa() devuelve '0', '1' o NULL.
    if (!is_null($programaPDF->getAprobadoVa()) && !is_null($programaPDF->getAprobadoDepto())){
        return TRUE;
    } else {
        return FALSE;
    }
}

if (isset($_POST["aprobarPrograma"])) {
    if ($programaPDF->aprobar($rol)) {
        $_SESSION['mensajeRevisarPrograma'] = '<div class="alert alert-success alert-dismissible fade show text-center" role="alert">
            El programa de '.$datosAsig.' <b>fue Aprobado</b>.
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            </div>';
            
        // Recargamos el objeto para tener los datos actualizados
        $programaPDF = new ProgramaPDFDetalle($idProgramaPDF);
        
        // Chequeamos si fue revisado por ambas autoridades para enviar el email
        if (fueRevisadoPorSAyDpto($programaPDF)){
            include_once '../lib/notificacionesMail/notificacionProgramaAprobadoDesaprobado.php';
            $idLegacy = $programaPDF->getProgramaLegacyId();
            if ($idLegacy) {
                enviarNotificacionProfesor($idLegacy); // enviamos el mail con ID legacy
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
            El programa de '.$datosAsig.' <b>fue Desaprobado</b>.
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            </div>';
            
        // Recargamos el objeto para tener los datos actualizados
        $programaPDF = new ProgramaPDFDetalle($idProgramaPDF);

        // Chequeamos si fue revisado por ambas autoridades para enviar el email
        // O SI FUE DESAPROBADO (si fueDesaprobado es 1, se envia mail de rechazo)
        if (fueRevisadoPorSAyDpto($programaPDF) || $programaPDF->getFueDesaprobado() == 1){
            include_once '../lib/notificacionesMail/notificacionProgramaAprobadoDesaprobado.php';
            $idLegacy = $programaPDF->getProgramaLegacyId();
            if ($idLegacy) {
                enviarNotificacionProfesor($idLegacy); // enviamos el mail con ID legacy
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
