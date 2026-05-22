<?php
include_once '../lib/ControlAcceso.Class.php';
include_once '../modeloSistema/ProgramaPDFDetalle.Class.php';

// Validar que el usuario sea Admin o Vinculación Académica
$UsuarioSes = $_SESSION['usuario'];
$perfil = "";
if (isset($UsuarioSes->roles[0])) {
    $perfil = $UsuarioSes->roles[0]->nombre;
}
if ($perfil !== PermisosSistema::ROL_ADMIN && $perfil !== PermisosSistema::ROL_VINCULACION_ACADEMICA) {
    header("Location: ../vista/panelVA.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $idProgramaPDF = isset($_POST['idProgramaPDF']) ? intval($_POST['idProgramaPDF']) : 0;
    
    if ($idProgramaPDF > 0) {
        $programaPdf = new ProgramaPDFDetalle($idProgramaPDF);
        
        if ($programaPdf->getId() > 0) {
            $idAsignatura = $programaPdf->getIdAsignatura();
            $anio = $programaPdf->getAnio();
            
            if ($programaPdf->resetearParaReentrega()) {
                
                // Intentar notificar por mail (M11) si la clase existe
                if (file_exists('../lib/notificacionesMail/notificacionCircuitoVaspa.php')) {
                    include_once '../lib/notificacionesMail/notificacionCircuitoVaspa.php';
                    if (class_exists('notificacionCircuitoVaspa')) {
                        // Obtener el email del profesor responsable
                        $conexion = BDConexionSistema::getInstancia();
                        $sqlProf = "SELECT p.email FROM profesor p 
                                    JOIN asignatura a ON p.id = a.idProfesor 
                                    WHERE a.id = '" . $conexion->real_escape_string($idAsignatura) . "'";
                        $resProf = $conexion->query($sqlProf);
                        $emailDocente = "";
                        if ($resProf && $resProf->num_rows > 0) {
                            $rowProf = $resProf->fetch_assoc();
                            $emailDocente = $rowProf['email'];
                        }
                        
                        if (!empty($emailDocente)) {
                            notificacionCircuitoVaspa::notificarReentregaHabilitada($idAsignatura, $anio, $emailDocente);
                        }
                    }
                }
                
                header("Location: ../vista/monitoreo.circuito.php?mensaje=reentrega_habilitada");
                exit();
            } else {
                header("Location: ../vista/monitoreo.circuito.php?error=No se pudo resetear el programa para reentrega");
                exit();
            }
        } else {
            header("Location: ../vista/monitoreo.circuito.php?error=Programa no encontrado");
            exit();
        }
    } else {
        header("Location: ../vista/monitoreo.circuito.php?error=ID de programa inválido");
        exit();
    }
} else {
    header("Location: ../vista/monitoreo.circuito.php");
    exit();
}
?>
