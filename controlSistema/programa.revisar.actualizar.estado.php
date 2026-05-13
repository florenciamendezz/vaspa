<?php

// Aqui se actualiza el estado de un Programa de asignatura (APROBADO, DESAPROBADO).
// en caso de desaprobado se guarda el comentario realizado por el usuario segun su Rol (VA, Depto)
/*
 * Observaciones: Rol Vinculación Académica y Admin comparten la misma funcionalidad
 * Esto quiere decir que si el usuario tiene el rol de Admin va a revisar los programas
 * como si fuese un usuario de VA. (preguntar a los chicos)
 */
// 17/05/20 --> Se agrega funcionalidad que Envia Notificacion al Profesor infomando el resultado de la revision
// 30/06/20 --> Se agrega mas info al mensaje que se devuelve cuando se aprueba/desaprueba un programa (como el nombre de la asignatura, codigo y vigencia del programa)

include_once '../lib/ControlAcceso.Class.php';
include_once '../modeloSistema/BDConexionSistema.Class.php';
include_once '../modeloSistema/Programa.Class.php';
include_once '../modeloSistema/Asignatura.Class.php';

$idPrograma = $_POST["idPrograma"];

//creacion de objetos programa y asignatura
$programa = new Programa($idPrograma);
$asignatura = new Asignatura($programa->getIdAsignatura());
$vigencia = "";
switch ($programa->getVigencia()) {
    case "1":
        $vigencia = "el a&ntilde;o: [".$programa->getAnio()."]";
        break;
    case "2":
        $vigencia = "los a&ntilde;os: [".$programa->getAnio()." - ".($programa->getAnio()+1)."]";
        break;
    case "3":
        $vigencia = "los a&ntilde;os: [".$programa->getAnio()." - ".($programa->getAnio()+1)." - ".($programa->getAnio()+2)."]";
        break;

}
$datosAsig = "<b>{$asignatura->getNombre()} - {$asignatura->getId()}</b>, con vigencia para <b>{$vigencia}</b>";

if ($_SERVER["REQUEST_METHOD"] !== "POST"){
    header("location: ../vista/revisar.programas.php");
} elseif (isset ($_POST["aprobarPrograma"])) {
    
    // preparamos la sentencia SQL segun el rol del usuario (VA o Dpto)
    
    $Usuario = $_SESSION['usuario'];
    $rol = $Usuario->roles[0]->nombre;
    $query = '';
    if ($rol == PermisosSistema::ROL_ADMIN || $rol == PermisosSistema::ROL_VINCULACION_ACADEMICA){
        // comprobamos si fue desaprobado por Dpto para setear a 1 el campo  fueDesaprobado
        if ($programa->getAprobadoDepto() === '0'){
            $desa = ", fueDesaprobado = 1 ";
        }else {
            $desa = "";
        }
        $query = "UPDATE PROGRAMA "
                        . "SET aprobadoVa = 1 "
                        . $desa 
                        . "WHERE id = '{$idPrograma}'";
    } elseif ($rol == PermisosSistema::ROL_DIRECTOR_DEPARTAMENTO) {
        // comprobamos si fue desaprobado por VA para setear a 1 el campo  fueDesaprobado
        if ($programa->getAprobadoVa() === '0' || $programa->getAprobadoEscuela() === '0'){
            $desa = ", fueDesaprobado = 1 ";
        }else {
            $desa = "";
        }
        $query = "UPDATE PROGRAMA "
                        . "SET aprobadoDepto = 1 "
                        . $desa 
                        . "WHERE id = '{$idPrograma}'";
    } elseif ($rol == PermisosSistema::ROL_DIRECTOR_ESCUELA) {
        // comprobamos si fue desaprobado por VA o Depto
        if ($programa->getAprobadoVa() === '0' || $programa->getAprobadoDepto() === '0'){
            $desa = ", fueDesaprobado = 1 ";
        }else {
            $desa = "";
        }
        $query = "UPDATE PROGRAMA "
                        . "SET aprobadoEscuela = 1 "
                        . $desa 
                        . "WHERE id = '{$idPrograma}'";
    }
    
    // Actualizamos tambien la tabla programa_pdf_detalle si existe
    $idAsignatura = $programa->getIdAsignatura();
    $anio = $programa->getAnio();
    $queryPDF = "";
    if ($rol == PermisosSistema::ROL_ADMIN || $rol == PermisosSistema::ROL_VINCULACION_ACADEMICA){
        $queryPDF = "UPDATE programa_pdf_detalle SET aprobado_va = 1 WHERE id_asignatura = '{$idAsignatura}' AND anio = {$anio}";
    } elseif ($rol == PermisosSistema::ROL_DIRECTOR_DEPARTAMENTO) {
        $queryPDF = "UPDATE programa_pdf_detalle SET aprobado_depto = 1 WHERE id_asignatura = '{$idAsignatura}' AND anio = {$anio}";
    } elseif ($rol == PermisosSistema::ROL_DIRECTOR_ESCUELA) {
        $queryPDF = "UPDATE programa_pdf_detalle SET aprobado_escuela = 1 WHERE id_asignatura = '{$idAsignatura}' AND anio = {$anio}";
    }
    if ($queryPDF != "") {
        BDConexionSistema::getInstancia()->query($queryPDF);
    }
    
    //procedemos a cambiar el estado del programa a "APROBADO"
    
    $resultado = BDConexionSistema::getInstancia()->query($query);
    
    // chequeamos la ejecucion del update
    if (BDConexionSistema::getInstancia()->affected_rows == 1) {
        // se actualizo
        $_SESSION['mensajeRevisarPrograma'] = '<div class="alert alert-success alert-dismissible fade show text-center" role="alert">
            El programa de '.$datosAsig.' <b>fue Aprobado</b>.
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>';
        
        // Chequeamos si fue revisado por ambas autoridades para enviar el email
        $revisado = fueRevisadoPorSAyDpto($idPrograma);
        if ($revisado){
            include_once '../lib/notificacionesMail/notificacionProgramaAprobadoDesaprobado.php';
            enviarNotificacionProfesor($idPrograma); // enviamos el mail
        }
        
        header("location: ../vista/revisar.programas.php");
    } else {
        // no se actualizo
        $_SESSION['mensajeRevisarPrograma'] = '<div class="alert alert-danger alert-dismissible fade show text-center" role="alert">
            Ocurrio un error al intentar aprobar el programa de '.$datosAsig.'.
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>';
        header("location: ../vista/revisar.programas.php");
    }   
    
} elseif (isset ($_POST["desaprobarPrograma"])){

    $comentario = $_POST["comentario"];
    
    //procedemos a cambiar el estado del programa a "DESAPROBADO" y modificando el comentario
    // Con que uno lo haya desaprobado al programa, este pasa al estado "Desaprobado" por lo cual tambien se modifica el campo "fueDesaprobado"
    
    // preparamos la sentencia SQL segun el rol del usuario (VA o Dpto)
    
    $Usuario = $_SESSION['usuario'];
    $rol = $Usuario->roles[0]->nombre;
    $query = '';
    if ($rol == PermisosSistema::ROL_ADMIN || $rol == PermisosSistema::ROL_VINCULACION_ACADEMICA){
        // comprobamos si depto todavia no califico el programa
        $desa = 1;
        $query = "UPDATE PROGRAMA "
                        . "SET aprobadoVa = 0, "
                        . "fueDesaprobado = {$desa}, "
                        . "comentarioVa = '{$comentario}' "
                        . "WHERE id = '{$idPrograma}'";
    } elseif ($rol == PermisosSistema::ROL_DIRECTOR_DEPARTAMENTO) {
        // comprobamos si depto todavia no califico el programa
        $desa = 1;
        $query = "UPDATE PROGRAMA "
                        . "SET aprobadoDepto = 0, "
                        . "fueDesaprobado = {$desa}, "
                        . "comentarioDepto = '{$comentario}' "
                        . "WHERE id = '{$idPrograma}'";
    } elseif ($rol == PermisosSistema::ROL_DIRECTOR_ESCUELA) {
        $desa = 1;
        $query = "UPDATE PROGRAMA "
                        . "SET aprobadoEscuela = 0, "
                        . "fueDesaprobado = {$desa}, "
                        . "comentarioEscuela = '{$comentario}' "
                        . "WHERE id = '{$idPrograma}'";
    }
    
    // Actualizamos tambien la tabla programa_pdf_detalle si existe
    $idAsignatura = $programa->getIdAsignatura();
    $anio = $programa->getAnio();
    $queryPDF = "";
    if ($rol == PermisosSistema::ROL_ADMIN || $rol == PermisosSistema::ROL_VINCULACION_ACADEMICA){
        $queryPDF = "UPDATE programa_pdf_detalle SET aprobado_va = 0, fue_desaprobado = 1 WHERE id_asignatura = '{$idAsignatura}' AND anio = {$anio}";
    } elseif ($rol == PermisosSistema::ROL_DIRECTOR_DEPARTAMENTO) {
        $queryPDF = "UPDATE programa_pdf_detalle SET aprobado_depto = 0, fue_desaprobado = 1 WHERE id_asignatura = '{$idAsignatura}' AND anio = {$anio}";
    } elseif ($rol == PermisosSistema::ROL_DIRECTOR_ESCUELA) {
        $queryPDF = "UPDATE programa_pdf_detalle SET aprobado_escuela = 0, fue_desaprobado = 1 WHERE id_asignatura = '{$idAsignatura}' AND anio = {$anio}";
    }
    if ($queryPDF != "") {
        BDConexionSistema::getInstancia()->query($queryPDF);
    }
    
    $resultado = BDConexionSistema::getInstancia()->query($query);

    // chqueamos que se haya realizado correctamente el update
    if (BDConexionSistema::getInstancia()->affected_rows == 1) {
        // se actualizo
        $_SESSION['mensajeRevisarPrograma'] = '<div class="alert alert-success alert-dismissible fade show text-center" role="alert">
            El programa de '.$datosAsig.' <b>fue Desaprobado</b>.
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>';
        
        // Chequeamos si fue revisado por ambas autoridades para enviar el email notificando al profesor el resultado de la evaluacion del programa
        // O SI FUE DESAPROBADO (si fueDesaprobado es 1, se envia mail de rechazo inmediatamente)
        $revisado = fueRevisadoPorSAyDpto($idPrograma);
        if ($revisado || $desa == 1){ 
            include_once '../lib/notificacionesMail/notificacionProgramaAprobadoDesaprobado.php';
            enviarNotificacionProfesor($idPrograma); // enviamos el mail
        }
        
        header("location: ../vista/revisar.programas.php");
    } else {
        // no se actualizo
        $_SESSION['mensajeRevisarPrograma'] = '<div class="alert alert-danger alert-dismissible fade show text-center" role="alert">
            Ocurrio un error al intentar desaprobar el programa de '.$datosAsig.'.
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>';
        header("location: ../vista/revisar.programas.php");
    }
        
}

// metodo que comprueba si el programa ya fue revisado por ambas autoridades tanto VA o como Dpto
function fueRevisadoPorSAyDpto($idPrograma){
    $programa = new Programa($idPrograma);
    // comprobamos que los campos aprobados tanto en SA como en Dpto y Escuela no sean nulos
    if (!is_null($programa->getAprobadoVa()) && !is_null($programa->getAprobadoDepto()) && !is_null($programa->getAprobadoEscuela())){
        return TRUE;
    } else {
        return FALSE;
    }
}

