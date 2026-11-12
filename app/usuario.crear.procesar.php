<?php
include_once '../lib/ControlAcceso.Class.php';
ControlAcceso::requierePermiso(PermisosSistema::PERMISO_USUARIOS);
ControlAcceso::requiereCsrf();
include_once '../modelo/BDConexion.Class.php';
include_once '../modelo/Rol.Class.php';
$DatosFormulario = $_POST;

$db = BDConexion::getInstancia();
$nombreInput = trim($DatosFormulario["nombre"] ?? '');
$emailPrincipalInput = trim($DatosFormulario["mail"] ?? '');
$idRol = intval($DatosFormulario["rol"] ?? 0);
$rol = $idRol > 0 ? new Rol($idRol) : null;
$nombreRolSeleccionado = $rol ? $rol->getNombre() : '';
$esRolVinculacion = ($idRol === 11 || stripos($nombreRolSeleccionado, 'Vinculaci') !== false);

// Recolectar todos los correos ingresados (soportando lista separada por comas/punto y coma o campos adicionales)
$emailsIngresados = [];
foreach (preg_split('/[,;]+/', $emailPrincipalInput) as $parte) {
    $parteLimpia = strtolower(trim($parte));
    if ($parteLimpia !== '') {
        $emailsIngresados[] = $parteLimpia;
    }
}

if ($esRolVinculacion && !empty($DatosFormulario["mail_adicional"]) && is_array($DatosFormulario["mail_adicional"])) {
    foreach ($DatosFormulario["mail_adicional"] as $mailExtra) {
        foreach (preg_split('/[,;]+/', (string) $mailExtra) as $parte) {
            $parteLimpia = strtolower(trim($parte));
            if ($parteLimpia !== '') {
                $emailsIngresados[] = $parteLimpia;
            }
        }
    }
}

$mensaje = '';
$consulta = FALSE;
$validacionPreviaOk = true;

// Validar formato de correo institucional (@uarg.unpa.edu.ar) y cantidad mínima para Vinculación Académica
$patronInstitucional = '/^[a-zA-Z0-9._%+-]+@uarg\.unpa\.edu\.ar$/';

if ($esRolVinculacion) {
    if (count($emailsIngresados) < 2) {
        $validacionPreviaOk = false;
        $mensaje = "El rol <b>Vinculaci&oacute;n Acad&eacute;mica</b> representa a Secretar&iacute;a y Vinculaci&oacute;n, por lo que debe ingresar <b>al menos 2 correos institucionales</b> (@uarg.unpa.edu.ar).";
    } elseif (count($emailsIngresados) !== count(array_unique($emailsIngresados))) {
        $validacionPreviaOk = false;
        $mensaje = "Los correos institucionales ingresados para <b>Vinculaci&oacute;n Acad&eacute;mica</b> deben ser distintos entre s&iacute;.";
    } else {
        foreach ($emailsIngresados as $correoEval) {
            if (!preg_match($patronInstitucional, $correoEval)) {
                $validacionPreviaOk = false;
                $correoEsc = e($correoEval);
                $mensaje = "El correo <b>{$correoEsc}</b> no es un correo institucional v&aacute;lido (debe finalizar en <code>@uarg.unpa.edu.ar</code>).";
                break;
            }
        }
    }
} else {
    if (empty($emailsIngresados)) {
        $validacionPreviaOk = false;
        $mensaje = "Debe ingresar un correo electr&oacute;nico v&aacute;lido.";
    }
}

$emailInput = implode(', ', array_unique($emailsIngresados));

if ($validacionPreviaOk) {
    // Verificamos si ya existe el nombre de usuario o alguno de los correos en la base de datos
    $resTodos = $db->query("SELECT id, nombre, email FROM usuario");
    if (!$resTodos) {
        $mensaje = 'Error al realizar petici&oacute;n a la Base de Datos';
        $consulta = FALSE;
    } else {
        $existeNombre = false;
        $emailColisionado = '';

        while ($registro = $resTodos->fetch_assoc()) {
            if (strcasecmp(trim($registro['nombre']), $nombreInput) === 0) {
                $existeNombre = true;
            }
            $emailsExistentesFila = array_filter(array_map('strtolower', array_map('trim', preg_split('/[,;]+/', (string) $registro['email']))));
            foreach ($emailsIngresados as $correoNuevo) {
                if (in_array($correoNuevo, $emailsExistentesFila, true)) {
                    $emailColisionado = $correoNuevo;
                    break;
                }
            }
        }

        if ($existeNombre && $emailColisionado !== '') {
            $nombreEsc = e($nombreInput);
            $emailEsc = e($emailColisionado);
            $mensaje = "El nombre de usuario: <b>{$nombreEsc}</b> y el email: <b>{$emailEsc}</b> ya existen, por favor ingrese otro nombre de usuario y otro email.";
            $consulta = FALSE;
        } elseif ($existeNombre) {
            $nombreEsc = e($nombreInput);
            $mensaje = "El nombre de usuario: <b>{$nombreEsc}</b> ya existe, por favor ingrese otro nombre de usuario.";
            $consulta = FALSE;
        } elseif ($emailColisionado !== '') {
            $emailEsc = e($emailColisionado);
            $mensaje = "El email: <b>{$emailEsc}</b> ya existe, por favor ingrese otro email";
            $consulta = FALSE;
        } else {
            // No se encontraron coincidencias en la BD, por lo tanto se procede con la inserción
            if ($nombreRolSeleccionado == "Profesor") {
                $_SESSION["usuarioNombre"] = $nombreInput;
                $_SESSION["usuarioEmail"] = $emailInput;
                $_SESSION["usuarioRol"] = $idRol;
                header("Location: ../vista/profesor.crear.php");
                exit();
            }

            // Iniciamos transacción para insertar el usuario y su rol en la BD
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
