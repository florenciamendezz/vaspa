<?php

/*
 * Nota: Solo se permitira modificar el rol en el caso de que el usuario a modificarle
 * los datos no sea un profesor.
 */

include_once '../lib/ControlAcceso.Class.php';
ControlAcceso::requierePermiso(PermisosSistema::PERMISO_USUARIOS);
include_once '../modelo/Usuario.Class.php';
include_once '../modelo/ColeccionRoles.php';
$id = $_GET["id"];
$Usuario = new Usuario($id);

// obtenemos el rol del Usuario
$rolUsuario = $Usuario->getRoles()[0]->getNombre();

$habilitarRoles; // boolean para mostrar o no los roles (Si es profesor no se permitira cambiar roles)
if ($rolUsuario == "Profesor"){
    $habilitarRoles = FALSE;
} else {
    $habilitarRoles = TRUE;
    $RolesSistema = new ColeccionRoles();
}

// Separar los correos en caso de que el usuario tenga múltiples correos registrados (ej. Vinculación Académica)
$emailsActuales = array_values(array_filter(array_map('trim', preg_split('/[,;]+/', (string) $Usuario->getEmail()))));
$emailPrincipal = $emailsActuales[0] ?? '';
$emailSecundario = $emailsActuales[1] ?? '';
$emailsAdicionalesRestantes = array_slice($emailsActuales, 2);

?>
<html>
    <head>
        <meta charset="UTF-8">
        <link rel="stylesheet" href="../lib/bootstrap-4.1.1-dist/css/bootstrap.css" />
        <link rel="stylesheet" href="../lib/open-iconic-master/font/css/open-iconic-bootstrap.css" />
        <script type="text/javascript" src="../lib/JQuery/jquery-3.3.1.js"></script>
        <script type="text/javascript" src="../lib/bootstrap-4.1.1-dist/js/bootstrap.min.js"></script>
        <title><?= Constantes::NOMBRE_SISTEMA; ?> - Actualizar Usuario</title>
        
        <!-- Google Fonts Outfit y Estilos Premium -->
        <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="../lib/css/premium.css" />
    </head>
    <body>
        <?php include_once '../gui/navbar.php'; ?>
        <div class="container">
            <form action="usuario.modificar.procesar.php" method="post" id="formModificarUsuario">
                <?= ControlAcceso::campoCsrf(); ?>
                <div class="card card-premium">
                    <div class="card-header card-header-premium">
                        <h3>Actualizar Usuario</h3>
                        <p class="mb-0 mt-2 text-white" style="opacity: 0.85;">
                            Complete los campos a continuaci&oacute;n. 
                            Luego, presione el bot&oacute;n <b>Confirmar</b>.<br />
                            Si desea cancelar, presione el bot&oacute;n <b>Cancelar</b>.
                        </p>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="inputNombre">Nombre</label>
                            <input type="text" name="nombre" class="form-control" id="inputNombre" value="<?= e($Usuario->getNombre()); ?>" placeholder="Ingrese el nombre del usuario" required="">
                        </div>
                        <div class="form-group">
                            <label for="inputEmail" id="labelEmailPrincipal">Email</label>
                            <input type="email" name="email" class="form-control" id="inputEmail" value="<?= e($emailPrincipal); ?>" placeholder="Ingrese el email del usuario" required="" pattern="^[a-z]+@uarg.unpa.edu.ar$" title="nombreusuario@uarg.unpa.edu.ar">
                            <small id="ayudaEmailPrincipal" class="form-text text-muted" style="display: none;">
                                Primer correo institucional (ej. Secretar&iacute;a Acad&eacute;mica: <code>secretaria@uarg.unpa.edu.ar</code>).
                            </small>
                        </div>
                        <div id="contenedorEmailsVinculacion" style="display: none;">
                            <div class="alert alert-info py-2 mb-3">
                                <span class="oi oi-info mr-1"></span>
                                El rol <b>Vinculaci&oacute;n Acad&eacute;mica</b> representa a <b>Secretar&iacute;a Acad&eacute;mica</b> y <b>Vinculaci&oacute;n Acad&eacute;mica</b>, por lo que requiere al menos <b>2 correos institucionales</b> (<code>@uarg.unpa.edu.ar</code>).
                            </div>
                            <div class="form-group">
                                <label for="inputEmail2">Segundo Email Institucional (Vinculaci&oacute;n / Secretar&iacute;a)</label>
                                <input type="email" name="email_adicional[]" class="form-control input-mail-vinculacion" id="inputEmail2" value="<?= e($emailSecundario); ?>" placeholder="Ingrese el segundo email institucional" pattern="^[a-zA-Z0-9._%+-]+@uarg.unpa.edu.ar$" title="correo@uarg.unpa.edu.ar">
                            </div>
                            <div id="listaEmailsExtra">
                                <?php foreach ($emailsAdicionalesRestantes as $indiceExtra => $correoExtra) { ?>
                                    <div class="form-group grupo-email-extra">
                                        <label>Email Institucional Adicional #<?= ($indiceExtra + 3); ?></label>
                                        <div class="input-group">
                                            <input type="email" name="email_adicional[]" class="form-control input-mail-vinculacion" value="<?= e($correoExtra); ?>" placeholder="Ingrese otro email institucional (@uarg.unpa.edu.ar)" required pattern="^[a-zA-Z0-9._%+-]+@uarg.unpa.edu.ar$" title="correo@uarg.unpa.edu.ar">
                                            <div class="input-group-append">
                                                <button type="button" class="btn btn-outline-danger btn-quitar-email" title="Quitar este correo"><span class="oi oi-trash"></span></button>
                                            </div>
                                        </div>
                                    </div>
                                <?php } ?>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary mb-2" id="btnAgregarEmailInstitucional">
                                <span class="oi oi-plus"></span> Agregar otro email institucional
                            </button>
                        </div>

                        <input type="hidden" name="id" class="form-control" id="id" value="<?= $Usuario->getId(); ?>" >
                        
                        <?php if ($habilitarRoles){?>
                        
                        <hr />
                        <h3>Roles</h3>
                        <?php foreach ($RolesSistema->getRoles() as $RolSistema) {
                                if ($RolSistema->getNombre() != "Profesor"){?>
                                    <div class="custom-control custom-radio">
                                    <input class="custom-control-input radio-rol-usuario" type="radio" value="<?= $RolSistema->getId(); ?>" data-rol-nombre="<?= e($RolSistema->getNombre()); ?>" id="<?= $RolSistema->getId(); ?>" name="rol" required="" 
                                          <?php echo $Usuario->buscarRolPorId($RolSistema->getId()) ? "checked" : ""; ?> />
                                    <label class="custom-control-label" for="<?= $RolSistema->getId(); ?>"><?= $RolSistema->getNombre();?></label>
                                    </div>
                        <?php } // fin if
                        } // fin foreach
                        ?>
                        <?php } // fin if ?>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-outline-success">
                            <span class="oi oi-check"></span>
                            Confirmar
                        </button>
                        <a href="usuarios.php">
                            <button type="button" class="btn btn-outline-danger">
                                <span class="oi oi-x"></span>
                                Cancelar
                            </button>
                        </a>
                    </div>
                </div>
            </form>
        </div>
        <?php include_once '../gui/footer.php'; ?>
        <script type="text/javascript">
            $(document).ready(function () {
                var rolInicial = <?= json_encode($rolUsuario); ?>;

                function actualizarModoVinculacion() {
                    var rolSeleccionado = $('input[name="rol"]:checked');
                    var nombreRol = rolSeleccionado.length ? (rolSeleccionado.data('rol-nombre') || '') : rolInicial;
                    var idRol = rolSeleccionado.length ? rolSeleccionado.val() : '';
                    var esVinculacion = (idRol == '11' || nombreRol.indexOf('Vinculaci') !== -1);

                    if (esVinculacion) {
                        $('#contenedorEmailsVinculacion').slideDown(150);
                        $('#ayudaEmailPrincipal').show();
                        $('#labelEmailPrincipal').text('Primer Email Institucional (Secretaría / Vinculación)');
                        $('#inputEmail').attr('pattern', '^[a-zA-Z0-9._%+-]+@uarg.unpa.edu.ar$');
                        $('#inputEmail2').prop('required', true);
                    } else {
                        $('#contenedorEmailsVinculacion').slideUp(150);
                        $('#ayudaEmailPrincipal').hide();
                        $('#labelEmailPrincipal').text('Email');
                        $('#inputEmail').attr('pattern', '^[a-z]+@uarg.unpa.edu.ar$');
                        $('#inputEmail2').prop('required', false).val('');
                        $('#listaEmailsExtra').empty();
                    }
                }

                $('input[name="rol"]').on('change', actualizarModoVinculacion);
                actualizarModoVinculacion();

                $('#btnAgregarEmailInstitucional').on('click', function () {
                    var idx = $('#listaEmailsExtra .grupo-email-extra').length + 3;
                    var html = '<div class="form-group grupo-email-extra">' +
                        '<label>Email Institucional Adicional #' + idx + '</label>' +
                        '<div class="input-group">' +
                        '<input type="email" name="email_adicional[]" class="form-control input-mail-vinculacion" placeholder="Ingrese otro email institucional (@uarg.unpa.edu.ar)" required pattern="^[a-zA-Z0-9._%+-]+@uarg.unpa.edu.ar$" title="correo@uarg.unpa.edu.ar">' +
                        '<div class="input-group-append">' +
                        '<button type="button" class="btn btn-outline-danger btn-quitar-email" title="Quitar este correo"><span class="oi oi-trash"></span></button>' +
                        '</div></div></div>';
                    $('#listaEmailsExtra').append(html);
                });

                $(document).on('click', '.btn-quitar-email', function () {
                    $(this).closest('.grupo-email-extra').remove();
                });
            });
        </script>
    </body>
</html>
