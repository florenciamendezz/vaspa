<?php
include_once '../lib/ControlAcceso.Class.php';
include_once '../modeloSistema/ProgramaPDFDetalle.Class.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $idAsignatura = $_POST['idAsignatura'];
    $anio = $_POST['anio'];
    $vigencia = isset($_POST['vigencia']) ? $_POST['vigencia'] : 1; // Default a 1 año si falta
    
    // 1. Verificar si existe algún registro en revisión activa (en_revision = 1)
    $programaExistente = ProgramaPDFDetalle::obtenerPorAsignaturaYAnio($idAsignatura, $anio);
    if ($programaExistente && $programaExistente->getEnRevision() == 1) {
        echo "<script>alert('Ya hay un programa en curso. No podés subir otro hasta que sea aprobado o devuelto.'); window.history.back();</script>";
        exit;
    }

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
                    echo "<script>alert('Programa cargado correctamente. ¡Atención! Esta asignatura está compartida entre múltiples planes de estudio. El archivo subido se aplicará a todos ellos.'); window.location.href = '../vista/asignaturasDeProfesor.php';</script>";
                } else {
                    echo "<script>alert('Programa cargado correctamente.'); window.location.href = '../vista/asignaturasDeProfesor.php';</script>";
                }
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
