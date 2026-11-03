<?php
include_once '../lib/ControlAcceso.Class.php';
ControlAcceso::requierePermiso(PermisosSistema::PERMISO_USUARIOS);
ControlAcceso::requiereCsrf();
include_once '../modelo/BDConexion.Class.php';
include_once '../modelo/Rol.Class.php';
$DatosFormulario = $_POST;

//// Comprobamos si el Rol seleccionado es de Profesor, de serlo redireccionamos
//$idRol = $DatosFormulario["rol"]; //obtenemos el id del rol
//$rol = new Rol($idRol); // creamos objeto rol
//
//if ($rol->getNombre() == "Profesor"){
//    $_SESSION["usuarioNombre"] = $DatosFormulario["nombre"];
//    $_SESSION["usuarioEmail"] = $DatosFormulario["mail"];
//    $_SESSION["usuarioRol"] = $DatosFormulario["rol"];
//    header("Location: ../vista/profesor.crear.php");
//    exit();
//}

// chequeamos si ya existe tanto el nombre de Usuario como el email.
$db = BDConexion::getInstancia();
$nombreInput = trim($DatosFormulario["nombre"] ?? '');
$emailInput = trim($DatosFormulario["mail"] ?? '');

$stmtCheck = $db->prepare("SELECT * FROM usuario WHERE nombre = ? OR email = ?");
if ($stmtCheck) {
    $stmtCheck->bind_param("ss", $nombreInput, $emailInput);
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

        // Comprobamos si el Rol seleccionado es de Profesor, de serlo redireccionamos
        $idRol = intval($DatosFormulario["rol"] ?? 0); //obtenemos el id del rol
        $rol = new Rol($idRol); // creamos objeto rol

        if ($rol->getNombre() == "Profesor") {
            $_SESSION["usuarioNombre"] = $nombreInput;
            $_SESSION["usuarioEmail"] = $emailInput;
            $_SESSION["usuarioRol"] = $idRol;
            header("Location: ../vista/profesor.crear.php");
            exit();
        }

        // iniciamos transaccion para insertar el usuario y su rol en la BD
        $db->autocommit(false);
        $db->begin_transaction();

        $stmtIns = $db->prepare("INSERT INTO usuario (id, nombre, email) VALUES (NULL, ?, ?)");
        if (!$stmtIns) {
            $db->rollback();
            $consulta = false;
            $mensaje = "Error al preparar la creación del usuario.";
        } else {
            $stmtIns->bind_param("ss", $nombreInput, $emailInput);
            if (!$stmtIns->execute()) {
                $stmtIns->close();
                $db->rollback();
                $consulta = false;
                $mensaje = "Error al insertar el usuario.";
            } else {
                $idUsuario = (int) $db->insert_id;
                $stmtIns->close();

                $stmtRol = $db->prepare("INSERT INTO usuario_rol (id_usuario, id_rol) VALUES (?, ?)");
                if (!$stmtRol) {
                    $db->rollback();
                    $consulta = false;
                    $mensaje = "Error al preparar la asignación de rol.";
                } else {
                    $stmtRol->bind_param("ii", $idUsuario, $idRol);
                    if (!$stmtRol->execute()) {
                        $stmtRol->close();
                        $db->rollback();
                        $consulta = false;
                        $mensaje = "Error al asignar el rol al usuario.";
                    } else {
                        $stmtRol->close();
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
        <title><?= Constantes::NOMBRE_SISTEMA; ?> - Crear Usuario</title>
    </head>
    <body>
        <?php include_once '../gui/navbar.php'; ?>

        <div class="container">
            <p></p>
            <div class="card">
                <div class="card-header">
                    <h3>Crear Usuario</h3>
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
