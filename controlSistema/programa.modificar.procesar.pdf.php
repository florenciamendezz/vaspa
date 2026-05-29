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
                // Marcar devoluciones previas como resueltas
                $conexion = BDConexionSistema::getInstancia();
                $progExistente = ProgramaPDFDetalle::obtenerPorAsignaturaYAnio($idAsignatura, $anio);
                if ($progExistente) {
                    $idPdf = intval($progExistente->getId());
                    $idLegacy = intval($progExistente->getProgramaLegacyId());
                    $sqlMarkResolved = "UPDATE programa_devoluciones SET resuelto = 1 
                                        WHERE (id_programa_pdf = {$idPdf}" . ($idLegacy ? " OR id_programa = {$idLegacy}" : "") . ") AND resuelto = 0";
                    $conexion->query($sqlMarkResolved);
                }
                echo "<script>alert('Programa actualizado correctamente.'); window.location.href = '../vista/inicio.php';</script>";
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
    header("Location: ../vista/inicio.php");
}
?>
