<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once '../../ControlAcceso.Class.php';
include_once '../../../modeloSistema/BDConexionSistema.Class.php';
include_once '../../../modeloSistema/Carrera.Class.php';
include_once '../../../modeloSistema/Asignatura.Class.php';

if (isset($_POST['codCarrera']) && isset($_POST['idProfesor'])) {
    $codCarrera = $_POST['codCarrera'];
    $idProfesor = $_POST['idProfesor'];
    
    $conexion = BDConexionSistema::getInstancia();
    
    // Obtener los últimos 5 años
    $anioActual = intval(date("Y"));
    $anios = [];
    for ($i = 4; $i >= 0; $i--) {
        $anios[] = $anioActual - $i;
    }
    
    // Armar condiciones del filtro
    $where = " 1 = 1 ";
    if ($codCarrera !== 'todas') {
        $codCarreraEscaped = $conexion->real_escape_string($codCarrera);
        $where .= " AND ca.idCarrera = '{$codCarreraEscaped}' ";
    }
    if ($idProfesor !== 'todos') {
        $idProfesorEscaped = intval($idProfesor);
        $where .= " AND ar.idProfesor = {$idProfesorEscaped} ";
    }
    
    // Consulta para obtener las asignaturas únicas de acuerdo al filtro
    $sqlAsignaturas = "
        SELECT DISTINCT a.id as idAsignatura, a.nombre as nombreAsignatura, c.nombre as nombreCarrera, ca.idCarrera
        FROM asignatura a
        JOIN carrera_asignatura ca ON a.id = ca.idAsignatura
        JOIN carrera c ON ca.idCarrera = c.id
        LEFT JOIN asignatura_responsable ar ON a.id = ar.idAsignatura
        WHERE {$where}
        ORDER BY c.nombre ASC, a.nombre ASC
    ";
    
    $resAsignaturas = $conexion->query($sqlAsignaturas);
    
    if (!$resAsignaturas) {
        echo '<div class="alert alert-danger">Error al ejecutar la consulta en la base de datos: ' . htmlspecialchars($conexion->error) . '</div>';
        exit;
    }
    
    // Construir tabla HTML
    $html = '<div class="table-responsive">';
    $html .= '<table class="table table-premium table-striped" id="tablaReporte5AniosTable">';
    $html .= '<thead>';
    $html .= '<tr>';
    $html .= '<th>Carrera</th>';
    $html .= '<th>Asignatura</th>';
    $html .= '<th>Docente(s) Responsable(s)</th>';
    foreach ($anios as $a) {
        $html .= '<th class="text-center">' . $a . '</th>';
    }
    $html .= '</tr>';
    $html .= '</thead>';
    $html .= '<tbody>';
    
    if ($resAsignaturas->num_rows > 0) {
        while ($row = $resAsignaturas->fetch_assoc()) {
            $idAsig = $row['idAsignatura'];
            $nombreAsig = $row['nombreAsignatura'];
            $nombreCarrera = $row['nombreCarrera'];
            $idCarrera = $row['idCarrera'];
            
            // Obtener docentes de la asignatura
            $sqlDocs = "SELECT DISTINCT CONCAT(p.apellido, ', ', p.nombre) as nombreCompleto 
                        FROM profesor p 
                        JOIN asignatura_responsable ar ON p.id = ar.idProfesor 
                        WHERE ar.idAsignatura = '{$idAsig}'";
            $resDocs = $conexion->query($sqlDocs);
            $docentesArr = [];
            if ($resDocs && $resDocs->num_rows > 0) {
                while ($d = $resDocs->fetch_assoc()) {
                    $docentesArr[] = $d['nombreCompleto'];
                }
            }
            $docentesText = !empty($docentesArr) ? implode('; ', $docentesArr) : '<span class="text-muted italic">No asignado</span>';
            
            $html .= '<tr>';
            $html .= '<td><span class="small font-weight-bold">[' . $idCarrera . ']</span> ' . htmlspecialchars($nombreCarrera) . '</td>';
            $html .= '<td><span class="small font-weight-bold">[' . $idAsig . ']</span> ' . htmlspecialchars($nombreAsig) . '</td>';
            $html .= '<td>' . htmlspecialchars($docentesText) . '</td>';
            
            // Evaluar estado para cada uno de los últimos 5 años
            foreach ($anios as $a) {
                $estadoBadge = '<span class="badge badge-danger text-white" style="font-size:0.8em; padding: 5px 8px;">No presentado</span>';
                
                // 1. Buscar en programa_pdf_detalle
                $sqlPdf = "SELECT aprobado_va_firma, aprobado_va, aprobado_depto, aprobado_escuela, en_revision, fue_desaprobado 
                           FROM programa_pdf_detalle 
                           WHERE id_asignatura = '{$idAsig}' AND anio = {$a} 
                           ORDER BY id DESC LIMIT 1";
                $resPdf = $conexion->query($sqlPdf);
                
                if ($resPdf && $resPdf->num_rows > 0) {
                    $pdfData = $resPdf->fetch_assoc();
                    if ($pdfData['fue_desaprobado'] == 1) {
                        $estadoBadge = '<span class="badge badge-warning text-white" style="font-size:0.8em; padding: 5px 8px;">Devuelto</span>';
                    } elseif ($pdfData['aprobado_va_firma'] == 1) {
                        $estadoBadge = '<span class="badge badge-success text-white" style="font-size:0.8em; padding: 5px 8px;">Aprobado</span>';
                    } elseif ($pdfData['en_revision'] == 1) {
                        $estadoBadge = '<span class="badge badge-info text-white" style="font-size:0.8em; padding: 5px 8px;">En revisión</span>';
                    } else {
                        $estadoBadge = '<span class="badge badge-secondary text-white" style="font-size:0.8em; padding: 5px 8px;">Borrador</span>';
                    }
                } else {
                    // 2. Buscar en programa (legacy)
                    $sqlLegacy = "SELECT aprobadoVa, aprobadoDepto, aprobadoEscuela, enRevision, fueDesaprobado 
                                  FROM programa 
                                  WHERE idAsignatura = '{$idAsig}' AND anio = {$a} 
                                  ORDER BY id DESC LIMIT 1";
                    $resLegacy = $conexion->query($sqlLegacy);
                    
                    if ($resLegacy && $resLegacy->num_rows > 0) {
                        $legacyData = $resLegacy->fetch_assoc();
                        if ($legacyData['fueDesaprobado'] == 1) {
                            $estadoBadge = '<span class="badge badge-warning text-white" style="font-size:0.8em; padding: 5px 8px;">Devuelto</span>';
                        } elseif ($legacyData['aprobadoVa'] == 1 && $legacyData['aprobadoDepto'] == 1 && $legacyData['aprobadoEscuela'] == 1) {
                            $estadoBadge = '<span class="badge badge-success text-white" style="font-size:0.8em; padding: 5px 8px;">Aprobado</span>';
                        } elseif ($legacyData['enRevision'] == 1) {
                            $estadoBadge = '<span class="badge badge-info text-white" style="font-size:0.8em; padding: 5px 8px;">En revisión</span>';
                        } else {
                            $estadoBadge = '<span class="badge badge-secondary text-white" style="font-size:0.8em; padding: 5px 8px;">Borrador</span>';
                        }
                    }
                }
                
                $html .= '<td class="text-center">' . $estadoBadge . '</td>';
            }
            
            $html .= '</tr>';
        }
    } else {
        $html .= '<tr><td colspan="' . (3 + count($anios)) . '" class="text-center text-muted">No se encontraron asignaturas asociadas para los filtros seleccionados.</td></tr>';
    }
    
    $html .= '</tbody>';
    $html .= '</table>';
    $html .= '</div>';
    
    echo $html;
}
?>
