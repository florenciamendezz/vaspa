<?php
include_once '../lib/ControlAcceso.Class.php';
ControlAcceso::requierePermiso(PermisosSistema::PERMISO_USUARIOS);
ControlAcceso::requiereCsrf();
include_once '../modelo/BDConexion.Class.php';
$DatosFormulario = $_POST;

$db = BDConexion::getInstancia();
$idUsuario = intval($DatosFormulario["id"] ?? 0);

// obtenemos el rol del usuario
$stmtRol = $db->prepare("SELECT rol.nombre, usuario.email FROM usuario_rol JOIN rol ON id_rol = rol.id JOIN usuario ON id_usuario = usuario.id WHERE id_usuario = ?");
if ($stmtRol) {
    $stmtRol->bind_param("i", $idUsuario);
    $stmtRol->execute();
    $resultado = $stmtRol->get_result();
    $stmtRol->close();
} else {
    $resultado = false;
}

$mensaje = '';
$error = "";
$consulta = FALSE;

if (!$resultado){
    $error = 'Error al realizar petici&oacute;n a la Base de Datos';
    $consulta = FALSE;

} else {
    $registro = $resultado->fetch_assoc();
    $nombreRol = $registro["nombre"] ?? '';
    
    // comprobamos el rol del usuario
    if ($nombreRol == "Profesor"){
        // Hay que eliminarlo tambien de la tabla PROFESOR
        // Verificamos si el profesor es responsable de asignaturas
        $emailProfesor = $registro["email"] ?? '';
        
        // obtenemos datos del profesor
        $stmtProf = $db->prepare("SELECT * FROM " . Constantes::BD_SCHEMA . ".profesor WHERE email = ? LIMIT 1");
        if ($stmtProf) {
            $stmtProf->bind_param("s", $emailProfesor);
            $stmtProf->execute();
            $resultadoProf = $stmtProf->get_result();
            $stmtProf->close();
        } else {
            $resultadoProf = false;
        }
        
        if (!$resultadoProf || $resultadoProf->num_rows == 0){
            $error = 'Error al realizar petici&oacute;n a la Base de Datos o profesor no encontrado';
            $consulta = FALSE;
        } else {
            include_once '../modeloSistema/Profesor.Class.php';
            $registroProf = $resultadoProf->fetch_assoc();
            $idProfesor = $registroProf["id"];
            $profesor = new Profesor($idProfesor);
            
            if (is_null($profesor->obtenerAsignaturas())){
                // El profesor no tiene asignaturas con lo cual se lo puede borrar
                include_once '../controlSistema/ManejadorProfesor.php';
                
                $manejadorProfesor = new ManejadorProfesor();
                // procedemos a borrar los registros
                
                try {
                    $consulta = $manejadorProfesor->bajaUsuarioProfesor($idProfesor, $idUsuario);
                } catch (Exception $e) {
                    $error = $e->getMessage();
                    $consulta = FALSE;
                }
                            
            } else {
                // El profesor esta a cargo de asignaturas por lo cual no se deberia poder eliminar
                $error = 'El usuario que intenta eliminar es un <b>Profesor responsable de Asignaturas</b>, y tiene asignaturas a su cargo. Operaci&oacute;n no permitida.';
                $consulta = FALSE;
            }
        }
         
    } else {
        // Solamente eliminamos el usuario
        $db->autocommit(false);
        $db->begin_transaction();

        $stmtDelRol = $db->prepare("DELETE FROM usuario_rol WHERE id_usuario = ?");
        if (!$stmtDelRol) {
            $db->rollback();
            $error = "Error al preparar eliminación de rol del usuario.";
            $consulta = false;
        } else {
            $stmtDelRol->bind_param("i", $idUsuario);
            if (!$stmtDelRol->execute()) {
                $stmtDelRol->close();
                $db->rollback();
                $error = "Error al eliminar rol del usuario.";
                $consulta = false;
            } else {
                $stmtDelRol->close();

                $stmtDelUsr = $db->prepare("DELETE FROM usuario WHERE id = ?");
                if (!$stmtDelUsr) {
                    $db->rollback();
                    $error = "Error al preparar eliminación de usuario.";
                    $consulta = false;
                } else {
                    $stmtDelUsr->bind_param("i", $idUsuario);
                    if (!$stmtDelUsr->execute()) {
                        $stmtDelUsr->close();
                        $db->rollback();
                        $error = "Error al eliminar usuario.";
                        $consulta = false;
                    } else {
                        $stmtDelUsr->close();
                        $db->commit();
                        $db->autocommit(true);
                        $consulta = true;
                    }
                }
            }
        }
    }
}


?>
<html>
    <head>
        <meta charset="UTF-8">
        <link rel="stylesheet" href="../lib/bootstrap-4.1.1-dist/css/bootstrap.css" />
        <link rel="stylesheet" href="../lib/open-iconic-master/font/css/open-iconic-bootstrap.css" />
        <script type="text/javascript" src="../lib/JQuery/jquery-3.3.1.js"></script>
        <script type="text/javascript" src="../lib/bootstrap-4.1.1-dist/js/bootstrap.min.js"></script>
        <title><?php echo Constantes::NOMBRE_SISTEMA; ?> - Eliminar Usuario</title>
    </head>
    <body>
        <?php include_once '../gui/navbar.php'; ?>
        <div class="container">
            <p></p>
            <div class="card">
                <div class="card-header">
                    <h3>Eliminar Usuario</h3>
                </div>
                <div class="card-body">
                    <?php if ($consulta) { ?>
                        <div class="alert alert-success" role="alert">
                            Operaci&oacute;n realizada con &eacute;xito.
                        </div>
                    <?php } ?>   
                    <?php if (!$consulta) { ?>
                        <div class="alert alert-danger" role="alert">
                            Ha ocurrido un error. <?= $error; ?>
                        </div>
                    <?php } ?>
                    <hr />
                    <h5 class="card-text">Opciones</h5>
                     <a href="usuarios.php">
                        <button type="button" class="btn btn-primary">
                            <span class="oi oi-account-logout"></span> Salir
                        </button>
                    </a>
                </div>
            </div>
        </div>
        <?php include_once '../gui/footer.php'; ?>
    </body>
</html>
