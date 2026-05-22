<?php
include_once '../lib/ControlAcceso.Class.php';
include_once '../modeloSistema/ProgramaPDFDetalle.Class.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['idPrograma']) && isset($_FILES['archivoPdf'])) {
    
    $idPrograma = $_POST['idPrograma'];
    $archivo = $_FILES['archivoPdf'];
    
    $programa = new ProgramaPDFDetalle($idPrograma);
    if (!$programa->getId()) {
        echo "<script>alert('Programa no encontrado.'); window.history.back();</script>";
        exit;
    }

    $idAsignatura = $programa->getIdAsignatura();
    $anio = $programa->getAnio();
    
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    $rolNombre = '';
    if (isset($_SESSION['usuario']) && isset($_SESSION['usuario']->roles[0])) {
        $rolNombre = $_SESSION['usuario']->roles[0]->nombre;
    }

    if (!in_array($rolNombre, ['Vinculación Académica', 'Administrador', 'Director de Departamento', 'Director de Escuela', 'Secretario de Escuela'])) {
        echo "<script>alert('No tenés permisos para realizar esta acción.'); window.history.back();</script>";
        exit;
    }

    // Si el rol es VA pero el programa ya está listo para firma final (aprobadoDepto == 1), no se debe subir PDF aquí.
    if (($rolNombre == 'Vinculación Académica' || $rolNombre == 'Administrador') && $programa->getAprobadoDepto() == 1) {
        echo "<script>alert('En la etapa de firma final no se requiere subir un nuevo PDF.'); window.history.back();</script>";
        exit;
    }

    // Determinar slug para el nombre de archivo
    $rolSlug = 'Revisor';
    if ($rolNombre == 'Vinculación Académica' || $rolNombre == 'Administrador') {
        $rolSlug = 'VA';
    } elseif ($rolNombre == 'Director de Departamento') {
        $rolSlug = 'Depto';
    } elseif ($rolNombre == 'Director de Escuela' || $rolNombre == 'Secretario de Escuela') {
        $rolSlug = 'Escuela';
    }

    // Manejo del archivo
    if ($archivo['error'] == 0) {
        $extension = pathinfo($archivo['name'], PATHINFO_EXTENSION);
        
        if (strtolower($extension) != 'pdf') {
            echo "<script>alert('Solo se permiten archivos PDF.'); window.history.back();</script>";
            exit;
        }
        
        // Crear directorio si no existe
        $directorioDestino = '../archivos/programas/';
        if (!file_exists($directorioDestino)) {
            mkdir($directorioDestino, 0777, true);
        }
        
        // Nombre del archivo: prg_[idAsignatura]_[anio]_firmado-[rol]_[timestamp].pdf
        $nombreArchivo = "prg_{$idAsignatura}_{$anio}_firmado-{$rolSlug}_" . time() . ".pdf";
        $rutaCompleta = $directorioDestino . $nombreArchivo;
        
        if (move_uploaded_file($archivo['tmp_name'], $rutaCompleta)) {
            // Guardar en base de datos usando el método del modelo
            $res = $programa->revisorSubePdfFirmado($rolNombre, $nombreArchivo);
            
            if ($res) {
                $redirectUrl = '../vista/revisar.programas.php';
                echo "<script>alert('Programa firmado cargado correctamente como borrador de revisión. Presioná \"Enviar al siguiente\" para avanzar en el circuito.'); window.location.href = '{$redirectUrl}';</script>";
            } else {
                echo "<script>alert('Error al actualizar la base de datos.'); window.history.back();</script>";
            }
        } else {
            echo "<script>alert('Error al subir el archivo.'); window.history.back();</script>";
        }
    } else {
        echo "<script>alert('Debe seleccionar un archivo válido.'); window.history.back();</script>";
    }
    
} else {
    header("Location: ../vista/revisar.programas.php");
}
?>
