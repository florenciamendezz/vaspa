<?php
include_once '../lib/ControlAcceso.Class.php';
ControlAcceso::requierePermiso(PermisosSistema::PERMISO_PERMISOS);
include_once '../modelo/Permiso.Class.php';

$Permiso = new Permiso($_GET["id"]);
?>


<html>
    <head>
        <meta charset="UTF-8">
        <link rel="stylesheet" href="../lib/bootstrap-4.1.1-dist/css/bootstrap.css" />
        <link rel="stylesheet" href="../lib/open-iconic-master/font/css/open-iconic-bootstrap.css" />
        <script type="text/javascript" src="../lib/JQuery/jquery-3.3.1.js"></script>
        <script type="text/javascript" src="../lib/bootstrap-4.1.1-dist/js/bootstrap.min.js"></script>
       <title><?php echo Constantes::NOMBRE_SISTEMA; ?> - Propiedades del Permiso</title>
        
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
                    <h3 class="mb-0">Propiedades del Permiso</h3>
                </div>
                <div class="card-body">
                    <h4 class="card-text">Nombre</h4>
                    <p> <?= $Permiso->getNombre(); ?></p>
                    <hr />
                    <h5 class="card-text">Opciones</h5>
                     <a href="permisos.php">
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
