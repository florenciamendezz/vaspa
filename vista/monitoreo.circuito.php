<?php
header('Content-Type: text/html; charset=UTF-8');
include_once '../lib/ControlAcceso.Class.php';
include_once '../modeloSistema/BDConexionSistema.Class.php';
include_once '../modeloSistema/ProgramaPDFDetalle.Class.php';
include_once '../modeloSistema/Profesor.Class.php';

// Validar que el usuario sea Admin o Vinculación Académica
$UsuarioSes = $_SESSION['usuario'];
$perfil = "";
if (isset($UsuarioSes->roles[0])) {
    $perfil = $UsuarioSes->roles[0]->nombre;
}
if ($perfil !== PermisosSistema::ROL_ADMIN && $perfil !== PermisosSistema::ROL_VINCULACION_ACADEMICA) {
    header("Location: ../vista/panelVA.php");
    exit();
}

$conexion = BDConexionSistema::getInstancia();
$anioActual = date("Y");
if (isset($_GET['anio']) && is_numeric($_GET['anio'])) {
    $anioActual = intval($_GET['anio']);
}

// 1. Obtener estado de vacancia de Escuela
$vacanciaActiva = false;
$sqlVac = "SELECT valor FROM configuracion_sistema WHERE clave = 'vacancia_escuela'";
$resVac = $conexion->query($sqlVac);
if ($resVac && $resVac->num_rows > 0) {
    $rowVac = $resVac->fetch_assoc();
    $vacanciaActiva = ($rowVac['valor'] == '1');
}

// 2. Obtener lista de asignaturas ocultas
$ocultas = array();
$sqlOcultas = "SELECT valor FROM configuracion_sistema WHERE clave = 'asignaturas_ocultas_panel_va'";
$resOcultas = $conexion->query($sqlOcultas);
if ($resOcultas && $resOcultas->num_rows > 0) {
    $rowOcultas = $resOcultas->fetch_assoc();
    $val = trim($rowOcultas['valor']);
    if (!empty($val)) {
        $ocultas = explode(',', $val);
        $ocultas = array_map('trim', $ocultas);
    }
}

// Obtener si se debe mostrar las asignaturas ocultas por GET o sesión
$mostrarOcultas = isset($_GET['mostrar_ocultas']) && $_GET['mostrar_ocultas'] == '1';

// 3. Obtener todas las asignaturas de planes vigentes
$sqlAsignaturas = "SELECT DISTINCT a.id as idAsignatura, a.nombre as nombreAsignatura, a.idProfesor, 
                    p.nombre as nombreProf, p.apellido as apellidoProf, p.email as emailProf,
                    pl.id as codPlan, c.nombre as nombreCarrera, a.es_institucional,
                    ppd.id as idProgramaPDF, ppd.ruta_archivo, ppd.en_revision, ppd.aprobado_escuela,
                    ppd.aprobado_va, ppd.aprobado_depto, ppd.aprobado_va_firma, ppd.fue_desaprobado,
                    ppd.comentario_desaprobacion, ppd.fecha_ultimo_movimiento_circuito
                   FROM plan pl
                   JOIN plan_asignatura pa ON pl.id = pa.idPlan
                   JOIN asignatura a ON pa.idAsignatura = a.id
                   LEFT JOIN profesor p ON a.idProfesor = p.id
                   INNER JOIN carrera c ON pl.idCarrera = c.id
                   LEFT JOIN programa_pdf_detalle ppd ON a.id = ppd.id_asignatura AND ppd.anio = {$anioActual}
                   WHERE (pl.anio_inicio <= '{$anioActual}' AND (pl.anio_fin >= '{$anioActual}' OR pl.anio_fin IS NULL))
                   ORDER BY c.nombre ASC, a.nombre ASC";

$resAsignaturas = $conexion->query($sqlAsignaturas);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title><?php echo Constantes::NOMBRE_SISTEMA; ?> - Monitoreo del Circuito</title>
    
    <script type="text/javascript" src="../lib/JQuery/jquery-3.3.1.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
    <script type="text/javascript" src="../lib/bootstrap-4.1.1-dist/js/bootstrap.min.js"></script>
    <link rel="stylesheet" href="../lib/bootstrap-4.1.1-dist/css/bootstrap.css" />
    <link rel="stylesheet" href="../lib/open-iconic-master/font/css/open-iconic-bootstrap.css" />
    <link rel="stylesheet" href="../lib/datatable/dataTables.bootstrap4.min.css" />
    <script type="text/javascript" src="../lib/datatable/jquery.dataTables.min.js"></script>
    <script type="text/javascript" src="../lib/datatable/dataTables.bootstrap4.min.js"></script>
    
    <style>
        .badge-depto {
            background-color: #6f42c1;
            color: white;
        }
        .badge-va-firma {
            background-color: #fd7e14;
            color: white;
        }
        .custom-switch-md .custom-control-label::before {
            height: 1.5rem;
            width: 2.75rem;
            border-radius: 1rem;
        }
        .custom-switch-md .custom-control-label::after {
            width: calc(1.5rem - 4px);
            height: calc(1.5rem - 4px);
            border-radius: calc(1rem - 2px);
        }
        .custom-switch-md .custom-control-input:checked ~ .custom-control-label::after {
            transform: translateX(1.25rem);
        }
        .custom-switch-md .custom-control-label {
            padding-left: 2rem;
            padding-top: 2px;
        }
        .card-shadow {
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .blink-alert {
            animation: blinker 1.5s linear infinite;
        }
        @keyframes blinker {
            50% { opacity: 0.4; }
        }
    </style>
</head>
<body class="bg-light">
    <?php include_once '../gui/navbar.php'; ?>
    
    <div class="container-fluid my-4">
        <!-- Encabezado de la Página -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card card-shadow border-0">
                    <div class="card-body p-4 text-center text-md-left">
                        <div class="d-md-flex justify-content-between align-items-center">
                            <div>
                                <h1 class="h2 font-weight-bold text-dark mb-1">
                                    <span class="oi oi-pulse text-primary mr-2"></span>
                                    Panel de Monitoreo del Circuito
                                </h1>
                                <p class="text-muted mb-0">Control en tiempo real de los programas analíticos y configuraciones del circuito secuencial (Año <?php echo $anioActual; ?>).</p>
                            </div>
                            <!-- Controles de Vacancia y Año -->
                            <div class="mt-3 mt-md-0 d-flex align-items-center flex-wrap" style="gap: 15px;">
                                <!-- Selector de Año de Monitoreo -->
                                <div class="bg-white p-3 border rounded card-shadow d-flex align-items-center">
                                    <form method="GET" action="" class="form-inline mb-0">
                                        <label for="selectAnio" class="mr-2 font-weight-bold text-secondary mb-0">Año:</label>
                                        <?php if (isset($_GET['mostrar_ocultas'])): ?>
                                            <input type="hidden" name="mostrar_ocultas" value="<?php echo htmlspecialchars($_GET['mostrar_ocultas']); ?>">
                                        <?php endif; ?>
                                        <select name="anio" id="selectAnio" class="form-control form-control-sm font-weight-bold" onchange="this.form.submit()">
                                            <?php
                                            $anioMax = date("Y") + 1;
                                            for ($y = 2019; $y <= $anioMax; $y++) {
                                                $selected = ($y == $anioActual) ? 'selected' : '';
                                                echo "<option value=\"{$y}\" {$selected}>{$y}</option>";
                                            }
                                            ?>
                                        </select>
                                    </form>
                                </div>

                                <!-- Switch de Vacancia -->
                                <div class="bg-white p-3 border rounded card-shadow">
                                    <form action="../controlSistema/programa.vacancia.php" method="POST" id="formVacancia" class="mb-0">
                                        <input type="hidden" name="anio" value="<?php echo $anioActual; ?>">
                                        <div class="custom-control custom-switch custom-switch-md">
                                            <input type="checkbox" class="custom-control-input" id="switchVacancia" name="vacancia" value="1" <?php echo $vacanciaActiva ? 'checked' : ''; ?> onchange="document.getElementById('formVacancia').submit()">
                                            <label class="custom-control-label font-weight-bold cursor-pointer" for="switchVacancia" style="user-select: none;">
                                                Vacancia de Escuela: 
                                                <?php if ($vacanciaActiva): ?>
                                                    <span class="badge badge-success ml-1 blink-alert">ACTIVA (Omite Escuela)</span>
                                                <?php else: ?>
                                                    <span class="badge badge-secondary ml-1">INACTIVA</span>
                                                <?php endif; ?>
                                            </label>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Mensajes del Sistema -->
        <?php if (isset($_GET['mensaje'])): ?>
            <div class="alert alert-success alert-dismissible fade show card-shadow" role="alert">
                <span class="oi oi-circle-check mr-2"></span>
                <?php
                switch ($_GET['mensaje']) {
                    case 'vacancia_actualizada':
                        echo "<strong>¡Configuración Actualizada!</strong> Se modificó el estado de Vacancia de Escuela y se redirigieron los programas pendientes de Escuela a la bandeja de Vinculación Académica.";
                        break;
                    case 'reentrega_habilitada':
                        echo "<strong>¡Nueva Entrega Habilitada!</strong> Se ha reseteado el programa. El Profesor correspondiente ha sido notificado y ahora puede volver a subir su programa para el año actual.";
                        break;
                    case 'ocultar_actualizado':
                        echo "<strong>¡Acción realizada!</strong> Se ha actualizado correctamente la preferencia de visualización de la asignatura.";
                        break;
                    case 'aviso_enviado':
                        echo "<strong>¡Notificación Enviada!</strong> Se envió un correo electrónico de aviso de retraso al Revisor actual y al Profesor de la asignatura.";
                        break;
                    default:
                        echo "Operación realizada con éxito.";
                }
                ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show card-shadow" role="alert">
                <span class="oi oi-warning mr-2"></span>
                <strong>Error:</strong> <?php echo htmlspecialchars($_GET['error']); ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>

        <!-- Sección de Filtros rápidos y Controles de Vista -->
        <div class="row mb-3">
            <div class="col-md-12 d-flex justify-content-end">
                <a href="?mostrar_ocultas=<?php echo $mostrarOcultas ? '0' : '1'; ?>&anio=<?php echo $anioActual; ?>" class="btn btn-outline-secondary btn-sm card-shadow">
                    <span class="oi <?php echo $mostrarOcultas ? 'oi-eye' : 'oi-eye'; ?> mr-1"></span>
                    <?php echo $mostrarOcultas ? 'Ocultar asignaturas descartadas' : 'Ver asignaturas descartadas (ocultadas)'; ?>
                </a>
            </div>
        </div>

        <!-- Tabla Principal de Monitoreo -->
        <div class="row">
            <div class="col-md-12">
                <div class="card card-shadow border-0">
                    <div class="card-body p-0">
                        <div class="table-responsive p-3">
                            <table class="table table-hover table-striped align-middle" id="tablaMonitoreo">
                                <thead class="thead-dark">
                                    <tr>
                                        <th>Código</th>
                                        <th>Asignatura</th>
                                        <th>Docente Responsable</th>
                                        <th>Carrera / Plan</th>
                                        <th>Circuito</th>
                                        <th>Estado Actual</th>
                                        <th>Último Movimiento</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if ($resAsignaturas && $resAsignaturas->num_rows > 0) {
                                        while ($fila = $resAsignaturas->fetch_assoc()) {
                                            $idAsignatura = $fila['idAsignatura'];
                                            
                                            // Validar filtro de asignaturas ocultadas
                                            $estaOculta = in_array($idAsignatura, $ocultas);
                                            $tienePrograma = !is_null($fila['idProgramaPDF']);
                                            
                                            // Si la asignatura está marcada como oculta y NO estamos forzando ver las ocultas, y NO tiene programa cargado, la salteamos
                                            if ($estaOculta && !$mostrarOcultas && !$tienePrograma) {
                                                continue;
                                            }
                                            
                                            $docenteNom = $fila['apellidoProf'] . ', ' . $fila['nombreProf'];
                                            if (is_null($fila['idProfesor'])) {
                                                $docenteNom = '<span class="text-muted italic">No asignado</span>';
                                            }
                                            
                                            // Determinar circuito visualmente
                                            $circuitoVisual = "";
                                            if ($fila['es_institucional'] == 1) {
                                                $circuitoVisual = '<span class="badge badge-info font-weight-normal">Institucional</span>';
                                            } elseif ($vacanciaActiva) {
                                                $circuitoVisual = '<span class="badge badge-warning text-dark font-weight-normal">Vacancia Escuela</span>';
                                            } else {
                                                $circuitoVisual = '<span class="badge badge-secondary font-weight-normal">Estándar</span>';
                                            }
                                            
                                            // Estado actual y badges
                                            $estadoVisual = "";
                                            $ultimoMov = "-";
                                            $diasRetraso = 0;
                                            $retrasoAlerta = false;
                                            
                                            if ($tienePrograma) {
                                                // Instanciar temporalmente el modelo para usar su maquina de estados
                                                $progModel = new ProgramaPDFDetalle($fila['idProgramaPDF']);
                                                $estadoActual = $progModel->obtenerEstadoActual();
                                                
                                                switch ($estadoActual) {
                                                    case 'Aprobado':
                                                        $estadoVisual = '<span class="badge badge-success px-2 py-2"><span class="oi oi-check"></span> Aprobado</span>';
                                                        break;
                                                    case 'Devuelto al Profesor':
                                                        $estadoVisual = '<span class="badge badge-warning px-2 py-2 text-dark"><span class="oi oi-action-undo"></span> Devuelto al Profesor</span>';
                                                        break;
                                                    case 'Pendiente de revisión de Escuela':
                                                        $estadoVisual = '<span class="badge badge-primary px-2 py-2">Pendiente Escuela</span>';
                                                        break;
                                                    case 'Pendiente de revisión VA':
                                                        $estadoVisual = '<span class="badge badge-info px-2 py-2">Pendiente VA (Acred.)</span>';
                                                        break;
                                                    case 'Pendiente de revisión de Departamento':
                                                        $estadoVisual = '<span class="badge badge-depto px-2 py-2">Pendiente Depto</span>';
                                                        break;
                                                    case 'Pendiente de firma final VA':
                                                        $estadoVisual = '<span class="badge badge-va-firma px-2 py-2">Pendiente VA (Firma)</span>';
                                                        break;
                                                    default:
                                                        $estadoVisual = '<span class="badge badge-secondary px-2 py-2">' . $estadoActual . '</span>';
                                                }
                                                
                                                if (!is_null($fila['fecha_ultimo_movimiento_circuito'])) {
                                                    $fechaMov = new DateTime($fila['fecha_ultimo_movimiento_circuito']);
                                                    $ultimoMov = $fechaMov->format('d/m/Y H:i');
                                                    
                                                    // Calcular retraso si está en revisión activa (en_revision = 1)
                                                    if ($fila['en_revision'] == 1) {
                                                        $hoy = new DateTime();
                                                        $intervalo = $hoy->diff($fechaMov);
                                                        $diasRetraso = $intervalo->days;
                                                        if ($diasRetraso >= 15) {
                                                            $retrasoAlerta = true;
                                                        }
                                                    }
                                                }
                                            } else {
                                                $estadoVisual = '<span class="badge badge-danger px-2 py-2">Falta Programa</span>';
                                            }
                                            
                                            ?>
                                            <tr <?php echo $estaOculta ? 'class="table-warning" style="opacity: 0.75;"' : ''; ?>>
                                                <td class="font-weight-bold"><?php echo htmlspecialchars($idAsignatura); ?></td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($fila['nombreAsignatura']); ?></strong>
                                                    <?php if ($estaOculta): ?>
                                                        <span class="badge badge-dark ml-1">Ocultado</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php echo $docenteNom; ?>
                                                    <?php if (!is_null($fila['idProfesor'])): ?>
                                                        <br><small class="text-muted"><?php echo htmlspecialchars($fila['emailProf']); ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <small class="font-weight-bold"><?php echo htmlspecialchars($fila['nombreCarrera']); ?></small>
                                                    <br><small class="text-muted">Plan: <?php echo htmlspecialchars($fila['codPlan']); ?></small>
                                                </td>
                                                <td><?php echo $circuitoVisual; ?></td>
                                                <td>
                                                    <?php echo $estadoVisual; ?>
                                                    <?php if ($retrasoAlerta): ?>
                                                        <br>
                                                        <span class="badge badge-danger blink-alert mt-1 text-white px-2 py-1 font-weight-normal" title="Más de 15 días sin cambios">
                                                            <span class="oi oi-timer"></span> Retrasado (<?php echo $diasRetraso; ?> días)
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-muted"><?php echo $ultimoMov; ?></td>
                                                <td>
                                                    <div class="d-flex justify-content-center align-items-center" style="gap: 5px;">
                                                        
                                                        <!-- Acción: Habilitar nueva entrega -->
                                                        <?php 
                                                        $mostrarHabilitar = $tienePrograma && ($estadoActual == 'Aprobado' || $estadoActual == 'Devuelto al Profesor');
                                                        ?>
                                                        <form action="../controlSistema/programa.habilitar.reentrega.php" method="POST" onsubmit="return confirm('¿Estás seguro de habilitar una nueva entrega para esta asignatura? Esto reiniciará el circuito para el año actual.');" style="margin:0;">
                                                            <input type="hidden" name="idProgramaPDF" value="<?php echo $fila['idProgramaPDF']; ?>">
                                                            <button type="submit" class="btn btn-warning btn-sm" title="Habilitar nueva entrega" <?php echo !$mostrarHabilitar ? 'disabled style="opacity: 0.4;"' : ''; ?>>
                                                                <span class="oi oi-reload"></span> Habilitar Reentrega
                                                            </button>
                                                        </form>
                                                        
                                                        <!-- Acción: Aviso Manual de Retraso -->
                                                        <?php if ($tienePrograma && $fila['en_revision'] == 1): ?>
                                                            <form action="../controlSistema/programa.aviso.manual.php" method="POST" onsubmit="return confirm('¿Deseas enviar una notificación de retraso al Revisor y al Profesor?');" style="margin:0;">
                                                                <input type="hidden" name="idProgramaPDF" value="<?php echo $fila['idProgramaPDF']; ?>">
                                                                <button type="submit" class="btn btn-outline-danger btn-sm" title="Enviar aviso manual de retraso">
                                                                    <span class="oi oi-bell text-danger"></span> Avisar
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
                                                        
                                                        <!-- Acción: Ocultar / Mostrar asignatura sin programa -->
                                                        <?php if (!$tienePrograma): ?>
                                                            <form action="../controlSistema/programa.ocultar.asignatura.php" method="POST" style="margin:0;">
                                                                <input type="hidden" name="idAsignatura" value="<?php echo $idAsignatura; ?>">
                                                                <input type="hidden" name="ocultar" value="<?php echo $estaOculta ? '0' : '1'; ?>">
                                                                <button type="submit" class="btn <?php echo $estaOculta ? 'btn-outline-dark' : 'btn-outline-secondary'; ?> btn-sm" title="<?php echo $estaOculta ? 'Mostrar en listado principal' : 'Descartar/Ocultar asignatura vacía'; ?>">
                                                                    <span class="oi <?php echo $estaOculta ? 'oi-eye' : 'oi-circle-x'; ?>"></span> 
                                                                    <?php echo $estaOculta ? 'Mostrar' : 'Ocultar'; ?>
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php
                                        }
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        $(document).ready(function() {
            $('#tablaMonitoreo').DataTable({
                "language": {
                    "url": "../lib/datatable/es-ar.json"
                },
                "order": [[ 5, "desc" ]], // Ordenar inicialmente por estado / acciones
                "pageLength": 25,
                "columnDefs": [
                    { "orderable": false, "targets": 7 } // Deshabilitar ordenamiento en la columna de acciones
                ]
            });
        });
    </script>
    
    <?php include_once '../gui/footer.php'; ?>
</body>
</html>
