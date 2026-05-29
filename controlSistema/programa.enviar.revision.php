<?php
include_once '../lib/ControlAcceso.Class.php';
include_once '../modeloSistema/ProgramaPDFDetalle.Class.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['idPrograma'])) {
    $idPrograma = $_POST['idPrograma'];
    
    $programa = new ProgramaPDFDetalle($idPrograma);
    if (!$programa->getId()) {
        echo "<script>alert('Programa no encontrado.'); window.history.back();</script>";
        exit;
    }

    $idAsignatura = $programa->getIdAsignatura();
    
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    $rolNombre = '';
    if (isset($_SESSION['usuario']) && isset($_SESSION['usuario']->roles[0])) {
        $rolNombre = $_SESSION['usuario']->roles[0]->nombre;
    }

    // Identificar el circuito de la asignatura
    $circuito = $programa->determinarCircuito($idAsignatura);
    
    // Validar quién está enviando y que el estado sea coherente
    $esPermitido = false;
    $notificacionFn = '';

    if ($rolNombre == 'Profesor') {
        // El profesor envía el borrador inicial o devuelto
        if ($programa->getEnRevision() == 0 && $programa->getAprobadoVaFirma() != 1) {
            $esPermitido = true;
            if ($circuito == 'estandar') {
                $notificacionFn = 'notificarEnvioAEscuela';
            } else {
                $notificacionFn = 'notificarEnvioAVA';
            }
        }
    } elseif ($rolNombre == 'Director de Escuela' || $rolNombre == 'Secretario de Escuela') {
        // La escuela envía después de haber subido el PDF (aprobado_escuela = 1, en_revision = 0)
        if ($programa->getAprobadoEscuela() == 1 && $programa->getEnRevision() == 0 && $programa->getAprobadoVa() === null) {
            $esPermitido = true;
            $notificacionFn = 'notificarEnvioAVA';
        }
    } elseif ($rolNombre == 'Vinculación Académica' || $rolNombre == 'Administrador') {
        // El VA envía a Depto después de acreditar (aprobado_va = 1, en_revision = 0)
        if ($programa->getAprobadoVa() == 1 && $programa->getEnRevision() == 0 && $programa->getAprobadoDepto() === null) {
            $esPermitido = true;
            $notificacionFn = 'notificarEnvioADepto';
        }
    } elseif ($rolNombre == 'Director de Departamento') {
        // El Depto envía a firma final después de revisar nómina (aprobado_depto = 1, en_revision = 0)
        if ($programa->getAprobadoDepto() == 1 && $programa->getEnRevision() == 0 && $programa->getAprobadoVaFirma() === null) {
            $esPermitido = true;
            $notificacionFn = 'notificarEnvioAVA';
        }
    }

    if (!$esPermitido) {
        echo "<script>alert('Acción no permitida o el programa no está en el estado adecuado para ser enviado.'); window.history.back();</script>";
        exit;
    }

    // Avanzar en el circuito
    $resultado = $programa->enviarAlSiguiente();

    if ($resultado) {
        // Marcar devoluciones previas como resueltas si envía el Profesor
        if ($rolNombre == 'Profesor') {
            $idPdf = intval($programa->getId());
            $idLegacy = intval($programa->getProgramaLegacyId());
            $sqlMarkResolved = "UPDATE programa_devoluciones SET resuelto = 1 
                                WHERE (id_programa_pdf = {$idPdf}" . ($idLegacy ? " OR id_programa = {$idLegacy}" : "") . ") AND resuelto = 0";
            BDConexionSistema::getInstancia()->query($sqlMarkResolved);
        }

        // Intentar enviar notificación si existe la librería
        $mailLib = '../lib/notificacionesMail/notificacionCircuitoVaspa.php';
        if (file_exists($mailLib)) {
            include_once $mailLib;
            if (class_exists('notificacionCircuitoVaspa')) {
                $idAsignatura = $programa->getIdAsignatura();
                $anio = $programa->getAnio();
                if ($notificacionFn == 'notificarEnvioAEscuela') {
                    notificacionCircuitoVaspa::notificarEnvioAEscuela($idAsignatura, $anio);
                } elseif ($notificacionFn == 'notificarEnvioAVA') {
                    $origen = 'Escuela';
                    if ($rolNombre == 'Profesor') {
                        $origen = 'Profesor';
                    } elseif ($rolNombre == 'Director de Departamento') {
                        $origen = 'Departamento';
                    }
                    notificacionCircuitoVaspa::notificarEnvioAVA($idAsignatura, $anio, $origen);
                } elseif ($notificacionFn == 'notificarEnvioADepto') {
                    notificacionCircuitoVaspa::notificarEnvioADepto($idAsignatura, $anio);
                }
            }
        }
        
        $redirectUrl = '../vista/inicio.php';
        echo "<script>alert('Programa enviado a la siguiente etapa de revisión correctamente.'); window.location.href = '{$redirectUrl}';</script>";
    } else {
        echo "<script>alert('Error al actualizar el estado en el circuito.'); window.history.back();</script>";
    }

} else {
    header("Location: ../vista/inicio.php");
}
?>
