<?php
include_once '../lib/ControlAcceso.Class.php';
ControlAcceso::requierePermiso(PermisosSistema::PERMISO_ROLES);
ControlAcceso::requiereCsrf();
include_once '../modelo/BDConexion.Class.php';
$DatosFormulario = $_POST;
$nombreRol = trim($DatosFormulario["nombre"] ?? '');
$mensaje = '';
$consulta = false;

// Comprobar si ya existe un rol con el mismo nombre
$db = BDConexion::getInstancia();
$stmtCheck = $db->prepare("SELECT id FROM rol WHERE nombre = ?");
if ($stmtCheck) {
    $stmtCheck->bind_param("s", $nombreRol);
    $stmtCheck->execute();
    $resultado = $stmtCheck->get_result();
    $stmtCheck->close();
} else {
    $resultado = false;
}

if ($resultado && $resultado->num_rows > 0) {
    $mensaje = "El rol <b>" . e($nombreRol) . "</b> ya existe, por favor ingrese otro nombre.";
    $consulta = false;
} else {
    $db->autocommit(false);
    $db->begin_transaction();

    $stmtInsert = $db->prepare("INSERT INTO rol (id, nombre) VALUES (null, ?)");
    if ($stmtInsert) {
        $stmtInsert->bind_param("s", $nombreRol);
        $consulta = $stmtInsert->execute();
        $idRol = $stmtInsert->insert_id;
        $stmtInsert->close();
    } else {
        $consulta = false;
    }

    if (!$consulta) {
        $db->rollback();
        $mensaje = "Error al insertar el rol en la base de datos.";
    } else {
        if (isset($DatosFormulario["permiso"]) && is_array($DatosFormulario["permiso"])) {
            $stmtPerm = $db->prepare("INSERT INTO rol_permiso (id_rol, id_permiso) VALUES (?, ?)");
            foreach ($DatosFormulario["permiso"] as $idPermiso) {
                $idPermInt = intval($idPermiso);
                if ($stmtPerm) {
                    $stmtPerm->bind_param("ii", $idRol, $idPermInt);
                    $consulta = $stmtPerm->execute();
                    if (!$consulta) {
                        $db->rollback();
                        $mensaje = "Error al asignar los permisos al rol.";
                        break;
                    }
                }
            }
            if ($stmtPerm) {
                $stmtPerm->close();
            }
        }
        if ($consulta) {
            $db->commit();
            $db->autocommit(true);
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