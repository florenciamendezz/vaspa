<?php
include_once '../lib/ControlAcceso.Class.php';
include_once '../modeloSistema/ProgramaPDFDetalle.Class.php';
include_once '../modeloSistema/BDConexionSistema.Class.php';

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
            
            // Intentar notificar por mail si la clase existe
            if (file_exists('../lib/notificacionesMail/notificacionCircuitoVaspa.php')) {
                include_once '../lib/notificacionesMail/notificacionCircuitoVaspa.php';
                if (class_exists('notificacionCircuitoVaspa')) {
                    
                    // 1. Obtener emails de los profesores responsables
                    $conexion = BDConexionSistema::getInstancia();
                    $sqlProf = "SELECT p.email FROM profesor p 
                                JOIN asignatura_responsable ar ON p.id = ar.idProfesor 
                                WHERE ar.idAsignatura = '" . $conexion->real_escape_string($idAsignatura) . "'";
                    $resProf = $conexion->query($sqlProf);
                    $emailsDocentes = [];
                    if ($resProf && $resProf->num_rows > 0) {
                        while ($rowProf = $resProf->fetch_assoc()) {
                            if (!empty($rowProf['email'])) {
                                $emailsDocentes[] = $rowProf['email'];
                            }
                        }
                    }
                    
                    // 2. Determinar email del revisor actual
                    $emailRevisor = "";
                    $rolRevisor = "";
                    
                    if ($programaPdf->getAprobadoEscuela() === null) {
                        // Está en manos de Escuela. Obtener email dinámico de Escuela (rol 12)
                        $sqlEsc = "SELECT u.email FROM usuario u 
                                   JOIN usuario_rol ur ON u.id = ur.id_usuario 
                                   WHERE ur.id_rol = 12 LIMIT 1";
                        $resEsc = $conexion->query($sqlEsc);
                        if ($resEsc && $resEsc->num_rows > 0) {
                            $rowEsc = $resEsc->fetch_assoc();
                            $emailRevisor = $rowEsc['email'];
                            $rolRevisor = "Director de Escuela";
                        }
                    } elseif ($programaPdf->getAprobadoVa() === null) {
                        // Está en manos de VA (primer paso)
                        $rolRevisor = "Vinculación Académica";
                    } elseif ($programaPdf->getAprobadoDepto() === null) {
                        // Está en manos de Departamento.
                        // Obtener el departamento de la asignatura para saber a qué email enviar
                        $sqlAsigDepto = "SELECT idDepartamento FROM asignatura WHERE id = '" . $conexion->real_escape_string($idAsignatura) . "'";
                        $resAsigDepto = $conexion->query($sqlAsigDepto);
                        if ($resAsigDepto && $resAsigDepto->num_rows > 0) {
                            $rowAD = $resAsigDepto->fetch_assoc();
                            $idDepto = $rowAD['idDepartamento'];
                            
                            include_once '../lib/funcionesUtiles/constantesMail.php';
                            $deptoCNE = ['6', '7', '8', '11'];
                            if (in_array($idDepto, $deptoCNE)) {
                                $emailRevisor = MAIL_DEPTO_CNE;
                            } else {
                                $emailRevisor = MAIL_DEPTO_CS;
                            }
                            $rolRevisor = "Director de Departamento";
                        }
                    } elseif ($programaPdf->getAprobadoVaFirma() === null) {
                        // Está en manos de VA (firma final)
                        $rolRevisor = "Vinculación Académica (Firma Final)";
                    }
                    
                    // Llamamos a la función de notificación para cada uno de los responsables
                    if (!empty($emailsDocentes)) {
                        foreach ($emailsDocentes as $emailDocente) {
                            notificacionCircuitoVaspa::notificarAvisoManual($idAsignatura, $anio, $emailDocente, $emailRevisor, $rolRevisor);
                        }
                    }
                }
            }
            
            header("Location: ../vista/monitoreo.circuito.php?mensaje=aviso_enviado");
            exit();
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
