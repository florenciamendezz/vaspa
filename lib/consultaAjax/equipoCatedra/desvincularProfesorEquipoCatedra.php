<?php
include_once '../../../lib/ControlAcceso.Class.php';
ControlAcceso::verificaLogin();
$UsuarioSes = $_SESSION['usuario'] ?? null;
$perfil = $UsuarioSes->roles[0]->nombre ?? "";
if (!ControlAcceso::verificaPermiso(PermisosSistema::PERMISO_ASIGNATURAS) && $perfil !== PermisosSistema::ROL_VINCULACION_ACADEMICA && $perfil !== PermisosSistema::ROL_ADMIN) {
    http_response_code(403);
    die('<div class="alert alert-danger text-center">Acceso denegado: No posee permisos suficientes.</div>');
}

include_once '../../../modeloSistema/BDConexionSistema.Class.php';
include_once '../../../modeloSistema/Profesor.Class.php';

// variable donde se almacenara el resultado de la operacion y que se devolvera
$mensaje = '';

// Validamos que esten seteados los campos
if (isset($_POST['idAsignatura']) && isset($_POST['idProfesor']) && isset($_POST['rol'])){
    $idProfesor = $_POST['idProfesor'];
    $idAsignatura = $_POST['idAsignatura'];
    $rol = $_POST['rol'];
    
    $profesor = new Profesor($idProfesor);
    // Procedemos a eliminar en la BD
    $db = BDConexionSistema::getInstancia();
    $stmt = $db->prepare("DELETE FROM profesor_asignatura WHERE idAsignatura = ? AND idProfesor = ? AND rol = ?");
    $affected = 0;
    if ($stmt) {
        $stmt->bind_param("sss", $idAsignatura, $idProfesor, $rol);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();
    }
            
    if ($affected == 1) {
        //echo "Eliminado exitosamente";
        $mensaje = '<div class="alert alert-success alert-dismissible fade show text-center" role="alert">
                        Se desvincul&oacute; el Profesor <strong>'.$profesor->getApellido().'</strong>, <b>'.$profesor->getNombre().'</b> del Equipo de C&aacute;tedra.
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                          <span aria-hidden="true">&times;</span>
                        </button>
                      </div>';
    } else {
        //echo "Ocurrio un error al eliminar";
        $mensaje = '<div class="alert alert-warning alert-dismissible fade show text-center" role="alert">
                        Ocurrio un error al desvincular el Profesor <strong>'.$profesor->getApellido().'</strong>, <b>'.$profesor->getNombre().'</b> del Equipo de C&aacute;tedra.
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                          <span aria-hidden="true">&times;</span>
                        </button>
                      </div>';
    }
}

echo $mensaje;

//echo $codAsignatura.' - '.$codPlan;
/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

