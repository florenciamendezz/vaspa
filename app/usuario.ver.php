<?php
include_once '../lib/ControlAcceso.Class.php';
ControlAcceso::requierePermiso(PermisosSistema::PERMISO_USUARIOS);
include_once '../modelo/Usuario.Class.php';

$Usuario = new Usuario($_GET["id"]);
$correosLista = array_values(array_filter(array_map('trim', preg_split('/[,;]+/', (string) $Usuario->getEmail()))));
?>
<html>
    <head>
        <meta charset="UTF-8">
        <link rel="stylesheet" href="../lib/bootstrap-4.1.1-dist/css/bootstrap.css" />
        <link rel="stylesheet" href="../lib/open-iconic-master/font/css/open-iconic-bootstrap.css" />
        <script type="text/javascript" src="../lib/JQuery/jquery-3.3.1.js"></script>
        <script type="text/javascript" src="../lib/bootstrap-4.1.1-dist/js/bootstrap.min.js"></script>
        <title><?= Constantes::NOMBRE_SISTEMA; ?> - Propiedades del Usuario</title>
        
        <!-- Google Fonts Outfit y Estilos Premium -->
        <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="../lib/css/premium.css" />
    </head>
    <body>
        <?php include_once '../gui/navbar.php'; ?>
        <div class="container">
            <p></p>
            <div class="card card-premium">
                <div class="card-header card-header-premium">
                    <h3 class="mb-0">Propiedades del Usuario</h3>
                </div>
                <div class="card-body">
                    <h4 class="card-text">Nombre</h4>
                    <p><?= e($Usuario->getNombre()); ?></p>
                    <hr />
                    <h4 class="card-text"><?= count($correosLista) > 1 ? 'Emails Institucionales' : 'Email'; ?></h4>
                    <?php if (count($correosLista) > 1) { ?>
                        <ul class="mb-2">
                            <?php foreach ($correosLista as $correoItem) { ?>
                                <li><?= e($correoItem); ?></li>
                            <?php } ?>
                        </ul>
                    <?php } else { ?>
                        <p><?= e($Usuario->getEmail()); ?></p>
                    <?php } ?>
                    <hr />
                    <h4 class="card-text">Roles</h4>
                    <?php foreach ($Usuario->getRoles() as $Rol) { ?>
                        <p><?= e($Rol->getNombre()); ?></p>
                    <?php } ?> 

                    <hr />
                    <h5 class="card-text">Opciones</h5>
                    <a href="usuarios.php">
                        <button type="button" class="btn btn-outline-danger">
                            <span class="oi oi-account-logout"></span> Salir
                        </button>
                    </a>
                </div>
            </div>
        </div>
        <?php include_once '../gui/footer.php'; ?>
    </body>
</html>
