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

if (isset($_POST["guardarComentarioAvanzado"])) {
    $comentario = $_POST["comentario"];
    $accionEstado = $_POST["accionEstado"]; // 'observacion', 'aprobar', 'desaprobar'
    $habilitarReentrega = isset($_POST["habilitarReentrega"]) ? 1 : 0;
    
    $idUsuario = intval($Usuario->id);
    $comentarioEscaped = BDConexionSistema::getInstancia()->real_escape_string($comentario);
    $rolEscaped = BDConexionSistema::getInstancia()->real_escape_string($rol);
    $idLegacyVal = $programaPDF->getProgramaLegacyId();
    
    // 2. Procesar acción sobre el estado
    $reentregaRealizada = false;
    
    if ($accionEstado == 'aprobar') {
        // Insertar comentario en historial (el método aprobar() no lo hace)
        $sqlDevolucion = "INSERT INTO programa_devoluciones (id_programa, id_programa_pdf, id_usuario, rol_revisor, fecha, comentario, leido, resuelto) 
                          VALUES (" . ($idLegacyVal ? $idLegacyVal : "NULL") . ", {$idProgramaPDF}, {$idUsuario}, '{$rolEscaped}', NOW(), '{$comentarioEscaped}', 0, 0)";
        BDConexionSistema::getInstancia()->query($sqlDevolucion);
        
        $programaPDF->aprobar($rol);
        if ($rol == 'Secretario de Escuela' || $rol == 'Director de Escuela') {
            $sqlUp = "UPDATE programa_pdf_detalle SET aprobado_escuela = 1 WHERE id = {$idProgramaPDF}";
            BDConexionSistema::getInstancia()->query($sqlUp);
            if ($idLegacyVal) {
                $sqlUpLegacy = "UPDATE programa SET aprobadoEscuela = 1, comentarioEscuela = '{$comentarioEscaped}' WHERE id = {$idLegacyVal}";
                BDConexionSistema::getInstancia()->query($sqlUpLegacy);
            }
        } elseif ($rol == 'Vinculación Académica' || $rol == 'Administrador') {
            $sqlUp = "UPDATE programa_pdf_detalle SET aprobado_va = 1 WHERE id = {$idProgramaPDF}";
            BDConexionSistema::getInstancia()->query($sqlUp);
            if ($idLegacyVal) {
                $sqlUpLegacy = "UPDATE programa SET aprobadoVa = 1, comentarioVa = '{$comentarioEscaped}' WHERE id = {$idLegacyVal}";
                BDConexionSistema::getInstancia()->query($sqlUpLegacy);
            }
        }
    } elseif ($accionEstado == 'desaprobar') {
        // El método desaprobar() ya inserta en programa_devoluciones internamente,
        // por eso NO se hace un INSERT manual aquí para evitar duplicados.
        $programaPDF->desaprobar($rol, $comentario);
        $habilitarReentrega = 1; // Obligatorio al desaprobar
    } else {
        // Solo registrar comentario (observacion): insertar en historial
        $sqlDevolucion = "INSERT INTO programa_devoluciones (id_programa, id_programa_pdf, id_usuario, rol_revisor, fecha, comentario, leido, resuelto) 
                          VALUES (" . ($idLegacyVal ? $idLegacyVal : "NULL") . ", {$idProgramaPDF}, {$idUsuario}, '{$rolEscaped}', NOW(), '{$comentarioEscaped}', 0, 0)";
        BDConexionSistema::getInstancia()->query($sqlDevolucion);
        
        if ($rol == 'Secretario de Escuela' || $rol == 'Director de Escuela') {
            if ($idLegacyVal) {
                $sqlUp = "UPDATE programa SET comentarioEscuela = '{$comentarioEscaped}' WHERE id = {$idLegacyVal}";
                BDConexionSistema::getInstancia()->query($sqlUp);
            }
        } elseif ($rol == 'Vinculación Académica' || $rol == 'Administrador') {
            if ($idLegacyVal) {
                $sqlUp = "UPDATE programa SET comentarioVa = '{$comentarioEscaped}' WHERE id = {$idLegacyVal}";
                BDConexionSistema::getInstancia()->query($sqlUp);
            }
        }
    }
    
    // 3. Procesar reentrega si corresponde
    if ($habilitarReentrega == 1) {
        $programaPDF->resetearParaReentrega();
        $reentregaRealizada = true;
    }
    
    // 4. Enviar notificación por correo electrónico
    if (file_exists($mailLib)) {
        include_once $mailLib;
        if (class_exists('notificacionCircuitoVaspa')) {
            $idAsignatura = $programaPDF->getIdAsignatura();
            $anio = $programaPDF->getAnio();
            $profesor = new Profesor($asignatura->getIdProfesor());
            $emailDocente = $profesor->getEmail();
            
            notificacionCircuitoVaspa::notificarComentarioDocente($idAsignatura, $anio, $emailDocente, $rol, $accionEstado, $comentario, $reentregaRealizada);
        }
    }
    
    $_SESSION['mensajeRevisarPrograma'] = '<div class="alert alert-success alert-dismissible fade show text-center" role="alert">
        La revisión de '.$datosAsig.' fue guardada con éxito y se notificó al docente responsable.
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
        </div>';
        
    header("location: ../vista/inicio.php");
    exit;
}

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
                if (class_exists('notificacionCircuitoVaspa')) {
                    $idAsignatura = $programaPDF->getIdAsignatura();
                    $anio = $programaPDF->getAnio();
                    $profesor = new Profesor($asignatura->getIdProfesor());
                    $emailDocente = $profesor->getEmail();
                    notificacionCircuitoVaspa::notificarAprobacionFinal($idAsignatura, $anio, $emailDocente, $idProgramaPDF);
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
            if (class_exists('notificacionCircuitoVaspa')) {
                $idAsignatura = $programaPDF->getIdAsignatura();
                $anio = $programaPDF->getAnio();
                if ($rol == 'Director de Departamento') {
                    notificacionCircuitoVaspa::notificarDesaprobacionDepto($idAsignatura, $anio, $comentario);
                } else {
                    // Escuela, VA 1° paso o Admin desaprueban
                    $profesor = new Profesor($asignatura->getIdProfesor());
                    $emailDocente = $profesor->getEmail();
                    notificacionCircuitoVaspa::notificarDesaprobacion($idAsignatura, $anio, $emailDocente, $comentario);
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

header("location: ../vista/inicio.php");
?>
