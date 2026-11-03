<?php
include_once '../lib/ControlAcceso.Class.php';
ControlAcceso::requierePermiso(PermisosSistema::PERMISO_USUARIOS);
ControlAcceso::requiereCsrf();
include_once '../modelo/BDConexion.Class.php';
include_once '../modelo/Usuario.Class.php';
$DatosFormulario = $_POST;
$db = BDConexion::getInstancia();
$idUsuario = intval($DatosFormulario["id"] ?? 0);
$nombreInput = trim($DatosFormulario["nombre"] ?? '');
$emailInput = trim($DatosFormulario["email"] ?? '');

$Usuario = new Usuario($idUsuario);

// chequeamos si ya existe tanto el nombre de Usuario como el email en "otros" usuarios del Sistema.
$stmtCheck = $db->prepare("SELECT id, nombre, email FROM usuario WHERE id <> ? AND (nombre = ? OR email = ?)");
if ($stmtCheck) {
    $stmtCheck->bind_param("iss", $idUsuario, $nombreInput, $emailInput);
    $stmtCheck->execute();
    $resultado = $stmtCheck->get_result();
    $stmtCheck->close();
} else {
    $resultado = false;
}

$mensaje = '';

if (!$resultado){
    $mensaje = 'Error al realizar petici&oacute;n a la Base de Datos';
    $consulta = FALSE;
} else {
    
    if ($resultado->num_rows == 1) { // Si hay un registro esto puede significar que ya existe el nombre de usuario o correo
        $registro = $resultado->fetch_assoc();

        $nombre = $registro["nombre"];
        $email = $registro["email"];
        $nombreEsc = e($nombre);
        $emailEsc = e($email);
        
        if ($nombreInput == $nombre && $emailInput == $email){
            $mensaje = "El nombre de usuario: <b>{$nombreEsc}</b> y el email: <b>{$emailEsc}</b> ya existen, por favor ingrese otro nombre de usuario y otro email.";
        } elseif ($nombreInput == $nombre) {
            $mensaje = "El nombre de usuario: <b>{$nombreEsc}</b> ya existe, por favor ingrese otro nombre de usuario.";
        } elseif ($emailInput == $email) {
            $mensaje = "El email: <b>{$emailEsc}</b> ya existe, por favor ingrese otro email";
        }
        
        $consulta = FALSE;
         
    } elseif ($resultado->num_rows == 2) {
        // si hay dos registros esto quiere decir que se repiten tanto el nombre como el correo.
        $consulta = FALSE;
        $nombreInputEsc = e($nombreInput);
        $emailInputEsc = e($emailInput);
        $mensaje = "El nombre de usuario: <b>{$nombreInputEsc}</b> y el email: <b>{$emailInputEsc}</b> ya existen, por favor ingrese otro nombre de usuario y otro email.";
    } elseif ($resultado->num_rows == 0) { // no se encontraron coincidencias en la BD, por lo tanto se debe proceder con la insercion
        
        // obtenemos el rol del Usuario a modificar
        $roles = $Usuario->getRoles();
        $rolUsuario = (!empty($roles) && isset($roles[0])) ? $roles[0]->getNombre() : '';
        
        // comprobamos si el Rol es de Profesor (No se modifica el Rol, pero si hay que modificar el mail de la tabla profesor).
        if ($rolUsuario == "Profesor"){
            // es profesor --> hay que actualizar el email en la tabla profesor
            $db->autocommit(false);
            $db->begin_transaction();

            $stmtUsr = $db->prepare("UPDATE usuario SET nombre = ?, email = ? WHERE id = ?");
            if (!$stmtUsr) {
                $db->rollback();
                $consulta = false;
                $mensaje = "Error al preparar la actualización de usuario.";
            } else {
                $stmtUsr->bind_param("ssi", $nombreInput, $emailInput, $idUsuario);
                if (!$stmtUsr->execute()) {
                    $stmtUsr->close();
                    $db->rollback();
                    $consulta = false;
                    $mensaje = "Error al actualizar los datos del usuario.";
                } else {
                    $stmtUsr->close();

                    $emailAnterior = $Usuario->getEmail();
                    $stmtProf = $db->prepare("UPDATE " . Constantes::BD_SCHEMA . ".profesor SET email = ? WHERE email = ?");
                    if (!$stmtProf) {
                        $db->rollback();
                        $consulta = false;
                        $mensaje = "Error al preparar la actualización del profesor.";
                    } else {
                        $stmtProf->bind_param("ss", $emailInput, $emailAnterior);
                        if (!$stmtProf->execute()) {
                            $stmtProf->close();
                            $db->rollback();
                            $consulta = false;
                            $mensaje = "Error al actualizar el correo del profesor.";
                        } else {
                            $stmtProf->close();
                            $db->commit();
                            $db->autocommit(true);
                            $consulta = true;
                        }
                    }
                }
            }
            
        } else {
            // no es profesor, procedemos a actualizar los campos
            $db->autocommit(false);
            $db->begin_transaction();

            $stmtUsr = $db->prepare("UPDATE usuario SET nombre = ?, email = ? WHERE id = ?");
            if (!$stmtUsr) {
                $db->rollback();
                $consulta = false;
                $mensaje = "Error al preparar la actualización de usuario.";
            } else {
                $stmtUsr->bind_param("ssi", $nombreInput, $emailInput, $idUsuario);
                if (!$stmtUsr->execute()) {
                    $stmtUsr->close();
                    $db->rollback();
                    $consulta = false;
                    $mensaje = "Error al actualizar los datos del usuario.";
                } else {
                    $stmtUsr->close();

                    $stmtDelRol = $db->prepare("DELETE FROM usuario_rol WHERE id_usuario = ?");
                    if (!$stmtDelRol) {
                        $db->rollback();
                        $consulta = false;
                        $mensaje = "Error al preparar la limpieza de roles.";
                    } else {
                        $stmtDelRol->bind_param("i", $idUsuario);
                        if (!$stmtDelRol->execute()) {
                            $stmtDelRol->close();
                            $db->rollback();
                            $consulta = false;
                            $mensaje = "Error al eliminar roles anteriores.";
                        } else {
                            $stmtDelRol->close();

                            $idRol = intval($DatosFormulario["rol"] ?? 0);
                            $stmtInsRol = $db->prepare("INSERT INTO usuario_rol (id_usuario, id_rol) VALUES (?, ?)");
                            if (!$stmtInsRol) {
                                $db->rollback();
                                $consulta = false;
                                $mensaje = "Error al preparar la asignación de rol.";
                            } else {
                                $stmtInsRol->bind_param("ii", $idUsuario, $idRol);
                                if (!$stmtInsRol->execute()) {
                                    $stmtInsRol->close();
                                    $db->rollback();
                                    $consulta = false;
                                    $mensaje = "Error al asignar el rol al usuario.";
                                } else {
                                    $stmtInsRol->close();
                                    $db->commit();
                                    $db->autocommit(true);
                                    $consulta = true;
                                }
                            }
                        }
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
        <title><?= Constantes::NOMBRE_SISTEMA; ?> - Actualizar Usuario</title>
    </head>
    <body>
        <?php include_once '../gui/navbar.php'; ?>

        <div class="container">
            <div class="card">
                <div class="card-header">
                    <h3>Actualizar Usuario</h3>
                </div>
                <div class="card-body">
                    <?php if ($consulta) { ?>
                        <div class="alert alert-success" role="alert">
                            Operaci&oacute;n realizada con &eacute;xito.
                        </div>
                    <?php } ?>   
                    <?php if (!$consulta) { ?>
                        <div class="alert alert-danger" role="alert">
                            Ha ocurrido un error. <?= $mensaje; ?>
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
