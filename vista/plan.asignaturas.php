<?php
include_once '../lib/ControlAcceso.Class.php';

// Validamos permisos: Carreras o VA
$UsuarioSes = $_SESSION['usuario'] ?? null;
$perfil = "";
if (isset($UsuarioSes->roles[0])) {
    $perfil = $UsuarioSes->roles[0]->nombre;
}

if (!ControlAcceso::verificaPermiso(PermisosSistema::PERMISO_CARRERAS, $UsuarioSes) && $perfil != PermisosSistema::ROL_VINCULACION_ACADEMICA) {
    ControlAcceso::requierePermiso(PermisosSistema::PERMISO_CARRERAS);
}

include_once '../modeloSistema/Plan.Class.php';
include_once '../modeloSistema/Carrera.Class.php';
include_once '../modeloSistema/BDConexionSistema.Class.php';

$idCarrera = $_GET['idCarrera'] ?? '';
$idPlan = $_GET['id'] ?? '';

$plan = new Plan($idPlan);
$carrera = new Carrera($idCarrera);

if (is_null($plan->getId())) {
    header("Location: carreras.php");
    exit;
}

if (is_null($plan->getAnio_fin())) {
    $periodo = ' (' . $plan->getAnio_inicio() . ' - Presente)';
} else {
    $periodo = ' (' . $plan->getAnio_inicio() . ' - ' . $plan->getAnio_fin() . ')';
}

// Obtenemos el catálogo general de asignaturas para el autocompletado
$queryAsig = "SELECT id, nombre FROM asignatura ORDER BY nombre ASC";
$resAsig = BDConexionSistema::getInstancia()->query($queryAsig);
$catalogoAsignaturas = [];
if ($resAsig) {
    while ($row = $resAsig->fetch_assoc()) {
        $catalogoAsignaturas[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo Constantes::NOMBRE_SISTEMA; ?> - Asignaturas del Plan</title>

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
        .search-add-box {
            background: #f8fafc;
            border: 2px dashed #cbd5e1;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
            transition: all 0.2s ease;
        }
        .search-add-box:hover {
            border-color: #3b82f6;
            background: #f0f7ff;
        }
        .tag-info {
            font-size: 0.85rem;
            color: #64748b;
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
                        <h3 class="mb-1">Asignaturas del Plan <span style="color: #60a5fa; font-weight: 700;"><?= htmlspecialchars($plan->getId() . $periodo); ?></span></h3>
                        <div class="text-white" style="opacity: 0.9;">
                            Carrera: <strong><?= htmlspecialchars($carrera->getId() . ' - ' . $carrera->getNombre()); ?></strong>
                        </div>
                    </div>
                    <a href="carrera.verPlanes.php?id=<?= urlencode($idCarrera); ?>" class="btn btn-outline-light btn-sm">
                        <span class="oi oi-arrow-thick-left"></span> Volver a Planes
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

                <!-- Panel de Búsqueda y Carga Rápida / Al Vuelo -->
                <div class="search-add-box">
                    <h5 class="text-primary font-weight-bold mb-2">
                        <span class="oi oi-magnifying-glass"></span> Agregar Asignatura al Plan
                    </h5>
                    <p class="tag-info mb-3">
                        Busque una materia existente en el catálogo o escriba el nombre de una materia nueva para darla de alta automáticamente sin salir de la pantalla.
                    </p>

                    <div class="row align-items-center">
                        <div class="col-md-9 mb-2 mb-md-0">
                            <div class="input-group">
                                <input type="text" id="inputAsignatura" class="form-control form-control-lg" list="listaSugerencias" placeholder="Escriba el nombre o código de la asignatura..." autocomplete="off">
                                <datalist id="listaSugerencias">
                                    <?php foreach ($catalogoAsignaturas as $asig): ?>
                                        <option data-id="<?= htmlspecialchars($asig['id']); ?>" value="<?= htmlspecialchars($asig['nombre']); ?>">
                                            [Cód: <?= htmlspecialchars($asig['id']); ?>]
                                        </option>
                                    <?php endforeach; ?>
                                </datalist>
                                <div class="input-group-append">
                                    <button class="btn btn-secondary" type="button" onclick="$('#inputAsignatura').val('').focus();" title="Limpiar">
                                        <span class="oi oi-delete"></span>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <button type="button" id="btnAgregar" class="btn btn-success btn-lg btn-block" onclick="agregarOcrearAsignatura();">
                                <span class="oi oi-plus"></span> Agregar al Plan
                            </button>
                        </div>
                    </div>
                    <div id="sugerenciaNueva" class="mt-2" style="display: none;">
                        <span class="badge badge-warning p-2">
                            <span class="oi oi-bolt"></span> Esta materia no está en el catálogo. Al hacer clic en <strong>Agregar</strong> se creará y vinculará automáticamente.
                        </span>
                    </div>
                </div>

                <!-- Tabla de Asignaturas Vinculadas -->
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="text-dark font-weight-bold mb-0">
                        <span class="oi oi-list"></span> Materias cargadas en este Plan
                    </h4>
                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="cargarTablaAsignaturas();" title="Recargar tabla">
                        <span class="oi oi-reload"></span> Actualizar lista
                    </button>
                </div>

                <div id="contenedorTabla">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="sr-only">Cargando...</span>
                        </div>
                        <p class="text-muted mt-2">Cargando asignaturas del plan...</p>
                    </div>
                </div>

            </div>

            <div class="card-footer text-center">
                <a href="carrera.verPlanes.php?id=<?= urlencode($idCarrera); ?>" class="btn btn-primary mr-2">
                    <span class="oi oi-arrow-thick-left"></span> Volver a Planes de la Carrera
                </a>
                <a href="carreras.php" class="btn btn-outline-secondary">
                    <span class="oi oi-account-logout"></span> Volver a Carreras
                </a>
            </div>
        </div>
    </div>

    <?php include_once '../gui/footer.php'; ?>

    <script type="text/javascript">
        var codPlan = "<?= addslashes($idPlan); ?>";
        var catalogo = <?= json_encode($catalogoAsignaturas); ?>;

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

        function cargarTablaAsignaturas() {
            $('#contenedorTabla').load('../lib/consultaAjax/planAsignatura/asignaturasDelPlan.php?id=' + encodeURIComponent(codPlan));
        }

        // Detectar si lo escrito es una materia nueva o existente
        $('#inputAsignatura').on('input', function() {
            var val = $(this).val().trim().toLowerCase();
            if (val.length === 0) {
                $('#sugerenciaNueva').hide();
                return;
            }

            var existe = catalogo.some(function(item) {
                return item.nombre.toLowerCase() === val || item.id.toLowerCase() === val;
            });

            if (!existe && val.length > 2) {
                $('#sugerenciaNueva').show();
            } else {
                $('#sugerenciaNueva').hide();
            }
        });

        // Permitir agregar con la tecla ENTER
        $('#inputAsignatura').on('keypress', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                agregarOcrearAsignatura();
            }
        });

        function agregarOcrearAsignatura() {
            var valor = $('#inputAsignatura').val().trim();
            if (valor.length === 0) {
                mostrarAlerta('warning', 'Por favor, ingrese o seleccione el nombre de una asignatura.');
                $('#inputAsignatura').focus();
                return;
            }

            // Buscar si coincide con alguna del catálogo
            var idEncontrado = '';
            for (var i = 0; i < catalogo.length; i++) {
                if (catalogo[i].nombre.toLowerCase() === valor.toLowerCase() || catalogo[i].id.toLowerCase() === valor.toLowerCase()) {
                    idEncontrado = catalogo[i].id;
                    break;
                }
            }

            var payload = {
                idPlan: codPlan
            };

            if (idEncontrado !== '') {
                payload.idAsignatura = idEncontrado;
            } else {
                payload.nombreAsignatura = valor;
            }

            $('#btnAgregar').prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Guardando...');

            $.ajax({
                url: '../lib/consultaAjax/planAsignatura/agregarAsignaturaPlan.php',
                type: 'POST',
                data: payload,
                dataType: 'json',
                success: function(resp) {
                    $('#btnAgregar').prop('disabled', false).html('<span class="oi oi-plus"></span> Agregar al Plan');
                    if (resp.status === 'success') {
                        mostrarAlerta('success', '<strong>¡Éxito!</strong> ' + resp.message);
                        $('#inputAsignatura').val('');
                        $('#sugerenciaNueva').hide();
                        
                        // Si era nueva, agregarla al catálogo local para futuros autocompletados
                        if (!idEncontrado && resp.idAsignatura) {
                            catalogo.push({ id: resp.idAsignatura, nombre: valor });
                            $('#listaSugerencias').append('<option data-id="' + resp.idAsignatura + '" value="' + valor + '">');
                        }

                        cargarTablaAsignaturas();
                        $('#inputAsignatura').focus();
                    } else if (resp.status === 'warning') {
                        mostrarAlerta('warning', resp.message);
                    } else {
                        mostrarAlerta('danger', '<strong>Error:</strong> ' + resp.message);
                    }
                },
                error: function(xhr, status, error) {
                    $('#btnAgregar').prop('disabled', false).html('<span class="oi oi-plus"></span> Agregar al Plan');
                    mostrarAlerta('danger', 'Error de comunicación con el servidor: ' + error);
                }
            });
        }

        function quitarAsignatura(idAsignatura, nombreAsignatura) {
            bootbox.confirm({
                title: "Desvincular Asignatura",
                message: "¿Está seguro de que desea quitar la asignatura <b>" + idAsignatura + " - " + nombreAsignatura + "</b> de este plan de estudio?",
                buttons: {
                    cancel: {
                        label: '<span class="oi oi-x"></span> Cancelar',
                        className: 'btn-outline-secondary'
                    },
                    confirm: {
                        label: '<span class="oi oi-trash"></span> Quitar del Plan',
                        className: 'btn-danger'
                    }
                },
                callback: function (result) {
                    if (result) {
                        $.ajax({
                            url: '../lib/consultaAjax/planAsignatura/quitarAsignaturaPlan.php',
                            type: 'POST',
                            data: {
                                idPlan: codPlan,
                                idAsignatura: idAsignatura
                            },
                            dataType: 'json',
                            success: function(resp) {
                                if (resp.status === 'success') {
                                    mostrarAlerta('success', 'La asignatura fue desvinculada del plan correctamente.');
                                    cargarTablaAsignaturas();
                                } else {
                                    mostrarAlerta('danger', '<strong>Error:</strong> ' + resp.message);
                                }
                            },
                            error: function(xhr, status, error) {
                                mostrarAlerta('danger', 'Error de comunicación: ' + error);
                            }
                        });
                    }
                }
            });
        }

        $(document).ready(function() {
            cargarTablaAsignaturas();
        });
    </script>
</body>
</html>