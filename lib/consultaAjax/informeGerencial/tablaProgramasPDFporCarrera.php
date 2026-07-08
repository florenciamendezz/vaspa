<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include_once '../../ControlAcceso.Class.php';
include_once '../../../modeloSistema/BDConexionSistema.Class.php';
include_once '../../../modeloSistema/Carrera.Class.php';
include_once '../../../modeloSistema/Plan.Class.php';
include_once '../../../modeloSistema/Asignatura.Class.php';
include_once '../../../modeloSistema/Profesor.Class.php';

$print = ''; // valor a devolver
if (isset($_POST['codCarrera']) && isset($_POST['anio'])){
    $codCarrera = $_POST['codCarrera'];
    $anio = $_POST['anio'];
    $carrera = new Carrera($codCarrera);
    
    $conexion = BDConexionSistema::getInstancia();
    
    // Consulta optimizada para traer todas las asignaturas de la carrera y su respectivo estado de circuito
    $sqlAsignaturas = "
        SELECT a.id as idAsignatura, a.nombre as nombreAsignatura, 
               GROUP_CONCAT(DISTINCT CONCAT(p.apellido, ', ', p.nombre) SEPARATOR '; ') as nombresProfesores,
               GROUP_CONCAT(DISTINCT p.email SEPARATOR '; ') as emailsProfesores,
               ppd.id as idProgramaPDF, ppd.ruta_archivo, ppd.en_revision, ppd.aprobado_escuela,
               ppd.aprobado_va, ppd.aprobado_depto, ppd.aprobado_va_firma, ppd.fue_desaprobado,
               ppd.comentario_desaprobacion, ppd.fecha_ultimo_movimiento_circuito, ppd.fecha_carga
        FROM carrera_asignatura ca
        INNER JOIN asignatura a ON ca.idAsignatura = a.id
        LEFT JOIN asignatura_responsable ar ON a.id = ar.idAsignatura
        LEFT JOIN profesor p ON ar.idProfesor = p.id
        LEFT JOIN programa_pdf_detalle ppd ON a.id = ppd.id_asignatura AND ppd.anio = {$anio}
        WHERE ca.idCarrera = '{$codCarrera}'
        GROUP BY a.id, ppd.id
        ORDER BY a.nombre ASC
    ";
        
        $datos = $conexion->query($sqlAsignaturas);
        
        $totalAsignaturas = 0;
        $cantConPdf = 0;
        $cantSinPdf = 0;
        $cantAprobados = 0;
        $cantEnRevision = 0;
        $cantDevueltos = 0;
        $cantRetrasados = 0;
        $cantBorrador = 0;
        
        // Etapas de revisión
        $etapaEscuela = 0;
        $etapaVaAcred = 0;
        $etapaDepto = 0;
        $etapaVaFirma = 0;
        
        // Colecciones para Alertas
        $alertasRetrasados = array();
        $alertasSinPrograma = array();
        $alertasDevueltos = array();
        
        // Filas para la tabla final
        $tablaFilasHtml = '';
        
        if ($datos && $datos->num_rows > 0) {
            while ($fila = $datos->fetch_assoc()) {
                $totalAsignaturas++;
                $idAsignatura = $fila['idAsignatura'];
                $nombreAsignatura = $fila['nombreAsignatura'];
                $docenteNom = $fila['nombresProfesores'];
                if (empty($docenteNom)) {
                    $docenteNom = '<span class="text-muted italic">No asignado</span>';
                }
                
                $tienePdf = !is_null($fila['idProgramaPDF']);
                $estadoVisual = "Sin programa";
                $disponibilidad = "No";
                
                if ($tienePdf) {
                    $cantConPdf++;
                    
                    // Determinar estado actual según la máquina de estados de VASPA
                    if ($fila['fue_desaprobado'] == 1) {
                        $estadoVisual = "Devuelto al Profesor";
                        $cantDevueltos++;
                        $alertasDevueltos[] = array(
                            'codigo' => $idAsignatura,
                            'nombre' => $nombreAsignatura,
                            'profesor' => $docenteNom,
                            'comentario' => $fila['comentario_desaprobacion']
                        );
                    } elseif ($fila['en_revision'] == 0) {
                        if ($fila['aprobado_va_firma'] == 1) {
                            $estadoVisual = "Aprobado";
                            $cantAprobados++;
                            $disponibilidad = "Sí";
                        } else {
                            $estadoVisual = "Borrador";
                            $cantBorrador++;
                        }
                    } else { // en_revision = 1
                        $cantEnRevision++;
                        
                        if (is_null($fila['aprobado_escuela'])) {
                            $estadoVisual = "Pendiente de revisión de Escuela";
                            $etapaEscuela++;
                        } elseif (is_null($fila['aprobado_va'])) {
                            $estadoVisual = "Pendiente de revisión VA";
                            $etapaVaAcred++;
                        } elseif (is_null($fila['aprobado_depto'])) {
                            $estadoVisual = "Pendiente de revisión de Departamento";
                            $etapaDepto++;
                        } elseif (is_null($fila['aprobado_va_firma'])) {
                            $estadoVisual = "Pendiente de firma final VA";
                            $etapaVaFirma++;
                        }
                        
                        // Validar retraso (+15 días sin movimiento)
                        $fechaMov = $fila['fecha_ultimo_movimiento_circuito'] ?? $fila['fecha_carga'] ?? null;
                        if ($fechaMov) {
                            $diff = time() - strtotime($fechaMov);
                            $dias = floor($diff / (60 * 60 * 24));
                            if ($dias >= 15) {
                                $cantRetrasados++;
                                $alertasRetrasados[] = array(
                                    'codigo' => $idAsignatura,
                                    'nombre' => $nombreAsignatura,
                                    'profesor' => $docenteNom,
                                    'dias' => $dias,
                                    'estado' => $estadoVisual
                                );
                            }
                        }
                    }
                } else {
                    // 2. Verificar PDF Legacy + Aprobación en tabla programa (Compatibilidad hacia atrás)
                    $estaAprobadoLegacy = false;
                    $sqlLegacy = "SELECT id FROM programa WHERE idAsignatura = '{$idAsignatura}' AND anio = {$anio} AND aprobadoVa = 1 AND aprobadoDepto = 1";
                    $resLegacy = $conexion->query($sqlLegacy);
                    if ($resLegacy && $resLegacy->num_rows > 0) {
                        $estaAprobadoLegacy = true;
                    }
                    
                    if ($estaAprobadoLegacy) {
                        $cantConPdf++;
                        $cantAprobados++;
                        $estadoVisual = "Aprobado (Legacy)";
                        $disponibilidad = "Sí";
                    } else {
                        $cantSinPdf++;
                        $estadoVisual = "Falta programa";
                        $alertasSinPrograma[] = array(
                            'codigo' => $idAsignatura,
                            'nombre' => $nombreAsignatura,
                            'profesor' => $docenteNom
                        );
                    }
                }
                
                // Formatear badges
                $badgeDisponibilidad = ($disponibilidad == "Sí") 
                    ? '<span class="badge badge-success px-2 py-1"><span class="oi oi-circle-check"></span> Sí</span>' 
                    : '<span class="badge badge-secondary px-2 py-1"><span class="oi oi-circle-x"></span> No</span>';
                
                $badgeEstado = "";
                switch ($estadoVisual) {
                    case 'Aprobado':
                    case 'Aprobado (Legacy)':
                        $badgeEstado = '<span class="badge badge-success">Aprobado</span>';
                        break;
                    case 'Devuelto al Profesor':
                        $badgeEstado = '<span class="badge badge-warning text-dark">Devuelto</span>';
                        break;
                    case 'Borrador':
                        $badgeEstado = '<span class="badge badge-light border">Borrador</span>';
                        break;
                    case 'Falta programa':
                    case 'Sin programa':
                        $badgeEstado = '<span class="badge badge-danger">Falta PDF</span>';
                        break;
                    default:
                        $badgeEstado = '<span class="badge badge-info">' . $estadoVisual . '</span>';
                        break;
                }
                
                // Construir fila de tabla
                $tablaFilasHtml .= '<tr>';
                $tablaFilasHtml .= '<td>' . htmlspecialchars($idAsignatura) . '</td>';
                $tablaFilasHtml .= '<td><strong>' . htmlspecialchars($nombreAsignatura) . '</strong></td>';
                $tablaFilasHtml .= '<td>' . $docenteNom . '</td>';
                $tablaFilasHtml .= '<td class="text-center">' . $badgeDisponibilidad . '</td>';
                $tablaFilasHtml .= '<td>' . $badgeEstado . '</td>';
                $tablaFilasHtml .= '</tr>';
            }
        }
        
        // Calcular porcentaje de avance (programas aprobados sobre total asignaturas)
        $porcentajeAvance = $totalAsignaturas > 0 ? round(($cantAprobados / $totalAsignaturas) * 100) : 0;
        
        // ----------------- MAQUETACIÓN HTML DEL DASHBOARD -----------------
        
        $print .= '<div class="dashboard-container">';
        
        // 1. Tarjetas KPI
        $print .= '
            <div class="kpi-grid">
                <!-- Total Asignaturas -->
                <div class="kpi-card kpi-total">
                    <div class="kpi-icon-wrapper"><i data-lucide="book-open"></i></div>
                    <div class="kpi-details">
                        <span class="kpi-value">' . $totalAsignaturas . '</span>
                        <span class="kpi-title">Asignaturas</span>
                    </div>
                </div>
                <!-- Con PDF Cargado -->
                <div class="kpi-card kpi-cargados">
                    <div class="kpi-icon-wrapper"><i data-lucide="file-text"></i></div>
                    <div class="kpi-details">
                        <span class="kpi-value">' . $cantConPdf . '</span>
                        <span class="kpi-title">Con PDF</span>
                    </div>
                </div>
                <!-- Sin PDF -->
                <div class="kpi-card kpi-sin-pdf">
                    <div class="kpi-icon-wrapper"><i data-lucide="file-warning"></i></div>
                    <div class="kpi-details">
                        <span class="kpi-value">' . $cantSinPdf . '</span>
                        <span class="kpi-title">Sin PDF</span>
                    </div>
                </div>
                <!-- Aprobados -->
                <div class="kpi-card kpi-aprobados">
                    <div class="kpi-icon-wrapper"><i data-lucide="check-circle"></i></div>
                    <div class="kpi-details">
                        <span class="kpi-value">' . $cantAprobados . '</span>
                        <span class="kpi-title">Aprobados</span>
                    </div>
                </div>
                <!-- En Revisión -->
                <div class="kpi-card kpi-revision">
                    <div class="kpi-icon-wrapper"><i data-lucide="refresh-cw"></i></div>
                    <div class="kpi-details">
                        <span class="kpi-value">' . $cantEnRevision . '</span>
                        <span class="kpi-title">En Revisión</span>
                    </div>
                </div>
                <!-- Devueltos -->
                <div class="kpi-card kpi-devueltos">
                    <div class="kpi-icon-wrapper"><i data-lucide="corner-up-left"></i></div>
                    <div class="kpi-details">
                        <span class="kpi-value">' . $cantDevueltos . '</span>
                        <span class="kpi-title">Devueltos</span>
                    </div>
                </div>
                <!-- Retrasados -->
                <div class="kpi-card kpi-retrasados">
                    <div class="kpi-icon-wrapper"><i data-lucide="alert-circle"></i></div>
                    <div class="kpi-details">
                        <span class="kpi-value">' . $cantRetrasados . '</span>
                        <span class="kpi-title">Retrasados</span>
                    </div>
                </div>
            </div>
        ';
        
        // 2. Fila de Gráficos (Visibles directamente)
        $print .= '
            <div class="charts-row">
                <!-- Gráfico de Dona: Distribución de Estados -->
                <div class="chart-col">
                    <div class="chart-title"><i data-lucide="pie-chart"></i> Distribución General por Estado</div>
                    <div id="chartDonaEstados" style="width: 100%; height: 260px;"></div>
                </div>
                <!-- Gráfico de Barras: Etapas del Circuito -->
                <div class="chart-col">
                    <div class="chart-title"><i data-lucide="bar-chart-3"></i> Programas en Revisión por Etapa</div>
                    <div id="chartBarrasEtapas" style="width: 100%; height: 260px;"></div>
                </div>
                <!-- Tarjeta de Avance General -->
                <div class="chart-col">
                    <div class="chart-title"><i data-lucide="percent"></i> Progreso General de la Carrera</div>
                    <div class="progress-container">
                        <div class="progress-stat">
                            <span class="font-weight-bold text-secondary">Aprobación</span>
                            <span class="progress-stat-val">' . $porcentajeAvance . '%</span>
                        </div>
                        <div class="progress-bar-premium">
                            <div class="progress-bar-fill" style="width: ' . $porcentajeAvance . '%"></div>
                        </div>
                        <div class="progress-details">
                            <span>Aprobados: <strong>' . $cantAprobados . '</strong></span>
                            <span>Total Materias: <strong>' . $totalAsignaturas . '</strong></span>
                        </div>
                    </div>
                </div>
            </div>
        ';
        
        // 3. Fila de Alertas Visuales
        $alertasHtml = '';
        
        // Alerta: Programas Retrasados
        if (count($alertasRetrasados) > 0) {
            $alertasHtml .= '
                <div class="alert-card alert-danger-premium">
                    <div class="alert-card-header">
                        <i data-lucide="alert-triangle"></i> Programas Retrasados (+15 días en revisión)
                    </div>
                    <div class="alert-card-body">
                        <ul class="alert-list">';
            foreach ($alertasRetrasados as $a) {
                $alertasHtml .= '
                            <li class="alert-item">
                                <div class="alert-item-header">
                                    <span>' . htmlspecialchars($a['codigo']) . ' - ' . htmlspecialchars($a['nombre']) . '</span>
                                    <span class="badge badge-danger">' . $a['dias'] . ' días</span>
                                </div>
                                <span class="alert-item-desc">Docente: ' . $a['profesor'] . ' | Estado: ' . $a['estado'] . '</span>
                            </li>';
            }
            $alertasHtml .= '
                        </ul>
                    </div>
                </div>
            ';
        }
        
        // Alerta: Programas Devueltos
        if (count($alertasDevueltos) > 0) {
            $alertasHtml .= '
                <div class="alert-card alert-warning-premium">
                    <div class="alert-card-header">
                        <i data-lucide="corner-up-left"></i> Programas Devueltos al Profesor
                    </div>
                    <div class="alert-card-body">
                        <ul class="alert-list">';
            foreach ($alertasDevueltos as $a) {
                $alertasHtml .= '
                            <li class="alert-item">
                                <div class="alert-item-header">
                                    <span>' . htmlspecialchars($a['codigo']) . ' - ' . htmlspecialchars($a['nombre']) . '</span>
                                </div>
                                <span class="alert-item-desc">Docente: ' . $a['profesor'] . '</span>
                                <span class="alert-item-desc mt-1 text-dark"><strong>Motivo:</strong> "' . htmlspecialchars($a['comentario'] ?? '') . '"</span>
                            </li>';
            }
            $alertasHtml .= '
                        </ul>
                    </div>
                </div>
            ';
        }
        
        // Alerta: Asignaturas sin programa
        if (count($alertasSinPrograma) > 0) {
            $alertasHtml .= '
                <div class="alert-card alert-info-premium">
                    <div class="alert-card-header">
                        <i data-lucide="file-warning"></i> Asignaturas sin Programa analítico cargado
                    </div>
                    <div class="alert-card-body">
                        <ul class="alert-list">';
            foreach ($alertasSinPrograma as $a) {
                $alertasHtml .= '
                            <li class="alert-item">
                                <div class="alert-item-header">
                                    <span>' . htmlspecialchars($a['codigo']) . ' - ' . htmlspecialchars($a['nombre']) . '</span>
                                </div>
                                <span class="alert-item-desc">Docente Responsable: ' . $a['profesor'] . '</span>
                            </li>';
            }
            $alertasHtml .= '
                        </ul>
                    </div>
                </div>
            ';
        }
        
        if (!empty($alertasHtml)) {
            $print .= '<h4 class="mb-3 font-weight-bold text-dark"><i data-lucide="bell" style="vertical-align: middle; margin-right: 0.5rem; width:20px; height:20px;"></i> Alertas del Circuito</h4>';
            $print .= '<div class="alerts-row">' . $alertasHtml . '</div>';
        }
        
        // 4. Tabla de detalles inferior
        $print .= '
            <h4 class="mt-4 mb-3 font-weight-bold text-dark"><i data-lucide="list" style="vertical-align: middle; margin-right: 0.5rem; width:20px; height:20px;"></i> Detalle de Asignaturas</h4>
            <div class="table-responsive table-premium">
                <table class="table table-hover table-striped mb-0" id="tablaAsignaturas">
                    <thead>
                        <tr>
                            <th>C&oacute;digo</th>
                            <th>Asignatura</th>
                            <th>Profesor Responsable</th>
                            <th class="text-center">PDF Disponible</th>
                            <th>Estado Resumen</th>
                        </tr>
                    </thead>
                    <tbody>
                        ' . $tablaFilasHtml . '
                    </tbody>
                </table>
            </div>
        ';
        
        $print .= '</div>'; // Fin dashboard-container
    
    // --- LOGGING ---
    include_once '../../../modeloSistema/LogInforme.Class.php';
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    $idUsuarioLog = 0;
    $emailUsuarioLog = 'Desconocido';
    if (isset($_SESSION['usuario'])) {
        $idUsuarioLog = $_SESSION['usuario']->id;
        $emailUsuarioLog = $_SESSION['usuario']->email;
    }
    
    if (isset($codCarrera) && isset($anio)) {
        $tipoInforme = "Reporte Carreras Dashboard (Carrera: {$codCarrera}, Año: {$anio})";
        LogInforme::guardarLog($idUsuarioLog, $emailUsuarioLog, $tipoInforme, $print);
    }
    // --- FIN LOGGING ---
    
    echo $print;
} else {
    echo '<div class="alert alert-warning" role="alert">Faltan datos.</div>';
}
?>

<script type="text/javascript">
if (typeof google !== 'undefined' && google.charts) {
    google.charts.load('current', {'packages':['corechart', 'bar']});
    google.charts.setOnLoadCallback(drawDashboardCharts);
}

function drawDashboardCharts() {
    // 1. Gráfico de Dona: Distribución de Estados
    var dataDona = google.visualization.arrayToDataTable([
        ['Estado', 'Cantidad'],
        ['Borrador', <?php echo $cantBorrador; ?>],
        ['En revisión', <?php echo $cantEnRevision; ?>],
        ['Devuelto al Profesor', <?php echo $cantDevueltos; ?>],
        ['Aprobado', <?php echo $cantAprobados; ?>]
    ]);
    
    var optionsDona = {
        pieHole: 0.45,
        chartArea: { left: '5%', top: '10%', width: '90%', height: '80%' },
        colors: ['#cbd5e1', '#3b82f6', '#f59e0b', '#10b981'], // Slate, Blue, Amber, Emerald
        legend: { 
            position: 'right', 
            textStyle: { fontName: 'Outfit', fontSize: 11, color: '#4b5563' } 
        },
        pieSliceText: 'value',
        pieSliceTextStyle: { fontName: 'Outfit', fontSize: 12, fontWeight: 'bold' },
        tooltip: { textStyle: { fontName: 'Outfit', fontSize: 12 } },
        backgroundColor: 'transparent'
    };
    
    var chartDona = new google.visualization.PieChart(document.getElementById('chartDonaEstados'));
    if (document.getElementById('chartDonaEstados')) {
        chartDona.draw(dataDona, optionsDona);
    }
    
    // 2. Gráfico de Barras: Etapas del Circuito
    var dataBarras = google.visualization.arrayToDataTable([
        ['Etapa', 'Programas', { role: 'style' }],
        ['Escuela', <?php echo $etapaEscuela; ?>, '#3b82f6'],
        ['VA Acred.', <?php echo $etapaVaAcred; ?>, '#06b6d4'],
        ['Depto.', <?php echo $etapaDepto; ?>, '#6366f1'],
        ['VA Firma', <?php echo $etapaVaFirma; ?>, '#fd7e14']
    ]);
    
    var optionsBarras = {
        chartArea: { left: '10%', top: '10%', width: '80%', height: '70%' },
        legend: { position: 'none' },
        hAxis: {
            textStyle: { fontName: 'Outfit', fontSize: 10, color: '#64748b' }
        },
        vAxis: {
            minValue: 0,
            format: '#',
            textStyle: { fontName: 'Outfit', fontSize: 10, color: '#64748b' },
            gridlines: { color: '#f1f5f9' }
        },
        backgroundColor: 'transparent'
    };
    
    var chartBarras = new google.visualization.ColumnChart(document.getElementById('chartBarrasEtapas'));
    if (document.getElementById('chartBarrasEtapas')) {
        chartBarras.draw(dataBarras, optionsBarras);
    }
    
    // Hacer responsivo
    function resizeHandler() {
        if (document.getElementById('chartDonaEstados')) chartDona.draw(dataDona, optionsDona);
        if (document.getElementById('chartBarrasEtapas')) chartBarras.draw(dataBarras, optionsBarras);
    }
    window.removeEventListener('resize', resizeHandler);
    window.addEventListener('resize', resizeHandler, false);
}

// Inicializar iconos de Lucide
if (typeof lucide !== 'undefined') {
    lucide.createIcons();
}
</script>