<?php
include_once '../lib/ControlAcceso.Class.php';
include_once '../modeloSistema/ProgramaPDFDetalle.Class.php';
include_once '../lib/notificacionesMail/notificacionNuevoProgramaPDF.php';

if (isset($_GET['id'])) {
    $idPrograma = $_GET['id'];
    $programa = new ProgramaPDFDetalle($idPrograma);
    
    if ($programa->getId()) {
        if ($programa->enviarARevision()) {
            notificarNuevoProgramaPDF($idPrograma);
            echo "<script>alert('Programa enviado a revisión correctamente.'); window.location.href = '../vista/inicio.php';</script>";
        } else {
            echo "<script>alert('Error al enviar a revisión.'); window.history.back();</script>";
        }
    } else {
        echo "<script>alert('Programa no encontrado.'); window.history.back();</script>";
    }
} else {
    header("Location: ../vista/inicio.php");
}
?>
