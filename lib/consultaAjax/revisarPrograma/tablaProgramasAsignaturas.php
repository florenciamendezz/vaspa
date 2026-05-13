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
                    $acciones = '<a title="Ver Programa" href="'.$link.'"><button type="button" class="btn btn-outline-info"><span class="oi oi-document"></span></button></a>';
                    
                    // Boton Subir PDF Firmado (Aprobar)
                    $puedeSubirFirmado = false;
                    if ($data['origen'] == 'pdf' && $rol != "Profesor" && $data['fueDesaprobado'] != 1) {
                        if (($rol == "Director de Departamento" || $rol == "DCNE" || $rol == "DCS" || $rol == 10) && $data['aprobadoDepto'] != 1 && $data['aprobadoEscuela'] == 1) {
                            $puedeSubirFirmado = true;
                        } elseif ($rol == "Director de Escuela" && $data['aprobadoEscuela'] != 1 && $data['enRevision'] == 1) {
                            $puedeSubirFirmado = true;
                        } elseif (($rol == "VA" || $rol == 8) && $data['aprobadoVa'] != 1 && $data['aprobadoDepto'] == 1) {
                            $puedeSubirFirmado = true;
                        }
                    }

                    if ($puedeSubirFirmado) {
                        $acciones .= ' <button type="button" class="btn btn-outline-warning btn-sm" title="Subir PDF Firmado y Aprobar" onclick="abrirModalSubirPdf('.$data['id'].')"><span class="oi oi-cloud-upload"></span></button>';
                    }
                    
                    // Boton Enviar a Revision
                    $puedeEnviarRevision = false;
                    if ($data['origen'] == 'pdf' && $data['fueDesaprobado'] != 1) {
                        // Logica ajustada: Si ya aprobo/subio, no mostrar boton de enviar
                        /* 
                        if ($rol == "Director de Escuela" && $data['aprobadoEscuela'] == 1 && $data['aprobadoDepto'] != 1) {
                            $puedeEnviarRevision = true;
                        } elseif (($rol == "Director de Departamento" || $rol == "DCNE" || $rol == "DCS" || $rol == 10) && $data['aprobadoDepto'] == 1 && $data['aprobadoVa'] != 1) {
                            $puedeEnviarRevision = true;
                        }
                        */
                        $puedeEnviarRevision = false;
                    }
                    
                    if ($puedeEnviarRevision) {
                         $acciones .= ' <a title="Enviar a Revisión" class="btn btn-outline-purple btn-sm" href="enviarProgramaRevision.php?idPrograma='.$data['id'].'" role="button"><span class="oi oi-share"></span></a>';
                    }
                    
                    // Mostrar quien subio el ultimo PDF firmado
                    if ($data['ruta_archivo']) {
                        if (strpos($data['ruta_archivo'], '_firmado-VA_') !== false) {
                            $acciones .= ' <span class="badge badge-info">Firmado por VA</span>';
                        } elseif (strpos($data['ruta_archivo'], '_firmado-Depto_') !== false) {
                            $acciones .= ' <span class="badge badge-warning">Firmado por Depto</span>';
                        } elseif (strpos($data['ruta_archivo'], '_firmado-Escuela_') !== false) {
                            $acciones .= ' <span class="badge badge-success">Firmado por Escuela</span>';
                        }
                    }
                    
                    $rows .= '<td>'.$acciones.'</td>';
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

                    $acciones = '';

                    if ($data['aprobadoVa'] == 1 && $data['aprobadoDepto'] == 1 && $data['aprobadoEscuela'] == 1) {
                         if ($data['origen'] == 'pdf') {
                            $acciones .= '<a title="Descargar Programa" href="programa.descargarPDF.php?id='.$data['id'].'&tipo=pdf" download><button type="button" class="btn btn-outline-primary"><span class="oi oi-data-transfer-download"></span></button></a>';
                        } else {
                            $acciones .= '<a title="Descargar Programa" href="programa.descargarPDF.php?id='.$data['id'].'&tipo=legacy"><button type="button" class="btn btn-outline-primary"><span class="oi oi-data-transfer-download"></span></button></a>';
                        }
                    } else {
                        $link = ($data['origen'] == 'pdf') ? "revisar.programa.pdf.php?id=".$data['id'] : "revisar.programa.php?id=".$data['id'];
                        $acciones .= '<a title="Ver Programa" href="'.$link.'"><button type="button" class="btn btn-outline-info"><span class="oi oi-document"></span></button></a>';
                    }

                    // Boton Subir PDF Firmado en Aprobados
                    $puedeSubirFirmado = false;
                    // Solo permitir modificar si NO ha sido aprobado totalmente (por VA)
                    if ($data['origen'] == 'pdf' && $data['aprobadoVa'] != 1) {
                        if (($rol == "Director de Departamento" || $rol == "DCNE" || $rol == "DCS" || $rol == 10) && $data['aprobadoDepto'] == 1) {
                            $puedeSubirFirmado = true;
                        } elseif ($rol == "Director de Escuela" && $data['aprobadoEscuela'] == 1) {
                            $puedeSubirFirmado = true;
                        } 
                        // VA se excluye aqui porque si aprobadoVa != 1, entonces VA no aprobo, 
                        // y si VA aprobo, el if principal lo oculta.
                        // Sin embargo, si queremos que VA pueda corregir MIENTRAS sea el ultimo... 
                        // El usuario pidio "aprobado por TODOS... desaparecer". 
                        // Si VA aprueba, es "aprobado por todos". Entonces desaparece.
                        // Por lo tanto, VA nunca vera este boton en la pestaña "Aprobados".
                    }

                    if ($puedeSubirFirmado) {
                        $acciones .= ' <button type="button" class="btn btn-outline-warning btn-sm" title="Subir PDF Firmado" onclick="abrirModalSubirPdf('.$data['id'].')"><span class="oi oi-cloud-upload"></span></button>';
                    }

                    // Boton Enviar a Revision (AGREGADO)
                    $puedeEnviarRevision = false;
                    if ($data['origen'] == 'pdf') {
                        if ($rol == "Director de Escuela" && $data['aprobadoEscuela'] == 1 && $data['aprobadoDepto'] != 1) {
                            $puedeEnviarRevision = true;
                        } elseif (($rol == "Director de Departamento" || $rol == "DCNE" || $rol == "DCS" || $rol == 10) && $data['aprobadoDepto'] == 1 && $data['aprobadoVa'] != 1) {
                            $puedeEnviarRevision = true;
                        }
                    }
                    
                    if ($puedeEnviarRevision) {
                         $acciones .= ' <a title="Enviar a Revisión" class="btn btn-outline-purple btn-sm" href="enviarProgramaRevision.php?idPrograma='.$data['id'].'" role="button"><span class="oi oi-share"></span></a>';
                    }

                    // Mostrar quien subio el ultimo PDF firmado
                    if ($data['ruta_archivo']) {
                        if (strpos($data['ruta_archivo'], '_firmado-VA_') !== false) {
                            $acciones .= ' <span class="badge badge-info">Firmado por VA</span>';
                        } elseif (strpos($data['ruta_archivo'], '_firmado-Depto_') !== false) {
                            $acciones .= ' <span class="badge badge-warning">Firmado por Depto</span>';
                        } elseif (strpos($data['ruta_archivo'], '_firmado-Escuela_') !== false) {
                            $acciones .= ' <span class="badge badge-success">Firmado por Escuela</span>';
                        }
                    }

                    $rows .= '<td>'.$acciones.'</td>';
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
    
    // Modal Subir PDF Firmado
    $html .= '
    <div class="modal fade" id="modalSubirPdf" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Subir PDF Firmado</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="../controlSistema/programa.actualizar.pdf.php" method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="idPrograma" id="idProgramaSubir">
                        <div class="form-group">
                            <label for="archivoPdf">Seleccione el archivo PDF firmado:</label>
                            <input type="file" class="form-control-file" id="archivoPdf" name="archivoPdf" accept=".pdf" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Subir Archivo</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    function abrirModalSubirPdf(idPrograma) {
        $("#idProgramaSubir").val(idPrograma);
        $("#modalSubirPdf").modal("show");
    }
    </script>
    ';
    
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
    // 1. Totalmente Aprobado
    if ($data['aprobadoVa'] == 1 && $data['aprobadoDepto'] == 1 && $data['aprobadoEscuela'] == 1) {
        return ['estado' => "Aprobado Totalmente", 'ubicacion' => "Finalizado", 'badgeClass' => "badge-success"];
    } 
    
    // 2. Rechazado / Desaprobado
    if ($data['fueDesaprobado'] == 1) {
        $estado = "Rechazado";
        if (!is_null($data['aprobadoEscuela']) && $data['aprobadoEscuela'] == 0) {
            $estado = "Desaprobado por Escuela";
        } elseif (!is_null($data['aprobadoDepto']) && $data['aprobadoDepto'] == 0) {
            $estado = "Desaprobado por Depto";
        } elseif (!is_null($data['aprobadoVa']) && $data['aprobadoVa'] == 0) {
            $estado = "Desaprobado por VA";
        }
        return ['estado' => $estado, 'ubicacion' => "Profesor", 'badgeClass' => "badge-danger"];
    }

    // 3. Flujo de Aprobación (Escuela -> Depto -> VA)
    // Si está aprobado por Depto (y no por VA aun), está en VA
    if ($data['aprobadoDepto'] == 1) {
        return ['estado' => "Aprobado por Depto", 'ubicacion' => "Vinculación Académica", 'badgeClass' => "badge-warning"];
    }
    
    // Si está aprobado por Escuela (y no por Depto aun), está en Depto
    if ($data['aprobadoEscuela'] == 1) {
        return ['estado' => "Aprobado por Escuela", 'ubicacion' => "Departamento", 'badgeClass' => "badge-warning"];
    }
    
    // Si está en revisión (y no aprobado por Escuela aun), está en Escuela
    if ($data['enRevision'] == 1) {
        return ['estado' => "En Revisión", 'ubicacion' => "Escuela", 'badgeClass' => "badge-info"];
    }
    
    // 4. Por defecto (recién cargado o borrador)
    return ['estado' => "Pendiente", 'ubicacion' => "Profesor", 'badgeClass' => "badge-secondary"];
}
?>