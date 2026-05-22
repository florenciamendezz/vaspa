<?php
/* 
 * EN ESTE SCRIPT SE CONSTRUYE EL TAB CON SUS RESPECTIVAS PESTANIAS SEGUN EL 
 * ESTADO DEL PROGRAMA Y EL ROL DEL USUARIO.
 */

// Ajustamos el include_path para que las clases puedan incluirse entre sí correctamente
$path = realpath('../../../modeloSistema');
set_include_path(get_include_path() . PATH_SEPARATOR . $path);

include_once '../../../modeloSistema/BDConexionSistema.Class.php';
include '../../../modeloSistema/Carrera.Class.php';
require_once '../../../modeloSistema/Programa.Class.php';
require_once '../../../modeloSistema/ProgramaPDFDetalle.Class.php';

if (isset($_POST['codCarrera']) && isset($_POST['rol'])){
    $codCarrera = $_POST['codCarrera'];
    $carrera = new Carrera($codCarrera);
    $plan = $carrera->getPlanVigente();
    
    if (is_null($plan)) {
        echo '<div class="alert alert-warning" role="alert">No hay un plan de estudio vigente para esta carrera.</div>';
        exit;
    }

    $codPlan = $plan->getId();
    $rol = $_POST['rol']; 
    $isVA = ($rol == 'VA' || $rol == 'Administrador' || $rol == 'Vinculación Académica');

    // Tab a retornar en la pantalla Revisar Programas
    $html = '<ul class="nav nav-tabs nav-pills nav-fill mb-3" id="myTab" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="estadoGeneral-tab" data-toggle="tab" href="#estadoGeneral" role="tab" aria-controls="estadoGeneral" aria-selected="true">Estado General</a>
            </li>';
            
    if ($isVA) {
        $html .= '
            <li class="nav-item">
                <a class="nav-link" id="pendientes1-tab" data-toggle="tab" href="#pendientes1" role="tab" aria-controls="pendientes1" aria-selected="false">Pendientes Acreditación (1° Paso)</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="pendientes2-tab" data-toggle="tab" href="#pendientes2" role="tab" aria-controls="pendientes2" aria-selected="false">Pendientes Firma Final (2° Paso)</a>
            </li>';
    } else {
        $html .= '
            <li class="nav-item">
                <a class="nav-link" id="pendientes-tab" data-toggle="tab" href="#pendientes" role="tab" aria-controls="pendientes" aria-selected="false">Pendientes de Revisión</a>
            </li>';
    }
    
    $html .= '
            <li class="nav-item">
                <a class="nav-link" id="aprobados-tab" data-toggle="tab" href="#aprobados" role="tab" aria-controls="aprobados" aria-selected="false">Aprobados</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="contact-tab" data-toggle="tab" href="#contact" role="tab" aria-controls="contact" aria-selected="false">Desaprobados</a>
            </li>
        </ul>
        <div class="tab-content" id="myTabContent">';
                            
    // Obtener todas las asignaturas del plan incluyendo el idDepartamento
    $queryAsignaturas = "SELECT a.id, a.nombre, a.idDepartamento 
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
                
                // Determinar si está completamente aprobado
                $estaAprobadoTotal = ($data['origen'] == 'pdf') ? ($data['aprobadoVaFirma'] == 1) : ($data['aprobadoVa'] == 1 && $data['aprobadoDepto'] == 1 && $data['aprobadoEscuela'] == 1);
                
                // Acciones
                if ($estaAprobadoTotal) {
                    if ($data['origen'] == 'pdf') {
                        $acciones = '<a title="Descargar Programa" href="programa.descargarPDF.php?id='.$data['id'].'&tipo=pdf" class="btn btn-outline-primary btn-sm" download><span class="oi oi-data-transfer-download"></span> Descargar</a>';
                    } else {
                        $acciones = '<a title="Descargar Programa" href="programa.descargarPDF.php?id='.$data['id'].'&tipo=legacy" class="btn btn-outline-primary btn-sm"><span class="oi oi-data-transfer-download"></span> Descargar</a>';
                    }
                } else {
                    $link = ($data['origen'] == 'pdf') ? "revisar.programa.pdf.php?id=".$data['id'] : "revisar.programa.php?id=".$data['id'];
                    $acciones = '<a title="Ver detalles" href="'.$link.'" class="btn btn-outline-info btn-sm"><span class="oi oi-eye"></span> Ver Ficha</a>';
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
    // PESTANIAS PENDIENTES (Bandejas de Entrada)
    // =================================================================================
    if ($resultadoAsignaturas) $resultadoAsignaturas->data_seek(0);

    if ($isVA) {
        // --- 1. VA Acreditación ---
        $html .= '<div class="tab-pane fade" id="pendientes1" role="tabpanel" aria-labelledby="pendientes1-tab">';
        $html .= '<br>';
        $rowsP1 = "";
        $countP1 = 0;
        
        if ($resultadoAsignaturas && $resultadoAsignaturas->num_rows > 0) {
            while ($asignatura = $resultadoAsignaturas->fetch_assoc()) {
                $data = getLatestProgramData($asignatura['id']);
                if ($data && $data['enRevision'] == 1 && $data['aprobadoEscuela'] == 1 && $data['aprobadoVa'] === null && $data['fueDesaprobado'] == 0) {
                    $countP1++;
                    $fechaCarga = new DateTime($data['fechaCarga']);
                    $link = ($data['origen'] == 'pdf') ? "revisar.programa.pdf.php?id=".$data['id'] : "revisar.programa.php?id=".$data['id'];
                    
                    $rowsP1 .= '<tr>';
                    $rowsP1 .= '<td>'.$asignatura['nombre'].'</td>';
                    $rowsP1 .= '<td>'.$data['anio'].'</td>';
                    $rowsP1 .= '<td>'.$fechaCarga->format('d/m/y').'</td>';
                    $rowsP1 .= '<td><a href="'.$link.'" class="btn btn-success btn-sm"><span class="oi oi-document"></span> Revisar Acreditación</a></td>';
                    $rowsP1 .= '</tr>';
                }
            }
        }
        
        if ($countP1 > 0) {
            $html .= '<table class="table table-hover table-sm" id="tablaPendientes1">
                        <thead>
                            <tr class="table-info">
                                <th>Asignatura</th>
                                <th>Año</th>
                                <th>Fecha de Carga</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>'.$rowsP1.'</tbody></table>';
        } else {
            $html .= '<div class="alert alert-success text-center" role="alert">No tenés programas pendientes de acreditación.</div>';
        }
        $html .= '</div>';

        // --- 2. VA Firma Final ---
        if ($resultadoAsignaturas) $resultadoAsignaturas->data_seek(0);
        $html .= '<div class="tab-pane fade" id="pendientes2" role="tabpanel" aria-labelledby="pendientes2-tab">';
        $html .= '<br>';
        $rowsP2 = "";
        $countP2 = 0;
        
        if ($resultadoAsignaturas && $resultadoAsignaturas->num_rows > 0) {
            while ($asignatura = $resultadoAsignaturas->fetch_assoc()) {
                $data = getLatestProgramData($asignatura['id']);
                if ($data && $data['origen'] == 'pdf' && $data['enRevision'] == 1 && $data['aprobadoDepto'] == 1 && $data['aprobadoVaFirma'] === null && $data['fueDesaprobado'] == 0) {
                    $countP2++;
                    $fechaCarga = new DateTime($data['fechaCarga']);
                    $link = "revisar.programa.pdf.php?id=".$data['id'];
                    
                    $rowsP2 .= '<tr>';
                    $rowsP2 .= '<td>'.$asignatura['nombre'].'</td>';
                    $rowsP2 .= '<td>'.$data['anio'].'</td>';
                    $rowsP2 .= '<td>'.$fechaCarga->format('d/m/y').'</td>';
                    $rowsP2 .= '<td><a href="'.$link.'" class="btn btn-info btn-sm"><span class="oi oi-pencil"></span> Confirmar Firma Final</a></td>';
                    $rowsP2 .= '</tr>';
                }
            }
        }
        
        if ($countP2 > 0) {
            $html .= '<table class="table table-hover table-sm" id="tablaPendientes2">
                        <thead>
                            <tr class="table-info">
                                <th>Asignatura</th>
                                <th>Año</th>
                                <th>Fecha de Carga</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>'.$rowsP2.'</tbody></table>';
        } else {
            $html .= '<div class="alert alert-success text-center" role="alert">No tenés programas pendientes de firma final.</div>';
        }
        $html .= '</div>';

    } else {
        // --- 3. Pendientes Escuela o Departamento ---
        $html .= '<div class="tab-pane fade" id="pendientes" role="tabpanel" aria-labelledby="pendientes-tab">';
        $html .= '<br>';
        $rowsP = "";
        $countP = 0;
        
        if ($resultadoAsignaturas && $resultadoAsignaturas->num_rows > 0) {
            while ($asignatura = $resultadoAsignaturas->fetch_assoc()) {
                $data = getLatestProgramData($asignatura['id']);
                if (!$data) continue;
                
                $esPendiente = false;
                
                if ($rol == 'Director de Escuela') {
                    // Escuela: en_revision = 1 y aprobado_escuela IS NULL
                    if ($data['enRevision'] == 1 && $data['aprobadoEscuela'] === null && $data['fueDesaprobado'] == 0) {
                        $esPendiente = true;
                    }
                } elseif ($rol == 'DCNE' || $rol == 'DCS' || $rol == 'Director de Departamento' || $rol == 10) {
                    // Departamento: en_revision = 1 y aprobado_va = 1 y aprobado_depto IS NULL
                    if ($data['enRevision'] == 1 && $data['aprobadoVa'] == 1 && $data['aprobadoDepto'] === null && $data['fueDesaprobado'] == 0) {
                        // Filtrar por ID de Departamento del docente si corresponde
                        if ($rol == 'DCNE' && $asignatura['idDepartamento'] != '2') {
                            $esPendiente = false;
                        } elseif ($rol == 'DCS' && $asignatura['idDepartamento'] != '1') {
                            $esPendiente = false;
                        } else {
                            $esPendiente = true;
                        }
                    }
                }
                
                if ($esPendiente) {
                    $countP++;
                    $fechaCarga = new DateTime($data['fechaCarga']);
                    $link = ($data['origen'] == 'pdf') ? "revisar.programa.pdf.php?id=".$data['id'] : "revisar.programa.php?id=".$data['id'];
                    
                    $rowsP .= '<tr>';
                    $rowsP .= '<td>'.$asignatura['nombre'].'</td>';
                    $rowsP .= '<td>'.$data['anio'].'</td>';
                    $rowsP .= '<td>'.$fechaCarga->format('d/m/y').'</td>';
                    $rowsP .= '<td><a href="'.$link.'" class="btn btn-success btn-sm"><span class="oi oi-document"></span> Revisar Programa</a></td>';
                    $rowsP .= '</tr>';
                }
            }
        }
        
        if ($countP > 0) {
            $html .= '<table class="table table-hover table-sm" id="tablaPendientes">
                        <thead>
                            <tr class="table-info">
                                <th>Asignatura</th>
                                <th>Año</th>
                                <th>Fecha de Carga</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>'.$rowsP.'</tbody></table>';
        } else {
            $html .= '<div class="alert alert-success text-center" role="alert">No tenés programas pendientes de revisión en tu bandeja.</div>';
        }
        $html .= '</div>';
    }

    // =================================================================================
    // PESTANIA APROBADOS (Programas que avanzaron o terminaron)
    // =================================================================================
    if ($resultadoAsignaturas) $resultadoAsignaturas->data_seek(0);
    
    $html .= '<div class="tab-pane fade" id="aprobados" role="tabpanel" aria-labelledby="aprobados-tab">';
    $html .= '<br>';
    
    $rowsA = "";
    $countA = 0;
    
    if ($resultadoAsignaturas && $resultadoAsignaturas->num_rows > 0) {
        while ($asignatura = $resultadoAsignaturas->fetch_assoc()) {
            $data = getLatestProgramData($asignatura['id']);
            if ($data) {
                // Se considera aprobado si cuenta con firma de Escuela, VA, Depto o Firma Final
                if ($data['aprobadoVa'] == 1 || $data['aprobadoDepto'] == 1 || $data['aprobadoEscuela'] == 1 || $data['aprobadoVaFirma'] == 1) {
                    $countA++;
                    $info = getStatusInfo($data);
                    $fechaCarga = new DateTime($data['fechaCarga']);
                    
                    $estaAprobadoTotal = ($data['origen'] == 'pdf') ? ($data['aprobadoVaFirma'] == 1) : ($data['aprobadoVa'] == 1 && $data['aprobadoDepto'] == 1 && $data['aprobadoEscuela'] == 1);
                    
                    $rowsA .= '<tr>';
                    $rowsA .= '<td>'.$asignatura['nombre'].'</td>';
                    $rowsA .= '<td>'.$data['anio'].'</td>';
                    $rowsA .= '<td>'.$fechaCarga->format('d/m/y').'</td>';
                    $rowsA .= '<td><span class="badge '.$info['badgeClass'].'">'.$info['estado'].'</span></td>';

                    $acciones = '';
                    if ($estaAprobadoTotal) {
                         if ($data['origen'] == 'pdf') {
                            $acciones .= '<a title="Descargar Programa" href="programa.descargarPDF.php?id='.$data['id'].'&tipo=pdf" class="btn btn-outline-primary btn-sm" download><span class="oi oi-data-transfer-download"></span> Descargar</a>';
                        } else {
                            $acciones .= '<a title="Descargar Programa" href="programa.descargarPDF.php?id='.$data['id'].'&tipo=legacy" class="btn btn-outline-primary btn-sm"><span class="oi oi-data-transfer-download"></span> Descargar</a>';
                        }
                    } else {
                        $link = ($data['origen'] == 'pdf') ? "revisar.programa.pdf.php?id=".$data['id'] : "revisar.programa.php?id=".$data['id'];
                        $acciones .= '<a title="Ver Ficha" href="'.$link.'" class="btn btn-outline-info btn-sm"><span class="oi oi-eye"></span> Ver Ficha</a>';
                    }

                    // Quien subió el último archivo firmado
                    if ($data['ruta_archivo']) {
                        if (strpos($data['ruta_archivo'], '_firmado-VA_') !== false) {
                            $acciones .= ' <span class="badge badge-info ml-1">Firma VA</span>';
                        } elseif (strpos($data['ruta_archivo'], '_firmado-Depto_') !== false) {
                            $acciones .= ' <span class="badge badge-warning ml-1">Firma Depto</span>';
                        } elseif (strpos($data['ruta_archivo'], '_firmado-Escuela_') !== false) {
                            $acciones .= ' <span class="badge badge-success ml-1">Firma Escuela</span>';
                        }
                    }

                    $rowsA .= '<td>'.$acciones.'</td>';
                    $rowsA .= '</tr>';
                }
            }
        }
    }
    
    if ($countA > 0) {
        $html .= '<table class="table table-hover table-sm" id="tablaProgramaAprobados">
                    <thead>
                        <tr class="table-info">
                            <th>Asignatura</th>
                            <th>Año</th>
                            <th>Fecha de Carga</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>'.$rowsA.'</tbody></table>';
    } else {
        $html .= '<div class="alert alert-warning alert-dismissible fade show text-center" role="alert">No hay programas aprobados.</div>';
    }
    $html .= '</div>';

    // =================================================================================
    // PESTANIA DESAPROBADOS (Devueltos al Profesor)
    // =================================================================================
    if ($resultadoAsignaturas) $resultadoAsignaturas->data_seek(0);
    
    $html .= '<div class="tab-pane fade" id="contact" role="tabpanel" aria-labelledby="contact-tab">';
    $html .= '<br>';
    
    $rowsD = "";
    $countD = 0;
    
    if ($resultadoAsignaturas && $resultadoAsignaturas->num_rows > 0) {
        while ($asignatura = $resultadoAsignaturas->fetch_assoc()) {
            $data = getLatestProgramData($asignatura['id']);
            if ($data && $data['fueDesaprobado'] == 1) {
                $countD++;
                $fechaCarga = new DateTime($data['fechaCarga']);
                $link = ($data['origen'] == 'pdf') ? "revisar.programa.pdf.php?id=".$data['id'] : "revisar.programa.php?id=".$data['id'];
                
                $rowsD .= '<tr>';
                $rowsD .= '<td>'.$asignatura['nombre'].'</td>';
                $rowsD .= '<td>'.$data['anio'].'</td>';
                $rowsD .= '<td>'.$fechaCarga->format('d/m/y').'</td>';
                $rowsD .= '<td><a href="'.$link.'" class="btn btn-outline-danger btn-sm"><span class="oi oi-eye"></span> Ver Ficha</a></td>';
                $rowsD .= '</tr>';
            }
        }
    }
    
    if ($countD > 0) {
        $html .= '<table class="table table-hover table-sm" id="tablaProgramaDesaprobados">
                    <thead>
                        <tr class="table-info">
                            <th>Asignatura</th>
                            <th>Año</th>
                            <th>Fecha de Carga</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>'.$rowsD.'</tbody></table>';
    } else {
        $html .= '<div class="alert alert-warning alert-dismissible fade show text-center" role="alert">No hay programas devueltos / desaprobados.</div>';
    }
    $html .= '</div>';
    
    $html .= '</div>'; // Fin tab-content
    
    echo $html;
}

// =================================================================================
// FUNCIONES AUXILIARES
// =================================================================================

function getLatestProgramData($idAsignatura) {
    $sqlPDF = "SELECT * FROM programa_pdf_detalle WHERE id_asignatura = '$idAsignatura' ORDER BY anio DESC, id DESC LIMIT 1";
    $resProgramaPDF = BDConexionSistema::getInstancia()->query($sqlPDF);
    
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
        if ($usarPDF) {
            return [
                'id' => $programaData['id'],
                'anio' => $programaData['anio'],
                'vigencia' => $programaData['vigencia'],
                'fechaCarga' => $programaData['fecha_carga'],
                'aprobadoVa' => $programaData['aprobado_va'],
                'aprobadoDepto' => $programaData['aprobado_depto'],
                'aprobadoEscuela' => $programaData['aprobado_escuela'],
                'aprobadoVaFirma' => $programaData['aprobado_va_firma'],
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
                'aprobadoVaFirma' => null,
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
    if ($data['origen'] == 'pdf') {
        if ($data['aprobadoVaFirma'] == 1) {
            return ['estado' => "Aprobado Totalmente", 'ubicacion' => "Finalizado", 'badgeClass' => "badge-success"];
        }
    } else {
        if ($data['aprobadoVa'] == 1 && $data['aprobadoDepto'] == 1 && $data['aprobadoEscuela'] == 1) {
            return ['estado' => "Aprobado Totalmente", 'ubicacion' => "Finalizado", 'badgeClass' => "badge-success"];
        }
    }
    
    if ($data['fueDesaprobado'] == 1) {
        return ['estado' => "Devuelto al Profesor", 'ubicacion' => "Profesor", 'badgeClass' => "badge-danger"];
    }
    
    if ($data['origen'] == 'pdf' && $data['aprobadoDepto'] == 1) {
        if ($data['enRevision'] == 1) {
            return ['estado' => "Pendiente Firma Final VA", 'ubicacion' => "Vinculación Académica (Firma)", 'badgeClass' => "badge-info"];
        } else {
            return ['estado' => "Revisado por Departamento", 'ubicacion' => "Departamento (Borrador)", 'badgeClass' => "badge-warning"];
        }
    }
    
    if ($data['aprobadoVa'] == 1) {
        if ($data['enRevision'] == 1) {
            return ['estado' => "Pendiente de Depto", 'ubicacion' => "Departamento", 'badgeClass' => "badge-info"];
        } else {
            return ['estado' => "Revisado por VA", 'ubicacion' => "Vinculación Académica (Borrador)", 'badgeClass' => "badge-warning"];
        }
    }
    
    if ($data['aprobadoEscuela'] == 1) {
        if ($data['enRevision'] == 1) {
            return ['estado' => "Pendiente de VA", 'ubicacion' => "Vinculación Académica", 'badgeClass' => "badge-info"];
        } else {
            return ['estado' => "Revisado por Escuela", 'ubicacion' => "Escuela (Borrador)", 'badgeClass' => "badge-warning"];
        }
    }
    
    if ($data['enRevision'] == 1) {
        return ['estado' => "Pendiente de Escuela", 'ubicacion' => "Escuela", 'badgeClass' => "badge-info"];
    }
    
    return ['estado' => "Borrador", 'ubicacion' => "Profesor", 'badgeClass' => "badge-secondary"];
}
?>