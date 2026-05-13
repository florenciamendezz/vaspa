<?php
include_once '../controlSistema/ManejadorCarrera.php';
include_once '../lib/ControlAcceso.Class.php';

// Validamos permisos: Carreras o VA
$UsuarioSes = $_SESSION['usuario'];
$perfil = "";
if (isset($UsuarioSes->roles[0])) {
    $perfil = $UsuarioSes->roles[0]->nombre;
}

if (!ControlAcceso::verificaPermiso(PermisosSistema::PERMISO_CARRERAS, $UsuarioSes) && $perfil != PermisosSistema::ROL_VINCULACION_ACADEMICA) {
    ControlAcceso::requierePermiso(PermisosSistema::PERMISO_CARRERAS);
}

include_once '../lib/Constantes.Class.php';

$ManejadorCarrera = new ManejadorCarrera();
$Carreras = $ManejadorCarrera->getColeccion();
?>

<html>
    <head>
        <meta charset="UTF-8">
        <link rel="stylesheet" href="../lib/bootstrap-4.1.1-dist/css/bootstrap.css" />
        <link rel="stylesheet" href="../lib/open-iconic-master/font/css/open-iconic-bootstrap.css" />
        <link rel="stylesheet" href="../lib/datatable/dataTables.bootstrap4.min.css" />
        <script type="text/javascript" src="../lib/JQuery/jquery-3.3.1.js"></script>
        <script type="text/javascript" src="../lib/bootstrap-4.1.1-dist/js/bootstrap.min.js"></script>
        <script type="text/javascript" src="../lib/datatable/jquery.dataTables.min.js"></script>
        <script type="text/javascript" src="../lib/datatable/dataTables.bootstrap4.min.js"></script>      
        <script src="../lib/bootbox/bootbox.js"></script>
        <script src="../lib/bootbox/bootbox.locales.js"></script>
        <title><?php echo Constantes::NOMBRE_SISTEMA; ?> - Planes de Estudios</title>

    </head>
    <body>

        <?php include_once '../gui/navbar.php'; ?>

        <div class="container">
            <div class="card">
                <div class="card-header">
                    <h3>Planes de Estudios</h3>
                </div>
                <div class="card-body">
                    
                    <table class="table table-hover table-sm" id="tablaPlanes">
                        <thead>
                            <tr class="table-info">
                                <th>C&oacute;digo de la Carrera</th>
                                <th>Carrera</th>
                                <th>Opciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            if (!empty($Carreras)) {
                                foreach ($Carreras as $Carrera) { 
                            ?>
                                    <tr>
                                        <td><?= $Carrera->getId(); ?></td>
                                        <td><?= $Carrera->getNombre(); ?></td>
                                        <td>
                                            <a title="Ver Planes" href="carrera.verPlanes.php?id=<?= $Carrera->getId(); ?>">
                                                <button type="button" class="btn btn-outline-info">
                                                    <span class="oi oi-list"></span>
                                                </button>
                                            </a>
                                        </td>
                                    </tr>
                            <?php 
                                }
                            } 
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php include_once '../gui/footer.php'; ?>
        <script type="text/javascript">
            $(document).ready(function () {
                $('#tablaPlanes').DataTable({
                    language: {
                        url: '../lib/datatable/es-ar.json'
                    }
                });
            });
        </script>
    </body>
</html>
