<?php
 
 include_once '../lib/ControlAcceso.Class.php';
 require_once '../modeloSistema/Profesor.Class.php';
 require_once '../modeloSistema/BDConexionSistema.Class.php';
 require_once '../modeloSistema/Programa.Class.php';
 require_once '../modeloSistema/Asignatura.Class.php';
 require_once '../modeloSistema/ProgramaPDFDetalle.Class.php';
 
 // Obtenemos el rol del usuario logueado en el sistema
 $usuario = $_SESSION['usuario'];
$rol = $usuario->roles[0]->nombre;

// Obtenemos el email del profesor
$email = $usuario->email;

// Preparamos la query para obtener todos los datos de Profesor segun el email
$sql = "SELECT * FROM profesor WHERE email = '{$email}'";
     
$resultado = BDConexionSistema::getInstancia()->query($sql);

$mostrarError = FALSE; 
if (!$resultado) {
    $mensaje = "Ocurrio un Error al obtener los datos del Profesor con email: {$email}.";
    $mostrarError = TRUE;
} elseif ($resultado->num_rows >= 1) { 
    $profesor = $resultado->fetch_object("Profesor"); 
} else {
    $mensaje = "No hay Profesor en el Sistema con email: <b>{$email}.</b>";
    $mostrarError = TRUE;
}

if (!$mostrarError){ 
    $asignaturas = $profesor->obtenerAsignaturasDePlanVigente();
    
    // Además, obtenemos TODAS las asignaturas del sistema
    $sqlTodas = "SELECT DISTINCT a.* FROM ASIGNATURA a ORDER BY a.nombre ASC";
    $resultadoTodas = BDConexionSistema::getInstancia()->query($sqlTodas);
    $todasAsignaturas = array();
    if ($resultadoTodas && $resultadoTodas->num_rows > 0) {
        while ($fila = $resultadoTodas->fetch_assoc()) {
            $asigTemp = new Asignatura($fila['id']);
            if ($asigTemp->getId()) {
                $todasAsignaturas[] = $asigTemp;
            }
        }
    }
    
    // Obtenemos TODAS las carreras del sistema
    $sqlCarreras = "SELECT * FROM CARRERA ORDER BY nombre ASC";
    $resultadoCarreras = BDConexionSistema::getInstancia()->query($sqlCarreras);
    $todasCarreras = array();
    if ($resultadoCarreras && $resultadoCarreras->num_rows > 0) {
        while ($filaCarrera = $resultadoCarreras->fetch_assoc()) {
            $todasCarreras[] = $filaCarrera;
        }
    }
    
    // Construir mapa asignatura -> lista de carreras (puede haber varias)
    $sqlRel = "SELECT c.id AS carreraID, c.nombre AS carrera, a.id AS asignaturaID, a.nombre AS asignatura, p.id AS plan "
            . "FROM carrera c "
            . "JOIN plan p ON p.idCarrera = c.id "
            . "JOIN plan_asignatura pa ON pa.idPlan = p.id "
            . "JOIN asignatura a ON a.id = pa.idAsignatura "
            . "ORDER BY c.nombre";
    $resultadoRel = BDConexionSistema::getInstancia()->query($sqlRel);
    $asignaturaCarrera = array();
    if ($resultadoRel && $resultadoRel->num_rows > 0) {
        while ($r = $resultadoRel->fetch_assoc()) {
            $aid = $r['asignaturaID'];
            $cname = $r['carrera'];
            if (!isset($asignaturaCarrera[$aid])) {
                $asignaturaCarrera[$aid] = $cname;
            } else {
                // evitar duplicados
                if (strpos($asignaturaCarrera[$aid], $cname) === false) {
                    $asignaturaCarrera[$aid] .= ', ' . $cname;
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
        <title><?php echo Constantes::NOMBRE_SISTEMA; ?> - Mis Asignaturas</title>
        <style type="text/css">
            .btn-outline-purple {
                color: #3a2166;
                background-color: transparent;
                background-image: none;
                border-color: #3a2166;
            }

            .btn-outline-purple:hover {
                color: #fff;
                background-color: #3a2166;
                border-color: #3a2166;
            }

            .btn-outline-purple:focus, .btn-outline-purple.focus {
                box-shadow: 0 0 0 0.2rem rgba(145, 109, 208, 1);
            }

            .btn-outline-purple.disabled, .btn-outline-purple:disabled {
                color: #3a2166;
                background-color: transparent;
            }

            .btn-outline-purple:not(:disabled):not(.disabled):active, .btn-outline-purple:not(:disabled):not(.disabled).active,
            .show > .btn-outline-purple.dropdown-toggle {
                color: #fff;
                background-color: #3a2166;
                border-color: #3a2166;
            }

            .btn-outline-purple:not(:disabled):not(.disabled):active:focus, .btn-outline-purple:not(:disabled):not(.disabled).active:focus,
            .show > .btn-outline-purple.dropdown-toggle:focus {
                box-shadow: 0 0 0 0.2rem rgba(145, 109, 208, 1);
            }
        </style>

    </head>
    <body>

        <?php include_once '../gui/navbar.php';   ?>

        <div class="container">
            <div class="card">
                <div class="card-header">

                    <h3>Mis Asignaturas</h3>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="filtroCarrera">Filtrar por Carrera:</label>
                            <select class="form-control" id="filtroCarrera">
                                <option value="">Todas las Carreras</option>
                                <?php foreach ($todasCarreras as $carrera) { ?>
                                    <option value="<?= $carrera['id'] ?>"><?= $carrera['nombre'] ?></option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                    <?php
                    if ($mostrarError) { ?>
                        <div class="alert alert-danger text-center" role="alert">
                            <?= $mensaje;?>
                        </div>
                    <?php
                    } else {
                        //var_dump($asignaturas);
                        if (empty($todasAsignaturas)){ ?>
                            <div class="alert alert-warning text-center" role="alert">
                                No hay asignaturas en el sistema.
                            </div>
                        <?php    
                        } else { ?>
                            <table class="table table-hover table-sm">
                        <tr class="table-info">
                            <th>C&oacute;digo de Asignatura</th>
                            <th>Nombre</th>
                            <th>Carreras</th>
                            <th>Estado del programa</th>
                            <th>Vigencia</th>
                            <th>Gestionar Programa</th>
                        </tr>
                        <?php foreach ($todasAsignaturas as $Asignatura) { 
                                $carreras = $Asignatura->getCarreras();
                                $idsCarreras = [];
                                $nombresCarreras = [];
                                if ($carreras) {
                                    foreach ($carreras as $c) {
                                        $idsCarreras[] = $c->getId();
                                        $nombresCarreras[] = $c->getNombre();
                                    }
                                }
                                $dataCarreras = implode(',', $idsCarreras);
                            ?>                            <tr class="fila-asignatura" data-carreras="<?= $dataCarreras ?>">
                            <td><?= $Asignatura->getId(); ?></td>
                            <td>
                                <?= htmlspecialchars($Asignatura->getNombre()); ?>
                                <?php 
                                $anioActual = date('Y');
                                $programaDetalle = ProgramaPDFDetalle::obtenerPorAsignaturaYAnio($Asignatura->getId(), $anioActual);
                                if ($programaDetalle && $programaDetalle->obtenerEstadoActual() == "Devuelto al Profesor" && !empty($programaDetalle->getComentarioDesaprobacion())) { ?>
                                    <div class="alert alert-danger mt-1 p-2" style="font-size: 0.85em; border-left: 4px solid #dc3545;">
                                        <strong>Observaciones de devolución:</strong><br>
                                        <?= nl2br(htmlspecialchars($programaDetalle->getComentarioDesaprobacion())); ?>
                                    </div>
                                <?php } ?>
                            </td>
                            <td>
                                <?php
                                if (!empty($nombresCarreras)) {
                                    echo implode("<br>", $nombresCarreras);
                                } else {
                                    echo "-";
                                }
                                ?>
                            </td>
                            <td><?php 
                                 $vigencia = '-';
                                 $estado = 'No Cargado';
                                 $claseEstado = 'badge-secondary';
                                 
                                 // Botones base
                                 $btnNuevoHabilitado = '<a title="Subir Programa" class="btn btn-outline-success btn-sm" href="programa.crear.php?id='.$Asignatura->getId().'" role="button"><span class="oi oi-plus"></span></a>&nbsp;';
                                 $btnNuevoDeshabilitado = '<button type="button" title="Subir Programa" class="btn btn-outline-success btn-sm" disabled><span class="oi oi-plus"></span></button>&nbsp;';
                                 
                                 $btnModificarHabilitado = '<a title="Reemplazar PDF" class="btn btn-outline-warning btn-sm" href="programa.crear.php?id='.$Asignatura->getId().'" role="button"><span class="oi oi-pencil"></span></a>&nbsp;';
                                 $btnModificarDeshabilitado = '<button type="button" title="Reemplazar PDF" class="btn btn-outline-warning btn-sm" disabled><span class="oi oi-pencil"></span></button>&nbsp;';
                                 
                                 $btnDescargarDeshabilitado = '<button type="button" class="btn btn-outline-info btn-sm" disabled title="Descargar PDF"><span class="oi oi-document"></span></button>';
                                 
                                 $botones = '';
                                 if (is_null($programaDetalle)) {
                                     $estado = 'No Cargado';
                                     $claseEstado = 'badge-secondary';
                                     $btnEnviarDeshabilitado = '<button type="button" title="Enviar a Revisión" class="btn btn-outline-purple btn-sm" disabled><span class="oi oi-share"></span></button>&nbsp;';
                                     $botones = $btnNuevoHabilitado
                                                . $btnModificarDeshabilitado
                                                . $btnEnviarDeshabilitado
                                                . $btnDescargarDeshabilitado;
                                 } else {
                                     $estadoReal = $programaDetalle->obtenerEstadoActual();
                                     $estado = $estadoReal;
                                     $anioPrograma = $programaDetalle->getAnio();
                                     $vigenciaVal = $programaDetalle->getVigencia();
                                     if ($vigenciaVal == 1) {
                                         $vigencia = "$anioPrograma";
                                     } elseif ($vigenciaVal == 2) {
                                         $vigencia = "$anioPrograma - ".($anioPrograma+1);
                                     } elseif ($vigenciaVal == 3) {
                                         $vigencia = "$anioPrograma - ".($anioPrograma+1)." - ".($anioPrograma+2);
                                     }
                                     
                                     // Definición de colores de badge según el nuevo estado
                                     if ($estadoReal == 'Aprobado') {
                                         $claseEstado = 'badge-success';
                                     } elseif ($estadoReal == 'Borrador') {
                                         $claseEstado = 'badge-warning';
                                     } elseif ($estadoReal == 'Devuelto al Profesor') {
                                         $claseEstado = 'badge-danger';
                                     } elseif (strpos($estadoReal, 'Pendiente') !== false) {
                                         $claseEstado = 'badge-primary';
                                     } else {
                                         $claseEstado = 'badge-info'; // Estados intermedios "Revisado por..."
                                     }
                                     
                                     $btnEnviarHabilitado = '<button type="button" title="Enviar a Revisión" class="btn btn-outline-purple btn-sm" onclick="enviarARevision('.$programaDetalle->getId().')"><span class="oi oi-share"></span></button>&nbsp;';
                                     $btnEnviarDeshabilitado = '<button type="button" title="Enviar a Revisión" class="btn btn-outline-purple btn-sm" disabled><span class="oi oi-share"></span></button>&nbsp;';
                                     
                                     $btnDescargarHabilitado = '<a title="Descargar PDF" class="btn btn-outline-info btn-sm" href="programa.descargarPDF.php?id='.$programaDetalle->getId().'&tipo=pdf" role="button" target="_blank"><span class="oi oi-document"></span></a>';
                                     
                                     // Asignar botones según estado actual del circuito
                                     switch ($estadoReal) {
                                         case "Borrador":
                                             $botones = $btnNuevoDeshabilitado
                                                        . $btnModificarHabilitado
                                                        . $btnEnviarHabilitado
                                                        . $btnDescargarHabilitado;
                                             break;
                                         case "Devuelto al Profesor":
                                             $botones = $btnNuevoDeshabilitado
                                                        . $btnModificarHabilitado
                                                        . $btnEnviarDeshabilitado
                                                        . $btnDescargarHabilitado;
                                             break;
                                         case "Aprobado":
                                             $botones = $btnNuevoDeshabilitado
                                                        . $btnModificarDeshabilitado
                                                        . $btnEnviarDeshabilitado
                                                        . $btnDescargarHabilitado;
                                             break;
                                         default:
                                             // En revisión activa o estados de revisores intermedios
                                             $botones = $btnNuevoDeshabilitado
                                                        . $btnModificarDeshabilitado
                                                        . $btnEnviarDeshabilitado
                                                        . $btnDescargarHabilitado;
                                             break;
                                     }
                                 }
                                 ?>
                                 <span class="badge <?= $claseEstado; ?>"><?= htmlspecialchars($estado); ?></span>
                             </td>
                             <td><?= htmlspecialchars($vigencia);?></td>
 
                             <td>
                                 <?php echo $botones; ?>
                             </td>
                             </tr>
                        <?php } ?>
                    </table>
                    <?php    
                        }
                    }
                    ?>
                    
                </div>
            </div>
        </div>
        <?php include_once '../gui/footer.php'; ?>
        <script>
            $(document).ready(function() {
                $('#filtroCarrera').on('change', function() {
                    var carreraId = $(this).val();
                    if (carreraId === "") {
                        $('.fila-asignatura').show();
                    } else {
                        $('.fila-asignatura').each(function() {
                            var ids = $(this).data('carreras');
                            // Convert to string and split, or handle empty case
                            var idsArray = String(ids).split(',');
                            if (idsArray.includes(carreraId)) {
                                $(this).show();
                            } else {
                                $(this).hide();
                            }
                        });
                    }
                });
            });

            function enviarARevision(idPrograma) {
                if (confirm('¿Está seguro de enviar este programa a revisión? Una vez enviado, no podrá realizar cambios ni subir archivos hasta que sea revisado por los evaluadores.')) {
                    $("#idProgramaEnviar").val(idPrograma);
                    $("#formEnviarRevision").submit();
                }
            }
        </script>

        <!-- Formulario POST Oculto para Enviar a Revisión -->
        <form id="formEnviarRevision" action="../controlSistema/programa.enviar.revision.php" method="POST" style="display:none;">
            <input type="hidden" name="idPrograma" id="idProgramaEnviar">
        </form>
    </body>
</html>