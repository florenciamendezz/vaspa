<?php 
include_once '../lib/ControlAcceso.Class.php'; 
ControlAcceso::requierePermiso(PermisosSistema::PERMISO_REVISAR_PROGRAMA);
include_once '../modeloSistema/ProgramaPDFDetalle.Class.php';
include_once '../modeloSistema/Asignatura.Class.php';
include_once '../modeloSistema/Programa.Class.php'; // Para obtener comentarios legacy si es necesario

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("location: revisar.programas.php");
    exit;
}

$programaPDF = new ProgramaPDFDetalle($_GET['id']);
if (!$programaPDF->getId()) {
    header("location: revisar.programas.php");
    exit;
}

$asignatura = new Asignatura($programaPDF->getIdAsignatura());
$carreras = $asignatura->getCarreras();

// Obtenemos datos del programa legacy asociado para ver comentarios/estado desaprobado
// Esto es porque los comentarios se guardan en la tabla programa
$sqlProg = "SELECT * FROM programa WHERE idAsignatura = {$programaPDF->getIdAsignatura()} AND anio = {$programaPDF->getAnio()}";
$resProg = BDConexionSistema::getInstancia()->query($sqlProg);
$programaLegacy = null;
if ($resProg && $resProg->num_rows > 0) {
    $fila = $resProg->fetch_assoc();
    $programaLegacy = new Programa($fila['id']);
}

$rutaPDF = '../archivos/programas/' . $programaPDF->getRutaArchivo();
?>

<html lang="es">
    <head>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
      <script type="text/javascript" src="../lib/JQuery/jquery-3.3.1.js"></script>
      <script type="text/javascript" src="../lib/bootstrap-4.1.1-dist/js/bootstrap.min.js"></script>
      <link rel="stylesheet" href="../lib/open-iconic-master/font/css/open-iconic-bootstrap.css" />
      <link rel="stylesheet" href="../lib/bootstrap-4.1.1-dist/css/bootstrap.css" />
      <title><?php echo Constantes::NOMBRE_SISTEMA; ?> - Revisar Programa PDF</title>
    </head>
    <body>
        <?php include_once '../gui/navbar.php'; ?>
        <div class="container">
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h3>Revisar Programa de <span class="text-info"><?= $asignatura->getNombre().' - '.$asignatura->getId()?></span> (PDF)</h3>
                        </div>
                        <div class="card-body">
                            <?php 
                            if ($programaLegacy && $programaLegacy->getFueDesaprobado() == 1){
                                echo '<div class="alert alert-primary alert-dismissible fade show text-center" role="alert">
                                    El programa que est&aacute; revisando fue <strong>desaprobado </strong>anteriormente.
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                      <span aria-hidden="true">&times;</span>
                                    </button>
                                  </div>';
                            }
                            ?>
                            <div class="text-center mb-3">
                                <!-- Botones de accion -->
                                <button type="button" class="btn btn-success" data-toggle="modal" data-target="#modalAprobar">
                                    Aprobar Programa
                                </button>
                                <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#modalDesaprobar">
                                    Desaprobar Programa
                                </button>
                            </div>

                            <!-- Modal Aprobar -->
                            <div class="modal fade" id="modalAprobar" tabindex="-1" role="dialog" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">¿Est&aacute; seguro de aprobar el programa?</h5>
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                        <form action="../controlSistema/programa.revisar.actualizar.estado.pdf.php" method="POST">
                                            <div class="modal-footer">
                                                <input type="hidden" name="idPrograma" value="<?= $programaPDF->getId();?>">
                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">No</button>
                                                <button type="submit" class="btn btn-primary" name="aprobarPrograma">S&iacute;, aprobar programa</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- Modal Desaprobar -->
                            <div class="modal fade" id="modalDesaprobar" tabindex="-1" role="dialog" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">¿Est&aacute; seguro de desaprobar el programa?</h5>
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                        <form action="../controlSistema/programa.revisar.actualizar.estado.pdf.php" method="POST">
                                            <div class="modal-body">
                                                <div class="form-group">
                                                    <input type="hidden" name="idPrograma" value="<?= $programaPDF->getId();?>">
                                                    <label for="comentario" class="col-form-label">Comentario:</label>
                                                    <textarea class="form-control" id="comentario" rows="5" name="comentario" required=""></textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">No</button>
                                                <button type="submit" class="btn btn-primary" name="desaprobarPrograma">S&iacute;, desaprobar y enviar Comentario</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <hr>
                            
                            <!-- Visualizador PDF -->
                            <div class="embed-responsive embed-responsive-16by9" style="height: 800px;">
                                <iframe class="embed-responsive-item" src="<?= $rutaPDF ?>" allowfullscreen></iframe>
                            </div>
                            
                            <br>
                            <div class="text-center">
                                <a href="revisar.programas.php" class="btn btn-secondary">
                                    <span class="oi oi-arrow-circle-left"></span> Volver a Revisar Programas
                                </a>
                            </div>
                        </div>
                        
                        <div class="card-footer">
                            <div class="card mb-12">
                                <div class="card-header"><h4 class="card-title">Comentarios Anteriores</h4></div>
                                <?php 
                                if ($programaLegacy) {
                                    if (!is_null($programaLegacy->getComentarioVa())){
                                        echo '<div class="card-body">
                                                <h5 class="card-title">Vinculaci&oacute;n Acad&eacute;mica</h5>
                                                <p class="card-text text-muted">'.$programaLegacy->getComentarioVa().'</p>
                                              </div>';
                                    }
                                    if (!is_null($programaLegacy->getComentarioDepto())){
                                        echo '<hr>
                                              <div class="card-body">
                                                <h5 class="card-title">Departamento</h5>
                                                <p class="card-text text-muted">'.$programaLegacy->getComentarioDepto().'</p>
                                              </div>';
                                    }
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php include_once '../gui/footer.php'; ?>
    </body>
</html>
