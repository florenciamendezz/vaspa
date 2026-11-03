<?php
include_once '../lib/ControlAcceso.Class.php';
include_once '../modeloSistema/Carrera.Class.php';
include_once '../controlSistema/ManejadorPlan.php';
ControlAcceso::requierePermiso(PermisosSistema::PERMISO_CARRERAS);

// validamos que se esta enviando el codigo de la carrera
if (!isset($_GET["id"]) || empty($_GET["id"])){
    header("Location: carreras.php");
    exit;
}
$codCarrera = $_GET["id"];
$carrera = new Carrera($codCarrera);

if (is_null($carrera->getId())){
    header("Location: carreras.php");
    exit;
}

$manejadorPlan = new ManejadorPlan();
$planesExistentes = $manejadorPlan->getPlanesSegunCarrera($codCarrera);
$nroRevisionSugerido = !empty($planesExistentes) ? (count($planesExistentes) + 1) : 1;
$codigoSugerido = $carrera->getId() . 'P' . $nroRevisionSugerido;
?>

<html>
    <head>
        <meta charset="UTF-8">
        <link rel="stylesheet" href="../lib/bootstrap-4.1.1-dist/css/bootstrap.css" />
        <link rel="stylesheet" href="../lib/open-iconic-master/font/css/open-iconic-bootstrap.css" />
        <script type="text/javascript" src="../lib/JQuery/jquery-3.3.1.js"></script>
        <script type="text/javascript" src="../lib/bootstrap-4.1.1-dist/js/bootstrap.min.js"></script>
        <script type="text/javascript" src="../lib/js/valida.anios.js"></script>
        <script src="../lib/bootbox/bootbox.js"></script>
        <script src="../lib/bootbox/bootbox.locales.js"></script>
        <title><?php echo Constantes::NOMBRE_SISTEMA; ?> - Crear Plan de Estudio</title>
        
        <!-- Google Fonts Outfit y Estilos Premium -->
        <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="../lib/css/premium.css" />
    </head>
    <body>
        <?php include_once '../gui/navbar.php'; ?>
        <div class="container">
            <form action="plan.crear.procesar.php" method="post"> 
                <?= ControlAcceso::campoCsrf(); ?> 
                <div class="card card-premium">
                    <div class="card-header card-header-premium">
                        <h3>Crear Plan de Estudio para: <span style="color: #60a5fa; font-weight: 600;"><?= $carrera->getId().' - '.$carrera->getNombre();?></span></h3>
                        <p class="mb-0 mt-2 text-white" style="opacity: 0.85;">
                            Complete los campos a continuaci&oacute;n. 
                            Luego, presione el bot&oacute;n <b>Confirmar</b>.<br />
                            Si desea cancelar, presione el bot&oacute;n <b>Cancelar</b>.
                        </p>
                    </div>
                    <div class="card-body">
                        <h4>Propiedades del Plan</h4>
                        
                        <div class="form-group">
                            <label for="inputCodigoPlan" class="font-weight-bold">C&oacute;digo / Identificador del Plan</label>
                            <input type="text" name="id" class="form-control" id="inputCodigoPlan" 
                                   value="<?= htmlspecialchars($codigoSugerido); ?>" 
                                   placeholder="Ej: <?= htmlspecialchars($codigoSugerido); ?>" 
                                   maxlength="20" required autofocus>
                            <small class="form-text text-muted">
                                Puede modificar e ingresar manualmente el c&oacute;digo o identificador completo que corresponda para este plan.
                            </small>
                        </div>
                        
                        <input type="hidden" id="inputCarrera" name="idCarrera" value="<?= $carrera->getId(); ?>">
                                                
                        <div class="form-group">
                            <label for="inputAnioInicio">A&ntilde;o de Inicio</label>
                            <!--En el año maximo se coloca el año actual + 1-->
                            <input type="number" name="anio_inicio" class="form-control" min="1995" max="<?= date("Y") + 1; ?>" id="inputAnioInicio" placeholder="Ingrese el a&ntilde;o de Inicio del Plan" required="">
                        </div>

                        <div class="form-group">
                            <label for="inputAnioFin">A&ntilde;o de Fin</label>
                            <!--En el año maximo se coloca el año actual + 25-->
                            <input type="number" name="anio_fin" class="form-control" min="1995" max="<?= date("Y") + 25; ?>" id="inputAnioFin" placeholder="Ingrese el a&ntilde;o de Fin del Plan" >
                        </div>

                        <div class="form-group bg-light p-3 rounded border border-info">
                            <label for="idPlanOrigen" class="font-weight-bold text-info">
                                <span class="oi oi-fork"></span> Inicializar a partir de un Plan existente (Clonaci&oacute;n r&aacute;pida)
                            </label>
                            <select name="idPlanOrigen" id="idPlanOrigen" class="form-control">
                                <option value="">-- Plan en blanco (sin asignaturas iniciales) --</option>
                                <?php if (!empty($planesExistentes)) { 
                                    foreach ($planesExistentes as $pBase) { 
                                        $periodoBase = is_null($pBase->getAnio_fin()) ? $pBase->getAnio_inicio().' - Presente' : $pBase->getAnio_inicio().' - '.$pBase->getAnio_fin();
                                ?>
                                    <option value="<?= $pBase->getId(); ?>">Copiar materias y correlatividades del Plan <?= $pBase->getId(); ?> (<?= $periodoBase; ?>)</option>
                                <?php } } ?>
                            </select>
                            <small class="form-text text-muted">
                                Si selecciona un plan previo, se copiar&aacute;n autom&aacute;ticamente todas sus asignaturas y correlatividades al nuevo plan, permiti&eacute;ndole ahorrar tiempo y luego editar s&oacute;lo las diferencias.
                            </small>
                        </div>

                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-outline-success" onclick="return valida_anios(event);">
                            <span class="oi oi-check"></span> Confirmar
                        </button>
                        <a href="carreras.php">
                            <button type="button" class="btn btn-outline-danger">
                                <span class="oi oi-x"></span> Cancelar
                            </button>
                        </a>
                    </div>
                </div>
            </form>

        </div>
        <?php include_once '../gui/footer.php'; ?>
    </body>
</html>
