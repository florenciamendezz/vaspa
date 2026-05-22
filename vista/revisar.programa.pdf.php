<?php 
include_once '../lib/ControlAcceso.Class.php'; 
ControlAcceso::requierePermiso(PermisosSistema::PERMISO_REVISAR_PROGRAMA);
include_once '../modeloSistema/ProgramaPDFDetalle.Class.php';
include_once '../modeloSistema/Asignatura.Class.php';
include_once '../modeloSistema/Programa.Class.php';

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
$sqlProg = "SELECT * FROM programa WHERE idAsignatura = {$programaPDF->getIdAsignatura()} AND anio = {$programaPDF->getAnio()}";
$resProg = BDConexionSistema::getInstancia()->query($sqlProg);
$programaLegacy = null;
if ($resProg && $resProg->num_rows > 0) {
    $fila = $resProg->fetch_assoc();
    $programaLegacy = new Programa($fila['id']);
}

$rutaPDF = '../archivos/programas/' . $programaPDF->getRutaArchivo();

// Lógica de circuito secuencial
$circuito = $programaPDF->determinarCircuito($asignatura->getId());
$aprobado_escuela = $programaPDF->getAprobadoEscuela();
$aprobado_va = $programaPDF->getAprobadoVa();
$aprobado_depto = $programaPDF->getAprobadoDepto();
$aprobado_va_firma = $programaPDF->getAprobadoVaFirma();
$en_revision = $programaPDF->getEnRevision();
$fue_desaprobado = $programaPDF->getFueDesaprobado();

$Usuario = $_SESSION['usuario'];
$rol = $Usuario->roles[0]->nombre;

// Mapear roles equivalentes
$esAdmin = ($rol == 'Administrador');
$esEscuela = ($rol == 'Director de Escuela' || $rol == 'Secretario de Escuela' || $esAdmin);
$esVA = ($rol == 'Vinculación Académica' || $esAdmin);
$esDepto = ($rol == 'Director de Departamento' || $esAdmin);

$mostrarAcciones = false;
$accionTipo = ''; // 'subir_o_desaprobar', 'enviar_o_reemplazar', 'firma_final'
$responsableText = '';

if ($fue_desaprobado == 1) {
    $responsableText = 'Devuelto al Profesor (Esperando nueva carga)';
} elseif ($en_revision == 0 && $aprobado_escuela === null && $aprobado_va === null) {
    $responsableText = 'Profesor (Borrador, pendiente de envío a revisión)';
}

if ($circuito == 'estandar') {
    if ($aprobado_escuela === null) {
        if ($en_revision == 1) {
            $mostrarAcciones = $esEscuela;
            $accionTipo = 'subir_o_desaprobar';
            $responsableText = 'Director de Escuela (Pendiente de subir PDF firmado)';
        }
    } elseif ($aprobado_escuela == 1 && $aprobado_va === null) {
        if ($en_revision == 0) {
            $mostrarAcciones = $esEscuela;
            $accionTipo = 'enviar_o_reemplazar';
            $responsableText = 'Director de Escuela (Borrador firmado, pendiente de enviar a VA)';
        } elseif ($en_revision == 1) {
            $mostrarAcciones = $esVA;
            $accionTipo = 'subir_o_desaprobar';
            $responsableText = 'Vinculación Académica (Acreditación - Pendiente de subir PDF firmado)';
        }
    }
} else {
    // Circuito institucional o vacancia
    if ($aprobado_va === null) {
        if ($en_revision == 1) {
            $mostrarAcciones = $esVA;
            $accionTipo = 'subir_o_desaprobar';
            $responsableText = 'Vinculación Académica (Acreditación - Pendiente de subir PDF firmado)';
        }
    }
}

if ($aprobado_va == 1 && $aprobado_depto === null) {
    if ($en_revision == 0) {
        $mostrarAcciones = $esVA;
        $accionTipo = 'enviar_o_reemplazar';
        $responsableText = 'Vinculación Académica (Borrador firmado, pendiente de enviar a Departamento)';
    } elseif ($en_revision == 1) {
        $mostrarAcciones = $esDepto;
        $accionTipo = 'subir_o_desaprobar';
        $responsableText = 'Director de Departamento (Pendiente de subir PDF firmado)';
    }
} elseif ($aprobado_depto == 1 && $aprobado_va_firma === null) {
    if ($en_revision == 0) {
        $mostrarAcciones = $esDepto;
        $accionTipo = 'enviar_o_reemplazar';
        $responsableText = 'Director de Departamento (Borrador firmado, pendiente de enviar a VA Firma Final)';
    } elseif ($en_revision == 1) {
        $mostrarAcciones = $esVA;
        $accionTipo = 'firma_final';
        $responsableText = 'Vinculación Académica (Firma Final - Pendiente de aprobación definitiva)';
    }
}

if ($aprobado_va_firma == 1) {
    $responsableText = 'Circuito finalizado (Programa Aprobado)';
}

// Configurar los pasos del indicador visual
$idxActivo = 0;
if ($circuito == 'estandar') {
    if ($fue_desaprobado == 1 || ($en_revision == 0 && $aprobado_escuela === null)) {
        $idxActivo = 0;
    } elseif (($en_revision == 1 && $aprobado_escuela === null) || ($en_revision == 0 && $aprobado_escuela == 1 && $aprobado_va === null)) {
        $idxActivo = 1;
    } elseif (($en_revision == 1 && $aprobado_escuela == 1 && $aprobado_va === null) || ($en_revision == 0 && $aprobado_va == 1 && $aprobado_depto === null)) {
        $idxActivo = 2;
    } elseif (($en_revision == 1 && $aprobado_va == 1 && $aprobado_depto === null) || ($en_revision == 0 && $aprobado_depto == 1 && $aprobado_va_firma === null)) {
        $idxActivo = 3;
    } elseif ($en_revision == 1 && $aprobado_depto == 1 && $aprobado_va_firma === null) {
        $idxActivo = 4;
    } elseif ($aprobado_va_firma == 1) {
        $idxActivo = 5;
    }
    
    $pasos = [
        ['nombre' => 'Profesor', 'icono' => 'person'],
        ['nombre' => 'Escuela', 'icono' => 'home'],
        ['nombre' => 'VA (Acred.)', 'icono' => 'document'],
        ['nombre' => 'Depto', 'icono' => 'briefcase'],
        ['nombre' => 'VA (Firma)', 'icono' => 'pencil'],
        ['nombre' => 'Aprobado', 'icono' => 'check']
    ];
} else {
    if ($fue_desaprobado == 1 || ($en_revision == 0 && $aprobado_va === null)) {
        $idxActivo = 0;
    } elseif (($en_revision == 1 && $aprobado_va === null) || ($en_revision == 0 && $aprobado_va == 1 && $aprobado_depto === null)) {
        $idxActivo = 1;
    } elseif (($en_revision == 1 && $aprobado_va == 1 && $aprobado_depto === null) || ($en_revision == 0 && $aprobado_depto == 1 && $aprobado_va_firma === null)) {
        $idxActivo = 2;
    } elseif ($en_revision == 1 && $aprobado_depto == 1 && $aprobado_va_firma === null) {
        $idxActivo = 3;
    } elseif ($aprobado_va_firma == 1) {
        $idxActivo = 4;
    }
    
    $pasos = [
        ['nombre' => 'Profesor', 'icono' => 'person'],
        ['nombre' => 'VA (Acred.)', 'icono' => 'document'],
        ['nombre' => 'Depto', 'icono' => 'briefcase'],
        ['nombre' => 'VA (Firma)', 'icono' => 'pencil'],
        ['nombre' => 'Aprobado', 'icono' => 'check']
    ];
}
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
        <div class="container-fluid px-4 py-3">
            <div class="row">
                <div class="col-md-12">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white border-bottom-0 pt-4">
                            <h3 class="card-title text-center text-dark">
                                Revisar Programa de <span class="text-primary font-weight-bold"><?= htmlspecialchars($asignatura->getNombre().' - '.$asignatura->getId())?></span> (Año <?= $programaPDF->getAnio() ?>)
                            </h3>
                        </div>
                        <div class="card-body">
                            
                            <!-- Indicador visual del circuito -->
                            <div class="card mb-4 bg-light border-0">
                                <div class="card-body py-3">
                                    <h6 class="text-center text-muted mb-3">Circuito de Aprobación: <span class="badge badge-info text-capitalize"><?= htmlspecialchars($circuito) ?></span></h6>
                                    <div class="d-flex justify-content-around align-items-center flex-wrap">
                                        <?php foreach ($pasos as $i => $p) {
                                            $colorClass = "text-muted";
                                            $badgeClass = "badge-secondary";
                                            $borderClass = "";
                                            
                                            if ($i < $idxActivo) {
                                                $colorClass = "text-success font-weight-bold";
                                                $badgeClass = "badge-success";
                                            } elseif ($i == $idxActivo) {
                                                $colorClass = "text-primary font-weight-bold";
                                                $badgeClass = "badge-primary shadow-sm";
                                                $borderClass = "border border-primary rounded p-2 bg-white shadow-sm";
                                            }
                                            
                                            $icono = 'oi-circle-check';
                                            if ($p['icono'] == 'person') $icono = 'oi-person';
                                            elseif ($p['icono'] == 'home') $icono = 'oi-home';
                                            elseif ($p['icono'] == 'document') $icono = 'oi-document';
                                            elseif ($p['icono'] == 'briefcase') $icono = 'oi-briefcase';
                                            elseif ($p['icono'] == 'pencil') $icono = 'oi-pencil';
                                            elseif ($p['icono'] == 'check') $icono = 'oi-check';
                                        ?>
                                            <div class="text-center mx-2 mb-2 d-flex align-items-center">
                                                <div class="<?= $borderClass ?>" style="min-width: 90px;">
                                                    <span class="badge <?= $badgeClass ?> p-2 mb-1 rounded-circle" style="font-size: 1.1rem; width: 35px; height: 35px; display: inline-flex; align-items: center; justify-content: center;">
                                                        <span class="oi <?= $icono ?>"></span>
                                                    </span>
                                                    <div class="small <?= $colorClass ?>" style="font-size: 0.8rem;"><?= $p['nombre'] ?></div>
                                                </div>
                                                <?php if ($i < count($pasos) - 1) { ?>
                                                    <span class="oi oi-chevron-right text-muted mx-3 d-none d-sm-inline" style="font-size: 0.9rem;"></span>
                                                <?php } ?>
                                            </div>
                                        <?php } ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Alerta de Comentario de Desaprobación Actual -->
                            <?php if (!empty($programaPDF->getComentarioDesaprobacion())) { ?>
                                <div class="alert alert-danger shadow-sm mb-4" role="alert">
                                    <h5 class="alert-heading font-weight-bold"><span class="oi oi-warning"></span> Comentario de Devolución Actual:</h5>
                                    <p class="mb-0 text-dark" style="font-size: 1.05rem;"><?= nl2br(htmlspecialchars($programaPDF->getComentarioDesaprobacion())) ?></p>
                                </div>
                            <?php } ?>

                            <!-- Botones de Acción -->
                            <div class="text-center mb-4">
                                <?php if ($mostrarAcciones) { ?>
                                    <?php if ($accionTipo == 'subir_o_desaprobar') { ?>
                                        <button type="button" class="btn btn-success btn-lg mx-2 my-1 shadow-sm" data-toggle="modal" data-target="#modalSubirFirmado">
                                            <span class="oi oi-cloud-upload"></span> Subir PDF Firmado
                                        </button>
                                        <button type="button" class="btn btn-danger btn-lg mx-2 my-1 shadow-sm" data-toggle="modal" data-target="#modalDesaprobar">
                                            <span class="oi oi-circle-x"></span> Desaprobar y Devolver
                                        </button>
                                    <?php } elseif ($accionTipo == 'enviar_o_reemplazar') { ?>
                                        <button type="button" class="btn btn-primary btn-lg mx-2 my-1 shadow-sm" onclick="enviarAlSiguiente()">
                                            <span class="oi oi-chevron-right"></span> Enviar al Siguiente Paso
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary btn-lg mx-2 my-1 shadow-sm" data-toggle="modal" data-target="#modalSubirFirmado">
                                            <span class="oi oi-loop-circular"></span> Reemplazar PDF Firmado
                                        </button>
                                        <button type="button" class="btn btn-danger btn-lg mx-2 my-1 shadow-sm" data-toggle="modal" data-target="#modalDesaprobar">
                                            <span class="oi oi-circle-x"></span> Desaprobar y Devolver
                                        </button>
                                    <?php } elseif ($accionTipo == 'firma_final') { ?>
                                        <button type="button" class="btn btn-success btn-lg mx-2 my-1 shadow-sm" data-toggle="modal" data-target="#modalAprobarFinal">
                                            <span class="oi oi-circle-check"></span> Confirmar Aprobación Final
                                        </button>
                                    <?php } ?>
                                <?php } else { ?>
                                    <div class="alert alert-info d-inline-block px-5 py-3 mb-0 shadow-sm" role="alert">
                                        <span class="oi oi-info mr-2"></span> El programa está actualmente en la etapa: <strong><?= $responsableText ?></strong>.
                                    </div>
                                <?php } ?>
                            </div>

                            <!-- Modal Subir PDF Firmado -->
                            <div class="modal fade" id="modalSubirFirmado" tabindex="-1" role="dialog" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered" role="document">
                                    <div class="modal-content shadow-lg">
                                        <div class="modal-header bg-success text-white">
                                            <h5 class="modal-title font-weight-bold"><span class="oi oi-cloud-upload"></span> Subir Programa PDF Firmado</h5>
                                            <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                        <form action="../controlSistema/programa.actualizar.pdf.php" method="POST" enctype="multipart/form-data">
                                            <div class="modal-body text-left">
                                                <input type="hidden" name="idPrograma" value="<?= $programaPDF->getId();?>">
                                                <div class="form-group">
                                                    <label for="archivoPdf" class="font-weight-bold">Seleccionar archivo PDF firmado:</label>
                                                    <input type="file" class="form-control-file" id="archivoPdf" name="archivoPdf" accept=".pdf" required>
                                                    <small class="form-text text-muted">Subí el PDF que cuenta con tu firma digital o escaneada.</small>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                                                <button type="submit" class="btn btn-success">Subir Archivo Firmado</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- Modal Desaprobar -->
                            <div class="modal fade" id="modalDesaprobar" tabindex="-1" role="dialog" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered" role="document">
                                    <div class="modal-content shadow-lg">
                                        <div class="modal-header bg-danger text-white">
                                            <h5 class="modal-title font-weight-bold"><span class="oi oi-warning"></span> ¿Desaprobar y Devolver el programa?</h5>
                                            <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                        <form action="../controlSistema/programa.revisar.actualizar.estado.pdf.php" method="POST">
                                            <div class="modal-body text-left">
                                                <div class="form-group">
                                                    <input type="hidden" name="idPrograma" value="<?= $programaPDF->getId();?>">
                                                    <label for="comentario" class="font-weight-bold">Comentarios/Observaciones de Devolución:</label>
                                                    <textarea class="form-control" id="comentario" rows="5" name="comentario" placeholder="Escribí los motivos del rechazo y las correcciones solicitadas..." required></textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">No, cancelar</button>
                                                <button type="submit" class="btn btn-danger" name="desaprobarPrograma">Sí, devolver con comentario</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- Modal Aprobación Final -->
                            <div class="modal fade" id="modalAprobarFinal" tabindex="-1" role="dialog" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered" role="document">
                                    <div class="modal-content shadow-lg">
                                        <div class="modal-header bg-success text-white">
                                            <h5 class="modal-title font-weight-bold"><span class="oi oi-circle-check"></span> Confirmar Aprobación Final</h5>
                                            <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                        <form action="../controlSistema/programa.revisar.actualizar.estado.pdf.php" method="POST">
                                            <div class="modal-body text-left">
                                                <p style="font-size: 1.1rem;">
                                                    ¿Estás seguro de confirmar la aprobación final de esta asignatura? Esto finalizará el circuito y el programa quedará oficialmente aprobado.
                                                </p>
                                                <p class="text-muted small">
                                                    Se enviará una notificación automática por correo electrónico al profesor informándole sobre la aprobación final y adjuntando el enlace de descarga del PDF.
                                                </p>
                                            </div>
                                            <div class="modal-footer">
                                                <input type="hidden" name="idPrograma" value="<?= $programaPDF->getId();?>">
                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                                                <button type="submit" class="btn btn-success" name="aprobarPrograma">Sí, confirmar aprobación</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- Formulario oculto para avanzar el circuito -->
                            <form id="formEnviarSiguiente" action="../controlSistema/programa.enviar.revision.php" method="POST" style="display:none;">
                                <input type="hidden" name="idPrograma" value="<?= $programaPDF->getId();?>">
                            </form>

                            <hr>
                            
                            <!-- Visualizador PDF -->
                            <div class="embed-responsive embed-responsive-16by9 border rounded shadow-sm" style="height: 800px; background-color: #eee;">
                                <iframe class="embed-responsive-item" src="<?= $rutaPDF ?>" allowfullscreen></iframe>
                            </div>
                            
                            <br>
                            <div class="text-center">
                                <a href="revisar.programas.php" class="btn btn-secondary shadow-sm">
                                    <span class="oi oi-arrow-circle-left"></span> Volver a Revisar Programas
                                </a>
                            </div>
                        </div>
                        
                        <div class="card-footer bg-white pt-4">
                            <div class="card border shadow-sm">
                                <div class="card-header bg-light">
                                    <h4 class="card-title mb-0 font-weight-bold text-dark"><span class="oi oi-comment-square mr-2"></span> Historial de Comentarios</h4>
                                </div>
                                <?php 
                                $comentariosEncontrados = false;
                                if ($programaLegacy) {
                                    if (!is_null($programaLegacy->getComentarioVa()) && !empty($programaLegacy->getComentarioVa())){
                                        $comentariosEncontrados = true;
                                        echo '<div class="card-body">
                                                <h5 class="card-title font-weight-bold text-primary">Vinculación Académica (Acreditación)</h5>
                                                <p class="card-text text-dark">'.nl2br(htmlspecialchars($programaLegacy->getComentarioVa())).'</p>
                                              </div>';
                                    }
                                    if (!is_null($programaLegacy->getComentarioDepto()) && !empty($programaLegacy->getComentarioDepto())){
                                        if ($comentariosEncontrados) echo '<hr class="my-0">';
                                        $comentariosEncontrados = true;
                                        echo '<div class="card-body">
                                                <h5 class="card-title font-weight-bold text-primary">Departamento</h5>
                                                <p class="card-text text-dark">'.nl2br(htmlspecialchars($programaLegacy->getComentarioDepto())).'</p>
                                              </div>';
                                    }
                                    if (!is_null($programaLegacy->getComentarioEscuela()) && !empty($programaLegacy->getComentarioEscuela())){
                                        if ($comentariosEncontrados) echo '<hr class="my-0">';
                                        $comentariosEncontrados = true;
                                        echo '<div class="card-body">
                                                <h5 class="card-title font-weight-bold text-primary">Escuela</h5>
                                                <p class="card-text text-dark">'.nl2br(htmlspecialchars($programaLegacy->getComentarioEscuela())).'</p>
                                              </div>';
                                    }
                                }
                                if (!$comentariosEncontrados) {
                                    echo '<div class="card-body text-center text-muted">
                                            No hay comentarios registrados en este programa.
                                          </div>';
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <script type="text/javascript">
            function enviarAlSiguiente() {
                if (confirm("¿Estás seguro de enviar este programa a la siguiente etapa de revisión?")) {
                    document.getElementById("formEnviarSiguiente").submit();
                }
            }
        </script>
        
        <?php include_once '../gui/footer.php'; ?>
    </body>
</html>
