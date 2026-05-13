<?php
include_once '../lib/ControlAcceso.Class.php';
include_once '../modeloSistema/BDConexionSistema.Class.php';
include_once '../modeloSistema/ProgramaPDFDetalle.Class.php';

// Verificar sesión y permisos básicos
// ControlAcceso::requierePermiso(PermisosSistema::PERMISO_REVISAR_PROGRAMA);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['idPrograma']) && isset($_FILES['archivoPdf'])) {
    
    $idPrograma = $_POST['idPrograma'];
    $archivo = $_FILES['archivoPdf'];
    
    $programa = new ProgramaPDFDetalle($idPrograma);
    if (!$programa->getId()) {
        echo "<script>alert('Programa no encontrado.'); window.history.back();</script>";
        exit;
    }

    // Obtener datos para el nombre del archivo
    $idAsignatura = $programa->getIdAsignatura();
    $anio = $programa->getAnio();
    
    // Obtener rol del usuario para el nombre del archivo
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    $rolSlug = 'Desconocido';
    if (isset($_SESSION['usuario'])) {
        $rolNombre = $_SESSION['usuario']->roles[0]->nombre;
        if ($rolNombre == 'Vinculación Académica' || $rolNombre == 'Administrador') {
            $rolSlug = 'VA';
        } elseif ($rolNombre == 'Director de Departamento') {
            $rolSlug = 'Depto';
        } elseif ($rolNombre == 'Director de Escuela') {
            $rolSlug = 'Escuela';
        }
    }

    // Manejo del archivo (Logica alineada con programa.crear.procesar.pdf.php)
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
            // Actualizar base de datos
            $sql = "UPDATE programa_pdf_detalle SET ruta_archivo = '{$nombreArchivo}'";
            
            // Logica especifica por rol
            if ($rolSlug == 'VA') {
                $sql .= ", aprobado_va = 1, aprobado_depto = 1, aprobado_escuela = 1";
            } elseif ($rolSlug == 'Depto') {
                $sql .= ", aprobado_depto = 1, aprobado_escuela = 1";
            } elseif ($rolSlug == 'Escuela') {
                $sql .= ", aprobado_escuela = 1";
            } else {
                // Asumimos Profesor si no es ninguno de los anteriores
                // Verificar si es profesor para activar enRevision y notificar
                if (isset($_SESSION['usuario']) && $_SESSION['usuario']->roles[0]->nombre == 'Profesor') {
                     // IMPORTANTE: Reseteamos a NULL para que !is_null() funcione correctamente en validaciones posteriores
                     $sql .= ", en_revision = 1, fue_desaprobado = 0, aprobado_va = NULL, aprobado_depto = NULL, aprobado_escuela = NULL";
                     // Incluir script de notificacion
                     include_once '../lib/notificacionesMail/notificacionNuevoPrograma.php';
                     notificarNuevoPrograma($idPrograma);
                }
            }
            
            $sql .= " WHERE id = {$idPrograma}";
            
            $res = BDConexionSistema::getInstancia()->query($sql);
            
            // Si el usuario es profesor, actualizamos tambien la tabla `programa` (legacy) 
            // ya que la vista revisar.programa.pdf.php usa el estado de esa tabla para ocultar botones
            if (isset($_SESSION['usuario']) && $_SESSION['usuario']->roles[0]->nombre == 'Profesor') {
                $sqlLegacy = "UPDATE programa SET 
                                enRevision = 1, 
                                fueDesaprobado = 0, 
                                aprobadoDepto = NULL, 
                                aprobadoEscuela = NULL, 
                                aprobadoVa = NULL
                              WHERE idAsignatura = '{$idAsignatura}' AND anio = '{$anio}'";
                BDConexionSistema::getInstancia()->query($sqlLegacy);
            }
            
            if ($res) {
                $redirectUrl = '../vista/revisar.programas.php';
                if (isset($_SESSION['usuario']) && $_SESSION['usuario']->roles[0]->nombre == 'Profesor') {
                    $redirectUrl = '../vista/asignaturasDeProfesor.php';
                }
                
                echo "<script>alert('Programa firmado cargado correctamente.'); window.location.href = '{$redirectUrl}';</script>";
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
