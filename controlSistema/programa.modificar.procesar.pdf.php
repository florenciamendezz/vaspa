<?php
include_once '../lib/ControlAcceso.Class.php';
include_once '../modeloSistema/ProgramaPDFDetalle.Class.php';

// Validar sesion y permisos si es necesario

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $idAsignatura = $_POST['idAsignatura'];
    $anio = $_POST['anio'];
    
    // Manejo del archivo
    if (isset($_FILES['archivoPDF']) && $_FILES['archivoPDF']['error'] == 0) {
        $archivo = $_FILES['archivoPDF'];
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

        // Nombre del archivo: prg_[idAsignatura]_[anio]_[timestamp].pdf
        $nombreArchivo = "prg_{$idAsignatura}_{$anio}_" . time() . ".pdf";
        $rutaCompleta = $directorioDestino . $nombreArchivo;

        if (move_uploaded_file($archivo['tmp_name'], $rutaCompleta)) {
            // Actualizar en BD
            $programa = new ProgramaPDFDetalle();
            $resultado = $programa->actualizarArchivo($idAsignatura, $anio, $nombreArchivo);

            if ($resultado) {
                echo "<script>alert('Programa actualizado correctamente.'); window.location.href = '../vista/asignaturasDeProfesor.php';</script>";
            } else {
                echo "<script>alert('Error al actualizar en la base de datos.'); window.history.back();</script>";
            }
        } else {
            echo "<script>alert('Error al subir el archivo.'); window.history.back();</script>";
        }
    } else {
        echo "<script>alert('Debe seleccionar un archivo.'); window.history.back();</script>";
    }
} else {
    header("Location: ../vista/asignaturasDeProfesor.php");
}
?>
