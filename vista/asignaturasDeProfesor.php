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
    
    // Simplificado sin planes: cargamos las asignaturas directamente
    $asignaturasAgrupadas = array();
    if ($asignaturas) {
        foreach ($asignaturas as $Asignatura) {
            $idAsig = $Asignatura->getId();
            $asignaturasAgrupadas[$idAsig] = array(
                'objeto' => $Asignatura
            );
        }
    }
    
    // Obtenemos solo las carreras correspondientes a las asignaturas del profesor en planes vigentes
    $todasCarreras = array();
    $carrerasIdsUnicos = array();
    $asignaturaCarrera = array();
    
    if ($asignaturas) {
        foreach ($asignaturas as $Asignatura) {
            $carreras = $Asignatura->getCarreras();
            if ($carreras) {
                foreach ($carreras as $c) {
                    // Cargar en la lista de carreras del filtro (sin repetir)
                    if (!in_array($c->getId(), $carrerasIdsUnicos)) {
                        $carrerasIdsUnicos[] = $c->getId();
                        $todasCarreras[] = array(
                            'id' => $c->getId(),
                            'nombre' => $c->getNombre()
                        );
                    }
                    
                    // Cargar en el mapa de asignaturas para el JavaScript del filtro
                    $aid = $Asignatura->getId();
                    $cname = $c->getNombre();
                    if (!isset($asignaturaCarrera[$aid])) {
                        $asignaturaCarrera[$aid] = $cname;
                    } else {
                        if (strpos($asignaturaCarrera[$aid], $cname) === false) {
                            $asignaturaCarrera[$aid] .= ', ' . $cname;
                        }
                    }
                }
            }
        }
        
        // Ordenar alfabéticamente las carreras por nombre
        usort($todasCarreras, function($a, $b) {
            return strcmp($a['nombre'], $b['nombre']);
        });
    }
    
    // Obtener todas las asignaturas para el modal de asociar nueva materia
    $resTodasAsignaturas = BDConexionSistema::getInstancia()->query("SELECT id, nombre FROM asignatura ORDER BY nombre ASC");
    $todasAsignaturasModal = [];
    if ($resTodasAsignaturas) {
        while ($row = $resTodasAsignaturas->fetch_assoc()) {
            $todasAsignaturasModal[] = $row;
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
        
        <!-- Google Fonts Outfit -->
        <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
        
        <!-- Estilos Premium comunes -->
        <link rel="stylesheet" href="../lib/css/premium.css" />

    </head>
    <body>

        <?php include_once '../gui/navbar.php';   ?>

        <div class="container">
            <div class="card card-premium">
                <div class="card-header card-header-premium">
                    <h3>Mis Asignaturas</h3>
                </div>
                <div class="card-body">
                    <div class="row mb-3 align-items-end">
                        <div class="col-md-6">
                            <label for="filtroCarrera">Filtrar por Carrera:</label>
                            <select class="form-control" id="filtroCarrera">
                                <option value="">Todas las Carreras</option>
                                <?php foreach ($todasCarreras as $carrera) { ?>
                                    <option value="<?= $carrera['id'] ?>"><?= $carrera['nombre'] ?></option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="col-md-6 text-md-right mt-3 mt-md-0">
                            <button type="button" class="btn btn-primary btn-premium px-4" data-toggle="modal" data-target="#modalModificacionRevista">
                                <span class="oi oi-envelope-closed mr-2"></span> Informar modificación situación de revista
                            </button>
                        </div>
                    </div>
                    <?php
                    if ($mostrarError) { ?>
                        <div class="alert alert-danger text-center" role="alert">
                            <?= $mensaje;?>
                        </div>
                    <?php
                    } else {
                        //var_dump($asignaturas);                        if (empty($asignaturas)){ ?>
                            <div class="alert alert-warning text-center" role="alert">
                                No tienes asignaturas asignadas en planes de estudio vigentes.
                            </div>
                        <?php    
                        } else { ?>
                            <div class="table-responsive">
                                <table class="table table-premium">
                                    <thead>
                                        <tr>
                                            <th>C&oacute;digo de Asignatura</th>
                                            <th>Nombre</th>
                                            <th>Carreras</th>
                                            <th>Estado del programa</th>
                                            <th>Vigencia</th>
                                            <th>Gestionar Programa</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                        <?php foreach ($asignaturasAgrupadas as $item) { 
                                $Asignatura = $item['objeto']; 
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
                            ?>                            <tr class="fila-asignatura" data-carreras="<?= $dataCarreras ?>">
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
                                if (empty($nombresCarreras)) {
                                    echo "-";
                                } elseif (count($nombresCarreras) <= 2) {
                                    echo htmlspecialchars(implode(", ", $nombresCarreras));
                                } else {
                                    echo htmlspecialchars($nombresCarreras[0] . ", " . $nombresCarreras[1]);
                                    $restantes = count($nombresCarreras) - 2;
                                    $todosTexto = htmlspecialchars(implode(", ", $nombresCarreras));
                                    echo ' <span class="badge badge-info ml-1 px-2 py-1" style="cursor: pointer; background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); font-size: 0.75rem; font-weight: 600;" data-toggle="tooltip" data-placement="top" title="' . $todosTexto . '">+' . $restantes . ' más</span>';
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
                                    </tbody>
                                </table>
                            </div>
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
                $('[data-toggle="tooltip"]').tooltip();
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

            $(document).ready(function() {
                $('#tipoModificacion').on('change', function() {
                    var valor = $(this).val();
                    if (valor === 'eliminar') {
                        $('#grupoEliminar').removeClass('d-none');
                        $('#materiaEliminar').attr('required', true);
                        $('#grupoAgregar').addClass('d-none');
                        $('#materiaAgregar').removeAttr('required');
                    } else if (valor === 'agregar') {
                        $('#grupoAgregar').removeClass('d-none');
                        $('#materiaAgregar').attr('required', true);
                        $('#grupoEliminar').addClass('d-none');
                        $('#materiaEliminar').removeAttr('required');
                    } else {
                        $('#grupoEliminar').addClass('d-none');
                        $('#materiaEliminar').removeAttr('required');
                        $('#grupoAgregar').addClass('d-none');
                        $('#materiaAgregar').removeAttr('required');
                    }
                });

                $('#formModificacionRevista').on('submit', function(e) {
                    e.preventDefault();
                    
                    var btn = $(this).find('button[type="submit"]');
                    var originalHtml = btn.html();
                    btn.html('<span class="spinner-border spinner-border-sm mr-2" role="status" aria-hidden="true"></span> Enviando...').attr('disabled', true);

                    $.ajax({
                        url: '../controlSistema/profesor.informar.revista.php',
                        type: 'POST',
                        data: $(this).serialize(),
                        dataType: 'json',
                        success: function(response) {
                            btn.html(originalHtml).removeAttr('disabled');
                            if (response.success) {
                                alert('Solicitud enviada correctamente por correo a Vinculación Académica.');
                                $('#modalModificacionRevista').modal('hide');
                                $('#formModificacionRevista')[0].reset();
                                $('#grupoEliminar, #grupoAgregar').addClass('d-none').find('select').removeAttr('required');
                            } else {
                                alert('Error al enviar la solicitud: ' + (response.error || 'Intente nuevamente.'));
                            }
                        },
                        error: function() {
                            btn.html(originalHtml).removeAttr('disabled');
                            alert('Error de conexión con el servidor.');
                        }
                    });
                });
            });
        </script>

        <!-- Formulario POST Oculto para Enviar a Revisión -->
        <form id="formEnviarRevision" action="../controlSistema/programa.enviar.revision.php" method="POST" style="display:none;">
            <input type="hidden" name="idPrograma" id="idProgramaEnviar">
        </form>

        <!-- Modal para Solicitar Modificación Situación de Revista -->
        <div class="modal fade" id="modalModificacionRevista" tabindex="-1" role="dialog" aria-labelledby="modalModificacionRevistaLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content" style="border-radius: 16px; border: none; box-shadow: 0 15px 35px rgba(0,0,0,0.15);">
                    <div class="modal-header" style="background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); color: white; border-top-left-radius: 16px; border-top-right-radius: 16px; border-bottom: none;">
                        <h5 class="modal-title font-weight-bold" id="modalModificacionRevistaLabel">
                            <span class="oi oi-envelope-closed mr-2"></span> Informar modificación situación de revista
                        </h5>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form id="formModificacionRevista">
                        <div class="modal-body p-4">
                            <div class="alert alert-info" style="border-radius: 10px; font-size: 0.9rem;">
                                <strong>Nota:</strong> Esta solicitud será enviada por correo electrónico a <strong>Vinculación Académica</strong> para que realice las modificaciones correspondientes en el sistema.
                            </div>
                            
                            <div class="form-group">
                                <label for="tipoModificacion" class="font-weight-bold">Tipo de Modificación:</label>
                                <select class="form-control" id="tipoModificacion" name="tipoModificacion" required>
                                    <option value="">-- Seleccionar tipo --</option>
                                    <option value="eliminar">Eliminarme de una materia (dejar de cargar el programa)</option>
                                    <option value="agregar">Agregarme a una materia nueva (cargar un nuevo programa y ser responsable)</option>
                                </select>
                            </div>

                            <!-- Div para desvinculación -->
                            <div class="form-group d-none" id="grupoEliminar">
                                <label for="materiaEliminar" class="font-weight-bold">Seleccione la asignatura de la cual desea desvincularse:</label>
                                <select class="form-control" id="materiaEliminar" name="materiaEliminar">
                                    <option value="">-- Seleccionar asignatura --</option>
                                    <?php 
                                    if (!empty($asignaturasAgrupadas)) {
                                        foreach ($asignaturasAgrupadas as $item) { 
                                            $asig = $item['objeto'];
                                            echo '<option value="' . htmlspecialchars($asig->getId() . ' - ' . $asig->getNombre()) . '">' . htmlspecialchars($asig->getId() . ' - ' . $asig->getNombre()) . '</option>';
                                        }
                                    }
                                    ?>
                                </select>
                            </div>

                            <!-- Div para asignación -->
                            <div class="form-group d-none" id="grupoAgregar">
                                <label for="materiaAgregar" class="font-weight-bold">Seleccione la nueva asignatura a la cual desea asignarse:</label>
                                <select class="form-control" id="materiaAgregar" name="materiaAgregar">
                                    <option value="">-- Seleccionar asignatura --</option>
                                    <?php 
                                    foreach ($todasAsignaturasModal as $asig) {
                                        echo '<option value="' . htmlspecialchars($asig['id'] . ' - ' . $asig['nombre']) . '">' . htmlspecialchars($asig['id'] . ' - ' . $asig['nombre']) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="comentariosRevista" class="font-weight-bold">Comentarios / Observaciones adicionales:</label>
                                <textarea class="form-control" id="comentariosRevista" name="comentarios" rows="4" placeholder="Detalle los motivos, resoluciones o aclaraciones necesarias para Vinculación Académica..."></textarea>
                            </div>
                        </div>
                        <div class="modal-footer" style="border-top: none; padding: 1.5rem;">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal" style="border-radius: 8px;">Cancelar</button>
                            <button type="submit" class="btn btn-primary" style="background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); border: none; border-radius: 8px; padding: 0.5rem 1.5rem;">
                                <span class="oi oi-location mr-1"></span> Enviar Solicitud
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </body>
</html>