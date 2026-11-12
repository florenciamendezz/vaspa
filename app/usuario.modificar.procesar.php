<?php
include_once '../lib/ControlAcceso.Class.php';
ControlAcceso::requierePermiso(PermisosSistema::PERMISO_USUARIOS);
ControlAcceso::requiereCsrf();
include_once '../modelo/BDConexion.Class.php';
include_once '../modelo/Usuario.Class.php';
include_once '../modelo/Rol.Class.php';
$DatosFormulario = $_POST;
$db = BDConexion::getInstancia();
$idUsuario = intval($DatosFormulario["id"] ?? 0);
$nombreInput = trim($DatosFormulario["nombre"] ?? '');
$emailPrincipalInput = trim($DatosFormulario["email"] ?? '');

$Usuario = new Usuario($idUsuario);
$rolesActuales = $Usuario->getRoles();
$rolUsuarioActual = (!empty($rolesActuales) && isset($rolesActuales[0])) ? $rolesActuales[0]->getNombre() : '';

$idRolSeleccionado = intval($DatosFormulario["rol"] ?? 0);
$rolNuevoObj = $idRolSeleccionado > 0 ? new Rol($idRolSeleccionado) : null;
$nombreRolFinal = ($rolUsuarioActual === "Profesor") ? "Profesor" : ($rolNuevoObj ? $rolNuevoObj->getNombre() : $rolUsuarioActual);
$esRolVinculacion = ($idRolSeleccionado === 11 || stripos($nombreRolFinal, 'Vinculaci') !== false);

// Recolectar todos los correos ingresados
$emailsIngresados = [];
foreach (preg_split('/[,;]+/', $emailPrincipalInput) as $parte) {
    $parteLimpia = strtolower(trim($parte));
    if ($parteLimpia !== '') {
        $emailsIngresados[] = $parteLimpia;
    }
}

if ($esRolVinculacion && !empty($DatosFormulario["email_adicional"]) && is_array($DatosFormulario["email_adicional"])) {
    foreach ($DatosFormulario["email_adicional"] as $mailExtra) {
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
    // Chequeamos si ya existe tanto el nombre de Usuario como alguno de los emails en "otros" usuarios del Sistema.
    $stmtCheck = $db->prepare("SELECT id, nombre, email FROM usuario WHERE id <> ?");
    if ($stmtCheck) {
        $stmtCheck->bind_param("i", $idUsuario);
        $stmtCheck->execute();
        $resultado = $stmtCheck->get_result();
        $stmtCheck->close();
    } else {
        $resultado = false;
    }

    if (!$resultado) {
        $mensaje = 'Error al realizar petici&oacute;n a la Base de Datos';
        $consulta = FALSE;
    } else {
        $existeNombre = false;
        $emailColisionado = '';

        while ($registro = $resultado->fetch_assoc()) {
            if (strcasecmp(trim($registro["nombre"]), $nombreInput) === 0) {
                $existeNombre = true;
            }
            $emailsExistentesFila = array_filter(array_map('strtolower', array_map('trim', preg_split('/[,;]+/', (string) $registro["email"]))));
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
            // No se encontraron coincidencias en otros usuarios, procedemos con la actualización
            if ($rolUsuarioActual == "Profesor") {
                // Es profesor --> hay que actualizar el email en la tabla profesor
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
                // No es profesor, procedemos a actualizar los campos
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
