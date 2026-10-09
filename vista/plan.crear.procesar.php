<?php
include_once '../lib/ControlAcceso.Class.php';
ControlAcceso::requierePermiso(PermisosSistema::PERMISO_CARRERAS);
include_once '../controlSistema/ManejadorPlan.php';
include_once '../lib/Constantes.Class.php';

$DatosFormulario = $_POST;

$ManejadorPlan = new ManejadorPlan();

if (empty($DatosFormulario)) {
    header("location: carreras.php");
} else {
    
    $error = "";
    $consulta = false;
    
    // Obtenemos el ID del Plan ingresado por el usuario
    $DatosFormulario["id"] = isset($DatosFormulario["id"]) ? trim($DatosFormulario["id"]) : "";
    // usado para volver a las revisiones del plan
    $idCarrera = "?id=".$DatosFormulario["idCarrera"];
    
    if (empty($DatosFormulario["id"])) {
        $error = "Debe especificar un c&oacute;digo o identificador de Plan v&aacute;lido.";
    } else {
        try {
            $consulta = $ManejadorPlan->alta($DatosFormulario);
            if ($consulta && !empty($DatosFormulario["idPlanOrigen"])) {
                $ManejadorPlan->clonarEstructura($DatosFormulario["idPlanOrigen"], $DatosFormulario["id"]);
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
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
            <title><?php echo Constantes::NOMBRE_SISTEMA; ?> - Crear Plan de Estudio</title>
        </head>
        <body>
    <?php include_once '../gui/navbar.php'; ?>

            <div class="container">
                <p></p>
                <div class="card">
                    <div class="card-header">
                        <h3>Crear Plan de Estudio</h3>
                    </div>
                    <div class="card-body">
    <?php if ($consulta) { ?>
                            <div class="alert alert-success" role="alert">
                                <strong>Operaci&oacute;n realizada con &eacute;xito.</strong> El plan <b><?= htmlspecialchars($DatosFormulario["id"]) ?></b> ha sido creado<?php if (!empty($DatosFormulario["idPlanOrigen"])) { echo " y se han copiado las asignaturas y correlatividades del plan <b>" . htmlspecialchars($DatosFormulario["idPlanOrigen"]) . "</b>."; } else { echo "."; } ?>
                            </div>
    <?php } ?>   
                        <?php if (!$consulta) { ?>
                            <div class="alert alert-danger" role="alert">
                                Ha ocurrido un error. <?= $error; ?>
                            </div>
    <?php } ?>
                        <hr />
                        <h5 class="card-text">Opciones</h5>
                        <?php if ($consulta) { ?>
                        <a href="plan.asignaturas.php?id=<?= urlencode($DatosFormulario["id"]); ?>&idCarrera=<?= urlencode($DatosFormulario["idCarrera"]); ?>">
                            <button type="button" class="btn btn-success mr-2">
                                <span class="oi oi-list"></span> Ver y Gestionar Asignaturas del Plan
                            </button>
                        </a>
                        <?php } ?>
                        <a href="carrera.verPlanes.php<?= $idCarrera; ?>">
                            <button type="button" class="btn btn-primary mr-2">
                                <span class="oi oi-arrow-thick-left"></span> Volver a Planes de la Carrera
                            </button>
                        </a>
                        <a href="carreras.php">
                            <button type="button" class="btn btn-info">
                                <span class="oi oi-justify-center"></span> Volver a Carreras
                            </button>
                        </a>
                    </div>
                </div>
            </div>
    <?php
    include_once '../gui/footer.php';
}
?>
    </body>
</html>
