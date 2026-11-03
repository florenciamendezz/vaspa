<?php
include_once __DIR__ . '/../lib/ControlAcceso.Class.php';
ControlAcceso::verificaLogin();
ControlAcceso::requiereCsrf();
include_once __DIR__ . '/../modeloSistema/ProgramaPDFDetalle.Class.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $idAsignatura = preg_replace('/[^a-zA-Z0-9]/', '', $_POST['idAsignatura'] ?? '');
    $anio = intval($_POST['anio'] ?? 0);
    $vigencia = intval($_POST['vigencia'] ?? 1);
    
    if (empty($idAsignatura) || $anio < 2000 || $anio > 2100) {
        echo "<script>alert('Parámetros de asignatura o año inválidos.'); window.history.back();</script>";
        exit;
    }

    // 1. Verificar si existe algún registro en revisión activa (en_revision = 1)
    $programaExistente = ProgramaPDFDetalle::obtenerPorAsignaturaYAnio($idAsignatura, $anio);
    if ($programaExistente && $programaExistente->getEnRevision() == 1) {
        echo "<script>alert('Ya hay un programa en curso. No podés subir otro hasta que sea aprobado o devuelto.'); window.history.back();</script>";
        exit;
    }

    // Manejo y validación estricta del archivo
    if (!isset($_FILES['archivoPDF']) || $_FILES['archivoPDF']['error'] !== UPLOAD_ERR_OK) {
        echo "<script>alert('Debe seleccionar un archivo PDF válido o se produjo un error al transferir.'); window.history.back();</script>";
        exit;
    }

    $archivo = $_FILES['archivoPDF'];
    $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));

    if ($extension !== 'pdf') {
        echo "<script>alert('Solo se permiten archivos con extensión .pdf.'); window.history.back();</script>";
        exit;
    }

    // Validación estricta de Magic Bytes / Tipo MIME Real
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeReal = $finfo->file($archivo['tmp_name']);
    if ($mimeReal !== 'application/pdf') {
        echo "<script>alert('El contenido real del archivo no corresponde a un documento PDF legítimo.'); window.history.back();</script>";
        exit;
    }

    // Límite de tamaño máximo: 10 MB
    if ($archivo['size'] > 10 * 1024 * 1024) {
        echo "<script>alert('El archivo excede el tamaño máximo permitido (10 MB).'); window.history.back();</script>";
        exit;
    }

    // Crear directorio seguro si no existe
    $directorioDestino = __DIR__ . '/../archivos/programas/';
    if (!file_exists($directorioDestino)) {
        mkdir($directorioDestino, 0755, true);
    }

    // Nombre de archivo sanitizado y determinista
    $nombreArchivo = "prg_" . basename($idAsignatura) . "_" . $anio . "_" . time() . ".pdf";
    $rutaCompleta = $directorioDestino . $nombreArchivo;

    if (move_uploaded_file($archivo['tmp_name'], $rutaCompleta)) {
        $conexion = BDConexionSistema::getInstancia();
        
        // 2. Si ya existe un registro (fue desaprobado o es borrador)
        if ($programaExistente) {
            $conexion->autocommit(FALSE);
            try {
                $circuito = $programaExistente->determinarCircuito($idAsignatura);
                $aprobadoEscuelaVal = ($circuito == 'estandar') ? "NULL" : "1";
                $nombreArchivoEscaped = $conexion->real_escape_string($nombreArchivo);
                
                // Actualizar el registro existente
                $sqlDetalle = "UPDATE programa_pdf_detalle 
                               SET ruta_archivo = '{$nombreArchivoEscaped}',
                                   aprobado_escuela = {$aprobadoEscuelaVal},
                                   aprobado_va = NULL,
                                   aprobado_depto = NULL,
                                   aprobado_va_firma = NULL,
                                   en_revision = 0,
                                   fue_desaprobado = 0,
                                   subido_por_rol = 'profesor',
                                   fecha_ultimo_movimiento_circuito = NOW()
                               WHERE id = {$programaExistente->getId()}";
                if (!$conexion->query($sqlDetalle)) throw new Exception("Error al actualizar programa_pdf_detalle: " . $conexion->error);

                // Sincronizar con tabla legacy programa
                $idLegacy = $programaExistente->getProgramaLegacyId();
                if ($idLegacy) {
                    $sqlLegacy = "UPDATE programa 
                                  SET aprobadoEscuela = {$aprobadoEscuelaVal},
                                      aprobadoVa = NULL,
                                      aprobadoDepto = NULL,
                                      enRevision = 0,
                                      fueDesaprobado = 0,
                                      comentarioVa = '',
                                      comentarioDepto = '',
                                      comentarioEscuela = ''
                                  WHERE id = {$idLegacy}";
                    if (!$conexion->query($sqlLegacy)) throw new Exception("Error al actualizar programa legacy: " . $conexion->error);
                }

                // Marcar devoluciones previas como resueltas
                $idPdf = intval($programaExistente->getId());
                $idLegacy = intval($programaExistente->getProgramaLegacyId());
                $sqlMarkResolved = "UPDATE programa_devoluciones SET resuelto = 1 
                                    WHERE (id_programa_pdf = {$idPdf}" . ($idLegacy ? " OR id_programa = {$idLegacy}" : "") . ") AND resuelto = 0";
                if (!$conexion->query($sqlMarkResolved)) throw new Exception("Error marking feedback as resolved: " . $conexion->error);

                $conexion->commit();
                $conexion->autocommit(TRUE);
                $resultado = true;
            } catch (Exception $e) {
                $conexion->rollback();
                $conexion->autocommit(TRUE);
                error_log($e->getMessage());
                $resultado = false;
            }
        } else {
            // 3. Crear registro nuevo desde cero
            $resultado = (new ProgramaPDFDetalle())->crear($idAsignatura, $anio, $vigencia, $nombreArchivo);
        }

        if ($resultado) {
            // 4. Verificar si la asignatura es compartida
            $idAsignaturaEscaped = $conexion->real_escape_string($idAsignatura);
            $sqlCompartida = "SELECT COUNT(DISTINCT idPlan) as planes_count FROM plan_asignatura WHERE idAsignatura = '{$idAsignaturaEscaped}'";
            $resCompartida = $conexion->query($sqlCompartida);
            $esCompartida = false;
            if ($resCompartida && $rowC = $resCompartida->fetch_assoc()) {
                $esCompartida = ($rowC['planes_count'] > 1);
            }

            if ($esCompartida) {
                echo "<script>alert('Programa cargado correctamente. ¡Atención! Esta asignatura está compartida entre múltiples planes de estudio. El archivo subido se aplicará a todos ellos.'); window.location.href = '../vista/inicio.php';</script>";
            } else {
                echo "<script>alert('Programa cargado correctamente.'); window.location.href = '../vista/inicio.php';</script>";
            }
        } else {
            echo "<script>alert('Error al guardar en la base de datos.'); window.history.back();</script>";
        }
    } else {
        echo "<script>alert('Error al mover el archivo al directorio de destino.'); window.history.back();</script>";
    }
} else {
    header("Location: ../vista/inicio.php");
}
