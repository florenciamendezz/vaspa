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
include_once '../../../modeloSistema/Asignatura.Class.php';

// variable donde se almacenara el resultado de la operacion y que se devolvera
$mensaje = '';

// Validamos que esten seteados los campos
if (isset($_POST['idProfesor']) && isset($_POST['idAsignatura']) && isset($_POST['rol'])){
    $idProfesor = $_POST['idProfesor'];
    $idAsignatura = $_POST['idAsignatura'];
    $rol = $_POST['rol'];
    
    $profesor = new Profesor($idProfesor);
    
    $asignatura = new Asignatura($idAsignatura);
    
    if ($asignatura->esResponsable($idProfesor) && $rol == "teoria"){
        $mensaje = '<div class="alert alert-danger alert-dismissible fade show text-center" role="alert">
                        El Profesor <strong>'.$profesor->getApellido().', '.$profesor->getNombre().'</strong> al ser responsable de la asignatura, ya incluido est&aacute; en la Teor&iacute;a.
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                          <span aria-hidden="true">&times;</span>
                        </button>
                      </div>';
    } else {
    
    // Validamos que no se inserte en caso de que el profesor ya pertenezca al equipo de catedra bajo el mismo rol
    $db = BDConexionSistema::getInstancia();
    $stmtCheck = $db->prepare("SELECT 1 FROM profesor_asignatura WHERE idProfesor = ? AND idAsignatura = ? AND rol = ?");
    if ($stmtCheck) {
        $stmtCheck->bind_param("sss", $idProfesor, $idAsignatura, $rol);
        $stmtCheck->execute();
        $resultado = $stmtCheck->get_result();
        $stmtCheck->close();
    } else {
        $resultado = false;
    }
    
    if ($resultado) {
        
        // Si no se obtienen registros de la BD procedemos a insertar
        if ($resultado->num_rows == 0) {

            $stmtIns = $db->prepare("INSERT INTO profesor_asignatura (idAsignatura, idProfesor, rol) VALUES (?, ?, ?)");
            $affected = 0;
            if ($stmtIns) {
                $stmtIns->bind_param("sss", $idAsignatura, $idProfesor, $rol);
                $stmtIns->execute();
                $affected = $stmtIns->affected_rows;
                $stmtIns->close();
            }
            
            // Verificamos que la cantidad de filas afectadas sea igual a 1 (se inserto el registro)
            if ($affected == 1) {
                //echo "Se inserto";
                $mensaje = '<div class="alert alert-success alert-dismissible fade show text-center" role="alert">
                        Se agreg&oacute; al Profesor <strong>'.$profesor->getApellido().', '.$profesor->getNombre().'</strong> al Equipo de C&aacute;tedra.
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                          <span aria-hidden="true">&times;</span>
                        </button>
                      </div>';
            } else {
                //echo "No se inserto";
                $mensaje = '<div class="alert alert-warning alert-dismissible fade show text-center" role="alert">
                        Ocurrio un error al agregar al Profesor <strong>'.$profesor->getApellido().', '.$profesor->getNombre().'</strong> al Equipo de C&aacute;tedra.
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                          <span aria-hidden="true">&times;</span>
                        </button>
                      </div>';
            }
            
        } else {
            //echo "ya se encuentra";
            $mensaje = '<div class="alert alert-warning alert-dismissible fade show text-center" role="alert">
                        El Profesor <strong>'.$profesor->getApellido().', '.$profesor->getNombre().'</strong> ya se encuentra en el Equipo de C&aacute;tedra.
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                          <span aria-hidden="true">&times;</span>
                        </button>
                      </div>';
        }
    } else {
        //echo "Ocurrio un error al consultar";
        $mensaje = '<div class="alert alert-danger alert-dismissible fade show text-center" role="alert">
                        Ocurrio un error al agregar al Profesor <strong>'.$profesor->getApellido().', '.$profesor->getNombre().'</strong> al Equipo de C&aacute;tedra.
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                          <span aria-hidden="true">&times;</span>
                        </button>
                      </div>';
    }
    
    }
  
}

// imprimimos mensaje
echo $mensaje;
