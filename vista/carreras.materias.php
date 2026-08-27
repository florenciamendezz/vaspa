<?php 
include_once '../lib/ControlAcceso.Class.php'; 
ControlAcceso::requierePermiso(PermisosSistema::PERMISO_CARRERAS);
include_once '../controlSistema/ManejadorCarrera.php';
include_once '../modeloSistema/BDConexionSistema.Class.php';

$manejadorCarrera = new ManejadorCarrera();
$carreras = $manejadorCarrera->getColeccion();

// Obtener departamentos para el modal de creación rápida
$db = BDConexionSistema::getInstancia();
$resDeptos = $db->query("SELECT * FROM departamento ORDER BY nombre ASC");
$departamentos = [];
if ($resDeptos && $resDeptos->num_rows > 0) {
    while ($row = $resDeptos->fetch_assoc()) {
        $departamentos[] = $row;
    }
}

// Obtener escuelas para el modal de creación rápida
$resEscuelas = $db->query("SELECT * FROM escuela ORDER BY nombre ASC");
$escuelas = [];
if ($resEscuelas && $resEscuelas->num_rows > 0) {
    while ($row = $resEscuelas->fetch_assoc()) {
        $escuelas[] = $row;
    }
}

// Obtener profesores para el modal de creación/edición rápida
$resProfs = $db->query("SELECT * FROM profesor ORDER BY apellido ASC, nombre ASC");
$profesores = [];
if ($resProfs && $resProfs->num_rows > 0) {
    while ($row = $resProfs->fetch_assoc()) {
        $profesores[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
    <head>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
      <script type="text/javascript" src="../lib/JQuery/jquery-3.3.1.js"></script>
      <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
      <script type="text/javascript" src="../lib/bootstrap-4.1.1-dist/js/bootstrap.min.js"></script>
      <link rel="stylesheet" href="../lib/bootstrap-4.1.1-dist/css/bootstrap.css" />
      <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.13.8/css/bootstrap-select.min.css" rel="stylesheet"/>
      <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.13.8/js/bootstrap-select.min.js"></script>
      <link rel="stylesheet" href="../lib/open-iconic-master/font/css/open-iconic-bootstrap.css" />
      <link rel="stylesheet" href="../lib/datatable/dataTables.bootstrap4.min.css" />
      <script type="text/javascript" src="../lib/datatable/jquery.dataTables.min.js"></script>
      <script type="text/javascript" src="../lib/datatable/dataTables.bootstrap4.min.js"></script>
      
      <!-- Google Fonts Outfit y Estilos Premium -->
      <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
      <link rel="stylesheet" href="../lib/css/premium.css" />
      
      <title><?php echo Constantes::NOMBRE_SISTEMA; ?> - Gestión de Materias por Carrera</title>
      
      <style>
          .toggle-switch {
              position: relative;
              display: inline-block;
              width: 50px;
              height: 24px;
          }
          .toggle-switch input { 
              opacity: 0;
              width: 0;
              height: 0;
          }
          .slider {
              position: absolute;
              cursor: pointer;
              top: 0; left: 0; right: 0; bottom: 0;
              background-color: #cbd5e1;
              transition: .4s;
              border-radius: 24px;
          }
          .slider:before {
              position: absolute;
              content: "";
              height: 16px; width: 16px;
              left: 4px; bottom: 4px;
              background-color: white;
              transition: .4s;
              border-radius: 50%;
          }
          input:checked + .slider {
              background-color: #6366f1;
          }
          input:checked + .slider:before {
              transform: translateX(26px);
          }
      </style>
    </head>
    <body>
        <?php include_once '../gui/navbar.php'; ?>
        <br>
        <div class="container-fluid">
            <div class="card card-premium">
                <div class="card-header card-header-premium d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">Gestión de Materias por Carrera</h3>
                    <div class="d-flex gap-2">
                        <button type="button" id="btnAsociarExistente" class="btn btn-light btn-sm" disabled style="display:none;">
                            <span class="oi oi-link-intact"></span> Asociar Materia
                        </button>
                        <button type="button" id="btnCrearAsociar" class="btn btn-primary btn-sm" disabled style="display:none;">
                            <span class="oi oi-plus"></span> Crear y Asociar Materia
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row justify-content-center mb-4">
                        <div class="col-sm-5">
                            <label for="carreraSelect">Seleccione la Carrera</label>
                            <select id="carreraSelect" class="selectpicker" data-width="100%" data-live-search="true" required title="Seleccione una Carrera" data-none-results-text="No se encontraron resultados" data-size="7" data-container="body">
                                <?php if (!empty($carreras)) {
                                    foreach ($carreras as $c) {
                                        echo '<option value="'.$c->getId().'">'.$c->getId().' - '.$c->getNombre().'</option>';
                                    }
                                } ?>
                            </select>
                        </div>
                    </div>
                    
                    <div id="alertPlaceholder"></div>
                    
                    <div id="tablaMateriasContainer">
                        <div class="alert alert-info text-center" role="alert">
                            Seleccione una carrera de la lista para gestionar las asignaturas y su pertenencia.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL ASOCIAR EXISTENTE -->
        <div class="modal fade" id="modalAsociarExistente" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content card-premium">
                    <div class="modal-header">
                        <h5 class="modal-title">Asociar Materia a la Carrera</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="selectAsignaturaExistente">Seleccione la Materia</label>
                            <select id="selectAsignaturaExistente" class="selectpicker" data-width="100%" data-live-search="true" title="Seleccione Asignatura" data-size="5" data-container="body">
                                <!-- Se carga dinámicamente vía Ajax -->
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="button" id="btnConfirmarAsociar" class="btn btn-primary">Asociar</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL CREAR NUEVA MATERIA Y ASOCIAR -->
        <div class="modal fade" id="modalCrearAsociar" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                <div class="modal-content card-premium">
                    <div class="modal-header">
                        <h5 class="modal-title">Crear Nueva Asignatura y Asociar a Carrera</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form id="formCrearMateria">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="newId">Código (4 dígitos)</label>
                                        <input type="text" id="newId" class="form-control" placeholder="Ej: 0174" maxlength="4" required>
                                    </div>
                                </div>
                                <div class="col-md-8">
                                    <div class="form-group">
                                        <label for="newNombre">Nombre de Asignatura</label>
                                        <input type="text" id="newNombre" class="form-control" placeholder="Ej: Programación I" required>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="newDepto">Departamento</label>
                                        <select id="newDepto" class="selectpicker" data-width="100%" data-live-search="true" required>
                                            <option value="">Seleccione Departamento</option>
                                            <?php foreach ($departamentos as $d) {
                                                echo '<option value="'.$d['id'].'">'.$d['nombre'].'</option>';
                                            } ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="newEscuela">Escuela</label>
                                        <select id="newEscuela" class="selectpicker" data-width="100%" data-live-search="true" required>
                                            <option value="">Seleccione Escuela</option>
                                            <?php foreach ($escuelas as $e) {
                                                echo '<option value="'.$e['id'].'">'.$e['nombre'].'</option>';
                                            } ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="newResponsables">Profesores Responsables</label>
                                <select id="newResponsables" class="selectpicker" data-width="100%" data-live-search="true" multiple title="Seleccione uno o más Responsables" data-actions-box="true">
                                    <?php foreach ($profesores as $p) {
                                        echo '<option value="'.$p['id'].'">'.$p['apellido'].', '.$p['nombre'].' ('.$p['email'].')</option>';
                                    } ?>
                                </select>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="button" id="btnConfirmarCrear" class="btn btn-primary">Crear y Asociar</button>
                    </div>
                </div>
            </div>
        </div>

        <?php include_once '../gui/footer.php'; ?>

        <script>
            $(document).ready(function() {
                var codCarrera = "";

                // Cargar materias de la carrera seleccionada
                $('#carreraSelect').change(function() {
                    codCarrera = $(this).val();
                    if(codCarrera) {
                        $('#btnAsociarExistente').show().prop('disabled', false);
                        $('#btnCrearAsociar').show().prop('disabled', false);
                        cargarTablaMaterias();
                    } else {
                        $('#btnAsociarExistente').hide().prop('disabled', true);
                        $('#btnCrearAsociar').hide().prop('disabled', true);
                        $('#tablaMateriasContainer').html('<div class="alert alert-info text-center" role="alert">Seleccione una carrera de la lista para gestionar las asignaturas y su pertenencia.</div>');
                    }
                });

                function cargarTablaMaterias() {
                    $('#tablaMateriasContainer').html('<div class="text-center my-4"><img src="../lib/img/loader.gif"/> Cargando materias de la carrera...</div>');
                    $.post('../lib/consultaAjax/carrerasMaterias/tablaMateriasPorCarrera.php', {codCarrera: codCarrera}, function(html) {
                        $('#tablaMateriasContainer').html(html);
                        
                        // Inicializar DataTables
                        $('#tablaCarreraMaterias').DataTable({
                            language: {
                                url: '../lib/datatable/es-ar.json'
                            },
                            aaSorting: [[1, 'asc']]
                        });

                        // Re-inicializar selectpickers en la tabla dinámica
                        $('.depto-select, .responsables-select').selectpicker();
                    });
                }

                // Cargar modal de asociación de materias existentes
                $('#btnAsociarExistente').click(function() {
                    $('#selectAsignaturaExistente').empty().prop('disabled', true).selectpicker('refresh');
                    $.post('../lib/consultaAjax/carrerasMaterias/obtenerMateriasNoAsociadas.php', {codCarrera: codCarrera}, function(res) {
                        var materias = JSON.parse(res);
                        if (materias.length > 0) {
                            materias.forEach(function(m) {
                                $('#selectAsignaturaExistente').append('<option value="' + m.id + '">' + m.id + ' - ' + m.nombre + '</option>');
                            });
                            $('#selectAsignaturaExistente').prop('disabled', false).selectpicker('refresh');
                        } else {
                            $('#selectAsignaturaExistente').append('<option value="">No hay asignaturas disponibles para asociar</option>').selectpicker('refresh');
                        }
                    });
                    $('#modalAsociarExistente').modal('show');
                });

                // Confirmar asociar materia existente
                $('#btnConfirmarAsociar').click(function() {
                    var idAsignatura = $('#selectAsignaturaExistente').val();
                    if(!idAsignatura) {
                        alert('Seleccione una materia para asociar');
                        return;
                    }
                    $.post('../lib/consultaAjax/carrerasMaterias/asociarMateria.php', {codCarrera: codCarrera, idAsignatura: idAsignatura}, function(res) {
                        var respuesta = JSON.parse(res);
                        if(respuesta.success) {
                            $('#modalAsociarExistente').modal('hide');
                            mostrarAlerta('success', 'Materia asociada con éxito');
                            cargarTablaMaterias();
                        } else {
                            alert(respuesta.error || 'Error al asociar la materia');
                        }
                    });
                });

                // Cargar modal de creación rápida
                $('#btnCrearAsociar').click(function() {
                    $('#formCrearMateria')[0].reset();
                    $('#newDepto, #newEscuela, #newResponsables').selectpicker('val', '');
                    $('#modalCrearAsociar').modal('show');
                });

                // Confirmar creación y asociación
                $('#btnConfirmarCrear').click(function() {
                    var id = $('#newId').val().trim();
                    var nombre = $('#newNombre').val().trim();
                    var idDepto = $('#newDepto').val();
                    var idEscuela = $('#newEscuela').val();
                    var responsables = $('#newResponsables').val();

                    if(id.length !== 4 || nombre === "" || idDepto === "" || idEscuela === "") {
                        alert('Por favor complete todos los campos obligatorios.');
                        return;
                    }

                    $.post('../lib/consultaAjax/carrerasMaterias/crearMateriaAsociar.php', {
                        codCarrera: codCarrera,
                        id: id,
                        nombre: nombre,
                        idDepartamento: idDepto,
                        idEscuela: idEscuela,
                        responsables: responsables
                    }, function(res) {
                        var respuesta = JSON.parse(res);
                        if(respuesta.success) {
                            $('#modalCrearAsociar').modal('hide');
                            mostrarAlerta('success', 'Materia creada y asociada correctamente');
                            cargarTablaMaterias();
                        } else {
                            alert(respuesta.error || 'Error al crear la materia');
                        }
                    });
                });

                // Cambiar estado Activo/Desactivado
                $(document).on('change', '.toggle-activo', function() {
                    var idAsignatura = $(this).data('id');
                    var activo = $(this).is(':checked') ? 1 : 0;
                    $.post('../lib/consultaAjax/carrerasMaterias/cambiarEstadoMateria.php', {
                        codCarrera: codCarrera,
                        idAsignatura: idAsignatura,
                        activo: activo
                    }, function(res) {
                        var respuesta = JSON.parse(res);
                        if(!respuesta.success) {
                            alert('No se pudo cambiar el estado de la materia.');
                        }
                    });
                });

                // Guardar cambios en Depto o Responsables de la fila
                $(document).on('change', '.depto-select, .responsables-select', function() {
                    var selectElem = $(this);
                    var idAsignatura = selectElem.attr('data-id') || selectElem.data('id') || selectElem.closest('tr').attr('data-id') || selectElem.closest('tr').find('td:first').text().trim();
                    
                    if (!idAsignatura) {
                        alert('Error: No se pudo determinar el código de la asignatura.');
                        return;
                    }

                    var deptoElem = $('.depto-select[data-id="' + idAsignatura + '"]');
                    var respElem = $('.responsables-select[data-id="' + idAsignatura + '"]');

                    var idDepto = deptoElem.length ? deptoElem.val() : selectElem.closest('tr').find('.depto-select').val();
                    var responsables = (respElem.length ? respElem.val() : selectElem.closest('tr').find('.responsables-select').val()) || [];

                    var datos = {
                        idAsignatura: idAsignatura,
                        idDepartamento: idDepto,
                        responsables: responsables
                    };

                    $.post('../lib/consultaAjax/carrerasMaterias/cambiarDatosMateria.php', datos, function(res) {
                        var respuesta = JSON.parse(res);
                        if(respuesta.success) {
                            mostrarAlerta('success', 'Cambios guardados correctamente.');
                        } else {
                            alert('Error al guardar:\n' + (respuesta.error || '(sin detalle)'));
                        }
                    });
                });


                // Quitar materia de la carrera (Desasociar)
                $(document).on('click', '.btn-quitar-materia', function() {
                    var idAsignatura = $(this).data('id');
                    var nombre = $(this).data('nombre');
                    if(confirm('¿Está seguro de desasociar la materia "' + nombre + '" de esta carrera?')) {
                        $.post('../lib/consultaAjax/carrerasMaterias/quitarMateria.php', {
                            codCarrera: codCarrera,
                            idAsignatura: idAsignatura
                        }, function(res) {
                            var respuesta = JSON.parse(res);
                            if(respuesta.success) {
                                mostrarAlerta('success', 'Materia desasociada con éxito');
                                cargarTablaMaterias();
                            } else {
                                alert(respuesta.error || 'Error al desasociar la materia');
                            }
                        });
                    }
                });

                function mostrarAlerta(tipo, mensaje) {
                    var html = '<div class="alert alert-' + tipo + ' alert-dismissible fade show text-center" role="alert">' +
                               mensaje +
                               '<button type="button" class="close" data-dismiss="alert" aria-label="Close">' +
                               '<span aria-hidden="true">&times;</span>' +
                               '</button></div>';
                    $('#alertPlaceholder').html(html);
                    setTimeout(function() {
                        $('.alert').alert('close');
                    }, 3500);
                }
            });
        </script>
    </body>
</html>
