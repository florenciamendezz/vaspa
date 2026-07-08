<?php
header('Content-Type: text/html; charset=UTF-8');
include_once '../lib/ControlAcceso.Class.php';
require_once '../modeloSistema/BDConexionSistema.Class.php';

// Validar que el usuario tenga rol de Administrador o Vinculación Académica
$usuario = $_SESSION['usuario'];
$rol = (isset($usuario->roles) && is_array($usuario->roles) && count($usuario->roles) > 0) ? $usuario->roles[0]->nombre : '';
if ($rol != PermisosSistema::ROL_ADMIN && $rol != PermisosSistema::ROL_VINCULACION_ACADEMICA) {
    header("Location: inicio.php");
    exit();
}

$db = BDConexionSistema::getInstancia();

// Obtener los profesores oficiales ordenados por apellido, nombre
$resProfesores = $db->query("SELECT id, nombre, apellido FROM profesor ORDER BY apellido ASC, nombre ASC");
$profesores = [];
if ($resProfesores) {
    while ($row = $resProfesores->fetch_assoc()) {
        $profesores[] = $row;
    }
}

// Obtener las carreras para el filtro superior
$resCarreras = $db->query("SELECT id, nombre FROM carrera ORDER BY nombre ASC");
$carreras = [];
if ($resCarreras) {
    while ($row = $resCarreras->fetch_assoc()) {
        $carreras[] = $row;
    }
}

// Obtener todas las asignaturas, sus responsables y las carreras asociadas
$resAsignaturas = $db->query("
    SELECT a.id, a.nombre, 
           GROUP_CONCAT(DISTINCT ar.idProfesor) as responsables_ids,
           GROUP_CONCAT(DISTINCT CONCAT(c.id, ' - ', c.nombre) SEPARATOR '; ') as carreras_nombres
    FROM asignatura a
    LEFT JOIN asignatura_responsable ar ON a.id = ar.idAsignatura
    LEFT JOIN carrera_asignatura ca ON a.id = ca.idAsignatura
    LEFT JOIN carrera c ON ca.idCarrera = c.id
    GROUP BY a.id, a.nombre
    ORDER BY a.nombre ASC
");
$asignaturas = [];
if ($resAsignaturas) {
    while ($row = $resAsignaturas->fetch_assoc()) {
        $row['responsables'] = !empty($row['responsables_ids']) ? explode(',', $row['responsables_ids']) : [];
        $asignaturas[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= Constantes::NOMBRE_SISTEMA; ?> - Asociar Profesores Responsables</title>
    
    <!-- Google Fonts Outfit -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- CSS & JS -->
    <link rel="stylesheet" href="../lib/bootstrap-4.1.1-dist/css/bootstrap.css" />
    <link rel="stylesheet" href="../lib/open-iconic-master/font/css/open-iconic-bootstrap.css" />
    <link rel="stylesheet" href="../lib/datatable/dataTables.bootstrap4.min.css" />
    
    <script type="text/javascript" src="../lib/JQuery/jquery-3.3.1.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
    <script type="text/javascript" src="../lib/bootstrap-4.1.1-dist/js/bootstrap.min.js"></script>
    
    <!-- Bootstrap Select CSS & JS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.13.8/css/bootstrap-select.min.css" rel="stylesheet"/>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.13.8/js/bootstrap-select.min.js"></script>
    
    <script type="text/javascript" src="../lib/datatable/jquery.dataTables.min.js"></script>
    <script type="text/javascript" src="../lib/datatable/dataTables.bootstrap4.min.js"></script>      

    <style>
        body {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            color: #1e293b;
            font-family: 'Outfit', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            min-height: 100vh;
        }
        
        .card-premium {
            background: rgba(255, 255, 255, 0.95);
            border: none;
            border-radius: 16px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.05);
            backdrop-filter: blur(10px);
        }
        
        .card-header-gradient {
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            color: white;
            border-top-left-radius: 16px !important;
            border-top-right-radius: 16px !important;
            border-bottom: none;
            padding: 1.5rem;
        }

        .table-premium th {
            background-color: #f1f5f9;
            color: #475569;
            font-weight: 600;
            border: none;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
        }

        .table-premium td {
            vertical-align: middle !important;
            border-bottom: 1px solid #f1f5f9;
            color: #334155;
            font-size: 0.9rem;
        }

        .select-premium {
            border: 1.5px solid #cbd5e1;
            border-radius: 8px;
            padding: 0.375rem 2rem 0.375rem 0.75rem;
            font-size: 0.875rem;
            color: #334155;
            background-color: #fff;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
            cursor: pointer;
            width: 100%;
            max-width: 320px;
        }

        .select-premium:focus {
            border-color: #6366f1;
            outline: 0;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
        }

        /* Toast de notificación premium */
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
        }

        .custom-toast {
            min-width: 250px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            border-left: 5px solid #10b981;
            opacity: 0;
            transition: opacity 0.3s ease;
            pointer-events: none;
        }

        .custom-toast.show {
            opacity: 1;
            pointer-events: auto;
        }

        .custom-toast.error {
            border-left-color: #ef4444;
        }
        
        .btn-back {
            background-color: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            transition: background-color 0.2s ease;
            border-radius: 8px;
        }
        
        .btn-back:hover {
            background-color: rgba(255, 255, 255, 0.3);
            color: white;
        }
    </style>
</head>
<body>

    <?php include_once '../gui/navbar.php'; ?>

    <div class="container my-5">
        
        <div class="card card-premium mb-4">
            <div class="card-header card-header-gradient">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="h4 font-weight-bold mb-1">
                            <span class="oi oi-person mr-2"></span>
                            Asociar Profesores Responsables a Asignaturas
                        </h2>
                        <p class="mb-0 text-white-50" style="font-size: 0.9rem;">
                            Defina o modifique el profesor responsable encargado de cada asignatura del establecimiento.
                        </p>
                    </div>
                    <a href="inicio.php" class="btn btn-back px-3 py-2">
                        <span class="oi oi-arrow-left mr-1"></span> Volver al Panel
                    </a>
                </div>
            </div>
            
            <div class="card-body p-4">
                
                <!-- Selector superior para filtrar por carrera -->
                <div class="row mb-4">
                    <div class="col-md-6 col-sm-12">
                        <div class="form-group mb-0">
                            <label for="filtroCarrera" class="font-weight-bold text-secondary mb-1" style="font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.03em;">Filtrar por Carrera:</label>
                            <select id="filtroCarrera" class="selectpicker" data-live-search="true" data-width="100%" title="-- Mostrar todas las carreras --">
                                <option value="">-- Mostrar todas las carreras --</option>
                                <?php foreach ($carreras as $car): ?>
                                    <option value="<?= htmlspecialchars($car['id'] . ' - ' . $car['nombre']); ?>">
                                        <?= htmlspecialchars($car['id'] . ' - ' . $car['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover table-premium" id="tablaAsociacion">
                        <thead>
                            <tr>
                                <th style="width: 12%;">Código</th>
                                <th style="width: 33%;">Asignatura</th>
                                <th style="width: 25%;">Carrera(s)</th>
                                <th style="width: 30%;">Profesor Responsable Asignado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($asignaturas as $asig): ?>
                                <tr>
                                    <td class="font-weight-bold text-secondary"><?= htmlspecialchars($asig['id']); ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($asig['nombre']); ?></strong>
                                    </td>
                                    <td>
                                        <div style="max-height: 120px; overflow-y: auto;">
                                            <?php 
                                            if (!empty($asig['carreras_nombres'])) {
                                                $arrCarreras = explode('; ', $asig['carreras_nombres']);
                                                foreach ($arrCarreras as $carNombre) {
                                                    echo '<span class="badge badge-light border text-dark mb-1 p-1 d-inline-block text-wrap text-left" style="max-width: 100%; font-size: 0.78rem;">' . htmlspecialchars($carNombre) . '</span><br>';
                                                }
                                            } else {
                                                echo '<span class="text-muted font-italic" style="font-size: 0.8rem;">Sin carreras asignadas</span>';
                                            }
                                            ?>
                                        </div>
                                    </td>
                                    <td>
                                        <select class="selectpicker select-premium" multiple data-live-search="true" data-actions-box="true" data-width="100%" onchange="cambiarProfesores('<?= htmlspecialchars($asig['id']); ?>', $(this).val())" title="-- Sin responsable --" data-selected-text-format="count > 2">
                                            <?php foreach ($profesores as $prof): ?>
                                                <option value="<?= $prof['id']; ?>" <?= (in_array($prof['id'], $asig['responsables'])) ? 'selected' : ''; ?>>
                                                    <?= htmlspecialchars($prof['apellido'] . ', ' . $prof['nombre']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <!-- Contenedor de Toasts de Notificación -->
    <div class="toast-container">
        <div id="toastSuccess" class="custom-toast p-3">
            <div class="d-flex align-items-center">
                <span class="oi oi-check text-success mr-3" style="font-size: 1.2rem;"></span>
                <div>
                    <h6 class="mb-0 font-weight-bold">Cambio Guardado</h6>
                    <small class="text-muted">Los profesores responsables se actualizaron con éxito.</small>
                </div>
            </div>
        </div>
        
        <div id="toastError" class="custom-toast error p-3">
            <div class="d-flex align-items-center">
                <span class="oi oi-circle-x text-danger mr-3" style="font-size: 1.2rem;"></span>
                <div>
                    <h6 class="mb-0 font-weight-bold">Error al Guardar</h6>
                    <small id="errorMsg" class="text-muted">No se pudo realizar el cambio.</small>
                </div>
            </div>
        </div>
    </div>

    <?php include_once '../gui/footer.php'; ?>

    <script>
        $(document).ready(function() {
            var table = $('#tablaAsociacion').DataTable({
                "language": {
                    "url": "../lib/datatable/es-ar.json"
                },
                "pageLength": 25,
                "ordering": true,
                "order": [[1, "asc"]]
            });

            $('#filtroCarrera').on('change', function() {
                var valor = $(this).val();
                table.column(2).search(valor ? valor : '', false, false).draw();
            });
        });

        function cambiarProfesores(idAsignatura, idsProfesores) {
            $.ajax({
                url: '../controlSistema/asignatura.cambiar.profesor.php',
                type: 'POST',
                data: {
                    idAsignatura: idAsignatura,
                    idsProfesores: idsProfesores || []
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        showToast('#toastSuccess');
                    } else {
                        $('#errorMsg').text(response.error || 'Ocurrió un error inesperado.');
                        showToast('#toastError');
                    }
                },
                error: function() {
                    $('#errorMsg').text('Error de conexión con el servidor.');
                    showToast('#toastError');
                }
            });
        }

        function showToast(selector) {
            const toast = $(selector);
            toast.addClass('show');
            setTimeout(function() {
                toast.removeClass('show');
            }, 3000);
        }
    </script>

</body>
</html>
