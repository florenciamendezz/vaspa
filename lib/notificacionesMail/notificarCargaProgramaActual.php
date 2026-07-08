<?php

include_once 'notificaciones.php';
include_once '../../modeloSistema/Asignatura.Class.php';
include_once '../../modeloSistema/BDConexionSistema.Class.php';
include_once '../../modeloSistema/Profesor.Class.php';

/*
 * Se lleva cabo el envio del mail (Notificacion) al profesor responsable de la
 * asignatura, solicitando en el mismo la carga del programa del anio actual.
 */

//$_GET['id'] = 1655;
//$_POST['idAsignatura'] = 1655;
if (isset($_POST['idAsignatura'])){
    $idAsignatura = $_POST['idAsignatura'];
    
    $asignatura = new Asignatura($idAsignatura);
    $profesores = $asignatura->getProfesoresResponsables();
    
    if (empty($profesores)) {
        echo '<hr><div class="alert alert-danger alert-dismissible fade show text-center" role="alert">
            No hay profesores responsables asignados a esta asignatura.
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>';
        exit();
    }
    
    $resultadoOperacion = '';
    
    setlocale(LC_ALL,"es_ES");
    //Establece la zona horaria predeterminada usada por todas las funciones de fecha/hora en un script
    date_default_timezone_set('America/Argentina/Buenos_Aires');
    $fecha = date("Y-m-d"); // obtenemos la fecha actual
    
    $db = BDConexionSistema::getInstancia();
    
    foreach ($profesores as $profesor) {
        $mensajeError = '<hr><div class="alert alert-danger alert-dismissible fade show text-center" role="alert">
                        <b>Ha ocurrido un error</b> al enviar el correo al Profesor: <b>'.$profesor->getApellido().'</b> solicitando el Programa de: <b>'.$asignatura->getNombre().'</b>.
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                          <span aria-hidden="true">&times;</span>
                        </button>
                      </div>';
                      
        if ($db->begin_transaction()) {
            // armamos la sentencia para insertar una nueva notificacion 
            $sql = "INSERT INTO `registro_notificacion`"
                    . "(`fecha`, `observaciones`, `idProfesor`, `idAsignatura`) VALUES "
                    . "('{$fecha}',NULL,'{$profesor->getId()}','{$asignatura->getId()}')";

            $result = $db->query($sql);

            // Verificamos si se ejecutara correctamente la insercion en la BD
            if ($result && $db->affected_rows == 1) {
                // Procedemos a Enviar el correo al Profesor responsable de la asignatura
                if (enviarMailSolicitarCargaPrograma($idAsignatura, $profesor->getId()) == 1) {
                    $resultadoOperacion .= '<hr><div class="alert alert-success alert-dismissible fade show text-center" role="alert">
                        <b>Se envi&oacute; el correo</b> al Profesor: <b>'.$profesor->getApellido().'</b> solicitando el Programa de: <b>'.$asignatura->getNombre().'</b>.
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                          <span aria-hidden="true">&times;</span>
                        </button>
                      </div>';
                    // insertamos en la BD
                    $db->commit();
                } else {
                    // ocurrio un error al enviar el correo, hacemos rollback para que no se inserte el registro
                    $db->rollback();
                    $resultadoOperacion .= $mensajeError;
                }
            } else {
                $db->rollback();
                $resultadoOperacion .= $mensajeError;
            }
        } else {
            $resultadoOperacion .= $mensajeError;
        }
    }
    
    echo $resultadoOperacion;
} else {
    echo '<hr><div class="alert alert-danger alert-dismissible fade show text-center" role="alert">
        Ocurrio un error al intentar enviar el correo (Faltan datos).
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>';
}

