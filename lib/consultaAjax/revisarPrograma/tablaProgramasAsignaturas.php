<?php
/* EN ESTE SCRIPT CONSTRUYE EL TAB CON SUS RESPECTIVAS PESTANIAS SEGUN EL 
 * ESTADO DEL PROGRAMA (NR: NO REVISADO, A: APROBADO, D: DESAPROBADO) Y EL ROL 
 * DEL USUARIO (VA: ADMIN Y VA, DCNE: DPTO CIENCIAS NATURALES Y EXACTAS, 
 * DCS: DPTO CIENCIAS SOCIALES)
 * */

// Ajustamos el include_path para que las clases puedan incluirse entre sí correctamente
$path = realpath('../../../modeloSistema');
set_include_path(get_include_path() . PATH_SEPARATOR . $path);

include_once '../../../modeloSistema/BDConexionSistema.Class.php';
include '../../../modeloSistema/Carrera.Class.php';
require_once '../../../modeloSistema/Programa.Class.php';
require_once '../../../modeloSistema/ProgramaPDFDetalle.Class.php';

// RECUPERAMOS codCarrera, CodPlan y el rol del usuario.
if (isset($_POST['codCarrera']) && isset($_POST['rol'])){
    $codCarrera = $_POST['codCarrera'];
    $carrera = new Carrera($codCarrera);
    //$codPlan = $_POST['codPlan'];
    $plan = $carrera->getPlanVigente();
    
    if (is_null($plan)) {
        echo '<div class="alert alert-warning" role="alert">No hay un plan de estudio vigente para esta carrera.</div>';
        exit;
    }

    $codPlan = $plan->getId();
    $rol = $_POST['rol']; 
    
    // Tab a retornar en la pantalla Revisar Programas
    $html = '<ul class="nav nav-tabs nav-pills nav-fill" id="myTab" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="estadoGeneral-tab" data-toggle="tab" href="#estadoGeneral" role="tab" aria-controls="estadoGeneral" aria-selected="true">Estado General</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="cargados-tab" data-toggle="tab" href="#cargados" role="tab" aria-controls="cargados" aria-selected="false">Cargados</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="aprobados-tab" data-toggle="tab" href="#aprobados" role="tab" aria-controls="aprobados" aria-selected="false">Aprobados</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="contact-tab" data-toggle="tab" href="#contact" role="tab" aria-controls="contact" aria-selected="false">Desaprobados</a>
            </li>
        </ul>
        <div class="tab-content" id="myTabContent">';
                            
    // Obtener todas las asignaturas del plan
    $queryAsignaturas = "SELECT a.id, a.nombre 
                          FROM asignatura a
                          JOIN plan_asignatura pa ON a.id = pa.idAsignatura
                          WHERE pa.idPlan = '{$codPlan}'
                          ORDER BY a.nombre ASC";
    
    $resultadoAsignaturas = BDConexionSistema::getInstancia()->query($queryAsignaturas);

    // =================================================================================
    // PESTANIA ESTADO GENERAL
    // =================================================================================
    $html .= '<div class="tab-pane fade show active" id="estadoGeneral" role="tabpanel" aria-labelledby="estadoGeneral-tab">';
    $html .= '<br>';
    
    if ($resultadoAsignaturas && $resultadoAsignaturas->num_rows > 0) {
        $html .= '<table class="table table-hover table-sm" id="tablaEstadoGeneral">
                    <thead>
                        <tr class="table-info">
                            <th>Asignatura</th>
                            <th>Estado</th>
                            <th>Ubicación Actual</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>';
        
        while ($asignatura = $resultadoAsignaturas->fetch_assoc()) {
            $data = getLatestProgramData($asignatura['id']);
            
            $nombreAsignatura = $asignatura['nombre'];
            $acciones = "";
            $badgeClass = "badge-secondary";
            $estado = "No Cargado";
            $ubicacion = "-";
            
            if ($data) {
                // Determinar estado y ubicación
                $info = getStatusInfo($data);
                $estado = $info['estado'];
                $badgeClass = $info['badgeClass'];
                $ubicacion = $info['ubicacion'];
                
                // Acciones
                if ($data['aprobadoVa'] == 1 && $data['aprobadoDepto'] == 1 && $data['aprobadoEscuela'] == 1) {
                    if ($data['origen'] == 'pdf') {
                        $acciones = '<a title="Descargar Programa" href="programa.descargarPDF.php?id='.$data['id'].'&tipo=pdf" class="btn btn-outline-primary btn-sm" download><span class="oi oi-data-transfer-download"></span></a>';
                    } else {
                        $acciones = '<a title="Descargar Programa" href="programa.descargarPDF.php?id='.$data['id'].'&tipo=legacy" class="btn btn-outline-primary btn-sm"><span class="oi oi-data-transfer-download"></span></a>';
                    }
                } elseif ($data['enRevision'] == 1) {
                    if ($data['origen'] == 'pdf') {
                        $acciones = '<a title="Revisar Programa" href="revisar.programa.pdf.php?id='.$data['id'].'" class="btn btn-outline-success btn-sm"><span class="oi oi-document"></span></a>';
                    } else {
                        $acciones = '<a title="Revisar Programa" href="revisar.programa.php?id='.$data['id'].'" class="btn btn-outline-success btn-sm"><span class="oi oi-document"></span></a>';
                    }
                }
            }
            
            $html .= '<tr>';
            $html .= '<td>'.$nombreAsignatura.'</td>';
            $html .= '<td><span class="badge '.$badgeClass.'">'.$estado.'</span></td>';
            $html .= '<td>'.$ubicacion.'</td>';
            $html .= '<td>'.$acciones.'</td>';
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';
    } else {
        $html .= '<div class="alert alert-warning alert-dismissible fade show text-center" role="alert">No hay asignaturas en el plan.</div>';
    }
    $html .= '</div>';

    // =================================================================================
    // PESTANIA CARGADOS (Todos los que tienen programa)
    // =================================================================================
    if ($resultadoAsignaturas) $resultadoAsignaturas->data_seek(0);
    
    $html .= '<div class="tab-pane fade" id="cargados" role="tabpanel" aria-labelledby="cargados-tab">';
    $html .= '<br>';
    
    $rows = "";
    $count = 0;
    
    if ($resultadoAsignaturas && $resultadoAsignaturas->num_rows > 0) {
        while ($asignatura = $resultadoAsignaturas->fetch_assoc()) {
            $data = getLatestProgramData($asignatura['id']);
            if ($data) {
                $count++;
                $info = getStatusInfo($data);
                $fechaCarga = new DateTime($data['fechaCarga']);
                
                $rows .= '<tr>';
                $rows .= '<td>'.$asignatura['nombre'].'</td>';
                $rows .= '<td>'.$data['id'].'</td>';
                $rows .= '<td>'.getVigencia($data['anio'], $data['vigencia']).'</td>';
                $rows .= '<td>'.$fechaCarga->format('d/m/y').'</td>';
                $rows .= '<td><span class="badge '.$info['badgeClass'].'">'.$info['estado'].'</span></td>';
                
                // En Cargados también mostramos descarga si está aprobado
                if ($data['aprobadoVa'] == 1 && $data['aprobadoDepto'] == 1 && $data['aprobadoEscuela'] == 1) {
                    if ($data['origen'] == 'pdf') {
                        $rows .= '<td><a title="Descargar Programa" href="programa.descargarPDF.php?id='.$data['id'].'&tipo=pdf" download><button type="button" class="btn btn-outline-primary"><span class="oi oi-data-transfer-download"></span></button></a></td>';
                    } else {
                        $rows .= '<td><a title="Descargar Programa" href="programa.descargarPDF.php?id='.$data['id'].'&tipo=legacy"><button type="button" class="btn btn-outline-primary"><span class="oi oi-data-transfer-download"></span></button></a></td>';
                    }
                } else {
                    $link = ($data['origen'] == 'pdf') ? "revisar.programa.pdf.php?id=".$data['id'] : "revisar.programa.php?id=".$data['id'];
                    $rows .= '<td><a title="Ver Programa" href="'.$link.'"><button type="button" class="btn btn-outline-info"><span class="oi oi-document"></span></button></a></td>';
                }
                $rows .= '</tr>';
            }
        }
    }
    
    if ($count > 0) {
        $html .= '<table class="table table-hover table-sm" id="tablaProgramaCargados">
                    <thead>
                        <tr class="table-info">
                            <th>Programa de</th>
                            <th>C&oacute;digo</th>
                            <th>Vigencia</th>
                            <th>Fecha de Carga</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>'.$rows.'</tbody></table>';
    } else {
        $html .= '<div class="alert alert-warning alert-dismissible fade show text-center" role="alert">No hay programas cargados.</div>';
    }
    $html .= '</div>';

    // =================================================================================
    // PESTANIA APROBADOS
    // =================================================================================
    if ($resultadoAsignaturas) $resultadoAsignaturas->data_seek(0);
    
    $html .= '<div class="tab-pane fade" id="aprobados" role="tabpanel" aria-labelledby="aprobados-tab">';
    $html .= '<br>';
    
    $rows = "";
    $count = 0;
    
    if ($resultadoAsignaturas && $resultadoAsignaturas->num_rows > 0) {
        while ($asignatura = $resultadoAsignaturas->fetch_assoc()) {
            $data = getLatestProgramData($asignatura['id']);
            if ($data) {
                // Criterio de Aprobado: Al menos una aprobación
                if ($data['aprobadoVa'] == 1 || $data['aprobadoDepto'] == 1 || $data['aprobadoEscuela'] == 1) {
                    $count++;
                    $info = getStatusInfo($data);
                    $fechaCarga = new DateTime($data['fechaCarga']);
                    
                    $rows .= '<tr>';
                    $rows .= '<td>'.$asignatura['nombre'].'</td>';
                    $rows .= '<td>'.$data['id'].'</td>';
                    $rows .= '<td>'.getVigencia($data['anio'], $data['vigencia']).'</td>';
                    $rows .= '<td>'.$fechaCarga->format('d/m/y').'</td>';
                    $rows .= '<td><span class="badge '.$info['badgeClass'].'">'.$info['estado'].'</span></td>';
                    
                    if ($data['aprobadoVa'] == 1 && $data['aprobadoDepto'] == 1 && $data['aprobadoEscuela'] == 1) {
                         if ($data['origen'] == 'pdf') {
                            $rows .= '<td><a title="Descargar Programa" href="programa.descargarPDF.php?id='.$data['id'].'&tipo=pdf" download><button type="button" class="btn btn-outline-primary"><span class="oi oi-data-transfer-download"></span></button></a></td>';
                        } else {
                            $rows .= '<td><a title="Descargar Programa" href="programa.descargarPDF.php?id='.$data['id'].'&tipo=legacy"><button type="button" class="btn btn-outline-primary"><span class="oi oi-data-transfer-download"></span></button></a></td>';
                        }
                    } else {
                        $link = ($data['origen'] == 'pdf') ? "revisar.programa.pdf.php?id=".$data['id'] : "revisar.programa.php?id=".$data['id'];
                        $rows .= '<td><a title="Ver Programa" href="'.$link.'"><button type="button" class="btn btn-outline-info"><span class="oi oi-document"></span></button></a></td>';
                    }
                    $rows .= '</tr>';
                }
            }
        }
    }
    
    if ($count > 0) {
        $html .= '<table class="table table-hover table-sm" id="tablaProgramaAprobados">
                    <thead>
                        <tr class="table-info">
                            <th>Programa de</th>
                            <th>C&oacute;digo</th>
                            <th>Vigencia</th>
                            <th>Fecha de Carga</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>'.$rows.'</tbody></table>';
    } else {
        $html .= '<div class="alert alert-warning alert-dismissible fade show text-center" role="alert">No hay programas aprobados.</div>';
    }
    $html .= '</div>';

    // =================================================================================
    // PESTANIA DESAPROBADOS
    // =================================================================================
    if ($resultadoAsignaturas) $resultadoAsignaturas->data_seek(0);
    
    $html .= '<div class="tab-pane fade" id="contact" role="tabpanel" aria-labelledby="contact-tab">';
    $html .= '<br>';
    
    $rows = "";
    $count = 0;
    
    if ($resultadoAsignaturas && $resultadoAsignaturas->num_rows > 0) {
        while ($asignatura = $resultadoAsignaturas->fetch_assoc()) {
            $data = getLatestProgramData($asignatura['id']);
            
            // Mostrar si fue desaprobado globalmente O si el usuario actual lo desaprobó
            $desaprobadoPorUsuario = false;
            if ($data) {
                if (($rol == "VA" || $rol == 8) && $data['aprobadoVa'] === '0') {
                    $desaprobadoPorUsuario = true;
                } elseif (($rol == "DCNE" || $rol == "DCS" || $rol == 10 || $rol == "Director de Departamento") && $data['aprobadoDepto'] === '0') {
                    $desaprobadoPorUsuario = true;
                } elseif (($rol == "Director de Escuela") && $data['aprobadoEscuela'] === '0') {
                    $desaprobadoPorUsuario = true;
                }
            }

            if ($data && ($data['fueDesaprobado'] == 1 || $desaprobadoPorUsuario)) {
                $count++;
                $fechaCarga = new DateTime($data['fechaCarga']);
                
                $rows .= '<tr>';
                $rows .= '<td>'.$asignatura['nombre'].'</td>';
                $rows .= '<td>'.$data['id'].'</td>';
                $rows .= '<td>'.getVigencia($data['anio'], $data['vigencia']).'</td>';
                $rows .= '<td>'.$fechaCarga->format('d/m/y').'</td>';
                
                $link = ($data['origen'] == 'pdf') ? "revisar.programa.pdf.php?id=".$data['id'] : "revisar.programa.php?id=".$data['id'];
                $rows .= '<td><a title="Ver Programa" href="'.$link.'"><button type="button" class="btn btn-outline-info"><span class="oi oi-document"></span></button></a></td>';
                $rows .= '</tr>';
            }
        }
    }
    
    if ($count > 0) {
        $html .= '<table class="table table-hover table-sm" id="tablaProgramaDesaprobados">
                    <thead>
                        <tr class="table-info">
                            <th>Programa de</th>
                            <th>C&oacute;digo</th>
                            <th>Vigencia</th>
                            <th>Fecha de Carga</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>'.$rows.'</tbody></table>';
    } else {
        $html .= '<div class="alert alert-warning alert-dismissible fade show text-center" role="alert">No hay programas desaprobados.</div>';
    }
    $html .= '</div>';
    
    $html .= '</div>'; // Fin tab-content
    echo $html;
}

// =================================================================================
// FUNCIONES AUXILIARES
// =================================================================================

function getVigencia($anio, $vigencia) {
    if ($vigencia == 1) {
        return $anio;
    } elseif ($vigencia == 2) {
        return $anio . ' - ' . ($anio + 1);
    } elseif ($vigencia == 3) {
        return $anio . ' - ' . ($anio + 1) . ' - ' . ($anio + 2);
    }
    return $anio;
}

function getLatestProgramData($idAsignatura) {
    // Buscar programa PDF
    $sqlPDF = "SELECT * FROM programa_pdf_detalle WHERE id_asignatura = '$idAsignatura' ORDER BY anio DESC, id DESC LIMIT 1";
    $resProgramaPDF = BDConexionSistema::getInstancia()->query($sqlPDF);
    
    // Buscar programa Legacy
    $sqlLegacy = "SELECT * FROM programa WHERE idAsignatura = '$idAsignatura' ORDER BY anio DESC, id DESC LIMIT 1";
    $resPrograma = BDConexionSistema::getInstancia()->query($sqlLegacy);
    
    $pdfData = ($resProgramaPDF && $resProgramaPDF->num_rows > 0) ? $resProgramaPDF->fetch_assoc() : null;
    $legacyData = ($resPrograma && $resPrograma->num_rows > 0) ? $resPrograma->fetch_assoc() : null;
    
    $programaData = null;
    $usarPDF = false;
    
    if ($pdfData && $legacyData) {
        if ($pdfData['anio'] > $legacyData['anio']) {
            $programaData = $pdfData;
            $usarPDF = true;
        } elseif ($legacyData['anio'] > $pdfData['anio']) {
            $programaData = $legacyData;
            $usarPDF = false;
        } else {
            // Mismo año, priorizamos PDF
            $programaData = $pdfData;
            $usarPDF = true;
        }
    } elseif ($pdfData) {
        $programaData = $pdfData;
        $usarPDF = true;
    } elseif ($legacyData) {
        $programaData = $legacyData;
        $usarPDF = false;
    }
    
    if ($programaData) {
        // Normalizar estructura
        if ($usarPDF) {
            return [
                'id' => $programaData['id'],
                'anio' => $programaData['anio'],
                'vigencia' => $programaData['vigencia'],
                'fechaCarga' => $programaData['fecha_carga'],
                'aprobadoVa' => $programaData['aprobado_va'],
                'aprobadoDepto' => $programaData['aprobado_depto'],
                'aprobadoEscuela' => $programaData['aprobado_escuela'],
                'fueDesaprobado' => $programaData['fue_desaprobado'],
                'enRevision' => $programaData['en_revision'],
                'ruta_archivo' => $programaData['ruta_archivo'],
                'origen' => 'pdf'
            ];
        } else {
            return [
                'id' => $programaData['id'],
                'anio' => $programaData['anio'],
                'vigencia' => $programaData['vigencia'],
                'fechaCarga' => $programaData['fechaCarga'],
                'aprobadoVa' => $programaData['aprobadoVa'],
                'aprobadoDepto' => $programaData['aprobadoDepto'],
                'aprobadoEscuela' => $programaData['aprobadoEscuela'],
                'fueDesaprobado' => $programaData['fueDesaprobado'],
                'enRevision' => $programaData['enRevision'],
                'ruta_archivo' => null,
                'origen' => 'legacy'
            ];
        }
    }
    return null;
}

function getStatusInfo($data) {
    if ($data['aprobadoVa'] == 1 && $data['aprobadoDepto'] == 1 && $data['aprobadoEscuela'] == 1) {
        return ['estado' => "Aprobado por VA, Depto y Escuela", 'ubicacion' => "Aprobado", 'badgeClass' => "badge-success"];
    } elseif ($data['fueDesaprobado'] == 1) {
        $estado = "Rechazado";
        if (!is_null($data['aprobadoVa']) && $data['aprobadoVa'] == 0) {
            $estado = "Desaprobado por VA";
        } elseif (!is_null($data['aprobadoDepto']) && $data['aprobadoDepto'] == 0) {
            $estado = "Desaprobado por Director de Departamento";
        } elseif (!is_null($data['aprobadoEscuela']) && $data['aprobadoEscuela'] == 0) {
            $estado = "Desaprobado por Director de Escuela";
        }
        return ['estado' => $estado, 'ubicacion' => "Profesor", 'badgeClass' => "badge-danger"];
    } elseif ($data['aprobadoVa'] == 1 && $data['aprobadoDepto'] == 1) {
        return ['estado' => "Aprobado por VA y Depto", 'ubicacion' => "Pendiente Escuela", 'badgeClass' => "badge-warning"];
    } elseif ($data['aprobadoVa'] == 1) {
        return ['estado' => "Aprobado por VA", 'ubicacion' => "Pendiente Depto", 'badgeClass' => "badge-warning"];
    } elseif ($data['aprobadoDepto'] == 1) {
        return ['estado' => "Aprobado por Depto", 'ubicacion' => "Pendiente VA", 'badgeClass' => "badge-warning"];
    } elseif ($data['enRevision'] == 1) {
        return ['estado' => "En Revisión", 'ubicacion' => "Vinculación Académica", 'badgeClass' => "badge-info"];
    } else {
        return ['estado' => "Pendiente", 'ubicacion' => "Profesor", 'badgeClass' => "badge-secondary"];
    }
}
?>