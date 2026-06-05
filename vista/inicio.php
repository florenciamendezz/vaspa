<?php
header('Content-Type: text/html; charset=ISO-8859-1');
include_once '../lib/ControlAcceso.Class.php';
ControlAcceso::verificaLogin();

include_once '../modeloSistema/BDConexionSistema.Class.php';
require_once '../modeloSistema/Profesor.Class.php';
require_once '../modeloSistema/Programa.Class.php';
require_once '../modeloSistema/Asignatura.Class.php';
require_once '../modeloSistema/ProgramaPDFDetalle.Class.php';
include_once '../lib/funcionesUtiles/constantesMail.php';

// Obtener datos del usuario en sesión
$usuario = $_SESSION['usuario'];
$rol = (isset($usuario->roles) && is_array($usuario->roles) && count($usuario->roles) > 0) ? $usuario->roles[0]->nombre : '';
$nombreUsuario = isset($usuario->nombre) ? $usuario->nombre : '';
$emailUsuario = isset($usuario->email) ? $usuario->email : '';

$anioActual = date("Y");

// Helper para vigencia
function getVigenciaTexto($anio, $vigencia) {
    switch ($vigencia) {
        case 1:
            return $anio;
        case 2:
            return $anio.' - '.($anio+1);
        case 3:
            return $anio.' - '.($anio+1).' - '.($anio+2);
        default:
            return $anio;
    }
}

// =================================================================================
// DECORADOR DEL STEPPER (Extraído idéntico para consistencia de UX)
// =================================================================================
function renderizarStepperInicio($data, $idAsignatura, $esInstitucional = null) {
    if (!$data) {
        return '
        <div class="stepper-wrapper" title="Estado: No Cargado">
            <div class="stepper-item active"><div class="step-counter">1</div><div class="step-name">Carga</div></div>
            <div class="stepper-item"><div class="step-counter">2</div><div class="step-name">Escuela</div></div>
            <div class="stepper-item"><div class="step-counter">3</div><div class="step-name">VA</div></div>
            <div class="stepper-item"><div class="step-counter">4</div><div class="step-name">Depto</div></div>
            <div class="stepper-item"><div class="step-counter">5</div><div class="step-name">Firma</div></div>
        </div>';
    }

    if ($esInstitucional === null) {
        $conexion = BDConexionSistema::getInstancia();
        $idAsigEscaped = $conexion->real_escape_string($idAsignatura);
        $sqlInst = "SELECT es_institucional FROM asignatura WHERE id = '{$idAsigEscaped}'";
        $resInst = $conexion->query($sqlInst);
        $esInstitucional = false;
        if ($resInst && $resInst->num_rows > 0) {
            $rowInst = $resInst->fetch_assoc();
            $esInstitucional = ($rowInst['es_institucional'] == 1);
        }
    }
    
    $p1 = 2; // Profesor siempre cargó
    $p2 = 0; 
    $p3 = 0; 
    $p4 = 0; 
    $p5 = 0; 
    
    $fueDesaprobado = $data['fueDesaprobado'] == 1;
    $enRevision = $data['enRevision'] == 1;
    
    $aprobadoEscuela = ($data['aprobadoEscuela'] == 1);
    $aprobadoVa = ($data['aprobadoVa'] == 1);
    $aprobadoDepto = ($data['aprobadoDepto'] == 1);
    $aprobadoVaFirma = ($data['aprobadoVaFirma'] == 1);
    
    if ($esInstitucional) {
        $p2 = 4; // N/A Escuela
    }
    
    if ($fueDesaprobado) {
        if (!$esInstitucional && !$aprobadoEscuela) {
            $p2 = 3;
        } elseif (!$aprobadoVa) {
            if (!$esInstitucional) $p2 = 2;
            $p3 = 3;
        } elseif (!$aprobadoDepto) {
            if (!$esInstitucional) $p2 = 2;
            $p3 = 2;
            $p4 = 3;
        } elseif ($data['origen'] == 'pdf' && !$aprobadoVaFirma) {
            if (!$esInstitucional) $p2 = 2;
            $p3 = 2;
            $p4 = 2;
            $p5 = 3;
        }
    } else {
        if ($enRevision) {
            if (!$esInstitucional && !$aprobadoEscuela) {
                $p2 = 1;
            } elseif (!$aprobadoVa) {
                if (!$esInstitucional) $p2 = 2;
                $p3 = 1;
            } elseif (!$aprobadoDepto) {
                if (!$esInstitucional) $p2 = 2;
                $p3 = 2;
                $p4 = 1;
            } elseif ($data['origen'] == 'pdf' && !$aprobadoVaFirma) {
                if (!$esInstitucional) $p2 = 2;
                $p3 = 2;
                $p4 = 2;
                $p5 = 1;
            }
        } else {
            $estaAprobadoTotal = ($data['origen'] == 'pdf') ? $aprobadoVaFirma : ($aprobadoVa && $aprobadoDepto && ($esInstitucional || $aprobadoEscuela));
            if ($estaAprobadoTotal) {
                if (!$esInstitucional) $p2 = 2;
                $p3 = 2;
                $p4 = 2;
                $p5 = 2;
            } else {
                if ($aprobadoEscuela && !$esInstitucional) $p2 = 2;
                if ($aprobadoVa) {
                    if (!$esInstitucional) $p2 = 2;
                    $p3 = 2;
                }
                if ($aprobadoDepto) {
                    if (!$esInstitucional) $p2 = 2;
                    $p3 = 2;
                    $p4 = 2;
                }
            }
        }
    }

    $renderStep = function($num, $name, $status) {
        $class = 'stepper-item';
        $content = $num;
        if ($status == 1) {
            $class .= ' active';
        } elseif ($status == 2) {
            $class .= ' completed';
            $content = '✓';
        } elseif ($status == 3) {
            $class .= ' returned';
            $content = '✗';
        } elseif ($status == 4) {
            $class .= ' na';
            $content = '-';
            $name .= ' <small>(N/A)</small>';
        }
        return '<div class="'.$class.'"><div class="step-counter">'.$content.'</div><div class="step-name">'.$name.'</div></div>';
    };

    $html = '<div class="stepper-wrapper">';
    $html .= $renderStep(1, 'Carga', $p1);
    $html .= $renderStep(2, 'Escuela', $p2);
    $html .= $renderStep(3, 'VA', $p3);
    $html .= $renderStep(4, 'Depto', $p4);
    $html .= $renderStep(5, 'Firma', $p5);
    $html .= '</div>';
    return $html;
}

// Helpers para obtener info de estado de BD
function getLatestProgramDataInicio($idAsignatura, $anio = null) {
    $condAnioPDF = $anio ? "AND anio = $anio" : "";
    $condAnioLegacy = $anio ? "AND anio = $anio" : "";
    $sqlPDF = "SELECT * FROM programa_pdf_detalle WHERE id_asignatura = '$idAsignatura' $condAnioPDF ORDER BY anio DESC, id DESC LIMIT 1";
    $resProgramaPDF = BDConexionSistema::getInstancia()->query($sqlPDF);
    
    $sqlLegacy = "SELECT * FROM programa WHERE idAsignatura = '$idAsignatura' $condAnioLegacy ORDER BY anio DESC, id DESC LIMIT 1";
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
                'idAsignatura' => $idAsignatura,
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
                'comentario_desaprobacion' => isset($programaData['comentario_desaprobacion']) ? $programaData['comentario_desaprobacion'] : '',
                'origen' => 'pdf'
            ];
        } else {
            return [
                'id' => $programaData['id'],
                'idAsignatura' => $idAsignatura,
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
                'comentario_desaprobacion' => '',
                'origen' => 'legacy'
            ];
        }
    }
    return null;
}

function getStatusInfoInicio($data) {
    $esInstitucional = false;
    if (isset($data['idAsignatura'])) {
        $conexion = BDConexionSistema::getInstancia();
        $idAsigEscaped = $conexion->real_escape_string($data['idAsignatura']);
        $sqlInst = "SELECT es_institucional FROM asignatura WHERE id = '{$idAsigEscaped}'";
        $resInst = $conexion->query($sqlInst);
        if ($resInst && $resInst->num_rows > 0) {
            $rowInst = $resInst->fetch_assoc();
            $esInstitucional = ($rowInst['es_institucional'] == 1);
        }
    }

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
            // Si es institucional, no se considera "Revisado por Escuela" ya que no interviene la Escuela
            if (!$esInstitucional) {
                return ['estado' => "Revisado por Escuela", 'ubicacion' => "Escuela (Borrador)", 'badgeClass' => "badge-warning"];
            }
        }
    }
    
    if ($data['enRevision'] == 1) {
        if ($esInstitucional) {
            return ['estado' => "Pendiente de VA", 'ubicacion' => "Vinculación Académica", 'badgeClass' => "badge-info"];
        }
        return ['estado' => "Pendiente de Escuela", 'ubicacion' => "Escuela", 'badgeClass' => "badge-info"];
    }
    
    return ['estado' => "Borrador", 'ubicacion' => "Profesor", 'badgeClass' => "badge-secondary"];
}

// =================================================================================
// RECOPILACIÓN DE PENDIENTES SEGÚN ROL
// =================================================================================
$pendientesBuzon = [];

if ($rol == PermisosSistema::ROL_PROFESOR) {
    // Es un profesor
    $sql = "SELECT * FROM profesor WHERE email = '{$emailUsuario}'";
    $resultado = BDConexionSistema::getInstancia()->query($sql);
    $profesor = ($resultado && $resultado->num_rows > 0) ? $resultado->fetch_object("Profesor") : null;
    
    if ($profesor) {
        $asignaturasProf = $profesor->obtenerAsignaturasDePlanVigente();
        if (is_array($asignaturasProf)) {
            $asignaturasProcesadas = array();
            foreach ($asignaturasProf as $asig) {
                if (in_array($asig->getId(), $asignaturasProcesadas)) {
                    continue;
                }
                $asignaturasProcesadas[] = $asig->getId();
                $programaDetalle = getLatestProgramDataInicio($asig->getId(), $anioActual);
                
                $esPendiente = false;
                $accionNombre = "";
                $estadoNombre = "No Cargado";
                $claseEstado = "badge-secondary";
                $link = "";
                $motivoPendiente = "";
                
                if (is_null($programaDetalle)) {
                    $esPendiente = true;
                    $accionNombre = "Subir Programa";
                    $estadoNombre = "No Cargado";
                    $claseEstado = "badge-secondary";
                    $link = "programa.crear.php?id=" . $asig->getId();
                    $motivoPendiente = "Falta cargar el programa para el año actual.";
                } else {
                    $estadoReal = $programaDetalle['estado'] = getStatusInfoInicio($programaDetalle)['estado'];
                    $estadoNombre = $estadoReal;
                    
                    if ($estadoReal == 'Borrador') {
                        $esPendiente = true;
                        $accionNombre = "Modificar Borrador";
                        $claseEstado = "badge-warning";
                        $link = "programa.crear.php?id=" . $asig->getId();
                        $motivoPendiente = "Programa en borrador. Pendiente enviar a revisión.";
                    } elseif ($estadoReal == 'Devuelto al Profesor') {
                        $esPendiente = true;
                        $accionNombre = "Corregir Programa";
                        $claseEstado = "badge-danger";
                        $link = "programa.crear.php?id=" . $asig->getId();
                        
                        $motivoPendiente = "Devuelto con observaciones.";
                        $idPdfDetalle = intval($programaDetalle['id']);
                        $sqlDevs = "SELECT rol_revisor, comentario, leido, fecha FROM programa_devoluciones 
                                    WHERE id_programa_pdf = {$idPdfDetalle} AND resuelto = 0 
                                    ORDER BY fecha DESC";
                        $resDevs = BDConexionSistema::getInstancia()->query($sqlDevs);
                        if ($resDevs && $resDevs->num_rows > 0) {
                            $motivoPendiente .= "<ul class='mb-0 pl-3 mt-1' style='list-style-type: disc;'>";
                            while ($dFila = $resDevs->fetch_assoc()) {
                                $estadoLeido = $dFila['leido'] == 1 ? "<span class='badge badge-secondary py-0 px-1 ml-1' style='font-size: 0.65rem;'>Leído</span>" : "<span class='badge badge-info py-0 px-1 ml-1' style='font-size: 0.65rem;'>Nuevo</span>";
                                $fechaFormat = date('d/m/Y H:i', strtotime($dFila['fecha']));
                                $motivoPendiente .= "<li class='mb-1'><strong>" . htmlspecialchars($dFila['rol_revisor']) . "</strong> (" . $fechaFormat . ") " . $estadoLeido . ":<br><span class='text-secondary'>" . nl2br(htmlspecialchars($dFila['comentario'])) . "</span></li>";
                            }
                            $motivoPendiente .= "</ul>";
                        } else {
                            $motivoPendiente .= "<br>" . htmlspecialchars($programaDetalle['comentario_desaprobacion']);
                        }
                    }
                }
                
                if ($esPendiente) {
                    $carreras = $asig->getCarreras();
                    $carrerasNombres = [];
                    if ($carreras) {
                        foreach ($carreras as $c) {
                            $carrerasNombres[] = $c->getNombre();
                        }
                    }
                    
                    $pendientesBuzon[] = [
                        'id' => $asig->getId(),
                        'nombre' => $asig->getNombre(),
                        'carreras' => $carrerasNombres,
                        'anio' => $programaDetalle ? $programaDetalle['anio'] : date('Y'),
                        'estado' => $estadoNombre,
                        'claseEstado' => $claseEstado,
                        'accion' => $accionNombre,
                        'link' => $link,
                        'motivo' => $motivoPendiente,
                        'data' => $programaDetalle,
                        'original_id' => $programaDetalle ? $programaDetalle['id'] : null
                    ];
                }
            }
        }
    }
} elseif ($rol == PermisosSistema::ROL_DIRECTOR_ESCUELA) {
    // Director de Escuela
    $sqlPDF = "SELECT DISTINCT ppd.id as idProgramaPDF, ppd.anio, ppd.fecha_carga, ppd.aprobado_escuela, ppd.en_revision, ppd.aprobado_va, ppd.fue_desaprobado, a.id as idAsignatura, a.nombre as nombreAsignatura
               FROM programa_pdf_detalle ppd
               JOIN asignatura a ON ppd.id_asignatura = a.id
               WHERE (
                   (ppd.en_revision = 1 AND ppd.aprobado_escuela IS NULL AND ppd.fue_desaprobado = 0)
                   OR
                   (ppd.aprobado_escuela = 1 AND ppd.en_revision = 0 AND ppd.aprobado_va IS NULL AND ppd.fue_desaprobado = 0)
               ) AND ppd.anio <= {$anioActual}
               ORDER BY ppd.fecha_carga DESC";
    $resPDF = BDConexionSistema::getInstancia()->query($sqlPDF);

    $sqlLegacy = "SELECT DISTINCT p.id as idPrograma, p.anio, p.fechaCarga, p.aprobadoEscuela, p.enRevision, p.aprobadoVa, p.fueDesaprobado, a.id as idAsignatura, a.nombre as nombreAsignatura
                  FROM programa p
                  JOIN asignatura a ON p.idAsignatura = a.id
                  LEFT JOIN programa_pdf_detalle ppd ON p.idAsignatura = ppd.id_asignatura AND p.anio = ppd.anio
                  WHERE ppd.id IS NULL AND (
                      (p.enRevision = 1 AND p.aprobadoEscuela IS NULL AND p.fueDesaprobado = 0)
                  ) AND p.anio <= {$anioActual}
                  ORDER BY p.fechaCarga DESC";
    $resLegacy = BDConexionSistema::getInstancia()->query($sqlLegacy);

    if ($resPDF && $resPDF->num_rows > 0) {
        while ($row = $resPDF->fetch_assoc()) {
            $data = getLatestProgramDataInicio($row['idAsignatura']);
            
            $accion = "";
            $link = "";
            $motivo = "";
            if ($row['en_revision'] == 1 && $row['aprobado_escuela'] === null) {
                $accion = "Revisar Programa";
                $link = "revisar.programa.pdf.php?id=" . $row['idProgramaPDF'];
                $motivo = "Programa cargado por el profesor listo para la evaluación de la Escuela.";
            } elseif ($row['aprobado_escuela'] == 1 && $row['en_revision'] == 0 && $row['aprobado_va'] === null) {
                $accion = "Enviar a Vinculación";
                $link = "revisar.programa.pdf.php?id=" . $row['idProgramaPDF'];
                $motivo = "Aprobado por Escuela. Pendiente de ser enviado formalmente a Vinculación Académica.";
            }
            
            $asigObj = new Asignatura($row['idAsignatura']);
            $carreras = $asigObj->getCarreras();
            $carrerasNombres = [];
            if ($carreras) {
                foreach ($carreras as $c) {
                    $carrerasNombres[] = $c->getNombre();
                }
            }
            
            $pendientesBuzon[] = [
                'id' => $row['idAsignatura'],
                'nombre' => $row['nombreAsignatura'],
                'carreras' => $carrerasNombres,
                'anio' => $row['anio'],
                'estado' => $data ? getStatusInfoInicio($data)['estado'] : 'En Revisión',
                'claseEstado' => 'badge-info',
                'accion' => $accion,
                'link' => $link,
                'motivo' => $motivo,
                'data' => $data
            ];
        }
    }

    if ($resLegacy && $resLegacy->num_rows > 0) {
        while ($row = $resLegacy->fetch_assoc()) {
            $data = getLatestProgramDataInicio($row['idAsignatura']);
            
            $accion = "Revisar Programa";
            $link = "revisar.programa.php?id=" . $row['idPrograma'];
            $motivo = "Programa web legacy listo para la evaluación de la Escuela.";
            
            $asigObj = new Asignatura($row['idAsignatura']);
            $carreras = $asigObj->getCarreras();
            $carrerasNombres = [];
            if ($carreras) {
                foreach ($carreras as $c) {
                    $carrerasNombres[] = $c->getNombre();
                }
            }
            
            $pendientesBuzon[] = [
                'id' => $row['idAsignatura'],
                'nombre' => $row['nombreAsignatura'],
                'carreras' => $carrerasNombres,
                'anio' => $row['anio'],
                'estado' => $data ? getStatusInfoInicio($data)['estado'] : 'En Revisión',
                'claseEstado' => 'badge-info',
                'accion' => $accion,
                'link' => $link,
                'motivo' => $motivo,
                'data' => $data
            ];
        }
    }
} elseif ($rol == PermisosSistema::ROL_DIRECTOR_DEPARTAMENTO) {
    // Director de Departamento
    $deptoFiltro = "1=1";
    if ($emailUsuario == MAIL_DEPTO_CNE) {
        $deptoFiltro = "a.idDepartamento = 1";
    } elseif ($emailUsuario == MAIL_DEPTO_CS) {
        $deptoFiltro = "a.idDepartamento = 2";
    }

    $sqlPDF = "SELECT DISTINCT ppd.id as idProgramaPDF, ppd.anio, ppd.fecha_carga, ppd.aprobado_va, ppd.aprobado_depto, ppd.aprobado_va_firma, ppd.en_revision, ppd.fue_desaprobado, a.id as idAsignatura, a.nombre as nombreAsignatura
               FROM programa_pdf_detalle ppd
               JOIN asignatura a ON ppd.id_asignatura = a.id
               WHERE ({$deptoFiltro}) AND (
                   (ppd.en_revision = 1 AND ppd.aprobado_va = 1 AND ppd.aprobado_depto IS NULL AND ppd.fue_desaprobado = 0)
                   OR
                   (ppd.aprobado_depto = 1 AND ppd.en_revision = 0 AND ppd.aprobado_va_firma IS NULL AND ppd.fue_desaprobado = 0)
               ) AND ppd.anio <= {$anioActual}
               ORDER BY ppd.fecha_carga DESC";
    $resPDF = BDConexionSistema::getInstancia()->query($sqlPDF);

    $sqlLegacy = "SELECT DISTINCT p.id as idPrograma, p.anio, p.fechaCarga, p.aprobadoVa, p.aprobadoDepto, p.enRevision, p.fueDesaprobado, a.id as idAsignatura, a.nombre as nombreAsignatura
                  FROM programa p
                  JOIN asignatura a ON p.idAsignatura = a.id
                  LEFT JOIN programa_pdf_detalle ppd ON p.idAsignatura = ppd.id_asignatura AND p.anio = ppd.anio
                  WHERE ppd.id IS NULL AND ({$deptoFiltro}) AND (
                      (p.enRevision = 1 AND p.aprobadoVa = 1 AND p.aprobadoDepto IS NULL AND p.fueDesaprobado = 0)
                  ) AND p.anio <= {$anioActual}
                  ORDER BY p.fechaCarga DESC";
    $resLegacy = BDConexionSistema::getInstancia()->query($sqlLegacy);

    if ($resPDF && $resPDF->num_rows > 0) {
        while ($row = $resPDF->fetch_assoc()) {
            $data = getLatestProgramDataInicio($row['idAsignatura']);
            
            $accion = "";
            $link = "";
            $motivo = "";
            if ($row['en_revision'] == 1 && $row['aprobado_depto'] === null) {
                $accion = "Revisar Programa";
                $link = "revisar.programa.pdf.php?id=" . $row['idProgramaPDF'];
                $motivo = "Acreditado por Vinculación. Listo para la revisión y firma del Departamento.";
            } elseif ($row['aprobado_depto'] == 1 && $row['en_revision'] == 0 && $row['aprobado_va_firma'] === null) {
                $accion = "Enviar a VA (Firma Final)";
                $link = "revisar.programa.pdf.php?id=" . $row['idProgramaPDF'];
                $motivo = "Firmado por Departamento. Listo para despacharse a Vinculación Académica para su firma final.";
            }
            
            $asigObj = new Asignatura($row['idAsignatura']);
            $carreras = $asigObj->getCarreras();
            $carrerasNombres = [];
            if ($carreras) {
                foreach ($carreras as $c) {
                    $carrerasNombres[] = $c->getNombre();
                }
            }
            
            $pendientesBuzon[] = [
                'id' => $row['idAsignatura'],
                'nombre' => $row['nombreAsignatura'],
                'carreras' => $carrerasNombres,
                'anio' => $row['anio'],
                'estado' => $data ? getStatusInfoInicio($data)['estado'] : 'En Revisión',
                'claseEstado' => 'badge-info',
                'accion' => $accion,
                'link' => $link,
                'motivo' => $motivo,
                'data' => $data
            ];
        }
    }

    if ($resLegacy && $resLegacy->num_rows > 0) {
        while ($row = $resLegacy->fetch_assoc()) {
            $data = getLatestProgramDataInicio($row['idAsignatura']);
            
            $accion = "Revisar Programa";
            $link = "revisar.programa.php?id=" . $row['idPrograma'];
            $motivo = "Acreditado por Vinculación. Listo para la revisión y firma del Departamento (Legacy).";
            
            $asigObj = new Asignatura($row['idAsignatura']);
            $carreras = $asigObj->getCarreras();
            $carrerasNombres = [];
            if ($carreras) {
                foreach ($carreras as $c) {
                    $carrerasNombres[] = $c->getNombre();
                }
            }
            
            $pendientesBuzon[] = [
                'id' => $row['idAsignatura'],
                'nombre' => $row['nombreAsignatura'],
                'carreras' => $carrerasNombres,
                'anio' => $row['anio'],
                'estado' => $data ? getStatusInfoInicio($data)['estado'] : 'En Revisión',
                'claseEstado' => 'badge-info',
                'accion' => $accion,
                'link' => $link,
                'motivo' => $motivo,
                'data' => $data
            ];
        }
    }
} elseif ($rol == PermisosSistema::ROL_VINCULACION_ACADEMICA || $rol == PermisosSistema::ROL_ADMIN) {
    // Vinculación Académica o Administrador (tienen los mismos privilegios de revisión)
    $sqlPDF = "SELECT DISTINCT ppd.id as idProgramaPDF, ppd.anio, ppd.fecha_carga, ppd.aprobado_escuela, ppd.en_revision, ppd.aprobado_va, ppd.aprobado_depto, ppd.aprobado_va_firma, ppd.fue_desaprobado, a.id as idAsignatura, a.nombre as nombreAsignatura
               FROM programa_pdf_detalle ppd
               JOIN asignatura a ON ppd.id_asignatura = a.id
               WHERE (
                   (ppd.en_revision = 1 AND ppd.aprobado_va IS NULL AND ppd.fue_desaprobado = 0 AND (ppd.aprobado_escuela = 1 OR ppd.id_asignatura = '1108'))
                   OR
                   (ppd.aprobado_va = 1 AND ppd.en_revision = 0 AND ppd.aprobado_depto IS NULL AND ppd.fue_desaprobado = 0)
                   OR
                   (ppd.en_revision = 1 AND ppd.aprobado_depto = 1 AND ppd.aprobado_va_firma IS NULL AND ppd.fue_desaprobado = 0)
               ) AND ppd.anio <= {$anioActual}
               ORDER BY ppd.fecha_carga DESC";
    $resPDF = BDConexionSistema::getInstancia()->query($sqlPDF);

    $sqlLegacy = "SELECT DISTINCT p.id as idPrograma, p.anio, p.fechaCarga, p.aprobadoEscuela, p.enRevision, p.aprobadoVa, p.fueDesaprobado, a.id as idAsignatura, a.nombre as nombreAsignatura
                  FROM programa p
                  JOIN asignatura a ON p.idAsignatura = a.id
                  LEFT JOIN programa_pdf_detalle ppd ON p.idAsignatura = ppd.id_asignatura AND p.anio = ppd.anio
                  WHERE ppd.id IS NULL AND (
                      (p.enRevision = 1 AND p.aprobadoVa IS NULL AND p.fueDesaprobado = 0 AND (p.aprobadoEscuela = 1 OR p.idAsignatura = '1108'))
                  ) AND p.anio <= {$anioActual}
                  ORDER BY p.fechaCarga DESC";
    $resLegacy = BDConexionSistema::getInstancia()->query($sqlLegacy);

    if ($resPDF && $resPDF->num_rows > 0) {
        while ($row = $resPDF->fetch_assoc()) {
            $data = getLatestProgramDataInicio($row['idAsignatura']);
            
            $accion = "";
            $link = "";
            $motivo = "";
            if ($row['en_revision'] == 1 && $row['aprobado_va'] === null) {
                $accion = "Revisar Acreditación";
                $link = "revisar.programa.pdf.php?id=" . $row['idProgramaPDF'];
                $motivo = "Aprobado por Escuela. Pendiente de acreditación por Vinculación Académica (1° Paso).";
            } elseif ($row['aprobado_va'] == 1 && $row['en_revision'] == 0 && $row['aprobado_depto'] === null) {
                $accion = "Enviar a Departamento";
                $link = "revisar.programa.pdf.php?id=" . $row['idProgramaPDF'];
                $motivo = "Acreditado. Pendiente de despachar a Departamento para evaluación y firma.";
            } elseif ($data && $data['origen'] == 'pdf' && $row['en_revision'] == 1 && $row['aprobado_depto'] == 1 && $row['aprobado_va_firma'] === null) {
                $accion = "Confirmar Firma Final";
                $link = "revisar.programa.pdf.php?id=" . $row['idProgramaPDF'];
                $motivo = "Firmado por Departamento. Pendiente de Firma Final de Vinculación Académica para cerrar circuito.";
            }
            
            $asigObj = new Asignatura($row['idAsignatura']);
            $carreras = $asigObj->getCarreras();
            $carrerasNombres = [];
            if ($carreras) {
                foreach ($carreras as $c) {
                    $carrerasNombres[] = $c->getNombre();
                }
            }
            
            $pendientesBuzon[] = [
                'id' => $row['idAsignatura'],
                'nombre' => $row['nombreAsignatura'],
                'carreras' => $carrerasNombres,
                'anio' => $row['anio'],
                'estado' => $data ? getStatusInfoInicio($data)['estado'] : 'En Revisión',
                'claseEstado' => 'badge-info',
                'accion' => $accion,
                'link' => $link,
                'motivo' => $motivo,
                'data' => $data
            ];
        }
    }

    if ($resLegacy && $resLegacy->num_rows > 0) {
        while ($row = $resLegacy->fetch_assoc()) {
            $data = getLatestProgramDataInicio($row['idAsignatura']);
            
            $accion = "Revisar Acreditación";
            $link = "revisar.programa.php?id=" . $row['idPrograma'];
            $motivo = "Aprobado por Escuela. Pendiente de acreditación por Vinculación Académica (Legacy - 1° Paso).";
            
            $asigObj = new Asignatura($row['idAsignatura']);
            $carreras = $asigObj->getCarreras();
            $carrerasNombres = [];
            if ($carreras) {
                foreach ($carreras as $c) {
                    $carrerasNombres[] = $c->getNombre();
                }
            }
            
            $pendientesBuzon[] = [
                'id' => $row['idAsignatura'],
                'nombre' => $row['nombreAsignatura'],
                'carreras' => $carrerasNombres,
                'anio' => $row['anio'],
                'estado' => $data ? getStatusInfoInicio($data)['estado'] : 'En Revisión',
                'claseEstado' => 'badge-info',
                'accion' => $accion,
                'link' => $link,
                'motivo' => $motivo,
                'data' => $data
            ];
        }
    }
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title><?php echo Constantes::NOMBRE_SISTEMA; ?> - Dashboard de Inicio</title>
    
    <!-- Google Fonts Outfit -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- CSS & JS -->
    <link rel="stylesheet" href="../lib/bootstrap-4.1.1-dist/css/bootstrap.css" />
    <link rel="stylesheet" href="../lib/open-iconic-master/font/css/open-iconic-bootstrap.css" />
    <script type="text/javascript" src="../lib/JQuery/jquery-3.3.1.js"></script>
    <script type="text/javascript" src="../lib/bootstrap-4.1.1-dist/js/bootstrap.min.js"></script>
    
    <style>
        body {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            color: #1e293b;
            font-family: 'Outfit', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            min-height: 100vh;
        }

        /* Jumbotron Premium */
        .jumbotron-premium {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: white;
            border-radius: 16px;
            padding: 2.25rem 2.5rem;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
            margin-bottom: 2.5rem;
            position: relative;
            overflow: hidden;
        }

        .badge-rol {
            display: inline-flex;
            align-items: center;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: rgba(255, 255, 255, 0.9);
            border-radius: 30px;
            padding: 0.35rem 0.9rem;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        /* Buzón de Pendientes en Tarjetas */
        .card-premium-buzon {
            background: rgba(255, 255, 255, 0.85);
            border: none;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
            backdrop-filter: blur(10px);
            margin-bottom: 2rem;
        }

        .card-header-buzon {
            background: transparent;
            border-bottom: 1px solid #f1f5f9;
            padding: 1.25rem 1.5rem;
        }

        .card-pendiente {
            background: white;
            border: 1px solid #f1f5f9;
            border-radius: 14px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.02);
            transition: transform 0.22s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.22s ease;
            margin-bottom: 1.25rem;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            padding: 1.25rem;
        }

        @media (min-width: 992px) {
            .card-pendiente {
                flex-direction: row;
                align-items: center;
                justify-content: space-between;
            }
        }

        .card-pendiente:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.06);
        }

        /* Indicadores laterales de estado */
        .card-pendiente.status-borrador { border-left: 6px solid #94a3b8; }
        .card-pendiente.status-devuelto { border-left: 6px solid #ef4444; }
        .card-pendiente.status-revision { border-left: 6px solid #6366f1; }
        .card-pendiente.status-desconocido { border-left: 6px solid #cbd5e1; }

        .nombre-asignatura {
            color: #0f172a;
            font-size: 1.15rem;
            font-weight: 700;
        }

        .detail-tag {
            background-color: #f1f5f9;
            color: #475569;
            font-size: 0.8rem;
            padding: 0.25rem 0.6rem;
            border-radius: 6px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
        }

        .observaciones-box {
            background-color: #fef2f2;
            border: 1px solid #fee2e2;
            color: #b91c1c;
            border-radius: 8px;
            padding: 0.75rem;
            margin-top: 0.75rem;
            font-size: 0.85rem;
        }

        /* Botones de acción Premium */
        .btn-premium-action {
            font-weight: 600;
            border-radius: 10px;
            padding: 0.5rem 1.25rem;
            transition: all 0.2s ease;
            font-size: 0.875rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: none;
            cursor: pointer;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }

        .btn-premium-success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white !important;
        }
        .btn-premium-success:hover {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            transform: translateY(-1px);
        }

        .btn-premium-warning {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white !important;
        }
        .btn-premium-warning:hover {
            background: linear-gradient(135deg, #d97706 0%, #b45309 100%);
            transform: translateY(-1px);
        }

        .btn-premium-primary {
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            color: white !important;
        }
        .btn-premium-primary:hover {
            background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%);
            transform: translateY(-1px);
        }

        .btn-premium-outline-secondary {
            background-color: transparent;
            border: 1.5px solid #cbd5e1;
            color: #475569;
        }
        .btn-premium-outline-secondary:hover {
            background-color: #f1f5f9;
            color: #1e293b;
        }

        /* Accesos Rápidos */
        .card-acceso {
            background: white;
            border: none;
            border-radius: 16px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.02);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            height: 100%;
            overflow: hidden;
        }

        .card-acceso:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.06);
        }

        .acceso-icon-container {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            color: white;
            margin-bottom: 1.25rem;
        }

        .icon-blue { background: linear-gradient(135deg, #60a5fa 0%, #2563eb 100%); }
        .icon-purple { background: linear-gradient(135deg, #c084fc 0%, #7c3aed 100%); }
        .icon-pink { background: linear-gradient(135deg, #f472b6 0%, #db2777 100%); }
        .icon-indigo { background: linear-gradient(135deg, #818cf8 0%, #4f46e5 100%); }

        /* Estilos del Stepper Moderno */
        .stepper-wrapper {
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            width: 100%;
            margin: 0.5rem 0;
        }
        .stepper-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            z-index: 2;
            flex: 1;
        }
        .stepper-item::before {
            position: absolute;
            content: "";
            border-bottom: 2px solid #10b981;
            width: 100%;
            top: 10px;
            left: -50%;
            z-index: -1;
        }
        .stepper-item:first-child::before {
            content: none;
        }
        .step-counter {
            position: relative;
            z-index: 5;
            display: flex;
            justify-content: center;
            align-items: center;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #e2e8f0;
            color: #64748b;
            font-weight: 700;
            font-size: 0.65rem;
            margin-bottom: 3px;
            border: 2px solid white;
            transition: all 0.2s ease;
        }
        .step-name {
            font-size: 0.6rem;
            color: #64748b;
            font-weight: 600;
            text-align: center;
            white-space: nowrap;
        }
        .stepper-item.completed .step-counter {
            background-color: #10b981;
            color: #fff;
        }
        .stepper-item.completed::before {
            border-bottom-color: #10b981;
        }
        .stepper-item.active .step-counter {
            background-color: #3b82f6;
            color: #fff;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.25);
        }
        .stepper-item.active::before {
            border-bottom-color: #93c5fd;
        }
        .stepper-item.active .step-name {
            color: #3b82f6;
            font-weight: 700;
        }
        .stepper-item.returned .step-counter {
            background-color: #ef4444;
            color: #fff;
        }
        .stepper-item.returned::before {
            border-bottom-color: #fca5a5;
        }
        .stepper-item.returned .step-name {
            color: #ef4444;
            font-weight: 700;
        }
        .stepper-item.na .step-counter {
            background-color: #f1f5f9;
            color: #94a3b8;
            border: 1px dashed #cbd5e1;
        }
        .stepper-item.na .step-name {
            color: #94a3b8;
        }
    </style>
</head>
<body>

    <?php include_once '../gui/navbar.php'; ?>

    <div class="container my-5">
        
        <!-- BIENVENIDA ESTILO PREMIUM -->
        <div class="jumbotron-premium">
            <div class="badge-rol mb-3">
                <span class="oi oi-shield mr-2" style="font-size: 0.85rem; opacity: 0.9;"></span>
                Rol: <?php echo htmlspecialchars($rol); ?>
            </div>
            <h1 class="display-5 font-weight-bold mb-2">¡Hola, <?php echo htmlspecialchars($nombreUsuario); ?>!</h1>
            <p class="lead mb-0" style="opacity: 0.9; font-size: 1.05rem;">
                Este es tu panel centralizado de control. Aquí se listan las acciones que requieren tu firma o intervención de forma urgente.
            </p>
        </div>

        <!-- BUZÓN DE PENDIENTES EN TARJETAS MODERNAS -->
        <div class="card card-premium-buzon">
            <div class="card-header-buzon d-flex align-items-center">
                <h4 class="mb-0 font-weight-bold text-dark" style="font-size: 1.25rem;">
                    <span class="oi oi-inbox text-primary mr-2"></span> Mi Buzón de Pendientes
                </h4>
            </div>
            <div class="card-body p-4">
                <?php if (count($pendientesBuzon) > 0): ?>
                    <div class="listado-pendientes">
                        <?php foreach ($pendientesBuzon as $item): ?>
                            <?php 
                            // Determinar clase de estado
                            $cardStatusClass = "status-desconocido";
                            if ($item['estado'] == 'Borrador' || $item['estado'] == 'No Cargado') {
                                $cardStatusClass = "status-borrador";
                            } elseif ($item['estado'] == 'Devuelto al Profesor') {
                                $cardStatusClass = "status-devuelto";
                            } elseif (strpos($item['estado'], 'Pendiente') !== false || strpos($item['estado'], 'Revisando') !== false || strpos($item['estado'], 'Revisado') !== false) {
                                $cardStatusClass = "status-revision";
                            }
                            ?>
                            <div class="card-pendiente pending-card <?= $cardStatusClass; ?>">
                                <!-- Columna de Información -->
                                <div class="flex-grow-1 mr-lg-4 mb-3 mb-lg-0">
                                    <div class="d-flex align-items-center mb-2 flex-wrap" style="gap: 6px;">
                                        <span class="badge <?= $item['claseEstado']; ?> px-2.5 py-1" style="border-radius: 6px; font-weight: 600; font-size: 0.75rem;">
                                            <?= htmlspecialchars($item['estado']); ?>
                                        </span>
                                        <span class="text-muted" style="font-size: 0.8rem;">Cód: <?= htmlspecialchars($item['id']); ?></span>
                                        <span class="text-muted" style="font-size: 0.8rem; margin-left: auto;">Año: <?= htmlspecialchars($item['anio']); ?></span>
                                    </div>
                                    
                                    <h5 class="nombre-asignatura mb-2"><?= htmlspecialchars($item['nombre']); ?></h5>
                                    
                                    <div class="d-flex flex-wrap" style="gap: 8px;">
                                        <span class="detail-tag">
                                            <span class="oi oi-book mr-1.5" style="font-size: 0.75rem;"></span>
                                            <?php 
                                            $carrerasArr = $item['carreras'];
                                            if (empty($carrerasArr)) {
                                                echo "-";
                                            } elseif (count($carrerasArr) <= 2) {
                                                echo htmlspecialchars(implode(", ", $carrerasArr));
                                            } else {
                                                echo htmlspecialchars($carrerasArr[0] . ", " . $carrerasArr[1]);
                                                $restantes = count($carrerasArr) - 2;
                                                $todosTexto = htmlspecialchars(implode(", ", $carrerasArr));
                                                echo ' <span class="badge badge-info ml-1 px-2 py-1" style="cursor: pointer; background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); font-size: 0.75rem; font-weight: 600;" data-toggle="tooltip" data-placement="top" title="' . $todosTexto . '">+' . $restantes . ' más</span>';
                                            }
                                            ?>
                                        </span>
                                    </div>

                                    <?php if (!empty($item['motivo'])): ?>
                                        <div class="observaciones-box">
                                            <strong><span class="oi oi-warning mr-1"></span> Detalle / Observación:</strong><br>
                                            <?= $item['motivo']; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Columna del Stepper -->
                                <div class="mb-3 mb-lg-0 px-lg-3 text-center" style="min-width: 260px; max-width: 320px; align-self: center;">
                                    <div class="text-muted mb-1 text-uppercase font-weight-bold" style="font-size: 0.65rem; letter-spacing: 0.05em;">Progreso del Circuito</div>
                                    <?= renderizarStepperInicio($item['data'], $item['id']); ?>
                                </div>
                                
                                <!-- Columna de Acción -->
                                <div class="text-lg-right min-width-button" style="align-self: center; min-width: 170px;">
                                    <?php if ($item['accion'] == 'Modificar Borrador' || $item['accion'] == 'Corregir Programa'): ?>
                                        <a href="<?= $item['link']; ?>" class="btn-premium-action btn-premium-warning btn-block mb-2">
                                            <span class="oi oi-pencil mr-1.5"></span> <?= htmlspecialchars($item['accion']); ?>
                                        </a>
                                        <?php if ($item['estado'] == 'Borrador'): ?>
                                            <button type="button" class="btn-premium-action btn-premium-primary btn-block text-white" onclick="enviarARevisionDirecto(<?= $item['original_id']; ?>)">
                                                <span class="oi oi-share mr-1.5"></span> Enviar a Revisión
                                            </button>
                                        <?php endif; ?>
                                    <?php elseif ($item['accion'] == 'Subir Programa'): ?>
                                        <a href="<?= $item['link']; ?>" class="btn-premium-action btn-premium-success btn-block">
                                            <span class="oi oi-plus mr-1.5"></span> <?= htmlspecialchars($item['accion']); ?>
                                        </a>
                                    <?php elseif ($item['accion'] == 'Enviar a Vinculación' || $item['accion'] == 'Enviar a Departamento' || $item['accion'] == 'Enviar a VA (Firma Final)'): ?>
                                        <a href="<?= $item['link']; ?>" class="btn-premium-action btn-premium-warning btn-block text-white">
                                            <span class="oi oi-share mr-1.5"></span> <?= htmlspecialchars($item['accion']); ?>
                                        </a>
                                    <?php elseif ($item['accion'] == 'Confirmar Firma Final'): ?>
                                        <a href="<?= $item['link']; ?>" class="btn-premium-action btn-premium-success btn-block">
                                            <span class="oi oi-check mr-1.5"></span> <?= htmlspecialchars($item['accion']); ?>
                                        </a>
                                    <?php else: ?>
                                        <a href="<?= $item['link']; ?>" class="btn-premium-action btn-premium-primary btn-block">
                                            <span class="oi oi-check mr-1.5"></span> <?= htmlspecialchars($item['accion']); ?>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <div class="mx-auto bg-light rounded-circle d-flex align-items-center justify-content-center mb-3" style="width: 72px; height: 72px;">
                            <span class="oi oi-check text-success" style="font-size: 2.2rem;"></span>
                        </div>
                        <h5 class="font-weight-bold text-dark mb-1">¡Buzón al día!</h5>
                        <p class="text-muted mb-0">No tienes tareas de revisión o firmas pendientes que requieran tu acción inmediata.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ACCESOS RÁPIDOS Y SECCIONES PREMIUM -->
        <h4 class="mt-4 mb-3 text-secondary font-weight-bold" style="font-size: 1.1rem; letter-spacing: 0.05em; text-transform: uppercase;">
            <span class="oi oi-grid-three-up mr-1"></span> Accesos Rápidos y Navegación
        </h4>

        <div class="row">
            <?php if ($rol == PermisosSistema::ROL_PROFESOR): ?>
                <div class="col-md-4 mb-4">
                    <div class="card card-acceso">
                        <div class="card-body p-4 d-flex flex-column h-100">
                            <div class="acceso-icon-container icon-blue">
                                <span class="oi oi-book"></span>
                            </div>
                            <h5 class="font-weight-bold text-dark mb-2">Mis Asignaturas</h5>
                            <p class="text-muted flex-grow-1" style="font-size: 0.9rem;">
                                Accede al listado de las materias que tienes asignadas para revisar el historial académico y planificar.
                            </p>
                            <a href="asignaturasDeProfesor.php" class="btn-premium-action btn-premium-primary align-self-start mt-3">
                                Acceder <span class="oi oi-arrow-right ml-1.5" style="font-size: 0.75rem;"></span>
                            </a>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="col-md-4 mb-4">
                    <div class="card card-acceso">
                        <div class="card-body p-4 d-flex flex-column h-100">
                            <div class="acceso-icon-container icon-blue">
                                <span class="oi oi-magnifying-glass"></span>
                            </div>
                            <h5 class="font-weight-bold text-dark mb-2">Búsqueda e Historial</h5>
                            <p class="text-muted flex-grow-1" style="font-size: 0.9rem;">
                                Busca asignaturas de planes vigentes por carrera, y consulta el historial completo de programas analíticos anteriores.
                            </p>
                            <a href="revisar.programas.php" class="btn-premium-action btn-premium-primary align-self-start mt-3">
                                Acceder <span class="oi oi-arrow-right ml-1.5" style="font-size: 0.75rem;"></span>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($rol == PermisosSistema::ROL_ADMIN || $rol == PermisosSistema::ROL_VINCULACION_ACADEMICA): ?>
                <div class="col-md-4 mb-4">
                    <div class="card card-acceso">
                        <div class="card-body p-4 d-flex flex-column h-100">
                            <div class="acceso-icon-container icon-purple">
                                <span class="oi oi-pulse"></span>
                            </div>
                            <h5 class="font-weight-bold text-dark mb-2">Monitoreo del Circuito</h5>
                            <p class="text-muted flex-grow-1" style="font-size: 0.9rem;">
                                Monitorea en tiempo real el estado de todos los programas analíticos de la institución y gestiona alertas de demora.
                            </p>
                            <a href="monitoreo.circuito.php" class="btn-premium-action btn-premium-primary align-self-start mt-3">
                                Acceder <span class="oi oi-arrow-right ml-1.5" style="font-size: 0.75rem;"></span>
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4 mb-4">
                    <div class="card card-acceso">
                        <div class="card-body p-4 d-flex flex-column h-100">
                            <div class="acceso-icon-container icon-pink">
                                <span class="oi oi-spreadsheet"></span>
                            </div>
                            <h5 class="font-weight-bold text-dark mb-2">Administrar Carreras</h5>
                            <p class="text-muted flex-grow-1" style="font-size: 0.9rem;">
                                Agrega, modifica y gestiona las carreras del establecimiento y sus respectivos planes de estudio.
                            </p>
                            <a href="carreras.php" class="btn-premium-action btn-premium-primary align-self-start mt-3">
                                Acceder <span class="oi oi-arrow-right ml-1.5" style="font-size: 0.75rem;"></span>
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4 mb-4">
                    <div class="card card-acceso">
                        <div class="card-body p-4 d-flex flex-column h-100">
                            <div class="acceso-icon-container icon-indigo">
                                <span class="oi oi-tags"></span>
                            </div>
                            <h5 class="font-weight-bold text-dark mb-2">Asociar Departamentos</h5>
                            <p class="text-muted flex-grow-1" style="font-size: 0.9rem;">
                                Vincula las materias con sus correspondientes departamentos académicos para el flujo correcto de las firmas.
                            </p>
                            <a href="asignaturas.departamentos.php" class="btn-premium-action btn-premium-primary align-self-start mt-3">
                                Acceder <span class="oi oi-arrow-right ml-1.5" style="font-size: 0.75rem;"></span>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

    </div>

    <?php include_once '../gui/footer.php'; ?>

    <!-- Formulario POST Oculto para Enviar a Revisión (solo profesores) -->
    <form id="formEnviarRevision" action="../controlSistema/programa.enviar.revision.php" method="POST" style="display:none;">
        <input type="hidden" name="idPrograma" id="idProgramaEnviar">
    </form>

    <script>
        function enviarARevisionDirecto(idPrograma) {
            if (confirm('¿Está seguro de enviar este programa a revisión? Una vez enviado, no podrá realizar cambios ni subir archivos hasta que sea revisado por los evaluadores.')) {
                $("#idProgramaEnviar").val(idPrograma);
                $("#formEnviarRevision").submit();
            }
        }
        $(function () {
            $('[data-toggle="tooltip"]').tooltip();
        });
    </script>
</body>
</html>