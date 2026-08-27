<?php
include_once '../lib/ControlAcceso.Class.php';
ControlAcceso::requierePermiso(PermisosSistema::PERMISO_ROLES);
include_once '../modelo/BDConexion.Class.php';
$DatosFormulario = $_POST;
$nombreRol = trim($DatosFormulario["nombre"]);
$mensaje = '';
$consulta = false;

// Comprobar si ya existe un rol con el mismo nombre
$sql = "SELECT * FROM rol WHERE nombre LIKE '{$nombreRol}'";
$resultado = BDConexion::getInstancia()->query($sql);

if ($resultado && $resultado->num_rows > 0) {
    $mensaje = "El rol <b>{$nombreRol}</b> ya existe, por favor ingrese otro nombre.";
    $consulta = false;
} else {
    BDConexion::getInstancia()->autocommit(false);
    BDConexion::getInstancia()->begin_transaction();

    $query = "INSERT INTO rol "
            . "VALUES (null,'{$nombreRol}')";
    $consulta = BDConexion::getInstancia()->query($query);

    if (!$consulta) {
        BDConexion::getInstancia()->rollback();
        $mensaje = "Error al insertar el rol en la base de datos.";
    } else {
        $idRol = BDConexion::getInstancia()->insert_id;
        if (isset($DatosFormulario["permiso"]) && is_array($DatosFormulario["permiso"])) {
            foreach ($DatosFormulario["permiso"] as $idPermiso) {
                $query = "INSERT INTO rol_permiso "
                        . "VALUES ({$idRol}, {$idPermiso})";
                $consulta = BDConexion::getInstancia()->query($query);
                if (!$consulta) {
                    BDConexion::getInstancia()->rollback();
                    $mensaje = "Error al asignar los permisos al rol.";
                    break;
                }
            }
        }
        if ($consulta) {
            BDConexion::getInstancia()->commit();
            BDConexion::getInstancia()->autocommit(true);
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
                <title><?php echo Constantes::NOMBRE_SISTEMA; ?> - Crear Rol</title>

    </head>
    <body>
        <?php include_once '../gui/navbar.php'; ?>
        <div class="container">
            <p></p>
            <div class="card">
                <div class="card-header">
                    <h3>Crear Rol</h3>
                </div>
                <div class="card-body">
                    <?php if ($consulta) { ?>
                        <div class="alert alert-success" role="alert">
                            Operaci&oacute;n realizada con &eacute;xito.
                        </div>
                    <?php } ?>   
                    <?php if (!$consulta) { ?>
                        <div class="alert alert-danger" role="alert">
                            Ha ocurrido un error. <?= !empty($mensaje) ? $mensaje : ''; ?>
                        </div>
                    <?php } ?>
                    <hr />
                    <h5 class="card-text">Opciones</h5>
                    <a href="roles.php">
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