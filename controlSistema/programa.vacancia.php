<?php
include_once '../lib/ControlAcceso.Class.php';
include_once '../modeloSistema/BDConexionSistema.Class.php';

// Validar que el usuario sea Admin o Vinculación Académica
$UsuarioSes = $_SESSION['usuario'];
$perfil = "";
if (isset($UsuarioSes->roles[0])) {
    $perfil = $UsuarioSes->roles[0]->nombre;
}
if ($perfil !== PermisosSistema::ROL_ADMIN && $perfil !== PermisosSistema::ROL_VINCULACION_ACADEMICA) {
    header("Location: ../vista/panelVA.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $vacancia = isset($_POST['vacancia']) && $_POST['vacancia'] == '1' ? '1' : '0';
    $conexion = BDConexionSistema::getInstancia();
    
    $conexion->autocommit(FALSE);
    try {
        // 1. Actualizar configuración en la base de datos
        $vacanciaEscaped = $conexion->real_escape_string($vacancia);
        $idUsuario = $UsuarioSes->id;
        
        $sqlConfig = "UPDATE configuracion_sistema 
                      SET valor = '{$vacanciaEscaped}', 
                          actualizado_por = {$idUsuario},
                          fecha_actualizacion = NOW() 
                      WHERE clave = 'vacancia_escuela'";
        if (!$conexion->query($sqlConfig)) {
            throw new Exception("Error al actualizar la configuración de vacancia: " . $conexion->error);
        }
        
        // 2. Si se activa la vacancia ('1'), redirigir programas de Escuela a VA
        if ($vacancia == '1') {
            // Obtener los programas afectados para poder actualizar en cascada
            $sqlSelect = "SELECT id, id_asignatura, anio FROM programa_pdf_detalle 
                          WHERE en_revision = 1 
                            AND aprobado_escuela IS NULL 
                            AND fue_desaprobado = 0";
            $resSelect = $conexion->query($sqlSelect);
            
            if ($resSelect && $resSelect->num_rows > 0) {
                while ($row = $resSelect->fetch_assoc()) {
                    $idDetalle = $row['id'];
                    $idAsignatura = $row['id_asignatura'];
                    $anio = $row['anio'];
                    
                    // Actualizar el PDF detalle
                    $sqlUpdateDetalle = "UPDATE programa_pdf_detalle 
                                         SET aprobado_escuela = 1,
                                             fecha_ultimo_movimiento_circuito = NOW()
                                         WHERE id = {$idDetalle}";
                    if (!$conexion->query($sqlUpdateDetalle)) {
                        throw new Exception("Error al redirigir programa_pdf_detalle ID {$idDetalle}: " . $conexion->error);
                    }
                    
                    // Actualizar el programa legacy
                    $sqlUpdateLegacy = "UPDATE programa 
                                        SET aprobadoEscuela = 1 
                                        WHERE idAsignatura = '{$conexion->real_escape_string($idAsignatura)}' 
                                          AND anio = {$anio}";
                    if (!$conexion->query($sqlUpdateLegacy)) {
                        throw new Exception("Error al redirigir programa legacy para {$idAsignatura}/{$anio}: " . $conexion->error);
                    }
                }
            }
        }
        
        $conexion->commit();
        $conexion->autocommit(TRUE);
        
        // Enviar notificación por mail (M11) si la clase existe
        if (file_exists('../lib/notificacionesMail/notificacionCircuitoVaspa.php')) {
            include_once '../lib/notificacionesMail/notificacionCircuitoVaspa.php';
            if (class_exists('notificacionCircuitoVaspa')) {
                notificacionCircuitoVaspa::notificarVacanciaCambio($vacancia);
            }
        }
        
        header("Location: ../vista/monitoreo.circuito.php?mensaje=vacancia_actualizada");
        exit();
        
    } catch (Exception $e) {
        $conexion->rollback();
        $conexion->autocommit(TRUE);
        error_log($e->getMessage());
        header("Location: ../vista/monitoreo.circuito.php?error=" . urlencode($e->getMessage()));
        exit();
    }
} else {
    header("Location: ../vista/monitoreo.circuito.php");
    exit();
}
?>
