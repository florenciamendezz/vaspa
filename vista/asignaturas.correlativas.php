<?php
include_once '../lib/ControlAcceso.Class.php';

// Validamos permisos: Asignaturas o VA
$UsuarioSes = $_SESSION['usuario'] ?? null;
$perfil = "";
if (isset($UsuarioSes->roles[0])) {
    $perfil = $UsuarioSes->roles[0]->nombre;
}

if (!ControlAcceso::verificaPermiso(PermisosSistema::PERMISO_ASIGNATURAS, $UsuarioSes) && $perfil != PermisosSistema::ROL_VINCULACION_ACADEMICA) {
    ControlAcceso::requierePermiso(PermisosSistema::PERMISO_ASIGNATURAS);
}

include_once '../controlSistema/ManejadorAsignatura.php';
include_once '../modeloSistema/Asignatura.Class.php';
include_once '../modeloSistema/Plan.Class.php';
include_once '../modeloSistema/BDConexionSistema.Class.php';

$idAsignatura = $_GET['idAsignatura'] ?? '';
$idPlan = $_GET['idPlan'] ?? '';

$asignatura = new Asignatura($idAsignatura);
$plan = new Plan($idPlan);

if (is_null($asignatura->getId()) || is_null($plan->getId())) {
    header("Location: carreras.php");
    exit;
}

// Obtenemos las asignaturas del plan (y del catálogo) para el selector de correlativas
$queryOtras = "SELECT id, nombre FROM asignatura WHERE id != '{$idAsignatura}' ORDER BY nombre ASC";
$resOtras = BDConexionSistema::getInstancia()->query($queryOtras);
$materiasCatalogo = [];
if ($resOtras) {
    while ($row = $resOtras->fetch_assoc()) {
        $materiasCatalogo[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo Constantes::NOMBRE_SISTEMA; ?> - Correlatividades de Asignatura</title>

    <link rel="stylesheet" href="../lib/bootstrap-4.1.1-dist/css/bootstrap.css" />
    <link rel="stylesheet" href="../lib/open-iconic-master/font/css/open-iconic-bootstrap.css" />
    <link rel="stylesheet" href="../lib/datatable/dataTables.bootstrap4.min.css" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.13.8/css/bootstrap-select.min.css" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../lib/css/premium.css" />

    <script type="text/javascript" src="../lib/JQuery/jquery-3.3.1.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js"></script>
    <script type="text/javascript" src="../lib/bootstrap-4.1.1-dist/js/bootstrap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.13.8/js/bootstrap-select.min.js"></script>
    <script type="text/javascript" src="../lib/datatable/jquery.dataTables.min.js"></script>
    <script type="text/javascript" src="../lib/datatable/dataTables.bootstrap4.min.js"></script>
    <script src="../lib/bootbox/bootbox.js"></script>
    <script src="../lib/bootbox/bootbox.locales.js"></script>

    <style>
        .form-correlativa-box {
            background: #f8fafc;
            border: 2px dashed #cbd5e1;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
            transition: all 0.2s ease;
        }
        .form-correlativa-box:hover {
            border-color: #3b82f6;
            background: #f0f7ff;
        }
    </style>
</head>
<body>

    <?php include_once '../gui/navbar.php'; ?>

    <div class="container my-4">
        <div class="card card-premium">
            <div class="card-header card-header-premium">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-1">Correlatividades de: <span style="color: #60a5fa; font-weight: 700;"><?= htmlspecialchars($asignatura->getId() . ' - ' . $asignatura->getNombre()); ?></span></h3>
                        <div class="text-white" style="opacity: 0.9;">
                            Plan de Estudio: <strong><?= htmlspecialchars($plan->getId()); ?></strong>
                        </div>
                    </div>
                    <a href="plan.asignaturas.php?id=<?= urlencode($plan->getId()); ?>&idCarrera=<?= urlencode($plan->getIdCarrera()); ?>" class="btn btn-outline-light btn-sm">
                        <span class="oi oi-arrow-thick-left"></span> Volver a Materias del Plan
                    </a>
                </div>
            </div>

            <div class="card-body">
                
                <!-- Feedback Toast / Alert -->
                <div id="alertFeedback" style="display: none;" class="alert alert-dismissible fade show" role="alert">
                    <span id="alertFeedbackText"></span>
                    <button type="button" class="close" onclick="$('#alertFeedback').hide();">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <!-- Formulario Interactivo para Agregar / Modificar Correlatividades -->
                <div class="form-correlativa-box">
                    <h5 class="text-primary font-weight-bold mb-2">
                        <span class="oi oi-plus"></span> Agregar o Modificar Correlatividades
                    </h5>
                    <p class="text-muted mb-3" style="font-size: 0.9rem;">
                        Seleccione la materia correlativa, el requisito exigido y si es <strong>Precedente</strong> (materia que el alumno debe haber cursado/aprobado antes de cursar esta) o <strong>Subsiguiente</strong> (materia que requerirá esta).
                    </p>

                    <form id="formNuevaCorrelativa" onsubmit="event.preventDefault(); agregarCorrelativa();">
                        <div class="form-row">
                            <div class="col-md-5 mb-2">
                                <label for="selectCorrelativa" class="font-weight-bold">Asignatura Correlativa</label>
                                <select id="selectCorrelativa" class="form-control selectpicker" data-live-search="true" data-size="8" title="Seleccione una asignatura..." required>
                                    <?php foreach ($materiasCatalogo as $m): ?>
                                        <option value="<?= htmlspecialchars($m['id']); ?>" data-tokens="<?= htmlspecialchars($m['nombre'] . ' ' . $m['id']); ?>">
                                            <?= htmlspecialchars($m['id'] . ' - ' . $m['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-3 mb-2">
                                <label for="selectRequisito" class="font-weight-bold">Requisito</label>
                                <select id="selectRequisito" class="form-control" required>
                                    <option value="Regular">Regular (Cursada)</option>
                                    <option value="Aprobada">Aprobada (Final)</option>
                                </select>
                            </div>

                            <div class="col-md-2 mb-2">
                                <label for="selectTipo" class="font-weight-bold">Tipo</label>
                                <select id="selectTipo" class="form-control" required>
                                    <option value="Precedente">Precedente (Previa)</option>
                                    <option value="Subsiguiente">Subsiguiente (Posterior)</option>
                                </select>
                            </div>

                            <div class="col-md-2 mb-2 d-flex align-items-end">
                                <button type="submit" id="btnGuardarCorrelativa" class="btn btn-success btn-block" style="height: 38px;">
                                    <span class="oi oi-plus"></span> Agregar
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Tabla de Correlatividades Actuales -->
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="text-dark font-weight-bold mb-0">
                        <span class="oi oi-list"></span> Correlatividades Vigentes en el Plan
                    </h4>
                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="cargarTablaCorrelativas();" title="Recargar tabla">
                        <span class="oi oi-reload"></span> Actualizar lista
                    </button>
                </div>

                <div id="contenedorTablaCorrelativas">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="sr-only">Cargando...</span>
                        </div>
                        <p class="text-muted mt-2">Cargando correlatividades...</p>
                    </div>
                </div>

            </div>

            <div class="card-footer text-center">
                <a href="plan.asignaturas.php?id=<?= urlencode($plan->getId()); ?>&idCarrera=<?= urlencode($plan->getIdCarrera()); ?>" class="btn btn-primary mr-2">
                    <span class="oi oi-arrow-thick-left"></span> Volver a Asignaturas del Plan
                </a>
                <a href="carrera.verPlanes.php?id=<?= urlencode($plan->getIdCarrera()); ?>" class="btn btn-outline-secondary">
                    <span class="oi oi-list"></span> Volver a Planes
                </a>
            </div>
        </div>
    </div>

    <?php include_once '../gui/footer.php'; ?>

    <script type="text/javascript">
        var idPlanActual = "<?= addslashes($idPlan); ?>";
        var idAsignaturaActual = "<?= addslashes($idAsignatura); ?>";

        function mostrarAlerta(tipo, mensaje) {
            var alertBox = $('#alertFeedback');
            alertBox.removeClass('alert-success alert-danger alert-warning alert-info');
            alertBox.addClass('alert-' + tipo);
            $('#alertFeedbackText').html(mensaje);
            alertBox.fadeIn();
            setTimeout(function() {
                alertBox.fadeOut();
            }, 6000);
        }

        function cargarTablaCorrelativas() {
            $('#contenedorTablaCorrelativas').load('../lib/consultaAjax/asignaturaCorrelativa/correlativasDeAsignatura.php?idAsignatura=' + encodeURIComponent(idAsignaturaActual) + '&idPlan=' + encodeURIComponent(idPlanActual));
        }

        function agregarCorrelativa() {
            var idCorrelativa = $('#selectCorrelativa').val();
            var requisito = $('#selectRequisito').val();
            var tipo = $('#selectTipo').val();

            if (!idCorrelativa) {
                mostrarAlerta('warning', 'Debe seleccionar una asignatura correlativa.');
                return;
            }

            $('#btnGuardarCorrelativa').prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Guardando...');

            $.ajax({
                url: '../lib/consultaAjax/asignaturaCorrelativa/agregarCorrelativa.php',
                type: 'POST',
                data: {
                    idPlan: idPlanActual,
                    idAsignatura: idAsignaturaActual,
                    idCorrelativa: idCorrelativa,
                    requisito: requisito,
                    tipo: tipo
                },
                dataType: 'json',
                success: function(resp) {
                    $('#btnGuardarCorrelativa').prop('disabled', false).html('<span class="oi oi-plus"></span> Agregar');
                    if (resp.status === 'success') {
                        mostrarAlerta('success', '<strong>¡Éxito!</strong> ' + resp.message);
                        $('#selectCorrelativa').val('').selectpicker('refresh');
                        cargarTablaCorrelativas();
                    } else if (resp.status === 'warning') {
                        mostrarAlerta('warning', resp.message);
                    } else {
                        mostrarAlerta('danger', '<strong>Error:</strong> ' + resp.message);
                    }
                },
                error: function(xhr, status, error) {
                    $('#btnGuardarCorrelativa').prop('disabled', false).html('<span class="oi oi-plus"></span> Agregar');
                    mostrarAlerta('danger', 'Error de comunicación: ' + error);
                }
            });
        }

        function cambiarRequisito(idCorrelativaDe, nuevoRequisito) {
            $.ajax({
                url: '../lib/consultaAjax/asignaturaCorrelativa/cambiarRequisito.php',
                type: 'POST',
                data: {
                    id: idCorrelativaDe,
                    nuevoRequisito: nuevoRequisito
                },
                dataType: 'json',
                success: function(resp) {
                    if (resp.status === 'success') {
                        mostrarAlerta('success', resp.message);
                        cargarTablaCorrelativas();
                    } else {
                        mostrarAlerta('danger', resp.message);
                    }
                },
                error: function(xhr, status, error) {
                    mostrarAlerta('danger', 'Error al modificar requisito: ' + error);
                }
            });
        }

        function eliminarCorrelativa(idCorrelativaDe, nombreAsignatura, tipo) {
            bootbox.confirm({
                title: "Eliminar Regla de Correlatividad",
                message: "¿Está seguro de que desea eliminar la correlatividad " + tipo.toLowerCase() + " con <b>" + nombreAsignatura + "</b> para este plan?",
                buttons: {
                    cancel: {
                        label: '<span class="oi oi-x"></span> Cancelar',
                        className: 'btn-outline-secondary'
                    },
                    confirm: {
                        label: '<span class="oi oi-trash"></span> Eliminar Regla',
                        className: 'btn-danger'
                    }
                },
                callback: function (result) {
                    if (result) {
                        $.ajax({
                            url: '../lib/consultaAjax/asignaturaCorrelativa/eliminarCorrelativa.php',
                            type: 'POST',
                            data: {
                                id: idCorrelativaDe,
                                idPlan: idPlanActual,
                                idAsignatura: idAsignaturaActual
                            },
                            dataType: 'json',
                            success: function(resp) {
                                if (resp.status === 'success') {
                                    mostrarAlerta('success', resp.message);
                                    cargarTablaCorrelativas();
                                } else {
                                    mostrarAlerta('danger', resp.message);
                                }
                            },
                            error: function(xhr, status, error) {
                                mostrarAlerta('danger', 'Error al eliminar: ' + error);
                            }
                        });
                    }
                }
            });
        }

        $(document).ready(function() {
            cargarTablaCorrelativas();
        });
    </script>
</body>
</html>