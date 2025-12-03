<?php
include_once '../lib/ControlAcceso.Class.php';
include_once '../modeloSistema/ProgramaPDFDetalle.Class.php';

// Validar sesion y permisos si es necesario (asumimos que ControlAcceso lo hace o se maneja en la vista)

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $idAsignatura = $_POST['idAsignatura'];
    $anio = $_POST['anio'];
    $vigencia = isset($_POST['vigencia']) ? $_POST['vigencia'] : 1; // Default to 1 year if missing
    
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
            // Guardar en BD
            $programa = new ProgramaPDFDetalle();
            // El metodo crear ahora se encarga de insertar en ambas tablas (programa y programa_pdf_detalle)
            $resultado = $programa->crear($idAsignatura, $anio, $vigencia, $nombreArchivo);

            if ($resultado) {
                echo "<script>alert('Programa cargado correctamente.'); window.location.href = '../vista/asignaturasDeProfesor.php';</script>";
            } else {
                echo "<script>alert('Error al guardar en la base de datos.'); window.history.back();</script>";
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
