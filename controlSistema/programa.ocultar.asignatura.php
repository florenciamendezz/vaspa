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
    $idAsignatura = isset($_POST['idAsignatura']) ? trim($_POST['idAsignatura']) : '';
    $ocultar = isset($_POST['ocultar']) && $_POST['ocultar'] == '1' ? true : false;
    
    if (!empty($idAsignatura)) {
        $conexion = BDConexionSistema::getInstancia();
        
        $conexion->autocommit(FALSE);
        try {
            // Obtener el valor actual
            $sqlGet = "SELECT valor FROM configuracion_sistema WHERE clave = 'asignaturas_ocultas_panel_va'";
            $resGet = $conexion->query($sqlGet);
            $ocultas = array();
            
            if ($resGet && $resGet->num_rows > 0) {
                $row = $resGet->fetch_assoc();
                $valorActual = trim($row['valor']);
                if (!empty($valorActual)) {
                    $ocultas = explode(',', $valorActual);
                }
            } else {
                // Insertar fila si no existe
                $conexion->query("INSERT IGNORE INTO configuracion_sistema (clave, valor, descripcion) VALUES ('asignaturas_ocultas_panel_va', '', 'IDs de asignaturas ocultas')");
            }
            
            // Limpiar espacios de cada ID
            $ocultas = array_map('trim', $ocultas);
            // Quitar vacíos
            $ocultas = array_filter($ocultas);
            
            if ($ocultar) {
                if (!in_array($idAsignatura, $ocultas)) {
                    $ocultas[] = $idAsignatura;
                }
            } else {
                $ocultas = array_diff($ocultas, array($idAsignatura));
            }
            
            $nuevoValor = implode(',', $ocultas);
            $nuevoValorEscaped = $conexion->real_escape_string($nuevoValor);
            $idUsuario = $UsuarioSes->id;
            
            $sqlUpdate = "UPDATE configuracion_sistema 
                          SET valor = '{$nuevoValorEscaped}', 
                              actualizado_por = {$idUsuario},
                              fecha_actualizacion = NOW() 
                          WHERE clave = 'asignaturas_ocultas_panel_va'";
            
            if (!$conexion->query($sqlUpdate)) {
                throw new Exception("Error al actualizar asignaturas ocultas: " . $conexion->error);
            }
            
            $conexion->commit();
            $conexion->autocommit(TRUE);
            
            header("Location: ../vista/monitoreo.circuito.php?mensaje=ocultar_actualizado");
            exit();
        } catch (Exception $e) {
            $conexion->rollback();
            $conexion->autocommit(TRUE);
            error_log($e->getMessage());
            header("Location: ../vista/monitoreo.circuito.php?error=" . urlencode($e->getMessage()));
            exit();
        }
    } else {
        header("Location: ../vista/monitoreo.circuito.php?error=ID de asignatura vacío");
        exit();
    }
} else {
    header("Location: ../vista/monitoreo.circuito.php");
    exit();
}
?>
