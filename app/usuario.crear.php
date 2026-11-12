<?php
include_once '../lib/ControlAcceso.Class.php';
ControlAcceso::requierePermiso(PermisosSistema::PERMISO_USUARIOS);
include_once '../modelo/ColeccionRoles.php';
$Roles = new ColeccionRoles();
?>
<html>
    <head>
        <meta charset="UTF-8">
        <link rel="stylesheet" href="../lib/bootstrap-4.1.1-dist/css/bootstrap.css" />
        <link rel="stylesheet" href="../lib/open-iconic-master/font/css/open-iconic-bootstrap.css" />
        <script type="text/javascript" src="../lib/JQuery/jquery-3.3.1.js"></script>
        <script type="text/javascript" src="../lib/bootstrap-4.1.1-dist/js/bootstrap.min.js"></script>
        <title><?= Constantes::NOMBRE_SISTEMA; ?> - Crear Usuario</title>
        
        <!-- Google Fonts Outfit y Estilos Premium -->
        <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="../lib/css/premium.css" />
    </head>
    <body>
        <?php include_once '../gui/navbar.php'; ?>
        <div class="container">
            <form action="usuario.crear.procesar.php" method="post" id="formCrearUsuario">
                <?= ControlAcceso::campoCsrf(); ?>
                <div class="card card-premium">
                    <div class="card-header card-header-premium">
                        <h3>Crear Usuario</h3>
                        <p class="mb-0 mt-2 text-white" style="opacity: 0.85;">
                            Complete los campos a continuaci&oacute;n. 
                            Luego, presione el bot&oacute;n <b>Confirmar</b>.<br />
                            Si desea cancelar, presione el bot&oacute;n <b>Cancelar</b>.
                        </p>
                    </div>
                    <div class="card-body">
                        <h4>Propiedades</h4>
                        <div class="form-group">
                            <label for="inputNombre">Nombre</label>
                            <input type="text" name="nombre" class="form-control" id="inputNombre" placeholder="Ingrese el nombre del Usuario" required="">
                        </div>
                        <div class="form-group">
                            <label for="inputMail" id="labelMailPrincipal">Email</label>
                            <input type="email" name="mail" class="form-control" id="inputMail" placeholder="Ingrese el email del Usuario" required="" pattern="^[a-z]+@uarg.unpa.edu.ar$" title="nombreusuario@uarg.unpa.edu.ar">
                            <small id="ayudaMailPrincipal" class="form-text text-muted" style="display: none;">
                                Primer correo institucional (ej. Secretar&iacute;a Acad&eacute;mica: <code>secretaria@uarg.unpa.edu.ar</code>).
                            </small>
                        </div>
                        <div id="contenedorEmailsVinculacion" style="display: none;">
                            <div class="alert alert-info py-2 mb-3">
                                <span class="oi oi-info mr-1"></span>
                                El rol <b>Vinculaci&oacute;n Acad&eacute;mica</b> representa a <b>Secretar&iacute;a Acad&eacute;mica</b> y <b>Vinculaci&oacute;n Acad&eacute;mica</b>, por lo que requiere al menos <b>2 correos institucionales</b> (<code>@uarg.unpa.edu.ar</code>).
                            </div>
                            <div class="form-group">
                                <label for="inputMail2">Segundo Email Institucional (Vinculaci&oacute;n / Secretar&iacute;a)</label>
                                <input type="email" name="mail_adicional[]" class="form-control input-mail-vinculacion" id="inputMail2" placeholder="Ingrese el segundo email institucional" pattern="^[a-zA-Z0-9._%+-]+@uarg.unpa.edu.ar$" title="correo@uarg.unpa.edu.ar">
                            </div>
                            <div id="listaEmailsExtra"></div>
                            <button type="button" class="btn btn-sm btn-outline-primary mb-2" id="btnAgregarEmailInstitucional">
                                <span class="oi oi-plus"></span> Agregar otro email institucional
                            </button>
                        </div>
                        <hr />
                        <h4>Roles</h4>
                        <?php foreach ($Roles->getRoles() as $Rol) { ?>
                            <div class="custom-control custom-radio">
                                <input class="custom-control-input radio-rol-usuario" type="radio" value="<?= $Rol->getId(); ?>" data-rol-nombre="<?= e($Rol->getNombre()); ?>" id="<?= $Rol->getId(); ?>" name="rol" required="" />
                                <label class="custom-control-label" for="<?= $Rol->getId(); ?>">
                                    <?php 
                                    if($Rol->getNombre() == "Profesor"){
                                        echo 'Profesor Responsable';
                                    } else {
                                        echo $Rol->getNombre();
                                    }
                                    ?>
                                </label>
                            </div>
                        <?php } ?>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-outline-success">
                            <span class="oi oi-check"></span> Confirmar
                        </button>
                        <a href="usuarios.php">
                            <button type="button" class="btn btn-outline-danger">
                                <span class="oi oi-x"></span> Cancelar
                            </button>
                        </a>
                    </div>
                </div>
            </form>
        </div>
        <?php include_once '../gui/footer.php'; ?>
        <script type="text/javascript">
            $(document).ready(function () {
                function actualizarModoVinculacion() {
                    var rolSeleccionado = $('input[name="rol"]:checked');
                    var nombreRol = rolSeleccionado.data('rol-nombre') || '';
                    var idRol = rolSeleccionado.val();
                    var esVinculacion = (idRol == '11' || nombreRol.indexOf('Vinculaci') !== -1);

                    if (esVinculacion) {
                        $('#contenedorEmailsVinculacion').slideDown(150);
                        $('#ayudaMailPrincipal').show();
                        $('#labelMailPrincipal').text('Primer Email Institucional (Secretaría / Vinculación)');
                        $('#inputMail').attr('pattern', '^[a-zA-Z0-9._%+-]+@uarg.unpa.edu.ar$');
                        $('#inputMail2').prop('required', true);
                    } else {
                        $('#contenedorEmailsVinculacion').slideUp(150);
                        $('#ayudaMailPrincipal').hide();
                        $('#labelMailPrincipal').text('Email');
                        $('#inputMail').attr('pattern', '^[a-z]+@uarg.unpa.edu.ar$');
                        $('#inputMail2').prop('required', false).val('');
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
                        '<input type="email" name="mail_adicional[]" class="form-control input-mail-vinculacion" placeholder="Ingrese otro email institucional (@uarg.unpa.edu.ar)" required pattern="^[a-zA-Z0-9._%+-]+@uarg.unpa.edu.ar$" title="correo@uarg.unpa.edu.ar">' +
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
