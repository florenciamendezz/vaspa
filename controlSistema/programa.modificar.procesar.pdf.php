<?php
include_once __DIR__ . '/../lib/ControlAcceso.Class.php';
ControlAcceso::verificaLogin();
ControlAcceso::requiereCsrf();
include_once __DIR__ . '/../modeloSistema/ProgramaPDFDetalle.Class.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $idAsignatura = preg_replace('/[^a-zA-Z0-9]/', '', $_POST['idAsignatura'] ?? '');
    $anio = intval($_POST['anio'] ?? 0);
    
    if (empty($idAsignatura) || $anio < 2000 || $anio > 2100) {
        echo "<script>alert('Parámetros de asignatura o año inválidos.'); window.history.back();</script>";
        exit;
    }
    
    // Manejo y validación estricta del archivo
    if (!isset($_FILES['archivoPDF']) || $_FILES['archivoPDF']['error'] !== UPLOAD_ERR_OK) {
        echo "<script>alert('Debe seleccionar un archivo PDF válido.'); window.history.back();</script>";
        exit;
    }

    $archivo = $_FILES['archivoPDF'];
    $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
    
    if ($extension !== 'pdf') {
        echo "<script>alert('Solo se permiten archivos PDF.'); window.history.back();</script>";
        exit;
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeReal = $finfo->file($archivo['tmp_name']);
    if ($mimeReal !== 'application/pdf') {
        echo "<script>alert('El contenido del archivo no corresponde a un documento PDF.'); window.history.back();</script>";
        exit;
    }

    if ($archivo['size'] > 10 * 1024 * 1024) {
        echo "<script>alert('El archivo excede el tamaño máximo permitido (10 MB).'); window.history.back();</script>";
        exit;
    }

    // Crear directorio si no existe
    $directorioDestino = __DIR__ . '/../archivos/programas/';
    if (!file_exists($directorioDestino)) {
        mkdir($directorioDestino, 0755, true);
    }

    // Nombre del archivo sanitizado
    $nombreArchivo = "prg_" . basename($idAsignatura) . "_{$anio}_" . time() . ".pdf";
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
    header("Location: ../vista/inicio.php");
}
